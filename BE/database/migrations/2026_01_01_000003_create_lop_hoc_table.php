<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lop_hoc', function (Blueprint $table) {
            $table->id();
            $table->string('ma_lop_hoc', 20)->unique();
            $table->string('ten_lop', 200);
            $table->foreignId('ma_mon_hoc')->constrained('mon_hoc')->restrictOnDelete();
            $table->string('hoc_ky', 10);
            $table->string('nam_hoc', 20);
            $table->integer('so_luong_toi_da')->default(50);
            $table->enum('trang_thai', ['mo_dang_ky', 'dang_hoc', 'da_ket_thuc'])->default('mo_dang_ky');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lop_hoc');
    }
};
