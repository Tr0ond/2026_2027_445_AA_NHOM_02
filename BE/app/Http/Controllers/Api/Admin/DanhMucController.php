<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\LichHoc;
use App\Models\LopHoc;
use App\Models\MonHoc;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** US20 - Quản lý danh mục môn học, lớp học (kèm lịch học của lớp). */
class DanhMucController extends Controller
{
    // ---------- Môn học ----------

    public function danhSachMonHoc(Request $request): JsonResponse
    {
        $query = MonHoc::query()
            ->when($request->filled('tu_khoa'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('ten_mon', 'like', '%'.$request->tu_khoa.'%')
                    ->orWhere('ma_mon_hoc', 'like', '%'.$request->tu_khoa.'%');
            }))
            ->with('lopHoc:id,ma_lop_hoc,ten_lop,ma_mon_hoc')
            ->withCount('lopHoc as so_lop')
            ->orderBy('ten_mon');

        return response()->json(['danh_sach' => $query->get()]);
    }

    public function luuMonHoc(Request $request, ?MonHoc $monHoc = null): JsonResponse
    {
        $data = $request->validate([
            'ma_mon_hoc' => ['required', 'string', 'max:20', Rule::unique('mon_hoc', 'ma_mon_hoc')->ignore($monHoc?->id)],
            'ten_mon' => ['required', 'string', 'max:200'],
            'so_tin_chi' => ['required', 'integer', 'min:1', 'max:10'],
            'mo_ta' => ['nullable', 'string'],
        ]);

        if ($monHoc?->exists) {
            $monHoc->update($data);

            return response()->json(['message' => 'Đã cập nhật môn học.']);
        }

        MonHoc::create($data);

        return response()->json(['message' => 'Đã thêm môn học.'], 201);
    }

    public function xoaMonHoc(MonHoc $monHoc): JsonResponse
    {
        if ($monHoc->lopHoc()->exists()) {
            return response()->json(['message' => 'Môn học đang có lớp, không thể xóa.'], 422);
        }

        $monHoc->delete();

        return response()->json(['message' => 'Đã xóa môn học.']);
    }

    // ---------- Lớp học ----------

    public function danhSachLopHoc(Request $request): JsonResponse
    {
        $query = LopHoc::query()->with('monHoc')
            ->withCount(['dangKy as so_sinh_vien' => fn ($q) => $q->where('trang_thai', 'da_duyet')])
            ->when($request->filled('tu_khoa'), fn ($q) => $q->where(function ($w) use ($request) {
                $w->where('ten_lop', 'like', '%'.$request->tu_khoa.'%')
                    ->orWhere('ma_lop_hoc', 'like', '%'.$request->tu_khoa.'%');
            }))
            ->orderByDesc('id');

        return response()->json(['danh_sach' => $query->get()]);
    }

    public function luuLopHoc(Request $request, ?LopHoc $lopHoc = null): JsonResponse
    {
        $data = $request->validate([
            'ma_lop_hoc' => ['required', 'string', 'max:20', Rule::unique('lop_hoc', 'ma_lop_hoc')->ignore($lopHoc?->id)],
            'ten_lop' => ['required', 'string', 'max:200'],
            'ma_mon_hoc' => ['required', 'integer', 'exists:mon_hoc,id'],
            'hoc_ky' => ['required', 'string', 'max:10'],
            'nam_hoc' => ['required', 'string', 'max:20'],
            'so_luong_toi_da' => ['required', 'integer', 'min:1', 'max:200'],
            'trang_thai' => ['required', 'in:mo_dang_ky,dang_hoc,da_ket_thuc'],
        ]);

        if ($lopHoc?->exists) {
            $lopHoc->update($data);

            return response()->json(['message' => 'Đã cập nhật lớp học.']);
        }

        LopHoc::create($data);

        return response()->json(['message' => 'Đã thêm lớp học.'], 201);
    }

    public function xoaLopHoc(LopHoc $lopHoc): JsonResponse
    {
        if ($lopHoc->dangKy()->exists() || $lopHoc->lichHoc()->exists()) {
            return response()->json(['message' => 'Lớp đã có sinh viên hoặc lịch học, không thể xóa.'], 422);
        }

        $lopHoc->delete();

        return response()->json(['message' => 'Đã xóa lớp học.']);
    }

    // ---------- Lịch học của lớp ----------

    public function lichHocCuaLop(LopHoc $lopHoc): JsonResponse
    {
        return response()->json([
            'danh_sach' => $lopHoc->lichHoc()->orderBy('ngay_hoc')->orderBy('gio_bat_dau')->get(),
        ]);
    }

    public function luuLichHoc(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $data = $request->validate([
            'ngay_hoc' => ['required', 'date'],
            'gio_bat_dau' => ['required', 'date_format:H:i'],
            'gio_ket_thuc' => ['required', 'date_format:H:i', 'after:gio_bat_dau'],
            'phong_hoc' => ['nullable', 'string', 'max:50'],
            'co_hoc_truc_tuyen' => ['required', 'boolean'],
            'chu_de' => ['nullable', 'string', 'max:200'],
        ], [
            'gio_ket_thuc.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
        ]);

        $lopHoc->lichHoc()->create($data + ['trang_thai' => 'ke_hoach']);

        return response()->json(['message' => 'Đã thêm buổi học.'], 201);
    }

    public function xoaLichHoc(LichHoc $lichHoc): JsonResponse
    {
        $lichHoc->delete();

        return response()->json(['message' => 'Đã xóa buổi học.']);
    }

    /**
     * US20 - Tạo lịch học nhanh: chọn các thứ trong tuần + khung giờ + khoảng ngày,
     * hệ thống tự sinh buổi học lặp hàng tuần (VD: T2, T4 từ 01/09 đến 30/11).
     */
    public function taoLichNhanh(Request $request, LopHoc $lopHoc): JsonResponse
    {
        $data = $request->validate([
            'cac_thu' => ['required', 'array', 'min:1'],
            'cac_thu.*' => ['integer', 'between:0,6'], // 0=CN, 2=T2 ...
            'gio_bat_dau' => ['required', 'date_format:H:i'],
            'gio_ket_thuc' => ['required', 'date_format:H:i', 'after:gio_bat_dau'],
            'tu_ngay' => ['required', 'date'],
            'den_ngay' => ['required', 'date', 'after_or_equal:tu_ngay'],
            'phong_hoc' => ['nullable', 'string', 'max:50'],
            'co_hoc_truc_tuyen' => ['required', 'boolean'],
        ], [
            'cac_thu.min' => 'Chọn ít nhất một thứ trong tuần.',
            'gio_ket_thuc.after' => 'Giờ kết thúc phải sau giờ bắt đầu.',
            'den_ngay.after_or_equal' => 'Ngày kết thúc phải từ ngày bắt đầu trở đi.',
        ]);

        $tuNgay = \Illuminate\Support\Carbon::parse($data['tu_ngay']);
        $denNgay = \Illuminate\Support\Carbon::parse($data['den_ngay']);
        $cacThu = array_map('intval', $data['cac_thu']);

        // Bỏ qua buổi đã tồn tại trùng (ngày + giờ bắt đầu) để tạo lại không bị nhân đôi
        $daTonTai = $lopHoc->lichHoc()
            ->whereBetween('ngay_hoc', [$tuNgay->toDateString(), $denNgay->toDateString()])
            ->get()
            ->keyBy(fn ($lh) => $lh->ngay_hoc->toDateString().'|'.$lh->gio_bat_dau->format('H:i'));

        $daTao = 0;
        for ($ngay = $tuNgay->copy(); $ngay->lte($denNgay); $ngay->addDay()) {
            if (! in_array($ngay->dayOfWeek, $cacThu, true)) {
                continue;
            }

            $khoa = $ngay->toDateString().'|'.$data['gio_bat_dau'];
            if ($daTonTai->has($khoa)) {
                continue;
            }

            $lopHoc->lichHoc()->create([
                'ngay_hoc' => $ngay->toDateString(),
                'gio_bat_dau' => $data['gio_bat_dau'],
                'gio_ket_thuc' => $data['gio_ket_thuc'],
                'phong_hoc' => $data['phong_hoc'] ?? null,
                'co_hoc_truc_tuyen' => $data['co_hoc_truc_tuyen'],
                'trang_thai' => 'ke_hoach',
            ]);
            $daTao++;
        }

        return response()->json([
            'message' => "Đã tạo {$daTao} buổi học.",
            'so_buoi' => $daTao,
        ], 201);
    }
}
