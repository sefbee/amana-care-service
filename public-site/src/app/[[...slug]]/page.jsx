import { notFound } from 'next/navigation';
import CmsSection from '../../components/cms-section';
import { getPage, pagePath, safeImageUrl } from '../../lib/cms';

async function resolvedSlug(params) {
  const { slug } = await params;
  if (!slug?.length) return process.env.CMS_HOME_SLUG || 'home';
  if (slug.length !== 1 || !/^[a-z0-9]+(?:-[a-z0-9]+)*$/.test(slug[0])) return null;
  return slug[0];
}

export async function generateMetadata({ params }) {
  const slug = await resolvedSlug(params);
  if (!slug) return {};
  const page = await getPage(slug);
  if (!page) return {};
  const seo = page.seo || {};
  const image = safeImageUrl(seo.open_graph_image?.url);
  const canonical = typeof seo.canonical_url === 'string' && /^https?:\/\//i.test(seo.canonical_url)
    ? seo.canonical_url
    : new URL(pagePath(page.slug), process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000').toString();
  return {
    title: seo.title || page.title,
    description: seo.description || undefined,
    alternates: canonical ? { canonical } : undefined,
    openGraph: {
      title: seo.title || page.title,
      description: seo.description || undefined,
      url: canonical,
      images: image ? [{ url: image, alt: seo.open_graph_image?.alt || page.title }] : undefined,
      type: 'website',
    },
  };
}

export default async function CmsPage({ params }) {
  const slug = await resolvedSlug(params);
  if (!slug) notFound();
  const page = await getPage(slug);
  if (!page) notFound();
  return <article className="cms-page" aria-label={page.title}>
    {page.sections.map((section) => <CmsSection key={section.id} section={section} pageSlug={page.slug} />)}
  </article>;
}
