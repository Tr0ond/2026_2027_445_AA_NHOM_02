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

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ---- Tài khoản ----
        User::create([
            'ho_ten' => 'Quản trị viên hệ thống',
            'email' => 'admin@portal.test',
            'mat_khau' => 'password',
            'vai_tro' => 'admin',
        ]);

        $giangViensData = [
            ['ma' => 'GV001', 'ho_ten' => 'TS. Nguyễn Văn Minh', 'email' => 'minh.gv@portal.test', 'hoc_vi' => 'Tiến sĩ', 'bo_mon' => 'Công nghệ phần mềm'],
            ['ma' => 'GV002', 'ho_ten' => 'ThS. Trần Thị Hoa', 'email' => 'hoa.gv@portal.test', 'hoc_vi' => 'Thạc sĩ', 'bo_mon' => 'Hệ thống thông tin'],
            ['ma' => 'GV003', 'ho_ten' => 'PGS. Lê Văn Cường', 'email' => 'cuong.gv@portal.test', 'hoc_vi' => 'Phó giáo sư', 'bo_mon' => 'Khoa học máy tính'],
        ];

        $giangViens = [];
        foreach ($giangViensData as $gv) {
            $tk = User::create([
                'ho_ten' => $gv['ho_ten'],
                'email' => $gv['email'],
                'mat_khau' => 'password',
                'vai_tro' => 'giang_vien',
            ]);
            $giangViens[$gv['ma']] = GiangVien::create([
                'ma_giang_vien' => $gv['ma'],
                'ma_tai_khoan' => $tk->id,
                'hoc_vi' => $gv['hoc_vi'],
                'bo_mon' => $gv['bo_mon'],
            ]);
        }

        $sinhViens = [];
        $hoDem = ['Nguyễn', 'Trần', 'Lê', 'Phạm', 'Hoàng', 'Vũ', 'Đặng', 'Bùi'];
        $ten = ['An', 'Bình', 'Chi', 'Dũng', 'Phúc', 'Giang', 'Hà', 'Khang', 'Ly', 'Nam', 'Oanh', 'Quang'];
        foreach ($ten as $i => $t) {
            $tk = User::create([
                'ho_ten' => $hoDem[$i % count($hoDem)].' '.$t,
                'email' => 'sv'.($i + 1).'@portal.test',
                'mat_khau' => 'password',
                'vai_tro' => 'sinh_vien',
            ]);
            $sinhViens[] = SinhVien::create([
                'ma_sinh_vien' => 'SV'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'ma_tai_khoan' => $tk->id,
                'lop_danh_nghia' => 'CNTT-K48A',
                'khoa' => 'Công nghệ thông tin',
            ]);
        }

        // ---- Môn học ----
        $mons = [
            ['MH001', 'Công nghệ phần mềm', 3],
            ['MH002', 'Cơ sở dữ liệu', 4],
            ['MH003', 'Trí tuệ nhân tạo', 3],
            ['MH004', 'Lập trình web nâng cao', 3],
            ['MH005', 'An toàn thông tin', 3],
        ];
        $monIds = [];
        foreach ($mons as [$ma, $tenMon, $tc]) {
            $m = MonHoc::create(['ma_mon_hoc' => $ma, 'ten_mon' => $tenMon, 'so_tin_chi' => $tc]);
            $monIds[$ma] = $m->id;
        }

        // ---- Lớp học ----
        $lopsData = [
            ['LH001', 'CNT48-K48A', 'MH001', 'GV001', 'mo_dang_ky'],
            ['LH002', 'CSDL-K48A', 'MH002', 'GV002', 'dang_hoc'],
            ['LH003', 'AI-K48A', 'MH003', 'GV003', 'mo_dang_ky'],
        ];
        $lopIds = [];
        foreach ($lopsData as [$maLop, $tenLop, $maMon, $maGV, $trangThai]) {
            $lop = LopHoc::create([
                'ma_lop_hoc' => $maLop,
                'ten_lop' => $tenLop,
                'ma_mon_hoc' => $monIds[$maMon],
                'hoc_ky' => 'HK1',
                'nam_hoc' => '2026-2027',
                'so_luong_toi_da' => 40,
                'trang_thai' => $trangThai,
            ]);
            $lopIds[$maLop] = $lop->id;

            PhanCongGiangDay::create([
                'ma_giang_vien' => $giangViens[$maGV]->id,
                'ma_lop_hoc' => $lop->id,
                'vai_tro_phu_trach' => 'giang_vien_chinh',
            ]);
        }

        // ---- Ghi danh sinh viên ----
        foreach ($sinhViens as $i => $sv) {
            $cacsLop = [$lopIds['LH001']];
            if ($i % 2 === 0) {
                $cacsLop[] = $lopIds['LH002'];
            }
            if ($i % 3 === 0) {
                $cacsLop[] = $lopIds['LH003'];
            }
            foreach ($cacsLop as $maLop) {
                DangKyLopHoc::create([
                    'ma_sinh_vien' => $sv->id,
                    'ma_lop_hoc' => $maLop,
                    'ngay_dang_ky' => now()->subDays(rand(5, 30))->toDateString(),
                    'trang_thai' => 'da_duyet',
                ]);
            }
        }

        // ---- Lịch học: có buổi hôm nay để demo mở phòng + điểm danh ngay ----
        $homNay = now()->startOfDay();
        $buoiHoc = [
            [$lopIds['LH001'], $homNay->copy()->addHours(9), $homNay->copy()->addHours(11), 'Buổi 1: Quy trình phát triển phần mềm', true, 'ke_hoach'],
            [$lopIds['LH002'], $homNay->copy()->addHours(13), $homNay->copy()->addHours(15), 'Buổi 3: Mô hình hóa dữ liệu', true, 'ke_hoach'],
            [$lopIds['LH001'], $homNay->copy()->addDays(2)->addHours(9), $homNay->copy()->addDays(2)->addHours(11), 'Buổi 2: Kiểm thử phần mềm', true, 'ke_hoach'],
            [$lopIds['LH003'], $homNay->copy()->addDays(3)->addHours(7)->addMinutes(30), $homNay->copy()->addDays(3)->addHours(10), 'Buổi 1: Giới thiệu AI', false, 'ke_hoach'],
            [$lopIds['LH002'], $homNay->copy()->subDays(2)->addHours(13), $homNay->copy()->subDays(2)->addHours(15), 'Buổi 2: SQL nâng cao', true, 'da_hoc'],
        ];

        foreach ($buoiHoc as [$maLop, $batDau, $ketThuc, $chuDe, $trucTuyen, $trangThai]) {
            LichHoc::create([
                'ma_lop_hoc' => $maLop,
                'ngay_hoc' => $batDau->toDateString(),
                'gio_bat_dau' => $batDau->format('H:i'),
                'gio_ket_thuc' => $ketThuc->format('H:i'),
                'phong_hoc' => $trucTuyen ? null : 'D201',
                'co_hoc_truc_tuyen' => $trucTuyen,
                'chu_de' => $chuDe,
                'trang_thai' => $trangThai,
            ]);
        }

        // ---- Điểm thành phần: quản lý theo LỚP (ma_mon_hoc suy ra qua lop_hoc) ----
        foreach ($lopIds as $maLopHoc) {
            DiemThanhPhan::create(['ma_lop_hoc' => $maLopHoc, 'ten_thanh_phan' => 'Chuyên cần', 'trong_so' => 1]);
            DiemThanhPhan::create(['ma_lop_hoc' => $maLopHoc, 'ten_thanh_phan' => 'Giữa kỳ', 'trong_so' => 2]);
            DiemThanhPhan::create(['ma_lop_hoc' => $maLopHoc, 'ten_thanh_phan' => 'Cuối kỳ', 'trong_so' => 7]);
        }

        $this->command->info('Seed hoàn tất! Tài khoản demo (mật khẩu: password):');
        $this->command->table(
            ['Vai trò', 'Email'],
            [
                ['Admin', 'admin@portal.test'],
                ['Giảng viên', 'minh.gv@portal.test'],
                ['Giảng viên', 'hoa.gv@portal.test'],
                ['Sinh viên', 'sv1@portal.test'],
            ]
        );
    }
}
