<template>
  <div>
    <div class="tieu-de-trang">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1"><router-link
            :to="{ name: 'sinh-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link><i
            class="fa-solid fa-chevron-right text-[9px]"></i><span>Đăng ký lớp</span></div>
        <h4>Đăng ký lớp học phần</h4>
      </div>
    </div>

    <div
      class="bg-brand-50 border border-brand-200 rounded-xl px-4 py-3 flex items-start gap-3 mb-5 text-sm text-brand-800">
      <i class="fa-solid fa-circle-info text-brand-500 mt-0.5"></i>
      <div>
        <p class="font-semibold">Đợt đăng ký học phần đang mở</p>
        <p class="text-brand-600 text-xs mt-0.5">Đã đăng ký: <strong>{{ soLopDaDangKy }} lớp</strong> · Hệ thống tự động
          kiểm tra trùng môn, trùng lịch và sĩ số.</p>
      </div>
    </div>
    <div v-if="thongBao"
      class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm"><i
        class="fa-solid fa-circle-check mr-2"></i>{{ thongBao }}</div>
    <div v-if="loi" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2.5 text-sm"><i
        class="fa-solid fa-circle-exclamation mr-2"></i>{{ loi }}</div>

    <div class="flex flex-wrap items-center gap-3 mb-5">
      <div class="relative flex-1 min-w-[200px] max-w-xs"><i
          class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input
          v-model="tuKhoa" class="o-nhap !pl-8 !py-2" placeholder="Tìm mã lớp hoặc môn học..."></div><select
        v-model="locTrangThai" class="o-nhap !w-auto !py-2">
        <option value="">Tất cả lớp</option>
        <option value="co_the">Có thể đăng ký</option>
        <option value="da_dang_ky">Đã đăng ký</option>
        <option value="khong_the">Không khả dụng</option>
      </select>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
      <article v-for="l in danhSachLoc" :key="l.id" class="the p-5 transition-all hover:shadow-md"
        :class="l.da_dang_ky ? 'border-brand-300 ring-1 ring-brand-100' : ''">
        <div class="flex items-start justify-between gap-2 mb-3">
          <div class="min-w-0">
            <div class="flex items-center gap-2 flex-wrap mb-1"><span
                class="font-mono text-xs font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-lg border border-brand-200">{{
                  l.ma_lop_hoc }}</span><span v-if="l.da_dang_ky"
                class="nhan bg-brand-50 text-brand-700 border border-brand-200 !py-0.5">Đã đăng ký</span><span v-else
                class="nhan border !py-0.5"
                :class="theDuocDangKy(l) ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : 'bg-rose-50 text-rose-700 border-rose-200'">{{
                  theDuocDangKy(l) ? 'Còn chỗ' : 'Không khả dụng' }}</span></div>
            <h3 class="font-bold text-slate-900 text-sm">{{ l.mon_hoc }}</h3>
            <p class="text-xs text-slate-500 mt-0.5">{{ l.ten_lop }} · <strong>{{ l.so_tin_chi }}</strong> tín chỉ</p>
          </div>
        </div>
        <div class="space-y-2 text-xs text-slate-500 mb-3">
          <p><i class="fa-regular fa-calendar w-5 text-slate-300"></i>{{ l.hoc_ky }} {{ l.nam_hoc }}</p>
          <p v-if="l.lich_hoc?.length"><i class="fa-regular fa-clock w-5 text-slate-300"></i>{{
            dinhDangNgay(l.lich_hoc[0].ngay_hoc) }} · {{ l.lich_hoc[0].gio_bat_dau }}–{{ l.lich_hoc[0].gio_ket_thuc
            }}<span v-if="l.lich_hoc.length > 1"> · +{{ l.lich_hoc.length - 1 }} buổi</span></p>
        </div>
        <div class="mb-4">
          <div class="flex justify-between text-xs text-slate-500 mb-1"><span>Sĩ số</span><span class="font-semibold"
              :class="phanTram(l) >= 90 ? 'text-rose-600' : phanTram(l) >= 70 ? 'text-amber-600' : 'text-emerald-600'">{{
                l.so_luong_dang_ky }}/{{ l.so_luong_toi_da }}</span></div>
          <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
            <div class="h-full rounded-full"
              :class="phanTram(l) >= 90 ? 'bg-rose-500' : phanTram(l) >= 70 ? 'bg-amber-400' : 'bg-emerald-500'"
              :style="{ width: Math.min(phanTram(l), 100) + '%' }"></div>
          </div>
        </div>
        <div v-if="canhBao(l).length && !l.da_dang_ky" class="mb-3 space-y-1">
          <p v-for="w in canhBao(l)" :key="w" class="text-xs text-rose-600 flex items-start gap-1.5"><i
              class="fa-solid fa-triangle-exclamation mt-0.5"></i><span>{{ w }}</span></p>
        </div>
        <button v-if="!l.da_dang_ky" class="w-full py-2.5 text-sm font-semibold rounded-xl transition-all"
          :class="theDuocDangKy(l) ? 'bg-brand-600 text-white hover:bg-brand-700 shadow-sm' : 'bg-slate-100 text-slate-400 cursor-not-allowed'"
          :disabled="!theDuocDangKy(l)" @click="dangKy(l)"><i class="fa-solid fa-plus mr-2"></i>{{ theDuocDangKy(l) ?
            'Đăng ký' : 'Không thể đăng ký' }}</button>
        <button v-else class="w-full py-2.5 text-sm font-semibold rounded-xl border transition-all"
          :class="l.con_huy ? 'bg-rose-50 text-rose-600 border-rose-200 hover:bg-rose-100' : 'bg-slate-100 text-slate-400 border-slate-200 cursor-not-allowed'"
          :disabled="!l.con_huy" @click="huyDangKy(l)"><i class="fa-solid fa-minus mr-2"></i>{{ l.con_huy ? 'Hủy đăng ký' : 'Đã quá hạn hủy' }}</button>
      </article>
    </div>
    <div v-if="!danhSachLoc.length" class="the py-16 text-center"><i
        class="fa-solid fa-book-open text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Không tìm thấy lớp học phần phù hợp.</p>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'trang-dang-ky-lop',
  data() { return { danhSach: [], monDaHoc: [], monIdDaHoc: [], lichCuaToi: [], thongBao: '', loi: '', tuKhoa: '', locTrangThai: '' } },
  computed: {
    soLopDaDangKy() { return this.danhSach.filter((l) => l.da_dang_ky).length },
    danhSachLoc() { const q = this.tuKhoa.trim().toLowerCase(); return this.danhSach.filter((l) => { const dungQ = !q || `${l.ma_lop_hoc} ${l.mon_hoc} ${l.ten_lop}`.toLowerCase().includes(q); const tt = l.da_dang_ky ? 'da_dang_ky' : this.theDuocDangKy(l) ? 'co_the' : 'khong_the'; return dungQ && (!this.locTrangThai || tt === this.locTrangThai) }) },
  },
  async created() { await Promise.all([this.taiDanhSach(), this.taiDuLieuCuaToi()]) },
  methods: {
    async taiDanhSach() { const { data } = await api.get('/sinh-vien/lop-hoc-mo'); this.danhSach = data.danh_sach || [] },
    async taiDuLieuCuaToi() { const [resLop, resLich] = await Promise.all([api.get('/sinh-vien/lop-cua-toi'), api.get('/lich-hoc')]); this.monDaHoc = (resLop.data.danh_sach || []).map((l) => l.mon_hoc); this.lichCuaToi = resLich.data.danh_sach || []; this.monIdDaHoc = [...new Set(this.lichCuaToi.map((b) => b.ma_mon_hoc).filter(Boolean))] },
    hetSlot(l) { return l.so_luong_dang_ky >= l.so_luong_toi_da }, trungMon(l) { return this.monDaHoc.includes(l.mon_hoc) || this.monIdDaHoc.includes(l.ma_mon_hoc) },
    timBuoiTrung(l) { for (const lh of l.lich_hoc || []) for (const cuaToi of this.lichCuaToi) if (lh.ngay_hoc === cuaToi.ngay_hoc && lh.gio_bat_dau < cuaToi.gio_ket_thuc && cuaToi.gio_bat_dau < lh.gio_ket_thuc) return { moi: lh, cu: cuaToi }; return null },
    trungLich(l) { return !!this.timBuoiTrung(l) }, chiTietTrungLich(l) { const t = this.timBuoiTrung(l); return t ? `Trùng ${t.cu.mon_hoc} ngày ${this.dinhDangNgay(t.cu.ngay_hoc)} (${t.cu.gio_bat_dau}–${t.cu.gio_ket_thuc})` : '' },
    theDuocDangKy(l) { return !this.hetSlot(l) && l.con_dang_ky !== false && !this.trungMon(l) && !this.trungLich(l) },
    canhBao(l) { const w = []; if (this.hetSlot(l)) w.push('Lớp đã hết chỗ'); if (l.con_dang_ky === false) w.push('Đã hết hạn đăng ký'); if (this.trungMon(l)) w.push('Bạn đã học môn này ở lớp khác'); if (this.trungLich(l)) w.push(this.chiTietTrungLich(l)); return w },
    phanTram(l) { return l.so_luong_toi_da ? Math.round(l.so_luong_dang_ky / l.so_luong_toi_da * 100) : 0 },
    async dangKy(l) {
      this.thongBao = '';
      this.loi = '';
      try {
        const { data } = await api.post(`/sinh-vien/dang-ky-lop/${l.id}`);
        this.thongBao = data.message; await Promise.all([this.taiDanhSach(), this.taiDuLieuCuaToi()])
      } catch (e) {
        this.loi = e.response?.data?.message || 'Đăng ký thất bại.';
        await this.taiDanhSach()
      }
    },
    async huyDangKy(l) { this.thongBao = ''; this.loi = ''; try { const { data } = await api.post(`/sinh-vien/huy-dang-ky/${l.id}`); this.thongBao = data.message; await Promise.all([this.taiDanhSach(), this.taiDuLieuCuaToi()]) } catch (e) { this.loi = e.response?.data?.message || 'Hủy đăng ký thất bại.' } },
    dinhDangNgay(n) { return new Date(n).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit' }) },
  },
}
</script>
