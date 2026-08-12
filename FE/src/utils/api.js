import tenant from '../config/tenant.json';

export function apiUrl(path) {
  const baseFromEnv = import.meta.env.VITE_API_URL;
  const base = (baseFromEnv || tenant.base_url || '').replace(/\/+$/, '');
  
  const p = String(path || '').replace(/^\/+/, '');
  
  return `${base}/${p}`;
}

export default apiUrl;