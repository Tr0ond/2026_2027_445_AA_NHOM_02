<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('don_xin_phep', 'ma_lich_hoc')) {
            Schema::table('don_xin_phep', function (Blueprint $table): void {
                $table->foreignId('ma_lich_hoc')
                    ->nullable()
                    ->after('ma_lop_hoc')
                    ->constrained('lich_hoc')
                    ->cascadeOnDelete();
            });
        }

        $donChuaGanLich = DB::table('don_xin_phep')
            ->whereNull('ma_lich_hoc')
            ->get(['id', 'ma_lop_hoc', 'ngay_nghi']);

        foreach ($donChuaGanLich as $don) {
            $maLichHoc = DB::table('lich_hoc')
                ->where('ma_lop_hoc', $don->ma_lop_hoc)
                ->whereDate('ngay_hoc', $don->ngay_nghi)
                ->orderBy('id')
                ->value('id');

            if (! $maLichHoc) {
                throw new RuntimeException(
                    "Không thể gắn đơn xin phép #{$don->id} với buổi học ngày {$don->ngay_nghi}."
                );
            }

            DB::table('don_xin_phep')
                ->where('id', $don->id)
                ->update(['ma_lich_hoc' => $maLichHoc]);
        }

        Schema::table('don_xin_phep', function (Blueprint $table): void {
            $table->unsignedBigInteger('ma_lich_hoc')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('don_xin_phep', 'ma_lich_hoc')) {
            Schema::table('don_xin_phep', function (Blueprint $table): void {
                $table->dropForeign(['ma_lich_hoc']);
                $table->dropColumn('ma_lich_hoc');
            });
        }
    }
};
