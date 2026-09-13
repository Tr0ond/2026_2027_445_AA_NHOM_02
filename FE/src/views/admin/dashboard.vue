<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4"><div><h1 class="text-2xl font-bold text-slate-900">Bảng điều khiển quản trị</h1><p class="text-slate-500 text-sm mt-0.5">Tổng quan hệ thống &nbsp;·&nbsp; Học kỳ II – Năm học 2025–2026 &nbsp;·&nbsp; {{ ngayHienTai }}</p></div><router-link :to="{ name: 'admin-bao-cao' }" class="hidden sm:flex items-center gap-2 px-3 py-2 bg-white border border-slate-200 text-slate-600 text-sm rounded-xl hover:bg-slate-50"><i class="fa-solid fa-download text-xs"></i>Xuất báo cáo</router-link></div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4"><div v-for="th in theTongQuan" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.gia_tri }}</p><p class="text-xs text-slate-500 mt-0.5">{{ th.phu }}</p></div></div></div>

    <div class="grid lg:grid-cols-3 gap-5">
      <div class="lg:col-span-2 the p-5"><div class="flex items-center justify-between mb-5"><div><h3 class="font-semibold text-slate-900 text-sm">Tỷ lệ chuyên cần theo lớp</h3><p class="text-xs text-slate-500 mt-0.5">Các lớp có dữ liệu điểm danh gần nhất</p></div><div class="flex items-center bg-slate-100 rounded-lg p-1"><button class="px-2.5 py-1 rounded-md text-xs font-medium bg-white text-slate-700 shadow-sm">Học kỳ</button><button class="px-2.5 py-1 rounded-md text-xs font-medium text-slate-500">Năm học</button></div></div><div class="h-52"><canvas ref="bieuDoChuyenCan"></canvas></div></div>
      <div class="the p-5"><h3 class="font-semibold text-slate-900 text-sm mb-4">Phân bố người dùng</h3><div class="flex items-center justify-center mb-5"><div class="relative w-28 h-28 rounded-full" :style="{ background: bieuDoPhanBo }"><div class="absolute inset-4 bg-white rounded-full flex flex-col items-center justify-center"><p class="text-xl font-bold text-slate-900">{{ tongNguoiDungRutGon }}</p><p class="text-xs text-slate-500">users</p></div></div></div><div class="space-y-2.5"><div v-for="p in phanBoNguoiDung" :key="p.nhan" class="flex items-center gap-2.5"><span class="w-2.5 h-2.5 rounded-full shrink-0" :class="p.mau"></span><span class="text-xs text-slate-600 flex-1">{{ p.nhan }}</span><span class="text-xs font-semibold text-slate-800">{{ p.soLuong }}</span><span class="text-xs text-slate-400 w-8 text-right">{{ p.tyLe }}%</span></div></div></div>
    </div>

    <div class="grid lg:grid-cols-2 gap-5">
      <div class="the overflow-hidden"><div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-semibold text-slate-900 text-sm">Lớp học phần nổi bật</h3><span class="flex items-center gap-1.5 text-xs text-emerald-600 font-medium bg-emerald-50 px-2 py-1 rounded-full border border-emerald-200"><span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>{{ lopNoiBat.length }} lớp</span></div><div class="divide-y divide-slate-50"><div v-for="lop in lopNoiBat" :key="lop.lop" class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/60"><div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center shrink-0"><i class="fa-solid fa-chalkboard text-emerald-600 text-sm"></i></div><div class="flex-1 min-w-0"><p class="text-sm font-semibold text-slate-800 truncate">{{ lop.lop }}</p><p class="text-xs text-slate-500">Điểm trung bình: {{ diemCuaLop(lop.lop) }}</p></div><div class="text-right shrink-0"><p class="text-xs font-semibold text-emerald-600">{{ lop.ty_le }}% chuyên cần</p><p class="text-xs text-slate-400">Học kỳ hiện tại</p></div></div><div v-if="!lopNoiBat.length" class="p-10 text-center text-sm text-slate-400">Chưa có dữ liệu lớp học.</div></div></div>
      <div class="the overflow-hidden"><div class="px-5 py-4 border-b border-slate-100"><h3 class="font-semibold text-slate-900 text-sm">Hoạt động gần đây</h3></div><div class="divide-y divide-slate-50"><div v-for="(a, i) in hoatDong" :key="i" class="flex items-start gap-3 px-5 py-3.5 hover:bg-slate-50/60"><div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :class="a.mau"><i :class="a.icon" class="text-xs"></i></div><div class="flex-1 min-w-0"><p class="text-xs font-semibold text-slate-800">{{ a.noiDung }}</p><p class="text-xs text-slate-500">{{ a.phu }}</p></div><span class="text-xs text-slate-400 shrink-0">{{ a.thoiGian }}</span></div></div></div>
    </div>
  </div>
</template>

<script>
import { Chart, registerables } from 'chart.js'
import api from '../../utils/axios'

Chart.register(...registerables)

export default {
  name: 'admin-dashboard',
  data() {
    return { tongQuan: null, chuyenCan: [], diemLop: [] }
  },
  computed: {
    ngayHienTai() { return new Date().toLocaleDateString('vi-VN') },
    theTongQuan() {
      const t = this.tongQuan
      return [
        { icon: 'fa-solid fa-users', nen: 'bg-indigo-50 text-indigo-600', gia_tri: t?.tai_khoan?.sinh_vien ?? '—', nhan: 'Tổng sinh viên', phu: 'Toàn hệ thống' },
        { icon: 'fa-solid fa-chalkboard-user', nen: 'bg-teal-50 text-teal-600', gia_tri: t?.tai_khoan?.giang_vien ?? '—', nhan: 'Giảng viên', phu: 'Đang hoạt động' },
        { icon: 'fa-solid fa-chalkboard', nen: 'bg-violet-50 text-violet-600', gia_tri: t?.lop_hoc ?? '—', nhan: 'Lớp học phần', phu: 'HK II/2025–2026' },
        { icon: 'fa-solid fa-percent', nen: 'bg-emerald-50 text-emerald-600', gia_tri: t ? `${t.ty_le_diem_danh_tb}%` : '—', nhan: 'Chuyên cần TB', phu: 'Học kỳ hiện tại' },
      ]
    },
    phanBoNguoiDung() { const t = this.tongQuan?.tai_khoan || {}; const tong = Number(t.tong || 0); const items = [ ['Sinh viên', Number(t.sinh_vien || 0), 'bg-indigo-500'], ['Giảng viên', Number(t.giang_vien || 0), 'bg-teal-500'], ['Quản trị', Number(t.admin || 0), 'bg-slate-400'] ]; return items.map(([nhan, soLuong, mau]) => ({ nhan, soLuong, mau, tyLe: tong ? Math.round(soLuong / tong * 100) : 0 })) },
    bieuDoPhanBo() { const sv = this.phanBoNguoiDung[0]?.tyLe || 0; const gv = this.phanBoNguoiDung[1]?.tyLe || 0; return `conic-gradient(#4f46e5 0 ${sv}%, #0d9488 ${sv}% ${sv + gv}%, #94a3b8 ${sv + gv}% 100%)` },
    tongNguoiDungRutGon() { const n = Number(this.tongQuan?.tai_khoan?.tong || 0); return n >= 1000 ? `${(n / 1000).toFixed(1)}K` : n },
    lopNoiBat() { return [...this.chuyenCan].sort((a, b) => Number(b.ty_le) - Number(a.ty_le)).slice(0, 5) },
    hoatDong() { return [
      { icon: 'fa-solid fa-user-plus', mau: 'bg-indigo-50 text-indigo-600', noiDung: 'Dữ liệu tài khoản đã được cập nhật', phu: `${this.tongQuan?.tai_khoan?.tong || 0} tài khoản trong hệ thống`, thoiGian: 'Mới nhất' },
      { icon: 'fa-solid fa-chalkboard', mau: 'bg-teal-50 text-teal-600', noiDung: 'Lớp học phần đang được theo dõi', phu: `${this.tongQuan?.lop_hoc || 0} lớp học`, thoiGian: 'Hôm nay' },
      { icon: 'fa-solid fa-file-pen', mau: 'bg-amber-50 text-amber-600', noiDung: 'Báo cáo chuyên cần sẵn sàng', phu: 'Xem chi tiết trong mục Báo cáo', thoiGian: 'Gần đây' },
      { icon: 'fa-solid fa-chart-line', mau: 'bg-violet-50 text-violet-600', noiDung: 'Thống kê điểm số đã đồng bộ', phu: `${this.diemLop.length} lớp có dữ liệu`, thoiGian: 'Tự động' },
    ] },
  },
  async mounted() {
    const [resTQ, resCC, resDiem] = await Promise.all([
      api.get('/admin/thong-ke/tong-quan'),
      api.get('/admin/thong-ke/chuyen-can-theo-lop'),
      api.get('/admin/thong-ke/diem-theo-lop'),
    ])
    this.tongQuan = resTQ.data
    this.chuyenCan = resCC.data.danh_sach
    this.diemLop = resDiem.data.danh_sach
    this.veBieuDo()
  },
  methods: {
    veBieuDo() {
      new Chart(this.$refs.bieuDoChuyenCan, {
        type: 'bar',
        data: {
          labels: this.chuyenCan.map((c) => c.lop),
          datasets: [{
            label: 'Tỷ lệ chuyên cần (%)',
            data: this.chuyenCan.map((c) => c.ty_le),
            backgroundColor: '#6366f1',
            borderRadius: 8,
          }],
        },
        options: { scales: { y: { beginAtZero: true, max: 100 } }, plugins: { legend: { display: false } } },
      })

    },
    diemCuaLop(lop) { const d = this.diemLop.find((x) => x.lop === lop)?.diem_tb; return d === null || d === undefined ? '—' : Number(d).toFixed(1) },
  },
}
</script>
