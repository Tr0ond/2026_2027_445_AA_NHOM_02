import tenant from '../config/tenant.json';

export function apiBaseUrl() {
  const configured = import.meta.env.VITE_API_URL || tenant.base_url || 'http://127.0.0.1:8000/api/';
  const base = new URL(configured, typeof window !== 'undefined' ? window.location.origin : 'http://127.0.0.1');

  // A LAN-hosted Vite app must not call 127.0.0.1 on the client device.
  if (typeof window !== 'undefined' && !['localhost', '127.0.0.1'].includes(window.location.hostname)) {
    if (['localhost', '127.0.0.1'].includes(base.hostname)) base.hostname = window.location.hostname;
  }

  return base.toString().replace(/\/+$/, '');
}

export function apiUrl(path) {
  const base = apiBaseUrl();
  const p = String(path || '').replace(/^\/+/, '');

  return base + '/' + p;
}

export default apiUrl;
