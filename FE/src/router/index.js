import { createRouter, createWebHistory } from 'vue-router'
import { useAuthStore } from '../stores/auth'

const routes = [
  { path: '/', redirect: '/dang-nhap' },

  // ---- Auth (US01) ----
  {
    path: '/dang-nhap',
    name: 'dang-nhap',
    component: () => import('../views/auth/dang-nhap.vue'),
    meta: { layout: 'blank', khongCanDangNhap: true },
  },

  // ---- Mobile: quét QR điểm danh (US08) ----
  {
    path: '/diem-danh/:maQr',
    name: 'diem-danh-mobile',
    component: () => import('../views/diem-danh/diem-danh-mobile.vue'),
    meta: { layout: 'blank', choPhepKhongDangNhap: true },
  },

  // ---- Sinh viên ----
  {
    path: '/sinh-vien',
    name: 'sinh-vien-trang-chu',
    component: () => import('../views/sinh-vien/trang-chu.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/dang-ky-lop',
    name: 'dang-ky-lop',
    component: () => import('../views/sinh-vien/dang-ky-lop.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/lich-hoc',
    name: 'sinh-vien-lich-hoc',
    component: () => import('../views/sinh-vien/lich-hoc.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/lich-su-diem-danh',
    name: 'lich-su-diem-danh',
    component: () => import('../views/sinh-vien/lich-su-diem-danh.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/xin-phep',
    name: 'xin-phep-vang',
    component: () => import('../views/sinh-vien/xin-phep-vang.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },
  {
    path: '/sinh-vien/diem',
    name: 'diem-cua-toi',
    component: () => import('../views/sinh-vien/diem-cua-toi.vue'),
    meta: { layout: 'default', vai_tro: 'sinh_vien' },
  },

  // ---- Phòng học trực tuyến (US06, US07, US12, US13) ----
  {
    path: '/phong-hoc/:maPhong',
    name: 'phong-hoc',
    component: () => import('../views/phong-hoc/phong-hoc.vue'),
    meta: { layout: 'blank', vai_tro: 'any' },
  },

  // ---- Giảng viên ----
  {
    path: '/giang-vien',
    name: 'giang-vien-trang-chu',
    component: () => import('../views/giang-vien/lich-day.vue'),
    meta: { layout: 'admin', vai_tro: 'giang_vien' },
  },
  {
    path: '/giang-vien/xin-phep',
    name: 'gv-xin-phep',
    component: () => import('../views/giang-vien/xin-phep.vue'),
    meta: { layout: 'admin', vai_tro: 'giang_vien' },
  },
  {
    path: '/giang-vien/diem',
    name: 'gv-quan-ly-diem',
    component: () => import('../views/giang-vien/quan-ly-diem.vue'),
    meta: { layout: 'admin', vai_tro: 'giang_vien' },
  },
  {
    path: '/giang-vien/diem-danh',
    name: 'gv-quan-ly-diem-danh',
    component: () => import('../views/giang-vien/quan-ly-diem-danh.vue'),
    meta: { layout: 'admin', vai_tro: 'giang_vien' },
  },

  // ---- Admin ----
  {
    path: '/admin',
    name: 'admin-dashboard',
    component: () => import('../views/admin/dashboard.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },
  {
    path: '/admin/tai-khoan',
    name: 'admin-tai-khoan',
    component: () => import('../views/admin/tai-khoan.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },
  {
    path: '/admin/mon-hoc',
    name: 'admin-mon-hoc',
    component: () => import('../views/admin/mon-hoc.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },
  {
    path: '/admin/lop-hoc',
    name: 'admin-lop-hoc',
    component: () => import('../views/admin/lop-hoc.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },
  {
    path: '/admin/phan-cong',
    name: 'admin-phan-cong',
    component: () => import('../views/admin/phan-cong.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },
  {
    path: '/admin/sinh-vien',
    name: 'admin-sinh-vien',
    component: () => import('../views/admin/sinh-vien.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },
  {
    path: '/admin/bao-cao',
    name: 'admin-bao-cao',
    component: () => import('../views/admin/bao-cao.vue'),
    meta: { layout: 'admin', vai_tro: 'admin' },
  },

  // ---- Chung ----
  {
    path: '/ho-so',
    name: 'ho-so',
    component: () => import('../views/ho-so/ho-so.vue'),
    meta: { layout: 'default', vai_tro: 'any' },
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
  if (vaiTroYeuCau && vaiTroYeuCau !== 'any' && auth.user?.vai_tro !== vaiTroYeuCau) {
    return trangChuTheoVaiTro(auth.user?.vai_tro)
  }

  return true
})

export function trangChuTheoVaiTro(vaiTro) {
  if (vaiTro === 'admin') return { name: 'admin-dashboard' }
  if (vaiTro === 'giang_vien') return { name: 'giang-vien-trang-chu' }
  return { name: 'sinh-vien-trang-chu' }
}

export default router
