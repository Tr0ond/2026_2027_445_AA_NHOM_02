<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ket_qua_hoc_phan', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('ma_sinh_vien')->constrained('sinh_vien')->cascadeOnDelete();
            $table->foreignId('ma_lop_hoc')->constrained('lop_hoc')->cascadeOnDelete();
            $table->decimal('diem_tong_ket', 5, 2)->nullable();
            $table->string('xep_loai', 30)->nullable();
            $table->enum('trang_thai', ['dat', 'hoc_lai'])->default('hoc_lai');
            $table->timestamp('thoi_gian_cap_nhat')->nullable();
            $table->timestamps();
            $table->unique(['ma_sinh_vien', 'ma_lop_hoc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ket_qua_hoc_phan');
    }
};
