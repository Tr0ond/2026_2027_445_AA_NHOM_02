<template>
  <div>
    <div class="mb-5 flex items-end justify-between gap-4 flex-wrap">
      <div><div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'giang-vien-trang-chu' }" class="hover:text-teal-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Nhập điểm</span></div><h1 class="text-2xl font-bold text-slate-900">Nhập điểm</h1></div>
      <button class="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm font-semibold rounded-xl hover:bg-teal-700 shadow-sm" :disabled="!lopChon" @click="dongBoChuyenCan"><i class="fa-solid fa-rotate"></i>Đồng bộ chuyên cần</button>
    </div>

    <div class="flex items-center gap-2 mb-5 overflow-x-auto pb-1"><button v-for="l in lops" :key="l.id" class="shrink-0 px-4 py-2 rounded-xl text-sm font-medium border transition-colors" :class="lopChon === l.id ? 'bg-teal-600 text-white border-teal-600' : 'bg-white text-slate-600 border-slate-200 hover:border-teal-300'" @click="chonLop(l.id)">{{ l.ma_lop_hoc || l.ten_lop }}</button></div>

    <div v-if="lopChon" class="space-y-4">
      <div class="flex flex-wrap gap-3"><span v-for="(tp, i) in thanhPhan" :key="tp.id" class="text-xs px-2.5 py-1 rounded-lg border font-medium" :class="mauThanhPhan(i)">{{ tp.ten_thanh_phan }} ({{ dinhDangTrongSo(tp.trong_so) }})</span><span class="text-xs text-slate-400 self-center ml-auto">Thang điểm: 0 – 10</span></div>
      <div class="rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 flex items-start gap-3 text-sm"><span class="w-8 h-8 rounded-lg bg-white text-sky-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-calculator"></i></span><div><p class="font-semibold text-sky-900">Quy tắc điểm chuyên cần theo từng buổi</p><p class="text-xs text-sky-700 mt-0.5">Đạt ít nhất 2/3 phiên: 1 điểm · Vắng có phép: 0,5 điểm · Vắng hoặc không đủ phiên: 0 điểm. Tổng điểm được quy đổi về thang 10.</p></div></div>

      <!-- US18: bảng điểm -->
      <div class="the">
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-3"><p class="text-xs text-slate-500">{{ lopDangChon?.ma_lop_hoc || lopDangChon?.ten_lop }} – {{ lopDangChon?.hoc_ky }} {{ lopDangChon?.nam_hoc }} &nbsp;·&nbsp; {{ danhSach.length }} sinh viên</p><div class="ml-auto flex items-center gap-2"><span class="text-xs text-slate-400">Đã nhập:</span><span class="text-xs font-semibold text-teal-600">{{ soDaNhap }}/{{ danhSach.length }}</span></div></div>
        <div class="overflow-x-auto">
          <table class="bang">
            <thead>
              <tr>
                <th>Mã SV</th><th>Họ tên</th>
                <th v-for="(tp, i) in thanhPhan" :key="tp.id" class="!text-center min-w-[110px]" :class="mauTieuDeThanhPhan(i)">
                  {{ tp.ten_thanh_phan }}
                  <div class="font-normal normal-case opacity-70">{{ dinhDangTrongSo(tp.trong_so) }}</div>
                </th>
                <th class="!text-center">Điểm TB</th><th>Chữ</th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="!danhSach.length">
                <td :colspan="thanhPhan.length + 4" class="!py-10 text-center text-slate-400">Lớp chưa có sinh viên.</td>
              </tr>
              <tr v-for="sv in danhSach" :key="sv.ma_sinh_vien">
                <td class="font-mono text-xs">{{ sv.ma_sv_text }}</td>
                <td class="font-medium text-slate-800 whitespace-nowrap"><div class="flex items-center gap-2"><div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 text-xs font-semibold shrink-0">{{ chuCai(sv.ho_ten) }}</div>{{ sv.ho_ten }}</div></td>
                <td v-for="(tp, i) in thanhPhan" :key="tp.id">
                  <input type="number" step="0.1" min="0" max="10"
                    class="w-20 px-2.5 py-1.5 border rounded-lg text-sm text-center focus:outline-none focus:ring-2 bg-slate-50 focus:bg-white" :class="mauInput(i)"
                    :value="sv.diem[tp.id]" @change="luuDiem(sv, tp, $event.target.value)" />
                </td>
                <td class="!text-center font-extrabold" :class="sv.diem_tong_ket >= 5 ? 'text-emerald-600' : 'text-rose-600'">
                  {{ sv.diem_tong_ket ?? '—' }}
                </td>
                <td><span class="px-2 py-0.5 text-xs font-bold rounded-lg border" :class="mauXepLoai(sv.xep_loai)">{{ sv.xep_loai || '—' }}</span></td>
              </tr>
            </tbody>
          </table>
        </div><div class="px-5 py-3 border-t border-slate-100 bg-slate-50 flex flex-wrap gap-4 text-xs text-slate-500"><span>TB lớp: <strong class="text-indigo-600">{{ trungBinhLop }}</strong></span><span>Cao nhất: <strong class="text-emerald-600">{{ diemCaoNhat }}</strong></span><span>Thấp nhất: <strong class="text-rose-600">{{ diemThapNhat }}</strong></span><button class="ml-auto text-teal-600 font-medium" @click="dongBoChuyenCan"><i class="fa-solid fa-rotate mr-1"></i>Đồng bộ chuyên cần</button></div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'gv-quan-ly-diem',
  data() {
    return {
      lops: [],
      lopChon: '',
      thanhPhan: [],
      danhSach: [],
    }
  },
  async created() {
    const { data } = await api.get('/lop-day')
    this.lops = data.danh_sach
  },
  computed: {
    lopDangChon() { return this.lops.find((l) => l.id === this.lopChon) },
    soDaNhap() { const tp = this.thanhPhan[0]; return tp ? this.danhSach.filter((s) => s.diem[tp.id] !== null && s.diem[tp.id] !== undefined).length : 0 },
    diemTongKetHopLe() { return this.danhSach.map((s) => Number(s.diem_tong_ket)).filter(Number.isFinite) },
    trungBinhLop() { return this.diemTongKetHopLe.length ? (this.diemTongKetHopLe.reduce((a, b) => a + b, 0) / this.diemTongKetHopLe.length).toFixed(2) : '—' },
    diemCaoNhat() { return this.diemTongKetHopLe.length ? Math.max(...this.diemTongKetHopLe).toFixed(1) : '—' },
    diemThapNhat() { return this.diemTongKetHopLe.length ? Math.min(...this.diemTongKetHopLe).toFixed(1) : '—' },
  },
  methods: {
    async chonLop(id) { this.lopChon = id; await this.taiBangDiem() },
    async taiBangDiem() {
      if (!this.lopChon) return
      const [resTp, resDiem] = await Promise.all([
        api.get(`/lop-hoc/${this.lopChon}/thanh-phan`),
        api.get(`/lop-hoc/${this.lopChon}/diem`),
      ])
      this.thanhPhan = resTp.data.danh_sach
      this.danhSach = resDiem.data.danh_sach
    },
    async luuDiem(sv, tp, giaTri) {
      try {
        await api.post('/luu-diem', {
          ma_sinh_vien: sv.ma_sinh_vien,
          ma_thanh_phan: tp.id,
          diem: giaTri === '' ? null : Number(giaTri),
        })
        await this.taiBangDiem()
      } catch (e) {
        alert(e.response?.data?.message || 'Lưu điểm thất bại.')
      }
    },
    async dongBoChuyenCan() {
      try {
        const { data } = await api.post(`/lop-hoc/${this.lopChon}/dong-bo-chuyen-can`)
        alert(data.message)
        await this.taiBangDiem()
      } catch (e) {
        alert(e.response?.data?.message || 'Đồng bộ thất bại.')
      }
    },
    dinhDangTrongSo(ts) { const n = Number(ts); return n <= 1 ? `${Math.round(n * 100)}%` : `TS ${n}` },
    mauThanhPhan(i) { return ['bg-indigo-50 border-indigo-200 text-indigo-700', 'bg-teal-50 border-teal-200 text-teal-700', 'bg-violet-50 border-violet-200 text-violet-700'][i % 3] },
    mauTieuDeThanhPhan(i) { return ['!text-indigo-500', '!text-teal-500', '!text-violet-500'][i % 3] },
    mauInput(i) { return ['border-indigo-200 focus:ring-indigo-300 focus:border-indigo-400', 'border-teal-200 focus:ring-teal-300 focus:border-teal-400', 'border-violet-200 focus:ring-violet-300 focus:border-violet-400'][i % 3] },
    mauXepLoai(x) { const s = String(x || ''); return !s ? 'bg-slate-50 text-slate-400 border-slate-200' : s.startsWith('A') ? 'bg-emerald-50 text-emerald-700 border-emerald-200' : s.startsWith('B') ? 'bg-blue-50 text-blue-700 border-blue-200' : s.startsWith('C') ? 'bg-amber-50 text-amber-700 border-amber-200' : 'bg-rose-50 text-rose-700 border-rose-200' },
    chuCai(ten) { return (ten || '?').trim().split(/\s+/).slice(-2).map((x) => x[0]).join('').toUpperCase() },
  },
}
</script>
