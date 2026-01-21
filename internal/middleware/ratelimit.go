package middleware

import (
	"net/http"
	"sync"
	"time"

	"github.com/gin-gonic/gin"
)

type RateLimitConfig struct {
	RequestsPerMinute int
	BurstSize         int
	Enabled           bool
	SkipPaths         []string
}

func NewRateLimitConfig() *RateLimitConfig {
	return &RateLimitConfig{
		RequestsPerMinute: 60,
		BurstSize:         10,
		Enabled:           false,
		SkipPaths:         []string{"/health"},
	}
}

type client struct {
	tokens     float64
	lastUpdate time.Time
}

type rateLimiter struct {
	clients map[string]*client
	mu      sync.Mutex
	config  *RateLimitConfig
}

func newRateLimiter(config *RateLimitConfig) *rateLimiter {
	rl := &rateLimiter{
		clients: make(map[string]*client),
		config:  config,
	}

	go rl.cleanup()

	return rl
}

func (rl *rateLimiter) cleanup() {
	ticker := time.NewTicker(5 * time.Minute)
	for range ticker.C {
		rl.mu.Lock()
		for ip, c := range rl.clients {
			if time.Since(c.lastUpdate) > 10*time.Minute {
				delete(rl.clients, ip)
			}
		}
		rl.mu.Unlock()
	}
}

func (rl *rateLimiter) allow(ip string) bool {
	rl.mu.Lock()
	defer rl.mu.Unlock()

	c, exists := rl.clients[ip]
	now := time.Now()

	if !exists {
		rl.clients[ip] = &client{
			tokens:     float64(rl.config.BurstSize) - 1,
			lastUpdate: now,
		}
		return true
	}

	elapsed := now.Sub(c.lastUpdate).Seconds()
	tokensPerSecond := float64(rl.config.RequestsPerMinute) / 60.0
	c.tokens += elapsed * tokensPerSecond

	if c.tokens > float64(rl.config.BurstSize) {
		c.tokens = float64(rl.config.BurstSize)
	}

	c.lastUpdate = now

	if c.tokens >= 1 {
		c.tokens--
		return true
	}

	return false
}

func RateLimit(config *RateLimitConfig) gin.HandlerFunc {
	limiter := newRateLimiter(config)

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

		ip := c.ClientIP()

		if !limiter.allow(ip) {
			c.AbortWithStatusJSON(http.StatusTooManyRequests, gin.H{
				"error":   "Rate limit exceeded",
				"message": "Too many requests. Please try again later.",
				"limit":   config.RequestsPerMinute,
				"unit":    "requests per minute",
			})
			return
		}

		c.Next()
	}
}
