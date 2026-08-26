<template>
  <div>
    <div class="tieu-de-trang">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
          <router-link :to="{ name: 'sinh-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link>
          <i class="fa-solid fa-chevron-right text-[9px]"></i>
          <span>Đơn xin phép</span>
        </div>
        <h4>Đơn xin phép nghỉ học</h4>
      </div>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5">
      <div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-3">
        <div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen">
          <i :class="th.icon"></i>
        </div>
        <div>
          <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">{{ th.nhan }}</p>
          <p class="text-2xl font-bold text-slate-900">{{ th.giaTri }}</p>
        </div>
      </div>
    </div>

    <div v-if="thongBao" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm">
      <i class="fa-solid fa-circle-check mr-2"></i>{{ thongBao }}
    </div>
    <div v-if="loi" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2.5 text-sm">
      <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ loi }}
    </div>

    <div class="flex items-center justify-between mb-5">
      <div class="flex flex-wrap items-center gap-3">
        <div class="relative flex-1 min-w-[190px] max-w-xs">
          <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
          <input v-model="tuKhoa" class="o-nhap !pl-8 !py-2" placeholder="Tìm môn học...">
        </div>
        <select v-model="locTrangThai" class="o-nhap !w-auto !py-2">
          <option value="">Tất cả trạng thái</option>
          <option value="cho_duyet">Chờ duyệt</option>
          <option value="da_duyet">Đã duyệt</option>
          <option value="tu_choi">Từ chối</option>
        </select>
      </div>
      <button class="nut-chinh" @click="moFormTao">
        <i class="fa-solid fa-plus mr-2"></i>Tạo đơn mới
      </button>
    </div>

    <div v-if="danhSachLoc.length" class="the overflow-hidden">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr>
              <th>Môn học</th>
              <th>Lớp</th>
              <th>Ngày nghỉ</th>
              <th>Lý do</th>
              <th>Trạng thái</th>
              <th>Thao tác</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="don in danhSachLoc" :key="don.id">
              <td>
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center">
                    <i class="fa-solid fa-book text-xs"></i>
                  </div>
                  <span class="font-semibold text-slate-800">{{ don.lop_hoc?.mon_hoc?.ten_mon }}</span>
                </div>
              </td>
              <td>{{ don.lop_hoc?.ten_lop }}</td>
              <td>
                <p class="font-mono text-xs font-semibold text-slate-800">{{ dinhDangNgay(don.ngay_nghi) }}</p>
              </td>
              <td>
                <p class="text-sm text-slate-600 line-clamp-2 max-w-[200px]">{{ don.ly_do }}</p>
              </td>
              <td>
                <span class="nhan border !py-0.5" :class="mauTrangThai(don.trang_thai)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="mauCham(don.trang_thai)"></span>
                  {{ tenTrangThai(don.trang_thai) }}
                </span>
              </td>
              <td>
                <div class="flex items-center gap-2">
                  <button class="text-brand-600 hover:text-brand-700 text-sm font-medium" @click="xemChiTiet(don)">
                    <i class="fa-solid fa-eye mr-1"></i>Chi tiết
                  </button>
                  <button v-if="don.trang_thai === 'cho_duyet'" class="text-rose-600 hover:text-rose-700 text-sm font-medium" @click="huyDon(don)">
                    <i class="fa-solid fa-trash mr-1"></i>Hủy
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="the py-16 text-center">
      <i class="fa-solid fa-file-circle-question text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Chưa có đơn xin phép nào.</p>
    </div>

    <!-- Modal chi tiết đơn -->
    <div v-if="donChon" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="donChon = null">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Chi tiết đơn xin phép</h2>
          <button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="donChon = null">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="p-5">
          <div v-if="dangTaiChiTiet" class="flex items-center justify-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-brand-600 text-2xl"></i>
          </div>
          <div v-else>
            <div class="space-y-4">
              <div v-if="donChon.lop_hoc">
                <p class="text-xs text-slate-500 mb-1">Môn học</p>
                <p class="text-sm font-semibold text-slate-800">{{ donChon.lop_hoc.mon_hoc?.ten_mon }}</p>
              </div>
              <div v-if="donChon.lop_hoc">
                <p class="text-xs text-slate-500 mb-1">Lớp học</p>
                <p class="text-sm font-semibold text-slate-800">{{ donChon.lop_hoc.ten_lop }}</p>
              </div>
              <div v-if="donChon.lich_hoc">
                <p class="text-xs text-slate-500 mb-1">Buổi học</p>
                <p class="text-sm font-semibold text-slate-800">
                  {{ dinhDangNgay(donChon.lich_hoc.ngay_hoc) }} · {{ donChon.lich_hoc.gio_bat_dau }} - {{ donChon.lich_hoc.gio_ket_thuc }}
                </p>
              </div>
              <div>
                <p class="text-xs text-slate-500 mb-1">Ngày nghỉ</p>
                <p class="text-sm font-semibold text-slate-800">{{ dinhDangNgay(donChon.ngay_nghi) }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500 mb-1">Lý do</p>
                <p class="text-sm text-slate-600 bg-slate-50 rounded-lg p-3">{{ donChon.ly_do }}</p>
              </div>
              <div>
                <p class="text-xs text-slate-500 mb-1">Trạng thái</p>
                <span class="nhan border !py-0.5" :class="mauTrangThai(donChon.trang_thai)">
                  <span class="w-1.5 h-1.5 rounded-full" :class="mauCham(donChon.trang_thai)"></span>
                  {{ tenTrangThai(donChon.trang_thai) }}
                </span>
              </div>
              <div v-if="donChon.nguoi_duyet">
                <p class="text-xs text-slate-500 mb-1">Người duyệt</p>
                <p class="text-sm font-semibold text-slate-800">{{ donChon.nguoi_duyet?.ho_ten }}</p>
              </div>
              <div v-if="donChon.thoi_gian_duyet">
                <p class="text-xs text-slate-500 mb-1">Thời gian duyệt</p>
                <p class="text-sm font-semibold text-slate-800">{{ dinhDangThoiGian(donChon.thoi_gian_duyet) }}</p>
              </div>
            </div>
            <div class="flex items-center gap-3 mt-6">
              <button class="nut-phu flex-1" @click="donChon = null">Đóng</button>
              <button v-if="donChon.trang_thai === 'cho_duyet'" class="nut-nguy-hiem flex-1" @click="huyDonTuChiTiet">
                <i class="fa-solid fa-trash mr-2"></i>Hủy đơn
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Modal tạo đơn -->
    <div v-if="hienFormTao" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="hienFormTao = false">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Tạo đơn xin phép nghỉ học</h2>
          <button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="hienFormTao = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="p-5">
          <div v-if="dangTaiLop" class="flex items-center justify-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-brand-600 text-2xl"></i>
          </div>
          <form v-else @submit.prevent="guiDon">
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Lớp học *</label>
                <select v-model="form.ma_lop_hoc" class="o-nhap" required @change="taiLichHoc">
                  <option value="">Chọn lớp học</option>
                  <option v-for="lop in danhSachLop" :key="lop.id" :value="lop.id">
                    {{ lop.mon_hoc }} - {{ lop.ten_lop }}
                  </option>
                </select>
              </div>
              <div v-if="form.ma_lop_hoc">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Buổi học *</label>
                <select v-model="form.ma_lich_hoc" class="o-nhap" required @change="onChonLichHoc">
                  <option value="">Chọn buổi học</option>
                  <option v-for="lich in danhSachLich" :key="lich.id" :value="lich.id">
                    {{ dinhDangNgay(lich.ngay_hoc) }} - {{ lich.gio_bat_dau }} đến {{ lich.gio_ket_thuc }}
                  </option>
                </select>
              </div>
              <div v-if="form.ma_lich_hoc">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Ngày nghỉ *</label>
                <input v-model="form.ngay_nghi" type="date" class="o-nhap" required readonly>
                <p class="text-xs text-slate-400 mt-1">Ngày nghỉ tự động theo buổi học đã chọn</p>
              </div>
              <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5">Lý do *</label>
                <textarea v-model="form.ly_do" class="o-nhap" rows="3" required maxlength="500" placeholder="Nhập lý do xin nghỉ..."></textarea>
                <p class="text-xs text-slate-400 mt-1">{{ form.ly_do.length }}/500 ký tự</p>
              </div>
            </div>
            <div class="flex items-center gap-3 mt-6">
              <button type="button" class="nut-phu flex-1" @click="hienFormTao = false">Hủy</button>
              <button type="submit" class="nut-chinh flex-1" :disabled="dangGui">
                <i v-if="dangGui" class="fa-solid fa-spinner fa-spin mr-2"></i>
                {{ dangGui ? 'Đang gửi...' : 'Gửi đơn' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'don-xin-phep',
  data() {
    return {
      danhSach: [],
      danhSachLop: [],
      danhSachLich: [],
      tuKhoa: '',
      locTrangThai: '',
      thongBao: '',
      loi: '',
      hienFormTao: false,
      donChon: null,
      dangTaiLop: false,
      dangTaiChiTiet: false,
      dangGui: false,
      form: {
        ma_lop_hoc: '',
        ma_lich_hoc: '',
        ngay_nghi: '',
        ly_do: ''
      }
    }
  },
  computed: {
    danhSachLoc() {
      const q = this.tuKhoa.trim().toLowerCase()
      return this.danhSach.filter((d) => {
        const dungQ = !q || `${d.lop_hoc?.mon_hoc?.ten_mon} ${d.lop_hoc?.ten_lop}`.toLowerCase().includes(q)
        const dungTT = !this.locTrangThai || d.trang_thai === this.locTrangThai
        return dungQ && dungTT
      })
    },
    ngayMin() {
      const homNay = new Date()
      homNay.setDate(homNay.getDate() + 1)
      return homNay.toISOString().split('T')[0]
    },
    thongKe() {
      return [
        { icon: 'fa-solid fa-file-lines', nen: 'bg-brand-50 text-brand-600', nhan: 'Tổng đơn', giaTri: this.danhSach.length },
        { icon: 'fa-solid fa-clock', nen: 'bg-amber-50 text-amber-600', nhan: 'Chờ duyệt', giaTri: this.danhSach.filter((d) => d.trang_thai === 'cho_duyet').length },
        { icon: 'fa-solid fa-circle-check', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Đã duyệt', giaTri: this.danhSach.filter((d) => d.trang_thai === 'da_duyet').length },
        { icon: 'fa-solid fa-circle-xmark', nen: 'bg-rose-50 text-rose-600', nhan: 'Từ chối', giaTri: this.danhSach.filter((d) => d.trang_thai === 'tu_choi').length },
      ]
    }
  },
  async created() {
    await Promise.all([this.taiDanhSach(), this.taiDanhSachLop()])
  },
  methods: {
    async taiDanhSach() {
      try {
        const { data } = await api.get('/sinh-vien/don-xin-phep')
        this.danhSach = data.data || []
      } catch (e) {
        console.error('Lỗi khi tải danh sách đơn:', e)
      }
    },
    async taiDanhSachLop() {
      this.dangTaiLop = true
      try {
        const { data } = await api.get('/sinh-vien/lop-cua-toi')
        this.danhSachLop = data.danh_sach || []
      } catch (e) {
        console.error('Lỗi khi tải danh sách lớp:', e)
      } finally {
        this.dangTaiLop = false
      }
    },
    async taiLichHoc() {
      if (!this.form.ma_lop_hoc) {
        this.danhSachLich = []
        return
      }
      try {
        const { data } = await api.get('/lich-hoc')
        // Filter schedules by class ID on client side
        this.danhSachLich = (data.danh_sach || []).filter(l => l.ma_lop_hoc === this.form.ma_lop_hoc)
        this.form.ma_lich_hoc = ''
        this.form.ngay_nghi = ''
      } catch (e) {
        console.error('Lỗi khi tải lịch học:', e)
        this.danhSachLich = []
      }
    },
    onChonLichHoc() {
      const lichChon = this.danhSachLich.find(l => l.id === this.form.ma_lich_hoc)
      if (lichChon) {
        this.form.ngay_nghi = lichChon.ngay_hoc
      }
    },
    moFormTao() {
      this.form = {
        ma_lop_hoc: '',
        ma_lich_hoc: '',
        ngay_nghi: '',
        ly_do: ''
      }
      this.danhSachLich = []
      this.thongBao = ''
      this.loi = ''
      this.hienFormTao = true
    },
    async guiDon() {
      this.dangGui = true
      this.thongBao = ''
      this.loi = ''
      try {
        const { data } = await api.post('/sinh-vien/don-xin-phep', this.form)
        this.thongBao = data.message
        this.hienFormTao = false
        await this.taiDanhSach()
      } catch (e) {
        console.error('Lỗi gửi đơn:', e.response?.data)
        this.loi = e.response?.data?.message || JSON.stringify(e.response?.data) || 'Gửi đơn thất bại.'
      } finally {
        this.dangGui = false
      }
    },
    async xemChiTiet(don) {
      this.donChon = don
      this.dangTaiChiTiet = true
      try {
        const { data } = await api.get(`/sinh-vien/don-xin-phep/${don.id}`)
        this.donChon = data.data || don
      } catch (e) {
        console.error('Lỗi khi tải chi tiết đơn:', e)
        this.loi = e.response?.data?.message || 'Không tải được chi tiết đơn.'
      } finally {
        this.dangTaiChiTiet = false
      }
    },
    async huyDon(don) {
      if (!confirm('Bạn có chắc muốn hủy đơn này?')) return
      try {
        const { data } = await api.delete(`/sinh-vien/don-xin-phep/${don.id}`)
        this.thongBao = data.message
        this.donChon = null
        await this.taiDanhSach()
      } catch (e) {
        this.loi = e.response?.data?.message || 'Hủy đơn thất bại.'
      }
    },
    huyDonTuChiTiet() {
      if (this.donChon) {
        this.huyDon(this.donChon)
      }
    },
    dinhDangNgay(n) {
      return new Date(n).toLocaleDateString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric' })
    },
    dinhDangThoiGian(t) {
      return new Date(t).toLocaleString('vi-VN', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' })
    },
    tenTrangThai(t) {
      return { cho_duyet: 'Chờ duyệt', da_duyet: 'Đã duyệt', tu_choi: 'Từ chối' }[t] || t
    },
    mauTrangThai(t) {
      return {
        cho_duyet: 'bg-amber-50 text-amber-700 border-amber-200',
        da_duyet: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        tu_choi: 'bg-rose-50 text-rose-700 border-rose-200',
      }[t] || 'bg-slate-100 text-slate-600 border-slate-200'
    },
    mauCham(t) {
      return { cho_duyet: 'bg-amber-500', da_duyet: 'bg-emerald-500', tu_choi: 'bg-rose-500' }[t] || 'bg-slate-400'
    }
  }
}
</script>
