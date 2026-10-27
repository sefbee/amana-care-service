'use client';

export default function ErrorPage({ reset }) {
  return <section className="not-found"><h1>Website content is temporarily unavailable</h1><p>Please try again in a moment.</p><button className="button button-primary" onClick={() => reset()}>Try again</button></section>;
}
