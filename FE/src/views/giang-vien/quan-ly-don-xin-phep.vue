<template>
  <div>
    <div class="tieu-de-trang">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
          <router-link :to="{ name: 'giang-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link>
          <i class="fa-solid fa-chevron-right text-[9px]"></i>
          <span>Quản lý đơn xin phép</span>
        </div>
        <h4>Quản lý đơn xin phép</h4>
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

    <div v-if="thongBao"
         class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm">
      <i class="fa-solid fa-circle-check mr-2"></i>{{ thongBao }}
    </div>
    <div v-if="loi" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2.5 text-sm">
      <i class="fa-solid fa-circle-exclamation mr-2"></i>{{ loi }}
    </div>

    <div class="flex items-center gap-33 mb-5">
      <select v-model="lopChon" class="o-nhap !w-auto !py-2" @change="taiDonTheoLop">
        <option value="">Chọn lớp học</option>
        <option v-for="lop in danhSachLop" :key="lop.id" :value="lop.id">
          {{ lop.mon_hoc }} - {{ lop.ten_lop }}
        </option>
      </select>
      <select v-model="locTrangThai" class="o-nhap !w-auto !py-2">
        <option value="">Tất cả trạng thái</option>
        <option value="cho_duyet">Chờ duyệt</option>
        <option value="da_duyet">Đã duyệt</option>
        <option value="tu_choi">Từ chối</option>
      </select>
      <div v-if="danhSachLoc.length > 0" class="flex items-center gap-2 ml-auto">
        <input v-model="xuLyHangLoat" type="checkbox" id="xuLyHangLoat"
               class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
        <label for="xuLyHangLoat" class="text-sm text-slate-600">Xử lý hàng loạt</label>
      </div>
    </div>

    <div v-if="lopChon && danhSachLoc.length" class="the overflow-hidden">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
          <tr>
            <th v-if="xuLyHangLoat">
              <input v-model="chonTatCa" type="checkbox"
                     class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            </th>
            <th>Sinh viên</th>
            <th>Môn học</th>
            <th>Ngày nghỉ</th>
            <th>Lý do</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
          </thead>
          <tbody>
          <tr v-for="don in danhSachLoc" :key="don.id">
            <td v-if="xuLyHangLoat">
              <input v-model="don.chon" type="checkbox"
                     class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
            </td>
            <td>
              <div class="flex items-center gap-2.5">
                <div class="w-7 h-7 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center">
                  <i class="fa-solid fa-user text-xs"></i>
                </div>
                <div>
                  <span class="font-semibold text-slate-800">{{ don.sinh_vien?.ho_ten }}</span>
                  <p class="text-xs text-slate-400">{{ don.sinh_vien?.ma_sinh_vien }}</p>
                </div>
              </div>
            </td>
            <td>{{ don.lop_hoc?.mon_hoc?.ten_mon }}</td>
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
              <div v-if="!xuLyHangLoat" class="flex items-center gap-2">
                <button v-if="don.trang_thai === 'cho_duyet'"
                        class="text-emerald-600 hover:text-emerald-700 text-sm font-medium" @click="duyetDon(don)">
                  <i class="fa-solid fa-check mr-1"></i>Duyệt
                </button>
                <button v-if="don.trang_thai === 'cho_duyet'"
                        class="text-rose-600 hover:text-rose-700 text-sm font-medium" @click="moModalTuChoi(don)">
                  <i class="fa-solid fa-xmark mr-1"></i>Từ chối
                </button>
                <button v-else class="text-slate-400 text-sm font-medium cursor-not-allowed" disabled>
                  —
                </button>
              </div>
            </td>
          </tr>
          </tbody>
        </table>
      </div>

      <div v-if="xuLyHangLoat && soDonChon > 0" class="flex items-center gap-3 p-4 border-t border-slate-100">
        <span class="text-sm text-slate-600">Đã chọn {{ soDonChon }} đơn</span>
        <button class="nut-chinh" @click="xuLyHangLoatDuyet" :disabled="dangXuLy">
          <i v-if="dangXuLy" class="fa-solid fa-spinner fa-spin mr-2"></i>
          {{ dangXuLy ? 'Đang xử lý...' : 'Duyệt tất cả' }}
        </button>
        <button class="nut-nguy-hiem" @click="moModalTuChoiHangLoat" :disabled="dangXuLy">
          <i v-if="dangXuLy" class="fa-solid fa-spinner fa-spin mr-2"></i>
          {{ dangXuLy ? 'Đang xử lý...' : 'Từ chối tất cả' }}
        </button>
      </div>
    </div>

    <div v-else-if="lopChon && !danhSachLoc.length" class="the py-16 text-center">
      <i class="fa-solid fa-file-circle-question text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Lớp học chưa có đơn xin phép nào.</p>
    </div>

    <div v-else class="the py-16 text-center">
      <i class="fa-solid fa-graduation-cap text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Vui lòng chọn lớp học để xem đơn xin phép.</p>
    </div>

    <!-- Modal từ chối đơn -->
    <div v-if="hienModalTuChoi" class="fixed inset-0 z-50 flex items-center justify-center p-4"
         @click.self="hienModalTuChoi = false">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Từ chối đơn xin phép</h2>
          <button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="hienModalTuChoi = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="p-5">
          <div class="mb-4">
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Lý do từ chối</label>
            <textarea v-model="lyDoTuChoi" class="o-nhap" rows="3" maxlength="500"
                      placeholder="Nhập lý do từ chối..."></textarea>
            <p class="text-xs text-slate-400 mt-1">{{ lyDoTuChoi.length }}/500 ký tự</p>
          </div>
          <div class="flex items-center gap-3">
            <button class="nut-phu flex-1" @click="hienModalTuChoi = false">Hủy</button>
            <button class="nut-nguy-hiem flex-1" @click="tuChoiDon" :disabled="dangXuLy">
              <i v-if="dangXuLy" class="fa-solid fa-spinner fa-spin mr-2"></i>
              {{ dangXuLy ? 'Đang xử lý...' : 'Từ chối' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'quan-ly-don-xin-phep',
  data() {
    return {
      danhSachLop: [],
      danhSach: [],
      lopChon: '',
      locTrangThai: '',
      xuLyHangLoat: false,
      dangXuLy: false,
      thongBao: '',
      loi: '',
      hienModalTuChoi: false,
      donTuChoi: null,
      lyDoTuChoi: '',
      laHangLoat: false
    }
  },
  computed: {
    danhSachLoc() {
      return this.danhSach.filter((d) => {
        const dungTT = !this.locTrangThai || d.trang_thai === this.locTrangThai
        return dungTT
      })
    },
    chonTatCa: {
      get() {
        return this.danhSachLoc.length > 0 && this.danhSachLoc.every(d => d.chon)
      },
      set(value) {
        this.danhSachLoc.forEach(d => d.chon = value)
      }
    },
    soDonChon() {
      return this.danhSachLoc.filter(d => d.chon).length
    },
    thongKe() {
      return [
        {
          icon: 'fa-solid fa-file-lines',
          nen: 'bg-brand-50 text-brand-600',
          nhan: 'Tổng đơn',
          giaTri: this.danhSach.length
        },
        {
          icon: 'fa-solid fa-clock',
          nen: 'bg-amber-50 text-amber-600',
          nhan: 'Chờ duyệt',
          giaTri: this.danhSach.filter((d) => d.trang_thai === 'cho_duyet').length
        },
        {
          icon: 'fa-solid fa-circle-check',
          nen: 'bg-emerald-50 text-emerald-600',
          nhan: 'Đã duyệt',
          giaTri: this.danhSach.filter((d) => d.trang_thai === 'da_duyet').length
        },
        {
          icon: 'fa-solid fa-circle-xmark',
          nen: 'bg-rose-50 text-rose-600',
          nhan: 'Từ chối',
          giaTri: this.danhSach.filter((d) => d.trang_thai === 'tu_choi').length
        },
      ]
    }
  },
  async created() {
    await this.taiDanhSachLop()
  },
  methods: {
    async taiDanhSachLop() {
      try {
        const {data} = await api.get('/lop-day')
        this.danhSachLop = data.data || []
      } catch (e) {
        console.error('Lỗi khi tải danh sách lớp:', e)
        this.loi = 'Không tải được danh sách lớp.'
      }
    },
    async taiDonTheoLop() {
      if (!this.lopChon) {
        this.danhSach = []
        return
      }

      try {
        const {data} = await api.get('/don-xin-phep/lop-hoc', {
          params: {ma_lop_hoc: this.lopChon}
        })
        this.danhSach = (data.data || []).map(d => ({...d, chon: false}))
      } catch (e) {
        console.error('Lỗi khi tải đơn:', e)
        this.loi = e.response?.data?.message || 'Không tải được danh sách đơn.'
        this.danhSach = []
      }
    },
    async duyetDon(don) {
      if (!confirm('Bạn có chắc muốn duyệt đơn này?')) return
      this.dangXuLy = true
      this.thongBao = ''
      this.loi = ''
      try {
        const {data} = await api.post(`/don-xin-phep/${don.id}/duyet`)
        this.thongBao = data.message
        await this.taiDonTheoLop()
      } catch (e) {
        console.error('Lỗi duyệt đơn:', e)
        this.loi = e.response?.data?.message || 'Duyệt đơn thất bại.'
      } finally {
        this.dangXuLy = false
      }
    },
    moModalTuChoi(don) {
      this.donTuChoi = don
      this.lyDoTuChoi = ''
      this.laHangLoat = false
      this.hienModalTuChoi = true
    },
    moModalTuChoiHangLoat() {
      this.donTuChoi = null
      this.lyDoTuChoi = ''
      this.laHangLoat = true
      this.hienModalTuChoi = true
    },
    async tuChoiDon() {
      this.dangXuLy = true
      this.thongBao = ''
      this.loi = ''
      try {
        if (this.laHangLoat) {
          await this.xuLyHangLoatTuChoi()
        } else {
          const {data} = await api.post(`/don-xin-phep/${this.donTuChoi.id}/tu-choi`, {
            ly_do_tu_choi: this.lyDoTuChoi
          })
          this.thongBao = data.message
          this.hienModalTuChoi = false
          await this.taiDonTheoLop()
        }
      } catch (e) {
        console.error('Lỗi từ chối đơn:', e)
        this.loi = e.response?.data?.message || 'Từ chối đơn thất bại.'
      } finally {
        this.dangXuLy = false
      }
    },
    async xuLyHangLoatDuyet() {
      const donsChon = this.danhSachLoc.filter(d => d.chon && d.trang_thai === 'cho_duyet')
      if (donsChon.length === 0) {
        this.loi = 'Vui lòng chọn ít nhất một đơn đang chờ duyệt.'
        return
      }

      this.dangXuLy = true
      this.thongBao = ''
      this.loi = ''
      try {
        const {data} = await api.post('/don-xin-phep/xu-ly-hang-loat', {
          ma_don: donsChon.map(d => d.id),
          hanh_dong: 'duyet'
        })
        this.thongBao = `Đã duyệt ${donsChon.length} đơn.`
        this.xuLyHangLoat = false
        await this.taiDonTheoLop()
      } catch (e) {
        console.error('Lỗi xử lý hàng loạt:', e)
        this.loi = e.response?.data?.message || 'Xử lý hàng loạt thất bại.'
      } finally {
        this.dangXuLy = false
      }
    },
    async xuLyHangLoatTuChoi() {
      const donsChon = this.danhSachLoc.filter(d => d.chon && d.trang_thai === 'cho_duyet')
      if (donsChon.length === 0) {
        this.loi = 'Vui lòng chọn ít nhất một đơn đang chờ duyệt.'
        return
      }

      this.dangXuLy = true
      this.thongBao = ''
      this.loi = ''
      try {
        const {data} = await api.post('/don-xin-phep/xu-ly-hang-loat', {
          ma_don: donsChon.map(d => d.id),
          hanh_dong: 'tu_choi',
          ly_do_tu_choi: this.lyDoTuChoi
        })
        this.thongBao = `Đã từ chối ${donsChon.length} đơn.`
        this.hienModalTuChoi = false
        this.xuLyHangLoat = false
        await this.taiDonTheoLop()
      } catch (e) {
        console.error('Lỗi xử lý hàng loạt:', e)
        this.loi = e.response?.data?.message || 'Xử lý hàng loạt thất bại.'
      } finally {
        this.dangXuLy = false
      }
    },
    dinhDangNgay(n) {
      return new Date(n).toLocaleDateString('vi-VN', {day: '2-digit', month: '2-digit', year: 'numeric'})
    },
    tenTrangThai(t) {
      return {cho_duyet: 'Chờ duyệt', da_duyet: 'Đã duyệt', tu_choi: 'Từ chối'}[t] || t
    },
    mauTrangThai(t) {
      return {
        cho_duyet: 'bg-amber-50 text-amber-700 border-amber-200',
        da_duyet: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        tu_choi: 'bg-rose-50 text-rose-700 border-rose-200',
      }[t] || 'bg-slate-100 text-slate-600 border-slate-200'
    },
    mauCham(t) {
      return {cho_duyet: 'bg-amber-500', da_duyet: 'bg-emerald-500', tu_choi: 'bg-rose-500'}[t] || 'bg-slate-400'
    }
  }
}
</script>
