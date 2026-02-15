package handlers

import (
	"context"
	"encoding/json"
	"fmt"
	"net/http"
	"strconv"
	"time"

	"github.com/adolp26/querybase/internal/database"
	"github.com/adolp26/querybase/internal/models"
	"github.com/adolp26/querybase/internal/repository"
	"github.com/adolp26/querybase/internal/services"
	"github.com/gin-gonic/gin"
)



type DynamicQueryHandler struct {
	queryRepo      *repository.QueryRepository
	datasourceRepo *repository.DatasourceRepository
	connManager    *database.ConnectionManager
	cacheService   *services.CacheService
}

func NewDynamicQueryHandler(
	queryRepo *repository.QueryRepository,
	datasourceRepo *repository.DatasourceRepository,
	connManager *database.ConnectionManager,
	cacheService *services.CacheService,
) *DynamicQueryHandler {
	return &DynamicQueryHandler{
		queryRepo:      queryRepo,
		datasourceRepo: datasourceRepo,
		connManager:    connManager,
		cacheService:   cacheService,
	}
}

// GET /api/query/:slug?param1=value1&param2=value2
func (h *DynamicQueryHandler) Execute(c *gin.Context) {
	slug := c.Param("slug")
	startTime := time.Now()

	ctx := c.Request.Context()

	query, err := h.queryRepo.FindBySlug(ctx, slug)
	if err != nil {
		c.JSON(http.StatusNotFound, gin.H{
			"error":   "Query nao encontrada",
			"slug":    slug,
			"details": err.Error(),
		})
		return
	}

	if query.DatasourceID == nil || *query.DatasourceID == "" {
		c.JSON(http.StatusBadRequest, gin.H{
			"error": "Query nao possui datasource vinculado",
			"slug":  slug,
		})
		return
	}

	datasource, err := h.datasourceRepo.FindByID(ctx, *query.DatasourceID)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":   "Erro ao buscar datasource",
			"slug":    slug,
			"details": err.Error(),
		})
		return
	}

	params, validationErrors := h.extractAndValidateParams(c, query)
	if len(validationErrors) > 0 {
		c.JSON(http.StatusBadRequest, gin.H{
			"error":      "Parametros invalidos",
			"slug":       slug,
			"validation": validationErrors,
		})
		return
	}

	cacheKey := h.buildCacheKey(slug, c, query.Parameters)
	args := h.buildQueryArgs(params, query.Parameters)

	queryCtx, cancel := context.WithTimeout(ctx, time.Duration(query.TimeoutSeconds)*time.Second)
	defer cancel()

	results, cacheHit, err := h.executeWithCache(queryCtx, cacheKey, query.CacheTTL, query, datasource, args)
	duration := time.Since(startTime)

	go h.logExecution(query, params, duration, cacheHit, results, err, c)

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error":      "Erro ao executar query",
			"slug":       slug,
			"datasource": datasource.Slug,
			"details":    err.Error(),
			"duration":   duration.String(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"data": results,
		"meta": gin.H{
			"slug":       slug,
			"name":       query.Name,
			"datasource": datasource.Slug,
			"driver":     datasource.Driver,
			"count":      len(results),
			"cache_hit":  cacheHit,
			"duration":   duration.String(),
			"parameters": params,
		},
	})
}

func (h *DynamicQueryHandler) executeWithCache(
	ctx context.Context,
	cacheKey string,
	cacheTTL int,
	query *models.Query,
	datasource *database.DatasourceConfig,
	args []interface{},
) ([]map[string]interface{}, bool, error) {
	var cacheHit bool = true
	var results []map[string]interface{}

	data, err := h.cacheService.GetOrSet(ctx, cacheKey, cacheTTL, func() (interface{}, error) {
		cacheHit = false
		fmt.Printf("[Query] Executando '%s' no datasource '%s' (%s)...\n",
			query.Slug, datasource.Slug, datasource.Driver)

		return h.connManager.Query(ctx, *datasource, query.SQLQuery, args...)
	})

	if err != nil {
		return nil, false, err
	}

	if cacheHit {
		fmt.Printf("[Cache] HIT para query '%s'\n", query.Slug)
	}

	jsonData, err := json.Marshal(data)
	if err != nil {
		return nil, cacheHit, err
	}

	if err := json.Unmarshal(jsonData, &results); err != nil {
		return nil, cacheHit, err
	}

	return results, cacheHit, nil
}

func (h *DynamicQueryHandler) extractAndValidateParams(
	c *gin.Context,
	query *models.Query,
) (map[string]interface{}, map[string]string) {
	params := make(map[string]interface{})
	errors := make(map[string]string)

	for _, p := range query.Parameters {
		rawValue := c.Query(p.Name)

		if rawValue == "" {
			if p.IsRequired {
				errors[p.Name] = "parametro obrigatorio nao fornecido"
				continue
			}
			if p.DefaultValue != nil {
				rawValue = *p.DefaultValue
			} else {
				continue
			}
		}

		converted, err := h.convertParamType(rawValue, p.ParamType)
		if err != nil {
			errors[p.Name] = fmt.Sprintf("tipo invalido: esperado %s, erro: %s", p.ParamType, err.Error())
			continue
		}

		params[p.Name] = converted
	}

	return params, errors
}

func (h *DynamicQueryHandler) convertParamType(value string, paramType string) (interface{}, error) {
	switch paramType {
	case "string":
		return value, nil
	case "integer":
		return strconv.Atoi(value)
	case "number":
		return strconv.ParseFloat(value, 64)
	case "boolean":
		return strconv.ParseBool(value)
	case "date":
		return time.Parse("2006-01-02", value)
	case "datetime":
		return time.Parse("2006-01-02 15:04:05", value)
	default:
		return value, nil
	}
}

func (h *DynamicQueryHandler) buildCacheKey(
	slug string,
	c *gin.Context,
	definitions []models.QueryParameter,
) string {
	key := fmt.Sprintf("query:%s", slug)

	for _, def := range definitions {
		rawValue := c.Query(def.Name)
		if rawValue == "" && def.DefaultValue != nil {
			rawValue = *def.DefaultValue
		}
		key += fmt.Sprintf(":%s=%s", def.Name, rawValue)
	}

	return key
}

func (h *DynamicQueryHandler) buildQueryArgs(
	params map[string]interface{},
	definitions []models.QueryParameter,
) []interface{} {
	maxPos := 0
	for _, def := range definitions {
		if def.Position > maxPos {
			maxPos = def.Position
		}
	}

	if maxPos == 0 {
		return nil
	}

	args := make([]interface{}, maxPos)

	for _, def := range definitions {
		if val, ok := params[def.Name]; ok {
			args[def.Position-1] = val
		}
	}

	return args
}

func (h *DynamicQueryHandler) logExecution(
	query *models.Query,
	params map[string]interface{},
	duration time.Duration,
	cacheHit bool,
	results []map[string]interface{},
	execError error,
	c *gin.Context,
) {
	paramsJSON, _ := json.Marshal(params)

	var errMsg *string
	if execError != nil {
		msg := execError.Error()
		errMsg = &msg
	}

	execution := models.QueryExecution{
		QueryID:    &query.ID,
		QuerySlug:  query.Slug,
		DurationMs: int(duration.Milliseconds()),
		CacheHit:   cacheHit,
		RowCount:   len(results),
		Parameters: string(paramsJSON),
		Error:      errMsg,
		ClientIP:   stringPtr(c.ClientIP()),
		UserAgent:  stringPtr(c.Request.UserAgent()),
	}

	ctx, cancel := context.WithTimeout(context.Background(), 5*time.Second)
	defer cancel()

	if err := h.queryRepo.LogExecution(ctx, execution); err != nil {
		fmt.Printf("[Log] Erro ao registrar execucao: %v\n", err)
	}
}

func stringPtr(s string) *string {
	return &s
}

// ListQueries lista todas as queries ativas.
// GET /api/queries
func (h *DynamicQueryHandler) ListQueries(c *gin.Context) {
	ctx := c.Request.Context()

	queries, err := h.queryRepo.ListActive(ctx)
	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error": "Erro ao listar queries",
		})
		return
	}

	var endpoints []gin.H
	for _, q := range queries {
		endpoint := gin.H{
			"slug":        q.Slug,
			"name":        q.Name,
			"description": q.Description,
			"endpoint":    fmt.Sprintf("/api/query/%s", q.Slug),
			"cache_ttl":   q.CacheTTL,
			"datasource":  q.DatasourceSlug,
			"parameters":  q.Parameters,
		}
		endpoints = append(endpoints, endpoint)
	}

	c.JSON(http.StatusOK, gin.H{
		"queries": endpoints,
		"count":   len(endpoints),
	})
}
