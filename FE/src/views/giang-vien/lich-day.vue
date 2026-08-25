<template>
  <div class="space-y-6">
    <div class="flex flex-wrap items-start justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">
          Xin chào, <span class="text-teal-600">{{ auth.hoTen || 'Giảng viên' }}</span>
        </h1>
        <p class="mt-1 text-sm text-slate-500">Theo dõi lịch dạy và mở phiên điểm danh QR.</p>
      </div>
      <router-link :to="{ name: 'gv-quan-ly-diem-danh' }" class="nut-than-cong">
        <i class="fa-solid fa-qrcode"></i>Quản lý điểm danh
      </router-link>
    </div>

    <div v-if="loi" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
      <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ loi }}
      <button class="ml-2 font-semibold underline" @click="taiDuLieu">Thử lại</button>
    </div>

    <div v-if="dangTai" class="the p-14 text-center text-slate-400">
      <i class="fa-solid fa-spinner fa-spin text-2xl"></i>
      <p class="mt-2 text-sm">Đang tải lịch giảng dạy...</p>
    </div>

    <template v-else>
      <div v-if="buoiKeTiep" class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-teal-600 to-emerald-600 p-6 text-white shadow-lg">
        <div class="relative">
          <span class="mb-3 inline-flex rounded-full border border-white/30 bg-white/20 px-2.5 py-1 text-xs font-semibold">
            Buổi học tiếp theo
          </span>
          <h2 class="text-xl font-bold">{{ buoiKeTiep.mon_hoc }} – {{ buoiKeTiep.ten_lop }}</h2>
          <div class="mt-2 flex flex-wrap gap-x-5 gap-y-1 text-sm text-teal-50">
            <span><i class="fa-regular fa-calendar mr-1.5"></i>{{ dinhDangNgay(buoiKeTiep.ngay_hoc) }}</span>
            <span><i class="fa-regular fa-clock mr-1.5"></i>{{ buoiKeTiep.gio_bat_dau }}–{{ buoiKeTiep.gio_ket_thuc }}</span>
            <span><i class="fa-solid fa-location-dot mr-1.5"></i>{{ buoiKeTiep.phong_hoc || 'Chưa cập nhật phòng' }}</span>
          </div>
          <router-link :to="{ name: 'gv-quan-ly-diem-danh' }" class="mt-5 inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-bold text-teal-700">
            <i class="fa-solid fa-qrcode"></i>Tạo QR điểm danh
          </router-link>
        </div>
      </div>

      <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <div v-for="item in thongKe" :key="item.nhan" class="the flex items-center gap-3 p-5">
          <span class="flex h-11 w-11 items-center justify-center rounded-xl" :class="item.mau">
            <i :class="item.icon"></i>
          </span>
          <div>
            <p class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ item.nhan }}</p>
            <p class="text-2xl font-bold text-slate-900">{{ item.giaTri }}</p>
          </div>
        </div>
      </div>

      <div class="the overflow-hidden">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
          <h2 class="text-sm font-semibold text-slate-900">Lớp học phần đang phụ trách</h2>
          <span class="text-xs font-medium text-teal-600">{{ lops.length }} lớp</span>
        </div>
        <div v-if="!lops.length" class="p-12 text-center text-sm text-slate-400">
          Chưa có lớp học phần được phân công.
        </div>
        <div v-else class="divide-y divide-slate-100">
          <div v-for="lop in lops" :key="lop.id" class="flex items-center gap-4 px-5 py-4">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-600">
              <i class="fa-solid fa-chalkboard"></i>
            </span>
            <div class="min-w-0 flex-1">
              <p class="truncate text-sm font-semibold text-slate-800">{{ lop.mon_hoc }} · {{ lop.ten_lop }}</p>
              <p class="mt-1 text-xs text-slate-500">{{ lop.ma_lop_hoc }} · {{ lop.so_sinh_vien || 0 }} sinh viên</p>
            </div>
          </div>
        </div>
      </div>

      <div class="the overflow-hidden">
        <div class="border-b border-slate-100 px-5 py-4">
          <h2 class="text-sm font-semibold text-slate-900">Các buổi dạy sắp tới</h2>
        </div>
        <div v-if="!danhSach.length" class="p-12 text-center text-sm text-slate-400">Chưa có lịch dạy.</div>
        <div v-else class="overflow-x-auto">
          <table class="bang">
            <thead><tr><th>Ngày</th><th>Môn học</th><th>Lớp</th><th>Giờ học</th><th>Phòng</th></tr></thead>
            <tbody>
              <tr v-for="buoi in danhSach" :key="buoi.id">
                <td>{{ dinhDangNgay(buoi.ngay_hoc) }}</td>
                <td class="font-semibold">{{ buoi.mon_hoc }}</td>
                <td>{{ buoi.ten_lop }}</td>
                <td>{{ buoi.gio_bat_dau }}–{{ buoi.gio_ket_thuc }}</td>
                <td>{{ buoi.phong_hoc || '—' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </template>
  </div>
</template>

<script>
import api from '../../utils/axios'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'giang-vien-lich-day',
  data() {
    return {
      danhSach: [],
      lops: [],
      dangTai: true,
      loi: '',
    }
  },
  computed: {
    auth() { return useAuthStore() },
    buoiKeTiep() {
      return this.danhSach.find((buoi) => !['da_hoc', 'da_huy'].includes(buoi.trang_thai)) || null
    },
    thongKe() {
      return [
        { nhan: 'Lớp đang dạy', giaTri: this.lops.length, icon: 'fa-solid fa-chalkboard', mau: 'bg-teal-50 text-teal-600' },
        { nhan: 'Tổng sinh viên', giaTri: this.lops.reduce((tong, lop) => tong + Number(lop.so_sinh_vien || 0), 0), icon: 'fa-solid fa-users', mau: 'bg-brand-50 text-brand-600' },
        { nhan: 'Buổi sắp tới', giaTri: this.danhSach.filter((buoi) => !['da_hoc', 'da_huy'].includes(buoi.trang_thai)).length, icon: 'fa-solid fa-calendar-check', mau: 'bg-amber-50 text-amber-600' },
      ]
    },
  },
  created() {
    this.taiDuLieu()
  },
  methods: {
    async taiDuLieu() {
      this.dangTai = true
      this.loi = ''
      try {
        const [buoi, lop] = await Promise.all([
          api.get('/lop-day/buoi-hoc'),
          api.get('/lop-day'),
        ])
        this.danhSach = buoi.data.danh_sach || []
        this.lops = lop.data.danh_sach || []
      } catch (error) {
        this.loi = error.response?.data?.message || 'Không tải được lịch giảng dạy.'
      } finally {
        this.dangTai = false
      }
    },
    dinhDangNgay(ngay) {
      if (!ngay) return '—'
      return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN')
    },
  },
}
</script>

