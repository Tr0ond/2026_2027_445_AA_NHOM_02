<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sinh_vien', function (Blueprint $table) {
            $table->id();
            $table->string('ma_sinh_vien', 20)->unique();
            $table->foreignId('ma_tai_khoan')->unique()->constrained('tai_khoan')->cascadeOnDelete();
            $table->string('lop_danh_nghia', 50)->nullable();
            $table->string('khoa', 100)->nullable();
            $table->date('ngay_sinh')->nullable();
            $table->enum('gioi_tinh', ['nam', 'nu'])->nullable();
            $table->timestamps();
        });

        Schema::create('giang_vien', function (Blueprint $table) {
            $table->id();
            $table->string('ma_giang_vien', 20)->unique();
            $table->foreignId('ma_tai_khoan')->unique()->constrained('tai_khoan')->cascadeOnDelete();
            $table->string('hoc_vi', 50)->nullable();
            $table->string('bo_mon', 100)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sinh_vien');
        Schema::dropIfExists('giang_vien');
    }
};
