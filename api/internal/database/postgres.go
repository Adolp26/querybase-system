package database

import (
	"context"
	"database/sql"
	"fmt"
	"time"

	"github.com/adolp26/querybase/internal/models"
	_ "github.com/jackc/pgx/v5/stdlib"
)

type PostgresClient struct {
	db     *sql.DB
	config models.PostgresConfig
}

func NewPostgresClient(cfg models.PostgresConfig) (*PostgresClient, error) {
	dsn := fmt.Sprintf(
		"postgres://%s:%s@%s:%s/%s?sslmode=%s",
		cfg.Username,
		cfg.Password,
		cfg.Host,
		cfg.Port,
		cfg.Database,
		cfg.SSLMode,
	)

	db, err := sql.Open("pgx", dsn)
	if err != nil {
		return nil, fmt.Errorf("erro ao abrir conexão PostgreSQL: %w", err)
	}

	if err := db.Ping(); err != nil {
		return nil, fmt.Errorf("erro ao conectar no PostgreSQL: %w", err)
	}

	db.SetMaxOpenConns(10)
	db.SetMaxIdleConns(5)
	db.SetConnMaxLifetime(5 * time.Minute)

	return &PostgresClient{
		db:     db,
		config: cfg,
	}, nil
}

func (p *PostgresClient) GetDB() *sql.DB {
	return p.db
}

func (p *PostgresClient) Ping(ctx context.Context) error {
	return p.db.PingContext(ctx)
}

func (p *PostgresClient) Close() error {
	return p.db.Close()
}
