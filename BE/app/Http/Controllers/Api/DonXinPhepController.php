<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DonXinPhep;
use App\Models\SinhVien;
use App\Models\GiangVien;
use App\Models\LopHoc;
use App\Models\LichHoc;
use App\Models\PhanCongGiangDay;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DonXinPhepController extends Controller
{
    /**
     * Sinh viên nộp đơn xin nghỉ học
     */
    public function store(Request $request)
    {
        $user = Auth::user();
        if (!$user->laSinhVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $validated = $request->validate([
            'ma_lop_hoc' => 'required|exists:lop_hoc,id',
            'ma_lich_hoc' => 'required|exists:lich_hoc,id',
            'ngay_nghi' => 'required|date',
            'ly_do' => 'required|string|max:500',
        ]);

        $sinhVien = SinhVien::where('ma_tai_khoan', $user->id)->first();
        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin sinh viên'], 404);
        }

        // Kiểm tra sinh viên có đăng ký lớp học này không
        $dangKy = DB::table('dang_ky_lop_hoc')
            ->where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lop_hoc', $validated['ma_lop_hoc'])
            ->where('trang_thai', '!=', 'huy')
            ->first();

        if (!$dangKy) {
            return response()->json(['message' => 'Bạn không đăng ký lớp học này'], 400);
        }

        // Kiểm tra lịch học có thuộc lớp học này không
        $lichHoc = LichHoc::where('id', $validated['ma_lich_hoc'])
            ->where('ma_lop_hoc', $validated['ma_lop_hoc'])
            ->first();

        if (!$lichHoc) {
            return response()->json(['message' => 'Lịch học không thuộc lớp học này'], 400);
        }

        // Kiểm tra ngày nghỉ có trùng với lịch học không
        if ($lichHoc->ngay_hoc != $validated['ngay_nghi']) {
            return response()->json(['message' => 'Ngày nghỉ không trùng với ngày lịch học'], 400);
        }

        // Kiểm tra đã có đơn xin nghỉ cho buổi học này chưa
        $donTonTai = DonXinPhep::where('ma_sinh_vien', $sinhVien->id)
            ->where('ma_lich_hoc', $validated['ma_lich_hoc'])
            ->whereIn('trang_thai', ['cho_duyet', 'da_duyet'])
            ->first();

        if ($donTonTai) {
            return response()->json(['message' => 'Bạn đã có đơn xin nghỉ cho buổi học này'], 400);
        }

        DB::beginTransaction();
        try {
            $don = DonXinPhep::create([
                'ma_sinh_vien' => $sinhVien->id,
                'ma_lop_hoc' => $validated['ma_lop_hoc'],
                'ma_lich_hoc' => $validated['ma_lich_hoc'],
                'ngay_nghi' => $validated['ngay_nghi'],
                'ly_do' => $validated['ly_do'],
                'trang_thai' => 'cho_duyet',
            ]);

            DB::commit();
            return response()->json(['message' => 'Đơn xin nghỉ đã được gửi', 'data' => $don->load(['sinhVien', 'lopHoc', 'lichHoc'])], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Sinh viên xem danh sách đơn của mình
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        if (!$user->laSinhVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $sinhVien = SinhVien::where('ma_tai_khoan', $user->id)->first();
        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin sinh viên'], 404);
        }

        $query = DonXinPhep::where('ma_sinh_vien', $sinhVien->id)
            ->with(['lopHoc.monHoc', 'lichHoc', 'nguoiDuyet']);

        // Filter by status if specified
        if ($request->has('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        // Filter by class if specified
        if ($request->has('ma_lop_hoc')) {
            $query->where('ma_lop_hoc', $request->ma_lop_hoc);
        }

        $dons = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $dons], 200);
    }

    /**
     * Sinh viên xem chi tiết đơn
     */
    public function show($id)
    {
        $user = Auth::user();
        if (!$user->laSinhVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $sinhVien = SinhVien::where('ma_tai_khoan', $user->id)->first();
        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin sinh viên'], 404);
        }

        $don = DonXinPhep::where('id', $id)
            ->where('ma_sinh_vien', $sinhVien->id)
            ->with(['lopHoc.monHoc', 'lichHoc', 'nguoiDuyet'])
            ->first();

        if (!$don) {
            return response()->json(['message' => 'Không tìm thấy đơn xin nghỉ'], 404);
        }

        return response()->json(['data' => $don], 200);
    }

    /**
     * Sinh viên hủy đơn xin nghỉ (chỉ khi chưa được duyệt)
     */
    public function destroy($id)
    {
        $user = Auth::user();
        if (!$user->laSinhVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $sinhVien = SinhVien::where('ma_tai_khoan', $user->id)->first();
        if (!$sinhVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin sinh viên'], 404);
        }

        $don = DonXinPhep::where('id', $id)
            ->where('ma_sinh_vien', $sinhVien->id)
            ->first();

        if (!$don) {
            return response()->json(['message' => 'Không tìm thấy đơn xin nghỉ'], 404);
        }

        if ($don->trang_thai !== 'cho_duyet') {
            return response()->json(['message' => 'Chỉ có thể hủy đơn đang chờ duyệt'], 400);
        }

        $don->delete();

        return response()->json(['message' => 'Đã hủy đơn xin nghỉ'], 200);
    }

    /**
     * Giảng viên xem danh sách đơn xin nghỉ của lớp học
     */
    public function danhSachTheoLop(Request $request)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $validated = $request->validate([
            'ma_lop_hoc' => 'required|exists:lop_hoc,id',
        ]);

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        // Kiểm tra giảng viên có phụ trách lớp học này không
        $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
            ->where('ma_lop_hoc', $validated['ma_lop_hoc'])
            ->first();

        if (!$phanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này'], 403);
        }

        $query = DonXinPhep::where('ma_lop_hoc', $validated['ma_lop_hoc'])
            ->with(['sinhVien', 'lichHoc', 'nguoiDuyet']);

        // Filter by status if specified
        if ($request->has('trang_thai')) {
            $query->where('trang_thai', $request->trang_thai);
        }

        $dons = $query->orderBy('created_at', 'desc')->get();

        return response()->json(['data' => $dons], 200);
    }

    /**
     * Giảng viên duyệt đơn xin nghỉ
     */
    public function duyet(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        $don = DonXinPhep::with(['lopHoc'])->find($id);
        if (!$don) {
            return response()->json(['message' => 'Không tìm thấy đơn xin nghỉ'], 404);
        }

        if ($don->trang_thai !== 'cho_duyet') {
            return response()->json(['message' => 'Đơn này không ở trạng thái chờ duyệt'], 400);
        }

        // Kiểm tra giảng viên có phụ trách lớp học này không
        $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
            ->where('ma_lop_hoc', $don->ma_lop_hoc)
            ->first();

        if (!$phanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này'], 403);
        }

        DB::beginTransaction();
        try {
            $don->update([
                'trang_thai' => 'da_duyet',
                'nguoi_duyet' => $user->id,
                'thoi_gian_duyet' => now(),
            ]);

            DB::commit();
            return response()->json(['message' => 'Đã duyệt đơn xin nghỉ', 'data' => $don->load(['sinhVien', 'lopHoc', 'lichHoc', 'nguoiDuyet'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Giảng viên từ chối đơn xin nghỉ
     */
    public function tuChoi(Request $request, $id)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $validated = $request->validate([
            'ly_do_tu_choi' => 'nullable|string|max:500',
        ]);

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        $don = DonXinPhep::with(['lopHoc'])->find($id);
        if (!$don) {
            return response()->json(['message' => 'Không tìm thấy đơn xin nghỉ'], 404);
        }

        if ($don->trang_thai !== 'cho_duyet') {
            return response()->json(['message' => 'Đơn này không ở trạng thái chờ duyệt'], 400);
        }

        // Kiểm tra giảng viên có phụ trách lớp học này không
        $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
            ->where('ma_lop_hoc', $don->ma_lop_hoc)
            ->first();

        if (!$phanCong) {
            return response()->json(['message' => 'Bạn không phụ trách lớp học này'], 403);
        }

        DB::beginTransaction();
        try {
            $don->update([
                'trang_thai' => 'tu_choi',
                'nguoi_duyet' => $user->id,
                'thoi_gian_duyet' => now(),
            ]);

            // Lưu lý do từ chối vào ly_do (có thể thêm trường riêng nếu cần)
            if (isset($validated['ly_do_tu_choi'])) {
                $don->ly_do = $don->ly_do . ' [Lý do từ chối: ' . $validated['ly_do_tu_choi'] . ']';
                $don->save();
            }

            DB::commit();
            return response()->json(['message' => 'Đã từ chối đơn xin nghỉ', 'data' => $don->load(['sinhVien', 'lopHoc', 'lichHoc', 'nguoiDuyet'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Giảng viên duyệt/từ chối nhiều đơn cùng lúc
     */
    public function xuLyHangLoat(Request $request)
    {
        $user = Auth::user();
        if (!$user->laGiangVien()) {
            return response()->json(['message' => 'Bạn không có quyền thực hiện hành động này'], 403);
        }

        $validated = $request->validate([
            'ma_don' => 'required|array',
            'ma_don.*' => 'required|exists:don_xin_phep,id',
            'hanh_dong' => 'required|in:duyet,tu_choi',
            'ly_do_tu_choi' => 'nullable|string|max:500',
        ]);

        $giangVien = GiangVien::where('ma_tai_khoan', $user->id)->first();
        if (!$giangVien) {
            return response()->json(['message' => 'Không tìm thấy thông tin giảng viên'], 404);
        }

        DB::beginTransaction();
        try {
            $results = [];
            foreach ($validated['ma_don'] as $donId) {
                $don = DonXinPhep::find($donId);
                if (!$don) {
                    $results[] = [
                        'ma_don' => $donId,
                        'status' => 'error',
                        'message' => 'Không tìm thấy đơn'
                    ];
                    continue;
                }

                if ($don->trang_thai !== 'cho_duyet') {
                    $results[] = [
                        'ma_don' => $donId,
                        'status' => 'error',
                        'message' => 'Đơn không ở trạng thái chờ duyệt'
                    ];
                    continue;
                }

                // Kiểm tra quyền giảng viên
                $phanCong = PhanCongGiangDay::where('ma_giang_vien', $giangVien->id)
                    ->where('ma_lop_hoc', $don->ma_lop_hoc)
                    ->first();

                if (!$phanCong) {
                    $results[] = [
                        'ma_don' => $donId,
                        'status' => 'error',
                        'message' => 'Không phụ trách lớp học này'
                    ];
                    continue;
                }

                $trangThaiMoi = $validated['hanh_dong'] === 'duyet' ? 'da_duyet' : 'tu_choi';
                $don->update([
                    'trang_thai' => $trangThaiMoi,
                    'nguoi_duyet' => $user->id,
                    'thoi_gian_duyet' => now(),
                ]);

                if ($validated['hanh_dong'] === 'tu_choi' && isset($validated['ly_do_tu_choi'])) {
                    $don->ly_do = $don->ly_do . ' [Lý do từ chối: ' . $validated['ly_do_tu_choi'] . ']';
                    $don->save();
                }

                $results[] = [
                    'ma_don' => $donId,
                    'status' => 'success',
                    'data' => $don
                ];
            }

            DB::commit();
            return response()->json(['message' => 'Đã xử lý hàng loạt', 'data' => $results], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Có lỗi xảy ra: ' . $e->getMessage()], 500);
        }
    }
}
