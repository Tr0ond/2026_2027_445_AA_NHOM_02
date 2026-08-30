<template>
  <div class="flex min-h-screen flex-col bg-slate-50">
    <header class="sticky top-0 z-40 border-b border-slate-200 bg-white">
      <div class="mx-auto flex h-14 max-w-7xl items-center gap-4 px-4 sm:px-6">
        <router-link :to="{ name: 'sinh-vien-trang-chu' }" class="flex shrink-0 items-center gap-2">
          <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-brand-600 text-white">
            <i class="fa-solid fa-graduation-cap text-sm"></i>
          </span>
          <span class="hidden font-bold text-slate-900 sm:block">EduPortal</span>
        </router-link>

        <nav class="ml-3 hidden items-center gap-1 md:flex">
          <router-link
            v-for="item in menu"
            :key="item.route"
            :to="{ name: item.route }"
            class="flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-slate-600 transition-colors hover:bg-slate-50 hover:text-slate-900"
            active-class="!bg-brand-50 !text-brand-700"
          >
            <i :class="item.icon" class="text-xs"></i>{{ item.ten }}
          </router-link>
        </nav>

        <div class="ml-auto flex items-center gap-2">
          <div class="hidden text-right sm:block">
            <p class="max-w-44 truncate text-sm font-semibold text-slate-800">{{ auth.hoTen }}</p>
            <p class="text-xs text-slate-500">Sinh viên</p>
          </div>
          <button class="h-9 rounded-lg px-3 text-sm font-medium text-rose-600 hover:bg-rose-50" @click="dangXuat">
            <i class="fa-solid fa-arrow-right-from-bracket sm:mr-1.5"></i>
            <span class="hidden sm:inline">Đăng xuất</span>
          </button>
          <button class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-500 hover:bg-slate-100 md:hidden" @click="moMenu = !moMenu">
            <i class="fa-solid" :class="moMenu ? 'fa-xmark' : 'fa-bars'"></i>
          </button>
        </div>
      </div>

      <nav v-if="moMenu" class="space-y-1 border-t border-slate-100 px-4 py-3 md:hidden">
        <router-link
          v-for="item in menu"
          :key="item.route"
          :to="{ name: item.route }"
          class="flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-600"
          active-class="!bg-brand-50 !text-brand-700"
          @click="moMenu = false"
        >
          <i :class="item.icon" class="w-4 text-center"></i>{{ item.ten }}
        </router-link>
      </nav>
    </header>

    <main class="flex-1 py-6 sm:py-8">
      <div class="mx-auto max-w-7xl px-4 sm:px-6"><slot /></div>
    </main>
    <footer class="border-t border-slate-200 bg-white py-4 text-center text-xs text-slate-400">
      © 2026 EduPortal · Bản chức năng cốt lõi
    </footer>
  </div>
</template>

<script>
import { useAuthStore } from '../stores/auth'

export default {
  name: 'default-layout',
  data() {
    return {
      moMenu: false,
      menu: [
        { ten: 'Lịch học', route: 'sinh-vien-lich-hoc', icon: 'fa-solid fa-calendar-week' },
        { ten: 'Điểm số', route: 'diem-cua-toi', icon: 'fa-solid fa-chart-bar' },
        { ten: 'Đăng ký lớp', route: 'dang-ky-lop', icon: 'fa-solid fa-book-open' },
        { ten: 'Điểm danh', route: 'lich-su-diem-danh', icon: 'fa-solid fa-clipboard-check' },
        { ten: 'Xin phép vắng', route: 'xin-phep-vang', icon: 'fa-solid fa-file-pen' },
      ],
    }
  },
  computed: {
    auth() { return useAuthStore() },
  },
  methods: {
    async dangXuat() {
      await this.auth.dangXuat()
      this.$router.replace({ name: 'dang-nhap' })
    },
  },
}
</script>

