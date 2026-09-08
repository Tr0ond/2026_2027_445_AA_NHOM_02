<template>
  <div>
    <div class="mb-5">
      <div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'admin-dashboard' }" class="hover:text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Tài khoản</span></div>
      <h1 class="text-2xl font-bold text-slate-900">Quản lý tài khoản</h1>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
      <div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.giaTri }}</p></div></div>
    </div>

    <div class="the p-3 mb-4 flex flex-wrap items-center gap-2">
      <div class="relative flex-1 min-w-[220px]"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Tìm kiếm theo tên, email hoặc mã số..." @input="tai(1)" /></div>
        <select v-model="locVaiTro" class="o-nhap !w-auto min-w-[150px]" @change="tai(1)">
          <option value="">Tất cả vai trò</option>
          <option value="admin">Quản trị</option>
          <option value="giang_vien">Giảng viên</option>
          <option value="sinh_vien">Sinh viên</option>
        </select>
      <button class="nut-phu text-sm"><i class="fa-solid fa-download"></i>Xuất dữ liệu</button>
      <button class="nut-chinh text-sm" @click="moThem()"><i class="fa-solid fa-plus"></i>Thêm tài khoản</button>
    </div>

    <div class="the">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr>
              <th>Họ và tên</th><th>Mã số</th><th>Email</th><th>Vai trò</th><th>Trạng thái</th><th class="!text-right"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tk in danhSach" :key="tk.id">
              <td><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-xs font-bold shrink-0" :class="tk.vai_tro === 'giang_vien' ? 'bg-teal-500' : tk.vai_tro === 'admin' ? 'bg-slate-500' : 'bg-indigo-400'">{{ chuCai(tk.ho_ten) }}</div><span class="font-medium text-slate-800 whitespace-nowrap">{{ tk.ho_ten }}</span></div></td>
              <td class="font-mono text-xs text-slate-600">{{ tk.ma_dinh_danh || '—' }}</td>
              <td class="text-slate-500 text-xs">{{ tk.email }}</td>
              <td><span class="nhan border" :class="mauVaiTro(tk.vai_tro)">{{ tenVaiTro(tk.vai_tro) }}</span></td>
              <td>
                <span class="nhan border" :class="tk.trang_thai === 'hoat_dong' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-slate-100 text-slate-600 border-slate-200'">
                  {{ tk.trang_thai === 'hoat_dong' ? 'Hoạt động' : 'Bị khóa' }}
                </span>
              </td>
              <td class="!text-right">
                <div class="flex justify-end gap-1.5">
                  <button class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" title="Sửa" @click="moSua(tk)">
                    <i class="fa-solid fa-pen text-xs"></i>
                  </button>
                  <button class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100"
                    :title="tk.trang_thai === 'hoat_dong' ? 'Khóa' : 'Mở khóa'" @click="doiTrangThai(tk)">
                    <i :class="tk.trang_thai === 'hoat_dong' ? 'fa-solid fa-lock' : 'fa-solid fa-unlock'" class="text-xs"></i>
                  </button>
                  <button class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100" title="Xóa" @click="xoa(tk)">
                    <i class="fa-solid fa-trash text-xs"></i>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!danhSach.length"><td colspan="6" class="!py-12 text-center text-slate-400"><i class="fa-solid fa-users text-3xl text-slate-200 block mb-2"></i>Không tìm thấy tài khoản phù hợp.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 flex items-center justify-between border-t border-slate-100">
        <span class="text-xs text-slate-500">Hiển thị trang {{ trangHienTai }} / {{ trangCuoi }} · {{ tongTaiKhoan }} tài khoản</span>
        <div class="flex gap-2">
          <button class="nut-phu text-sm" :disabled="trangHienTai <= 1" @click="tai(trangHienTai - 1)">Trước</button>
          <button class="nut-phu text-sm" :disabled="trangHienTai >= trangCuoi" @click="tai(trangHienTai + 1)">Sau</button>
        </div>
      </div>
    </div>

    <!-- Modal thêm/sửa (Tailwind) -->
    <div v-if="moModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="moModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h5 class="font-bold text-slate-800 mb-4">{{ dangSua ? 'Sửa tài khoản' : 'Thêm tài khoản' }}</h5>
        <form @submit.prevent="luu" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Họ tên</label>
            <input v-model="form.ho_ten" class="o-nhap" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
            <input v-model="form.email" type="email" class="o-nhap" required />
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Vai trò</label>
              <select v-model="form.vai_tro" class="o-nhap">
                <option value="sinh_vien">Sinh viên</option>
                <option value="giang_vien">Giảng viên</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <div v-if="!dangSua">
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Mã định danh</label>
              <input v-model="form.ma_dinh_danh" class="o-nhap" placeholder="SV0001 / GV001" />
            </div>
          </div>
          <div v-if="form.vai_tro === 'giang_vien' && !dangSua">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Bộ môn</label>
            <input v-model="form.bo_mon" class="o-nhap" />
          </div>
          <div v-if="form.vai_tro === 'sinh_vien' && !dangSua" class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Lớp danh nghĩa</label>
              <input v-model="form.lop_danh_nghia" class="o-nhap" placeholder="CNTT-K48A" />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Khoa</label>
              <input v-model="form.khoa" class="o-nhap" placeholder="Công nghệ thông tin" />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">{{ dangSua ? 'Mật khẩu (bỏ trống nếu không đổi)' : 'Mật khẩu' }}</label>
            <input v-model="form.mat_khau" type="password" class="o-nhap" :required="!dangSua" />
          </div>
          <div v-if="dangSua">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Trạng thái</label>
            <select v-model="form.trang_thai" class="o-nhap">
              <option value="hoat_dong">Hoạt động</option>
              <option value="khoa">Khóa</option>
            </select>
          </div>
          <div v-if="loiForm" class="rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2 text-sm">{{ loiForm }}</div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="nut-phu" @click="moModal = false">Hủy</button>
            <button class="nut-chinh" :disabled="dangLuu">Lưu</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'admin-tai-khoan',
  data() {
    return {
      danhSach: [],
      trangHienTai: 1,
      trangCuoi: 1,
      tongTaiKhoan: 0,
      tuKhoa: '',
      locVaiTro: '',
      moModal: false,
      dangSua: false,
      dangLuu: false,
      loiForm: '',
      form: this.formTrong(),
    }
  },
  async created() {
    await this.tai()
  },
  computed: {
    thongKe() { return [
      { icon: 'fa-solid fa-users', nen: 'bg-indigo-50 text-indigo-600', nhan: 'Tổng tài khoản', giaTri: this.tongTaiKhoan },
      { icon: 'fa-solid fa-user-graduate', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Sinh viên', giaTri: this.danhSach.filter((x) => x.vai_tro === 'sinh_vien').length },
      { icon: 'fa-solid fa-chalkboard-user', nen: 'bg-teal-50 text-teal-600', nhan: 'Giảng viên', giaTri: this.danhSach.filter((x) => x.vai_tro === 'giang_vien').length },
      { icon: 'fa-solid fa-circle-pause', nen: 'bg-slate-100 text-slate-500', nhan: 'Tạm dừng', giaTri: this.danhSach.filter((x) => x.trang_thai !== 'hoat_dong').length },
    ] },
  },
  methods: {
    formTrong() {
      return {
        id: null, ho_ten: '', email: '', mat_khau: '', vai_tro: 'sinh_vien',
        trang_thai: 'hoat_dong', ma_dinh_danh: '', bo_mon: '', lop_danh_nghia: '', khoa: '',
      }
    },
    async tai(trang = 1) {
      const { data } = await api.get('/admin/tai-khoan', {
        params: { page: trang, tu_khoa: this.tuKhoa || null, vai_tro: this.locVaiTro || null },
      })
      this.danhSach = data.data
      this.trangHienTai = data.current_page
      this.trangCuoi = data.last_page
      this.tongTaiKhoan = data.total || this.danhSach.length
    },
    moThem() {
      this.dangSua = false
      this.form = this.formTrong()
      this.loiForm = ''
      this.moModal = true
    },
    moSua(tk) {
      this.dangSua = true
      this.loiForm = ''
      this.form = {
        id: tk.id, ho_ten: tk.ho_ten, email: tk.email, mat_khau: '', vai_tro: tk.vai_tro,
        trang_thai: tk.trang_thai, ma_dinh_danh: tk.ma_dinh_danh, bo_mon: '', lop_danh_nghia: '', khoa: '',
      }
      this.moModal = true
    },
    async luu() {
      this.dangLuu = true
      this.loiForm = ''
      try {
        if (this.dangSua) {
          await api.put(`/admin/tai-khoan/${this.form.id}`, this.form)
        } else {
          await api.post('/admin/tai-khoan', this.form)
        }
        this.moModal = false
        await this.tai(this.trangHienTai)
      } catch (e) {
        this.loiForm = Object.values(e.response?.data?.errors || {})[0]?.[0] || e.response?.data?.message || 'Lưu thất bại.'
      } finally {
        this.dangLuu = false
      }
    },
    async doiTrangThai(tk) {
      await api.post(`/admin/tai-khoan/${tk.id}/doi-trang-thai`)
      await this.tai(this.trangHienTai)
    },
    async xoa(tk) {
      if (!confirm(`Xóa tài khoản "${tk.ho_ten}"?`)) return
      try {
        await api.delete(`/admin/tai-khoan/${tk.id}`)
        await this.tai(this.trangHienTai)
      } catch (e) {
        alert(e.response?.data?.message || 'Xóa thất bại.')
      }
    },
    chuCai(ten) { return (ten || '?').trim().split(/\s+/).slice(-2).map((x) => x[0]).join('').toUpperCase() },
    tenVaiTro(v) { return { admin: 'Quản trị', giang_vien: 'Giảng viên', sinh_vien: 'Sinh viên' }[v] || v },
    mauVaiTro(v) { return { admin: 'bg-slate-100 text-slate-600 border-slate-200', giang_vien: 'bg-blue-50 text-blue-700 border-blue-200', sinh_vien: 'bg-emerald-50 text-emerald-700 border-emerald-200' }[v] || 'bg-slate-100 text-slate-600 border-slate-200' },
  },
}
</script>
