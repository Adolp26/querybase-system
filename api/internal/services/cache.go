package services

import (
	"context"
	"encoding/json"
	"fmt"

	"github.com/adolp26/querybase/internal/database"
)

type CacheService struct {
	redis *database.RedisClient
}

func NewCacheService(redis *database.RedisClient) *CacheService {
	return &CacheService{
		redis: redis,
	}
}

func (s *CacheService) GetOrSet(
	ctx context.Context,
	key string,
	ttlSeconds int,
	fetchFunc func() (interface{}, error),
) (interface{}, error) {

	cached, err := s.redis.Get(ctx, key)
	if err == nil {
		var result interface{}
		if err := json.Unmarshal([]byte(cached), &result); err != nil {
			return nil, fmt.Errorf("erro ao decodificar cache: %w", err)
		}
		fmt.Printf("Cache HIT: %s\n", key)
		return result, nil
	}

	fmt.Printf("❌ Cache MISS: %s - Buscando dados...\n", key)
	data, err := fetchFunc()
	if err != nil {
		return nil, err
	}

	jsonData, err := json.Marshal(data)
	if err != nil {
		fmt.Printf("Erro ao serializar cache: %v\n", err)
		return data, nil
	}

	if err := s.redis.Set(ctx, key, string(jsonData), ttlSeconds); err != nil {
		fmt.Printf("Erro ao salvar cache: %v\n", err)
	}

	return data, nil
}
