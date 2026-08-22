<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('don_xin_phep', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_sinh_vien')->constrained('sinh_vien')->cascadeOnDelete();
            $table->foreignId('ma_lop_hoc')->constrained('lop_hoc')->cascadeOnDelete();
            $table->date('ngay_nghi');
            $table->string('ly_do', 500);
            $table->enum('trang_thai', ['cho_duyet', 'duoc_duyet', 'tu_choi'])->default('cho_duyet');
            $table->foreignId('nguoi_duyet')->nullable()->constrained('tai_khoan')->nullOnDelete();
            $table->timestamp('thoi_gian_duyet')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('don_xin_phep');
    }
};
