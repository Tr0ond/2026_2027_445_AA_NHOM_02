<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lich_hoc', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ma_lop_hoc')->constrained('lop_hoc')->cascadeOnDelete();
            $table->date('ngay_hoc');
            $table->time('gio_bat_dau');
            $table->time('gio_ket_thuc');
            $table->string('phong_hoc', 50)->nullable();
            $table->boolean('co_hoc_truc_tuyen')->default(false);
            $table->string('chu_de', 200)->nullable();
            $table->enum('trang_thai', ['ke_hoach', 'dang_dien_ra', 'da_hoc', 'da_huy'])->default('ke_hoach');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lich_hoc');
    }
};
