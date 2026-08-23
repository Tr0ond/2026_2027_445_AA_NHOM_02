<template>
  <main class="min-h-screen bg-slate-950 px-4 py-12 text-white">
    <div class="mx-auto max-w-lg rounded-2xl border border-slate-700 bg-slate-900 p-6 shadow-2xl">
      <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-xl bg-amber-400/10 text-amber-300">
        <i class="fa-solid fa-screwdriver-wrench"></i>
      </div>
      <h1 class="text-xl font-bold">Khu vực quản trị chưa thuộc bản MVP</h1>
      <p class="mt-2 text-sm leading-6 text-slate-300">
        Bản hiện tại ưu tiên đăng nhập, lịch học, đăng ký lớp và điểm danh QR.
      </p>
      <button class="mt-6 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900" @click="dangXuat">
        Đăng xuất
      </button>
    </div>
  </main>
</template>

<script>
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'mvp-admin',
  methods: {
    async dangXuat() {
      await useAuthStore().dangXuat()
      this.$router.replace({ name: 'dang-nhap' })
    },
  },
}
</script>

