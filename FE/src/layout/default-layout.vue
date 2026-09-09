<template>
  <div class="min-h-screen flex flex-col bg-[#F8FAFC]">
    <header class="sticky top-0 z-40 bg-white border-b border-slate-200">
      <div class="max-w-7xl mx-auto px-4 sm:px-6">
        <div class="h-14 flex items-center gap-4">
          <router-link :to="{ name: 'sinh-vien-trang-chu' }" class="flex items-center gap-2 shrink-0">
            <span class="w-8 h-8 rounded-lg bg-brand-600 text-white flex items-center justify-center"><i class="fa-solid fa-graduation-cap text-sm"></i></span>
            <span class="hidden sm:block font-bold text-slate-900">EduPortal</span>
          </router-link>

          <nav class="hidden md:flex items-center gap-1 ml-3">
            <router-link v-for="m in menu" :key="m.route" :to="{ name: m.route }"
              class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-900 transition-colors"
              active-class="!bg-brand-50 !text-brand-700">
              <i :class="m.icon" class="text-xs"></i>{{ m.ten }}
            </router-link>
          </nav>

          <div class="ml-auto flex items-center gap-2">
            <div class="relative">
              <button class="relative w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100 transition-colors" title="Thông báo" @click="moThongBao = !moThongBao; moHoSo = false">
                <i class="fa-regular fa-bell text-sm"></i>
                <span v-if="thongBao.chuaDoc" class="absolute top-1 right-1 min-w-4 h-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center">{{ thongBao.chuaDoc }}</span>
              </button>
              <div v-if="moThongBao" class="absolute right-0 top-11 z-50 w-[min(20rem,calc(100vw-2rem))] rounded-xl bg-white text-slate-800 shadow-xl border border-slate-200 overflow-hidden">
                <div class="px-4 py-3 flex items-center justify-between border-b border-slate-100"><span class="font-semibold text-sm">Thông báo</span><button class="text-xs font-medium text-brand-600" @click="docTatCa">Đọc tất cả</button></div>
                <div v-if="!thongBao.danhSach.length" class="p-5 text-sm text-slate-400">Chưa có thông báo.</div>
                <button v-for="item in thongBao.danhSach.slice(0, 5)" :key="item.id" class="w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-slate-50" :class="item.da_doc ? '' : 'bg-brand-50/60'" @click="doc(item)">
                  <div class="text-sm font-semibold">{{ item.tieu_de }}</div><div class="text-xs text-slate-500 mt-0.5">{{ item.noi_dung }}</div>
                </button>
              </div>
            </div>

            <div class="relative">
              <button class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition-colors" @click="moHoSo = !moHoSo; moThongBao = false">
                <span class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-semibold text-xs">{{ chuCai(auth.hoTen) }}</span>
                <span class="hidden sm:block max-w-[120px] truncate text-sm font-medium text-slate-700">{{ auth.hoTen }}</span>
                <i class="hidden sm:block fa-solid fa-chevron-down text-[10px] text-slate-400"></i>
              </button>
              <div v-if="moHoSo" class="absolute right-0 top-11 z-50 w-52 rounded-xl bg-white shadow-xl border border-slate-200 py-1">
                <div class="px-4 py-3 border-b border-slate-100"><p class="text-sm font-semibold text-slate-900 truncate">{{ auth.hoTen }}</p><p class="text-xs text-slate-500">Sinh viên</p></div>
                <router-link :to="{ name: 'ho-so' }" class="flex items-center gap-2.5 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50" @click="moHoSo = false"><i class="fa-regular fa-user w-4 text-slate-400"></i>Hồ sơ</router-link>
                <button class="w-full flex items-center gap-2.5 px-4 py-2.5 text-sm text-rose-600 hover:bg-rose-50 border-t border-slate-100" @click="dangXuat"><i class="fa-solid fa-arrow-right-from-bracket w-4 text-rose-400"></i>Đăng xuất</button>
              </div>
            </div>

            <button class="md:hidden w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100" @click="moMenu = !moMenu"><i class="fa-solid" :class="moMenu ? 'fa-xmark' : 'fa-bars'"></i></button>
          </div>
        </div>

        <nav v-if="moMenu" class="md:hidden py-2 pb-3 border-t border-slate-100 space-y-1">
          <router-link v-for="m in menu" :key="m.route" :to="{ name: m.route }"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-50"
            active-class="!bg-brand-50 !text-brand-700" @click="moMenu = false">
            <i :class="m.icon" class="w-4 text-center"></i>{{ m.ten }}
          </router-link>
        </nav>
      </div>
    </header>

    <div v-if="moThongBao || moHoSo" class="fixed inset-0 z-30" @click="dongDropdown"></div>

    <main class="flex-1 py-6 sm:py-8"><div class="max-w-7xl mx-auto px-4 sm:px-6"><slot /></div></main>
    <footer class="py-4 text-center text-slate-400 text-xs bg-white border-t border-slate-200">© 2026 EduPortal · Nền tảng quản lý học tập</footer>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'
import { useThongBaoStore } from '../stores/thong-bao'

export default {
  name: 'default-layout',
  data() {
    return {
      moMenu: false,
      moThongBao: false,
      moHoSo: false,
      menu: [
        { ten: 'Tổng quan', route: 'sinh-vien-trang-chu', icon: 'fa-solid fa-house' },
        { ten: 'Lịch học', route: 'sinh-vien-lich-hoc', icon: 'fa-solid fa-calendar-week' },
        { ten: 'Điểm số', route: 'diem-cua-toi', icon: 'fa-solid fa-chart-bar' },
        { ten: 'Điểm danh', route: 'lich-su-diem-danh', icon: 'fa-solid fa-clipboard-check' },
        { ten: 'Đăng ký lớp', route: 'dang-ky-lop', icon: 'fa-solid fa-book-open' },
        { ten: 'Xin phép vắng', route: 'xin-phep-vang', icon: 'fa-solid fa-file-pen' },
      ],
    }
  },
  computed: {
    auth() { return useAuthStore() },
    thongBao() { return useThongBaoStore() },
  },
  mounted() { this.thongBao.khoiTao(this.auth.user, this.auth.token) },
  methods: {
    dongDropdown() { this.moThongBao = false; this.moHoSo = false },
    async doc(item) { if (!item.da_doc) await this.thongBao.danhDauDaDoc(item.id) },
    async docTatCa() { await this.thongBao.danhDauTatCaDaDoc() },
    chuCai(ten) { return (ten || '?').split(' ').filter(Boolean).slice(-2).map((tu) => tu[0]).join('').toUpperCase() },
    async dangXuat() { await this.auth.dangXuat(); this.$router.push({ name: 'dang-nhap' }) },
  },
}
</script>
