<template>
  <div>
    <div class="mb-5"><div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'giang-vien-trang-chu' }" class="hover:text-teal-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Đơn xin phép</span></div><h1 class="text-2xl font-bold text-slate-900">Duyệt xin phép vắng</h1></div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5"><div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.giaTri }}</p></div></div></div>

    <div class="the p-3 mb-4 flex flex-wrap items-center gap-2"><div class="relative flex-1 min-w-[220px]"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Tìm sinh viên, lớp hoặc môn học..." /></div><select v-model="locTrangThai" class="o-nhap !w-auto min-w-[160px]"><option value="">Tất cả trạng thái</option><option value="cho_duyet">Chờ duyệt</option><option value="duoc_duyet">Được duyệt</option><option value="tu_choi">Từ chối</option></select></div>

    <div class="the">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr>
              <th>Sinh viên</th><th>Lớp / Môn</th><th>Ngày nghỉ</th><th>Lý do</th><th>Trạng thái</th><th class="!text-right">Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="!danhSachHienThi.length">
              <td colspan="6" class="!py-10 text-center text-slate-400">Chưa có đơn nào.</td>
            </tr>
            <tr v-for="d in danhSachHienThi" :key="d.id">
              <td>
                <div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-700 flex items-center justify-center text-xs font-bold">{{ chuCai(d.sinh_vien) }}</div><div><div class="font-medium text-slate-800">{{ d.sinh_vien }}</div>
                <div class="text-xs text-slate-400">{{ d.ma_sinh_vien_text }}</div>
                </div></div>
              </td>
              <td>
                {{ d.mon_hoc }}
                <div class="text-xs text-slate-400">{{ d.lop_hoc }}</div>
              </td>
              <td class="whitespace-nowrap">{{ d.ngay_nghi }}</td>
              <td class="max-w-xs">{{ d.ly_do }}</td>
              <td>
                <span class="nhan" :class="mau(d.trang_thai)">{{ ten(d.trang_thai) }}</span>
                <div v-if="d.nguoi_duyet" class="text-xs text-slate-400 mt-1">bởi {{ d.nguoi_duyet }}</div>
              </td>
              <td class="!text-right">
                <div v-if="d.trang_thai === 'cho_duyet'" class="flex justify-end gap-1.5">
                  <button class="nut bg-emerald-600 text-white hover:bg-emerald-700 text-xs !px-3 !py-1.5" @click="duyet(d, 'duoc_duyet')">Duyệt</button>
                  <button class="nut bg-rose-50 text-rose-600 hover:bg-rose-100 text-xs !px-3 !py-1.5" @click="duyet(d, 'tu_choi')">Từ chối</button>
                </div>
                <span v-else class="text-slate-300">—</span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'gv-xin-phep',
  data() {
    return { danhSach: [], tuKhoa: '', locTrangThai: '' }
  },
  async created() {
    await this.tai()
  },
  computed: {
    danhSachHienThi() { const q = this.tuKhoa.trim().toLowerCase(); return this.danhSach.filter((d) => (!this.locTrangThai || d.trang_thai === this.locTrangThai) && (!q || [d.sinh_vien, d.ma_sinh_vien_text, d.lop_hoc, d.mon_hoc].some((x) => String(x || '').toLowerCase().includes(q)))) },
    thongKe() { return [ { icon: 'fa-solid fa-file-circle-question', nen: 'bg-indigo-50 text-indigo-600', nhan: 'Tổng đơn', giaTri: this.danhSach.length }, { icon: 'fa-solid fa-clock', nen: 'bg-amber-50 text-amber-600', nhan: 'Chờ duyệt', giaTri: this.danhSach.filter((d) => d.trang_thai === 'cho_duyet').length }, { icon: 'fa-solid fa-circle-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Đã duyệt', giaTri: this.danhSach.filter((d) => d.trang_thai === 'duoc_duyet').length }, { icon: 'fa-solid fa-circle-xmark', nen: 'bg-rose-50 text-rose-600', nhan: 'Từ chối', giaTri: this.danhSach.filter((d) => d.trang_thai === 'tu_choi').length } ] },
  },
  methods: {
    async tai() {
      const { data } = await api.get('/xin-phep')
      this.danhSach = data.danh_sach
    },
    async duyet(don, trangThai) {
      await api.post(`/xin-phep/${don.id}/duyet`, { trang_thai: trangThai })
      await this.tai()
    },
    ten(t) {
      return { cho_duyet: 'Chờ duyệt', duoc_duyet: 'Được duyệt', tu_choi: 'Từ chối' }[t] || t
    },
    mau(t) {
      return {
        cho_duyet: 'bg-amber-100 text-amber-700',
        duoc_duyet: 'bg-emerald-100 text-emerald-700',
        tu_choi: 'bg-rose-100 text-rose-700',
      }[t] || 'bg-slate-100 text-slate-600'
    },
    chuCai(ten) { return (ten || '?').trim().split(/\s+/).slice(-2).map((x) => x[0]).join('').toUpperCase() },
  },
}
</script>
