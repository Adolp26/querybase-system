package main

import (
	"fmt"
	"log"

	"github.com/adolp26/querybase/internal/crypto"
	"github.com/adolp26/querybase/internal/database"
	"github.com/adolp26/querybase/internal/handlers"
	"github.com/adolp26/querybase/internal/middleware"
	"github.com/adolp26/querybase/internal/repository"
	"github.com/adolp26/querybase/internal/services"
	"github.com/adolp26/querybase/pkg/config"
	"github.com/gin-gonic/gin"
)

func main() {
	fmt.Println("[QueryBase] Iniciando API...")
	fmt.Println("")


	fmt.Println("[Config] Carregando configuracoes...")
	cfg, err := config.LoadConfig("configs/config.yaml")
	if err != nil {
		log.Fatalf("[Config] Erro ao carregar config: %v", err)
	}
	fmt.Println("[Config] OK")


	fmt.Println("[Crypto] Inicializando...")
	if err := crypto.Init(); err != nil {
		fmt.Printf("[Crypto] Aviso: %v (senhas nao serao descriptografadas)\n", err)
	} else {
		fmt.Println("[Crypto] OK")
	}



	// Redis (cache de queries)
	fmt.Println("[Redis] Conectando...")
	redisClient, err := database.NewRedisClient(cfg.Redis)
	if err != nil {
		log.Fatalf("[Redis] Erro ao conectar: %v", err)
	}
	defer redisClient.Close()
	fmt.Println("[Redis] OK")

	// PostgreSQL 
	fmt.Println("[PostgreSQL] Conectando ao banco de metadados...")
	postgresClient, err := database.NewPostgresClient(cfg.Postgres)
	if err != nil {
		log.Fatalf("[PostgreSQL] Erro ao conectar: %v", err)
	}
	defer postgresClient.Close()
	fmt.Println("[PostgreSQL] OK")

	// ConnectionManager (conexoes dinamicas)
	fmt.Println("[ConnectionManager] Inicializando...")
	connManager := database.NewConnectionManager()
	defer connManager.CloseAll()
	fmt.Println("[ConnectionManager] OK")


	cacheService := services.NewCacheService(redisClient)
	queryRepo := repository.NewQueryRepository(postgresClient.GetDB())
	datasourceRepo := repository.NewDatasourceRepository(postgresClient.GetDB())


	connectionHandler := handlers.NewConnectionHandler(connManager)
	dynamicHandler := handlers.NewDynamicQueryHandler(queryRepo, datasourceRepo, connManager, cacheService)


	if cfg.Server.Mode == "release" {
		gin.SetMode(gin.ReleaseMode)
	}

	router := gin.New()
	router.Use(gin.Logger())
	router.Use(gin.Recovery())

	// CORS
	corsConfig := middleware.NewCORSConfig()
	if len(cfg.Security.AllowedOrigins) > 0 {
		corsConfig.AllowOrigins = cfg.Security.AllowedOrigins
	}
	router.Use(middleware.CORS(corsConfig))

	// Security headers
	router.Use(middleware.SecurityHeaders())
	router.Use(middleware.InputSanitizer())

	// Rate limiting
	rateLimitConfig := middleware.NewRateLimitConfig()
	rateLimitConfig.Enabled = cfg.Security.EnableRateLimit
	if cfg.Security.RequestsPerMinute > 0 {
		rateLimitConfig.RequestsPerMinute = cfg.Security.RequestsPerMinute
	}
	if cfg.Security.BurstSize > 0 {
		rateLimitConfig.BurstSize = cfg.Security.BurstSize
	}
	router.Use(middleware.RateLimit(rateLimitConfig))

	// API Key auth
	authConfig := middleware.NewAuthConfig()
	authConfig.Enabled = cfg.Security.EnableAuth
	for _, key := range cfg.Security.APIKeys {
		if key != "" {
			authConfig.AddKey(key)
		}
	}
	router.Use(middleware.APIKeyAuth(authConfig))

// Routes

	// Health check
	router.GET("/health", handlers.HealthCheck)

	// Testar conexao com datasource (chamado pelo Laravel)
	router.POST("/api/test-connection", connectionHandler.TestConnection)

	// Listar queries disponiveis
	router.GET("/api/queries", dynamicHandler.ListQueries)

	// Executar query por slug
	router.GET("/api/query/:slug", dynamicHandler.Execute)


	addr := fmt.Sprintf(":%s", cfg.Server.Port)
	fmt.Println("")
	fmt.Println("==========================================================")
	fmt.Printf("  QueryBase API rodando em http://localhost%s\n", addr)
	fmt.Println("==========================================================")
	fmt.Println("")
	fmt.Println("Endpoints:")
	fmt.Println("  GET  /health              - Health check")
	fmt.Println("  POST /api/test-connection - Testar conexao com datasource")
	fmt.Println("  GET  /api/queries         - Listar queries disponiveis")
	fmt.Println("  GET  /api/query/:slug     - Executar query por slug")
	fmt.Println("")

	if err := router.Run(addr); err != nil {
		log.Fatalf("[Server] Erro ao iniciar: %v", err)
	}
}
