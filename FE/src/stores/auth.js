import { defineStore } from 'pinia'
import api from '../utils/axios'
import { huyEcho } from '../utils/echo'
import { useThongBaoStore } from './thong-bao'

export const useAuthStore = defineStore('auth', {
  state: () => ({
    token: localStorage.getItem('token') || null,
    user: JSON.parse(localStorage.getItem('user') || 'null'),
  }),

  getters: {
    daDangNhap: (s) => !!s.token,
    laAdmin: (s) => s.user?.vai_tro === 'admin',
    laGiangVien: (s) => s.user?.vai_tro === 'giang_vien',
    laSinhVien: (s) => s.user?.vai_tro === 'sinh_vien',
    hoTen: (s) => s.user?.ho_ten || '',
  },

  actions: {
    async dangNhap(email, matKhau) {
      const { data } = await api.post('/dang-nhap', { email, mat_khau: matKhau })
      this.token = data.token
      this.user = data.tai_khoan
      localStorage.setItem('token', data.token)
      localStorage.setItem('user', JSON.stringify(data.tai_khoan))
      return data.tai_khoan
    },

    async dangXuat() {
      try {
        await api.post('/dang-xuat')
      } catch {
        // token hết hạn cũng xem như đã đăng xuất
      }
      // Hủy kết nối WebSocket cũ — token đã thu hồi, kênh private sẽ không còn xác thực được
      huyEcho()
      useThongBaoStore().reset()
      this.token = null
      this.user = null
      localStorage.removeItem('token')
      localStorage.removeItem('user')
    },

    async taiLaiHoSo() {
      const { data } = await api.get('/me')
      this.user = data.tai_khoan
      localStorage.setItem('user', JSON.stringify(data.tai_khoan))
    },
  },
})
