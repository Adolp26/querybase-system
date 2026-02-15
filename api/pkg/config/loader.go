package config

import (
	"fmt"

	"github.com/adolp26/querybase/internal/models"
	"github.com/spf13/viper"
)

func LoadConfig(path string) (*models.Config, error) {
	viper.SetConfigFile(path)
	viper.SetConfigType("yaml")

	if err := viper.ReadInConfig(); err != nil {
		return nil, fmt.Errorf("erro ao ler config: %w", err)
	}

	var config models.Config

	if err := viper.Unmarshal(&config); err != nil {
		return nil, fmt.Errorf("erro ao parsear config: %w", err)
	}

	return &config, nil
}
