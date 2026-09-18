<template>
  <div class="min-h-screen flex items-center justify-center p-6 bg-slate-950">
    <section class="w-full max-w-md rounded-3xl bg-white p-7 sm:p-9 shadow-2xl">
      <router-link :to="{ name: 'dang-nhap' }" class="inline-flex items-center gap-2 text-sm text-slate-500 hover:text-brand-600 mb-8">
        <i class="fa-solid fa-arrow-left text-xs"></i>Đăng nhập
      </router-link>
      <div class="w-12 h-12 rounded-2xl bg-brand-50 text-brand-600 flex items-center justify-center mb-5">
        <i class="fa-solid fa-shield-halved text-lg"></i>
      </div>
      <h1 class="text-2xl font-bold text-slate-900">Đặt lại mật khẩu</h1>
      <p class="text-sm text-slate-500 mt-2">Tạo mật khẩu mới cho tài khoản <strong class="text-slate-700">{{ email || 'của bạn' }}</strong>.</p>

      <div v-if="thanhCong" class="mt-6">
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
          <i class="fa-solid fa-circle-check mr-2"></i>{{ thanhCong }}
        </div>
        <router-link :to="{ name: 'dang-nhap' }" class="nut-chinh w-full !py-3 font-semibold mt-6 inline-flex justify-center">
          <i class="fa-solid fa-arrow-right-to-bracket"></i>Đăng nhập
        </router-link>
      </div>
      <form v-else class="mt-7 space-y-5" @submit.prevent="datLai">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5" for="mat-khau-moi">Mật khẩu mới</label>
          <input id="mat-khau-moi" v-model="matKhau" type="password" class="o-nhap" minlength="8" required autocomplete="new-password">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1.5" for="xac-nhan">Xác nhận mật khẩu</label>
          <input id="xac-nhan" v-model="xacNhan" type="password" class="o-nhap" minlength="8" required autocomplete="new-password">
        </div>
        <div v-if="loi" class="rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2.5 text-sm">{{ loi }}</div>
        <button class="nut-chinh w-full !py-3 font-semibold" :disabled="dangGui || !token">
          <i :class="dangGui ? 'fa-solid fa-circle-notch fa-spin' : 'fa-solid fa-check'"></i>
          {{ dangGui ? 'Đang cập nhật...' : 'Lưu mật khẩu mới' }}
        </button>
      </form>
    </section>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'dat-lai-mat-khau',
  data() {
    return {
      email: this.$route.query.email || '',
      token: this.$route.query.token || '',
      matKhau: '',
      xacNhan: '',
      dangGui: false,
      loi: this.$route.query.token ? '' : 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.',
      thanhCong: '',
    }
  },
  methods: {
    async datLai() {
      this.loi = ''
      if (this.matKhau !== this.xacNhan) {
        this.loi = 'Mật khẩu xác nhận không khớp.'
        return
      }
      this.dangGui = true
      try {
        const { data } = await api.post('/dat-lai-mat-khau', {
          token: this.token,
          email: this.email,
          password: this.matKhau,
          password_confirmation: this.xacNhan,
        })
        this.thanhCong = data.message
      } catch (e) {
        this.loi = Object.values(e.response?.data?.errors || {})[0]?.[0] || e.response?.data?.message || 'Không thể đặt lại mật khẩu.'
      } finally {
        this.dangGui = false
      }
    },
  },
}
</script>
