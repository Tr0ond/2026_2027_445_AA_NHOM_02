<template>
  <div>
    <div class="mb-5 flex flex-wrap items-end justify-between gap-3">
      <div>
        <div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5">
          <router-link :to="{ name: 'giang-vien-trang-chu' }" class="hover:text-teal-600">Tổng quan</router-link>
          <i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Quản lý điểm danh</span>
        </div>
        <h1 class="text-2xl font-bold text-slate-900">Quản lý điểm danh</h1>
        <p class="text-sm text-slate-500 mt-1">Chọn lớp và ngày học để xem hoặc điều chỉnh trạng thái sinh viên.</p>
      </div>
      <span v-if="duLieu.lich_hoc" class="nhan bg-teal-50 text-teal-700 border border-teal-200">
        <i class="fa-regular fa-calendar-check"></i>{{ dinhDangNgay(duLieu.lich_hoc.ngay_hoc) }} · {{ duLieu.lich_hoc.gio_bat_dau }}–{{ duLieu.lich_hoc.gio_ket_thuc }}
      </span>
    </div>

    <div class="the p-4 mb-5">
      <div class="grid md:grid-cols-2 xl:grid-cols-[1fr_1fr_1.2fr] gap-3">
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Lớp học phần</label>
          <select v-model="maLopHoc" class="o-nhap" @change="doiLop">
            <option value="" disabled>— Chọn lớp học —</option>
            <option v-for="lop in lops" :key="lop.id" :value="lop.id">{{ lop.mon_hoc }} · {{ lop.ten_lop }}</option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Ngày học</label>
          <select v-model="maLichHoc" class="o-nhap" :disabled="!maLopHoc || dangTaiLich" @change="taiDanhSach">
            <option value="" disabled>{{ dangTaiLich ? 'Đang tải lịch...' : '— Chọn ngày học —' }}</option>
            <option v-for="lich in lichHocs" :key="lich.id" :value="lich.id">
              {{ tenThu(lich.ngay_hoc) }}, {{ dinhDangNgay(lich.ngay_hoc) }} · {{ lich.gio_bat_dau }}–{{ lich.gio_ket_thuc }}
            </option>
          </select>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tìm sinh viên</label>
          <div class="relative"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Mã sinh viên hoặc họ tên..." /></div>
        </div>
      </div>
      <p v-if="maLopHoc && !dangTaiLich && !lichHocs.length" class="mt-3 text-sm text-amber-600"><i class="fa-solid fa-circle-info mr-1.5"></i>Lớp học này chưa có lịch học.</p>
    </div>

    <div v-if="thongBao" class="mb-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm"><i class="fa-solid fa-circle-check mr-2"></i>{{ thongBao }}</div>
    <div v-if="loi" class="mb-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm"><i class="fa-solid fa-circle-exclamation mr-2"></i>{{ loi }}</div>

    <div v-if="maLichHoc" class="the mb-5 overflow-hidden">
      <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
        <div>
          <h3 class="font-bold text-slate-900">Phiên QR điểm danh</h3>
          <p class="mt-0.5 text-xs text-slate-500">Mã QR tự làm mới trước khi token 10 giây hết hạn.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
          <select v-if="!phienDangMo" v-model.number="soPhut" class="o-nhap !w-auto">
            <option :value="3">3 phút</option>
            <option :value="5">5 phút</option>
            <option :value="10">10 phút</option>
          </select>
          <button v-if="!phienDangMo" class="nut-than-cong" :disabled="dangXuLyPhien" @click="moPhienQr">
            <i class="fa-solid" :class="dangXuLyPhien ? 'fa-spinner fa-spin' : 'fa-qrcode'"></i>Mở phiên
          </button>
          <button v-else class="nut-nguy-hiem" :disabled="dangXuLyPhien" @click="dongPhienQr">
            <i class="fa-solid" :class="dangXuLyPhien ? 'fa-spinner fa-spin' : 'fa-lock'"></i>Đóng phiên
          </button>
        </div>
      </div>

      <div v-if="phienDangMo" class="grid gap-5 p-5 md:grid-cols-[240px_1fr]">
        <div class="flex min-h-60 items-center justify-center rounded-2xl border border-slate-200 bg-white p-3">
          <img v-if="anhQr" :src="anhQr" alt="Mã QR điểm danh" class="h-52 w-52" />
          <i v-else class="fa-solid fa-spinner fa-spin text-2xl text-brand-600"></i>
        </div>
        <div class="flex flex-col justify-center">
          <span class="nhan w-fit bg-emerald-50 text-emerald-700">
            <i class="fa-solid fa-circle text-[7px]"></i>Phiên đang mở
          </span>
          <p class="mt-3 text-sm text-slate-600">Mã phiên: <strong class="font-mono text-slate-900">{{ phienHienTai.ma_phien }}</strong></p>
          <p class="mt-1 text-sm text-slate-600">QR hết hạn lúc: <strong>{{ dinhDangThoiGian(qrHetHanLuc) }}</strong></p>
          <p class="mt-3 text-xs leading-5 text-slate-500">
            Giữ trang này mở. Danh sách sinh viên được cập nhật định kỳ và qua realtime khi có phòng trực tuyến.
          </p>
          <button class="nut-phu mt-4 w-fit" :disabled="dangLayQr" @click="layQrMoi">
            <i class="fa-solid" :class="dangLayQr ? 'fa-spinner fa-spin' : 'fa-rotate'"></i>Làm mới QR ngay
          </button>
        </div>
      </div>
      <div v-else class="p-8 text-center text-sm text-slate-400">
        Chưa có phiên QR đang mở cho buổi học này.
      </div>
    </div>

    <div v-if="duLieu.lich_hoc" class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-5">
      <div v-for="item in thongKe" :key="item.nhan" class="the p-4 flex items-center gap-3">
        <span class="w-10 h-10 rounded-xl flex items-center justify-center" :class="item.nen"><i :class="item.icon"></i></span>
        <div><p class="text-xs text-slate-500">{{ item.nhan }}</p><p class="text-xl font-bold text-slate-900">{{ item.giaTri }}</p></div>
      </div>
    </div>

    <div class="the overflow-hidden">
      <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-center justify-between gap-2">
        <div>
          <h3 class="font-bold text-slate-900">Danh sách sinh viên</h3>
          <p v-if="duLieu.lop_hoc" class="text-xs text-slate-500 mt-0.5">{{ duLieu.lop_hoc.mon_hoc }} · {{ duLieu.lop_hoc.ten_lop }} · {{ danhSach.length }} sinh viên</p>
        </div>
        <span v-if="duLieu.phien" class="nhan" :class="duLieu.phien.trang_thai === 'dang_mo' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600'">{{ duLieu.phien.trang_thai === 'dang_mo' ? 'Phiên đang mở' : 'Phiên đã đóng' }}</span>
      </div>

      <div v-if="dangTaiDanhSach" class="p-14 text-center text-slate-400"><i class="fa-solid fa-spinner fa-spin text-2xl"></i><p class="text-sm mt-2">Đang tải danh sách điểm danh...</p></div>
      <div v-else-if="!maLichHoc" class="p-14 text-center"><span class="w-14 h-14 bg-slate-100 text-slate-400 rounded-2xl inline-flex items-center justify-center text-xl"><i class="fa-regular fa-calendar"></i></span><p class="font-semibold text-slate-700 mt-3">Hãy chọn lớp và ngày học</p><p class="text-sm text-slate-400 mt-1">Danh sách điểm danh sẽ xuất hiện tại đây.</p></div>
      <div v-else-if="!danhSachLoc.length" class="p-14 text-center text-slate-400"><i class="fa-solid fa-user-slash text-2xl"></i><p class="text-sm mt-2">Không tìm thấy sinh viên phù hợp.</p></div>
      <div v-else class="overflow-x-auto">
        <table class="bang">
          <thead><tr><th class="w-14">STT</th><th>Sinh viên</th><th>Mã sinh viên</th><th>Trạng thái</th><th>Thời gian</th><th>Hình thức</th></tr></thead>
          <tbody>
            <tr v-for="(sv, index) in danhSachLoc" :key="sv.ma_sinh_vien">
              <td class="text-slate-400">{{ index + 1 }}</td>
              <td><div class="flex items-center gap-3"><span class="w-9 h-9 rounded-full bg-brand-100 text-brand-700 flex items-center justify-center font-bold text-xs">{{ chuCai(sv.ho_ten) }}</span><span class="font-semibold text-slate-800">{{ sv.ho_ten }}</span></div></td>
              <td class="font-mono text-xs text-slate-600">{{ sv.ma_sv_text }}</td>
              <td>
                <select :value="sv.trang_thai_diem_danh" class="rounded-xl border px-3 py-2 text-xs font-semibold outline-none min-w-[150px]" :class="mauSelect(sv.trang_thai_diem_danh)" :disabled="dangLuu === sv.ma_sinh_vien" @change="capNhat(sv, $event.target.value)">
                  <option value="chua_diem_danh">Chưa điểm danh</option><option value="co_mat">Có mặt</option><option value="di_muon">Đi muộn</option><option value="vang">Vắng</option><option value="vang_co_phep">Vắng có phép</option><option value="xin_phep">Xin phép</option>
                </select>
              </td>
              <td class="text-xs text-slate-500"><i v-if="dangLuu === sv.ma_sinh_vien" class="fa-solid fa-spinner fa-spin text-brand-600"></i><span v-else>{{ sv.thoi_gian_diem_danh || '—' }}</span></td>
              <td><span class="nhan bg-slate-100 text-slate-600">{{ tenHinhThuc(sv.hinh_thuc_diem_danh) }}</span></td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script>
import QRCode from 'qrcode'
import api from '../../utils/axios'
import { taoEcho } from '../../utils/echo'
import { useAuthStore } from '../../stores/auth'

export default {
  name: 'quan-ly-diem-danh',
  data() {
    return {
      auth: useAuthStore(), echo: null, kenhRealtime: null,
      lops: [], lichHocs: [], danhSach: [], duLieu: {},
      maLopHoc: '', maLichHoc: '', tuKhoa: '',
      dangTaiLich: false, dangTaiDanhSach: false, dangLuu: null,
      phienHienTai: null, anhQr: '', qrHetHanLuc: null, soPhut: 5,
      dangXuLyPhien: false, dangLayQr: false, boDemQr: null, boDemDanhSach: null,
      thongBao: '', loi: '',
    }
  },
  computed: {
    danhSachLoc() {
      const q = this.tuKhoa.trim().toLowerCase()
      return this.danhSach.filter((sv) => !q || `${sv.ma_sv_text} ${sv.ho_ten}`.toLowerCase().includes(q))
    },
    thongKe() {
      const dem = (trangThai) => this.danhSach.filter((sv) => trangThai.includes(sv.trang_thai_diem_danh)).length
      return [
        { nhan: 'Tổng sinh viên', giaTri: this.danhSach.length, icon: 'fa-solid fa-users text-brand-600', nen: 'bg-brand-50' },
        { nhan: 'Có mặt', giaTri: dem(['co_mat']), icon: 'fa-solid fa-user-check text-emerald-600', nen: 'bg-emerald-50' },
        { nhan: 'Đi muộn', giaTri: dem(['di_muon']), icon: 'fa-solid fa-clock text-amber-600', nen: 'bg-amber-50' },
        { nhan: 'Vắng', giaTri: dem(['vang']), icon: 'fa-solid fa-user-xmark text-rose-600', nen: 'bg-rose-50' },
        { nhan: 'Có phép', giaTri: dem(['vang_co_phep', 'xin_phep']), icon: 'fa-solid fa-file-circle-check text-sky-600', nen: 'bg-sky-50' },
      ]
    },
    phienDangMo() {
      return this.phienHienTai?.trang_thai === 'dang_mo'
    },
  },
  async created() {
    try {
      const { data } = await api.get('/lop-day')
      this.lops = data.danh_sach || []
      if (this.lops.length) {
        this.maLopHoc = this.lops[0].id
        await this.taiLichHoc()
      }
    } catch (e) {
      this.loi = e.response?.data?.message || 'Không tải được danh sách lớp.'
    }
  },
  beforeUnmount() {
    this.ngatKetNoiRealtime()
    this.dungBoDem()
  },
  methods: {
    async doiLop() {
      this.ngatKetNoiRealtime()
      this.dungBoDem()
      this.maLichHoc = ''
      this.danhSach = []
      this.duLieu = {}
      this.phienHienTai = null
      this.anhQr = ''
      await this.taiLichHoc()
    },
    async taiLichHoc() {
      if (!this.maLopHoc) return
      this.dangTaiLich = true
      this.loi = ''
      try {
        const { data } = await api.get(`/giang-vien/diem-danh/lop/${this.maLopHoc}/lich-hoc`)
        this.lichHocs = data.danh_sach || []
      } catch (e) {
        this.lichHocs = []
        this.loi = e.response?.data?.message || 'Không tải được lịch học của lớp.'
      } finally {
        this.dangTaiLich = false
      }
    },
    async taiDanhSach() {
      if (!this.maLichHoc) return
      this.dangTaiDanhSach = true
      this.loi = ''
      this.thongBao = ''
      try {
        const { data } = await api.get(`/giang-vien/diem-danh/lich-hoc/${this.maLichHoc}`)
        this.nhanDuLieu(data)
        if (this.phienDangMo) await this.layQrMoi()
      } catch (e) {
        this.loi = e.response?.data?.message || 'Không tải được danh sách điểm danh.'
      } finally {
        this.dangTaiDanhSach = false
      }
    },
    async capNhat(sv, trangThai) {
      this.dangLuu = sv.ma_sinh_vien
      this.loi = ''
      this.thongBao = ''
      try {
        const { data } = await api.put(`/giang-vien/diem-danh/lich-hoc/${this.maLichHoc}`, { ma_sinh_vien: sv.ma_sinh_vien, trang_thai_diem_danh: trangThai })
        this.nhanDuLieu(data)
        this.thongBao = `Đã cập nhật điểm danh cho ${sv.ho_ten}.`
      } catch (e) {
        this.loi = e.response?.data?.message || 'Không cập nhật được điểm danh.'
      } finally {
        this.dangLuu = null
      }
    },
    nhanDuLieu(data) {
      this.duLieu = data
      this.danhSach = data.danh_sach || []
      this.phienHienTai = data.phien || null
      if (!this.phienDangMo) {
        this.anhQr = ''
        this.qrHetHanLuc = null
        this.dungBoDem()
      }
      this.ketNoiRealtime()
    },
    async moPhienQr() {
      if (!this.maLichHoc) return
      this.dangXuLyPhien = true
      this.loi = ''
      this.thongBao = ''
      try {
        const { data } = await api.post('/phien-diem-danh', {
          ma_lich_hoc: Number(this.maLichHoc),
          so_phut: this.soPhut,
        })
        this.phienHienTai = data.phien
        await this.capNhatAnhQr(data.phien.duong_dan_qr, data.phien.qr_het_han_luc)
        this.thongBao = data.message || 'Đã mở phiên điểm danh.'
        this.batDauBoDem()
        await this.taiDanhSachAmTham()
      } catch (e) {
        this.loi = e.response?.data?.message || 'Không mở được phiên điểm danh.'
      } finally {
        this.dangXuLyPhien = false
      }
    },
    async layQrMoi(imLang = false) {
      if (!this.phienDangMo || this.dangLayQr) return
      this.dangLayQr = true
      if (!imLang) this.loi = ''
      try {
        const { data } = await api.get(`/phien-diem-danh/${this.phienHienTai.id}/qr-token`)
        await this.capNhatAnhQr(data.duong_dan_qr, data.qr_het_han_luc)
        this.batDauBoDem()
      } catch (e) {
        if (!imLang) this.loi = e.response?.data?.message || 'Không làm mới được mã QR.'
        if ([404, 422].includes(e.response?.status)) {
          this.phienHienTai = this.phienHienTai ? { ...this.phienHienTai, trang_thai: 'da_dong' } : null
          this.dungBoDem()
        }
      } finally {
        this.dangLayQr = false
      }
    },
    async dongPhienQr() {
      if (!this.phienDangMo) return
      this.dangXuLyPhien = true
      this.loi = ''
      this.thongBao = ''
      try {
        const { data } = await api.post(`/phien-diem-danh/${this.phienHienTai.id}/dong`)
        this.phienHienTai = { ...this.phienHienTai, trang_thai: 'da_dong' }
        this.anhQr = ''
        this.qrHetHanLuc = null
        this.dungBoDem()
        this.thongBao = data.message || 'Đã đóng phiên điểm danh.'
        await this.taiDanhSachAmTham()
      } catch (e) {
        this.loi = e.response?.data?.message || 'Không đóng được phiên điểm danh.'
      } finally {
        this.dangXuLyPhien = false
      }
    },
    async capNhatAnhQr(duongDan, hetHanLuc) {
      this.qrHetHanLuc = hetHanLuc || null
      this.anhQr = duongDan
        ? await QRCode.toDataURL(duongDan, { width: 320, margin: 1, errorCorrectionLevel: 'M' })
        : ''
    },
    batDauBoDem() {
      this.dungBoDem()
      if (!this.phienDangMo) return
      this.boDemQr = window.setInterval(() => this.layQrMoi(true), 8000)
      this.boDemDanhSach = window.setInterval(() => this.taiDanhSachAmTham(), 3000)
    },
    dungBoDem() {
      if (this.boDemQr) window.clearInterval(this.boDemQr)
      if (this.boDemDanhSach) window.clearInterval(this.boDemDanhSach)
      this.boDemQr = null
      this.boDemDanhSach = null
    },
    ketNoiRealtime() {
      const maPhong = this.duLieu.phong?.ma_phong
      if (this.kenhRealtime === maPhong) return
      this.ngatKetNoiRealtime()
      if (!maPhong) return

      this.echo = taoEcho(this.auth.token)
      this.kenhRealtime = maPhong
      this.echo.private(`phong.${maPhong}`)
        .listen('.trang.thai.diem.danh.cap.nhat', () => this.taiDanhSachAmTham())
        .listen('.diem.danh.thanh.cong', () => this.taiDanhSachAmTham())
    },
    ngatKetNoiRealtime() {
      if (this.echo && this.kenhRealtime) this.echo.leave(`phong.${this.kenhRealtime}`)
      this.kenhRealtime = null
    },
    async taiDanhSachAmTham() {
      if (!this.maLichHoc) return
      try {
        const { data } = await api.get(`/giang-vien/diem-danh/lich-hoc/${this.maLichHoc}`)
        this.duLieu = data
        this.danhSach = data.danh_sach || []
      } catch {
        // Giữ nguyên dữ liệu hiện tại nếu kết nối realtime vừa bị ngắt.
      }
    },
    dinhDangThoiGian(thoiGian) {
      return thoiGian ? new Date(thoiGian).toLocaleTimeString('vi-VN') : '—'
    },
    dinhDangNgay(ngay) { return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN') },
    tenThu(ngay) { return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN', { weekday: 'short' }) },
    chuCai(ten) { return (ten || '?').split(' ').filter(Boolean).slice(-2).map((tu) => tu[0]).join('').toUpperCase() },
    tenHinhThuc(hinhThuc) { return { qr_code: 'Quét QR', thu_cong: 'Thủ công', sua_thu_cong: 'Chỉnh tay', don_xin_phep: 'Đơn xin phép' }[hinhThuc] || 'Chưa có' },
    mauSelect(trangThai) {
      return {
        co_mat: 'bg-emerald-50 border-emerald-200 text-emerald-700', di_muon: 'bg-amber-50 border-amber-200 text-amber-700',
        vang: 'bg-rose-50 border-rose-200 text-rose-700', vang_co_phep: 'bg-sky-50 border-sky-200 text-sky-700',
        xin_phep: 'bg-violet-50 border-violet-200 text-violet-700', chua_diem_danh: 'bg-slate-50 border-slate-200 text-slate-600',
      }[trangThai]
    },
  },
}
</script>
