<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $viewExists = DB::select("
            SELECT EXISTS (
                SELECT FROM pg_views WHERE viewname = 'vw_queries_with_params'
            ) as exists
        ")[0]->exists;

        if ($viewExists) {
            return;
        }

        DB::unprepared("
            CREATE VIEW vw_queries_with_params AS
            SELECT
                q.id,
                q.slug,
                q.name,
                q.description,
                q.sql_query,
                q.cache_ttl,
                q.timeout_seconds,
                q.is_active,
                q.created_at,
                d.slug as datasource_slug,
                d.name as datasource_name,
                COALESCE(
                    json_agg(
                        json_build_object(
                            'name', p.name,
                            'type', p.param_type,
                            'required', p.is_required,
                            'default', p.default_value,
                            'description', p.description,
                            'position', p.position
                        )
                        ORDER BY p.position
                    ) FILTER (WHERE p.id IS NOT NULL),
                    '[]'
                ) as parameters
            FROM queries q
            LEFT JOIN datasources d ON q.datasource_id = d.id
            LEFT JOIN query_parameters p ON q.id = p.query_id
            GROUP BY q.id, d.slug, d.name;
        ");
    }

    public function down(): void
    {
        DB::unprepared('DROP VIEW IF EXISTS vw_queries_with_params;');
    }
};
