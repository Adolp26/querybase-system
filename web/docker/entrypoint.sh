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

# Verifica se já existem dados no banco
datasource_count=$(php artisan tinker --execute="echo App\Models\Datasource::count();")
if [ "$datasource_count" -eq 0 ]; then
    echo "[Entrypoint] Populando banco com dados de demonstração..."
    php artisan db:seed --class=DemoDataSeeder --force
    echo "[Entrypoint] Dados de demonstração inseridos!"
else
    echo "[Entrypoint] Dados já existem no banco (pulando seeder)"
fi

echo "[Entrypoint] Iniciando PHP-FPM..."
exec php-fpm
