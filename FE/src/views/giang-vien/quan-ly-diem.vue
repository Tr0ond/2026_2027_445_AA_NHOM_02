<template>
  <div>
    <div class="mb-5 flex items-end justify-between gap-4 flex-wrap">
      <div><div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'giang-vien-trang-chu' }" class="hover:text-teal-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Nhập điểm</span></div><h1 class="text-2xl font-bold text-slate-900">Nhập điểm</h1></div>
      <div class="flex flex-wrap items-center gap-2">
        <a v-if="lopChon" :href="urlXuatDiem" target="_blank" rel="noopener" class="nut-phu text-sm"><i class="fa-solid fa-file-excel text-emerald-600"></i>Xuất bảng điểm</a>
        <button class="flex items-center gap-2 px-4 py-2 bg-teal-600 text-white text-sm font-semibold rounded-xl hover:bg-teal-700 shadow-sm" :disabled="!lopChon" @click="dongBoChuyenCan"><i class="fa-solid fa-rotate"></i>Đồng bộ chuyên cần</button>
      </div>
    </div>

    <section class="the mb-5 p-5" aria-labelledby="tieu-de-bo-loc-diem">
      <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
          <h2 id="tieu-de-bo-loc-diem" class="text-sm font-bold text-slate-800 flex items-center gap-2">
            <i class="fa-solid fa-filter text-teal-600" aria-hidden="true"></i>Bộ lọc lớp học
          </h2>
          <p class="text-xs text-slate-500 mt-1">Chọn lần lượt năm học, học kỳ, môn và lớp cần nhập điểm.</p>
        </div>
        <div class="flex items-center gap-3">
          <span class="text-xs font-semibold text-slate-500">{{ lopTheoBoLoc.length }} lớp phù hợp</span>
          <button v-if="coBoLoc" type="button" class="h-10 px-3 rounded-xl text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors" @click="datLaiBoLoc">
            <i class="fa-solid fa-rotate-left mr-1.5" aria-hidden="true"></i>Đặt lại
          </button>
        </div>
      </div>

      <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <div>
          <label for="loc-nam-hoc" class="block text-xs font-semibold text-slate-600 mb-1.5">Năm học</label>
          <select id="loc-nam-hoc" v-model="boLoc.namHoc" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500" @change="doiNamHoc">
            <option value="">Tất cả năm học</option>
            <option v-for="nam in danhSachNamHoc" :key="nam" :value="nam">{{ nam }}</option>
          </select>
        </div>

        <div>
          <label for="loc-hoc-ky" class="block text-xs font-semibold text-slate-600 mb-1.5">Học kỳ</label>
          <select id="loc-hoc-ky" v-model="boLoc.hocKy" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500" @change="doiHocKy">
            <option value="">Tất cả học kỳ</option>
            <option v-for="hocKy in danhSachHocKy" :key="hocKy" :value="hocKy">{{ nhanHocKy(hocKy) }}</option>
          </select>
        </div>

        <div>
          <label for="loc-mon-hoc" class="block text-xs font-semibold text-slate-600 mb-1.5">Môn học</label>
          <select id="loc-mon-hoc" v-model="boLoc.monHoc" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500" @change="doiMonHoc">
            <option value="">Tất cả môn học</option>
            <option v-for="mon in danhSachMonHoc" :key="mon.giaTri" :value="mon.giaTri">{{ mon.nhan }}</option>
          </select>
        </div>

        <div>
          <label for="loc-lop-hoc" class="block text-xs font-semibold text-slate-600 mb-1.5">Lớp học</label>
          <select id="loc-lop-hoc" v-model.number="lopChon" class="w-full h-11 px-3 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 focus:outline-none focus:ring-2 focus:ring-teal-200 focus:border-teal-500 disabled:bg-slate-100 disabled:text-slate-400" :disabled="!lopTheoBoLoc.length" @change="chonLop(lopChon)">
            <option value="">Chọn lớp cần nhập điểm</option>
            <option v-for="lop in lopTheoBoLoc" :key="lop.id" :value="lop.id">{{ lop.ma_lop_hoc }} — {{ lop.ten_lop }}</option>
          </select>
        </div>
      </div>

      <div v-if="lops.length && !lopTheoBoLoc.length" class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
        Không có lớp phù hợp với bộ lọc hiện tại. Hãy thay đổi điều kiện hoặc đặt lại bộ lọc.
      </div>
      <div v-else-if="!lops.length" class="mt-4 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500">
        Giảng viên chưa được phân công lớp học nào.
      </div>
    </section>

    <div v-if="lopChon" class="space-y-4">
      <div class="flex flex-wrap gap-3"><span v-for="(tp, i) in thanhPhan" :key="tp.id" class="text-xs px-2.5 py-1 rounded-lg border font-medium" :class="mauThanhPhan(i)">{{ tp.ten_thanh_phan }} ({{ dinhDangTrongSo(tp.trong_so) }})</span><span class="text-xs text-slate-400 self-center ml-auto">Thang điểm: 0 – 10</span></div>
      <div class="rounded-xl bg-sky-50 border border-sky-200 px-4 py-3 flex items-start gap-3 text-sm"><span class="w-8 h-8 rounded-lg bg-white text-sky-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-calculator"></i></span><div><p class="font-semibold text-sky-900">Quy tắc điểm chuyên cần theo từng buổi</p><p class="text-xs text-sky-700 mt-0.5">Đạt ít nhất 2/3 phiên: 1 điểm · Vắng có phép: 0,5 điểm · Vắng hoặc không đủ phiên: 0 điểm. Tổng điểm được quy đổi về thang 10.</p></div></div>

      <!-- US18: bảng điểm -->
      <div class="the">
        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center gap-3"><p class="text-xs text-slate-500"><strong class="text-slate-700">{{ lopDangChon?.ma_lop_hoc || lopDangChon?.ten_lop }}</strong> · {{ lopDangChon?.mon_hoc }} · {{ nhanHocKy(lopDangChon?.hoc_ky) }} · Năm học {{ lopDangChon?.nam_hoc }} &nbsp;·&nbsp; {{ danhSach.length }} sinh viên</p><div class="ml-auto flex items-center gap-2"><span class="text-xs text-slate-400">Đã nhập:</span><span class="text-xs font-semibold text-teal-600">{{ soDaNhap }}/{{ danhSach.length }}</span></div></div>
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
      boLoc: {
        namHoc: '',
        hocKy: '',
        monHoc: '',
      },
      thanhPhan: [],
      danhSach: [],
    }
  },
  async created() {
    const { data } = await api.get('/lop-day')
    this.lops = data.danh_sach || []
    if (this.lops.length === 1) {
      const lop = this.lops[0]
      this.boLoc.namHoc = lop.nam_hoc || ''
      this.boLoc.hocKy = lop.hoc_ky || ''
      this.boLoc.monHoc = this.giaTriMon(lop)
      await this.chonLop(lop.id)
    }
  },
  computed: {
    urlXuatDiem() { return `${api.defaults.baseURL}giang-vien/bao-cao/diem/${this.lopChon}/xuat?token=${encodeURIComponent(localStorage.getItem('token') || '')}` },
    lopDangChon() { return this.lops.find((l) => l.id === this.lopChon) },
    danhSachNamHoc() {
      return [...new Set(this.lops.map((lop) => lop.nam_hoc).filter(Boolean))]
        .sort((a, b) => String(b).localeCompare(String(a), 'vi', { numeric: true }))
    },
    lopTheoNamHoc() {
      return this.lops.filter((lop) => !this.boLoc.namHoc || lop.nam_hoc === this.boLoc.namHoc)
    },
    danhSachHocKy() {
      return [...new Set(this.lopTheoNamHoc.map((lop) => lop.hoc_ky).filter(Boolean))]
        .sort((a, b) => String(a).localeCompare(String(b), 'vi', { numeric: true }))
    },
    lopTheoHocKy() {
      return this.lopTheoNamHoc.filter((lop) => !this.boLoc.hocKy || lop.hoc_ky === this.boLoc.hocKy)
    },
    danhSachMonHoc() {
      const cacMon = new Map()
      this.lopTheoHocKy.forEach((lop) => {
        const giaTri = this.giaTriMon(lop)
        if (!giaTri || cacMon.has(giaTri)) return
        cacMon.set(giaTri, {
          giaTri,
          nhan: [lop.ma_mon, lop.mon_hoc].filter(Boolean).join(' — '),
        })
      })
      return [...cacMon.values()].sort((a, b) => a.nhan.localeCompare(b.nhan, 'vi', { numeric: true }))
    },
    lopTheoBoLoc() {
      return this.lopTheoHocKy
        .filter((lop) => !this.boLoc.monHoc || this.giaTriMon(lop) === this.boLoc.monHoc)
        .sort((a, b) => String(a.ma_lop_hoc).localeCompare(String(b.ma_lop_hoc), 'vi', { numeric: true }))
    },
    coBoLoc() { return Boolean(this.boLoc.namHoc || this.boLoc.hocKy || this.boLoc.monHoc || this.lopChon) },
    soDaNhap() { const tp = this.thanhPhan[0]; return tp ? this.danhSach.filter((s) => s.diem[tp.id] !== null && s.diem[tp.id] !== undefined).length : 0 },
    diemTongKetHopLe() { return this.danhSach.map((s) => Number(s.diem_tong_ket)).filter(Number.isFinite) },
    trungBinhLop() { return this.diemTongKetHopLe.length ? (this.diemTongKetHopLe.reduce((a, b) => a + b, 0) / this.diemTongKetHopLe.length).toFixed(2) : '—' },
    diemCaoNhat() { return this.diemTongKetHopLe.length ? Math.max(...this.diemTongKetHopLe).toFixed(1) : '—' },
    diemThapNhat() { return this.diemTongKetHopLe.length ? Math.min(...this.diemTongKetHopLe).toFixed(1) : '—' },
  },
  methods: {
    giaTriMon(lop) { return String(lop.ma_mon || lop.mon_hoc || '') },
    nhanHocKy(hocKy) {
      const giaTri = String(hocKy || '')
      return /^hk/i.test(giaTri) ? giaTri.toUpperCase() : `Học kỳ ${giaTri}`
    },
    xoaLopDangChon() {
      this.lopChon = ''
      this.thanhPhan = []
      this.danhSach = []
    },
    doiNamHoc() {
      this.boLoc.hocKy = ''
      this.boLoc.monHoc = ''
      this.xoaLopDangChon()
    },
    doiHocKy() {
      this.boLoc.monHoc = ''
      this.xoaLopDangChon()
    },
    doiMonHoc() { this.xoaLopDangChon() },
    datLaiBoLoc() {
      this.boLoc = { namHoc: '', hocKy: '', monHoc: '' }
      this.xoaLopDangChon()
    },
    async chonLop(id) {
      if (!id) {
        this.xoaLopDangChon()
        return
      }
      this.lopChon = Number(id)
      await this.taiBangDiem()
    },
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
