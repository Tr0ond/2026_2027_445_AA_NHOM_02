<template>
  <div>
    <div class="tieu-de-trang">
      <div>
        <div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1">
          <router-link :to="{ name: 'sinh-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link>
          <i class="fa-solid fa-chevron-right text-[9px]"></i>
          <span>Kết quả học tập</span>
        </div>
        <h4>Kết quả học tập</h4>
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

    <div class="flex flex-wrap items-center gap-3 mb-5">
      <div class="relative flex-1 min-w-[190px] max-w-xs">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
        <input v-model="tuKhoa" class="o-nhap !pl-8 !py-2" placeholder="Tìm môn học hoặc lớp...">
      </div>
      <select v-model="locHocKy" class="o-nhap !w-auto !py-2">
        <option value="">Tất cả học kỳ</option>
        <option v-for="hk in danhSachHocKy" :key="hk" :value="hk">{{ hk }}</option>
      </select>
    </div>

    <div v-if="danhSachLoc.length" class="the overflow-hidden mb-5">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr>
              <th>Môn học</th>
              <th>Lớp</th>
              <th>Học kỳ</th>
              <th>Điểm tổng kết</th>
              <th>Xếp loại</th>
              <th>Chi tiết</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="d in danhSachLoc" :key="d.id">
              <td>
                <div class="flex items-center gap-2.5">
                  <div class="w-7 h-7 rounded-lg bg-brand-100 text-brand-600 flex items-center justify-center">
                    <i class="fa-solid fa-book text-xs"></i>
                  </div>
                  <div>
                    <span class="font-semibold text-slate-800">{{ d.mon_hoc }}</span>
                    <p class="text-xs text-slate-400">{{ d.ma_mon_hoc }}</p>
                  </div>
                </div>
              </td>
              <td>{{ d.ten_lop }}</td>
              <td>{{ d.hoc_ky }} {{ d.nam_hoc }}</td>
              <td>
                <span v-if="d.diem_tong_ket !== null" class="font-bold text-lg" :class="mauDiem(d.diem_tong_ket)">
                  {{ d.diem_tong_ket.toFixed(1) }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td>
                <span v-if="d.xep_loai" class="nhan border !py-0.5" :class="mauXepLoai(d.xep_loai)">
                  {{ d.xep_loai }}
                </span>
                <span v-else class="text-slate-400">—</span>
              </td>
              <td>
                <button class="text-brand-600 hover:text-brand-700 text-sm font-medium" @click="xemChiTiet(d)">
                  <i class="fa-solid fa-eye mr-1"></i>Xem
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-else class="the py-16 text-center">
      <i class="fa-solid fa-graduation-cap text-slate-200 text-3xl"></i>
      <p class="text-sm text-slate-400 mt-2">Chưa có kết quả học tập.</p>
    </div>

    <!-- Modal chi tiết điểm -->
    <div v-if="lopChon" class="fixed inset-0 z-50 flex items-center justify-center p-4" @click.self="lopChon = null">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden max-h-[90vh]">
        <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
          <h2 class="font-bold text-slate-900">Chi tiết điểm - {{ lopChon.mon_hoc }}</h2>
          <button class="w-8 h-8 rounded-lg text-slate-400 hover:bg-slate-100" @click="lopChon = null">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <div class="p-5">
          <div v-if="dangTaiChiTiet" class="flex items-center justify-center py-8">
            <i class="fa-solid fa-spinner fa-spin text-brand-600 text-2xl"></i>
          </div>
          <div v-else>
            <div class="grid grid-cols-2 gap-3 mb-5">
              <div class="bg-slate-50 rounded-xl p-3">
                <p class="text-xs text-slate-500 mb-1">Lớp học</p>
                <p class="text-sm font-bold text-slate-800">{{ lopChon.ten_lop }}</p>
              </div>
              <div class="bg-slate-50 rounded-xl p-3">
                <p class="text-xs text-slate-500 mb-1">Học kỳ</p>
                <p class="text-sm font-bold text-slate-800">{{ lopChon.hoc_ky }} {{ lopChon.nam_hoc }}</p>
              </div>
            </div>

            <div class="the overflow-hidden mb-5">
              <table class="bang">
                <thead>
                  <tr>
                    <th>Thành phần</th>
                    <th>Trọng số</th>
                    <th>Điểm</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="item in chiTietDiem.diem_thanh_phan" :key="item.thanh_phan.id">
                    <td>{{ item.thanh_phan.ten_thanh_phan }}</td>
                    <td>{{ (item.thanh_phan.trong_so * 100).toFixed(0) }}%</td>
                    <td>
                      <span v-if="item.da_co_diem" class="font-bold" :class="mauDiem(item.diem)">
                        {{ item.diem.toFixed(1) }}
                      </span>
                      <span v-else class="text-slate-400">—</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div class="bg-brand-50 border border-brand-200 rounded-xl p-4">
              <div class="flex items-center justify-between">
                <div>
                  <p class="text-xs text-brand-600 font-medium uppercase tracking-wide">Điểm tổng kết</p>
                  <p v-if="chiTietDiem.diem_tong_ket !== null" class="text-3xl font-bold text-brand-700">
                    {{ chiTietDiem.diem_tong_ket.toFixed(1) }}
                  </p>
                  <p v-else class="text-3xl font-bold text-brand-400">—</p>
                </div>
                <div v-if="chiTietDiem.xep_loai" class="nhan bg-brand-600 text-white border border-brand-700 !py-2 !px-4 text-lg">
                  {{ chiTietDiem.xep_loai }}
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'xem-diem',
  data() {
    return {
      danhSach: [],
      tuKhoa: '',
      locHocKy: '',
      lopChon: null,
      dangTaiChiTiet: false,
      chiTietDiem: {
        diem_thanh_phan: [],
        diem_tong_ket: null,
        xep_loai: null
      }
    }
  },
  computed: {
    danhSachLoc() {
      const q = this.tuKhoa.trim().toLowerCase()
      return this.danhSach.filter((d) => {
        const dungQ = !q || `${d.mon_hoc} ${d.ten_lop} ${d.ma_mon_hoc}`.toLowerCase().includes(q)
        const dungHK = !this.locHocKy || d.hoc_ky === this.locHocKy
        return dungQ && dungHK
      })
    },
    danhSachHocKy() {
      return [...new Set(this.danhSach.map((d) => d.hoc_ky))].sort()
    },
    thongKe() {
      const coDiem = this.danhSach.filter((d) => d.diem_tong_ket !== null)
      const dtb = coDiem.length ? coDiem.reduce((sum, d) => sum + d.diem_tong_ket, 0) / coDiem.length : 0
      const soA = coDiem.filter((d) => d.xep_loai === 'A').length
      const soF = coDiem.filter((d) => d.xep_loai === 'F').length
      
      return [
        { icon: 'fa-solid fa-book', nen: 'bg-brand-50 text-brand-600', nhan: 'Tổng môn', giaTri: this.danhSach.length },
        { icon: 'fa-solid fa-chart-line', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Điểm TB', giaTri: dtb.toFixed(1) },
        { icon: 'fa-solid fa-star', nen: 'bg-amber-50 text-amber-600', nhan: 'Loại A', giaTri: soA },
        { icon: 'fa-solid fa-circle-xmark', nen: 'bg-rose-50 text-rose-600', nhan: 'Loại F', giaTri: soF },
      ]
    }
  },
  async created() {
    await this.taiDanhSach()
  },
  methods: {
    async taiDanhSach() {
      try {
        const { data } = await api.get('/sinh-vien/diem')
        const rawData = data.data || []
        const uniqueClasses = new Map()
        
        rawData.forEach(d => {
          const lopHoc = d.thanh_phan?.lop_hoc
          if (!lopHoc?.id) return
          
          if (!uniqueClasses.has(lopHoc.id)) {
            uniqueClasses.set(lopHoc.id, {
              id: lopHoc.id,
              mon_hoc: lopHoc.mon_hoc?.ten_mon || lopHoc.ten_lop,
              ma_mon_hoc: lopHoc.mon_hoc?.ma_mon_hoc,
              ten_lop: lopHoc.ten_lop,
              hoc_ky: lopHoc.hoc_ky,
              nam_hoc: lopHoc.nam_hoc,
              diem_tong_ket: null,
              xep_loai: null
            })
          }
        })
        
        this.danhSach = Array.from(uniqueClasses.values())
        
        for (const lop of this.danhSach) {
          try {
            const { data: gradeData } = await api.get(`/sinh-vien/diem/lop-hoc/${lop.id}`)
            lop.diem_tong_ket = gradeData.data?.diem_tong_ket
            lop.xep_loai = gradeData.data?.xep_loai
          } catch (e) {
            console.error(`Lỗi khi tải điểm lớp ${lop.id}:`, e)
          }
        }
      } catch (e) {
        console.error('Lỗi khi tải danh sách điểm:', e)
      }
    },
    async xemChiTiet(lop) {
      this.lopChon = lop
      this.dangTaiChiTiet = true
      try {
        const { data } = await api.get(`/sinh-vien/diem/lop-hoc/${lop.id}`)
        this.chiTietDiem = data.data || { diem_thanh_phan: [], diem_tong_ket: null, xep_loai: null }
      } catch (e) {
        console.error('Lỗi khi tải chi tiết điểm:', e)
        this.chiTietDiem = { diem_thanh_phan: [], diem_tong_ket: null, xep_loai: null }
      } finally {
        this.dangTaiChiTiet = false
      }
    },
    mauDiem(diem) {
      if (diem >= 8.5) return 'text-emerald-600'
      if (diem >= 7.0) return 'text-sky-600'
      if (diem >= 5.5) return 'text-amber-600'
      if (diem >= 4.0) return 'text-orange-600'
      return 'text-rose-600'
    },
    mauXepLoai(xepLoai) {
      return {
        A: 'bg-emerald-50 text-emerald-700 border-emerald-200',
        B: 'bg-sky-50 text-sky-700 border-sky-200',
        C: 'bg-amber-50 text-amber-700 border-amber-200',
        D: 'bg-orange-50 text-orange-700 border-orange-200',
        F: 'bg-rose-50 text-rose-700 border-rose-200',
      }[xepLoai] || 'bg-slate-100 text-slate-600 border-slate-200'
    }
  }
}
</script>
