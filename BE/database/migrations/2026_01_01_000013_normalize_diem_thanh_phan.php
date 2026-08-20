<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Đưa điểm thành phần về đúng mức lớp học.
     *
     * ma_mon_hoc là thuộc tính suy diễn từ lop_hoc.ma_mon_hoc nên không được
     * lưu lặp lại trong diem_thanh_phan. Với dữ liệu cũ đang dùng một dòng
     * chung cho cả môn, migration nhân dòng đó cho từng lớp tương ứng và
     * chuyển điểm sinh viên sang đúng lớp mà sinh viên đã đăng ký.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('diem_thanh_phan', 'ma_mon_hoc')) {
            $this->ensureMaLopHocNotNullAndUnique();

            return;
        }

        $thanhPhans = DB::table('diem_thanh_phan')
            ->whereNotNull('ma_mon_hoc')
            ->get();

        foreach ($thanhPhans as $thanhPhan) {
                $lopIds = DB::table('lop_hoc')
                    ->where('ma_mon_hoc', $thanhPhan->ma_mon_hoc)
                    ->pluck('id');

                if ($lopIds->isEmpty()) {
                    throw new RuntimeException(
                        "Không thể chuẩn hóa điểm thành phần #{$thanhPhan->id}: môn #{$thanhPhan->ma_mon_hoc} chưa có lớp học."
                    );
                }

                $diemCu = DB::table('diem_sinh_vien')
                    ->where('ma_thanh_phan', $thanhPhan->id)
                    ->get();

                foreach ($lopIds as $lopId) {
                    $thanhPhanMoi = DB::table('diem_thanh_phan')
                        ->where('ma_lop_hoc', $lopId)
                        ->where('ten_thanh_phan', $thanhPhan->ten_thanh_phan)
                        ->first();

                    $now = now();
                    $maThanhPhanMoi = $thanhPhanMoi?->id;

                    if (! $maThanhPhanMoi) {
                        $maThanhPhanMoi = DB::table('diem_thanh_phan')->insertGetId([
                            'ma_lop_hoc' => $lopId,
                            'ten_thanh_phan' => $thanhPhan->ten_thanh_phan,
                            'trong_so' => $thanhPhan->trong_so,
                            'created_at' => $thanhPhan->created_at ?? $now,
                            'updated_at' => $now,
                        ]);
                    }

                    foreach ($diemCu as $diem) {
                        $thuocLop = DB::table('dang_ky_lop_hoc')
                            ->where('ma_lop_hoc', $lopId)
                            ->where('ma_sinh_vien', $diem->ma_sinh_vien)
                            ->whereIn('trang_thai', ['da_duyet', 'huy'])
                            ->exists();

                        if (! $thuocLop) {
                            continue;
                        }

                        DB::table('diem_sinh_vien')->updateOrInsert(
                            [
                                'ma_sinh_vien' => $diem->ma_sinh_vien,
                                'ma_thanh_phan' => $maThanhPhanMoi,
                            ],
                            [
                                'diem' => $diem->diem,
                                'created_at' => $diem->created_at ?? $now,
                                'updated_at' => $now,
                            ]
                        );
                    }
                }

                DB::table('diem_sinh_vien')
                    ->where('ma_thanh_phan', $thanhPhan->id)
                    ->delete();
                DB::table('diem_thanh_phan')
                    ->where('id', $thanhPhan->id)
                    ->delete();
        }

        $orphan = DB::table('diem_thanh_phan')
            ->whereNull('ma_lop_hoc')
            ->exists();

        if ($orphan) {
            throw new RuntimeException(
                'Không thể chuẩn hóa diem_thanh_phan: vẫn còn thành phần không gắn với lớp học.'
            );
        }

        Schema::table('diem_thanh_phan', function (Blueprint $table): void {
            $table->dropForeign(['ma_mon_hoc']);
            $table->dropColumn('ma_mon_hoc');
        });

        $this->ensureMaLopHocNotNullAndUnique();
    }

    public function down(): void
    {
        if (Schema::hasColumn('diem_thanh_phan', 'ma_mon_hoc')) {
            return;
        }

        Schema::table('diem_thanh_phan', function (Blueprint $table): void {
            $table->foreignId('ma_mon_hoc')
                ->nullable()
                ->after('ma_lop_hoc')
                ->constrained('mon_hoc')
                ->nullOnDelete();
        });

        $rows = DB::table('diem_thanh_phan as tp')
            ->join('lop_hoc as lh', 'lh.id', '=', 'tp.ma_lop_hoc')
            ->select('tp.id', 'lh.ma_mon_hoc')
            ->get();

        foreach ($rows as $row) {
            DB::table('diem_thanh_phan')
                ->where('id', $row->id)
                ->update(['ma_mon_hoc' => $row->ma_mon_hoc]);
        }
    }

    private function ensureMaLopHocNotNullAndUnique(): void
    {
        Schema::table('diem_thanh_phan', function (Blueprint $table): void {
            $table->unsignedBigInteger('ma_lop_hoc')->nullable(false)->change();
        });

        $hasUnique = collect(Schema::getIndexes('diem_thanh_phan'))
            ->contains(fn (array $index): bool => ($index['name'] ?? null)
                === 'diem_thanh_phan_ma_lop_hoc_ten_thanh_phan_unique');

        if (! $hasUnique) {
            Schema::table('diem_thanh_phan', function (Blueprint $table): void {
                $table->unique(
                    ['ma_lop_hoc', 'ten_thanh_phan'],
                    'diem_thanh_phan_ma_lop_hoc_ten_thanh_phan_unique'
                );
            });
        }
    }
};
