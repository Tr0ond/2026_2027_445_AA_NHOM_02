<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Migration cũ từng đưa điểm thành phần lên mức môn học.
     *
     * Migration kế tiếp sẽ chuẩn hóa ngược về mức lớp học. Phần này vẫn
     * được giữ tương thích với SQLite để một database mới có thể chạy toàn
     * bộ lịch sử migration trước khi migration chuẩn hóa được áp dụng.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('diem_thanh_phan', 'ma_mon_hoc')) {
            Schema::table('diem_thanh_phan', function (Blueprint $table) {
                $table->foreignId('ma_mon_hoc')
                    ->nullable()
                    ->after('ma_lop_hoc')
                    ->constrained('mon_hoc')
                    ->nullOnDelete();
            });
        }

        // Bỏ unique cũ nếu còn (giờ thành phần mức môn có ma_lop_hoc = NULL)
        $indexes = collect(Schema::getIndexes('diem_thanh_phan'));
        $idx = $indexes->contains(fn (array $index) => ($index['name'] ?? null)
            === 'diem_thanh_phan_ma_lop_hoc_ten_thanh_phan_unique');
        $hasMaLopHocIndex = $indexes->contains(fn (array $index) => ($index['name'] ?? null)
            === 'idx_dtp_ma_lop_hoc');

        if ($idx && ! $hasMaLopHocIndex) {
            Schema::table('diem_thanh_phan', function (Blueprint $table) {
                // MySQL cần một index thay thế cho FK trước khi unique index bị bỏ.
                $table->index('ma_lop_hoc', 'idx_dtp_ma_lop_hoc');
            });
        }

        if ($idx) {
            Schema::table('diem_thanh_phan', function (Blueprint $table) {
                $table->dropUnique('diem_thanh_phan_ma_lop_hoc_ten_thanh_phan_unique');
            });
        }

        // Gán môn cho các thành phần mức lớp chưa có môn.
        // Dùng query builder thay cho UPDATE ... JOIN để chạy được cả SQLite.
        $thanhPhanChuaCoMon = DB::table('diem_thanh_phan')
            ->whereNull('ma_mon_hoc')
            ->whereNotNull('ma_lop_hoc')
            ->get(['id', 'ma_lop_hoc']);

        foreach ($thanhPhanChuaCoMon as $thanhPhan) {
            $maMonHoc = DB::table('lop_hoc')
                ->where('id', $thanhPhan->ma_lop_hoc)
                ->value('ma_mon_hoc');

            DB::table('diem_thanh_phan')
                ->where('id', $thanhPhan->id)
                ->update(['ma_mon_hoc' => $maMonHoc]);
        }

        // Gom nhóm trùng (môn + tên): giữ 1 dòng, chuyển điểm của dòng trùng sang rồi xóa
        $nhoms = DB::table('diem_thanh_phan')
            ->whereNotNull('ma_mon_hoc')
            ->groupBy('ma_mon_hoc', 'ten_thanh_phan')
            ->selectRaw('ma_mon_hoc, ten_thanh_phan, MIN(id) as id_giu')
            ->get();

        foreach ($nhoms as $nhom) {
            $trungIds = DB::table('diem_thanh_phan')
                ->where('ma_mon_hoc', $nhom->ma_mon_hoc)
                ->where('ten_thanh_phan', $nhom->ten_thanh_phan)
                ->where('id', '!=', $nhom->id_giu)
                ->pluck('id');

            if ($trungIds->isNotEmpty()) {
                DB::table('diem_sinh_vien')
                    ->whereIn('ma_thanh_phan', $trungIds)
                    ->update(['ma_thanh_phan' => $nhom->id_giu]);
                DB::table('diem_thanh_phan')->whereIn('id', $trungIds)->delete();
            }
        }

        // Cho phép ma_lop_hoc NULL (thành phần mức môn không gắn lớp nào)
        Schema::table('diem_thanh_phan', function (Blueprint $table) {
            $table->unsignedBigInteger('ma_lop_hoc')->nullable()->change();
        });

        // Dòng còn lại trở thành thành phần mức môn (ma_lop_hoc = NULL)
        DB::table('diem_thanh_phan')
            ->whereNotNull('ma_mon_hoc')
            ->whereNotNull('ma_lop_hoc')
            ->update(['ma_lop_hoc' => null]);
    }

    public function down(): void
    {
        if (Schema::hasColumn('diem_thanh_phan', 'ma_mon_hoc')) {
            Schema::table('diem_thanh_phan', function (Blueprint $table) {
                $table->dropConstrainedForeignId('ma_mon_hoc');
            });
        }
    }
};
