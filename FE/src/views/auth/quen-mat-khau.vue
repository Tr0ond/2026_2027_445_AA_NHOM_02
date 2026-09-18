<template>
  <div class="min-h-screen flex items-center justify-center p-6 bg-slate-950">
    <section class="w-full max-w-md rounded-3xl bg-white p-7 sm:p-9 shadow-2xl">
      <router-link :to="{ name: 'dang-nhap' }" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600 mb-8">
        <i class="fa-solid fa-arrow-left text-xs"></i>Đăng nhập
      </router-link>
      <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-5">
        <i class="fa-solid fa-key text-lg"></i>
      </div>
      <h1 class="text-2xl font-bold text-slate-900">Quên mật khẩu?</h1>
      <p class="text-sm text-slate-500 mt-2 leading-relaxed">Nhập email tài khoản để nhận liên kết đặt lại mật khẩu.</p>

      <div v-if="thanhCong" class="mt-6 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
        <i class="fa-solid fa-circle-check mr-2"></i>{{ thanhCong }}
      </div>
      <form v-else class="mt-7 space-y-5" @submit.prevent="guiYeuCau">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5" for="email">Email</label>
          <div class="relative">
            <i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
            <input id="email" v-model="email" type="email" class="o-nhap !pl-10" :class="loi ? '!border-rose-300 !bg-rose-50' : ''" placeholder="Nhập email tài khoản" required autocomplete="email">
          </div>
          <p v-if="loi" class="text-xs text-rose-500 mt-1">{{ loi }}</p>
        </div>
        <button class="nut-chinh w-full !py-3 font-semibold" :disabled="dangGui">
          <i :class="dangGui ? 'fa-solid fa-circle-notch fa-spin' : 'fa-solid fa-paper-plane'"></i>
          {{ dangGui ? 'Đang gửi...' : 'Gửi liên kết đặt lại' }}
        </button>
      </form>
      <router-link v-if="thanhCong" :to="{ name: 'dang-nhap' }" class="nut-chinh w-full !py-3 font-semibold mt-6 inline-flex justify-center">
        <i class="fa-solid fa-arrow-right-to-bracket"></i>Quay lại đăng nhập
      </router-link>
    </section>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'quen-mat-khau',
  data() {
    return { email: '', dangGui: false, loi: '', thanhCong: '' }
  },
  methods: {
    async guiYeuCau() {
      this.dangGui = true
      this.loi = ''
      try {
        const { data } = await api.post('/quen-mat-khau', { email: this.email })
        this.thanhCong = data.message
      } catch (e) {
        this.loi = Object.values(e.response?.data?.errors || {})[0]?.[0] || e.response?.data?.message || 'Không thể gửi yêu cầu. Vui lòng thử lại.'
      } finally {
        this.dangGui = false
      }
    },
  },
}
</script>
