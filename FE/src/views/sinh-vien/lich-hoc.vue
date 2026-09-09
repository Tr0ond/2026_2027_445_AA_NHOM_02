<template>
  <div>
    <div class="mb-5">
      <div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5">
        <router-link :to="{ name: 'sinh-vien-trang-chu' }" class="hover:text-brand-600">Tổng quan</router-link>
        <i class="fa-solid fa-chevron-right text-[9px]"></i>
        <span class="text-slate-600">Lịch học</span>
      </div>
      <h1 class="text-2xl font-bold text-slate-900">Lịch học</h1>
    </div>

    <div class="flex items-center justify-between mb-5 flex-wrap gap-3">
      <div class="flex items-center gap-2">
        <button class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50" @click="doiTuan(-1)"><i class="fa-solid fa-chevron-left text-xs"></i></button>
        <div class="text-center min-w-[210px]">
          <p class="text-sm font-semibold text-slate-700">Tuần {{ soTuan }} – {{ dinhDangNgay(tuanBatDau) }} đến {{ dinhDangNgay(tuanKetThuc) }}/{{ tuanKetThuc.getFullYear() }}</p>
          <button class="text-xs text-brand-600 font-medium" @click="veTuanHienTaiVaTai">{{ laTuanHienTai ? 'Tuần hiện tại' : 'Về tuần hiện tại' }}</button>
        </div>
        <button class="w-8 h-8 rounded-lg border border-slate-200 bg-white flex items-center justify-center text-slate-500 hover:bg-slate-50" @click="doiTuan(1)"><i class="fa-solid fa-chevron-right text-xs"></i></button>
      </div>
      <div class="flex items-center gap-3 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-emerald-500"></span>Trực tuyến</span>
        <span class="flex items-center gap-1.5"><span class="w-2 h-2 rounded-full bg-slate-300"></span>Trực tiếp</span>
      </div>
    </div>

    <div class="grid grid-cols-3 sm:grid-cols-6 gap-2 mb-5">
      <button v-for="ngay in cacNgayHienThi" :key="'pill-' + ngay.iso" class="rounded-xl p-2.5 text-center transition-all"
        :class="ngay.la_hom_nay ? 'bg-brand-600 text-white shadow-md' : ngay.buois.length ? 'bg-white border border-slate-200 text-slate-700' : 'bg-slate-50 border border-slate-100 text-slate-400'">
        <p class="text-xs font-bold" :class="ngay.la_hom_nay ? 'text-indigo-200' : ''">{{ ngay.thu }}</p>
        <p class="text-xl font-bold">{{ ngay.ngaySo }}</p>
        <p class="text-xs" :class="ngay.la_hom_nay ? 'text-indigo-200' : ngay.buois.length ? 'text-brand-600 font-semibold' : ''">{{ ngay.buois.length ? `${ngay.buois.length} môn` : '—' }}</p>
      </button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
      <div v-for="ngay in cacNgayHienThi" :key="ngay.iso" class="rounded-[14px] border overflow-hidden" :class="ngay.la_hom_nay ? 'border-brand-300 shadow-md' : 'border-slate-200'">
        <div class="px-3 py-2.5 text-center border-b" :class="ngay.la_hom_nay ? 'bg-brand-600 border-brand-500' : 'bg-white border-slate-100'">
          <p class="text-xs font-bold" :class="ngay.la_hom_nay ? 'text-indigo-200' : 'text-slate-600'">{{ tenThuDayDu(ngay.iso).toUpperCase() }}</p>
          <p class="text-xl font-bold" :class="ngay.la_hom_nay ? 'text-white' : 'text-slate-900'">{{ ngay.ngaySo }}</p>
          <p v-if="ngay.la_hom_nay" class="text-indigo-300 text-xs font-medium">Hôm nay</p>
        </div>
        <div class="p-2 space-y-1.5 min-h-[100px]" :class="ngay.la_hom_nay ? 'bg-indigo-50/40' : 'bg-white'">
          <div v-if="!ngay.buois.length" class="flex items-center justify-center h-14 text-slate-200"><i class="fa-regular fa-calendar text-2xl"></i></div>
          <button v-for="b in ngay.buois" :key="b.id" class="w-full text-left rounded-xl p-2.5 border hover:opacity-90 active:scale-95 transition-all"
            :class="[mauMon(b).bg, mauMon(b).border]" @click="chonBuoi(b)">
            <div class="flex items-center gap-1.5 mb-1"><span class="w-2 h-2 rounded-full shrink-0" :class="mauMon(b).dot"></span><i v-if="b.co_hoc_truc_tuyen" class="fa-solid fa-wifi text-emerald-500 text-xs ml-auto"></i></div>
            <p class="text-xs font-bold line-clamp-2 leading-tight" :class="mauMon(b).text">{{ b.mon_hoc }}</p>
            <p class="text-xs text-slate-500 mt-0.5">{{ b.gio_bat_dau }}–{{ b.gio_ket_thuc }}</p>
          </button>
        </div>
      </div>
    </div>

    <div v-if="buoiChon" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4" @click.self="buoiChon = null">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100"><h2 class="font-bold text-slate-900">Chi tiết buổi học</h2><button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="buoiChon = null"><i class="fa-solid fa-xmark"></i></button></div>
        <div class="p-5 space-y-4">
          <div class="rounded-2xl p-4 border" :class="[mauMon(buoiChon).bg, mauMon(buoiChon).border]">
            <p class="text-xs font-semibold uppercase tracking-wide mb-0.5" :class="mauMon(buoiChon).text">{{ tenThuDayDu(buoiChon.ngay_hoc) }} – {{ dinhDangNgayString(buoiChon.ngay_hoc) }}</p>
            <h3 class="text-xl font-bold text-slate-900">{{ buoiChon.mon_hoc }}</h3>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div v-for="info in thongTinBuoiChon" :key="info.nhan" class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-500 flex items-center gap-1.5 mb-1.5"><i :class="info.icon"></i>{{ info.nhan }}</p><p class="text-sm font-bold text-slate-800">{{ info.giaTri }}</p></div>
          </div>
          <div v-if="buoiChon.co_hoc_truc_tuyen" class="flex gap-3">
            <button v-if="buoiChon.phong_truc_tuyen?.trang_thai === 'dang_dien_ra'" class="flex-1 flex items-center justify-center gap-2 py-3 bg-brand-600 text-white text-sm font-bold rounded-xl hover:bg-brand-700" @click="vaoPhong(buoiChon)"><i class="fa-solid fa-video"></i>Vào phòng học</button>
            <div v-else class="flex-1 rounded-xl bg-brand-50 px-4 py-3 text-center text-sm text-brand-700"><i class="fa-solid fa-circle-info mr-2"></i>Phòng học trực tuyến chưa được mở.</div>
          </div>
          <div v-else class="flex items-center gap-3 bg-slate-50 rounded-xl px-4 py-3 text-sm text-slate-600"><i class="fa-solid fa-location-dot text-slate-400"></i><span>Học tại phòng <strong>{{ buoiChon.phong_hoc || '—' }}</strong> – đến trực tiếp</span></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

const THU = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
const BANG_MAU = [
  { bg: 'bg-indigo-50', border: 'border-indigo-200', text: 'text-indigo-700', dot: 'bg-indigo-500' },
  { bg: 'bg-teal-50', border: 'border-teal-200', text: 'text-teal-700', dot: 'bg-teal-500' },
  { bg: 'bg-violet-50', border: 'border-violet-200', text: 'text-violet-700', dot: 'bg-violet-500' },
  { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700', dot: 'bg-amber-500' },
  { bg: 'bg-rose-50', border: 'border-rose-200', text: 'text-rose-700', dot: 'bg-rose-500' },
  { bg: 'bg-sky-50', border: 'border-sky-200', text: 'text-sky-700', dot: 'bg-sky-500' },
]
const GIO_BAT_DAU_MAC_DINH = 7
const GIO_KET_THUC_MAC_DINH = 19

export default {
  name: 'sinh-vien-lich-hoc',
  data() {
    return {
      tuanBatDau: null,
      danhSach: [],
      pxMoiGio: 64,
      buoiChon: null,
      bangMauMon: {}, // ma_mon_hoc -> class màu
    }
  },
  computed: {
    tuanKetThuc() {
      return this.themNgay(this.tuanBatDau, 6)
    },
    cacNgay() {
      const ketQua = []
      for (let i = 0; i < 7; i++) {
        const d = this.themNgay(this.tuanBatDau, i)
        const iso = this.isoDate(d)
        // Chủ nhật cuối tuần để trống như hình
        const laChuNhat = d.getDay() === 0
        ketQua.push({
          iso,
          thu: THU[d.getDay()],
          ngaySo: d.getDate(),
          la_hom_nay: iso === this.isoDate(new Date()),
          buois: laChuNhat ? [] : this.danhSach.filter((b) => b.ngay_hoc === iso),
        })
      }
      return ketQua
    },
    cacNgayHienThi() { return this.cacNgay.slice(0, 6) },
    laTuanHienTai() {
      const homNay = new Date()
      return this.isoDate(homNay) >= this.isoDate(this.tuanBatDau) && this.isoDate(homNay) <= this.isoDate(this.tuanKetThuc)
    },
    soTuan() {
      const d = new Date(Date.UTC(this.tuanBatDau.getFullYear(), this.tuanBatDau.getMonth(), this.tuanBatDau.getDate()))
      d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7))
      return Math.ceil((((d - new Date(Date.UTC(d.getUTCFullYear(), 0, 1))) / 86400000) + 1) / 7)
    },
    thongTinBuoiChon() {
      if (!this.buoiChon) return []
      return [
        { icon: 'fa-regular fa-clock', nhan: 'Thời gian', giaTri: `${this.buoiChon.gio_bat_dau}–${this.buoiChon.gio_ket_thuc}` },
        { icon: 'fa-solid fa-chalkboard-user', nhan: 'Giảng viên', giaTri: this.buoiChon.giang_vien || 'Chưa cập nhật' },
        { icon: 'fa-solid fa-location-dot', nhan: 'Phòng học', giaTri: this.buoiChon.phong_hoc || (this.buoiChon.co_hoc_truc_tuyen ? 'Trực tuyến' : '—') },
        { icon: 'fa-solid fa-wifi', nhan: 'Hình thức', giaTri: this.buoiChon.co_hoc_truc_tuyen ? 'Trực tuyến' : 'Trực tiếp' },
      ]
    },
    // Khung giờ hiển thị: phủ hết các buổi trong tuần (mặc định 7h–19h)
    cacGio() {
      let min = GIO_BAT_DAU_MAC_DINH
      let max = GIO_KET_THUC_MAC_DINH
      for (const b of this.danhSach) {
        min = Math.min(min, Number(b.gio_bat_dau.slice(0, 2)))
        max = Math.max(max, Number(b.gio_ket_thuc.slice(0, 2)) + (b.gio_ket_thuc.endsWith('30') ? 0.5 : 0))
      }
      const tu = Math.floor(min)
      const den = Math.ceil(max)
      const gio = []
      for (let h = tu; h < den; h++) gio.push(h)
      return gio
    },
    gioDau() {
      return this.cacGio[0] ?? GIO_BAT_DAU_MAC_DINH
    },
    danhSachMon() {
      const m = {}
      for (const b of this.danhSach) {
        if (b.ma_mon_hoc) m[b.ma_mon_hoc] = b.mon_hoc
      }
      return m
    },
  },
  async created() {
    this.veTuanHienTai()
    await this.taiLich()
  },
  methods: {
    isoDate(d) {
      return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
    },
    themNgay(d, n) {
      const x = new Date(d)
      x.setDate(x.getDate() + n)
      return x
    },
    veTuanHienTai() {
      const homNay = new Date()
      const thu2 = new Date(homNay)
      thu2.setDate(homNay.getDate() - ((homNay.getDay() + 6) % 7))
      this.tuanBatDau = thu2
    },
    veTuanHienTaiVaTai() { this.veTuanHienTai(); this.taiLich() },
    doiTuan(n) {
      this.tuanBatDau = this.themNgay(this.tuanBatDau, n * 7)
      this.taiLich()
    },
    async taiLich() {
      const { data } = await api.get('/lich-hoc', {
        params: { tu_ngay: this.isoDate(this.tuanBatDau), den_ngay: this.isoDate(this.tuanKetThuc) },
      })
      this.danhSach = data.danh_sach
      this.phanMauMon()
    },
    // Gán màu cố định cho từng môn học
    phanMauMon() {
      const cacMon = [...new Set(this.danhSach.map((b) => b.ma_mon_hoc).filter(Boolean))]
      this.bangMauMon = {}
      cacMon.forEach((ma, i) => {
        this.bangMauMon[ma] = BANG_MAU[i % BANG_MAU.length]
      })
    },
    mauMon(b) {
      return this.bangMauMon[b.ma_mon_hoc] || { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-700', dot: 'bg-slate-400' }
    },
    // Vị trí + chiều cao khối lịch theo giờ học
    kieuKhoi(b) {
      const [gb, pb] = b.gio_bat_dau.split(':').map(Number)
      const [gk, pk] = b.gio_ket_thuc.split(':').map(Number)
      const batDau = gb + pb / 60
      const ketThuc = gk + pk / 60
      const top = (batDau - this.gioDau) * this.pxMoiGio
      const cao = Math.max((ketThuc - batDau) * this.pxMoiGio - 4, 44)
      return { top: top + 2 + 'px', height: cao + 'px' }
    },
    chonBuoi(b) {
      this.buoiChon = b
    },
    dinhDangNgay(d) {
      return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' })
    },
    dinhDangNgayFull(n) {
      return new Date(n).toLocaleDateString('vi-VN', { weekday: 'long', day: '2-digit', month: '2-digit', year: 'numeric' })
    },
    dinhDangNgayString(n) { return new Date(n).toLocaleDateString('vi-VN') },
    tenThuDayDu(n) { return new Date(n).toLocaleDateString('vi-VN', { weekday: 'long' }) },
    vaoPhong(buoi) {
      this.buoiChon = null
      this.$router.push({ name: 'phong-hoc', params: { maPhong: buoi.phong_truc_tuyen.ma_phong } })
    },
  },
}
</script>
