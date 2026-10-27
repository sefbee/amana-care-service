import Link from 'next/link';
import { getNavigation, getSettings, safeImageUrl, safeLink, safeThemeColor } from '../lib/cms';

function MenuLinks({ links }) {
  return links.map((item, index) => {
    const rawHref = safeLink(item.url);
    const homeSlug = process.env.CMS_HOME_SLUG || 'home';
    const href = rawHref === `/${homeSlug}` ? '/' : rawHref;
    const external = item.external || /^https:\/\//i.test(href);
    const target = item.opens_new_tab ? '_blank' : undefined;
    const rel = target ? 'noopener noreferrer' : undefined;
    return (
      <div className="nav-item" key={`${item.label}-${index}`}>
        {external ? <a href={href} target={target} rel={rel}>{item.label}</a> : <Link href={href}>{item.label}</Link>}
        {item.children?.length > 0 && <div className="nav-dropdown"><MenuLinks links={item.children} /></div>}
      </div>
    );
  });
}

export async function SiteFrame({ children }) {
  const [settings, header, footer] = await Promise.all([
    getSettings(), getNavigation('header'), getNavigation('footer'),
  ]);
  const primary = safeThemeColor(settings.primary_color, '#174a45');
  const secondary = safeThemeColor(settings.secondary_color, '#d59a52');
  const logo = safeImageUrl(settings.logo?.url);
  const siteName = settings.site_name || 'Amanah';

  return (
    <div className="site-shell" style={{ '--brand-primary': primary, '--brand-accent': secondary }}>
      <a className="skip-link" href="#main-content">Skip to content</a>
      <header className="site-header">
        <div className="header-inner">
          <Link className="brand" href="/" aria-label={`${siteName} home`}>
            {logo ? <img src={logo} alt={settings.logo?.alt || siteName} /> : <span className="brand-mark" aria-hidden="true">A</span>}
            <span><strong>{siteName}</strong>{settings.tagline && <small>{settings.tagline}</small>}</span>
          </Link>
          <nav className="main-nav" aria-label="Main navigation"><MenuLinks links={header} /></nav>
      <div className="header-contact">
        {settings.whatsapp_url && <a className="header-whatsapp" href={safeLink(settings.whatsapp_url)} target="_blank" rel="noopener noreferrer">{settings.whatsapp_button_label || 'WhatsApp'}</a>}
        {settings.support_phone && <a className="header-phone" href={`tel:${settings.support_phone}`}>{settings.call_button_label || 'Call'} {settings.support_phone}</a>}
      </div>
        </div>
      </header>
      <main id="main-content">{children}</main>
      <footer className="site-footer">
        <div className="footer-grid">
          <div><h2>{siteName}</h2>{settings.footer_about && <p>{settings.footer_about}</p>}</div>
          <nav aria-label={settings.footer_navigation_heading || 'Footer navigation'}><h2>{settings.footer_navigation_heading || 'Explore'}</h2><MenuLinks links={footer} /></nav>
          <div><h2>{settings.footer_contact_heading || 'Contact'}</h2>
            {settings.support_phone && <p><a href={`tel:${settings.support_phone}`}>{settings.support_phone}</a></p>}
            {settings.support_email && <p><a href={`mailto:${settings.support_email}`}>{settings.support_email}</a></p>}
            {settings.support_address && <p>{settings.support_address}</p>}
            {settings.whatsapp_url && <p><a href={safeLink(settings.whatsapp_url)} target="_blank" rel="noopener noreferrer">{settings.whatsapp_button_label || 'Message us on WhatsApp'}</a></p>}
            <div className="social-links">
              {settings.facebook_url && <a href={safeLink(settings.facebook_url)} rel="noopener noreferrer">Facebook</a>}
              {settings.instagram_url && <a href={safeLink(settings.instagram_url)} rel="noopener noreferrer">Instagram</a>}
              {settings.linkedin_url && <a href={safeLink(settings.linkedin_url)} rel="noopener noreferrer">LinkedIn</a>}
            </div>
          </div>
        </div>
        <div className="footer-bottom">{settings.footer_copyright || `© ${new Date().getFullYear()} ${siteName}`}</div>
      </footer>
    </div>
  );
}
