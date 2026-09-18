<template>
  <div class="lich-hoc-page space-y-6 max-w-[1600px] mx-auto">
    <!-- Header -->
    <section class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-brand-900 to-brand-700 px-6 py-8 sm:px-10 text-white shadow-xl">
      <div class="absolute -right-10 -top-16 h-64 w-64 rounded-full border-[28px] border-cyan-300/10"></div>
      <div class="absolute right-24 -bottom-20 h-48 w-48 rounded-full border-[20px] border-white/5"></div>
      <div class="relative z-10 flex flex-wrap items-end justify-between gap-6">
        <div>
          <div class="flex items-center gap-2 text-xs text-cyan-100/70 mb-3 font-medium">
            <router-link :to="{ name: 'sinh-vien-trang-chu' }" class="hover:text-white transition">Tổng quan</router-link>
            <i class="fa-solid fa-chevron-right text-[9px]"></i>
            <span class="text-cyan-100">Lịch học</span>
          </div>
          <p class="text-xs font-bold uppercase tracking-[0.2em] text-cyan-400 mb-2">Lịch trình học tập</p>
          <h1 class="text-3xl sm:text-4xl font-extrabold tracking-tight">Tuần học của {{ auth.hoTen || 'Sinh viên' }}</h1>
          <p class="text-sm sm:text-base text-cyan-100/80 mt-2">Theo dõi toàn bộ lịch học trong tuần dễ dàng, thẳng hàng và không bị khuất.</p>
        </div>
        <div class="flex gap-3">
          <div class="rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 px-5 py-4 text-center min-w-[100px]">
            <p class="text-3xl font-black">{{ danhSach.length }}</p>
            <p class="text-[11px] font-bold uppercase tracking-wide text-cyan-200 mt-1">Buổi học</p>
          </div>
          <div class="rounded-2xl bg-white/10 backdrop-blur-sm border border-white/20 px-5 py-4 text-center min-w-[100px]">
            <p class="text-3xl font-black">{{ cacNgay.filter((ngay) => ngay.buois.length).length }}</p>
            <p class="text-[11px] font-bold uppercase tracking-wide text-cyan-200 mt-1">Ngày bận</p>
          </div>
        </div>
      </div>
    </section>

    <!-- Toolbar điều hướng tuần -->
    <div class="flex flex-col sm:flex-row items-center justify-between gap-4 bg-white p-2 rounded-2xl shadow-sm border border-slate-200">
      <div class="flex items-center gap-2 w-full sm:w-auto justify-between sm:justify-start">
        <button class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 transition-all" @click="doiTuan(-1)">
          <i class="fa-solid fa-chevron-left"></i>
        </button>
        <div class="text-center min-w-[240px]">
          <p class="text-base font-extrabold text-slate-800">Tuần {{ soTuan }}</p>
          <p class="text-sm text-slate-500 mt-0.5 font-medium">{{ dinhDangNgay(tuanBatDau) }} – {{ dinhDangNgay(tuanKetThuc) }} / {{ tuanKetThuc.getFullYear() }}</p>
          <button class="text-xs text-brand-600 font-bold mt-1.5 hover:text-brand-800 transition" @click="veTuanHienTaiVaTai">
            {{ laTuanHienTai ? '★ Đang ở tuần hiện tại' : 'Trở về tuần hiện tại' }}
          </button>
        </div>
        <button class="w-12 h-12 rounded-xl border border-slate-200 bg-slate-50 flex items-center justify-center text-slate-600 hover:bg-brand-50 hover:text-brand-700 hover:border-brand-200 transition-all" @click="doiTuan(1)">
          <i class="fa-solid fa-chevron-right"></i>
        </button>
      </div>
      
      <div class="flex flex-wrap items-center justify-center gap-4 text-sm font-medium text-slate-600 px-4">
        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-emerald-500 shadow-sm"></span>Online</span>
        <span class="flex items-center gap-2"><span class="w-3 h-3 rounded-full bg-slate-300 shadow-sm"></span>Tại trường</span>
      </div>
    </div>

    <!-- Khung Loading -->
    <div v-if="dangTai" class="py-20 text-center text-slate-400 bg-white rounded-3xl border border-slate-100 shadow-sm">
      <i class="fa-solid fa-circle-notch fa-spin text-4xl text-brand-500"></i>
      <p class="mt-4 text-base font-medium">Đang đồng bộ lịch học...</p>
    </div>

    <!-- Lưới Lịch Học (Đã cấu trúc lại để đảm bảo vừa màn hình và thẳng hàng) -->
    <div v-else class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
      <div class="overflow-x-auto custom-scrollbar">
        <!-- Rút độ rộng tối thiểu để trên Laptop không phải cuộn ngang -->
        <div class="min-w-[900px]">
          
          <!-- Lưới Header (Các ngày trong tuần) -->
          <div class="grid grid-cols-7 divide-x divide-slate-100 border-b border-slate-200 bg-slate-50">
            <div v-for="ngay in cacNgayHienThi" :key="'header-'+ngay.iso" class="py-4 px-2 flex flex-col items-center justify-center transition-colors" :class="ngay.la_hom_nay ? 'bg-brand-600' : ''">
              <p class="text-xs font-bold uppercase tracking-widest mb-1" :class="ngay.la_hom_nay ? 'text-brand-100' : 'text-slate-500'">{{ tenThuDayDu(ngay.iso) }}</p>
              <div class="w-10 h-10 rounded-full flex items-center justify-center text-lg font-black" :class="ngay.la_hom_nay ? 'bg-white text-brand-700 shadow-md' : 'text-slate-800'">
                {{ ngay.ngaySo }}
              </div>
            </div>
          </div>

          <!-- Lưới Body (Các HÀNG khung giờ - Đảm bảo ngang hàng 100%) -->
          <div class="flex flex-col divide-y divide-slate-100">
            <div v-for="khung in khungGio" :key="khung.bat_dau" class="grid grid-cols-7 divide-x divide-slate-100">
              
              <!-- 7 ô tương ứng với 7 ngày trong cùng 1 khung giờ -->
              <div v-for="ngay in cacNgayHienThi" :key="ngay.iso + '-' + khung.bat_dau" class="p-3 flex flex-col transition-colors hover:bg-slate-50/50" :class="ngay.la_hom_nay ? 'bg-brand-50/20' : 'bg-white'">
                
                <!-- Nhãn thời gian -->
                <div class="text-[11px] font-bold text-slate-400 mb-2 flex items-center gap-1.5 opacity-70">
                  <i class="fa-regular fa-clock"></i> {{ khung.bat_dau }} - {{ khung.ket_thuc }}
                </div>

                <!-- Thẻ học (Nếu có) -->
                <div class="flex-1 flex flex-col gap-2">
                  <button 
                    v-for="b in buoiTrongKhung(ngay, khung)" 
                    :key="b.id" 
                    class="w-full h-full text-left rounded-xl p-3 border shadow-sm hover:shadow-md hover:-translate-y-0.5 active:scale-[.98] transition-all flex flex-col justify-between" 
                    :class="[mauHinhThuc(b).bg, mauHinhThuc(b).border]" 
                    @click="chonBuoi(b)"
                  >
                    <div class="flex items-start justify-between gap-2 mb-2">
                      <p class="text-xs sm:text-sm font-bold line-clamp-3 leading-snug" :class="mauHinhThuc(b).text">{{ b.mon_hoc }}</p>
                      <div class="shrink-0 bg-white/70 p-1.5 rounded-lg flex items-center justify-center">
                        <i v-if="b.co_hoc_truc_tuyen" class="fa-solid fa-wifi text-emerald-600 text-[10px]"></i>
                        <i v-else class="fa-solid fa-building text-slate-500 text-[10px]"></i>
                      </div>
                    </div>
                    
                    <div class="pt-2 border-t border-black/5 flex items-center justify-between text-[11px] font-semibold" :class="mauHinhThuc(b).text">
                      <span class="opacity-80">{{ b.phong_hoc || (b.co_hoc_truc_tuyen ? 'Online' : '—') }}</span>
                    </div>
                  </button>

                  <!-- Placeholder nếu trống (giữ cấu trúc cân đối) -->
                  <div v-if="!buoiTrongKhung(ngay, khung).length" class="flex-1 min-h-[70px] rounded-xl border border-dashed border-slate-200/80 flex items-center justify-center text-slate-200">
                    <i class="fa-solid fa-mug-hot"></i>
                  </div>
                </div>

              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Modal Chi Tiết Buổi Học -->
    <Transition name="fade">
      <div v-if="buoiChon" class="fixed inset-0 z-50 flex items-end sm:items-center justify-center p-4 sm:p-6" @click.self="buoiChon = null">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-[28px] shadow-2xl w-full max-w-lg overflow-hidden flex flex-col max-h-[90vh]">
          
          <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-white sticky top-0 z-10">
            <h2 class="text-lg font-black text-slate-900">Thông tin buổi học</h2>
            <button class="w-10 h-10 rounded-full bg-slate-50 text-slate-500 hover:bg-slate-200 hover:text-slate-800 transition" @click="buoiChon = null">
              <i class="fa-solid fa-xmark text-lg"></i>
            </button>
          </div>
          
          <div class="p-6 space-y-5 overflow-y-auto custom-scrollbar">
            <div class="rounded-2xl p-5 border shadow-sm" :class="[mauMon(buoiChon).bg, mauMon(buoiChon).border]">
              <p class="text-xs font-bold uppercase tracking-wider mb-2 opacity-80" :class="mauMon(buoiChon).text">
                {{ tenThuDayDu(buoiChon.ngay_hoc) }} – {{ dinhDangNgayString(buoiChon.ngay_hoc) }}
              </p>
              <h3 class="text-2xl font-extrabold text-slate-900 leading-tight">{{ buoiChon.mon_hoc }}</h3>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
              <div v-for="info in thongTinBuoiChon" :key="info.nhan" class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                <p class="text-xs font-semibold text-slate-500 flex items-center gap-2 mb-2">
                  <i :class="info.icon" class="text-brand-500"></i>{{ info.nhan }}
                </p>
                <p class="text-base font-bold text-slate-800">{{ info.giaTri }}</p>
              </div>
            </div>
            
            <div v-if="buoiChon.co_hoc_truc_tuyen" class="pt-2">
              <button v-if="buoiChon.phong_truc_tuyen?.trang_thai === 'dang_dien_ra'" class="w-full flex items-center justify-center gap-2 py-4 bg-emerald-600 text-white text-base font-bold rounded-2xl shadow-lg shadow-emerald-600/30 hover:bg-emerald-700 hover:-translate-y-0.5 transition-all" @click="vaoPhong(buoiChon)">
                <i class="fa-solid fa-video"></i>Tham gia phòng học ngay
              </button>
              <div v-else class="w-full rounded-2xl bg-amber-50 border border-amber-200 px-5 py-4 flex items-start gap-3 text-sm text-amber-800 font-medium">
                <i class="fa-solid fa-clock text-amber-500 text-lg mt-0.5"></i>
                <div>
                  <p class="font-bold text-amber-900 mb-0.5">Phòng học chưa mở</p>
                  <p>Hệ thống sẽ mở phòng khi đến giờ học. Vui lòng quay lại sau.</p>
                </div>
              </div>
            </div>
            <div v-else class="bg-blue-50 border border-blue-100 rounded-2xl p-4 flex items-center gap-4">
              <div class="w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center shrink-0">
                <i class="fa-solid fa-location-dot text-xl text-blue-600"></i>
              </div>
              <div>
                <p class="text-sm font-bold text-blue-900 mb-0.5">Học tập trung (Offline)</p>
                <p class="text-sm text-blue-800">Di chuyển đến phòng <strong class="text-blue-900 text-base">{{ buoiChon.phong_hoc || '—' }}</strong></p>
              </div>
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

const THU = ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7']
const BANG_MAU = [
  { bg: 'bg-indigo-50', border: 'border-indigo-200', text: 'text-indigo-800' },
  { bg: 'bg-rose-50', border: 'border-rose-200', text: 'text-rose-800' },
  { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-800' },
  { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-800' },
  { bg: 'bg-sky-50', border: 'border-sky-200', text: 'text-sky-800' },
  { bg: 'bg-fuchsia-50', border: 'border-fuchsia-200', text: 'text-fuchsia-800' },
  { bg: 'bg-orange-50', border: 'border-orange-200', text: 'text-orange-800' },
]

export default {
  name: 'sinh-vien-lich-hoc',
  data() {
    return {
      tuanBatDau: null,
      danhSach: [],
      dangTai: false,
      loi: '',
      ngayHienTai: new Date(),
      boDemNgay: null,
      buoiChon: null,
      bangMauMon: {}, 
    }
  },
  computed: {
    auth() { return useAuthStore() },
    tuanKetThuc() { return this.themNgay(this.tuanBatDau, 6) },
    cacNgay() {
      const ketQua = []
      for (let i = 0; i < 7; i++) {
        const d = this.themNgay(this.tuanBatDau, i)
        const iso = this.isoDate(d)
        ketQua.push({
          iso,
          thu: THU[d.getDay()],
          ngaySo: d.getDate(),
          la_hom_nay: iso === this.isoDate(this.ngayHienTai),
          buois: this.danhSach.filter((b) => b.ngay_hoc === iso).sort((a, b) => String(a.gio_bat_dau).localeCompare(String(b.gio_bat_dau))),
        })
      }
      return ketQua
    },
    cacNgayHienThi() { return this.cacNgay.slice(0, 7) },
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
        { icon: 'fa-regular fa-clock', nhan: 'Thời gian', giaTri: `${this.buoiChon.gio_bat_dau} – ${this.buoiChon.gio_ket_thuc}` },
        { icon: 'fa-solid fa-chalkboard-user', nhan: 'Giảng viên', giaTri: this.buoiChon.giang_vien || 'Chưa cập nhật' },
        { icon: 'fa-solid fa-location-dot', nhan: 'Phòng học', giaTri: this.buoiChon.phong_hoc || (this.buoiChon.co_hoc_truc_tuyen ? 'Trực tuyến' : '—') },
        { icon: 'fa-solid fa-wifi', nhan: 'Hình thức', giaTri: this.buoiChon.co_hoc_truc_tuyen ? 'Học Online' : 'Tại cơ sở' },
      ]
    },
    khungGio() {
      return [
        { bat_dau: '07:00', ket_thuc: '09:00' },
        { bat_dau: '09:15', ket_thuc: '11:15' },
        { bat_dau: '13:00', ket_thuc: '15:00' },
        { bat_dau: '15:15', ket_thuc: '17:15' },
        { bat_dau: '17:45', ket_thuc: '21:00' },
      ]
    }
  },
  async created() {
    this.veTuanHienTai()
    await this.taiLich()
  },
  mounted() {
    this.boDemNgay = window.setInterval(() => {
      this.ngayHienTai = new Date()
    }, 60 * 1000)
  },
  beforeUnmount() {
    window.clearInterval(this.boDemNgay)
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
      this.dangTai = true
      this.loi = ''
      try {
        const { data } = await api.get('/lich-hoc', { params: { tu_ngay: this.isoDate(this.tuanBatDau), den_ngay: this.isoDate(this.tuanKetThuc) } })
        let danhSach = data.danh_sach || []
        if (!danhSach.length) {
          const fallback = await api.get('/lich-hoc')
          danhSach = (fallback.data.danh_sach || []).filter((b) => b.ngay_hoc >= this.isoDate(this.tuanBatDau) && b.ngay_hoc <= this.isoDate(this.tuanKetThuc))
        }
        this.danhSach = danhSach
        this.phanMauMon()
      } catch (e) {
        this.danhSach = []
        this.loi = e.response?.data?.message || 'Không thể tải lịch học. Vui lòng thử lại.'
      } finally {
        this.dangTai = false
      }
    },
    phanMauMon() {
      const cacMon = [...new Set(this.danhSach.map((b) => b.ma_mon_hoc).filter(Boolean))]
      this.bangMauMon = {}
      cacMon.forEach((ma, i) => {
        this.bangMauMon[ma] = BANG_MAU[i % BANG_MAU.length]
      })
    },
    mauMon(b) {
      return this.bangMauMon[b.ma_mon_hoc] || { bg: 'bg-slate-50', border: 'border-slate-300', text: 'text-slate-800' }
    },
    mauHinhThuc(b) {
      return b.co_hoc_truc_tuyen
        ? { bg: 'bg-emerald-50', border: 'border-emerald-300', text: 'text-emerald-900' }
        : { bg: 'bg-white', border: 'border-slate-300', text: 'text-slate-800' }
    },
    buoiTrongKhung(ngay, khung) {
      const toPhut = (gio) => {
        const [h, m] = String(gio).split(':').map(Number)
        return h * 60 + m
      }
      const batDauKhung = toPhut(khung.bat_dau)
      const ketThucKhung = toPhut(khung.ket_thuc)
      return ngay.buois.filter((b) => {
        const batDau = toPhut(b.gio_bat_dau)
        return batDau >= batDauKhung && batDau < ketThucKhung
      })
    },
    chonBuoi(b) {
      this.buoiChon = b
    },
    dinhDangNgay(d) { return d.toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }) },
    dinhDangNgayString(n) { return new Date(n).toLocaleDateString('vi-VN') },
    tenThuDayDu(n) { return new Date(n).toLocaleDateString('vi-VN', { weekday: 'long' }) },
    vaoPhong(buoi) {
      this.buoiChon = null
      this.$router.push({ name: 'phong-hoc', params: { maPhong: buoi.phong_truc_tuyen.ma_phong } })
    },
  },
}
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s ease; }
.fade-enter-from, .fade-leave-to { opacity: 0; }

.custom-scrollbar::-webkit-scrollbar { height: 10px; width: 6px; }
.custom-scrollbar::-webkit-scrollbar-track { background: #f8fafc; border-radius: 8px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 8px; border: 2px solid #f8fafc; }
.custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>