import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', redirect: '/dang-nhap' },
  {
    path: '/dang-nhap',
    name: 'dang-nhap',
    component: () => import('../views/auth/dang-nhap.vue'),
    meta: { layout: 'blank', khongCanDangNhap: true },
  },
  {
    path: '/diem-danh/:maQr',
    name: 'diem-danh-mobile',
    component: () => import('../views/diem-danh/diem-danh-mobile.vue'),
    meta: { layout: 'blank', choPhepKhongDangNhap: true },
  },

  // MVP sinh viên: đăng ký lớp, lịch học và lịch sử điểm danh.
  {
    path: '/sinh-vien',
    name: 'sinh-vien-trang-chu',
    redirect: { name: 'sinh-vien-lich-hoc' },
    meta: { vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/lich-hoc',
    name: 'sinh-vien-lich-hoc',
    component: () => import('../views/sinh-vien/lich-hoc.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/dang-ky-lop',
    name: 'dang-ky-lop',
    component: () => import('../views/sinh-vien/dang-ky-lop.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/lich-su-diem-danh',
    name: 'lich-su-diem-danh',
    component: () => import('../views/sinh-vien/lich-su-diem-danh.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },

  // MVP giảng viên: lịch dạy và quản lý phiên/danh sách điểm danh.
  {
    path: '/giang-vien',
    name: 'giang-vien-trang-chu',
    component: () => import('../views/giang-vien/lich-day.vue'),
    meta: { layout: 'admin', vai_tro: 'giang_vien' },
  },
  {
    path: '/giang-vien/diem-danh',
    name: 'gv-quan-ly-diem-danh',
    component: () => import('../views/giang-vien/quan-ly-diem-danh.vue'),
    meta: { layout: 'admin', vai_tro: 'giang_vien' },
  },

  // Giữ đường đi hợp lệ cho tài khoản admin trong giai đoạn MVP.
  {
    path: '/mvp-admin',
    name: 'mvp-admin',
    component: () => import('../views/chung/mvp-admin.vue'),
    meta: { layout: 'blank', vai_tro: 'admin' },
  },
  { path: '/:pathMatch(.*)*', redirect: '/dang-nhap' },
]

const router = createRouter({
  history: createWebHistory(),
  routes,
})

router.beforeEach((to) => {
  const auth = useAuthStore()

  if (to.meta.khongCanDangNhap && auth.daDangNhap) {
    return trangChuTheoVaiTro(auth.user?.vai_tro)
  }

  if (!to.meta.khongCanDangNhap && !to.meta.choPhepKhongDangNhap && !auth.daDangNhap) {
    return { name: 'dang-nhap' }
  }

  const vaiTroYeuCau = to.meta.vai_tro
  if (vaiTroYeuCau && auth.user?.vai_tro !== vaiTroYeuCau) {
    return trangChuTheoVaiTro(auth.user?.vai_tro)
  }

  return true
})

export function trangChuTheoVaiTro(vaiTro) {
  if (vaiTro === 'admin') return { name: 'mvp-admin' }
  if (vaiTro === 'giang_vien') return { name: 'giang-vien-trang-chu' }
  return { name: 'sinh-vien-trang-chu' }
}

export default router

