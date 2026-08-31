<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Migration 000018 đã hỗ trợ MySQL nhưng bỏ qua SQLite dùng cho kiểm thử.
        // Không sửa migration đã chạy, không thay đổi dữ liệu/bảng MySQL hiện có.
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        Schema::table('chi_tiet_diem_danh', function (Blueprint $table): void {
            $table->enum('trang_thai_diem_danh', ['co_mat', 'vang', 'di_muon', 'xin_phep', 'vang_co_phep'])
                ->default('co_mat')->change();
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::table('chi_tiet_diem_danh')->where('trang_thai_diem_danh', 'vang_co_phep')
            ->update(['trang_thai_diem_danh' => 'xin_phep']);
        Schema::table('chi_tiet_diem_danh', function (Blueprint $table): void {
            $table->enum('trang_thai_diem_danh', ['co_mat', 'vang', 'di_muon', 'xin_phep'])
                ->default('co_mat')->change();
        });
    }
};
