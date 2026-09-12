<template>
  <div>
    <div class="mb-5">
      <div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'admin-dashboard' }" class="hover:text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Lớp học phần</span></div>
      <h1 class="text-2xl font-bold text-slate-900">Quản lý lớp học phần</h1>
    </div>

    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-5"><div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.giaTri }}</p></div></div></div>

    <div class="the p-3 mb-4 flex flex-wrap items-center gap-2"><div class="relative flex-1 min-w-[220px]"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Tìm kiếm lớp học phần hoặc mã lớp..." @input="tai" /></div><select v-model="locHocKy" class="o-nhap !w-auto min-w-[140px]"><option value="">Tất cả học kỳ</option><option v-for="hk in cacHocKy" :key="hk" :value="hk">{{ hk }}</option></select><select v-model="locTrangThai" class="o-nhap !w-auto min-w-[150px]"><option value="">Tất cả trạng thái</option><option value="mo_dang_ky">Mở đăng ký</option><option value="dang_hoc">Đang học</option><option value="da_ket_thuc">Đã kết thúc</option></select><button class="nut-phu text-sm"><i class="fa-solid fa-download"></i>Xuất dữ liệu</button><button class="nut-chinh text-sm" @click="moThem()"><i class="fa-solid fa-plus"></i>Tạo lớp học phần</button></div>

    <div class="the">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr><th>Mã lớp</th><th>Tên lớp / Môn</th><th>Học kỳ</th><th>Sĩ số</th><th>Trạng thái</th><th class="!text-right">Thao tác</th></tr>
          </thead>
          <tbody>
            <tr v-for="l in danhSachHienThi" :key="l.id">
              <td class="font-mono text-xs font-bold text-brand-600">{{ l.ma_lop_hoc }}</td>
              <td>
                <div class="font-medium text-slate-800">{{ l.ten_lop }}</div>
                <div class="text-xs text-slate-400">{{ l.mon_hoc?.ten_mon }}</div>
              </td>
              <td class="whitespace-nowrap">{{ l.hoc_ky }} {{ l.nam_hoc }}</td>
              <td><div class="flex items-center gap-1.5"><span class="font-semibold text-slate-800">{{ l.so_sinh_vien }}</span><span class="text-slate-400">/</span><span class="text-slate-500">{{ l.so_luong_toi_da }}</span><span v-if="Number(l.so_sinh_vien) >= Number(l.so_luong_toi_da)" class="text-xs text-rose-500 font-medium">(Đủ)</span></div></td>
              <td>
                <span class="nhan" :class="{
                  mo_dang_ky: 'bg-emerald-100 text-emerald-700',
                  dang_hoc: 'bg-sky-100 text-sky-700',
                  da_ket_thuc: 'bg-slate-100 text-slate-600',
                }[l.trang_thai]">
                  {{ { mo_dang_ky: 'Mở đăng ký', dang_hoc: 'Đang học', da_ket_thuc: 'Kết thúc' }[l.trang_thai] }}
                </span>
              </td>
              <td class="!text-right">
                <div class="flex justify-end gap-1.5">
                  <button class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" title="Sửa" @click="moSua(l)"><i class="fa-solid fa-pen text-xs"></i></button>
                  <button class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-100" title="Lịch học & sinh viên" @click="moChiTiet(l)"><i class="fa-solid fa-eye text-xs"></i></button>
                  <button class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100" title="Xóa" @click="xoa(l)"><i class="fa-solid fa-trash text-xs"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="!danhSachHienThi.length"><td colspan="6" class="!py-12 text-center text-slate-400"><i class="fa-solid fa-chalkboard text-3xl text-slate-200 block mb-2"></i>Không tìm thấy lớp học phần.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal thêm/sửa lớp -->
    <div v-if="moModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="moModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <h5 class="font-bold text-slate-800 mb-4">{{ dangSua ? 'Sửa lớp học' : 'Thêm lớp học' }}</h5>
        <form @submit.prevent="luu" class="space-y-4">
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Mã lớp</label>
              <input v-model="form.ma_lop_hoc" class="o-nhap" placeholder="LH001" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Tên lớp</label>
              <input v-model="form.ten_lop" class="o-nhap" placeholder="CNT48-K48A" required />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Môn học</label>
            <select v-model="form.ma_mon_hoc" class="o-nhap" required>
              <option v-for="m in monHocs" :key="m.id" :value="m.id">{{ m.ma_mon_hoc }} — {{ m.ten_mon }}</option>
            </select>
          </div>
          <div class="grid grid-cols-3 gap-3">
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Học kỳ</label>
              <input v-model="form.hoc_ky" class="o-nhap" placeholder="HK1" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Năm học</label>
              <input v-model="form.nam_hoc" class="o-nhap" placeholder="2026-2027" required />
            </div>
            <div>
              <label class="block text-sm font-medium text-slate-700 mb-1.5">Tối đa</label>
              <input v-model="form.so_luong_toi_da" type="number" min="1" class="o-nhap" required />
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Trạng thái</label>
            <select v-model="form.trang_thai" class="o-nhap">
              <option value="mo_dang_ky">Mở đăng ký</option>
              <option value="dang_hoc">Đang học</option>
              <option value="da_ket_thuc">Kết thúc</option>
            </select>
          </div>
          <div v-if="loiForm" class="rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2 text-sm">{{ loiForm }}</div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="nut-phu" @click="moModal = false">Hủy</button>
            <button class="nut-chinh">Lưu</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal chi tiết lớp: lịch học + sinh viên -->
    <div v-if="moChiTietModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="moChiTietModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-3xl p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-4">
          <h5 class="font-bold text-slate-800">
            {{ lopChiTiet.ten_lop }}
            <span class="text-slate-400 text-sm font-normal">({{ lopChiTiet.mon_hoc?.ten_mon }})</span>
          </h5>
          <button class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200" @click="moChiTietModal = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>

        <div class="flex gap-1 mb-4 bg-slate-100 rounded-xl p-1">
          <button class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
            :class="tabChiTiet === 'lich' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500'"
            @click="tabChiTiet = 'lich'">Lịch học</button>
          <button class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
            :class="tabChiTiet === 'sv' ? 'bg-white text-brand-700 shadow-sm' : 'text-slate-500'"
            @click="chuyenTabSv">Sinh viên</button>
        </div>

        <!-- Tab lịch học -->
        <div v-if="tabChiTiet === 'lich'">
          <!-- Tạo nhanh: lặp theo thứ hàng tuần -->
          <div class="rounded-2xl bg-brand-50 border border-brand-100 p-4 mb-4">
            <div class="flex items-center justify-between mb-3">
              <div class="text-sm font-bold text-brand-800">
                <i class="fa-solid fa-bolt mr-1.5"></i>Tạo nhanh lịch học — lặp hàng tuần
              </div>
              <button class="text-xs text-brand-600 hover:underline" @click="hienTaoNhanh = !hienTaoNhanh">
                {{ hienTaoNhanh ? 'Đóng' : 'Mở' }}
              </button>
            </div>
            <form v-if="hienTaoNhanh" @submit.prevent="taoLichNhanh" class="space-y-3">
              <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs text-slate-600 mr-1">Học vào các thứ:</span>
                <button v-for="(n, thu) in TEN_THU" :key="thu" type="button"
                  class="w-9 h-9 rounded-xl text-xs font-bold transition"
                  :class="nhanh.cac_thu.includes(thu) ? 'bg-brand-600 text-white' : 'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50'"
                  @click="batThu(thu)">
                  {{ n }}
                </button>
              </div>
              <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div>
                  <label class="block text-xs text-slate-600 mb-1">Giờ bắt đầu</label>
                  <input v-model="nhanh.gio_bat_dau" type="time" class="o-nhap !py-2 text-xs" required />
                </div>
                <div>
                  <label class="block text-xs text-slate-600 mb-1">Giờ kết thúc</label>
                  <input v-model="nhanh.gio_ket_thuc" type="time" class="o-nhap !py-2 text-xs" required />
                </div>
                <div>
                  <label class="block text-xs text-slate-600 mb-1">Từ ngày</label>
                  <input v-model="nhanh.tu_ngay" type="date" class="o-nhap !py-2 text-xs" required />
                </div>
                <div>
                  <label class="block text-xs text-slate-600 mb-1">Đến ngày</label>
                  <input v-model="nhanh.den_ngay" type="date" class="o-nhap !py-2 text-xs" required />
                </div>
              </div>
              <div class="flex flex-wrap items-center gap-4">
                <input v-model="nhanh.phong_hoc" class="o-nhap !w-28 !py-2 text-xs" placeholder="Phòng (VD D201)" />
                <label class="flex items-center gap-2 text-xs text-slate-600">
                  <input v-model="nhanh.co_hoc_truc_tuyen" type="checkbox" class="w-4 h-4 rounded accent-brand-600" />
                  Học trực tuyến
                </label>
                <button class="nut-chinh text-xs !py-2 ml-auto" :disabled="dangTaoNhanh">
                  <i class="fa-solid fa-bolt"></i>{{ dangTaoNhanh ? 'Đang tạo...' : 'Tạo lịch tự động' }}
                </button>
              </div>
              <div v-if="tbNhanh" class="rounded-xl px-3.5 py-2 text-xs"
                :class="tbNhanhLoi ? 'bg-rose-50 border border-rose-200 text-rose-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
                {{ tbNhanh }}
              </div>
            </form>
          </div>

          <!-- Thêm từng buổi thủ công -->
          <form @submit.prevent="themLichHoc" class="grid grid-cols-2 sm:grid-cols-6 gap-2 items-end mb-4">
            <div class="col-span-1">
              <label class="block text-xs text-slate-500 mb-1">Ngày</label>
              <input v-model="lichMoi.ngay_hoc" type="date" class="o-nhap !py-1.5 text-xs" required />
            </div>
            <div><label class="block text-xs text-slate-500 mb-1">Bắt đầu</label><input v-model="lichMoi.gio_bat_dau" type="time" class="o-nhap !py-1.5 text-xs" required /></div>
            <div><label class="block text-xs text-slate-500 mb-1">Kết thúc</label><input v-model="lichMoi.gio_ket_thuc" type="time" class="o-nhap !py-1.5 text-xs" required /></div>
            <div><label class="block text-xs text-slate-500 mb-1">Phòng</label><input v-model="lichMoi.phong_hoc" class="o-nhap !py-1.5 text-xs" placeholder="D201" /></div>
            <label class="flex items-center gap-2 text-xs text-slate-600 pb-2.5">
              <input v-model="lichMoi.co_hoc_truc_tuyen" type="checkbox" class="w-4 h-4 rounded accent-brand-600" />
              Trực tuyến
            </label>
            <button class="nut-chinh !py-1.5 text-xs"><i class="fa-solid fa-plus"></i></button>
          </form>
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="bang">
              <thead><tr><th>Ngày</th><th>Giờ</th><th>Phòng</th><th>Hình thức</th><th></th></tr></thead>
              <tbody>
                <tr v-for="lh in lichHocLop" :key="lh.id">
                  <td class="whitespace-nowrap">{{ lh.ngay_hoc }}</td>
                  <td class="whitespace-nowrap">{{ lh.gio_bat_dau }}–{{ lh.gio_ket_thuc }}</td>
                  <td>{{ lh.phong_hoc || '—' }}</td>
                  <td><span class="nhan" :class="lh.co_hoc_truc_tuyen ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-600'">{{ lh.co_hoc_truc_tuyen ? 'Trực tuyến' : 'Trực tiếp' }}</span></td>
                  <td class="!text-right">
                    <button class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100" @click="xoaLichHoc(lh)"><i class="fa-solid fa-xmark text-xs"></i></button>
                  </td>
                </tr>
                <tr v-if="!lichHocLop.length"><td colspan="5" class="!py-8 text-center text-slate-400">Chưa có buổi học.</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Tab sinh viên -->
        <div v-else>
          <div class="flex gap-2 mb-4">
            <select v-model="svThem" class="o-nhap flex-1">
              <option value="" disabled>— Chọn sinh viên để thêm —</option>
              <option v-for="sv in svNgoaiLop" :key="sv.id" :value="sv.id">{{ sv.ma_sinh_vien }} — {{ sv.ho_ten }}</option>
            </select>
            <button class="nut-chinh" :disabled="!svThem" @click="themSvVaoLop">Thêm vào lớp</button>
          </div>
          <div class="overflow-x-auto rounded-xl border border-slate-200">
            <table class="bang">
              <thead><tr><th>Mã SV</th><th>Họ tên</th><th>Email</th><th>Ngày đăng ký</th><th></th></tr></thead>
              <tbody>
                <tr v-for="sv in svTrongLop" :key="sv.ma_sinh_vien">
                  <td class="font-mono text-xs">{{ sv.ma_sv_text }}</td>
                  <td class="font-medium text-slate-800">{{ sv.ho_ten }}</td>
                  <td>{{ sv.email }}</td>
                  <td>{{ sv.ngay_dang_ky }}</td>
                  <td class="!text-right">
                    <button class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100" @click="xoaSvKhoiLop(sv)"><i class="fa-solid fa-xmark text-xs"></i></button>
                  </td>
                </tr>
                <tr v-if="!svTrongLop.length"><td colspan="5" class="!py-8 text-center text-slate-400">Lớp chưa có sinh viên.</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'admin-lop-hoc',
  data() {
    return {
      danhSach: [],
      monHocs: [],
      tuKhoa: '',
      locHocKy: '',
      locTrangThai: '',
      moModal: false,
      dangSua: false,
      loiForm: '',
      form: this.formTrong(),
      moChiTietModal: false,
      tabChiTiet: 'lich',
      lopChiTiet: {},
      lichHocLop: [],
      lichMoi: { ngay_hoc: '', gio_bat_dau: '', gio_ket_thuc: '', phong_hoc: '', co_hoc_truc_tuyen: true },
      svTrongLop: [],
      svNgoaiLop: [],
      svThem: '',
      hienTaoNhanh: true,
      dangTaoNhanh: false,
      tbNhanh: '',
      tbNhanhLoi: false,
      TEN_THU: ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
      nhanh: this.formNhanhTrong(),
    }
  },
  async created() {
    await Promise.all([this.tai(), this.taiMonHoc()])
  },
  computed: {
    cacHocKy() { return [...new Set(this.danhSach.map((l) => `${l.hoc_ky} ${l.nam_hoc}`))] },
    danhSachHienThi() { return this.danhSach.filter((l) => (!this.locHocKy || `${l.hoc_ky} ${l.nam_hoc}` === this.locHocKy) && (!this.locTrangThai || l.trang_thai === this.locTrangThai)) },
    thongKe() { return [
      { icon: 'fa-solid fa-chalkboard', nen: 'bg-indigo-50 text-indigo-600', nhan: 'Tổng lớp HP', giaTri: this.danhSach.length },
      { icon: 'fa-solid fa-circle-play', nen: 'bg-emerald-50 text-emerald-600', nhan: 'Đang hoạt động', giaTri: this.danhSach.filter((l) => l.trang_thai === 'dang_hoc').length },
      { icon: 'fa-solid fa-calendar-plus', nen: 'bg-blue-50 text-blue-600', nhan: 'Sắp bắt đầu', giaTri: this.danhSach.filter((l) => l.trang_thai === 'mo_dang_ky').length },
      { icon: 'fa-solid fa-circle-check', nen: 'bg-slate-100 text-slate-500', nhan: 'Đã kết thúc', giaTri: this.danhSach.filter((l) => l.trang_thai === 'da_ket_thuc').length },
    ] },
  },
  methods: {
    formTrong() {
      return {
        id: null, ma_lop_hoc: '', ten_lop: '', ma_mon_hoc: '', hoc_ky: 'HK1',
        nam_hoc: '2026-2027', so_luong_toi_da: 40, trang_thai: 'mo_dang_ky',
      }
    },
    formNhanhTrong() {
      const n = new Date()
      const den = new Date(n)
      den.setDate(den.getDate() + 30)
      const iso = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
      return {
        cac_thu: [2, 4],
        gio_bat_dau: '09:00',
        gio_ket_thuc: '11:00',
        tu_ngay: iso(n),
        den_ngay: iso(den),
        phong_hoc: '',
        co_hoc_truc_tuyen: true,
      }
    },
    batThu(thu) {
      const i = this.nhanh.cac_thu.indexOf(thu)
      if (i >= 0) this.nhanh.cac_thu.splice(i, 1)
      else this.nhanh.cac_thu.push(thu)
    },
    async taoLichNhanh() {
      this.tbNhanh = ''
      this.tbNhanhLoi = false
      if (!this.nhanh.cac_thu.length) {
        this.tbNhanh = 'Chọn ít nhất một thứ trong tuần.'
        this.tbNhanhLoi = true
        return
      }
      this.dangTaoNhanh = true
      try {
        const { data } = await api.post(`/admin/lop-hoc/${this.lopChiTiet.id}/lich-hoc-nhanh`, this.nhanh)
        this.tbNhanh = data.message
        const res = await api.get(`/admin/lop-hoc/${this.lopChiTiet.id}/lich-hoc`)
        this.lichHocLop = res.data.danh_sach.map((x) => ({ ...x, ngay_hoc: x.ngay_hoc?.slice(0, 10) }))
      } catch (e) {
        this.tbNhanh = Object.values(e.response?.data?.errors || {})[0]?.[0] || e.response?.data?.message || 'Tạo thất bại.'
        this.tbNhanhLoi = true
      } finally {
        this.dangTaoNhanh = false
      }
    },
    async tai() {
      const { data } = await api.get('/admin/lop-hoc', { params: { tu_khoa: this.tuKhoa || null } })
      this.danhSach = data.danh_sach
    },
    async taiMonHoc() {
      const { data } = await api.get('/admin/mon-hoc')
      this.monHocs = data.danh_sach
      if (!this.form.ma_mon_hoc && this.monHocs.length) this.form.ma_mon_hoc = this.monHocs[0].id
    },
    moThem() {
      this.dangSua = false
      this.form = this.formTrong()
      if (this.monHocs.length) this.form.ma_mon_hoc = this.monHocs[0].id
      this.loiForm = ''
      this.moModal = true
    },
    moSua(l) {
      this.dangSua = true
      this.form = { ...l, ma_mon_hoc: l.mon_hoc?.id }
      this.loiForm = ''
      this.moModal = true
    },
    async luu() {
      this.loiForm = ''
      try {
        if (this.dangSua) await api.post(`/admin/lop-hoc/${this.form.id}`, this.form)
        else await api.post('/admin/lop-hoc', this.form)
        this.moModal = false
        await this.tai()
      } catch (e) {
        this.loiForm = Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Lưu thất bại.'
      }
    },
    async xoa(l) {
      if (!confirm(`Xóa lớp "${l.ten_lop}"?`)) return
      try {
        await api.delete(`/admin/lop-hoc/${l.id}`)
        await this.tai()
      } catch (e) {
        alert(e.response?.data?.message || 'Xóa thất bại.')
      }
    },
    async moChiTiet(l) {
      this.lopChiTiet = l
      this.tabChiTiet = 'lich'
      const { data } = await api.get(`/admin/lop-hoc/${l.id}/lich-hoc`)
      this.lichHocLop = data.danh_sach.map((x) => ({ ...x, ngay_hoc: x.ngay_hoc?.slice(0, 10) }))
      this.moChiTietModal = true
    },
    async chuyenTabSv() {
      this.tabChiTiet = 'sv'
      await this.taiSinhVienLop()
    },
    async themLichHoc() {
      try {
        await api.post(`/admin/lop-hoc/${this.lopChiTiet.id}/lich-hoc`, this.lichMoi)
        this.lichMoi = { ngay_hoc: '', gio_bat_dau: '', gio_ket_thuc: '', phong_hoc: '', co_hoc_truc_tuyen: true }
        const { data } = await api.get(`/admin/lop-hoc/${this.lopChiTiet.id}/lich-hoc`)
        this.lichHocLop = data.danh_sach.map((x) => ({ ...x, ngay_hoc: x.ngay_hoc?.slice(0, 10) }))
      } catch (e) {
        alert(Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Thêm thất bại.')
      }
    },
    async xoaLichHoc(lh) {
      await api.delete(`/admin/lich-hoc/${lh.id}`)
      this.lichHocLop = this.lichHocLop.filter((x) => x.id !== lh.id)
    },
    async taiSinhVienLop() {
      const id = this.lopChiTiet.id
      const [resTrong, resNgoai] = await Promise.all([
        api.get(`/admin/lop-hoc/${id}/sinh-vien`),
        api.get(`/admin/lop-hoc/${id}/sinh-vien-ngoai`),
      ])
      this.svTrongLop = resTrong.data.danh_sach
      this.svNgoaiLop = resNgoai.data.danh_sach
      this.svThem = ''
    },
    async themSvVaoLop() {
      try {
        await api.post(`/admin/lop-hoc/${this.lopChiTiet.id}/sinh-vien`, { ma_sinh_vien: this.svThem })
        await this.taiSinhVienLop()
        await this.tai()
      } catch (e) {
        alert(e.response?.data?.message || 'Thêm thất bại.')
      }
    },
    async xoaSvKhoiLop(sv) {
      if (!confirm(`Xóa ${sv.ho_ten} khỏi lớp?`)) return
      await api.delete(`/admin/lop-hoc/${this.lopChiTiet.id}/sinh-vien/${sv.ma_sinh_vien}`)
      await this.taiSinhVienLop()
      await this.tai()
    },
  },
}
</script>
