import Link from 'next/link';

export default function NotFound() {
  return <section className="not-found"><p className="eyebrow">404</p><h1>We couldn’t find that page</h1><p>The page may have moved or is not currently available.</p><Link className="button button-primary" href="/">Go to homepage</Link></section>;
}
