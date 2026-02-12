package services

import (
	"context"
	"encoding/json"
	"fmt"
	"time"

	"github.com/adolp26/querybase/internal/database"
)

type QueryService struct {
	oracle *database.OracleDataSource
	cache  *CacheService
}

func NewQueryService(oracle *database.OracleDataSource, cache *CacheService) *QueryService {
	return &QueryService{
		oracle: oracle,
		cache:  cache,
	}
}

func (s *QueryService) ExecuteQuery(
	ctx context.Context,
	cacheKey string,
	query string,
	args ...interface{},
) ([]map[string]interface{}, error) {

	data, err := s.cache.GetOrSet(ctx, cacheKey, 0, func() (interface{}, error) {
		queryCtx, cancel := context.WithTimeout(ctx, 30*time.Second)
		defer cancel()

		fmt.Printf("Executando query no Oracle...\n")
		startTime := time.Now()

		results, err := s.oracle.Query(queryCtx, query, args...)
		if err != nil {
			return nil, err
		}

		duration := time.Since(startTime)
		fmt.Printf("Query executada em %v - %d registros\n", duration, len(results))

		return results, nil
	})

	if err != nil {
		return nil, err
	}

	jsonData, err := json.Marshal(data)
	if err != nil {
		return nil, err
	}

	var results []map[string]interface{}
	if err := json.Unmarshal(jsonData, &results); err != nil {
		return nil, err
	}

	return results, nil
}

func (s *QueryService) ExecuteQueryDirect(
	ctx context.Context,
	query string,
	args ...interface{},
) ([]map[string]interface{}, error) {
	startTime := time.Now()

	results, err := s.oracle.Query(ctx, query, args...)
	if err != nil {
		return nil, fmt.Errorf("erro ao executar query: %w", err)
	}

	duration := time.Since(startTime)
	fmt.Printf("Query executada em %v - %d registros\n", duration, len(results))

	return results, nil
}
