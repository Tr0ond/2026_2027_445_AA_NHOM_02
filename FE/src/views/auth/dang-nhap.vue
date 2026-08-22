<template>
  <div class="min-h-screen flex bg-white">
    <section class="hidden lg:flex flex-1 flex-col p-12 relative overflow-hidden bg-gradient-to-br from-brand-600 via-brand-700 to-violet-800">
      <div class="absolute -top-20 -left-20 w-96 h-96 bg-white/5 rounded-full"></div>
      <div class="absolute bottom-10 right-10 w-64 h-64 bg-white/5 rounded-full"></div>
      <div class="absolute top-1/2 right-0 w-48 h-48 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2"></div>

      <div class="relative z-10 flex items-center gap-3">
        <span class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm"><i class="fa-solid fa-graduation-cap text-white text-lg"></i></span>
        <span class="text-2xl font-bold text-white tracking-tight">EduPortal</span>
      </div>

      <div class="relative z-10 flex-1 flex items-center justify-center">
        <div class="max-w-sm w-full">
          <div class="bg-white/10 backdrop-blur-sm rounded-3xl p-8 border border-white/20 mb-6">
            <div class="grid grid-cols-2 gap-4 mb-6">
              <div class="col-span-2 bg-white/15 rounded-2xl p-4">
                <div class="flex items-center gap-3 mb-3">
                  <span class="w-8 h-8 bg-white/20 rounded-lg flex items-center justify-center"><i class="fa-solid fa-calendar-days text-white text-xs"></i></span>
                  <div><p class="text-white/60 text-xs">Buổi học tiếp theo</p><p class="text-white font-semibold text-sm">Lập trình C++</p></div>
                  <span class="ml-auto bg-emerald-400/30 text-emerald-200 text-xs px-2 py-0.5 rounded-full border border-emerald-400/40">Trực tuyến</span>
                </div>
                <div class="flex items-center gap-4 text-white/70 text-xs"><span><i class="fa-regular fa-clock mr-1"></i>08:00 – 09:30</span><span><i class="fa-solid fa-location-dot mr-1"></i>P.301-A</span></div>
              </div>
              <div class="bg-white/15 rounded-2xl p-4"><p class="text-white/60 text-xs mb-1">Chuyên cần</p><p class="text-white text-2xl font-bold">94%</p><div class="mt-2 h-1.5 bg-white/20 rounded-full overflow-hidden"><div class="h-full w-[94%] bg-emerald-400 rounded-full"></div></div></div>
              <div class="bg-white/15 rounded-2xl p-4"><p class="text-white/60 text-xs mb-1">Môn học</p><p class="text-white text-2xl font-bold">6</p><p class="text-white/50 text-xs mt-1">Học kỳ hiện tại</p></div>
            </div>
            <div class="flex items-center gap-3 bg-white/10 rounded-xl p-3 border border-white/15"><span class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center"><i class="fa-solid fa-qrcode text-white text-lg"></i></span><div><p class="text-white font-medium text-sm">Điểm danh QR</p><p class="text-white/60 text-xs">Quét mã trong lớp học trực tuyến</p></div></div>
          </div>
          <h1 class="text-white text-2xl font-bold leading-tight">Hệ thống quản lý<br><span class="text-indigo-200">học tập toàn diện</span></h1>
          <p class="text-white/60 text-sm mt-2 leading-relaxed">Lịch học, điểm danh QR, điểm số và phòng học trực tuyến — tất cả trong một nền tảng.</p>
        </div>
      </div>
    </section>

    <section class="flex-1 lg:max-w-md xl:max-w-lg flex items-center justify-center p-6 sm:p-10 bg-white">
      <div class="w-full max-w-sm">
        <div class="flex items-center gap-2.5 mb-8 lg:hidden"><span class="w-9 h-9 bg-brand-600 rounded-xl flex items-center justify-center"><i class="fa-solid fa-graduation-cap text-white"></i></span><span class="text-xl font-bold text-slate-900">EduPortal</span></div>
        <div class="mb-8"><h2 class="text-2xl font-bold text-slate-900 mb-1">Chào mừng trở lại</h2><p class="text-slate-500 text-sm">Đăng nhập để truy cập hệ thống học tập</p></div>

        <form class="space-y-5" @submit.prevent="dangNhap">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <div class="relative"><i class="fa-solid fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i><input v-model="email" type="email" class="o-nhap !pl-10" :class="loi.email ? '!border-rose-300 !bg-rose-50' : ''" placeholder="Nhập email tài khoản" required></div>
            <p v-if="loi.email" class="text-xs text-rose-500 mt-1">{{ loi.email }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mật khẩu</label>
            <div class="relative"><i class="fa-solid fa-lock absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i><input v-model="matKhau" :type="hienMatKhau ? 'text' : 'password'" class="o-nhap !pl-10 !pr-10" :class="loi.mat_khau ? '!border-rose-300 !bg-rose-50' : ''" placeholder="Nhập mật khẩu" required><button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600" @click="hienMatKhau = !hienMatKhau"><i class="fa-solid text-sm" :class="hienMatKhau ? 'fa-eye-slash' : 'fa-eye'"></i></button></div>
            <p v-if="loi.mat_khau" class="text-xs text-rose-500 mt-1">{{ loi.mat_khau }}</p>
          </div>
          <div v-if="thongBaoLoi" class="flex items-start gap-2.5 px-3.5 py-2.5 bg-rose-50 border border-rose-200 rounded-xl text-rose-700 text-sm"><i class="fa-solid fa-circle-exclamation mt-0.5"></i><span>{{ thongBaoLoi }}</span></div>
          <div class="text-right"><button type="button" class="text-xs text-brand-600 hover:text-brand-700 font-medium">Quên mật khẩu?</button></div>
          <button class="nut-chinh w-full !py-3 font-semibold" :disabled="dangGui"><i v-if="dangGui" class="fa-solid fa-circle-notch fa-spin"></i><i v-else class="fa-solid fa-arrow-right-to-bracket"></i>{{ dangGui ? 'Đang xử lý...' : 'Đăng nhập' }}</button>
        </form>

        <div class="mt-8 pt-6 border-t border-slate-100">
          <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Tài khoản demo</p>
          <div class="grid grid-cols-3 gap-2">
            <button v-for="tk in taiKhoanDemo" :key="tk.email" type="button" class="flex flex-col items-center gap-1.5 px-2 py-2.5 rounded-xl border text-xs font-medium transition-transform hover:-translate-y-0.5" :class="tk.mau" @click="dienTaiKhoan(tk)"><i :class="tk.icon" class="text-base"></i><span>{{ tk.ten }}</span></button>
          </div>
          <p class="text-xs text-slate-400 mt-2 text-center">Mật khẩu: <code class="font-mono text-slate-600">password</code></p>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import { useAuthStore } from '../../stores/auth'
import { trangChuTheoVaiTro } from '../../router'

export default {
  name: 'trang-dang-nhap',
  data() {
    return {
      email: '', matKhau: '', hienMatKhau: false, loi: {}, thongBaoLoi: '', dangGui: false,
      taiKhoanDemo: [
        { ten: 'Sinh viên', email: 'sv1@portal.test', icon: 'fa-solid fa-user-graduate', mau: 'bg-brand-50 border-brand-200 text-brand-700' },
        { ten: 'Giảng viên', email: 'minh.gv@portal.test', icon: 'fa-solid fa-chalkboard-user', mau: 'bg-teal-50 border-teal-200 text-teal-700' },
        { ten: 'Quản trị', email: 'admin@portal.test', icon: 'fa-solid fa-user-shield', mau: 'bg-slate-100 border-slate-300 text-slate-700' },
      ],
    }
  },
  methods: {
    dienTaiKhoan(tk) { this.email = tk.email; this.matKhau = 'password'; this.loi = {}; this.thongBaoLoi = '' },
    async dangNhap() {
      this.dangGui = true; this.loi = {}; this.thongBaoLoi = ''
      try {
        const taiKhoan = await this.auth.dangNhap(this.email, this.matKhau)
        const urlQr = sessionStorage.getItem('url_qr_sau_dang_nhap')
        if (urlQr && taiKhoan.vai_tro === 'sinh_vien') { sessionStorage.removeItem('url_qr_sau_dang_nhap'); this.$router.push(urlQr); return }
        this.$router.push(trangChuTheoVaiTro(taiKhoan.vai_tro))
      } catch (e) {
        if (e.response?.status === 422) { this.loi = e.response.data.errors || {}; this.thongBaoLoi = e.response.data.message || '' }
        else if (!e.response) this.thongBaoLoi = 'Không kết nối được máy chủ (port 8000). Hãy chạy START.bat để bật backend.'
        else this.thongBaoLoi = 'Máy chủ lỗi (HTTP ' + e.response.status + '). Vui lòng thử lại.'
      } finally { this.dangGui = false }
    },
  },
  computed: { auth() { return useAuthStore() } },
}
</script>
