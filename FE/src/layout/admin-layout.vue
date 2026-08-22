<template>
  <div class="flex min-h-screen bg-[#F8FAFC] overflow-hidden">
    <div v-if="moSidebar" class="fixed inset-0 z-40 bg-slate-900/40 backdrop-blur-sm lg:hidden" @click="moSidebar = false"></div>

    <aside
      class="fixed lg:relative inset-y-0 left-0 z-50 lg:z-auto flex flex-col shrink-0 bg-white border-r border-slate-200 shadow-2xl lg:shadow-none transition-all duration-200"
      :class="[moSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0', thuGon ? 'lg:w-[60px] w-64' : 'w-64 lg:w-56']"
    >
      <div class="h-14 shrink-0 flex items-center gap-2.5 px-4 border-b border-slate-100" :class="thuGon ? 'lg:justify-center' : ''">
        <span class="w-8 h-8 shrink-0 rounded-lg bg-brand-600 text-white flex items-center justify-center"><i class="fa-solid fa-graduation-cap text-sm"></i></span>
        <span class="font-bold text-slate-900 text-sm" :class="thuGon ? 'lg:hidden' : ''">EduPortal</span>
        <button class="ml-auto lg:hidden w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="moSidebar = false"><i class="fa-solid fa-xmark"></i></button>
      </div>

      <nav class="flex-1 overflow-y-auto px-2 py-3 space-y-0.5">
        <p class="px-3 pt-1 pb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400" :class="thuGon ? 'lg:hidden' : ''">{{ auth.laAdmin ? 'Quản trị' : 'Giảng dạy' }}</p>
        <router-link v-for="m in menuHienTai" :key="m.route" :to="{ name: m.route }"
          class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium text-slate-600 hover:bg-slate-50 hover:text-slate-800 transition-colors"
          :class="thuGon ? 'lg:justify-center lg:px-2' : ''" active-class="!bg-brand-50 !text-brand-700" :title="thuGon ? m.ten : ''" @click="moSidebar = false">
          <i :class="m.icon" class="w-4 text-center shrink-0"></i><span :class="thuGon ? 'lg:hidden' : ''">{{ m.ten }}</span>
        </router-link>
      </nav>

      <div class="shrink-0 border-t border-slate-100 p-3">
        <div class="flex items-center gap-2.5" :class="thuGon ? 'lg:justify-center' : ''">
          <span class="w-8 h-8 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-semibold text-xs shrink-0">{{ chuCai(auth.user?.ho_ten) }}</span>
          <div class="min-w-0 flex-1" :class="thuGon ? 'lg:hidden' : ''"><p class="text-xs font-semibold text-slate-800 truncate">{{ auth.user?.ho_ten }}</p><p class="text-xs text-slate-500">{{ auth.laAdmin ? 'Quản trị viên' : 'Giảng viên' }}</p></div>
          <button class="w-7 h-7 rounded-lg text-slate-400 hover:bg-rose-50 hover:text-rose-600" :class="thuGon ? 'lg:hidden' : ''" title="Đăng xuất" @click="dangXuat"><i class="fa-solid fa-arrow-right-from-bracket text-xs"></i></button>
        </div>
      </div>
    </aside>

    <div class="min-w-0 flex-1 flex flex-col h-screen overflow-hidden">
      <header class="h-14 shrink-0 px-4 sm:px-6 bg-white border-b border-slate-200 flex items-center gap-3">
        <button class="lg:hidden w-9 h-9 rounded-lg text-slate-500 hover:bg-slate-100" @click="moSidebar = true"><i class="fa-solid fa-bars"></i></button>
        <button class="hidden lg:flex w-8 h-8 rounded-lg items-center justify-center text-slate-400 hover:bg-slate-100 hover:text-brand-600" @click="thuGon = !thuGon" :title="thuGon ? 'Mở rộng menu' : 'Thu gọn menu'"><i class="fa-solid text-xs" :class="thuGon ? 'fa-indent' : 'fa-outdent'"></i></button>
        <div class="min-w-0">
          <p class="text-[11px] text-slate-400 leading-none mb-1">{{ auth.laAdmin ? 'Quản trị hệ thống' : 'Khu vực giảng viên' }}</p>
          <h1 class="text-sm font-semibold text-slate-900 truncate">{{ tieuDe }}</h1>
        </div>
        <div class="ml-auto flex items-center gap-2">
          <div class="relative">
            <button class="relative w-9 h-9 rounded-lg flex items-center justify-center text-slate-500 hover:bg-slate-100" title="Thông báo" @click="moThongBao = !moThongBao">
              <i class="fa-regular fa-bell"></i><span v-if="thongBao.chuaDoc" class="absolute top-1 right-1 min-w-4 h-4 px-1 rounded-full bg-rose-500 text-white text-[9px] font-bold flex items-center justify-center">{{ thongBao.chuaDoc }}</span>
            </button>
            <div v-if="moThongBao" class="absolute right-0 top-11 z-50 w-[min(20rem,calc(100vw-2rem))] rounded-xl bg-white text-slate-800 shadow-xl border border-slate-200 overflow-hidden">
              <div class="px-4 py-3 flex items-center justify-between border-b border-slate-100"><span class="font-semibold text-sm">Thông báo</span><button class="text-xs text-brand-600" @click="docTatCa">Đọc tất cả</button></div>
              <div v-if="!thongBao.danhSach.length" class="p-5 text-sm text-slate-400">Chưa có thông báo.</div>
              <button v-for="item in thongBao.danhSach.slice(0, 5)" :key="item.id" class="w-full text-left px-4 py-3 border-b border-slate-100 hover:bg-slate-50" :class="item.da_doc ? '' : 'bg-brand-50/60'" @click="doc(item)"><div class="text-sm font-semibold">{{ item.tieu_de }}</div><div class="text-xs text-slate-500 mt-0.5">{{ item.noi_dung }}</div></button>
            </div>
          </div>
          <router-link :to="{ name: 'ho-so' }" class="flex items-center gap-2 px-2 py-1.5 rounded-lg hover:bg-slate-100">
            <span class="w-7 h-7 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-semibold text-xs">{{ chuCai(auth.user?.ho_ten) }}</span><span class="hidden sm:block text-sm font-medium text-slate-700 max-w-[140px] truncate">{{ auth.user?.ho_ten }}</span>
          </router-link>
        </div>
      </header>

      <div v-if="moThongBao" class="fixed inset-0 z-30" @click="moThongBao = false"></div>
      <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-7"><div class="max-w-7xl mx-auto"><slot /></div></main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'
import { useThongBaoStore } from '../stores/thong-bao'

export default {
  name: 'admin-layout',
  data() {
    return {
      thuGon: false, moSidebar: false, moThongBao: false,
      menuAdmin: [
        { ten: 'Tổng quan', route: 'admin-dashboard', icon: 'fa-solid fa-house' },
        { ten: 'Tài khoản', route: 'admin-tai-khoan', icon: 'fa-solid fa-users' },
        { ten: 'Môn học', route: 'admin-mon-hoc', icon: 'fa-solid fa-book' },
        { ten: 'Lớp học phần', route: 'admin-lop-hoc', icon: 'fa-solid fa-chalkboard' },
        { ten: 'Phân công GV', route: 'admin-phan-cong', icon: 'fa-solid fa-user-tie' },
        { ten: 'Sinh viên', route: 'admin-sinh-vien', icon: 'fa-solid fa-user-graduate' },
        { ten: 'Báo cáo & Thống kê', route: 'admin-bao-cao', icon: 'fa-solid fa-chart-pie' },
      ],
      menuGV: [
        { ten: 'Lịch giảng dạy', route: 'giang-vien-trang-chu', icon: 'fa-solid fa-calendar-week' },
        { ten: 'Quản lý điểm danh', route: 'gv-quan-ly-diem-danh', icon: 'fa-solid fa-clipboard-user' },
        { ten: 'Nhập điểm', route: 'gv-quan-ly-diem', icon: 'fa-solid fa-pen-to-square' },
        { ten: 'Duyệt xin phép vắng', route: 'gv-xin-phep', icon: 'fa-regular fa-envelope' },
      ],
    }
  },
  computed: {
    auth() { return useAuthStore() }, thongBao() { return useThongBaoStore() },
    menuHienTai() { return this.auth.laAdmin ? this.menuAdmin : this.menuGV },
    tieuDe() { return [...this.menuAdmin, ...this.menuGV].find((m) => m.route === this.$route.name)?.ten || 'Hồ sơ cá nhân' },
  },
  mounted() { this.thongBao.khoiTao(this.auth.user, this.auth.token) },
  methods: {
    async doc(item) { if (!item.da_doc) await this.thongBao.danhDauDaDoc(item.id) }, async docTatCa() { await this.thongBao.danhDauTatCaDaDoc() },
    chuCai(ten) { return (ten || '?').split(' ').filter(Boolean).slice(-2).map((tu) => tu[0]).join('').toUpperCase() },
    async dangXuat() { await this.auth.dangXuat(); this.$router.push({ name: 'dang-nhap' }) },
  },
}
</script>
