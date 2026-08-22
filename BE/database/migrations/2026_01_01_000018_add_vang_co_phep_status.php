<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("ALTER TABLE chi_tiet_diem_danh MODIFY trang_thai_diem_danh ENUM('co_mat', 'vang', 'di_muon', 'xin_phep', 'vang_co_phep') NOT NULL DEFAULT 'co_mat'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::table('chi_tiet_diem_danh')
            ->where('trang_thai_diem_danh', 'vang_co_phep')
            ->update(['trang_thai_diem_danh' => 'xin_phep']);

        DB::statement("ALTER TABLE chi_tiet_diem_danh MODIFY trang_thai_diem_danh ENUM('co_mat', 'vang', 'di_muon', 'xin_phep') NOT NULL DEFAULT 'co_mat'");
    }
};
