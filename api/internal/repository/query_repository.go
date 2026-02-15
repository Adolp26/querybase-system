package repository

import (
	"context"
	"database/sql"
	"fmt"

	"github.com/adolp26/querybase/internal/models"
)

type QueryRepository struct {
	db *sql.DB
}

func NewQueryRepository(db *sql.DB) *QueryRepository {
	return &QueryRepository{db: db}
}

func (r *QueryRepository) FindBySlug(ctx context.Context, slug string) (*models.Query, error) {
	query := `
		SELECT
			q.id, q.slug, q.name, q.description, q.sql_query,
			q.datasource_id, q.cache_ttl, q.timeout_seconds, q.is_active,
			q.created_at, q.updated_at, q.created_by, q.updated_by,
			d.slug as datasource_slug, d.name as datasource_name
		FROM queries q
		LEFT JOIN datasources d ON q.datasource_id = d.id
		WHERE q.slug = $1 AND q.is_active = true
	`

	row := r.db.QueryRowContext(ctx, query, slug)

	var q models.Query
	err := row.Scan(
		&q.ID, &q.Slug, &q.Name, &q.Description, &q.SQLQuery,
		&q.DatasourceID, &q.CacheTTL, &q.TimeoutSeconds, &q.IsActive,
		&q.CreatedAt, &q.UpdatedAt, &q.CreatedBy, &q.UpdatedBy,
		&q.DatasourceSlug, &q.DatasourceName,
	)

	if err == sql.ErrNoRows {
		return nil, fmt.Errorf("query '%s' não encontrada", slug)
	}
	if err != nil {
		return nil, fmt.Errorf("erro ao buscar query: %w", err)
	}

	params, err := r.FindParametersByQueryID(ctx, q.ID)
	if err != nil {
		return nil, fmt.Errorf("erro ao buscar parâmetros: %w", err)
	}
	q.Parameters = params

	return &q, nil
}

func (r *QueryRepository) FindParametersByQueryID(ctx context.Context, queryID string) ([]models.QueryParameter, error) {
	query := `
		SELECT id, query_id, name, param_type, is_required,
			   default_value, description, position, validations, created_at
		FROM query_parameters
		WHERE query_id = $1
		ORDER BY position ASC
	`

	rows, err := r.db.QueryContext(ctx, query, queryID)
	if err != nil {
		return nil, fmt.Errorf("erro ao buscar parâmetros: %w", err)
	}
	defer rows.Close()

	var params []models.QueryParameter
	for rows.Next() {
		var p models.QueryParameter
		err := rows.Scan(
			&p.ID, &p.QueryID, &p.Name, &p.ParamType, &p.IsRequired,
			&p.DefaultValue, &p.Description, &p.Position, &p.Validations, &p.CreatedAt,
		)
		if err != nil {
			return nil, fmt.Errorf("erro ao ler parâmetro: %w", err)
		}
		params = append(params, p)
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("erro ao iterar parâmetros: %w", err)
	}

	return params, nil
}

func (r *QueryRepository) ListActive(ctx context.Context) ([]models.Query, error) {
	query := `
		SELECT
			q.id, q.slug, q.name, q.description, q.sql_query,
			q.datasource_id, q.cache_ttl, q.timeout_seconds, q.is_active,
			q.created_at, q.updated_at, q.created_by, q.updated_by,
			d.slug as datasource_slug, d.name as datasource_name
		FROM queries q
		LEFT JOIN datasources d ON q.datasource_id = d.id
		WHERE q.is_active = true
		ORDER BY q.name ASC
	`

	rows, err := r.db.QueryContext(ctx, query)
	if err != nil {
		return nil, fmt.Errorf("erro ao listar queries: %w", err)
	}
	defer rows.Close()

	var queries []models.Query
	for rows.Next() {
		var q models.Query
		err := rows.Scan(
			&q.ID, &q.Slug, &q.Name, &q.Description, &q.SQLQuery,
			&q.DatasourceID, &q.CacheTTL, &q.TimeoutSeconds, &q.IsActive,
			&q.CreatedAt, &q.UpdatedAt, &q.CreatedBy, &q.UpdatedBy,
			&q.DatasourceSlug, &q.DatasourceName,
		)
		if err != nil {
			return nil, fmt.Errorf("erro ao ler query: %w", err)
		}
		queries = append(queries, q)
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("erro ao iterar queries: %w", err)
	}

	for i := range queries {
		params, err := r.FindParametersByQueryID(ctx, queries[i].ID)
		if err != nil {
			return nil, err
		}
		queries[i].Parameters = params
	}

	return queries, nil
}

func (r *QueryRepository) LogExecution(ctx context.Context, execution models.QueryExecution) error {
	query := `
		INSERT INTO query_executions (
			query_id, query_slug, duration_ms, cache_hit,
			row_count, parameters, error, client_ip, user_agent
		) VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9)
	`

	_, err := r.db.ExecContext(ctx, query,
		execution.QueryID, execution.QuerySlug, execution.DurationMs, execution.CacheHit,
		execution.RowCount, execution.Parameters, execution.Error,
		execution.ClientIP, execution.UserAgent,
	)

	if err != nil {
		return fmt.Errorf("erro ao logar execução: %w", err)
	}

	return nil
}
