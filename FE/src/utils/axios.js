import axios from 'axios'
import router from '../router'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/',
  headers: { Accept: 'application/json' },
})

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('token')
  if (token) config.headers.Authorization = `Bearer ${token}`
  // Gửi kèm socket id để broadcast ->toOthers() loại đúng người gửi (tránh tin nhắn nhân đôi)
  if (window.Echo?.socketId()) {
    config.headers['X-Socket-Id'] = window.Echo.socketId()
  }
  return config
})

api.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response && err.response.status === 401) {
      localStorage.removeItem('token')
      localStorage.removeItem('user')
      router.push({ name: 'dang-nhap' })
    }
    return Promise.reject(err)
  }
)

export default api
