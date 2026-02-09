CREATE TABLE datasources (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug            VARCHAR(100) UNIQUE NOT NULL,
    name            VARCHAR(255) NOT NULL,
    driver          VARCHAR(50) NOT NULL DEFAULT 'oracle',
    host            VARCHAR(255) NOT NULL,
    port            VARCHAR(10) NOT NULL,
    database_name   VARCHAR(255) NOT NULL,
    username        VARCHAR(255) NOT NULL,
    password        VARCHAR(255) NOT NULL,
    max_open_conns  INTEGER DEFAULT 25,
    max_idle_conns  INTEGER DEFAULT 5,
    is_active       BOOLEAN DEFAULT true,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW()
);

CREATE INDEX idx_datasources_slug ON datasources(slug);

CREATE TABLE queries (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    slug            VARCHAR(100) UNIQUE NOT NULL,
    name            VARCHAR(255) NOT NULL,
    description     TEXT,
    sql_query       TEXT NOT NULL,
    datasource_id   UUID REFERENCES datasources(id) ON DELETE SET NULL,
    cache_ttl       INTEGER DEFAULT 300,
    timeout_seconds INTEGER DEFAULT 30,
    is_active       BOOLEAN DEFAULT true,
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    updated_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    created_by      VARCHAR(255),
    updated_by      VARCHAR(255)
);

CREATE INDEX idx_queries_slug ON queries(slug);
CREATE INDEX idx_queries_datasource ON queries(datasource_id);
CREATE INDEX idx_queries_active ON queries(is_active);

CREATE TABLE query_parameters (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    query_id        UUID NOT NULL REFERENCES queries(id) ON DELETE CASCADE,
    name            VARCHAR(100) NOT NULL,
    param_type      VARCHAR(50) NOT NULL DEFAULT 'string',
    is_required     BOOLEAN DEFAULT false,
    default_value   VARCHAR(255),
    description     TEXT,
    position        INTEGER NOT NULL,
    validations     JSONB DEFAULT '{}',
    created_at      TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    UNIQUE(query_id, name),
    UNIQUE(query_id, position)
);

CREATE INDEX idx_query_parameters_query ON query_parameters(query_id);

CREATE TABLE query_executions (
    id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
    query_id        UUID REFERENCES queries(id) ON DELETE SET NULL,
    query_slug      VARCHAR(100) NOT NULL,
    executed_at     TIMESTAMP WITH TIME ZONE DEFAULT NOW(),
    duration_ms     INTEGER,
    cache_hit       BOOLEAN DEFAULT false,
    row_count       INTEGER,
    parameters      JSONB DEFAULT '{}',
    error           TEXT,
    client_ip       VARCHAR(45),
    user_agent      VARCHAR(500)
);

CREATE INDEX idx_query_executions_query ON query_executions(query_id);
CREATE INDEX idx_query_executions_date ON query_executions(executed_at);
CREATE INDEX idx_query_executions_errors ON query_executions(query_id) WHERE error IS NOT NULL;

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_datasources_updated_at
    BEFORE UPDATE ON datasources
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_queries_updated_at
    BEFORE UPDATE ON queries
    FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

INSERT INTO datasources (slug, name, driver, host, port, database_name, username, password)
VALUES (
    'oracle-principal',
    'Oracle Principal',
    'oracle',
    'localhost',
    '1521',
    'XEPDB1',
    'querybase',
    'querybase123'
);

INSERT INTO queries (slug, name, description, sql_query, cache_ttl, timeout_seconds)
VALUES (
    'employees-all',
    'Listar Todos os Funcionarios',
    'Retorna os primeiros 100 funcionarios cadastrados no sistema.',
    'SELECT employee_id, first_name, last_name, email, department_id FROM employees WHERE ROWNUM <= 100',
    300,
    30
);

INSERT INTO queries (slug, name, description, sql_query, cache_ttl, timeout_seconds)
VALUES (
    'employees-by-department',
    'Funcionarios por Departamento',
    'Retorna todos os funcionarios de um departamento especifico.',
    'SELECT employee_id, first_name, last_name, email, department_id FROM employees WHERE department_id = :1',
    300,
    30
);

INSERT INTO query_parameters (query_id, name, param_type, is_required, description, position)
SELECT id, 'department', 'integer', true, 'ID do departamento (obrigatorio)', 1
FROM queries WHERE slug = 'employees-by-department';

CREATE VIEW vw_queries_with_params AS
SELECT
    q.id, q.slug, q.name, q.description, q.sql_query,
    q.cache_ttl, q.timeout_seconds, q.is_active, q.created_at,
    d.slug as datasource_slug, d.name as datasource_name,
    COALESCE(
        json_agg(
            json_build_object(
                'name', p.name,
                'type', p.param_type,
                'required', p.is_required,
                'default', p.default_value,
                'description', p.description,
                'position', p.position
            )
            ORDER BY p.position
        ) FILTER (WHERE p.id IS NOT NULL),
        '[]'
    ) as parameters
FROM queries q
LEFT JOIN datasources d ON q.datasource_id = d.id
LEFT JOIN query_parameters p ON q.id = p.query_id
GROUP BY q.id, d.slug, d.name;
