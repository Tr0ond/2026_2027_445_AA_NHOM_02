<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('thong_bao', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_tai_khoan')->constrained('tai_khoan')->cascadeOnDelete();
            $table->string('loai', 50);
            $table->string('tieu_de', 200);
            $table->text('noi_dung');
            $table->boolean('da_doc')->default(false);
            $table->timestamp('thoi_gian_doc')->nullable();
            $table->json('du_lieu')->nullable();
            $table->timestamps();
            $table->index(['ma_tai_khoan', 'da_doc', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('thong_bao');
    }
};
