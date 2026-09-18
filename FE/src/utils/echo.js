import Echo from 'laravel-echo'
import Pusher from 'pusher-js'
import { apiUrl } from './api'

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
  const reverbHost = import.meta.env.VITE_REVERB_HOST || window.location.hostname
  const reverbPort = Number(import.meta.env.VITE_REVERB_PORT || 8080)
  const reverbTls = (import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(':', '')) === 'https'

  window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY || 'local',
    wsHost: reverbHost,
    wsPort: reverbPort,
    wssPort: reverbPort,
    forceTLS: reverbTls,
    enabledTransports: ['ws', 'wss'],
    authEndpoint: apiUrl('broadcasting/auth'),
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
