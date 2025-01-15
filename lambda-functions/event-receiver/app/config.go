package main

import (
    "os"
)

type AppConfig struct {
    EventBusName string
}

func LoadConfig() AppConfig {
    return AppConfig{
        EventBusName: getEnv("EVENT_BUS_NAME", "default")
    }
}

func getEnv(key, fallback string) string {
    if value, exists := os.LookupEnv(key); exists {
        return value
    }
    return fallback
}