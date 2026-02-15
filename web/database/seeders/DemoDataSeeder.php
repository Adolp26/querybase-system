<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Datasource;
use App\Models\Query;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        // Datasource de exemplo - PostgreSQL local
        $datasource = Datasource::create([
            'slug' => 'postgres-local',
            'name' => 'PostgreSQL Local (Teste)',
            'driver' => 'postgres',
            'host' => 'postgres',
            'port' => 5432,
            'database_name' => 'querybase_metadata',
            'username' => 'querybase',
            'password' => 'querybase123',
            'max_open_conns' => 10,
            'max_idle_conns' => 2,
            'is_active' => true,
        ]);

        // Query de exemplo - Listar datasources
        Query::create([
            'datasource_id' => $datasource->id,
            'slug' => 'listar-datasources',
            'name' => 'Listar Datasources Cadastrados',
            'description' => 'Retorna todos os datasources cadastrados no sistema',
            'sql_query' => 'SELECT id, name, driver, host, port, database_name, is_active FROM datasources ORDER BY created_at DESC',
            'cache_ttl' => 60,
            'is_active' => true,
        ]);

        // Query de exemplo - Listar queries
        Query::create([
            'datasource_id' => $datasource->id,
            'slug' => 'listar-queries',
            'name' => 'Listar Queries Cadastradas',
            'description' => 'Retorna todas as queries cadastradas no sistema',
            'sql_query' => 'SELECT id, name, slug, datasource_id, cache_ttl, is_active FROM queries ORDER BY created_at DESC',
            'cache_ttl' => 60,
            'is_active' => true,
        ]);

        // Query de exemplo - Estatísticas do sistema
        Query::create([
            'datasource_id' => $datasource->id,
            'slug' => 'estatisticas-sistema',
            'name' => 'Estatísticas do Sistema',
            'description' => 'Retorna contadores de datasources e queries',
            'sql_query' => "SELECT
                (SELECT COUNT(*) FROM datasources WHERE is_active = true) as total_datasources,
                (SELECT COUNT(*) FROM queries WHERE is_active = true) as total_queries,
                NOW() as consultado_em",
            'cache_ttl' => 30,
            'is_active' => true,
        ]);
    }
}
