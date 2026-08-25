import { defineStore } from 'pinia'
import api from '../utils/axios'
import { taoEcho } from '../utils/echo'

export const useThongBaoStore = defineStore('thongBao', {
  state: () => ({
    danhSach: [],
    chuaDoc: 0,
    tokenDangNghe: null,
  }),

  actions: {
    async khoiTao(user, token) {
      if (!user?.id || !token) return

      try {
        const { data } = await api.get('/thong-bao')
        this.danhSach = data.danh_sach || []
        this.chuaDoc = Number(data.chua_doc) || 0
      } catch {
        // Thông báo không được làm gián đoạn luồng chính của ứng dụng.
      }

      if (this.tokenDangNghe === token) return

      this.tokenDangNghe = token
      taoEcho(token)
        .private(`nguoi-dung.${user.id}`)
        .listen('.thong.bao.moi', (event) => {
          const thongBao = event.thong_bao
          if (!thongBao) return
          this.danhSach = [thongBao, ...this.danhSach.filter((item) => item.id !== thongBao.id)].slice(0, 50)
          if (!thongBao.da_doc) this.chuaDoc += 1
        })
    },

    async danhDauDaDoc(id) {
      await api.post(`/thong-bao/${id}/da-doc`)
      const thongBao = this.danhSach.find((item) => item.id === id)
      if (thongBao && !thongBao.da_doc) {
        thongBao.da_doc = true
        this.chuaDoc = Math.max(0, this.chuaDoc - 1)
      }
    },

    async danhDauTatCaDaDoc() {
      await api.post('/thong-bao/doc-tat-ca')
      this.danhSach.forEach((item) => { item.da_doc = true })
      this.chuaDoc = 0
    },

    reset() {
      this.danhSach = []
      this.chuaDoc = 0
      this.tokenDangNghe = null
    },
  },
})

