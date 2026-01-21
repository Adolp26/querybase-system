# QueryBase

API Gateway para analytics empresariais. Transforma queries SQL cadastradas em endpoints REST com cache inteligente.

## Arquitetura

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│  Power BI   │────▶│  API (Go)   │────▶│   Oracle    │
│  Analistas  │     │   :8080     │     │  (Dados)    │
└─────────────┘     └──────┬──────┘     └─────────────┘
                           │
              ┌────────────┼────────────┐
              ▼            ▼            ▼
        ┌──────────┐ ┌──────────┐ ┌──────────┐
        │  Redis   │ │ Postgres │ │  Laravel │
        │ (Cache)  │ │(Metadata)│ │  (Admin) │
        └──────────┘ └──────────┘ └──────────┘
```

## Stack

- **API**: Go 1.21+ com Gin
- **Cache**: Redis 7
- **Metadados**: PostgreSQL 16
- **Dados**: Oracle (produção)
- **Admin** (futuro): Laravel 10+

## Quick Start

```bash
# 1. Subir infraestrutura
cd docker
docker-compose up -d

# 2. Configurar (edite se necessário)
cp configs/config.yaml configs/config.local.yaml

# 3. Rodar API
go run ./cmd/api/main.go
```

## Endpoints

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/health` | Health check |
| GET | `/api/queries` | Lista queries disponíveis |
| GET | `/api/query/:slug` | Executa query dinâmica |
| GET | `/api/query/:slug?param=valor` | Executa com parâmetros |

## Exemplos

```bash
# Listar queries disponíveis
curl http://localhost:8080/api/queries

# Executar query sem parâmetros
curl http://localhost:8080/api/query/employees-all

# Executar query com parâmetros
curl "http://localhost:8080/api/query/employees-by-department?department=10"
```

## Resposta

```json
{
  "data": [
    {"employee_id": 1, "first_name": "John", "last_name": "Doe"}
  ],
  "meta": {
    "slug": "employees-all",
    "name": "Listar Todos os Funcionários",
    "count": 100,
    "cache_hit": true,
    "duration": "2.5ms"
  }
}
```

## Estrutura do Projeto

```
querybase/
├── cmd/api/main.go           # Entrada da aplicação
├── configs/config.example.yaml  # Configurações exemplo
├── docker/
│   ├── docker-compose.yml    # Redis + PostgreSQL
│   └── init-scripts/         # Schema inicial
├── internal/
│   ├── database/             # Conexões (Oracle, Redis, Postgres)
│   ├── handlers/             # HTTP handlers
│   ├── middleware/           # Auth, CORS, Rate Limit, Security
│   ├── models/               # Structs de dados
│   ├── repository/           # Acesso ao PostgreSQL
│   └── services/             # Lógica de negócio
└── pkg/config/               # Loader de configuração
```

## Banco de Dados

### PostgreSQL (Metadados)

| Tabela | Descrição |
|--------|-----------|
| `datasources` | Fontes de dados (Oracle, MySQL, etc) |
| `queries` | Queries SQL cadastradas |
| `query_parameters` | Parâmetros das queries |
| `query_executions` | Log de execuções |

### Cadastrar Nova Query

```sql
INSERT INTO queries (slug, name, description, sql_query, cache_ttl)
VALUES (
  'vendas-por-periodo',
  'Vendas por Período',
  'Total de vendas em um período',
  'SELECT * FROM vendas WHERE data BETWEEN :1 AND :2',
  600
);

INSERT INTO query_parameters (query_id, name, param_type, is_required, position)
SELECT id, 'data_inicio', 'date', true, 1 FROM queries WHERE slug = 'vendas-por-periodo';

INSERT INTO query_parameters (query_id, name, param_type, is_required, position)
SELECT id, 'data_fim', 'date', true, 2 FROM queries WHERE slug = 'vendas-por-periodo';
```

Uso:
```bash
curl "http://localhost:8080/api/query/vendas-por-periodo?data_inicio=2024-01-01&data_fim=2024-12-31"
```

## Configuração

```yaml
server:
  port: "8080"
  mode: debug  # debug ou release

redis:
  host: localhost
  port: "6379"
  ttl: 300  # segundos

oracle:
  host: localhost
  port: "1521"
  service: XEPDB1
  username: querybase
  password: querybase123

postgres:
  host: localhost
  port: "5432"
  database: querybase_metadata
  username: querybase
  password: querybase123
  sslmode: disable
```

## Cache

- Cada query pode ter TTL próprio (campo `cache_ttl`)
- Cache key inclui parâmetros: `query:slug:param1=valor1:param2=valor2`
- Cache hit/miss reportado no campo `meta.cache_hit`

## Tipos de Parâmetros

| Tipo | Formato | Exemplo |
|------|---------|---------|
| `string` | Texto livre | `nome=João` |
| `integer` | Número inteiro | `id=123` |
| `number` | Decimal | `valor=99.90` |
| `date` | YYYY-MM-DD | `data=2024-01-15` |
| `datetime` | YYYY-MM-DD HH:MM:SS | `timestamp=2024-01-15 10:30:00` |
| `boolean` | true/false | `ativo=true` |

## Segurança

A API possui camadas de segurança configuráveis:

### API Key Authentication

```yaml
security:
  enable_auth: true
  api_keys:
    - "sua-chave-secreta-1"
    - "sua-chave-secreta-2"
```

```bash
# Via header
curl -H "X-API-Key: sua-chave-secreta-1" http://localhost:8080/api/queries

# Via query param
curl "http://localhost:8080/api/queries?api_key=sua-chave-secreta-1"
```

### Rate Limiting

```yaml
security:
  enable_rate_limit: true
  requests_per_minute: 60
  burst_size: 10
```

### CORS

```yaml
security:
  allowed_origins:
    - "https://seu-frontend.com"
    - "http://localhost:3000"
```

### Headers de Segurança

Aplicados automaticamente em todas as respostas:
- `X-Content-Type-Options: nosniff`
- `X-Frame-Options: DENY`
- `X-XSS-Protection: 1; mode=block`

### Input Sanitization

Proteção contra padrões maliciosos nos parâmetros de entrada.

## Roadmap

- [x] API básica com cache
- [x] Queries dinâmicas do PostgreSQL
- [x] Validação de parâmetros
- [x] Log de execuções
- [x] Autenticação (API keys)
- [x] Rate limiting
- [x] CORS configurável
- [x] Headers de segurança
- [ ] Interface admin (Laravel)

## Desenvolvimento

```bash
# Compilar
go build -o querybase ./cmd/api/main.go

# Rodar testes
go test ./...

# Verificar dependências
go mod tidy
```
<!-- 
## Licença

MIT -->
