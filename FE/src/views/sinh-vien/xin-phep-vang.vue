<template>
  <div>
    <div class="tieu-de-trang"><div><div class="flex items-center gap-1.5 text-xs text-slate-400 mb-1"><router-link :to="{ name: 'sinh-vien-trang-chu' }" class="text-brand-600">Tổng quan</router-link><i class="fa-solid fa-chevron-right text-[9px]"></i><span>Xin phép vắng</span></div><h4>Xin phép vắng</h4></div></div>
    <div class="grid lg:grid-cols-5 gap-6">
    <div class="lg:col-span-2">
      <div class="the h-fit">
        <div class="the-tieu-de flex items-center gap-2"><span class="w-8 h-8 bg-brand-50 rounded-xl flex items-center justify-center"><i class="fa-solid fa-file-pen text-brand-600 text-sm"></i></span>Gửi đơn xin phép</div>
        <form @submit.prevent="gui" class="space-y-4 p-5">
          <div v-if="thongBao" class="rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-2.5 text-sm">{{ thongBao }}</div>
          <div v-if="loi" class="rounded-xl bg-rose-50 border border-rose-200 text-rose-600 px-4 py-2.5 text-sm">{{ loi }}</div>

          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Lớp học</label>
            <select v-model="form.ma_lop_hoc" class="o-nhap" required @change="doiLopHoc">
              <option value="" disabled>— Chọn lớp —</option>
              <option v-for="l in lops" :key="l.id" :value="l.id">{{ l.mon_hoc }} ({{ l.ten_lop }})</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Ngày nghỉ · Buổi học</label>
            <select v-model="form.ma_lich_hoc" class="o-nhap" required :disabled="!form.ma_lop_hoc || !buoiHocCuaLop.length" @change="chonBuoiHoc">
              <option value="" disabled>{{ form.ma_lop_hoc ? '— Chọn ngày theo lịch môn học —' : '— Chọn lớp trước —' }}</option>
              <option v-for="b in buoiHocCuaLop" :key="b.id" :value="b.id">
                {{ tenThu(b.ngay_hoc) }}, {{ dinhDangNgay(b.ngay_hoc) }} · {{ b.gio_bat_dau }}–{{ b.gio_ket_thuc }}
              </option>
            </select>
            <p v-if="form.ma_lop_hoc && !buoiHocCuaLop.length" class="mt-1.5 text-xs text-amber-600"><i class="fa-solid fa-circle-info mr-1"></i>Lớp này không còn buổi học nào từ hôm nay.</p>
            <div v-if="buoiDaChon" class="mt-2.5 rounded-xl bg-brand-50 border border-brand-100 px-3 py-2.5 flex items-center gap-3"><span class="w-9 h-9 rounded-lg bg-white text-brand-600 flex items-center justify-center"><i class="fa-regular fa-calendar-check"></i></span><div><p class="text-xs text-slate-500">Buổi xin nghỉ đã chọn</p><p class="text-sm font-semibold text-slate-800">{{ tenThu(buoiDaChon.ngay_hoc) }}, {{ dinhDangNgay(buoiDaChon.ngay_hoc) }} · {{ buoiDaChon.gio_bat_dau }}–{{ buoiDaChon.gio_ket_thuc }}</p></div></div>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700 mb-1.5">Lý do</label>
            <textarea v-model="form.ly_do" class="o-nhap" rows="3" required placeholder="Nêu rõ lý do xin phép vắng..."></textarea>
          </div>
          <button type="submit" class="nut-chinh w-full" :disabled="dangGui">Gửi yêu cầu</button>
        </form>
      </div>
    </div>

    <div class="lg:col-span-3">
      <h3 class="font-bold text-slate-900 mb-4 flex items-center gap-2"><span class="w-8 h-8 bg-slate-100 rounded-xl flex items-center justify-center"><i class="fa-solid fa-clock-rotate-left text-slate-500 text-sm"></i></span>Lịch sử đơn đã gửi ({{ danhSach.length }})</h3>
      <div v-if="!danhSach.length" class="the p-12 text-center"><i class="fa-solid fa-file-pen text-slate-200 text-3xl"></i><p class="text-sm text-slate-400 mt-2">Chưa có đơn nào.</p></div>
      <div v-else class="space-y-3">
        <article v-for="d in danhSach" :key="d.id" class="the p-5 hover:shadow-md transition-shadow">
          <div class="flex items-start gap-4 mb-3"><div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center shrink-0"><i class="fa-solid fa-file-circle-check text-brand-600"></i></div><div class="flex-1 min-w-0"><div class="flex items-start justify-between gap-2 flex-wrap"><div><p class="font-bold text-slate-900 text-sm">{{ d.mon_hoc }}</p><p class="text-xs text-slate-500 mt-0.5">Ngày vắng: {{ d.ngay_nghi }}<span v-if="d.nguoi_duyet"> · Duyệt bởi {{ d.nguoi_duyet }}</span></p></div><span class="nhan border !py-0.5" :class="mau(d.trang_thai)"><span class="w-1.5 h-1.5 rounded-full" :class="d.trang_thai === 'duoc_duyet' ? 'bg-emerald-500' : d.trang_thai === 'tu_choi' ? 'bg-rose-500' : 'bg-amber-500'"></span>{{ ten(d.trang_thai) }}</span></div></div></div>
          <div class="bg-slate-50 rounded-xl px-3 py-2.5 text-xs text-slate-600 italic">“{{ d.ly_do }}”</div>
        </article>
      </div>
    </div>
  </div>
  </div>
</template>

<script>
import api from '../../utils/axios'

export default {
  name: 'xin-phep-vang',
  data() {
    return {
      lops: [],
      danhSach: [],
      form: { ma_lop_hoc: '', ma_lich_hoc: '', ngay_nghi: '', ly_do: '' },
      thongBao: '',
      loi: '',
      dangGui: false,
    }
  },
  computed: {
    buoiHocCuaLop() {
      const lop = this.lops.find((l) => Number(l.id) === Number(this.form.ma_lop_hoc))
      const homNay = new Date()
      homNay.setHours(0, 0, 0, 0)
      return (lop?.lich_hoc || []).filter((b) => new Date(`${b.ngay_hoc}T00:00:00`) >= homNay)
    },
    buoiDaChon() {
      return this.buoiHocCuaLop.find((b) => Number(b.id) === Number(this.form.ma_lich_hoc))
    },
  },
  async created() {
    const [resLop, resDon] = await Promise.all([
      api.get('/sinh-vien/lop-cua-toi'),
      api.get('/xin-phep'),
    ])
    this.lops = resLop.data.danh_sach
    this.danhSach = resDon.data.danh_sach
  },
  methods: {
    async gui() {
      this.dangGui = true
      this.thongBao = ''
      this.loi = ''
      try {
        const { data } = await api.post('/xin-phep', this.form)
        this.thongBao = data.message
        this.form = { ma_lop_hoc: '', ma_lich_hoc: '', ngay_nghi: '', ly_do: '' }
        const res = await api.get('/xin-phep')
        this.danhSach = res.data.danh_sach
      } catch (e) {
        this.loi = Object.values(e.response?.data?.errors || {})[0]?.[0] || e.response?.data?.message || 'Gửi thất bại.'
      } finally {
        this.dangGui = false
      }
    },
    doiLopHoc() {
      this.form.ma_lich_hoc = ''
      this.form.ngay_nghi = ''
    },
    chonBuoiHoc() {
      this.form.ngay_nghi = this.buoiDaChon?.ngay_hoc || ''
    },
    dinhDangNgay(ngay) {
      return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN')
    },
    tenThu(ngay) {
      return new Date(`${ngay}T00:00:00`).toLocaleDateString('vi-VN', { weekday: 'long' })
    },
    ten(t) {
      return { cho_duyet: 'Chờ duyệt', duoc_duyet: 'Được duyệt', tu_choi: 'Từ chối' }[t] || t
    },
    mau(t) {
      return {
        cho_duyet: 'bg-amber-100 text-amber-700',
        duoc_duyet: 'bg-emerald-100 text-emerald-700',
        tu_choi: 'bg-rose-100 text-rose-700',
      }[t] || 'bg-slate-100 text-slate-600'
    },
  },
}
</script>
