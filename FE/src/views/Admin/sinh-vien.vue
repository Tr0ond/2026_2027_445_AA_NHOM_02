<template>
  <div>
    <div class="mb-5"><div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'admin-dashboard' }" class="hover:text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Sinh viên</span></div><h1 class="text-2xl font-bold text-slate-900">Danh sách sinh viên</h1></div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5"><div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.giaTri }}</p></div></div></div>
    <div class="the p-3 mb-4 flex flex-wrap items-center gap-2"><div class="relative flex-1 min-w-[220px]"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Tìm mã sinh viên, họ tên hoặc email..." @input="tai(1)" /></div>
        <input v-model="locLop" class="o-nhap !w-auto" placeholder="Lớp danh nghĩa (CNTT-K48A)" @input="tai(1)" />
      <button class="nut-phu text-sm"><i class="fa-solid fa-download"></i>Xuất dữ liệu</button></div>
    <div class="the">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr><th>Mã SV</th><th>Họ tên</th><th>Email</th><th>Lớp danh nghĩa</th><th>Khoa</th><th>Số lớp</th><th>Trạng thái</th></tr>
          </thead>
          <tbody>
            <tr v-for="sv in danhSach" :key="sv.id">
              <td class="font-mono text-xs font-semibold">{{ sv.ma_sinh_vien }}</td>
              <td><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">{{ chuCai(sv.ho_ten) }}</div><span class="font-medium text-slate-800">{{ sv.ho_ten }}</span></div></td>
              <td>{{ sv.email }}</td>
              <td>{{ sv.lop_danh_nghia || '—' }}</td>
              <td>{{ sv.khoa || '—' }}</td>
              <td><span class="nhan bg-brand-100 text-brand-700">{{ sv.so_lop_dang_ky }}</span></td>
              <td>
                <span class="nhan" :class="sv.trang_thai === 'hoat_dong' ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'">
                  {{ sv.trang_thai === 'hoat_dong' ? 'Hoạt động' : 'Bị khóa' }}
                </span>
              </td>
            </tr>
            <tr v-if="!danhSach.length"><td colspan="7" class="!py-10 text-center text-slate-400">Không tìm thấy sinh viên.</td></tr>
          </tbody>
        </table>
      </div>
      <div class="p-4 flex items-center justify-between border-t border-slate-100">
        <span class="text-sm text-slate-500">Trang {{ trangHienTai }} / {{ trangCuoi }}</span>
        <div class="flex gap-2">
          <button class="nut-phu text-sm" :disabled="trangHienTai <= 1" @click="tai(trangHienTai - 1)">Trước</button>
          <button class="nut-phu text-sm" :disabled="trangHienTai >= trangCuoi" @click="tai(trangHienTai + 1)">Sau</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'admin-sinh-vien',
  data() {
    return {
      danhSach: [],
      trangHienTai: 1,
      trangCuoi: 1,
      tuKhoa: '',
      locLop: '',
      tong: 0,
    }
  },
  async created() {
    await this.tai()
  },
  computed: { thongKe() { return [ { icon: 'fa-solid fa-user-graduate', nen: 'bg-indigo-50 text-indigo-600', nhan: 'Tổng sinh viên', giaTri: this.tong }, { icon: 'fa-solid fa-circle-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Hoạt động', giaTri: this.danhSach.filter((s) => s.trang_thai === 'hoat_dong').length }, { icon: 'fa-solid fa-users-rectangle', nen: 'bg-teal-50 text-teal-600', nhan: 'Có lớp đăng ký', giaTri: this.danhSach.filter((s) => Number(s.so_lop_dang_ky) > 0).length }, { icon: 'fa-solid fa-user-lock', nen: 'bg-slate-100 text-slate-500', nhan: 'Bị khóa', giaTri: this.danhSach.filter((s) => s.trang_thai !== 'hoat_dong').length } ] } },
  methods: {
    async tai(trang = 1) {
      const { data } = await api.get('/admin/sinh-vien', {
        params: {
          page: trang,
          tu_khoa: this.tuKhoa || null,
          lop_danh_nghia: this.locLop || null,
        },
      })
      this.danhSach = data.data
      this.trangHienTai = data.current_page
      this.trangCuoi = data.last_page
      this.tong = data.total || this.danhSach.length
    },
    chuCai(ten) { return (ten || '?').trim().split(/\s+/).slice(-2).map((x) => x[0]).join('').toUpperCase() },
  },
}
</script>
