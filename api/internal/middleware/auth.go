package middleware

import (
	"net/http"
	"strings"

	"github.com/gin-gonic/gin"
)

type AuthConfig struct {
	APIKeys     []string
	HeaderName  string
	QueryParam  string
	SkipPaths   []string
	Enabled     bool
}

func NewAuthConfig() *AuthConfig {
	return &AuthConfig{
		APIKeys:    []string{},
		HeaderName: "X-API-Key",
		QueryParam: "api_key",
		SkipPaths:  []string{"/health"},
		Enabled:    false,
	}
}

func (c *AuthConfig) AddKey(key string) {
	c.APIKeys = append(c.APIKeys, key)
}

func (c *AuthConfig) SetEnabled(enabled bool) {
	c.Enabled = enabled
}

func APIKeyAuth(config *AuthConfig) gin.HandlerFunc {
	return func(c *gin.Context) {
		if !config.Enabled {
			c.Next()
			return
		}

		for _, path := range config.SkipPaths {
			if c.Request.URL.Path == path {
				c.Next()
				return
			}
		}

		apiKey := c.GetHeader(config.HeaderName)
		if apiKey == "" {
			apiKey = c.Query(config.QueryParam)
		}

		if apiKey == "" {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{
				"error":   "API key required",
				"message": "Provide API key via header '" + config.HeaderName + "' or query param '" + config.QueryParam + "'",
			})
			return
		}

		valid := false
		for _, key := range config.APIKeys {
			if apiKey == key {
				valid = true
				break
			}
		}

		if !valid {
			c.AbortWithStatusJSON(http.StatusUnauthorized, gin.H{
				"error":   "Invalid API key",
				"message": "The provided API key is not valid",
			})
			return
		}

		c.Set("api_key", apiKey)
		c.Next()
	}
}

func extractBearerToken(header string) string {
	if strings.HasPrefix(header, "Bearer ") {
		return strings.TrimPrefix(header, "Bearer ")
	}
	return header
}
