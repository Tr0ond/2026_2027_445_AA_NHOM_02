import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

// Token của phiên Echo hiện tại — nếu đổi tài khoản phải tạo lại kết nối WS
let tokenDangDung = null

/**
 * Kết nối Laravel Echo tới Reverb server (port 8080).
 * Xác thực kênh private bằng Sanctum token qua /broadcasting/auth.
 * Quan trọng: mỗi lần đăng nhập bằng tài khoản khác, Echo cũ phải bị hủy
 * (token cũ đã bị thu hồi → kênh private không xác thực được → không nhận event).
 */
export function taoEcho(token) {
  if (window.Echo && tokenDangDung === token) {
    return window.Echo
  }

  if (window.Echo) {
    window.Echo.disconnect()
    window.Echo = null
  }

  tokenDangDung = token

  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'local',
    wsHost: import.meta.env.VITE_REVERB_HOST || 'localhost',
    wsPort: import.meta.env.VITE_REVERB_PORT || 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT || 8080,
    forceTLS: false,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: (import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api/') + 'broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  })

  return window.Echo
}

export function huyEcho() {
  if (window.Echo) {
    window.Echo.disconnect()
    window.Echo = null
  }
  tokenDangDung = null
}
