package handlers

import (
	"net/http"
	"time"

	"github.com/adolp26/querybase/internal/services"
	"github.com/gin-gonic/gin"
)

type TestHandler struct {
	cacheService *services.CacheService
}

func NewTestHandler(cacheService *services.CacheService) *TestHandler {
	return &TestHandler{
		cacheService: cacheService,
	}
}

func (h *TestHandler) GetTestData(c *gin.Context) {
	cacheKey := "test:data"

	data, err := h.cacheService.GetOrSet(
		c.Request.Context(),
		cacheKey,
		0,
		func() (interface{}, error) {
			time.Sleep(2 * time.Second)

			return map[string]interface{}{
				"message":    "Dados do Oracle (simulado)",
				"timestamp":  time.Now().Unix(),
				"from_cache": false,
			}, nil
		},
	)

	if err != nil {
		c.JSON(http.StatusInternalServerError, gin.H{
			"error": err.Error(),
		})
		return
	}

	result := data.(map[string]interface{})
	if _, exists := result["from_cache"]; !exists {
		result["from_cache"] = true
	}

	c.JSON(http.StatusOK, result)
}
