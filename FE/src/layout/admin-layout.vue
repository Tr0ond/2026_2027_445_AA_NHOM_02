<template>
  <div class="flex min-h-screen bg-slate-50">
    <div v-if="moSidebar" class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" @click="moSidebar = false"></div>

    <aside
      class="fixed inset-y-0 left-0 z-50 flex w-64 shrink-0 flex-col border-r border-slate-200 bg-white transition-transform lg:relative lg:z-auto lg:w-56"
      :class="moSidebar ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    >
      <div class="flex h-14 items-center gap-2.5 border-b border-slate-100 px-4">
        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-teal-600 text-white">
          <i class="fa-solid fa-graduation-cap text-sm"></i>
        </span>
        <span class="text-sm font-bold text-slate-900">EduPortal</span>
        <button class="ml-auto h-8 w-8 rounded-lg text-slate-400 lg:hidden" @click="moSidebar = false">
          <i class="fa-solid fa-xmark"></i>
        </button>
      </div>

      <nav class="flex-1 space-y-1 px-2 py-3">
        <p class="px-3 pb-2 pt-1 text-[10px] font-semibold uppercase tracking-wider text-slate-400">Giảng dạy</p>
        <router-link
          v-for="item in menu"
          :key="item.route"
          :to="{ name: item.route }"
          class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
          active-class="!bg-teal-50 !text-teal-700"
          @click="moSidebar = false"
        >
          <i :class="item.icon" class="w-4 text-center"></i>{{ item.ten }}
        </router-link>
      </nav>

      <div class="border-t border-slate-100 p-3">
        <p class="truncate text-xs font-semibold text-slate-800">{{ auth.hoTen }}</p>
        <button class="mt-2 w-full rounded-lg px-3 py-2 text-left text-sm text-rose-600 hover:bg-rose-50" @click="dangXuat">
          <i class="fa-solid fa-arrow-right-from-bracket mr-2"></i>Đăng xuất
        </button>
      </div>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
      <header class="flex h-14 items-center gap-3 border-b border-slate-200 bg-white px-4 sm:px-6">
        <button class="h-9 w-9 rounded-lg text-slate-500 hover:bg-slate-100 lg:hidden" @click="moSidebar = true">
          <i class="fa-solid fa-bars"></i>
        </button>
        <div>
          <p class="text-[11px] leading-none text-slate-400">Khu vực giảng viên</p>
          <h1 class="mt-1 text-sm font-semibold text-slate-900">{{ tieuDe }}</h1>
        </div>
      </header>
      <main class="flex-1 p-4 sm:p-6 lg:p-7">
        <div class="mx-auto max-w-7xl"><slot /></div>
      </main>
    </div>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'

export default {
  name: 'admin-layout',
  data() {
    return {
      moSidebar: false,
      menu: [
        { ten: 'Lịch giảng dạy', route: 'giang-vien-trang-chu', icon: 'fa-solid fa-calendar-week' },
        { ten: 'Quản lý điểm danh', route: 'gv-quan-ly-diem-danh', icon: 'fa-solid fa-clipboard-user' },
        { ten: 'Quản lý điểm', route: 'gv-quan-ly-diem', icon: 'fa-solid fa-pen-to-square' },
        { ten: 'Đơn xin phép', route: 'gv-quan-ly-don-xin-phep', icon: 'fa-solid fa-file-lines' },
      ],
    }
  },
  computed: {
    auth() { return useAuthStore() },
    tieuDe() { return this.menu.find((item) => item.route === this.$route.name)?.ten || 'Giảng viên' },
  },
  methods: {
    async dangXuat() {
      await this.auth.dangXuat()
      this.$router.replace({ name: 'dang-nhap' })
    },
  },
}
</script>

