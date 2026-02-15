package models

type Config struct {
	Server   ServerConfig   `mapstructure:"server"`
	Redis    RedisConfig    `mapstructure:"redis"`
	Postgres PostgresConfig `mapstructure:"postgres"`
	Security SecurityConfig `mapstructure:"security"`
}

type SecurityConfig struct {
	APIKeys           []string `mapstructure:"api_keys"`
	EnableAuth        bool     `mapstructure:"enable_auth"`
	EnableRateLimit   bool     `mapstructure:"enable_rate_limit"`
	RequestsPerMinute int      `mapstructure:"requests_per_minute"`
	BurstSize         int      `mapstructure:"burst_size"`
	AllowedOrigins    []string `mapstructure:"allowed_origins"`
}

type ServerConfig struct {
	Port string `mapstructure:"port"`
	Mode string `mapstructure:"mode"`
}

type RedisConfig struct {
	Host     string `mapstructure:"host"`
	Port     string `mapstructure:"port"`
	Password string `mapstructure:"password"`
	DB       int    `mapstructure:"db"`
	TTL      int    `mapstructure:"ttl"`
}

type PostgresConfig struct {
	Host     string `mapstructure:"host"`
	Port     string `mapstructure:"port"`
	Database string `mapstructure:"database"`
	Username string `mapstructure:"username"`
	Password string `mapstructure:"password"`
	SSLMode  string `mapstructure:"sslmode"`
}
