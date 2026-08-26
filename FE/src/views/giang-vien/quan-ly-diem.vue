<template>
  <div>
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
        <div class="overflow-x-auto">
          <table class="bang">
            <thead>
              <tr>
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
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
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
}
</script>
