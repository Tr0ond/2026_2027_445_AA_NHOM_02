<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phong_hoc_truc_tuyen', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phong', 30)->unique();
            $table->foreignId('ma_lich_hoc')->constrained('lich_hoc')->cascadeOnDelete();
            $table->text('duong_dan_tham_gia')->nullable();
            $table->string('nen_tang', 30)->default('Daily');
            $table->enum('trang_thai', ['dang_dien_ra', 'da_ket_thuc'])->default('dang_dien_ra');
            $table->timestamps();
        });

        Schema::create('thanh_vien_phong_truc_tuyen', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_phong_hoc_truc_tuyen')->constrained('phong_hoc_truc_tuyen')->cascadeOnDelete();
            $table->foreignId('ma_tai_khoan')->constrained('tai_khoan')->cascadeOnDelete();
            $table->enum('vai_tro', ['giang_vien', 'sinh_vien']);
            $table->timestamp('thoi_gian_tham_gia')->nullable();
            $table->timestamp('thoi_gian_roi')->nullable();
            $table->timestamps();
            $table->unique(['ma_phong_hoc_truc_tuyen', 'ma_tai_khoan'], 'uq_thanh_vien_phong');
        });

        Schema::create('tin_nhan_phong', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_phong_hoc_truc_tuyen')->constrained('phong_hoc_truc_tuyen')->cascadeOnDelete();
            $table->foreignId('ma_tai_khoan')->constrained('tai_khoan')->cascadeOnDelete();
            $table->text('noi_dung');
            $table->timestamp('thoi_gian_gui')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tin_nhan_phong');
        Schema::dropIfExists('thanh_vien_phong_truc_tuyen');
        Schema::dropIfExists('phong_hoc_truc_tuyen');
    }
};
