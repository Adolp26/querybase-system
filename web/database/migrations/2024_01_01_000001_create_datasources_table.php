<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('datasources')) {
            return;
        }

        Schema::create('datasources', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->string('slug', 100)->unique();
            $table->string('name', 255);
            $table->string('driver', 50)->default('oracle');
            $table->string('host', 255);
            $table->string('port', 10);
            $table->string('database_name', 255);
            $table->string('username', 255);
            $table->string('password', 255);
            $table->integer('max_open_conns')->default(25);
            $table->integer('max_idle_conns')->default(5);
            $table->boolean('is_active')->default(true);
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));
            $table->timestampTz('updated_at')->default(DB::raw('NOW()'));

            $table->index('slug', 'idx_datasources_slug');
        });

        DB::unprepared('
            CREATE OR REPLACE FUNCTION update_updated_at_column()
            RETURNS TRIGGER AS $$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            $$ language \'plpgsql\';

            DROP TRIGGER IF EXISTS update_datasources_updated_at ON datasources;
            CREATE TRIGGER update_datasources_updated_at
                BEFORE UPDATE ON datasources
                FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();
        ');
    }

    public function down(): void
    {
        Schema::dropIfExists('datasources');
    }
};
