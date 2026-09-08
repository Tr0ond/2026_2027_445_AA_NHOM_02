<template>
  <div class="space-y-6">
    <div class="flex items-start justify-between gap-4 flex-wrap">
      <div>
        <h1 class="text-2xl font-bold text-slate-900">Xin chào, <span class="text-brand-600">{{ auth.hoTen || 'Sinh viên' }}</span> 👋</h1>
        <p class="text-slate-500 text-sm mt-0.5">Học kỳ II – Năm học 2025–2026 <span v-if="thongTinSinhVien">&nbsp;·&nbsp; MSSV: {{ thongTinSinhVien.ma_sinh_vien }} &nbsp;·&nbsp; Lớp: {{ thongTinSinhVien.lop_danh_nghia || '—' }}</span></p>
      </div>
      <div class="text-right shrink-0 hidden sm:block"><p class="text-sm text-slate-500">{{ homNay }}</p><p class="text-xl font-bold text-slate-800">{{ gioHienTai }}</p></div>
    </div>

    <div v-if="buoiKeTiep" class="bg-gradient-to-r from-brand-600 via-brand-600 to-violet-700 rounded-2xl p-6 text-white shadow-lg relative overflow-hidden">
      <div class="absolute -right-8 -top-8 w-40 h-40 bg-white/5 rounded-3xl rotate-12"></div><div class="absolute right-16 bottom-0 w-24 h-24 bg-white/5 rounded-2xl -rotate-6"></div>
      <div class="relative z-10">
        <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-2.5 py-1 rounded-full bg-white/20 border border-white/30 mb-3"><span class="w-1.5 h-1.5 rounded-full" :class="buoiKeTiep.phong_truc_tuyen?.trang_thai === 'dang_dien_ra' ? 'bg-emerald-300 animate-pulse' : 'bg-indigo-200'"></span>{{ buoiKeTiep.phong_truc_tuyen?.trang_thai === 'dang_dien_ra' ? 'Đang diễn ra' : 'Buổi học tiếp theo' }}</span>
        <h2 class="text-xl font-bold mb-1">{{ buoiKeTiep.mon_hoc }}</h2>
        <div class="flex flex-wrap items-center gap-x-5 gap-y-1 text-indigo-200 text-sm mb-5">
          <span><i class="fa-regular fa-calendar mr-1.5"></i>{{ dinhDangNgay(buoiKeTiep.ngay_hoc) }}</span><span><i class="fa-regular fa-clock mr-1.5"></i>{{ buoiKeTiep.gio_bat_dau }} – {{ buoiKeTiep.gio_ket_thuc }}</span><span v-if="buoiKeTiep.giang_vien"><i class="fa-solid fa-chalkboard-user mr-1.5"></i>{{ buoiKeTiep.giang_vien }}</span>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
          <button v-if="buoiKeTiep.phong_truc_tuyen?.trang_thai === 'dang_dien_ra'" class="flex items-center gap-2 px-5 py-2.5 bg-white text-brand-700 text-sm font-bold rounded-xl hover:bg-brand-50 shadow-sm" @click="vaoPhong(buoiKeTiep.phong_truc_tuyen.ma_phong)"><i class="fa-solid fa-video"></i>Vào phòng học</button>
          <button class="flex items-center gap-2 px-4 py-2.5 bg-white/15 text-white text-sm font-medium rounded-xl hover:bg-white/25 border border-white/25"><i class="fa-solid fa-qrcode"></i>Điểm danh QR</button>
          <router-link :to="{ name: 'sinh-vien-lich-hoc' }" class="flex items-center gap-2 px-4 py-2.5 bg-white/15 text-white text-sm font-medium rounded-xl hover:bg-white/25 border border-white/25"><i class="fa-regular fa-calendar"></i>Xem lịch</router-link>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div v-for="th in the" :key="th.nhan" class="the p-5 flex items-start gap-4">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div>
        <div class="min-w-0"><p class="text-xs text-slate-500 font-medium uppercase tracking-wide truncate">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 leading-tight mt-0.5">{{ th.gia_tri }}</p><p class="text-xs text-slate-500 mt-0.5">{{ th.phu }}</p></div>
      </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-5">
      <div class="lg:col-span-2 the overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><div><h3 class="font-semibold text-slate-900 text-sm">Lịch học hôm nay</h3><p class="text-xs text-slate-500 mt-0.5">{{ homNay }}</p></div><router-link :to="{ name: 'sinh-vien-lich-hoc' }" class="text-xs text-brand-600 font-medium">Lịch tuần <i class="fa-solid fa-arrow-right ml-1"></i></router-link></div>
        <div class="divide-y divide-slate-50">
          <div v-if="!buoiHomNay.length" class="px-5 py-10 text-center"><i class="fa-regular fa-calendar-check text-slate-200 text-3xl"></i><p class="text-sm text-slate-400 mt-2">Không có lịch học hôm nay</p></div>
          <div v-for="b in buoiHomNay" :key="b.id" class="flex items-center gap-4 px-5 py-3.5 hover:bg-slate-50/60">
            <div class="w-1 h-12 rounded-full shrink-0" :class="b.co_hoc_truc_tuyen ? 'bg-emerald-500' : 'bg-slate-300'"></div>
            <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-slate-800 truncate">{{ b.mon_hoc }}</p><p class="text-xs text-slate-500 truncate">{{ b.giang_vien || b.ten_lop || 'Lịch học' }} · {{ b.phong_hoc || (b.co_hoc_truc_tuyen ? 'Trực tuyến' : 'Chưa cập nhật phòng') }}</p></div>
            <div class="text-right shrink-0"><p class="text-xs font-semibold text-slate-700">{{ b.gio_bat_dau }}–{{ b.gio_ket_thuc }}</p><span class="nhan !px-2 !py-0.5 border" :class="b.co_hoc_truc_tuyen ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">{{ b.co_hoc_truc_tuyen ? 'Trực tuyến' : 'Trực tiếp' }}</span></div>
          </div>
        </div>
      </div>

      <div class="space-y-4">
        <div class="the overflow-hidden">
          <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-semibold text-slate-900 text-sm">Thông báo & Việc cần làm</h3><span class="text-xs bg-rose-500 text-white rounded-full px-2 py-0.5 font-medium">3</span></div>
          <div class="divide-y divide-slate-50">
            <div v-for="(n, i) in thongBaoMau" :key="i" class="flex items-start gap-3 px-4 py-3 hover:bg-slate-50/60"><div class="w-8 h-8 rounded-xl flex items-center justify-center shrink-0" :class="n.mau"><i :class="n.icon" class="text-xs"></i></div><div class="min-w-0 flex-1"><p class="text-xs font-semibold text-slate-800">{{ n.tieuDe }}</p><p class="text-xs text-slate-500">{{ n.moTa }}</p></div></div>
          </div>
        </div>
        <div class="the p-4"><h3 class="font-semibold text-slate-900 text-sm mb-3">Truy cập nhanh</h3><div class="grid grid-cols-2 gap-2"><router-link v-for="l in lienKetNhanh" :key="l.route" :to="{ name: l.route }" class="flex flex-col items-center gap-1.5 py-3 px-2 rounded-xl text-xs font-medium text-slate-600 border border-slate-100 hover:bg-brand-50 hover:border-brand-200 hover:text-brand-700"><i :class="l.icon" class="text-base"></i>{{ l.ten }}</router-link></div></div>
      </div>
    </div>

    <div class="the overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><h3 class="font-semibold text-slate-900 text-sm">Điểm số gần đây</h3><router-link :to="{ name: 'diem-cua-toi' }" class="text-xs text-brand-600 font-medium">Xem tất cả <i class="fa-solid fa-arrow-right ml-1"></i></router-link></div>
      <div v-if="!diemGanDay.length" class="p-10 text-center text-sm text-slate-400">Chưa có điểm số được công bố.</div>
      <div class="grid sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-slate-100">
        <div v-for="d in diemGanDay" :key="d.id" class="px-5 py-4 flex items-center gap-4 hover:bg-slate-50/60">
          <div class="w-10 h-10 bg-brand-50 rounded-xl flex items-center justify-center shrink-0"><i class="fa-solid fa-graduation-cap text-brand-600 text-sm"></i></div>
          <div class="flex-1 min-w-0"><p class="text-xs font-semibold text-slate-800 truncate">{{ d.mon_hoc }}</p><p class="text-xs text-slate-500">{{ d.so_tin_chi || '—' }} tín chỉ</p></div>
          <div class="text-right shrink-0"><p class="text-lg font-bold" :class="mauDiem(d.diem_tong_ket)">{{ dinhDangDiem(d.diem_tong_ket) }}</p><span class="text-xs font-bold px-1.5 py-0.5 rounded-lg border" :class="mauXepLoai(d.xep_loai)">{{ d.xep_loai || '—' }}</span></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'sinh-vien-trang-chu',
  data() {
    return {
      lops: [], buoiSapToi: [], diemSo: [], lichSuDiemDanh: [],
      thongBaoMau: [
        { icon: 'fa-solid fa-triangle-exclamation', mau: 'text-amber-500 bg-amber-50', tieuDe: 'Kiểm tra lịch học tuần này', moTa: 'Lịch có thể được cập nhật bởi giảng viên' },
        { icon: 'fa-solid fa-calendar-days', mau: 'text-brand-500 bg-brand-50', tieuDe: 'Theo dõi điểm thành phần', moTa: 'Xem kết quả mới nhất trong mục Điểm số' },
        { icon: 'fa-solid fa-circle-check', mau: 'text-emerald-500 bg-emerald-50', tieuDe: 'Hoàn thiện thông tin cá nhân', moTa: 'Bảo đảm hồ sơ luôn chính xác' },
      ],
      lienKetNhanh: [
        { ten: 'Xem điểm', icon: 'fa-solid fa-chart-bar', route: 'diem-cua-toi' }, { ten: 'Điểm danh', icon: 'fa-solid fa-clipboard-check', route: 'lich-su-diem-danh' },
        { ten: 'Đăng ký lớp', icon: 'fa-solid fa-book-open', route: 'dang-ky-lop' }, { ten: 'Xin phép vắng', icon: 'fa-solid fa-file-pen', route: 'xin-phep-vang' },
      ],
    }
  },
  computed: {
    auth() { return useAuthStore() },
    thongTinSinhVien() { return this.auth.user?.sinh_vien || null },
    buoiKeTiep() { return this.buoiSapToi[0] || null },
    buoiHomNay() { const ngay = new Date().toISOString().slice(0, 10); return this.buoiSapToi.filter((b) => b.ngay_hoc === ngay) },
    diemGanDay() { return this.diemSo.filter((d) => d.diem_tong_ket !== null && d.diem_tong_ket !== undefined).slice(0, 3) },
    gpa() { const ds = this.diemGanDay.map((d) => Number(d.diem_tong_ket)).filter(Number.isFinite); return ds.length ? (ds.reduce((a, b) => a + b, 0) / ds.length).toFixed(2) : '—' },
    tyLeChuyenCan() { const tong = this.lichSuDiemDanh.length; if (!tong) return '—'; const coMat = this.lichSuDiemDanh.filter((d) => ['co_mat', 'di_muon'].includes(d.trang_thai_diem_danh)).length; return `${Math.round(coMat / tong * 100)}%` },
    homNay() { return new Date().toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' }) },
    gioHienTai() { return new Date().toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }) },
    the() { return [
      { icon: 'fa-solid fa-book-open', nen: 'bg-brand-50 text-brand-600', gia_tri: this.lops.length, nhan: 'Môn đang học', phu: 'Học kỳ hiện tại' },
      { icon: 'fa-solid fa-calendar-check', nen: 'bg-teal-50 text-teal-600', gia_tri: this.buoiHomNay.length, nhan: 'Buổi học hôm nay', phu: 'Xem lịch chi tiết' },
      { icon: 'fa-solid fa-percent', nen: 'bg-emerald-50 text-emerald-600', gia_tri: this.tyLeChuyenCan, nhan: 'Chuyên cần', phu: 'Học kỳ hiện tại' },
      { icon: 'fa-solid fa-star', nen: 'bg-amber-50 text-amber-600', gia_tri: this.gpa, nhan: 'GPA tích lũy', phu: 'Theo thang điểm 10' },
    ] },
  },
  async created() {
    const [resLop, resLich, resDiem, resDiemDanh] = await Promise.all([api.get('/sinh-vien/lop-cua-toi'), api.get('/lich-hoc'), api.get('/sinh-vien/diem'), api.get('/sinh-vien/lich-su-diem-danh')])
    this.lops = resLop.data.danh_sach || []
    this.buoiSapToi = (resLich.data.danh_sach || []).filter((b) => b.ngay_hoc >= new Date().toISOString().slice(0, 10)).slice(0, 5)
    this.diemSo = resDiem.data.danh_sach || []
    this.lichSuDiemDanh = resDiemDanh.data.danh_sach || []
  },
  methods: {
    dinhDangNgay(n) { return new Date(n).toLocaleDateString('vi-VN', { weekday: 'short', day: '2-digit', month: '2-digit' }) },
    dinhDangDiem(n) { const x = Number(n); return Number.isFinite(x) ? x.toFixed(1) : '—' },
    mauDiem(n) { const x = Number(n); return x >= 8.5 ? 'text-emerald-600' : x >= 7 ? 'text-blue-600' : 'text-amber-600' },
    mauXepLoai(x) { return String(x || '').startsWith('A') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-blue-50 text-blue-700 border-blue-200' },
    vaoPhong(maPhong) { this.$router.push({ name: 'phong-hoc', params: { maPhong } }) },
  },
}
</script>
