package middleware

import (
	"regexp"
	"strings"

	"github.com/gin-gonic/gin"
)

func SecurityHeaders() gin.HandlerFunc {
	return func(c *gin.Context) {
		c.Header("X-Content-Type-Options", "nosniff")
		c.Header("X-Frame-Options", "DENY")
		c.Header("X-XSS-Protection", "1; mode=block")
		c.Header("Referrer-Policy", "strict-origin-when-cross-origin")

		if !strings.HasPrefix(c.Request.URL.Path, "/api/query/") {
			c.Header("Cache-Control", "no-store, no-cache, must-revalidate")
		}

		c.Next()
	}
}

var dangerousPatterns = []*regexp.Regexp{
	regexp.MustCompile(`(?i)(union\s+select)`),
	regexp.MustCompile(`(?i)(insert\s+into)`),
	regexp.MustCompile(`(?i)(delete\s+from)`),
	regexp.MustCompile(`(?i)(drop\s+table)`),
	regexp.MustCompile(`(?i)(update\s+\w+\s+set)`),
	regexp.MustCompile(`(?i)(<script)`),
	regexp.MustCompile(`(?i)(javascript:)`),
	regexp.MustCompile(`(?i)(on\w+\s*=)`),
}

func InputSanitizer() gin.HandlerFunc {
	return func(c *gin.Context) {
		for key, values := range c.Request.URL.Query() {
			for _, value := range values {
				if containsDangerousPattern(value) {
					c.AbortWithStatusJSON(400, gin.H{
						"error":   "Invalid input",
						"message": "Parameter '" + key + "' contains invalid characters",
					})
					return
				}
			}
		}

		c.Next()
	}
}

func containsDangerousPattern(input string) bool {
	normalized := strings.ToLower(strings.TrimSpace(input))

	for _, pattern := range dangerousPatterns {
		if pattern.MatchString(normalized) {
			return true
		}
	}

	return false
}
