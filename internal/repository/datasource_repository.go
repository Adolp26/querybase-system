package repository

import (
	"context"
	"database/sql"
	"fmt"

	"github.com/adolp26/querybase/internal/database"
)

type DatasourceRepository struct {
	db *sql.DB
}

func NewDatasourceRepository(db *sql.DB) *DatasourceRepository {
	return &DatasourceRepository{db: db}
}

func (r *DatasourceRepository) FindByID(ctx context.Context, id string) (*database.DatasourceConfig, error) {
	query := `
		SELECT
			id, slug, driver, host, port,
			database_name, username, password,
			max_open_conns, max_idle_conns
		FROM datasources
		WHERE id = $1 AND is_active = true
	`

	var ds database.DatasourceConfig
	var port int

	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&ds.ID, &ds.Slug, &ds.Driver, &ds.Host, &port,
		&ds.Database, &ds.Username, &ds.Password,
		&ds.MaxOpenConns, &ds.MaxIdleConns,
	)

	if err == sql.ErrNoRows {
		return nil, fmt.Errorf("datasource '%s' nao encontrado ou inativo", id)
	}
	if err != nil {
		return nil, fmt.Errorf("erro ao buscar datasource: %w", err)
	}

	ds.Port = port

	return &ds, nil
}

func (r *DatasourceRepository) FindBySlug(ctx context.Context, slug string) (*database.DatasourceConfig, error) {
	query := `
		SELECT
			id, slug, driver, host, port,
			database_name, username, password,
			max_open_conns, max_idle_conns
		FROM datasources
		WHERE slug = $1 AND is_active = true
	`

	var ds database.DatasourceConfig
	var port int

	err := r.db.QueryRowContext(ctx, query, slug).Scan(
		&ds.ID, &ds.Slug, &ds.Driver, &ds.Host, &port,
		&ds.Database, &ds.Username, &ds.Password,
		&ds.MaxOpenConns, &ds.MaxIdleConns,
	)

	if err == sql.ErrNoRows {
		return nil, fmt.Errorf("datasource '%s' nao encontrado ou inativo", slug)
	}
	if err != nil {
		return nil, fmt.Errorf("erro ao buscar datasource: %w", err)
	}

	ds.Port = port

	return &ds, nil
}

func (r *DatasourceRepository) ListActive(ctx context.Context) ([]database.DatasourceConfig, error) {
	query := `
		SELECT
			id, slug, driver, host, port,
			database_name, username, password,
			max_open_conns, max_idle_conns
		FROM datasources
		WHERE is_active = true
		ORDER BY name ASC
	`

	rows, err := r.db.QueryContext(ctx, query)
	if err != nil {
		return nil, fmt.Errorf("erro ao listar datasources: %w", err)
	}
	defer rows.Close()

	var datasources []database.DatasourceConfig

	for rows.Next() {
		var ds database.DatasourceConfig
		var port int

		err := rows.Scan(
			&ds.ID, &ds.Slug, &ds.Driver, &ds.Host, &port,
			&ds.Database, &ds.Username, &ds.Password,
			&ds.MaxOpenConns, &ds.MaxIdleConns,
		)
		if err != nil {
			return nil, fmt.Errorf("erro ao ler datasource: %w", err)
		}

		ds.Port = port
		datasources = append(datasources, ds)
	}

	return datasources, nil
}
