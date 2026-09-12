<template>
  <div>
    <div class="mb-5">
      <div class="flex items-center gap-2 text-xs text-slate-400 mb-1.5"><router-link :to="{ name: 'admin-dashboard' }" class="hover:text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span class="text-slate-600">Môn học</span></div>
      <h1 class="text-2xl font-bold text-slate-900">Quản lý môn học</h1>
    </div>

    <div class="grid grid-cols-3 gap-4 mb-5">
      <div v-for="th in thongKe" :key="th.nhan" class="the p-5 flex items-start gap-4"><div class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0" :class="th.nen"><i :class="th.icon"></i></div><div><p class="text-xs text-slate-500 font-medium">{{ th.nhan }}</p><p class="text-2xl font-bold text-slate-900 mt-0.5">{{ th.giaTri }}</p></div></div>
    </div>

    <div class="the p-3 mb-4 flex flex-wrap items-center gap-2"><div class="relative flex-1 min-w-[220px]"><i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i><input v-model="tuKhoa" class="o-nhap !pl-9" placeholder="Tìm kiếm môn học hoặc mã môn..." @input="tai" /></div><button class="nut-phu text-sm"><i class="fa-solid fa-download"></i>Xuất dữ liệu</button><button class="nut-chinh text-sm" @click="moThem()"><i class="fa-solid fa-plus"></i>Thêm môn học</button></div>

    <div class="the">
      <div class="overflow-x-auto">
        <table class="bang">
          <thead>
            <tr><th>Mã môn</th><th>Tên môn</th><th>Số tín chỉ</th><th>Số lớp</th><th class="!text-right">Thao tác</th></tr>
          </thead>
          <tbody>
            <tr v-for="m in danhSach" :key="m.id">
              <td><span class="font-mono text-xs font-bold text-brand-600 bg-brand-50 px-2 py-0.5 rounded-lg border border-brand-200">{{ m.ma_mon_hoc }}</span></td>
              <td class="font-semibold text-slate-800">{{ m.ten_mon }}</td>
              <td class="font-semibold text-slate-700">{{ m.so_tin_chi }}</td>
              <td><div class="flex items-center gap-2"><div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-teal-500 rounded-full" :style="{ width: Math.min(Number(m.so_lop || 0) * 10, 100) + '%' }"></div></div><span class="font-semibold text-slate-700">{{ m.so_lop }}</span></div></td>
              <td class="!text-right">
                <div class="flex justify-end gap-1.5">
                  <button class="w-8 h-8 rounded-lg bg-brand-50 text-brand-600 hover:bg-brand-100" title="Điểm thành phần của lớp"
                    @click="moThanhPhan(m)"><i class="fa-solid fa-table-cells text-xs"></i></button>
                  <button class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" @click="moSua(m)"><i class="fa-solid fa-pen text-xs"></i></button>
                  <button class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100" @click="xoa(m)"><i class="fa-solid fa-trash text-xs"></i></button>
                </div>
              </td>
            </tr>
            <tr v-if="!danhSach.length"><td colspan="5" class="!py-10 text-center text-slate-400">Không có môn học.</td></tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Modal Tailwind -->
    <div v-if="moModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="moModal = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6">
        <h5 class="font-bold text-slate-800 mb-4">{{ dangSua ? 'Sửa môn học' : 'Thêm môn học' }}</h5>
        <form @submit.prevent="luu" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mã môn học</label>
            <input v-model="form.ma_mon_hoc" class="o-nhap" placeholder="MH001" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Tên môn</label>
            <input v-model="form.ten_mon" class="o-nhap" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Số tín chỉ</label>
            <input v-model="form.so_tin_chi" type="number" min="1" max="10" class="o-nhap" required />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Mô tả</label>
            <textarea v-model="form.mo_ta" class="o-nhap" rows="2"></textarea>
          </div>
          <div v-if="loiForm" class="rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2 text-sm">{{ loiForm }}</div>
          <div class="flex justify-end gap-2 pt-2">
            <button type="button" class="nut-phu" @click="moModal = false">Hủy</button>
            <button class="nut-chinh">Lưu</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal điểm thành phần theo lớp (US23 - admin toàn quyền) -->
    <div v-if="moModalTp" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" @click="moModalTp = false"></div>
      <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
        <div class="flex items-center justify-between mb-1">
          <h5 class="font-bold text-slate-800">Điểm thành phần lớp</h5>
          <button class="w-9 h-9 rounded-xl bg-slate-100 text-slate-500 hover:bg-slate-200" @click="moModalTp = false">
            <i class="fa-solid fa-xmark"></i>
          </button>
        </div>
        <p class="text-sm text-slate-500 mb-3">{{ monChon?.ma_mon_hoc }} — {{ monChon?.ten_mon }}</p>
        <select v-model="lopChonTp" class="o-nhap mb-4" :disabled="!monChon?.lop_hoc?.length" @change="taiTp">
          <option value="" disabled>— Chọn lớp để cấu hình —</option>
          <option v-for="lop in (monChon?.lop_hoc || [])" :key="lop.id" :value="lop.id">
            {{ lop.ma_lop_hoc }} — {{ lop.ten_lop }}
          </option>
        </select>
        <p v-if="!monChon?.lop_hoc?.length" class="text-sm text-amber-600 mb-4">Môn chưa có lớp học để cấu hình điểm thành phần.</p>

        <!-- Danh sách thành phần -->
        <div class="space-y-2 mb-4">
          <div v-if="!danhSachTp.length" class="text-sm text-slate-400 text-center py-4">Môn chưa có thành phần nào.</div>
          <div v-for="tp in danhSachTp" :key="tp.id"
            class="flex items-center gap-2 rounded-xl border border-slate-200 px-3.5 py-2.5">
            <template v-if="dangSuaTp !== tp.id">
              <span class="font-medium text-slate-800 text-sm flex-1">{{ tp.ten_thanh_phan }}</span>
              <span class="nhan bg-brand-100 text-brand-700">TS {{ tp.trong_so }}</span>
              <button class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 hover:bg-slate-200" title="Sửa" @click="batDauSuaTp(tp)">
                <i class="fa-solid fa-pen text-xs"></i>
              </button>
              <button class="w-8 h-8 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100" title="Xóa" @click="xoaTp(tp)">
                <i class="fa-solid fa-trash text-xs"></i>
              </button>
            </template>
            <template v-else>
              <input v-model="tpSua.ten_thanh_phan" class="o-nhap flex-1 !py-1.5 text-sm" placeholder="Tên thành phần" />
              <input v-model="tpSua.trong_so" type="number" step="0.1" min="0" max="10" class="o-nhap !w-20 !py-1.5 text-sm" />
              <button class="nut-chinh text-xs !px-3 !py-1.5" @click="luuTp">Lưu</button>
              <button class="nut-phu text-xs !px-3 !py-1.5" @click="dangSuaTp = null">Hủy</button>
            </template>
          </div>
        </div>

        <!-- Thêm thành phần -->
        <form @submit.prevent="themTp" class="rounded-xl bg-brand-50 border border-brand-100 p-4 space-y-3">
          <div class="text-sm font-semibold text-brand-800"><i class="fa-solid fa-plus mr-1.5"></i>Thêm thành phần</div>
          <div class="flex gap-2">
            <input v-model="tpMoi.ten_thanh_phan" class="o-nhap flex-1 !py-2 text-sm" placeholder="VD: Chuyên cần, Giữa kỳ..." required />
            <input v-model="tpMoi.trong_so" type="number" step="0.1" min="0" max="10" class="o-nhap !w-24 !py-2 text-sm" placeholder="TS" required />
            <button class="nut-chinh text-sm" :disabled="dangLuTp">Thêm</button>
          </div>
          <div v-if="tbTp" class="rounded-xl px-3.5 py-2 text-xs"
            :class="tbTpLoi ? 'bg-rose-50 border border-rose-200 text-rose-600' : 'bg-emerald-50 border border-emerald-200 text-emerald-700'">
            {{ tbTp }}
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'admin-mon-hoc',
  data() {
    return {
      danhSach: [],
      tuKhoa: '',
      moModal: false,
      dangSua: false,
      loiForm: '',
      form: { id: null, ma_mon_hoc: '', ten_mon: '', so_tin_chi: 3, mo_ta: '' },
      // Điểm thành phần theo môn
      moModalTp: false,
      monChon: null,
      lopChonTp: '',
      danhSachTp: [],
      dangSuaTp: null,
      tpSua: { ten_thanh_phan: '', trong_so: 1 },
      tpMoi: { ten_thanh_phan: '', trong_so: 1 },
      dangLuTp: false,
      tbTp: '',
      tbTpLoi: false,
    }
  },
  async created() {
    await this.tai()
  },
  computed: {
    thongKe() { return [
      { icon: 'fa-solid fa-book', nen: 'bg-indigo-50 text-indigo-600', nhan: 'Tổng môn học', giaTri: this.danhSach.length },
      { icon: 'fa-solid fa-chalkboard', nen: 'bg-teal-50 text-teal-600', nhan: 'Tổng lớp học phần', giaTri: this.danhSach.reduce((s, m) => s + Number(m.so_lop || 0), 0) },
      { icon: 'fa-solid fa-star', nen: 'bg-amber-50 text-amber-600', nhan: 'Tổng tín chỉ', giaTri: this.danhSach.reduce((s, m) => s + Number(m.so_tin_chi || 0), 0) },
    ] },
  },
  methods: {
    async tai() {
      const { data } = await api.get('/admin/mon-hoc', { params: { tu_khoa: this.tuKhoa || null } })
      this.danhSach = data.danh_sach
    },
    moThem() {
      this.dangSua = false
      this.form = { id: null, ma_mon_hoc: '', ten_mon: '', so_tin_chi: 3, mo_ta: '' }
      this.loiForm = ''
      this.moModal = true
    },
    moSua(m) {
      this.dangSua = true
      this.form = { ...m }
      this.loiForm = ''
      this.moModal = true
    },
    async luu() {
      this.loiForm = ''
      try {
        if (this.dangSua) await api.post(`/admin/mon-hoc/${this.form.id}`, this.form)
        else await api.post('/admin/mon-hoc', this.form)
        this.moModal = false
        await this.tai()
      } catch (e) {
        this.loiForm = Object.values(e.response?.data?.errors || {})[0]?.[0] || 'Lưu thất bại.'
      }
    },
    async xoa(m) {
      if (!confirm(`Xóa môn "${m.ten_mon}"?`)) return
      try {
        await api.delete(`/admin/mon-hoc/${m.id}`)
        await this.tai()
      } catch (e) {
        alert(e.response?.data?.message || 'Xóa thất bại.')
      }
    },

    // ---------- Điểm thành phần theo môn ----------
    async moThanhPhan(m) {
      this.monChon = m
      this.lopChonTp = m.lop_hoc?.[0]?.id || ''
      this.moModalTp = true
      this.dangSuaTp = null
      this.tbTp = ''
      this.tpMoi = { ten_thanh_phan: '', trong_so: 1 }
      if (this.lopChonTp) await this.taiTp()
    },
    async taiTp() {
      if (!this.lopChonTp) {
        this.danhSachTp = []
        return
      }
      const { data } = await api.get(`/admin/lop-hoc/${this.lopChonTp}/thanh-phan`)
      this.danhSachTp = data.danh_sach
    },
    async themTp() {
      this.dangLuTp = true
      this.tbTp = ''
      this.tbTpLoi = false
      try {
        await api.post(`/admin/lop-hoc/${this.lopChonTp}/thanh-phan`, this.tpMoi)
        this.tpMoi = { ten_thanh_phan: '', trong_so: 1 }
        await this.taiTp()
      } catch (e) {
        this.tbTp = e.response?.data?.message || 'Thêm thất bại.'
        this.tbTpLoi = true
      } finally {
        this.dangLuTp = false
      }
    },
    batDauSuaTp(tp) {
      this.dangSuaTp = tp.id
      this.tpSua = { ten_thanh_phan: tp.ten_thanh_phan, trong_so: tp.trong_so }
    },
    async luuTp() {
      try {
        await api.put(`/admin/thanh-phan/${this.dangSuaTp}`, this.tpSua)
        this.dangSuaTp = null
        await this.taiTp()
      } catch (e) {
        alert(e.response?.data?.message || 'Sửa thất bại.')
      }
    },
    async xoaTp(tp) {
      if (!confirm(`Xóa "${tp.ten_thanh_phan}" cùng TOÀN BỘ điểm đã nhập của thành phần này trong môn?`)) return
      try {
        await api.delete(`/admin/thanh-phan/${tp.id}`)
        await this.taiTp()
      } catch (e) {
        alert(e.response?.data?.message || 'Xóa thất bại.')
      }
    },
  },
}
</script>
