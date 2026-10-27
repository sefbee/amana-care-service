import Link from 'next/link';
import LeadForm from './lead-form';
import { getSettings, pagePath, safeImageUrl, safeLink } from '../lib/cms';

function ActionLink({ href, children, className = 'button button-primary' }) {
  if (!href || !children) return null;
  const target = safeLink(href);
  if (target === '#') return null;
  const localTarget = target === `/${process.env.CMS_HOME_SLUG || 'home'}` ? '/' : target;
  return /^https:\/\//i.test(target) || /^(mailto|tel):/i.test(target)
    ? <a className={className} href={target} rel="noopener noreferrer">{children}</a>
    : <Link className={className} href={localTarget}>{children}</Link>;
}

function Hero({ content, id }) {
  const image = safeImageUrl(content.background_media?.url);
  const style = image ? { backgroundImage: `linear-gradient(90deg, rgba(9,34,31,.82), rgba(9,34,31,.2)), url("${image}")` } : undefined;
  return <section className="hero-section" style={style} aria-labelledby={`section-${id}`}>
    <div className="hero-content"><p className="eyebrow">{content.eyebrow}</p><h1 id={`section-${id}`}>{content.headline}</h1>{content.subheadline && <p className="hero-copy">{content.subheadline}</p>}<ActionLink href={content.cta_link}>{content.cta_label}</ActionLink></div>
  </section>;
}

function RichText({ content }) {
  return <section className="content-section"><div className="section-inner rich-content" dangerouslySetInnerHTML={{ __html: content.html || '' }} /></section>;
}

function ServiceGrid({ content }) {
  return <section className="content-section soft-section"><div className="section-inner">
    <div className="section-heading">{content.title && <h2>{content.title}</h2>}{content.body && <p>{content.body}</p>}</div>
    <div className="service-grid">{(content.services || []).map((service) => <article className="service-card" key={service.code}>
      <p className="eyebrow">{service.variant || service.code}</p><h3>{service.name}</h3><p>{service.description}</p>
      {service.price && <p className="service-price"><strong>{service.price.formatted}</strong><span> / {service.price.unit}</span></p>}
    </article>)}</div>
  </div></section>;
}

function ImageTextSplit({ content }) {
  const image = safeImageUrl(content.media?.url);
  return <section className="content-section"><div className={`section-inner split-layout ${content.layout_alignment === 'right' ? 'image-right' : ''}`}>
    <div className="split-image">{image && <img src={image} alt={content.media?.alt || ''} loading="lazy" />}</div>
    <div className="split-copy">{content.eyebrow && <p className="eyebrow">{content.eyebrow}</p>}<h2>{content.title}</h2><p>{content.body}</p><ActionLink href={content.cta_link}>{content.cta_label}</ActionLink></div>
  </div></section>;
}

function Testimonials({ content }) {
  return <section className="content-section soft-section"><div className="section-inner">
    {content.title && <div className="section-heading"><h2>{content.title}</h2></div>}
    <div className="testimonial-grid">{(content.items || []).map((item, index) => <figure className="testimonial-card" key={`${item.client_name}-${index}`}>
      <div className="rating" role="img" aria-label={`${item.rating} out of 5 stars`}>{'★'.repeat(Math.max(1, Math.min(5, Number(item.rating) || 1)))}</div>
      <blockquote>“{item.review_text}”</blockquote><figcaption>{item.client_name}</figcaption>
    </figure>)}</div>
  </div></section>;
}

function Faq({ content }) {
  return <section className="content-section"><div className="section-inner faq-wrap">
    {content.title && <div className="section-heading"><h2>{content.title}</h2></div>}
    {(content.items || []).map((item, index) => <details className="faq-item" key={`${item.question}-${index}`}><summary>{item.question}</summary><p>{item.answer}</p></details>)}
  </div></section>;
}

export default async function CmsSection({ section, pageSlug }) {
  const content = section.content || {};
  switch (section.type) {
    case 'hero': return <Hero id={section.id} content={content} />;
    case 'rich_text': return <RichText content={content} />;
    case 'service_grid': return <ServiceGrid content={content} />;
    case 'image_text_split': return <ImageTextSplit content={content} />;
    case 'testimonial_carousel': return <Testimonials content={content} />;
    case 'faq_accordion': return <Faq content={content} />;
    case 'dynamic_lead_form': return <LeadForm section={section} pageSlug={pageSlug} settings={await getSettings()} />;
    default: return null;
  }
}
