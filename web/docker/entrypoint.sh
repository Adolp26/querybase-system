#!/bin/bash
set -e

echo "[Entrypoint] Aguardando PostgreSQL..."

# Espera o PostgreSQL aceitar conexões
max_attempts=30
attempt=0

until php -r "new PDO('pgsql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_DATABASE}', '${DB_USERNAME}', '${DB_PASSWORD}');" > /dev/null 2>&1; do
    attempt=$((attempt + 1))
    if [ $attempt -ge $max_attempts ]; then
        echo "[Entrypoint] ERRO: PostgreSQL não respondeu após ${max_attempts} tentativas!"
        exit 1
    fi
    echo "[Entrypoint] PostgreSQL ainda não está pronto - aguardando... (tentativa $attempt/$max_attempts)"
    sleep 2
done

echo "[Entrypoint] PostgreSQL está pronto!"

echo "[Entrypoint] Executando migrations..."
php artisan migrate --force

echo "[Entrypoint] Migrations concluídas!"

echo "[Entrypoint] Iniciando PHP-FPM..."
exec php-fpm
