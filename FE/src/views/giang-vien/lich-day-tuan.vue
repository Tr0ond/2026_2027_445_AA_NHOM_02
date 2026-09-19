<template>
  <div class="space-y-6">
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-teal-900 to-teal-700 px-6 py-8 text-white shadow-xl sm:px-10">
      <div class="pointer-events-none absolute -right-10 -top-16 h-64 w-64 rounded-full border-[28px] border-cyan-300/10"></div>
      <div class="pointer-events-none absolute -bottom-20 right-24 h-48 w-48 rounded-full border-[20px] border-white/5"></div>
      <div class="relative z-10 flex flex-wrap items-end justify-between gap-6">
        <div class="min-w-0">
          <div class="mb-3 flex items-center gap-2 text-xs font-medium text-cyan-100/70">
            <router-link :to="{ name: 'giang-vien-trang-chu' }" class="transition hover:text-white">Tổng quan</router-link>
            <i class="fa-solid fa-chevron-right text-[9px]" aria-hidden="true"></i>
            <span class="text-cyan-100">Lịch dạy</span>
          </div>
          <p class="mb-2 text-xs font-bold uppercase tracking-[0.2em] text-cyan-300">Lịch trình giảng dạy</p>
          <h1 class="text-3xl font-extrabold tracking-tight sm:text-4xl">Tuần dạy của {{ auth.hoTen || 'Giảng viên' }}</h1>
          <p class="mt-2 max-w-2xl text-sm text-cyan-100/80 sm:text-base">Theo dõi các lớp được phân công, phòng học và trạng thái buổi dạy theo từng tuần.</p>
        </div>
        <div class="flex gap-3" aria-label="Thống kê lịch dạy trong tuần">
          <div class="min-w-[100px] rounded-2xl border border-white/20 bg-white/10 px-5 py-4 text-center backdrop-blur-sm">
            <p class="text-3xl font-black tabular-nums">{{ danhSachHoatDong.length }}</p>
            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-cyan-100">Buổi dạy</p>
          </div>
          <div class="min-w-[100px] rounded-2xl border border-white/20 bg-white/10 px-5 py-4 text-center backdrop-blur-sm">
            <p class="text-3xl font-black tabular-nums">{{ soNgayCoLich }}</p>
            <p class="mt-1 text-[11px] font-bold uppercase tracking-wide text-cyan-100">Ngày có lịch</p>
          </div>
        </div>
      </div>
    </section>

    <section class="flex flex-col items-center justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-2 shadow-sm sm:flex-row" aria-label="Điều hướng lịch theo tuần">
      <div class="flex w-full items-center justify-between gap-2 sm:w-auto sm:justify-start">
        <button type="button" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition-all hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500" aria-label="Xem tuần trước" @click="doiTuan(-1)">
          <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
        </button>
        <div class="min-w-0 flex-1 text-center sm:min-w-[240px]">
          <p class="text-base font-extrabold text-slate-800">Tuần {{ soTuan }}</p>
          <p class="mt-0.5 text-sm font-medium text-slate-500">{{ dinhDangNgay(tuanBatDau) }} – {{ dinhDangNgay(tuanKetThuc) }} / {{ tuanKetThuc.getFullYear() }}</p>
          <button type="button" class="mt-1.5 min-h-6 text-xs font-bold text-teal-700 transition hover:text-teal-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500" @click="veTuanHienTaiVaTai">
            {{ laTuanHienTai ? 'Đang ở tuần hiện tại' : 'Trở về tuần hiện tại' }}
          </button>
        </div>
        <button type="button" class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl border border-slate-200 bg-slate-50 text-slate-600 transition-all hover:border-teal-200 hover:bg-teal-50 hover:text-teal-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500" aria-label="Xem tuần sau" @click="doiTuan(1)">
          <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
        </button>
      </div>

      <div class="flex flex-wrap items-center justify-center gap-4 px-4 text-sm font-medium text-slate-600" aria-label="Chú thích lịch dạy">
        <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-emerald-500 shadow-sm"></span>Trực tuyến</span>
        <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-slate-300 shadow-sm"></span>Tại trường</span>
        <span class="flex items-center gap-2"><span class="h-3 w-3 rounded-full bg-rose-300 shadow-sm"></span>Đã hủy</span>
      </div>
    </section>

    <div v-if="loi" class="flex flex-col gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-700 sm:flex-row sm:items-center" role="alert">
      <div class="flex flex-1 items-start gap-3">
        <i class="fa-solid fa-circle-exclamation mt-0.5 text-base" aria-hidden="true"></i>
        <p>{{ loi }}</p>
      </div>
      <button type="button" class="min-h-11 rounded-xl border border-rose-200 bg-white px-4 font-bold text-rose-700 hover:bg-rose-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500" @click="taiLich()">Thử lại</button>
    </div>

    <div v-if="dangTai" class="rounded-3xl border border-slate-100 bg-white py-20 text-center text-slate-500 shadow-sm" role="status" aria-live="polite">
      <i class="fa-solid fa-circle-notch fa-spin text-4xl text-teal-600" aria-hidden="true"></i>
      <p class="mt-4 text-base font-medium">Đang đồng bộ lịch dạy...</p>
    </div>

    <section v-else class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm" aria-label="Lịch dạy trong tuần">
      <div class="custom-scrollbar overflow-x-auto">
        <div class="min-w-[900px]">
          <div class="grid grid-cols-7 divide-x divide-slate-100 border-b border-slate-200 bg-slate-50">
            <div v-for="ngay in cacNgay" :key="`header-${ngay.iso}`" class="flex flex-col items-center justify-center px-2 py-4 transition-colors" :class="ngay.la_hom_nay ? 'bg-teal-700' : ''">
              <p class="mb-1 text-xs font-bold uppercase tracking-widest" :class="ngay.la_hom_nay ? 'text-teal-100' : 'text-slate-500'">{{ tenThuDayDu(ngay.iso) }}</p>
              <div class="flex h-10 w-10 items-center justify-center rounded-full text-lg font-black" :class="ngay.la_hom_nay ? 'bg-white text-teal-700 shadow-md' : 'text-slate-800'">{{ ngay.ngaySo }}</div>
            </div>
          </div>

          <div class="flex flex-col divide-y divide-slate-100">
            <div v-if="!khungGio.length" class="px-6 py-16 text-center">
              <span class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-slate-100 text-slate-400"><i class="fa-regular fa-calendar-xmark text-xl" aria-hidden="true"></i></span>
              <p class="mt-4 font-semibold text-slate-700">Tuần này chưa có lịch dạy</p>
              <p class="mt-1 text-sm text-slate-500">Các buổi của lớp được phân công sẽ xuất hiện tại đây.</p>
            </div>
            <div v-for="khung in khungGio" :key="`${khung.bat_dau}-${khung.ket_thuc}`" class="grid grid-cols-7 divide-x divide-slate-100">
              <div v-for="ngay in cacNgay" :key="`${ngay.iso}-${khung.bat_dau}`" class="flex flex-col p-3 transition-colors hover:bg-slate-50/50" :class="ngay.la_hom_nay ? 'bg-teal-50/30' : 'bg-white'">
                <div class="mb-2 flex items-center gap-1.5 text-[11px] font-bold text-slate-400">
                  <i class="fa-regular fa-clock" aria-hidden="true"></i>{{ khung.bat_dau }} – {{ khung.ket_thuc }}
                </div>
                <div class="flex flex-1 flex-col gap-2">
                  <button v-for="buoi in buoiTrongKhung(ngay, khung)" :key="buoi.id" type="button"
                    class="flex min-h-[94px] w-full flex-1 flex-col justify-between rounded-xl border p-3 text-left shadow-sm transition-all hover:-translate-y-0.5 hover:shadow-md focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 active:scale-[.98]"
                    :class="[mauTheBuoi(buoi).bg, mauTheBuoi(buoi).border]" :aria-label="moTaBuoi(buoi)" @click="buoiChon = buoi">
                    <div>
                      <div class="mb-2 flex items-start justify-between gap-2">
                        <p class="line-clamp-2 text-xs font-bold leading-snug sm:text-sm" :class="mauTheBuoi(buoi).text">{{ buoi.mon_hoc }}</p>
                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg bg-white/70">
                          <i v-if="buoi.trang_thai === 'da_huy'" class="fa-solid fa-ban text-[10px] text-rose-600" aria-hidden="true"></i>
                          <i v-else-if="buoi.co_hoc_truc_tuyen" class="fa-solid fa-wifi text-[10px] text-emerald-600" aria-hidden="true"></i>
                          <i v-else class="fa-solid fa-building text-[10px] text-slate-500" aria-hidden="true"></i>
                        </span>
                      </div>
                      <p class="truncate text-[11px] font-semibold" :class="mauTheBuoi(buoi).text">{{ buoi.ma_lop || buoi.ten_lop }}</p>
                    </div>
                    <div class="mt-2 flex items-center justify-between gap-2 border-t border-black/5 pt-2 text-[11px] font-semibold" :class="mauTheBuoi(buoi).text">
                      <span class="truncate opacity-80">{{ buoi.phong_hoc || (buoi.co_hoc_truc_tuyen ? 'Online' : '—') }}</span>
                      <span v-if="buoi.phong_truc_tuyen?.trang_thai === 'dang_dien_ra'" class="shrink-0 rounded-full bg-rose-500 px-2 py-0.5 text-[9px] font-bold uppercase text-white">Đang dạy</span>
                    </div>
                  </button>
                  <div v-if="!buoiTrongKhung(ngay, khung).length" class="flex min-h-[94px] flex-1 items-center justify-center rounded-xl border border-dashed border-slate-200/80 text-slate-200" aria-hidden="true">
                    <i class="fa-solid fa-mug-hot"></i>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <Transition name="fade">
      <div v-if="buoiChon" class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center sm:p-6" role="dialog" aria-modal="true" aria-labelledby="tieu-de-buoi-day" @click.self="buoiChon = null">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative flex max-h-[90vh] w-full max-w-lg flex-col overflow-hidden rounded-[28px] bg-white shadow-2xl">
          <div class="sticky top-0 z-10 flex items-center justify-between border-b border-slate-100 bg-white px-6 py-5">
            <h2 id="tieu-de-buoi-day" class="text-lg font-black text-slate-900">Thông tin buổi dạy</h2>
            <button type="button" class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-50 text-slate-500 transition hover:bg-slate-200 hover:text-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500" aria-label="Đóng thông tin buổi dạy" @click="buoiChon = null">
              <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
            </button>
          </div>

          <div class="custom-scrollbar space-y-5 overflow-y-auto p-6">
            <div class="rounded-2xl border p-5 shadow-sm" :class="[mauMon(buoiChon).bg, mauMon(buoiChon).border]">
              <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                <p class="text-xs font-bold uppercase tracking-wider opacity-80" :class="mauMon(buoiChon).text">{{ tenThuDayDu(buoiChon.ngay_hoc) }} – {{ dinhDangNgayString(buoiChon.ngay_hoc) }}</p>
                <span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase" :class="mauTrangThai(buoiChon)">{{ nhanTrangThai(buoiChon) }}</span>
              </div>
              <h3 class="text-2xl font-extrabold leading-tight text-slate-900">{{ buoiChon.mon_hoc }}</h3>
              <p v-if="buoiChon.chu_de" class="mt-2 text-sm text-slate-600">{{ buoiChon.chu_de }}</p>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div v-for="info in thongTinBuoiChon" :key="info.nhan" class="rounded-2xl border border-slate-100 bg-slate-50 p-4">
                <p class="mb-2 flex items-center gap-2 text-xs font-semibold text-slate-500"><i :class="info.icon" class="text-teal-600" aria-hidden="true"></i>{{ info.nhan }}</p>
                <p class="text-base font-bold text-slate-800">{{ info.giaTri }}</p>
              </div>
            </div>

            <div v-if="loiMoPhong" class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">{{ loiMoPhong }}</div>

            <div v-if="buoiChon.trang_thai === 'da_huy'" class="flex items-start gap-3 rounded-2xl border border-rose-200 bg-rose-50 px-5 py-4 text-sm text-rose-800">
              <i class="fa-solid fa-ban mt-0.5 text-rose-500" aria-hidden="true"></i>
              <div><p class="font-bold text-rose-900">Buổi dạy đã hủy</p><p class="mt-0.5">Không thể mở phòng học cho lịch này.</p></div>
            </div>
            <div v-else-if="daQuaGio(buoiChon)" class="flex items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-5 py-4 text-sm text-slate-700">
              <i class="fa-solid fa-clock-rotate-left mt-0.5 text-slate-500" aria-hidden="true"></i>
              <div><p class="font-bold text-slate-900">Buổi dạy đã qua giờ</p><p class="mt-0.5">Phòng học trực tuyến không thể mở lại sau giờ kết thúc.</p></div>
            </div>
            <div v-else-if="buoiChon.co_hoc_truc_tuyen" class="pt-1">
              <button v-if="buoiChon.phong_truc_tuyen?.trang_thai === 'dang_dien_ra'" type="button" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-emerald-600 px-5 py-3 text-base font-bold text-white shadow-lg shadow-emerald-600/20 transition hover:bg-emerald-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2" @click="vaoPhong(buoiChon)">
                <i class="fa-solid fa-video" aria-hidden="true"></i>Vào phòng đang dạy
              </button>
              <button v-else type="button" class="flex min-h-12 w-full items-center justify-center gap-2 rounded-2xl bg-teal-600 px-5 py-3 text-base font-bold text-white shadow-lg shadow-teal-600/20 transition hover:bg-teal-700 disabled:cursor-wait disabled:opacity-60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-teal-500 focus-visible:ring-offset-2" :disabled="dangMo === buoiChon.id" @click="moPhong(buoiChon)">
                <i :class="dangMo === buoiChon.id ? 'fa-solid fa-spinner fa-spin' : 'fa-solid fa-video'" aria-hidden="true"></i>{{ dangMo === buoiChon.id ? 'Đang mở phòng...' : 'Mở phòng học trực tuyến' }}
              </button>
            </div>
            <div v-else class="flex items-center gap-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">
              <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-blue-100"><i class="fa-solid fa-location-dot text-xl text-blue-600" aria-hidden="true"></i></span>
              <div><p class="mb-0.5 text-sm font-bold text-blue-900">Dạy tại trường</p><p class="text-sm text-blue-800">Đến phòng <strong class="text-base text-blue-900">{{ buoiChon.phong_hoc || 'Chưa cập nhật' }}</strong></p></div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </div>
</template>

<script>
import api from '../../utils/axios'
import { useAuthStore } from '../../stores/auth'

const BANG_MAU = [
  { bg: 'bg-indigo-50', border: 'border-indigo-200', text: 'text-indigo-800' },
  { bg: 'bg-rose-50', border: 'border-rose-200', text: 'text-rose-800' },
  { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-800' },
  { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-800' },
  { bg: 'bg-sky-50', border: 'border-sky-200', text: 'text-sky-800' },
  { bg: 'bg-fuchsia-50', border: 'border-fuchsia-200', text: 'text-fuchsia-800' },
]

export default {
  name: 'giang-vien-lich-day-tuan',
  data() {
    return {
      tuanBatDau: null,
      danhSach: [],
      dangTai: false,
      loi: '',
      loiMoPhong: '',
      ngayHienTai: new Date(),
      boDemNgay: null,
      boDongBoLich: null,
      buoiChon: null,
      bangMauMon: {},
      dangMo: null,
    }
  },
  computed: {
    auth() { return useAuthStore() },
    tuanKetThuc() { return this.themNgay(this.tuanBatDau, 6) },
    danhSachHoatDong() { return this.danhSach.filter((buoi) => buoi.trang_thai !== 'da_huy') },
    soNgayCoLich() { return this.cacNgay.filter((ngay) => ngay.buois.some((buoi) => buoi.trang_thai !== 'da_huy')).length },
    cacNgay() {
      const ketQua = []
      for (let i = 0; i < 7; i++) {
        const ngay = this.themNgay(this.tuanBatDau, i)
        const iso = this.isoDate(ngay)
        ketQua.push({
          iso,
          ngaySo: ngay.getDate(),
          la_hom_nay: iso === this.isoDate(this.ngayHienTai),
          buois: this.danhSach.filter((buoi) => buoi.ngay_hoc === iso)
            .sort((a, b) => String(a.gio_bat_dau).localeCompare(String(b.gio_bat_dau))),
        })
      }
      return ketQua
    },
    soTuan() {
      const ngay = new Date(Date.UTC(this.tuanBatDau.getFullYear(), this.tuanBatDau.getMonth(), this.tuanBatDau.getDate()))
      ngay.setUTCDate(ngay.getUTCDate() + 4 - (ngay.getUTCDay() || 7))
      return Math.ceil((((ngay - new Date(Date.UTC(ngay.getUTCFullYear(), 0, 1))) / 86400000) + 1) / 7)
    },
    laTuanHienTai() {
      const homNay = this.isoDate(new Date())
      return homNay >= this.isoDate(this.tuanBatDau) && homNay <= this.isoDate(this.tuanKetThuc)
    },
    khungGio() {
      const cacKhung = new Map()
      this.danhSach.forEach((buoi) => {
        const batDau = String(buoi.gio_bat_dau || '').slice(0, 5)
        const ketThuc = String(buoi.gio_ket_thuc || '').slice(0, 5)
        if (batDau && ketThuc) cacKhung.set(`${batDau}-${ketThuc}`, { bat_dau: batDau, ket_thuc: ketThuc })
      })
      return [...cacKhung.values()].sort((a, b) => a.bat_dau.localeCompare(b.bat_dau))
    },
    thongTinBuoiChon() {
      if (!this.buoiChon) return []
      return [
        { icon: 'fa-regular fa-clock', nhan: 'Thời gian', giaTri: `${this.buoiChon.gio_bat_dau} – ${this.buoiChon.gio_ket_thuc}` },
        { icon: 'fa-solid fa-chalkboard', nhan: 'Lớp học phần', giaTri: `${this.buoiChon.ma_lop || ''}${this.buoiChon.ma_lop ? ' – ' : ''}${this.buoiChon.ten_lop || 'Chưa cập nhật'}` },
        { icon: 'fa-solid fa-location-dot', nhan: 'Phòng học', giaTri: this.buoiChon.phong_hoc || (this.buoiChon.co_hoc_truc_tuyen ? 'Trực tuyến' : 'Chưa cập nhật') },
        { icon: 'fa-solid fa-wifi', nhan: 'Hình thức', giaTri: this.buoiChon.co_hoc_truc_tuyen ? 'Dạy trực tuyến' : 'Dạy tại trường' },
      ]
    },
  },
  async created() {
    this.veTuanHienTai()
    await this.taiLich()
  },
  mounted() {
    this.boDemNgay = window.setInterval(() => { this.ngayHienTai = new Date() }, 60 * 1000)
    this.boDongBoLich = window.setInterval(() => this.taiLich(true), 30 * 1000)
  },
  beforeUnmount() {
    window.clearInterval(this.boDemNgay)
    window.clearInterval(this.boDongBoLich)
  },
  methods: {
    isoDate(ngay) {
      return `${ngay.getFullYear()}-${String(ngay.getMonth() + 1).padStart(2, '0')}-${String(ngay.getDate()).padStart(2, '0')}`
    },
    themNgay(ngay, soNgay) {
      const ketQua = new Date(ngay)
      ketQua.setDate(ketQua.getDate() + soNgay)
      return ketQua
    },
    veTuanHienTai() {
      const homNay = new Date()
      const thuHai = new Date(homNay)
      thuHai.setDate(homNay.getDate() - ((homNay.getDay() + 6) % 7))
      this.tuanBatDau = thuHai
    },
    veTuanHienTaiVaTai() {
      this.veTuanHienTai()
      this.taiLich()
    },
    doiTuan(soTuan) {
      this.tuanBatDau = this.themNgay(this.tuanBatDau, soTuan * 7)
      this.taiLich()
    },
    async taiLich(imLang = false) {
      if (!imLang) {
        this.dangTai = true
        this.loi = ''
      }
      try {
        const { data } = await api.get('/lich-hoc', {
          params: { tu_ngay: this.isoDate(this.tuanBatDau), den_ngay: this.isoDate(this.tuanKetThuc) },
        })
        this.danhSach = data.danh_sach || []
        if (this.buoiChon) this.buoiChon = this.danhSach.find((buoi) => Number(buoi.id) === Number(this.buoiChon.id)) || null
        this.phanMauMon()
      } catch (error) {
        if (!imLang) {
          this.danhSach = []
          this.loi = error.response?.data?.message || 'Không thể tải lịch dạy. Vui lòng thử lại.'
        }
      } finally {
        if (!imLang) this.dangTai = false
      }
    },
    phanMauMon() {
      this.bangMauMon = {}
      const cacMon = [...new Set(this.danhSach.map((buoi) => buoi.ma_mon_hoc).filter(Boolean))]
      cacMon.forEach((maMon, index) => { this.bangMauMon[maMon] = BANG_MAU[index % BANG_MAU.length] })
    },
    mauMon(buoi) {
      return this.bangMauMon[buoi.ma_mon_hoc] || { bg: 'bg-slate-50', border: 'border-slate-300', text: 'text-slate-800' }
    },
    mauTheBuoi(buoi) {
      if (buoi.trang_thai === 'da_huy') return { bg: 'bg-rose-50/60', border: 'border-dashed border-rose-200', text: 'text-rose-700' }
      return buoi.co_hoc_truc_tuyen
        ? { bg: 'bg-emerald-50', border: 'border-emerald-300', text: 'text-emerald-900' }
        : { bg: 'bg-white', border: 'border-slate-300', text: 'text-slate-800' }
    },
    buoiTrongKhung(ngay, khung) {
      return ngay.buois.filter((buoi) => String(buoi.gio_bat_dau).slice(0, 5) === khung.bat_dau
        && String(buoi.gio_ket_thuc).slice(0, 5) === khung.ket_thuc)
    },
    moTaBuoi(buoi) {
      return `${buoi.mon_hoc}, lớp ${buoi.ma_lop || buoi.ten_lop}, ${buoi.gio_bat_dau} đến ${buoi.gio_ket_thuc}`
    },
    daQuaGio(buoi) {
      if (typeof buoi.da_qua_gio_hoc === 'boolean') return buoi.da_qua_gio_hoc
      return new Date(`${buoi.ngay_hoc}T${buoi.gio_ket_thuc}:00`).getTime() <= Date.now()
    },
    nhanTrangThai(buoi) {
      if (buoi.trang_thai === 'da_huy') return 'Đã hủy'
      if (buoi.phong_truc_tuyen?.trang_thai === 'dang_dien_ra') return 'Đang dạy'
      if (this.daQuaGio(buoi) || buoi.trang_thai === 'da_hoc') return 'Đã kết thúc'
      return 'Sắp tới'
    },
    mauTrangThai(buoi) {
      if (buoi.trang_thai === 'da_huy') return 'bg-rose-100 text-rose-700'
      if (buoi.phong_truc_tuyen?.trang_thai === 'dang_dien_ra') return 'bg-emerald-600 text-white'
      if (this.daQuaGio(buoi) || buoi.trang_thai === 'da_hoc') return 'bg-slate-200 text-slate-700'
      return 'bg-amber-100 text-amber-700'
    },
    dinhDangNgay(ngay) { return ngay.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }) },
    dinhDangNgayString(ngay) { return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN') },
    tenThuDayDu(ngay) { return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN', { weekday: 'long' }) },
    async moPhong(buoi) {
      this.dangMo = buoi.id
      this.loiMoPhong = ''
      try {
        const { data } = await api.post('/phong/bat-dau', { ma_lich_hoc: buoi.id })
        this.$router.push({ name: 'phong-hoc', params: { maPhong: data.phong.ma_phong } })
      } catch (error) {
        this.loiMoPhong = error.response?.data?.message || 'Không thể mở phòng học trực tuyến.'
      } finally {
        this.dangMo = null
      }
    },
    vaoPhong(buoi) {
      this.$router.push({ name: 'phong-hoc', params: { maPhong: buoi.phong_truc_tuyen.ma_phong } })
    },
  },
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { width: 6px; height: 10px; }
.custom-scrollbar::-webkit-scrollbar-track { border-radius: 8px; background: #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb { border: 2px solid #f8fafc; border-radius: 8px; background: #cbd5e1; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

@media (prefers-reduced-motion: reduce) {
  .fade-enter-active, .fade-leave-active { transition: none; }
}
</style>
