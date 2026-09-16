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
            // حذف الفهارس المكررة أو غير الضرورية
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);

            // إضافة فهرس مركب لتسريع الترتيب الافتراضي (المهام الأحدث أولاً للمستخدم)
            $table->index(['user_id', 'created_at']);
        });

        Schema::table('category_task', function (Blueprint $table) {
            // إضافة فهرس منفرد لتسريع استعلامات (whereHas) العكسية
            $table->index('category_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('category_task', function (Blueprint $table) {
            // إزالة الفهرس المنفرد
            $table->dropIndex(['category_id']);
        });

        Schema::table('tasks', function (Blueprint $table) {
            // إزالة الفهرس المركب الجديد
            $table->dropIndex(['user_id', 'created_at']);

            // إعادة الفهارس القديمة كما كانت
            $table->index('user_id');
            $table->index('status');
        });
    }
};
