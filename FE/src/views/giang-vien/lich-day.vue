<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap"><div><h1 class="text-2xl font-bold text-slate-900">Xin chào, <span class="text-teal-600">{{ auth.hoTen || 'Giảng viên' }}</span></h1><p class="text-slate-500 text-sm mt-0.5">Học kỳ II – Năm học 2025–2026 <span v-if="thongTinGiangVien?.bo_mon">&nbsp;·&nbsp; {{ thongTinGiangVien.bo_mon }}</span></p></div><div class="text-right hidden sm:block"><p class="text-sm text-slate-500">{{ homNay }}</p><p class="text-xl font-bold text-slate-800">{{ gioHienTai }}</p></div></div>

    <div v-if="buoiKeTiep" class="bg-gradient-to-r from-teal-600 via-teal-600 to-emerald-600 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden"><div class="absolute right-6 top-6 w-20 h-20 bg-white/10 rounded-2xl rotate-12"></div><div class="absolute right-16 bottom-6 w-14 h-14 bg-white/10 rounded-xl -rotate-6"></div><div class="relative"><span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-white/20 border border-white/30 mb-3"><span class="w-1.5 h-1.5 bg-emerald-200 rounded-full animate-pulse"></span>{{ buoiKeTiep.phong?.trang_thai === 'dang_dien_ra' ? 'Đang giảng dạy' : 'Buổi học tiếp theo hôm nay' }}</span><h2 class="text-xl font-bold mb-1">{{ buoiKeTiep.mon_hoc }} – {{ buoiKeTiep.ten_lop }}</h2><div class="flex flex-wrap gap-x-5 gap-y-1 text-teal-100 text-sm mb-5"><span><i class="fa-regular fa-clock mr-1.5"></i>{{ buoiKeTiep.gio_bat_dau }}–{{ buoiKeTiep.gio_ket_thuc }}</span><span><i class="fa-solid fa-users mr-1.5"></i>{{ lopTheoTen(buoiKeTiep.ten_lop)?.so_sinh_vien || 0 }} sinh viên</span><span><i class="fa-solid fa-location-dot mr-1.5"></i>{{ buoiKeTiep.co_hoc_truc_tuyen ? 'Trực tuyến' : 'Trực tiếp' }}</span></div><div class="flex gap-3 flex-wrap"><button v-if="buoiKeTiep.co_hoc_truc_tuyen && !buoiKeTiep.phong" class="px-5 py-2.5 bg-white text-teal-700 text-sm font-bold rounded-xl hover:bg-teal-50" :disabled="dangMo === buoiKeTiep.id" @click="batDauDay(buoiKeTiep)"><i class="fa-solid fa-video mr-2"></i>Bắt đầu buổi học</button><button v-if="buoiKeTiep.phong?.trang_thai === 'dang_dien_ra'" class="px-5 py-2.5 bg-white text-teal-700 text-sm font-bold rounded-xl" @click="vaoPhong(buoiKeTiep.phong.ma_phong)"><i class="fa-solid fa-video mr-2"></i>Vào phòng học</button><button class="px-4 py-2.5 bg-white/15 text-white text-sm font-medium rounded-xl hover:bg-white/25 border border-white/25" @click="moPhongChoQr"><i class="fa-solid fa-qrcode mr-2"></i>Tạo QR điểm danh</button><router-link :to="{ name: 'gv-quan-ly-diem' }" class="px-4 py-2.5 bg-white/15 text-white text-sm font-medium rounded-xl hover:bg-white/25 border border-white/25"><i class="fa-solid fa-pen-to-square mr-2"></i>Nhập điểm</router-link></div></div></div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4"><div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-3"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium uppercase tracking-wide">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900">{{ th.giaTri }}</p><p class="text-xs text-slate-500">{{ th.phu }}</p></div></div></div>

    <div class="grid lg:grid-cols-3 gap-5">
      <div class="lg:col-span-2 the overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-semibold text-slate-900 text-sm">Lớp học phần đang dạy</h3><span class="text-xs text-teal-600 font-medium">{{ lops.length }} lớp</span></div>
        <div class="divide-y divide-slate-50">
          <div v-if="!lops.length" class="p-10 text-center text-sm text-slate-400">Chưa có lớp học phần được phân công.</div>
          <div v-for="lop in lops" :key="lop.id" class="px-5 py-4 flex items-center gap-4 hover:bg-slate-50/60">
            <div class="w-10 h-10 bg-teal-50 rounded-xl flex items-center justify-center shrink-0"><i class="fa-solid fa-chalkboard text-teal-600 text-sm"></i></div>
            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-slate-800 truncate">{{ lop.mon_hoc }} <span class="text-slate-400 font-normal">({{ lop.ma_lop_hoc }})</span></p><div class="flex items-center gap-3 mt-1"><span class="text-xs text-slate-500"><i class="fa-solid fa-users mr-1 text-slate-300"></i>{{ lop.so_sinh_vien }} SV</span><span class="text-xs text-slate-500"><i class="fa-solid fa-calendar-check mr-1 text-slate-300"></i>{{ soBuoiDaDay(lop) }}/{{ lop.so_buoi_hoc }} buổi</span><span class="nhan bg-emerald-50 text-emerald-700 border border-emerald-200">Đang dạy</span></div><div class="mt-2 h-1 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-teal-500 rounded-full" :style="{ width: tienDoLop(lop) + '%' }"></div></div></div>
            <div class="shrink-0 text-right"><p class="text-xs text-slate-400">Buổi tiếp</p><p class="text-xs font-semibold text-slate-700">{{ buoiTiepTheo(lop)?.ngay_hoc ? dinhDangNgayNgan(buoiTiepTheo(lop).ngay_hoc) : '—' }}</p><button v-if="buoiTiepTheo(lop)?.phong?.trang_thai === 'dang_dien_ra'" class="mt-1.5 text-xs px-2.5 py-1 bg-teal-50 text-teal-700 rounded-lg border border-teal-200" @click="vaoPhong(buoiTiepTheo(lop).phong.ma_phong)">Vào lớp</button></div>
          </div>
        </div>
      </div>

      <div class="the overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-semibold text-slate-900 text-sm">Đơn xin phép chờ duyệt</h3><span class="text-xs bg-amber-100 text-amber-700 px-2 py-0.5 rounded-full font-medium">{{ donChoDuyet.length }} đơn</span></div>
        <div class="divide-y divide-slate-50">
          <div v-if="!donChoDuyet.length" class="p-8 text-center text-sm text-slate-400">Không có đơn chờ duyệt.</div>
          <div v-for="don in donChoDuyet.slice(0, 4)" :key="don.id" class="px-4 py-3 flex items-center gap-3 hover:bg-slate-50/60"><div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-semibold shrink-0">{{ chuCai(don.sinh_vien) }}</div><div class="flex-1 min-w-0"><p class="text-xs font-semibold text-slate-800">{{ don.sinh_vien }} <span class="text-slate-400 font-normal">({{ don.lop_hoc }})</span></p><p class="text-xs text-slate-500 truncate">{{ don.ngay_nghi }} – {{ don.ly_do }}</p></div><div class="flex items-center gap-1 shrink-0"><button class="w-7 h-7 rounded-lg bg-emerald-50 text-emerald-600 border border-emerald-200" @click="duyetDon(don, 'duoc_duyet')"><i class="fa-solid fa-check text-xs"></i></button><button class="w-7 h-7 rounded-lg bg-rose-50 text-rose-600 border border-rose-200" @click="duyetDon(don, 'tu_choi')"><i class="fa-solid fa-xmark text-xs"></i></button></div></div>
        </div>
      </div>
    </div>

    <div class="the p-5"><h3 class="font-semibold text-slate-900 text-sm mb-4">Buổi dạy trong tuần này</h3><div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3"><div v-for="ngay in cacNgayTrongTuan" :key="ngay.iso" class="rounded-xl border p-3" :class="ngay.laHomNay ? 'border-teal-300 bg-teal-50/40' : 'border-slate-100 bg-slate-50/50'"><div class="text-center mb-2" :class="ngay.laHomNay ? 'text-teal-700' : 'text-slate-600'"><p class="text-xs font-bold">{{ ngay.thu }}</p><p class="text-lg font-bold" :class="ngay.laHomNay ? 'text-teal-600' : 'text-slate-800'">{{ ngay.ngay }}/{{ ngay.thang }}</p></div><div class="space-y-1.5"><p v-if="!ngay.buois.length" class="text-center text-slate-300 text-xs py-2">Trống</p><div v-for="b in ngay.buois" :key="b.id" class="rounded-lg px-2 py-1.5 bg-teal-50 border border-teal-100"><p class="text-xs font-semibold text-slate-700 truncate">{{ b.ten_lop }}</p><p class="text-xs text-slate-500">{{ b.gio_bat_dau }}–{{ b.gio_ket_thuc }}</p></div></div></div></div></div>
  </div>
</template>

<script>
import api from '../../utils/axios'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'giang-vien-lich-day',
  data() {
    return { danhSach: [], lops: [], donChoDuyet: [], dangMo: null }
  },
  computed: {
    auth() { return useAuthStore() },
    thongTinGiangVien() { return this.auth.user?.giang_vien || null },
    buoiKeTiep() { return this.danhSach.find((b) => b.trang_thai !== 'da_hoc' && b.trang_thai !== 'da_huy') || this.danhSach[0] || null },
    homNay() { return new Date().toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }) },
    gioHienTai() { return new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) },
    thongKe() { return [
      { icon: 'fa-solid fa-chalkboard', nen: 'bg-teal-50 text-teal-600', nhan: 'Lớp đang dạy', giaTri: this.lops.length, phu: 'HK II/2025–2026' },
      { icon: 'fa-solid fa-users', nen: 'bg-brand-50 text-brand-600', nhan: 'Tổng sinh viên', giaTri: this.lops.reduce((s, l) => s + Number(l.so_sinh_vien || 0), 0), phu: `${this.lops.length} lớp học phần` },
      { icon: 'fa-solid fa-calendar-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Buổi đã dạy', giaTri: `${this.danhSach.filter((b) => b.trang_thai === 'da_hoc').length}/${this.danhSach.length}`, phu: 'Tiến độ giảng dạy' },
      { icon: 'fa-solid fa-percent', nen: 'bg-amber-50 text-amber-600', nhan: 'Đơn chờ duyệt', giaTri: this.donChoDuyet.length, phu: 'Cần xử lý' },
    ] },
    cacNgayTrongTuan() { const homNay = new Date(); const thu2 = new Date(homNay); thu2.setDate(homNay.getDate() - ((homNay.getDay() + 6) % 7)); return Array.from({ length: 6 }, (_, i) => { const d = new Date(thu2); d.setDate(thu2.getDate() + i); const iso = d.toISOString().slice(0, 10); return { iso, thu: `T${i + 2}`, ngay: String(d.getDate()).padStart(2, '0'), thang: String(d.getMonth() + 1).padStart(2, '0'), laHomNay: iso === homNay.toISOString().slice(0, 10), buois: this.danhSach.filter((b) => b.ngay_hoc === iso) } }) },
  },
  async created() {
    await this.tai()
  },
  methods: {
    async tai() {
      const [buoi, lop, don] = await Promise.all([api.get('/lop-day/buoi-hoc'), api.get('/lop-day'), api.get('/xin-phep')])
      this.danhSach = buoi.data.danh_sach || []
      this.lops = lop.data.danh_sach || []
      this.donChoDuyet = (don.data.danh_sach || []).filter((d) => d.trang_thai === 'cho_duyet')
    },
    async batDauDay(buoi) {
      this.dangMo = buoi.id
      try {
        const { data } = await api.post('/phong/bat-dau', { ma_lich_hoc: buoi.id })
        this.vaoPhong(data.phong.ma_phong)
      } catch (e) {
        alert(e.response?.data?.message || 'Không mở được phòng.')
      } finally {
        this.dangMo = null
      }
    },
    moPhongChoQr() { if (this.buoiKeTiep?.phong?.ma_phong) this.vaoPhong(this.buoiKeTiep.phong.ma_phong); else if (this.buoiKeTiep?.co_hoc_truc_tuyen) this.batDauDay(this.buoiKeTiep) },
    vaoPhong(maPhong) {
      this.$router.push({ name: 'phong-hoc', params: { maPhong } })
    },
    lopTheoTen(ten) { return this.lops.find((l) => l.ten_lop === ten) },
    soBuoiDaDay(lop) { return this.danhSach.filter((b) => b.ten_lop === lop.ten_lop && b.trang_thai === 'da_hoc').length },
    tienDoLop(lop) { return lop.so_buoi_hoc ? Math.min(100, Math.round(this.soBuoiDaDay(lop) / lop.so_buoi_hoc * 100)) : 0 },
    buoiTiepTheo(lop) { return this.danhSach.find((b) => b.ten_lop === lop.ten_lop && !['da_hoc', 'da_huy'].includes(b.trang_thai)) },
    dinhDangNgayNgan(n) { return new Date(n).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }) },
    chuCai(ten) { return (ten || '?').trim().split(/\s+/).slice(-2).map((x) => x[0]).join('').toUpperCase() },
    async duyetDon(don, trangThai) { await api.post(`/xin-phep/${don.id}/duyet`, { trang_thai: trangThai }); await this.tai() },
    dinhDangNgay(n) {
      return new Date(n).toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit', year: 'numeric' })
    },
    tenTrangThai(t, phong) {
      if (phong?.trang_thai === 'dang_dien_ra') return 'Đang dạy'
      return { ke_hoach: 'Kế hoạch', dang_dien_ra: 'Đang diễn ra', da_hoc: 'Đã học', da_huy: 'Đã hủy' }[t] || t
    },
    mauTrangThai(t, phong) {
      if (phong?.trang_thai === 'dang_dien_ra') return 'bg-rose-100 text-rose-700'
      return {
        ke_hoach: 'bg-slate-100 text-slate-600',
        dang_dien_ra: 'bg-amber-100 text-amber-700',
        da_hoc: 'bg-emerald-100 text-emerald-700',
        da_huy: 'bg-slate-200 text-slate-600',
      }[t] || 'bg-slate-100 text-slate-600'
    },
  },
}
</script>
