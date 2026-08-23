<template>
  <div>
    <div class="tieu-de-trang"><div><div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1"><router-link :to="{ name: 'sinh-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span>Điểm danh</span></div><h4>Lịch sử điểm danh</h4></div></div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
      <div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-3"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium uppercase tracking-wide">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900">{{ th.giaTri }}</p></div></div>
    </div>

    <div class="the p-5 mb-5"><div class="flex items-center justify-between mb-2"><p class="text-sm font-semibold text-slate-800">Tỷ lệ chuyên cần tổng thể</p><p class="text-xl font-bold" :class="tyLe >= 80 ? 'text-emerald-600' : tyLe >= 65 ? 'text-amber-600' : 'text-rose-600'">{{ tyLe }}%</p></div><div class="h-3 bg-slate-100 rounded-full overflow-hidden"><div class="h-full rounded-full" :class="tyLe >= 80 ? 'bg-emerald-500' : tyLe >= 65 ? 'bg-amber-400' : 'bg-rose-500'" :style="{ width: tyLe + '%' }"></div></div><p class="text-xs text-slate-500 mt-1.5">Yêu cầu tối thiểu: 80% để đủ điều kiện dự thi cuối kỳ</p></div>

    <div class="flex flex-wrap items-center gap-3 mb-5"><div class="relative flex-1 min-w-[190px] max-w-xs"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-8 !py-2" placeholder="Tìm môn học..."></div><select v-model="locTrangThai" class="o-nhap !w-auto !py-2"><option value="">Tất cả trạng thái</option><option value="co_mat">Có mặt</option><option value="vang">Vắng mặt</option><option value="di_muon">Đi muộn</option><option value="vang_co_phep">Vắng có phép</option></select><button class="nut-phu ml-auto"><i class="fa-solid fa-download text-xs"></i>Xuất file</button></div>

    <div class="the overflow-hidden">
        <div class="overflow-x-auto">
          <table class="bang">
            <thead>
              <tr>
                <th>Ngày học</th><th>Môn học</th><th>Lớp</th><th>Thời gian điểm danh</th><th>Hình thức</th><th>Trạng thái</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!danhSachLoc.length">
                <td colspan="6" class="!py-14 text-center text-slate-400"><i class="fa-solid fa-clipboard-check text-slate-200 text-3xl"></i><p class="mt-2">Chưa có lịch sử điểm danh.</p></td>
              </tr>
              <tr v-for="d in danhSachLoc" :key="d.id">
                <td class="whitespace-nowrap"><p class="font-mono text-xs font-semibold text-slate-800">{{ d.ngay_hoc }}</p><p class="text-xs text-slate-400">{{ d.gio_bat_dau }}</p></td>
                <td><div class="flex items-center gap-2.5"><div class="w-7 h-7 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center"><i class="fa-solid fa-book text-xs"></i></div><span class="font-semibold text-slate-800">{{ d.mon_hoc }}</span></div></td>
                <td>{{ d.ten_lop }}</td>
                <td>{{ d.thoi_gian_diem_danh || '—' }}</td>
                <td><span class="nhan bg-slate-100 text-slate-600 border border-slate-200 !py-0.5">{{ tenHinhThuc(d.hinh_thuc_diem_danh) }}</span></td>
                <td><span class="nhan border !py-0.5" :class="mauTrangThai(d.trang_thai_diem_danh)"><span class="w-1.5 h-1.5 rounded-full" :class="mauCham(d.trang_thai_diem_danh)"></span>{{ tenTrangThai(d.trang_thai_diem_danh) }}</span></td>
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
  name: 'lich-su-diem-danh',
  data() {
    return { danhSach: [], tuKhoa: '', locTrangThai: '' }
  },
  computed: {
    soCoMat() { return this.danhSach.filter((d) => d.trang_thai_diem_danh === 'co_mat').length },
    soVang() { return this.danhSach.filter((d) => d.trang_thai_diem_danh === 'vang').length },
    soDiMuon() { return this.danhSach.filter((d) => d.trang_thai_diem_danh === 'di_muon').length },
    tyLe() { return this.danhSach.length ? Math.round(this.soCoMat / this.danhSach.length * 100) : 0 },
    thongKe() { return [
      { icon: 'fa-solid fa-list-check', nen: 'bg-brand-50 text-brand-600', nhan: 'Tổng buổi', giaTri: this.danhSach.length },
      { icon: 'fa-solid fa-circle-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Có mặt', giaTri: this.soCoMat },
      { icon: 'fa-solid fa-circle-xmark', nen: 'bg-rose-50 text-rose-600', nhan: 'Vắng mặt', giaTri: this.soVang },
      { icon: 'fa-solid fa-clock', nen: 'bg-amber-50 text-amber-600', nhan: 'Đi trễ', giaTri: this.soDiMuon },
    ] },
    danhSachLoc() { const q = this.tuKhoa.trim().toLowerCase(); return this.danhSach.filter((d) => (!q || `${d.mon_hoc} ${d.ten_lop}`.toLowerCase().includes(q)) && (!this.locTrangThai || d.trang_thai_diem_danh === this.locTrangThai)) },
  },
  async created() {
    const { data } = await api.get('/sinh-vien/lich-su-diem-danh')
    this.danhSach = data.danh_sach || []
  },
  methods: {
    tenHinhThuc(h) {
      return { qr_code: 'Quét QR', thu_cong: 'Thủ công', sua_thu_cong: 'Sửa tay' }[h] || h
    },
    tenTrangThai(t) {
      return { co_mat: 'Có mặt', vang: 'Vắng', di_muon: 'Đi muộn', xin_phep: 'Xin phép', vang_co_phep: 'Vắng có phép' }[t] || t
    },
    mauTrangThai(t) {
      return {
        co_mat: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        vang: 'bg-rose-50 text-rose-700 border-rose-200',
        di_muon: 'bg-amber-50 text-amber-700 border-amber-200',
        xin_phep: 'bg-violet-50 text-violet-700 border-violet-200',
        vang_co_phep: 'bg-sky-50 text-sky-700 border-sky-200',
      }[t] || 'bg-slate-100 text-slate-600 border-slate-200'
    },
    mauCham(t) { return { co_mat: 'bg-emerald-500', vang: 'bg-rose-500', di_muon: 'bg-amber-500', xin_phep: 'bg-violet-500', vang_co_phep: 'bg-sky-500' }[t] || 'bg-slate-400' },
  },
}
</script>

