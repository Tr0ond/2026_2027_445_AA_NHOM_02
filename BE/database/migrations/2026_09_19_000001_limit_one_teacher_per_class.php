<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Dữ liệu cũ có thể đã gán một lớp cho nhiều giảng viên. Giữ bản ghi
        // được tạo sớm nhất (id nhỏ nhất) trước khi thêm ràng buộc duy nhất.
        $phanCongCanGiu = DB::table('phan_cong_giang_day')
            ->select('ma_lop_hoc', DB::raw('MIN(id) as id_giu_lai'))
            ->groupBy('ma_lop_hoc')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($phanCongCanGiu as $phanCong) {
            DB::table('phan_cong_giang_day')
                ->where('ma_lop_hoc', $phanCong->ma_lop_hoc)
                ->where('id', '<>', $phanCong->id_giu_lai)
                ->delete();
        }

        Schema::table('phan_cong_giang_day', function (Blueprint $table) {
            $table->unique('ma_lop_hoc', 'phan_cong_giang_day_lop_unique');
        });
    }

    public function down(): void
    {
        Schema::table('phan_cong_giang_day', function (Blueprint $table) {
            $table->dropUnique('phan_cong_giang_day_lop_unique');
        });
    }
};
