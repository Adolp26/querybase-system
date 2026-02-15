package models

import (
	"time"
)

type Query struct {
	ID             string           `json:"id" db:"id"`
	Slug           string           `json:"slug" db:"slug"`
	Name           string           `json:"name" db:"name"`
	Description    *string          `json:"description,omitempty" db:"description"`
	SQLQuery       string           `json:"sql_query" db:"sql_query"`
	DatasourceID   *string          `json:"datasource_id,omitempty" db:"datasource_id"`
	CacheTTL       int              `json:"cache_ttl" db:"cache_ttl"`
	TimeoutSeconds int              `json:"timeout_seconds" db:"timeout_seconds"`
	IsActive       bool             `json:"is_active" db:"is_active"`
	CreatedAt      time.Time        `json:"created_at" db:"created_at"`
	UpdatedAt      time.Time        `json:"updated_at" db:"updated_at"`
	CreatedBy      *string          `json:"created_by,omitempty" db:"created_by"`
	UpdatedBy      *string          `json:"updated_by,omitempty" db:"updated_by"`
	Parameters     []QueryParameter `json:"parameters,omitempty" db:"-"`
	DatasourceSlug *string          `json:"datasource_slug,omitempty" db:"datasource_slug"`
	DatasourceName *string          `json:"datasource_name,omitempty" db:"datasource_name"`
}

type QueryParameter struct {
	ID           string    `json:"id" db:"id"`
	QueryID      string    `json:"query_id" db:"query_id"`
	Name         string    `json:"name" db:"name"`
	ParamType    string    `json:"param_type" db:"param_type"`
	IsRequired   bool      `json:"is_required" db:"is_required"`
	DefaultValue *string   `json:"default_value,omitempty" db:"default_value"`
	Description  *string   `json:"description,omitempty" db:"description"`
	Position     int       `json:"position" db:"position"`
	Validations  *string   `json:"validations,omitempty" db:"validations"`
	CreatedAt    time.Time `json:"created_at" db:"created_at"`
}

type QueryExecution struct {
	ID         string    `json:"id" db:"id"`
	QueryID    *string   `json:"query_id" db:"query_id"`
	QuerySlug  string    `json:"query_slug" db:"query_slug"`
	ExecutedAt time.Time `json:"executed_at" db:"executed_at"`
	DurationMs int       `json:"duration_ms" db:"duration_ms"`
	CacheHit   bool      `json:"cache_hit" db:"cache_hit"`
	RowCount   int       `json:"row_count" db:"row_count"`
	Parameters string    `json:"parameters" db:"parameters"`
	Error      *string   `json:"error,omitempty" db:"error"`
	ClientIP   *string   `json:"client_ip,omitempty" db:"client_ip"`
	UserAgent  *string   `json:"user_agent,omitempty" db:"user_agent"`
}

type Datasource struct {
	ID           string    `json:"id" db:"id"`
	Slug         string    `json:"slug" db:"slug"`
	Name         string    `json:"name" db:"name"`
	Driver       string    `json:"driver" db:"driver"`
	Host         string    `json:"host" db:"host"`
	Port         string    `json:"port" db:"port"`
	DatabaseName string    `json:"database_name" db:"database_name"`
	Username     string    `json:"-" db:"username"`
	Password     string    `json:"-" db:"password"`
	MaxOpenConns int       `json:"max_open_conns" db:"max_open_conns"`
	MaxIdleConns int       `json:"max_idle_conns" db:"max_idle_conns"`
	IsActive     bool      `json:"is_active" db:"is_active"`
	CreatedAt    time.Time `json:"created_at" db:"created_at"`
	UpdatedAt    time.Time `json:"updated_at" db:"updated_at"`
}

func (q *Query) GetParameterByPosition(position int) *QueryParameter {
	for i := range q.Parameters {
		if q.Parameters[i].Position == position {
			return &q.Parameters[i]
		}
	}
	return nil
}

func (q *Query) GetParameterByName(name string) *QueryParameter {
	for i := range q.Parameters {
		if q.Parameters[i].Name == name {
			return &q.Parameters[i]
		}
	}
	return nil
}

func (q *Query) HasRequiredParameters() bool {
	for _, p := range q.Parameters {
		if p.IsRequired {
			return true
		}
	}
	return false
}
