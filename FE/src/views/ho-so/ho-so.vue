<template>
  <div class="max-w-4xl mx-auto">
    <div class="tieu-de-trang"><div><h4>Hồ sơ cá nhân</h4><p>Cập nhật thông tin liên hệ và bảo mật tài khoản.</p></div></div>
    <div class="space-y-6">
    <div class="the">
      <div class="the-tieu-de"><i class="fa-regular fa-id-card mr-2 text-brand-600"></i>Thông tin cá nhân</div>
      <div class="p-6 space-y-4">
        <div class="grid sm:grid-cols-2 gap-x-8 gap-y-3 text-sm">
          <div class="text-slate-500">Họ và tên</div>
          <div class="font-semibold text-slate-800">{{ hoSo.ho_ten }}</div>
          <div class="text-slate-500">Email</div>
          <div class="text-slate-800">{{ hoSo.email }}</div>
          <div class="text-slate-500">Vai trò</div>
          <div><span class="nhan bg-brand-100 text-brand-700 uppercase">{{ hoSo.vai_tro }}</span></div>
          <template v-if="hoSo.sinh_vien">
            <div class="text-slate-500">Mã sinh viên</div>
            <div class="text-slate-800">{{ hoSo.sinh_vien.ma_sinh_vien }} — Lớp {{ hoSo.sinh_vien.lop_danh_nghia }}</div>
          </template>
          <template v-if="hoSo.giang_vien">
            <div class="text-slate-500">Mã giảng viên</div>
            <div class="text-slate-800">{{ hoSo.giang_vien.ma_giang_vien }} — {{ hoSo.giang_vien.bo_mon }}</div>
          </template>
        </div>

        <hr class="border-slate-100" />

        <form @submit.prevent="luuHoSo" class="grid sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Họ và tên</label>
            <input v-model="form.ho_ten" class="o-nhap" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Số điện thoại</label>
            <input v-model="form.so_dien_thoai" class="o-nhap" />
          </div>
          <div class="sm:col-span-2">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Địa chỉ</label>
            <input v-model="form.dia_chi" class="o-nhap" />
          </div>
          <div class="sm:col-span-2 flex items-center gap-3">
            <button class="nut-chinh" :disabled="dangLuu"><i class="fa-regular fa-floppy-disk"></i>Lưu thay đổi</button>
            <span v-if="thongBao" :class="thanhCong ? 'text-emerald-600' : 'text-rose-600'" class="text-sm">{{ thongBao }}</span>
          </div>
        </form>
      </div>
    </div>

    <div class="the">
      <div class="the-tieu-de"><i class="fa-solid fa-key mr-2 text-amber-500"></i>Đổi mật khẩu</div>
      <div class="p-6">
        <form @submit.prevent="doiMatKhau" class="grid sm:grid-cols-3 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mật khẩu cũ</label>
            <input v-model="mk.mat_khau_cu" type="password" class="o-nhap" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mật khẩu mới</label>
            <input v-model="mk.mat_khau_moi" type="password" class="o-nhap" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Xác nhận mới</label>
            <input v-model="mk.mat_khau_moi_confirmation" type="password" class="o-nhap" required />
          </div>
          <div class="sm:col-span-3 flex items-center gap-3">
            <button class="nut-phu">Đổi mật khẩu</button>
            <span v-if="tbMK" :class="thanhCongMK ? 'text-emerald-600' : 'text-rose-600'" class="text-sm">{{ tbMK }}</span>
          </div>
        </form>
      </div>
    </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'trang-ho-so',
  data() {
    return {
      hoSo: {},
      form: { ho_ten: '', so_dien_thoai: '', dia_chi: '' },
      mk: { mat_khau_cu: '', mat_khau_moi: '', mat_khau_moi_confirmation: '' },
      thongBao: '',
      thanhCong: false,
      tbMK: '',
      thanhCongMK: false,
      dangLuu: false,
    }
  },
  async created() {
    const { data } = await api.get('/ho-so')
    this.hoSo = data.ho_so
    this.form = {
      ho_ten: this.hoSo.ho_ten || '',
      so_dien_thoai: this.hoSo.so_dien_thoai || '',
      dia_chi: this.hoSo.dia_chi || '',
    }
  },
  methods: {
    async luuHoSo() {
      this.dangLuu = true
      try {
        await api.put('/ho-so', this.form)
        this.thongBao = 'Đã lưu thông tin.'
        this.thanhCong = true
      } catch (e) {
        this.thongBao = e.response?.data?.message || 'Lưu thất bại.'
        this.thanhCong = false
      } finally {
        this.dangLuu = false
      }
    },
    async doiMatKhau() {
      try {
        await api.put('/doi-mat-khau', this.mk)
        this.tbMK = 'Đổi mật khẩu thành công.'
        this.thanhCongMK = true
        this.mk = { mat_khau_cu: '', mat_khau_moi: '', mat_khau_moi_confirmation: '' }
      } catch (e) {
        this.tbMK = Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Đổi mật khẩu thất bại.'
        this.thanhCongMK = false
      }
    },
  },
}
</script>
