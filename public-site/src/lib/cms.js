const apiBase = (process.env.NEXT_PUBLIC_CMS_API_BASE_URL || 'http://127.0.0.1:8000/api/v1/cms').replace(/\/$/, '');

async function getJson(path) {
  const response = await fetch(`${apiBase}${path}`, { cache: 'no-store', headers: { Accept: 'application/json' } });
  if (response.status === 404) return null;
  if (!response.ok) throw new Error('CMS API request failed.');
  const payload = await response.json();
  return payload?.data ?? null;
}

export function getPage(slug) {
  return getJson(`/pages/${encodeURIComponent(slug)}`);
}

export function getPageIndex() {
  return getJson('/pages');
}

export async function getNavigation(location) {
  return (await getJson(`/navigation/${location}`)) ?? [];
}

export async function getSettings() {
  return (await getJson('/settings')) ?? {};
}

export function safeImageUrl(value) {
  if (typeof value !== 'string') return null;
  try {
    const url = new URL(value);
    return ['http:', 'https:'].includes(url.protocol) ? url.toString() : null;
  } catch {
    return null;
  }
}

export function safeThemeColor(value, fallback) {
  return typeof value === 'string' && /^#[0-9a-f]{6}$/i.test(value) ? value : fallback;
}

export function safeLink(value) {
  if (typeof value !== 'string' || /[\u0000-\u0020\\]/.test(value)) return '#';
  if (value.startsWith('/') && !value.startsWith('//')) return value;
  try {
    const url = new URL(value);
    return ['https:', 'mailto:', 'tel:'].includes(url.protocol) ? value : '#';
  } catch {
    return '#';
  }
}

export function pagePath(slug) {
  const homeSlug = process.env.CMS_HOME_SLUG || 'home';
  return slug === homeSlug ? '/' : `/${encodeURIComponent(slug)}`;
}
