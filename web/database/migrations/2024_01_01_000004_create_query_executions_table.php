<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('query_executions')) {
            return;
        }

        Schema::create('query_executions', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('query_id')->nullable();
            $table->foreign('query_id')
                ->references('id')
                ->on('queries')
                ->onDelete('set null');
            $table->string('query_slug', 100);
            $table->timestampTz('executed_at')->default(DB::raw('NOW()'));
            $table->integer('duration_ms')->nullable();
            $table->boolean('cache_hit')->default(false);
            $table->integer('row_count')->nullable();
            $table->jsonb('parameters')->default('{}');
            $table->text('error')->nullable();
            $table->string('client_ip', 45)->nullable();
            $table->string('user_agent', 500)->nullable();

            $table->index('query_id', 'idx_query_executions_query');
            $table->index('executed_at', 'idx_query_executions_date');
        });

        DB::unprepared('
            CREATE INDEX idx_query_executions_errors
            ON query_executions(query_id)
            WHERE error IS NOT NULL;
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('query_executions');
    }
};
