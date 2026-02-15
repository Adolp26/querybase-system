<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('query_parameters')) {
            return;
        }

        Schema::create('query_parameters', function (Blueprint $table) {
            $table->uuid('id')->primary()->default(DB::raw('gen_random_uuid()'));
            $table->uuid('query_id');
            $table->foreign('query_id')
                ->references('id')
                ->on('queries')
                ->onDelete('cascade');
            $table->string('name', 100);
            $table->string('param_type', 50)->default('string');
            $table->boolean('is_required')->default(false);
            $table->string('default_value', 255)->nullable();
            $table->text('description')->nullable();
            $table->integer('position');
            $table->jsonb('validations')->default('{}');
            $table->timestampTz('created_at')->default(DB::raw('NOW()'));

            $table->unique(['query_id', 'name']);
            $table->unique(['query_id', 'position']);
            $table->index('query_id', 'idx_query_parameters_query');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('query_parameters');
    }
};
