import { getPageIndex, pagePath } from '../lib/cms';

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000';
export const dynamic = 'force-dynamic';

export default async function sitemap() {
  let pages;
  try {
    pages = await getPageIndex();
  } catch {
    return [];
  }
  if (!Array.isArray(pages)) return [];
  return pages.map((page) => {
    const canonical = typeof page.seo?.canonical_url === 'string' && /^https?:\/\//i.test(page.seo.canonical_url)
      ? page.seo.canonical_url
      : new URL(pagePath(page.slug), siteUrl).toString();
    return { url: canonical, lastModified: page.published_at ? new Date(page.published_at) : undefined };
  });
}
