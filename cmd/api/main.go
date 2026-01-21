package main

import (
	"fmt"
	"log"

	"github.com/adolp26/querybase/internal/database"
	"github.com/adolp26/querybase/internal/handlers"
	"github.com/adolp26/querybase/internal/middleware"
	"github.com/adolp26/querybase/internal/repository"
	"github.com/adolp26/querybase/internal/services"
	"github.com/adolp26/querybase/pkg/config"
	"github.com/gin-gonic/gin"
)

func main() {
	fmt.Println("📖 Carregando configurações...")
	cfg, err := config.LoadConfig("configs/config.yaml")
	if err != nil {
		log.Fatalf("❌ Erro ao carregar config: %v", err)
	}
	fmt.Println("✅ Configurações carregadas")

	fmt.Println("🔌 Conectando no Redis...")
	redisClient, err := database.NewRedisClient(cfg.Redis)
	if err != nil {
		log.Fatalf("❌ Erro ao conectar no Redis: %v", err)
	}
	defer redisClient.Close()
	fmt.Println("✅ Redis conectado")

	fmt.Println("🔌 Conectando no Oracle...")
	oracleClient, err := database.NewOracleDataSource(cfg.Oracle)
	if err != nil {
		log.Fatalf("❌ Erro ao conectar no Oracle: %v", err)
	}
	defer oracleClient.Close()
	fmt.Println("✅ Oracle conectado")

	fmt.Println("🔌 Conectando no PostgreSQL...")
	postgresClient, err := database.NewPostgresClient(cfg.Postgres)
	if err != nil {
		log.Fatalf("❌ Erro ao conectar no PostgreSQL: %v", err)
	}
	defer postgresClient.Close()
	fmt.Println("✅ PostgreSQL conectado")

	cacheService := services.NewCacheService(redisClient)
	queryService := services.NewQueryService(oracleClient, cacheService)
	queryRepo := repository.NewQueryRepository(postgresClient.GetDB())

	testHandler := handlers.NewTestHandler(cacheService)
	employeeHandler := handlers.NewEmployeeHandler(queryService)
	dynamicHandler := handlers.NewDynamicQueryHandler(queryRepo, queryService, cacheService)

	if cfg.Server.Mode == "release" {
		gin.SetMode(gin.ReleaseMode)
	}

	router := gin.New()
	router.Use(gin.Logger())
	router.Use(gin.Recovery())

	corsConfig := middleware.NewCORSConfig()
	if len(cfg.Security.AllowedOrigins) > 0 {
		corsConfig.AllowOrigins = cfg.Security.AllowedOrigins
	}
	router.Use(middleware.CORS(corsConfig))

	router.Use(middleware.SecurityHeaders())
	router.Use(middleware.InputSanitizer())

	rateLimitConfig := middleware.NewRateLimitConfig()
	rateLimitConfig.Enabled = cfg.Security.EnableRateLimit
	if cfg.Security.RequestsPerMinute > 0 {
		rateLimitConfig.RequestsPerMinute = cfg.Security.RequestsPerMinute
	}
	if cfg.Security.BurstSize > 0 {
		rateLimitConfig.BurstSize = cfg.Security.BurstSize
	}
	router.Use(middleware.RateLimit(rateLimitConfig))

	authConfig := middleware.NewAuthConfig()
	authConfig.Enabled = cfg.Security.EnableAuth
	for _, key := range cfg.Security.APIKeys {
		if key != "" {
			authConfig.AddKey(key)
		}
	}
	router.Use(middleware.APIKeyAuth(authConfig))

	router.GET("/health", handlers.HealthCheck)

	router.GET("/api/test", testHandler.GetTestData)
	router.GET("/api/employees", employeeHandler.GetAll)
	router.GET("/api/employees/department/:department", employeeHandler.GetByDepartment)

	router.GET("/api/queries", dynamicHandler.ListQueries)
	router.GET("/api/query/:slug", dynamicHandler.Execute)

	addr := fmt.Sprintf(":%s", cfg.Server.Port)
	fmt.Println("")
	fmt.Printf("🚀 QueryBase API rodando em http://localhost%s\n", addr)
	fmt.Println("")
	fmt.Println("🔒 Segurança:")
	fmt.Printf("   Auth:       %v\n", cfg.Security.EnableAuth)
	fmt.Printf("   Rate Limit: %v (%d req/min)\n", cfg.Security.EnableRateLimit, cfg.Security.RequestsPerMinute)
	fmt.Println("")
	fmt.Println("📚 Endpoints:")
	fmt.Println("   GET /health              - Health check")
	fmt.Println("   GET /api/queries         - Listar queries")
	fmt.Println("   GET /api/query/:slug     - Executar query")
	fmt.Println("")

	if err := router.Run(addr); err != nil {
		log.Fatalf("❌ Erro ao iniciar servidor: %v", err)
	}
}