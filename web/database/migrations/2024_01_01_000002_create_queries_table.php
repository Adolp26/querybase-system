<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('queries')) {
            return;
        }

        Schema::create('queries', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->text('sql_query');
            $table->uuid('datasource_id')->nullable();
            $table->foreign('datasource_id')
                ->references('id')
                ->on('datasources')
                ->onDelete('set null');
            $table->integer('cache_ttl')->default(300);
            $table->integer('timeout_seconds')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));
            $table->string('created_by', 255)->nullable();
            $table->string('updated_by', 255)->nullable();

            $table->index('slug', 'idx_queries_slug');
            $table->index('datasource_id', 'idx_queries_datasource');
            $table->index('is_active', 'idx_queries_active');
        });

        DB::unprepared('
            DROP TRIGGER IF EXISTS update_queries_updated_at ON queries;
            CREATE TRIGGER update_queries_updated_at
                BEFORE UPDATE ON queries
                FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('queries');
    }
};
