<template>
  <div>
    <div class="mb-5 flex items-end justify-between gap-4 flex-wrap">
      <div><div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'admin-dashboard' }" class="hover:text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Phân công GV</span></div><h1 class="text-2xl font-bold text-slate-900">Phân công giảng viên</h1></div>
      <button class="nut-chinh text-sm" @click="moModal = true"><i class="fa-solid fa-plus"></i>Phân công mới</button>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5"><div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.giaTri }}</p><p v-if="th.phu" class="text-xs text-slate-500">{{ th.phu }}</p></div></div></div>

    <div class="the p-3 mb-4 flex flex-wrap items-center gap-2"><div class="relative flex-1 min-w-[220px]"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Tìm kiếm giảng viên..." /></div><button class="nut-phu text-sm"><i class="fa-solid fa-download"></i>Xuất dữ liệu</button></div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
      <div v-for="gv in giangVienHienThi" :key="gv.id" class="bg-white rounded-[14px] border p-5 shadow-[0_1px_3px_0_rgb(0,0,0,0.06)]" :class="gv.quaTai ? 'border-amber-300' : 'border-slate-200'">
        <div class="flex items-start gap-3 mb-4"><div class="w-11 h-11 rounded-2xl flex items-center justify-center text-white font-bold text-sm shrink-0" :class="gv.quaTai ? 'bg-amber-500' : 'bg-teal-500'">{{ chuCai(gv.ho_ten) }}</div><div class="flex-1 min-w-0"><h3 class="font-bold text-slate-900 text-sm truncate">{{ gv.ho_ten }}</h3><p class="text-xs text-slate-500">Giảng viên</p><p class="text-xs text-slate-400 font-mono">{{ gv.ma_giang_vien }}</p></div><span v-if="gv.quaTai" class="nhan bg-amber-50 text-amber-700 border border-amber-200">Quá tải</span></div>
        <div class="mb-4"><div class="flex justify-between text-xs text-slate-500 mb-1.5"><span>Tải giảng dạy</span><span class="font-bold" :class="gv.quaTai ? 'text-amber-600' : 'text-teal-600'">{{ gv.lops.length }}/{{ gioiHanLop }} lớp</span></div><div class="h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full rounded-full" :class="gv.quaTai ? 'bg-amber-500' : gv.lops.length >= 3 ? 'bg-teal-500' : 'bg-emerald-500'" :style="{ width: Math.min(gv.lops.length / gioiHanLop * 100, 100) + '%' }"></div></div></div>
        <div class="space-y-1.5 mb-4 min-h-[42px]"><div v-for="lop in gv.lops.slice(0, 3)" :key="lop.id" class="flex items-center gap-2 px-2.5 py-2 rounded-xl text-xs" :class="lop.trang_thai === 'dang_hoc' ? 'bg-teal-50 border border-teal-100' : 'bg-blue-50 border border-blue-100'"><span class="w-1.5 h-1.5 rounded-full shrink-0" :class="lop.trang_thai === 'dang_hoc' ? 'bg-teal-500' : 'bg-blue-500'"></span><span class="font-semibold text-slate-700">{{ lop.ma_lop_hoc }}</span><span class="text-slate-500 truncate">{{ lop.mon_hoc }}</span><span class="ml-auto shrink-0 font-medium text-teal-600">{{ lop.so_sinh_vien }}SV</span></div><p v-if="!gv.lops.length" class="text-xs text-slate-400 text-center py-2">Chưa phân công lớp nào</p></div>
        <div class="flex gap-2"><button class="flex-1 py-2 text-xs font-medium bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200"><i class="fa-solid fa-eye mr-1"></i>Xem tất cả</button><button v-if="!gv.quaTai" class="flex-1 py-2 text-xs font-medium bg-brand-600 text-white rounded-xl hover:bg-brand-700" @click="moPhanCongCho(gv)"><i class="fa-solid fa-plus mr-1"></i>Phân công</button></div>
      </div>
      <div v-if="!giangVienHienThi.length" class="sm:col-span-2 lg:col-span-3 the p-12 text-center text-slate-400"><i class="fa-solid fa-chalkboard-user text-3xl text-slate-200 block mb-2"></i>Không tìm thấy giảng viên.</div>
    </div>

    <div v-if="moModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="moModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between"><h2 class="font-bold text-slate-900">Phân công lớp học phần</h2><button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="moModal = false"><i class="fa-solid fa-xmark"></i></button></div>
        <form class="p-5 space-y-4" @submit.prevent="phanCong">
          <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Giảng viên</label><select v-model="form.ma_giang_vien" class="o-nhap" required><option value="" disabled>Chọn giảng viên</option><option v-for="g in giangViens" :key="g.id" :value="g.id">{{ g.ho_ten }} – {{ soLopCuaGv(g.id) }}/{{ gioiHanLop }} lớp</option></select></div>
          <div><label class="block text-sm font-medium text-slate-700 mb-1.5">Lớp học phần</label><select v-model="form.ma_lop_hoc" class="o-nhap" required><option value="" disabled>Chọn lớp học phần</option><option v-for="l in danhSach" :key="l.id" :value="l.id">{{ l.ma_lop_hoc }} – {{ l.mon_hoc }}</option></select></div>
          <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-xs text-amber-800 flex items-start gap-2"><i class="fa-solid fa-circle-info text-amber-500 mt-0.5"></i><span>Mỗi giảng viên chỉ được phân công tối đa số lớp theo quy định. Hệ thống sẽ cảnh báo nếu vượt giới hạn.</span></div>
          <div v-if="loiForm" class="rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2 text-sm">{{ loiForm }}</div>
          <div class="flex gap-3 pt-2"><button type="button" class="flex-1 py-2.5 text-sm bg-slate-100 text-slate-700 rounded-xl hover:bg-slate-200" @click="moModal = false">Hủy</button><button class="flex-1 py-2.5 text-sm bg-brand-600 text-white rounded-xl hover:bg-brand-700 font-medium">Xác nhận phân công</button></div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'admin-phan-cong',
  data() { return { danhSach: [], giangViens: [], form: { ma_lop_hoc: '', ma_giang_vien: '' }, moModal: false, loiForm: '', tuKhoa: '', gioiHanLop: 4 } },
  computed: {
    giangVienDayDu() { return this.giangViens.map((gv) => { const lops = this.danhSach.filter((l) => l.giang_vien.some((g) => g.id === gv.id)); return { ...gv, lops, quaTai: lops.length >= this.gioiHanLop } }) },
    giangVienHienThi() { const q = this.tuKhoa.trim().toLowerCase(); return this.giangVienDayDu.filter((gv) => !q || gv.ho_ten?.toLowerCase().includes(q) || gv.ma_giang_vien?.toLowerCase().includes(q)) },
    thongKe() { const daPhanCong = this.danhSach.filter((l) => l.giang_vien.length).length; return [
      { icon: 'fa-solid fa-chalkboard-user', nen: 'bg-teal-50 text-teal-600', nhan: 'Tổng giảng viên', giaTri: this.giangViens.length },
      { icon: 'fa-solid fa-chalkboard', nen: 'bg-indigo-50 text-indigo-600', nhan: 'Lớp đã phân công', giaTri: daPhanCong },
      { icon: 'fa-solid fa-circle-exclamation', nen: 'bg-amber-50 text-amber-600', nhan: 'GV quá tải', giaTri: this.giangVienDayDu.filter((g) => g.quaTai).length, phu: '≥ giới hạn' },
      { icon: 'fa-solid fa-circle-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'GV còn khả năng', giaTri: this.giangVienDayDu.filter((g) => !g.quaTai).length },
    ] },
  },
  async created() { await this.tai() },
  methods: {
    async tai() { const [resLop, resGv] = await Promise.all([api.get('/admin/phan-cong'), api.get('/admin/phan-cong/giang-vien')]); this.danhSach = resLop.data.danh_sach || []; this.giangViens = resGv.data.danh_sach || [] },
    async phanCong() { this.loiForm = ''; try { await api.post('/admin/phan-cong', this.form); this.form = { ma_lop_hoc: '', ma_giang_vien: '' }; this.moModal = false; await this.tai() } catch (e) { this.loiForm = e.response?.data?.message || 'Phân công thất bại.' } },
    async huyPhanCong(lop, gv) { if (!confirm(`Hủy phân công "${gv.ho_ten}" khỏi lớp ${lop.ma_lop_hoc}?`)) return; try { await api.delete('/admin/phan-cong', { params: { ma_giang_vien: gv.id, ma_lop_hoc: lop.id } }); await this.tai() } catch (e) { alert(e.response?.data?.message || 'Hủy thất bại.') } },
    moPhanCongCho(gv) { this.form.ma_giang_vien = gv.id; this.moModal = true },
    soLopCuaGv(id) { return this.danhSach.filter((l) => l.giang_vien.some((g) => g.id === id)).length },
    chuCai(ten) { return (ten || '?').trim().split(/\s+/).slice(-2).map((x) => x[0]).join('').toUpperCase() },
  },
}
</script>
