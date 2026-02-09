package database

import (
	"context"
	"database/sql"
	"fmt"
	"sync"
	"time"

	_ "github.com/go-sql-driver/mysql"
	_ "github.com/lib/pq"
	_ "github.com/sijms/go-ora/v2"
)

type DatasourceConfig struct {
	ID           string `json:"id"`
	Slug         string `json:"slug"`
	Driver       string `json:"driver"`
	Host         string `json:"host"`
	Port         int    `json:"port"`
	Database     string `json:"database"`
	Username     string `json:"username"`
	Password     string `json:"password"`
	MaxOpenConns int    `json:"max_open_conns"`
	MaxIdleConns int    `json:"max_idle_conns"`
}

type ConnectionManager struct {
	connections map[string]*sql.DB
	mu          sync.RWMutex
}

type ConnectionTestResult struct {
	Success       bool   `json:"success"`
	Message       string `json:"message"`
	DurationMs    int    `json:"duration_ms"`
	ServerVersion string `json:"server_version,omitempty"`
}

func NewConnectionManager() *ConnectionManager {
	return &ConnectionManager{
		connections: make(map[string]*sql.DB),
	}
}

func (cm *ConnectionManager) GetConnection(ctx context.Context, config DatasourceConfig) (*sql.DB, error) {
	cm.mu.RLock()
	if conn, exists := cm.connections[config.ID]; exists {
		cm.mu.RUnlock()
		if err := conn.PingContext(ctx); err == nil {
			return conn, nil
		}
		cm.closeConnection(config.ID)
	} else {
		cm.mu.RUnlock()
	}

	return cm.createConnection(ctx, config)
}

func (cm *ConnectionManager) createConnection(ctx context.Context, config DatasourceConfig) (*sql.DB, error) {
	connString, driverName, err := cm.buildConnectionString(config)
	if err != nil {
		return nil, fmt.Errorf("erro ao montar connection string: %w", err)
	}

	db, err := sql.Open(driverName, connString)
	if err != nil {
		return nil, fmt.Errorf("erro ao abrir conexao %s: %w", config.Driver, err)
	}

	maxOpen := config.MaxOpenConns
	if maxOpen <= 0 {
		maxOpen = 25
	}
	maxIdle := config.MaxIdleConns
	if maxIdle <= 0 {
		maxIdle = 5
	}

	db.SetMaxOpenConns(maxOpen)
	db.SetMaxIdleConns(maxIdle)
	db.SetConnMaxLifetime(5 * time.Minute)
	db.SetConnMaxIdleTime(2 * time.Minute)

	if err := db.PingContext(ctx); err != nil {
		db.Close()
		return nil, fmt.Errorf("erro ao conectar em %s://%s:%d: %w", config.Driver, config.Host, config.Port, err)
	}

	cm.mu.Lock()
	cm.connections[config.ID] = db
	cm.mu.Unlock()

	fmt.Printf("[ConnectionManager] Nova conexao criada: %s (%s)\n", config.Slug, config.Driver)

	return db, nil
}

func (cm *ConnectionManager) buildConnectionString(config DatasourceConfig) (string, string, error) {
	switch config.Driver {
	case "oracle":
		connStr := fmt.Sprintf(
			"oracle://%s:%s@%s:%d/%s",
			config.Username,
			config.Password,
			config.Host,
			config.Port,
			config.Database,
		)
		return connStr, "oracle", nil

	case "postgres", "postgresql":
		connStr := fmt.Sprintf(
			"host=%s port=%d user=%s password=%s dbname=%s sslmode=disable",
			config.Host,
			config.Port,
			config.Username,
			config.Password,
			config.Database,
		)
		return connStr, "postgres", nil

	case "mysql":
		connStr := fmt.Sprintf(
			"%s:%s@tcp(%s:%d)/%s?parseTime=true&loc=Local",
			config.Username,
			config.Password,
			config.Host,
			config.Port,
			config.Database,
		)
		return connStr, "mysql", nil

	default:
		return "", "", fmt.Errorf("driver nao suportado: %s", config.Driver)
	}
}

func (cm *ConnectionManager) TestConnection(ctx context.Context, config DatasourceConfig) (*ConnectionTestResult, error) {
	startTime := time.Now()

	connString, driverName, err := cm.buildConnectionString(config)
	if err != nil {
		return nil, err
	}

	db, err := sql.Open(driverName, connString)
	if err != nil {
		return nil, fmt.Errorf("erro ao abrir conexao: %w", err)
	}
	defer db.Close()

	if err := db.PingContext(ctx); err != nil {
		return nil, fmt.Errorf("erro ao conectar: %w", err)
	}

	duration := time.Since(startTime)
	serverVersion := cm.getServerVersion(ctx, db, config.Driver)

	return &ConnectionTestResult{
		Success:       true,
		Message:       "Conexao estabelecida com sucesso",
		DurationMs:    int(duration.Milliseconds()),
		ServerVersion: serverVersion,
	}, nil
}

func (cm *ConnectionManager) getServerVersion(ctx context.Context, db *sql.DB, driver string) string {
	var query string

	switch driver {
	case "oracle":
		query = "SELECT BANNER FROM V$VERSION WHERE ROWNUM = 1"
	case "postgres", "postgresql":
		query = "SELECT version()"
	case "mysql":
		query = "SELECT VERSION()"
	default:
		return ""
	}

	var version string
	row := db.QueryRowContext(ctx, query)
	if err := row.Scan(&version); err != nil {
		return ""
	}

	if len(version) > 100 {
		version = version[:100] + "..."
	}

	return version
}

func (cm *ConnectionManager) closeConnection(datasourceID string) {
	cm.mu.Lock()
	defer cm.mu.Unlock()

	if conn, exists := cm.connections[datasourceID]; exists {
		conn.Close()
		delete(cm.connections, datasourceID)
		fmt.Printf("[ConnectionManager] Conexao fechada: %s\n", datasourceID)
	}
}

func (cm *ConnectionManager) CloseAll() {
	cm.mu.Lock()
	defer cm.mu.Unlock()

	for id, conn := range cm.connections {
		conn.Close()
		fmt.Printf("[ConnectionManager] Conexao fechada: %s\n", id)
	}

	cm.connections = make(map[string]*sql.DB)
}

func (cm *ConnectionManager) Query(ctx context.Context, config DatasourceConfig, sqlQuery string, args ...interface{}) ([]map[string]interface{}, error) {
	db, err := cm.GetConnection(ctx, config)
	if err != nil {
		return nil, err
	}

	rows, err := db.QueryContext(ctx, sqlQuery, args...)
	if err != nil {
		return nil, fmt.Errorf("erro ao executar query: %w", err)
	}
	defer rows.Close()

	columns, err := rows.Columns()
	if err != nil {
		return nil, fmt.Errorf("erro ao obter colunas: %w", err)
	}

	var results []map[string]interface{}

	for rows.Next() {
		values := make([]interface{}, len(columns))
		valuePtrs := make([]interface{}, len(columns))

		for i := range values {
			valuePtrs[i] = &values[i]
		}

		if err := rows.Scan(valuePtrs...); err != nil {
			return nil, fmt.Errorf("erro ao ler linha: %w", err)
		}

		row := make(map[string]interface{})
		for i, col := range columns {
			val := values[i]
			if b, ok := val.([]byte); ok {
				val = string(b)
			}
			row[col] = val
		}

		results = append(results, row)
	}

	if err := rows.Err(); err != nil {
		return nil, fmt.Errorf("erro ao iterar resultados: %w", err)
	}

	return results, nil
}
