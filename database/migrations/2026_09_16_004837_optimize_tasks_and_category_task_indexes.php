<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {

            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);

            $table->index(['user_id', 'created_at']);
        });

        Schema::table('category_task', function (Blueprint $table) {
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_task', function (Blueprint $table) {

            $table->dropIndex(['category_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {

            $table->dropIndex(['user_id', 'created_at']);

            $table->index('user_id');
            $table->index('status');
        });
    }
};
