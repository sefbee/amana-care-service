import './globals.css';
import { SiteFrame } from '../components/site-frame';
import { getSettings } from '../lib/cms';

const siteUrl = process.env.NEXT_PUBLIC_SITE_URL || 'http://localhost:3000';

export async function generateMetadata() {
  const settings = await getSettings();
  const siteName = settings.site_name || 'Amanah';
  return {
    metadataBase: new URL(siteUrl),
    title: { default: siteName, template: `%s | ${siteName}` },
    description: settings.tagline || undefined,
  };
}

export default function RootLayout({ children }) {
  return <html lang="en"><body><SiteFrame>{children}</SiteFrame></body></html>;
}
