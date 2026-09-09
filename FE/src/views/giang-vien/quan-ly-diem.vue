<template>
  <div>
<<<<<<< HEAD
    <div class="tieu-de-trang">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
          <router-link :to="{ name: 'giang-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link>
          <i class="fa-solid fa-chevron-right text-[9px]"></i>
          <span>Quản lý điểm</span>
        </div>
        <h4>Quản lý điểm</h4>
      </div>
    </div>

    <div class="flex items-center gap-3 mb-5">
      <select v-model="lopChon" class="o-nhap !w-auto !py-2" @change="taiDiemLop">
        <option value="">Chọn lớp học</option>
        <option v-for="lop in danhSachLop" :key="lop.id" :value="lop.id">
          {{ lop.mon_hoc }} - {{ lop.ten_lop }}
        </option>
      </select>
      <select v-model="thanhPhanChon" class="o-nhap !w-auto !py-2" @change="taiDiemLop">
        <option value="">Chọn thành phần điểm</option>
        <option v-for="tp in danhSachThanhPhan" :key="tp.id" :value="tp.id">
          {{ tp.ten_thanh_phan }} ({{ (tp.trong_so * 100).toFixed(0) }}%)
        </option>
      </select>
    </div>

    <div v-if="thongBao" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm">
      <i class="fa-solid fa-circle-check mr-2"></i>{{ thongBao }}
    </div>
    <div v-if="loi" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2.5 text-sm">
      <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ loi }}
    </div>

    <div v-if="lopChon && thanhPhanChon && danhSachDiem.length">
      <div class="flex items-center justify-between mb-4">
        <div class="flex items-center gap-2">
          <input v-model="nhapHangLoat" type="checkbox" id="nhapHangLoat" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
          <label for="nhapHangLoat" class="text-sm text-slate-600">Nhập điểm hàng loạt</label>
        </div>
        <button v-if="nhapHangLoat" class="nut-chinh" @click="luuHangLoat" :disabled="dangLuu">
          <i v-if="dangLuu" class="fa-solid fa-spinner fa-spin mr-2"></i>
          {{ dangLuu ? 'Đang lưu...' : 'Lưu tất cả' }}
        </button>
      </div>

      <div class="the overflow-hidden">
=======
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
>>>>>>> e531ad188455fbcb42ccf88394c3b6435af65cda
        <div class="overflow-x-auto">
          <table class="bang">
            <thead>
              <tr>
<<<<<<< HEAD
                <th>MSSV</th>
                <th>Họ tên</th>
                <th>Điểm</th>
                <th v-if="!nhapHangLoat">Thao tác</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="item in danhSachDiem" :key="item.sinh_vien.id">
                <td class="font-mono text-sm">{{ item.sinh_vien.ma_sinh_vien }}</td>
                <td>{{ item.sinh_vien.ho_ten }}</td>
                <td>
                  <input 
                    v-if="nhapHangLoat"
                    v-model.number="item.diem_nhap" 
                    type="number" 
                    min="0" 
                    max="10" 
                    step="0.1"
                    class="o-nhap !w-20 !py-1.5 text-center"
                  >
                  <span v-else class="font-bold" :class="mauDiem(item.diem)">
                    {{ item.diem !== null ? item.diem.toFixed(1) : '—' }}
                  </span>
                </td>
                <td v-if="!nhapHangLoat">
                  <button class="text-brand-600 hover:text-brand-700 text-sm font-medium" @click="suaDiem(item)">
                    <i class="fa-solid fa-pen mr-1"></i>Sửa
                  </button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div v-else-if="lopChon && thanhPhanChon && !danhSachDiem.length" class="the py-16 text-center">
      <i class="fa-solid fa-user-graduate text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Lớp học chưa có sinh viên.</p>
    </div>

    <div v-else class="the py-16 text-center">
      <i class="fa-solid fa-graduation-cap text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Vui lòng chọn lớp học và thành phần điểm.</p>
    </div>

    <!-- Modal sửa điểm -->
    <div v-if="hienModalSua" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="hienModalSua = false">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Sửa điểm</h2>
          <button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="hienModalSua = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="p-5">
          <div class="mb-4">
            <p class="text-xs text-slate-500 mb-1">Sinh viên</p>
            <p class="text-sm font-semibold text-slate-800">{{ itemSua?.sinh_vien?.ho_ten }}</p>
          </div>
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Điểm *</label>
            <input v-model.number="diemSua" type="number" min="0" max="10" step="0.1" class="o-nhap" required>
          </div>
          <div class="flex items-center gap-3">
            <button class="nut-phu flex-1" @click="hienModalSua = false">Hủy</button>
            <button class="nut-chinh flex-1" @click="luuDiem" :disabled="dangLuu">
              <i v-if="dangLuu" class="fa-solid fa-spinner fa-spin mr-2"></i>
              {{ dangLuu ? 'Đang lưu...' : 'Lưu' }}
            </button>
          </div>
        </div>
=======
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
>>>>>>> e531ad188455fbcb42ccf88394c3b6435af65cda
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
<<<<<<< HEAD
  name: 'quan-ly-diem',
  data() {
    return {
      danhSachLop: [],
      danhSachThanhPhan: [],
      danhSachDiem: [],
      lopChon: '',
      thanhPhanChon: '',
      nhapHangLoat: false,
      dangLuu: false,
      thongBao: '',
      loi: '',
      hienModalSua: false,
      itemSua: null,
      diemSua: 0
    }
  },
  async created() {
    await this.taiDanhSachLop()
  },
  methods: {
    async taiDanhSachLop() {
      try {
        const { data } = await api.get('/lop-day')
        this.danhSachLop = data.data || []
      } catch (e) {
        console.error('Lỗi khi tải danh sách lớp:', e)
        this.loi = 'Không tải được danh sách lớp.'
      }
    },
    async taiDiemLop() {
      if (!this.lopChon) {
        this.danhSachThanhPhan = []
        this.danhSachDiem = []
        return
      }

      try {
        const { data } = await api.get(`/diem/lop-hoc/${this.lopChon}`)
        this.danhSachThanhPhan = data.data?.thanh_phans || []
        this.danhSachDiem = (data.data?.sinh_viens || []).map(sv => ({
          sinh_vien: sv.sinh_vien,
          diem_thanh_phan: sv.diem_thanh_phan,
          diem_tong_ket: sv.diem_tong_ket,
          xep_loai: sv.xep_loai,
          diem: null,
          diem_nhap: null
        }))
        this.thanhPhanChon = ''
      } catch (e) {
        console.error('Lỗi khi tải điểm lớp:', e)
        this.loi = e.response?.data?.message || 'Không tải được điểm lớp.'
        this.danhSachThanhPhan = []
        this.danhSachDiem = []
      }
    },
    async taiDiemTheoThanhPhan() {
      if (!this.lopChon || !this.thanhPhanChon) return

      try {
        const { data } = await api.get(`/diem/lop-hoc/${this.lopChon}`)
        this.danhSachDiem = (data.data?.sinh_viens || []).map(sv => {
          const diemTP = sv.diem_thanh_phan.find(tp => tp.thanh_phan_id === this.thanhPhanChon)
          return {
            sinh_vien: sv.sinh_vien,
            diem_thanh_phan: sv.diem_thanh_phan,
            diem_tong_ket: sv.diem_tong_ket,
            xep_loai: sv.xep_loai,
            diem: diemTP?.diem || null,
            diem_nhap: diemTP?.diem || null
          }
        })
      } catch (e) {
        console.error('Lỗi khi tải điểm:', e)
      }
    },
    suaDiem(item) {
      this.itemSua = item
      this.diemSua = item.diem !== null ? item.diem : 0
      this.hienModalSua = true
    },
    async luuDiem() {
      this.dangLuu = true
      this.thongBao = ''
      this.loi = ''
      try {
        const { data } = await api.post('/diem', {
          ma_sinh_vien: this.itemSua.sinh_vien.id,
          ma_thanh_phan: this.thanhPhanChon,
          diem: this.diemSua
        })
        this.thongBao = data.message
        this.hienModalSua = false
        await this.taiDiemTheoThanhPhan()
      } catch (e) {
        console.error('Lỗi lưu điểm:', e)
        this.loi = e.response?.data?.message || 'Lưu điểm thất bại.'
      } finally {
        this.dangLuu = false
      }
    },
    async luuHangLoat() {
      this.dangLuu = true
      this.thongBao = ''
      this.loi = ''
      try {
        const diems = this.danhSachDiem
          .filter(item => item.diem_nhap !== null && item.diem_nhap !== '')
          .map(item => ({
            ma_sinh_vien: item.sinh_vien.id,
            diem: item.diem_nhap
          }))

        if (diems.length === 0) {
          this.loi = 'Vui lòng nhập điểm cho ít nhất một sinh viên.'
          this.dangLuu = false
          return
        }

        const { data } = await api.post('/diem/bulk', {
          ma_thanh_phan: this.thanhPhanChon,
          diems
        })
        this.thongBao = `Đã lưu điểm cho ${diems.length} sinh viên.`
        this.nhapHangLoat = false
        await this.taiDiemTheoThanhPhan()
      } catch (e) {
        console.error('Lỗi lưu hàng loạt:', e)
        this.loi = e.response?.data?.message || 'Lưu điểm hàng loạt thất bại.'
      } finally {
        this.dangLuu = false
      }
    },
    mauDiem(diem) {
      if (diem === null) return 'text-slate-400'
      if (diem >= 8.5) return 'text-emerald-600'
      if (diem >= 7.0) return 'text-sky-600'
      if (diem >= 5.5) return 'text-amber-600'
      if (diem >= 4.0) return 'text-orange-600'
      return 'text-rose-600'
    }
  },
  watch: {
    thanhPhanChon() {
      this.taiDiemTheoThanhPhan()
    }
  }
=======
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
>>>>>>> e531ad188455fbcb42ccf88394c3b6435af65cda
}
</script>
