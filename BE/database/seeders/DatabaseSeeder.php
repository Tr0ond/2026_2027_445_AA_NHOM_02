<?php

namespace Database\Seeders;

use App\Models\DangKyLopHoc;
use App\Models\DiemThanhPhan;
use App\Models\GiangVien;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MonHoc;
use App\Models\PhanCongGiangDay;
use App\Models\SinhVien;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    private const MAT_KHAU_DEMO = 'password';

    private const SO_GIANG_VIEN = 20;

    private const SO_SINH_VIEN = 20;

    private const SO_LOP_MOI_MON = 5;

    private const SO_BUOI_MOI_LOP = 6;

    public function run(): void
    {
        DB::transaction(function () {
            $this->taoTaiKhoanAdmin();
            $giangViens = $this->taoGiangViens();
            $sinhViens = $this->taoSinhViens();
            $monHocs = $this->taoMonHocs();
            $lopHocs = $this->taoLopHocVaPhanCong($monHocs, $giangViens);

            $this->ghiDanhSinhVien($sinhViens, $lopHocs);
            $this->taoLichHoc($lopHocs);
            $this->taoThanhPhanDiem($lopHocs);
        });

        $this->inThongTinDangNhap();
    }

    private function taoTaiKhoanAdmin(): void
    {
        User::create([
            'ho_ten' => 'Quản trị viên hệ thống',
            'email' => 'admin@portal.test',
            'mat_khau' => self::MAT_KHAU_DEMO,
            'vai_tro' => 'admin',
            'trang_thai' => 'hoat_dong',
        ]);
    }

    /** @return array<int, GiangVien> */
    private function taoGiangViens(): array
    {
        $hoTen = [
            'Nguyễn Văn Minh', 'Trần Thị Hoa', 'Lê Văn Cường', 'Phạm Thu Hà',
            'Hoàng Đức Anh', 'Vũ Ngọc Lan', 'Đặng Quang Huy', 'Bùi Thanh Tùng',
            'Đỗ Mai Phương', 'Hồ Quốc Bảo', 'Ngô Thị Hương', 'Dương Minh Khang',
            'Lý Anh Tuấn', 'Phan Thảo Vy', 'Trương Gia Bảo', 'Mai Hoàng Nam',
            'Tạ Khánh Linh', 'Đinh Công Thành', 'Võ Thu Trang', 'Cao Nhật Long',
        ];
        $hocVi = ['Tiến sĩ', 'Thạc sĩ', 'Phó giáo sư'];
        $tienTo = ['TS.', 'ThS.', 'PGS.'];
        $boMon = [
            'Công nghệ phần mềm', 'Hệ thống thông tin', 'Khoa học máy tính',
            'Mạng máy tính', 'An toàn thông tin',
        ];
        $ketQua = [];

        for ($i = 0; $i < self::SO_GIANG_VIEN; $i++) {
            $soThuTu = $i + 1;
            $taiKhoan = User::create([
                'ho_ten' => $tienTo[$i % count($tienTo)].' '.$hoTen[$i],
                'email' => 'gv'.str_pad((string) $soThuTu, 2, '0', STR_PAD_LEFT).'@portal.test',
                'mat_khau' => self::MAT_KHAU_DEMO,
                'vai_tro' => 'giang_vien',
                'trang_thai' => 'hoat_dong',
            ]);

            $ketQua[] = GiangVien::create([
                'ma_giang_vien' => 'GV'.str_pad((string) $soThuTu, 3, '0', STR_PAD_LEFT),
                'ma_tai_khoan' => $taiKhoan->id,
                'hoc_vi' => $hocVi[$i % count($hocVi)],
                'bo_mon' => $boMon[$i % count($boMon)],
            ]);
        }

        return $ketQua;
    }

    /** @return array<int, SinhVien> */
    private function taoSinhViens(): array
    {
        $hoTen = [
            'Nguyễn Hoàng An', 'Trần Gia Bình', 'Lê Minh Chi', 'Phạm Anh Dũng',
            'Hoàng Đức Phúc', 'Vũ Minh Giang', 'Đặng Thu Hà', 'Bùi Quang Khang',
            'Đỗ Khánh Ly', 'Hồ Thành Nam', 'Ngô Bảo Oanh', 'Dương Nhật Quang',
            'Lý Tuấn Anh', 'Phan Thùy Dương', 'Trương Hải Đăng', 'Mai Ngọc Hân',
            'Tạ Quốc Khánh', 'Đinh Phương Linh', 'Võ Minh Nhật', 'Cao Thanh Tâm',
        ];
        $ketQua = [];

        for ($i = 0; $i < self::SO_SINH_VIEN; $i++) {
            $soThuTu = $i + 1;
            $taiKhoan = User::create([
                'ho_ten' => $hoTen[$i],
                'email' => 'sv'.$soThuTu.'@portal.test',
                'mat_khau' => self::MAT_KHAU_DEMO,
                'vai_tro' => 'sinh_vien',
                'trang_thai' => 'hoat_dong',
            ]);

            $ketQua[] = SinhVien::create([
                'ma_sinh_vien' => 'SV'.str_pad((string) $soThuTu, 4, '0', STR_PAD_LEFT),
                'ma_tai_khoan' => $taiKhoan->id,
                'lop_danh_nghia' => 'CNTT-K48'.chr(65 + ($i % 4)),
                'khoa' => 'Công nghệ thông tin',
                'ngay_sinh' => Carbon::create(2007, 1, 1)->addDays($i * 17)->toDateString(),
                'gioi_tinh' => $i % 2 === 0 ? 'nam' : 'nu',
            ]);
        }

        return $ketQua;
    }

    /** @return array<int, MonHoc> */
    private function taoMonHocs(): array
    {
        $duLieu = [
            ['Công nghệ phần mềm', 3],
            ['Cơ sở dữ liệu', 4],
            ['Trí tuệ nhân tạo', 3],
            ['Lập trình web nâng cao', 3],
            ['An toàn thông tin', 3],
            ['Mạng máy tính', 3],
            ['Hệ điều hành', 3],
            ['Cấu trúc dữ liệu và giải thuật', 4],
            ['Phân tích thiết kế hệ thống', 3],
            ['Điện toán đám mây', 3],
            ['Khoa học dữ liệu', 3],
            ['Phát triển ứng dụng di động', 3],
            ['Kiểm thử phần mềm', 3],
            ['Internet vạn vật', 3],
            ['Học máy', 3],
        ];
        $ketQua = [];

        foreach ($duLieu as $i => [$tenMon, $soTinChi]) {
            $ketQua[] = MonHoc::create([
                'ma_mon_hoc' => 'MH'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                'ten_mon' => $tenMon,
                'so_tin_chi' => $soTinChi,
                'mo_ta' => 'Dữ liệu mẫu cho môn '.$tenMon.'.',
            ]);
        }

        return $ketQua;
    }

    /**
     * Tạo đúng 5 lớp cho mỗi môn và phân công luân phiên để mỗi giảng viên
     * phụ trách 3-4 lớp, đồng thời mỗi lớp chỉ có một giảng viên.
     *
     * @param  array<int, MonHoc>  $monHocs
     * @param  array<int, GiangVien>  $giangViens
     * @return array<int, array{lop: LopHoc, mon_index: int, giang_vien_index: int, thu_tu_cua_giang_vien: int}>
     */
    private function taoLopHocVaPhanCong(array $monHocs, array $giangViens): array
    {
        $ketQua = [];
        $chiSoLop = 0;

        foreach ($monHocs as $chiSoMon => $monHoc) {
            for ($nhom = 1; $nhom <= self::SO_LOP_MOI_MON; $nhom++) {
                $chiSoGiangVien = $chiSoLop % count($giangViens);
                $thuTuCuaGiangVien = intdiv($chiSoLop, count($giangViens));
                $hocKy = $chiSoMon < 8 ? 'HK1' : 'HK2';
                $lop = LopHoc::create([
                    'ma_lop_hoc' => 'LH'.str_pad((string) ($chiSoLop + 1), 3, '0', STR_PAD_LEFT),
                    'ten_lop' => $monHoc->ma_mon_hoc.' - Nhóm '.str_pad((string) $nhom, 2, '0', STR_PAD_LEFT),
                    'ma_mon_hoc' => $monHoc->id,
                    'hoc_ky' => $hocKy,
                    'nam_hoc' => '2026-2027',
                    'so_luong_toi_da' => 40,
                    'trang_thai' => $hocKy === 'HK1' ? 'dang_hoc' : 'mo_dang_ky',
                ]);

                PhanCongGiangDay::create([
                    'ma_giang_vien' => $giangViens[$chiSoGiangVien]->id,
                    'ma_lop_hoc' => $lop->id,
                    'vai_tro_phu_trach' => 'giang_vien_chinh',
                ]);

                $ketQua[] = [
                    'lop' => $lop,
                    'mon_index' => $chiSoMon,
                    'giang_vien_index' => $chiSoGiangVien,
                    'thu_tu_cua_giang_vien' => $thuTuCuaGiangVien,
                ];
                $chiSoLop++;
            }
        }

        return $ketQua;
    }

    /**
     * Mỗi sinh viên học một nhóm của cả 15 môn. Với 20 sinh viên và 5 nhóm,
     * mỗi lớp có đúng 4 sinh viên để dữ liệu thống kê được phân bố đều.
     *
     * @param  array<int, SinhVien>  $sinhViens
     * @param  array<int, array{lop: LopHoc, mon_index: int, giang_vien_index: int, thu_tu_cua_giang_vien: int}>  $lopHocs
     */
    private function ghiDanhSinhVien(array $sinhViens, array $lopHocs): void
    {
        $lopTheoMonVaNhom = [];
        foreach ($lopHocs as $duLieuLop) {
            $lopTheoMonVaNhom[$duLieuLop['mon_index']][] = $duLieuLop['lop'];
        }

        foreach ($sinhViens as $chiSoSinhVien => $sinhVien) {
            $chiSoNhom = $chiSoSinhVien % self::SO_LOP_MOI_MON;
            foreach ($lopTheoMonVaNhom as $chiSoMon => $cacLop) {
                DangKyLopHoc::create([
                    'ma_sinh_vien' => $sinhVien->id,
                    'ma_lop_hoc' => $cacLop[$chiSoNhom]->id,
                    'ngay_dang_ky' => now()->subDays(10 + $chiSoSinhVien + $chiSoMon)->toDateString(),
                    'trang_thai' => 'da_duyet',
                ]);
            }
        }
    }

    /**
     * Mỗi lớp có 6 buổi. Các lớp của cùng một giảng viên được xếp vào những
     * ngày khác nhau nên seeder không tự tạo xung đột lịch giảng dạy.
     *
     * @param  array<int, array{lop: LopHoc, mon_index: int, giang_vien_index: int, thu_tu_cua_giang_vien: int}>  $lopHocs
     */
    private function taoLichHoc(array $lopHocs): void
    {
        $thuHaiTiepTheo = now()->startOfWeek(Carbon::MONDAY)->addWeek()->startOfDay();
        $cacCaHoc = [
            ['07:30', '09:30'],
            ['09:45', '11:45'],
            ['13:00', '15:00'],
            ['15:15', '17:15'],
        ];

        foreach ($lopHocs as $chiSoLop => $duLieuLop) {
            $lop = $duLieuLop['lop'];
            $thuTuCuaGiangVien = $duLieuLop['thu_tu_cua_giang_vien'];
            $hocKyHai = $lop->hoc_ky === 'HK2';
            $batDauHocKy = $hocKyHai
                ? $thuHaiTiepTheo->copy()->addMonths(4)->startOfWeek(Carbon::MONDAY)
                : $thuHaiTiepTheo->copy();
            [$gioBatDau, $gioKetThuc] = $cacCaHoc[$duLieuLop['giang_vien_index'] % count($cacCaHoc)];
            $hocTrucTuyen = $chiSoLop % 2 === 0;

            for ($buoi = 0; $buoi < self::SO_BUOI_MOI_LOP; $buoi++) {
                $ngayHoc = $batDauHocKy->copy()
                    ->addDays($thuTuCuaGiangVien)
                    ->addWeeks($buoi);

                LichHoc::create([
                    'ma_lop_hoc' => $lop->id,
                    'ngay_hoc' => $ngayHoc->toDateString(),
                    'gio_bat_dau' => $gioBatDau,
                    'gio_ket_thuc' => $gioKetThuc,
                    'phong_hoc' => $hocTrucTuyen
                        ? null
                        : 'D'.str_pad((string) (101 + $duLieuLop['giang_vien_index']), 3, '0', STR_PAD_LEFT),
                    'co_hoc_truc_tuyen' => $hocTrucTuyen,
                    'chu_de' => 'Buổi '.($buoi + 1).' - '.$lop->ten_lop,
                    'trang_thai' => 'ke_hoach',
                ]);
            }
        }
    }

    /**
     * @param  array<int, array{lop: LopHoc, mon_index: int, giang_vien_index: int, thu_tu_cua_giang_vien: int}>  $lopHocs
     */
    private function taoThanhPhanDiem(array $lopHocs): void
    {
        foreach ($lopHocs as $duLieuLop) {
            foreach ([['Chuyên cần', 1], ['Giữa kỳ', 2], ['Cuối kỳ', 7]] as [$ten, $trongSo]) {
                DiemThanhPhan::create([
                    'ma_lop_hoc' => $duLieuLop['lop']->id,
                    'ten_thanh_phan' => $ten,
                    'trong_so' => $trongSo,
                ]);
            }
        }
    }

    private function inThongTinDangNhap(): void
    {
        $this->command?->info('Seed hoàn tất: 20 sinh viên, 20 giảng viên, 15 môn và 75 lớp.');
        $this->command?->info('Tất cả tài khoản dùng mật khẩu: '.self::MAT_KHAU_DEMO);
        $this->command?->table(
            ['Vai trò', 'Tài khoản'],
            [
                ['Admin', 'admin@portal.test'],
                ['Giảng viên', 'gv01@portal.test ... gv20@portal.test'],
                ['Sinh viên', 'sv1@portal.test ... sv20@portal.test'],
            ]
        );
    }
}
