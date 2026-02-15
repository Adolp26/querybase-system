package database

import (
	"context"
	"fmt"
	"time"

	"github.com/adolp26/querybase/internal/models"
	"github.com/redis/go-redis/v9"
)

type RedisClient struct {
	Client *redis.Client
	Config models.RedisConfig
}

func NewRedisClient(cfg models.RedisConfig) (*RedisClient, error) {
	client := redis.NewClient(&redis.Options{
		Addr:     fmt.Sprintf("%s:%s", cfg.Host, cfg.Port),
		Password: cfg.Password,
		DB:       cfg.DB, // Database 0 é o default
	})

	ctx := context.Background()
	if err := client.Ping(ctx).Err(); err != nil {
		return nil, fmt.Errorf("falha ao conectar no Redis: %w", err)
	}

	return &RedisClient{
		Client: client,
		Config: cfg,
	}, nil
}

func (r *RedisClient) Get(ctx context.Context, key string) (string, error) {
	val, err := r.Client.Get(ctx, key).Result()

	if err == redis.Nil {
		return "", fmt.Errorf("chave '%s' não encontrada", key)
	}

	if err != nil {
		return "", fmt.Errorf("erro ao buscar no Redis: %w", err)
	}

	return val, nil
}

func (r *RedisClient) Set(ctx context.Context, key string, value string, ttlSeconds ...int) error {
	ttl := time.Duration(r.Config.TTL) * time.Second
	if len(ttlSeconds) > 0 && ttlSeconds[0] > 0 {
		ttl = time.Duration(ttlSeconds[0]) * time.Second
	}

	err := r.Client.Set(ctx, key, value, ttl).Err()
	if err != nil {
		return fmt.Errorf("erro ao salvar no Redis: %w", err)
	}

	return nil
}

func (r *RedisClient) Close() error {
	return r.Client.Close()
}
