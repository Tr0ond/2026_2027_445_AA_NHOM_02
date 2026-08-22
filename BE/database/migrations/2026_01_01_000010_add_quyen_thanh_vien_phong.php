<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('thanh_vien_phong_truc_tuyen', function (Blueprint $table) {
            // Sinh viên giơ tay trong phòng
            $table->boolean('gio_tay')->default(false);
            // Quyền GV cấp cho sinh viên: dùng micro và chia sẻ màn hình
            $table->boolean('duoc_phep_mac')->default(false);
            $table->boolean('duoc_phep_chia_se')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('thanh_vien_phong_truc_tuyen', function (Blueprint $table) {
            $table->dropColumn(['gio_tay', 'duoc_phep_mac', 'duoc_phep_chia_se']);
        });
    }
};
