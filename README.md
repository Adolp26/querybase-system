# QueryBase

Gateway de API multi-fonte para análises de negócio, permitindo que clientes executem queries SQL em múltiplas bases de dados (Oracle, PostgreSQL, MySQL) através de uma API REST com cache integrado.

## Estrutura do Projeto

```
querybase-system/
├── web/      # Interface administrativa (Laravel 10 + PHP)
│   └── Gerenciamento de datasources e queries via UI
│
├── api/      # Motor de execução (Golang + Gin)
│   └── Execução dinâmica de queries com cache Redis
│
└── docker-compose.yml  # Infraestrutura completa
```

## 🎯 Problema que Resolve

Em ambientes onde clientes conectam ferramentas de BI (Power BI, Tableau, etc.) diretamente em bancos de produção, surgem problemas de:
- **Performance**: Queries pesadas impactam a produção
- **Segurança**: Credenciais de produção expostas
- **Controle**: Sem gestão centralizada de acessos

O QueryBase resolve isso criando uma camada intermediária com:
- ✅ Cache inteligente de resultados (Redis)
- ✅ Gerenciamento centralizado de credenciais
- ✅ Pool de conexões otimizado
- ✅ Criptografia AES-256-GCM de senhas
- ✅ API REST simples para consumo

## Arquitetura

### querybase-web (Laravel)
- Interface para cadastrar datasources (conexões de banco)
- Gerenciamento de queries SQL reutilizáveis
- Criptografia de senhas com AES-256-GCM
- Testes de conexão via API Golang

### querybase-api (Golang)
- Execução dinâmica de queries
- Cache de resultados no Redis
- Connection pooling thread-safe
- Descriptografia de senhas
- Rate limiting e autenticação

## Segurança - Criptografia Compartilhada

As senhas de datasources são criptografadas usando **AES-256-GCM**, compartilhado entre Laravel e Golang.

### Configuração Inicial

1. **Gerar chave de criptografia:**
```bash
php generate-encryption-key.php
```

2. **Adicionar a chave em ambos os .env:**

**querybase-web/.env:**
```env
QUERYBASE_ENCRYPTION_KEY=SuaChaveGeradaAqui==
QUERYBASE_API_URL=http://localhost:8080
```

**querybase-api/.env:**
```env
QUERYBASE_ENCRYPTION_KEY=SuaChaveGeradaAqui==
```

**IMPORTANTE:**
- A chave DEVE ser idêntica nos dois projetos
- Mantenha em segredo (não commite no git)
- Use `.env.example` apenas para documentar

## Como Usar

### 1. Iniciar infraestrutura
```bash
docker-compose up -d
```

### 2. Configurar Laravel
```bash
cd querybase-web
cp .env.example .env
# Editar .env com suas configurações
php artisan migrate
php artisan serve
```

### 3. Configurar Golang
```bash
cd querybase-api
cp .env.example .env
# Editar .env com a mesma chave do Laravel
go run cmd/api/main.go
```

### 4. Cadastrar um Datasource

Acesse o painel Laravel e cadastre uma conexão:
- **Slug**: `oracle-producao`
- **Driver**: `oracle`
- **Host**: `192.168.1.100`
- **Port**: `1521`
- **Database**: `PROD`
- **Username**: `app_user`
- **Password**: `senha123` ← será criptografada automaticamente

### 5. Criar uma Query

Cadastre uma query SQL vinculada ao datasource:
- **Slug**: `vendas-diarias`
- **Datasource**: `oracle-producao`
- **SQL**: `SELECT * FROM vendas WHERE data = TRUNC(SYSDATE)`
- **Cache TTL**: `300` (5 minutos)

### 6. Executar via API

```bash
curl http://localhost:8080/api/query/vendas-diarias
```

Resposta:
```json
{
  "success": true,
  "query_slug": "vendas-diarias",
  "datasource": "oracle-producao",
  "cached": false,
  "execution_time_ms": 245,
  "rows_count": 1523,
  "data": [
    {"id": 1, "produto": "Notebook", "valor": 2500.00},
    ...
  ]
}
```

## 🔧 Tecnologias

**Backend (API)**
- Go 1.21+
- Gin Framework
- database/sql (Oracle, PostgreSQL, MySQL)
- Redis (cache)
- AES-256-GCM (criptografia)

**Admin (Web)**
- Laravel 10
- PHP 8.2+
- SQLite (metadados)
- Tailwind CSS

**Infraestrutura**
- Docker & Docker Compose
- Redis 7
- PostgreSQL 15

## 📊 Fluxo de Dados

```
Cliente (Power BI, Postman, etc.)
    ↓
    ↓ GET /api/query/vendas-diarias
    ↓
Golang API
    ↓
    ├─→ Redis Cache? → ✅ Retorna
    │
    ├─→ PostgreSQL (busca metadata da query + datasource)
    │
    ├─→ Descriptografa senha do datasource
    │
    ├─→ ConnectionManager (cria pool dinâmico)
    │
    ├─→ Oracle/MySQL/PostgreSQL (executa query)
    │
    ├─→ Redis (salva resultado em cache)
    │
    └─→ Retorna JSON para cliente
```

## Casos de Uso

1. **BI Self-Service Seguro**: Clientes executam queries pré-aprovadas sem acesso direto ao banco
2. **APIs de Dados**: Expor dados de produção via REST sem sobrecarregar o banco
3. **Dashboards em Tempo Real**: Cache inteligente reduz carga em queries frequentes
4. **Migração Gradual**: Centralizar acessos antes de migrar para data warehouse
