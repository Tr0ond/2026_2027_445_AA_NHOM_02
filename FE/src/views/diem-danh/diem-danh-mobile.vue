<template>
  <div class="min-h-screen bg-slate-950 text-white flex flex-col">
    <header class="h-14 px-5 flex items-center gap-3 border-b border-slate-800">
      <span class="w-8 h-8 bg-brand-600 rounded-lg flex items-center justify-center"><i class="fa-solid fa-graduation-cap text-sm"></i></span>
      <div><p class="font-semibold text-sm">EduPortal</p><p class="text-[10px] text-slate-500">Điểm danh QR an toàn</p></div>
      <span class="ml-auto inline-flex items-center gap-1.5 text-xs text-emerald-400"><span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span>Kết nối bảo mật</span>
    </header>
    <main class="flex-1 flex items-center justify-center p-5">
    <div class="w-full max-w-sm bg-slate-900 rounded-3xl border border-slate-800 shadow-2xl p-8 text-center">
      <!-- Đang xử lý -->
      <template v-if="trangThai === 'dang_xu_ly'">
        <div class="w-20 h-20 mx-auto rounded-3xl bg-brand-500/15 border border-brand-500/30 flex items-center justify-center"><div class="w-10 h-10 border-4 border-brand-300/30 border-t-brand-400 rounded-full animate-spin"></div></div>
        <h5 class="mt-5 font-bold text-white">Đang xác thực mã QR...</h5>
        <p class="text-sm text-slate-400 mt-1">Vui lòng đợi trong giây lát</p>
      </template>

      <!-- Thành công -->
      <template v-else-if="trangThai === 'thanh_cong'">
        <div class="w-24 h-24 rounded-3xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center mx-auto text-emerald-400 text-5xl"><i class="fa-solid fa-circle-check"></i></div>
        <h4 class="mt-5 text-xl font-bold text-white">Điểm danh thành công!</h4>
        <p class="text-slate-300 mt-1" v-if="ketQua.mon_hoc">{{ ketQua.mon_hoc }}</p>
        <div class="mt-5 rounded-2xl bg-slate-800 border border-slate-700 px-4 py-3 text-sm text-slate-400">Thời gian <strong class="block mt-1 text-slate-200">{{ ketQua.thoi_gian_diem_danh }}</strong></div>
      </template>

      <!-- Đã điểm danh trước đó -->
      <template v-else-if="trangThai === 'da_diem_danh'">
        <div class="w-24 h-24 rounded-3xl bg-sky-500/20 border border-sky-500/30 flex items-center justify-center mx-auto text-sky-400 text-4xl"><i class="fa-solid fa-clock-rotate-left"></i></div>
        <h5 class="mt-5 font-bold text-white">{{ ketQua.message }}</h5>
        <p class="text-sm text-slate-400 mt-2" v-if="ketQua.thoi_gian_diem_danh">Lúc {{ ketQua.thoi_gian_diem_danh }}</p>
      </template>

      <!-- Lỗi -->
      <template v-else>
        <div class="w-24 h-24 rounded-3xl bg-rose-500/20 border border-rose-500/30 flex items-center justify-center mx-auto text-rose-400 text-4xl"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h5 class="mt-5 font-bold text-white">Không thể điểm danh</h5>
        <p class="text-slate-400 mt-2">{{ ketQua.message || 'Mã QR không hợp lệ hoặc phiên đã hết hạn.' }}</p>
      </template>

      <div v-if="!daDangNhap" class="mt-5 rounded-xl bg-amber-500/10 border border-amber-500/30 text-amber-300 text-sm px-4 py-3">
        Bạn chưa đăng nhập trên thiết bị này.
        <router-link :to="{ name: 'dang-nhap' }" class="font-semibold underline">Đăng nhập trước</router-link>
        rồi quét lại mã QR.
      </div>
    </div></main>
  </div>
</template>

<script>
import api from '../../utils/axios'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'diem-danh-mobile',
  data() {
    return {
      trangThai: 'dang_xu_ly', // dang_xu_ly | thanh_cong | da_diem_danh | loi
      ketQua: {},
    }
  },
  computed: {
    daDangNhap() {
      return useAuthStore().daDangNhap
    },
  },
  async created() {
    const auth = useAuthStore()
    if (!auth.daDangNhap) {
      sessionStorage.setItem('url_qr_sau_dang_nhap', this.$route.fullPath)
      this.trangThai = 'loi'
      this.ketQua = { message: 'Vui lòng đăng nhập tài khoản sinh viên để điểm danh.' }
      return
    }

    try {
      const { data } = await api.post(`/sinh-vien/diem-danh/qr/${this.$route.params.maQr}`)
      if (data.da_diem_danh_truoc_do) {
        this.trangThai = 'da_diem_danh'
      } else {
        this.trangThai = 'thanh_cong'
      }
      this.ketQua = data
    } catch (e) {
      this.trangThai = 'loi'
      this.ketQua = { message: e.response?.data?.message || 'Có lỗi xảy ra khi điểm danh.' }
    }
  },
}
</script>
