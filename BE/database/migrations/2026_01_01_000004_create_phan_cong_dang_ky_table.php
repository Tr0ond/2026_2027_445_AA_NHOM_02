<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('phan_cong_giang_day', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_giang_vien')->constrained('giang_vien')->cascadeOnDelete();
            $table->foreignId('ma_lop_hoc')->constrained('lop_hoc')->cascadeOnDelete();
            $table->string('vai_tro_phu_trach', 50)->default('giang_vien_chinh');
            $table->timestamps();
            $table->unique(['ma_giang_vien', 'ma_lop_hoc']);
        });

        Schema::create('dang_ky_lop_hoc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_sinh_vien')->constrained('sinh_vien')->cascadeOnDelete();
            $table->foreignId('ma_lop_hoc')->constrained('lop_hoc')->cascadeOnDelete();
            $table->date('ngay_dang_ky');
            $table->enum('trang_thai', ['cho_duyet', 'da_duyet', 'huy'])->default('da_duyet');
            $table->timestamps();
            $table->unique(['ma_sinh_vien', 'ma_lop_hoc']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dang_ky_lop_hoc');
        Schema::dropIfExists('phan_cong_giang_day');
    }
};
