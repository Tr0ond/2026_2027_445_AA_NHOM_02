<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diem_thanh_phan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_lop_hoc')->constrained('lop_hoc')->cascadeOnDelete();
            $table->string('ten_thanh_phan', 50);
            $table->decimal('trong_so', 5, 2)->default(1);
            $table->timestamps();
            $table->unique(['ma_lop_hoc', 'ten_thanh_phan']);
        });

        Schema::create('diem_sinh_vien', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_sinh_vien')->constrained('sinh_vien')->cascadeOnDelete();
            $table->foreignId('ma_thanh_phan')->constrained('diem_thanh_phan')->cascadeOnDelete();
            $table->decimal('diem', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['ma_sinh_vien', 'ma_thanh_phan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diem_sinh_vien');
        Schema::dropIfExists('diem_thanh_phan');
    }
};
