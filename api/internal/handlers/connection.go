package handlers

import (
	"context"
	"net/http"
	"time"

	"github.com/adolp26/querybase/internal/database"
	"github.com/gin-gonic/gin"
)

type ConnectionHandler struct {
	connManager *database.ConnectionManager
}

func NewConnectionHandler(connManager *database.ConnectionManager) *ConnectionHandler {
	return &ConnectionHandler{
		connManager: connManager,
	}
}

type TestConnectionRequest struct {
	ID           string `json:"id"`
	Slug         string `json:"slug"`
	Driver       string `json:"driver" binding:"required"`
	Host         string `json:"host" binding:"required"`
	Port         int    `json:"port" binding:"required"`
	Database     string `json:"database" binding:"required"`
	Username     string `json:"username" binding:"required"`
	Password     string `json:"password" binding:"required"`
	MaxOpenConns int    `json:"max_open_conns"`
	MaxIdleConns int    `json:"max_idle_conns"`
}

func (h *ConnectionHandler) TestConnection(c *gin.Context) {
	var req TestConnectionRequest

	if err := c.ShouldBindJSON(&req); err != nil {
		c.JSON(http.StatusBadRequest, gin.H{
			"success": false,
			"error":   "Dados invalidos: " + err.Error(),
		})
		return
	}

	supportedDrivers := map[string]bool{
		"oracle":   true,
		"postgres": true,
		"mysql":    true,
	}
	if !supportedDrivers[req.Driver] {
		c.JSON(http.StatusBadRequest, gin.H{
			"success": false,
			"error":   "Driver nao suportado: " + req.Driver,
		})
		return
	}

	config := database.DatasourceConfig{
		ID:           req.ID,
		Slug:         req.Slug,
		Driver:       req.Driver,
		Host:         req.Host,
		Port:         req.Port,
		Database:     req.Database,
		Username:     req.Username,
		Password:     req.Password,
		MaxOpenConns: req.MaxOpenConns,
		MaxIdleConns: req.MaxIdleConns,
	}

	ctx, cancel := context.WithTimeout(c.Request.Context(), 30*time.Second)
	defer cancel()

	result, err := h.connManager.TestConnection(ctx, config)
	if err != nil {
		c.JSON(http.StatusOK, gin.H{
			"success": false,
			"error":   err.Error(),
		})
		return
	}

	c.JSON(http.StatusOK, gin.H{
		"success":        result.Success,
		"message":        result.Message,
		"duration_ms":    result.DurationMs,
		"server_version": result.ServerVersion,
	})
}
