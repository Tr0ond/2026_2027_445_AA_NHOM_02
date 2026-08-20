<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phien_diem_danh', function (Blueprint $table) {
            $table->id();
            $table->string('ma_phien', 30)->unique();
            $table->foreignId('ma_lich_hoc')->constrained('lich_hoc')->cascadeOnDelete();
            $table->foreignId('ma_giang_vien')->constrained('giang_vien')->cascadeOnDelete();
            $table->string('ma_qr', 64);
            $table->timestamp('thoi_gian_bat_dau')->nullable();
            $table->timestamp('thoi_gian_ket_thuc')->nullable();
            $table->enum('hinh_thuc_diem_danh', ['qr_code', 'thu_cong'])->default('qr_code');
            $table->enum('trang_thai', ['dang_mo', 'da_dong'])->default('dang_mo');
            $table->timestamps();
        });

        Schema::create('chi_tiet_diem_danh', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_phien_diem_danh')->constrained('phien_diem_danh')->cascadeOnDelete();
            $table->foreignId('ma_sinh_vien')->constrained('sinh_vien')->cascadeOnDelete();
            $table->enum('trang_thai_diem_danh', ['co_mat', 'vang', 'di_muon', 'xin_phep'])->default('co_mat');
            $table->timestamp('thoi_gian_diem_danh')->nullable();
            $table->enum('hinh_thuc_diem_danh', ['qr_code', 'thu_cong', 'sua_thu_cong'])->default('qr_code');
            $table->timestamps();
            $table->unique(['ma_phien_diem_danh', 'ma_sinh_vien']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chi_tiet_diem_danh');
        Schema::dropIfExists('phien_diem_danh');
    }
};
