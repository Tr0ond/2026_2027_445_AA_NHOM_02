<template>
  <div>
    <div class="tieu-de-trang"><div><div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1"><router-link :to="{ name: 'sinh-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span>Điểm số</span></div><h4>Bảng điểm</h4></div></div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6">
      <div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-3"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium uppercase tracking-wide">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 leading-tight mt-0.5">{{ th.giaTri }}</p><p class="text-xs text-slate-500 mt-0.5">{{ th.phu }}</p></div></div>
    </div>

    <div class="the p-5 mb-5">
      <div class="flex items-center justify-between mb-3"><h3 class="text-sm font-semibold text-slate-800">Tiến độ hoàn thành môn học</h3><span class="text-sm font-bold text-brand-600">{{ soMonDat }}/{{ danhSach.length }} môn</span></div>
      <div class="h-3 bg-slate-100 rounded-full overflow-hidden mb-2"><div class="h-full bg-gradient-to-r from-brand-500 to-violet-500 rounded-full" :style="{ width: tyLeHoanThanh + '%' }"></div></div>
      <div class="flex justify-between text-xs text-slate-500"><span>Đang học</span><span class="text-brand-600 font-medium">{{ tyLeHoanThanh }}% có kết quả đạt</span><span>Hoàn thành</span></div>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
      <div class="relative flex-1 min-w-[190px] max-w-xs"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-8 !py-2" placeholder="Tìm môn học..."></div>
      <select v-model="locTrangThai" class="o-nhap !w-auto !py-2"><option value="">Tất cả trạng thái</option><option value="dat">Đã đạt</option><option value="chua_dat">Chưa đạt</option><option value="chua_co">Chưa có điểm</option></select>
      <button class="nut-phu ml-auto"><i class="fa-solid fa-download text-xs"></i>Xuất bảng điểm</button>
    </div>

    <div class="the overflow-hidden">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead><tr><th>Môn học</th><th>Lớp</th><th>Điểm thành phần</th><th class="!text-center">Tổng kết</th><th class="!text-center">Xếp loại</th><th>Trạng thái</th></tr></thead>
          <tbody>
            <tr v-for="l in danhSachLoc" :key="l.id">
              <td><div class="flex items-center gap-2.5"><div class="w-8 h-8 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-book text-xs"></i></div><span class="font-semibold text-slate-800">{{ l.mon_hoc }}</span></div></td>
              <td class="whitespace-nowrap text-slate-600">{{ l.ten_lop }}</td>
              <td><div v-if="l.diem_thanh_phan?.length" class="flex flex-wrap gap-1.5"><span v-for="d in l.diem_thanh_phan" :key="d.ten_thanh_phan" class="inline-flex items-center gap-1 px-2 py-1 rounded-lg bg-slate-50 border border-slate-200 text-xs"><span class="text-slate-500">{{ d.ten_thanh_phan }}</span><strong :class="mauDiem(d.diem)">{{ d.diem ?? '—' }}</strong></span></div><span v-else class="text-slate-300">—</span></td>
              <td class="!text-center"><span class="text-base font-bold" :class="mauDiem(l.diem_tong_ket)">{{ l.diem_tong_ket ?? '—' }}</span></td>
              <td class="!text-center"><span class="inline-block px-2 py-0.5 text-xs font-bold rounded-lg border" :class="mauXepLoai(l)">{{ l.xep_loai || '—' }}</span></td>
              <td><span class="nhan border !py-0.5" :class="l.diem_tong_ket == null ? 'bg-slate-50 text-slate-500 border-slate-200' : l.trang_thai_ket_qua === 'dat' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'"><span class="w-1.5 h-1.5 rounded-full" :class="l.diem_tong_ket == null ? 'bg-slate-400' : l.trang_thai_ket_qua === 'dat' ? 'bg-emerald-500' : 'bg-rose-500'"></span>{{ l.diem_tong_ket == null ? 'Đang học' : l.trang_thai_ket_qua === 'dat' ? 'Hoàn thành' : 'Chưa đạt' }}</span></td>
            </tr>
            <tr v-if="!danhSachLoc.length"><td colspan="6" class="!py-14 text-center"><i class="fa-solid fa-chart-bar text-slate-200 text-3xl"></i><p class="text-sm text-slate-400 mt-2">Chưa có dữ liệu điểm phù hợp.</p></td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'diem-cua-toi',
  data() { return { danhSach: [], tuKhoa: '', locTrangThai: '' } },
  computed: {
    soMonDat() { return this.danhSach.filter((l) => l.trang_thai_ket_qua === 'dat').length },
    soMonCoDiem() { return this.danhSach.filter((l) => l.diem_tong_ket !== null && l.diem_tong_ket !== undefined).length },
    diemTrungBinh() { const ds = this.danhSach.map((l) => Number(l.diem_tong_ket)).filter(Number.isFinite); return ds.length ? (ds.reduce((a, b) => a + b, 0) / ds.length).toFixed(2) : '—' },
    tyLeHoanThanh() { return this.danhSach.length ? Math.round(this.soMonDat / this.danhSach.length * 100) : 0 },
    thongKe() { return [
      { icon: 'fa-solid fa-star', nen: 'bg-amber-50 text-amber-600', nhan: 'Điểm trung bình', giaTri: this.diemTrungBinh, phu: 'Các môn đã có điểm' },
      { icon: 'fa-solid fa-circle-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Môn hoàn thành', giaTri: `${this.soMonDat}/${this.danhSach.length}`, phu: 'Kết quả đạt' },
      { icon: 'fa-solid fa-book-open', nen: 'bg-brand-50 text-brand-600', nhan: 'Môn đang học', giaTri: this.danhSach.length - this.soMonCoDiem, phu: 'Chưa có tổng kết' },
      { icon: 'fa-solid fa-chart-bar', nen: 'bg-violet-50 text-violet-600', nhan: 'Đã có kết quả', giaTri: this.soMonCoDiem, phu: 'Trong học kỳ' },
    ] },
    danhSachLoc() { const q = this.tuKhoa.trim().toLowerCase(); return this.danhSach.filter((l) => { const dungTuKhoa = !q || `${l.mon_hoc} ${l.ten_lop}`.toLowerCase().includes(q); const trangThai = l.diem_tong_ket == null ? 'chua_co' : l.trang_thai_ket_qua === 'dat' ? 'dat' : 'chua_dat'; return dungTuKhoa && (!this.locTrangThai || this.locTrangThai === trangThai) }) },
  },
  async created() { const { data } = await api.get('/sinh-vien/diem'); this.danhSach = data.danh_sach || [] },
  methods: {
    mauDiem(d) { if (d === null || d === undefined) return 'text-slate-300'; return Number(d) >= 8 ? 'text-emerald-600' : Number(d) >= 5 ? 'text-blue-600' : 'text-rose-600' },
    mauXepLoai(l) { if (l.diem_tong_ket == null) return 'bg-slate-50 text-slate-400 border-slate-200'; return l.trang_thai_ket_qua === 'dat' ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200' },
  },
}
</script>
