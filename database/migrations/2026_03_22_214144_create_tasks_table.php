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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['pending', 'in_progress', 'completed'])->default('pending');
            $table->date('due_date')->nullable();
            $table->timestamps();

            // 🚀 تحسينات الأداء (Database Indexes)
            // 1. فهرس للحالة لأننا نصفي المهام حسب حالتها كثيراً
            $table->index('status');

            // 2. فهرس لتاريخ الاستحقاق لتسريع استعلامات (أقرب موعد، المهام المتأخرة)
            $table->index('due_date');

            // 3. فهرس مركب (Composite Index) لأننا دائماً نستعلم عن مهام "مستخدم معين" بـ "حالة معينة" معاً
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
