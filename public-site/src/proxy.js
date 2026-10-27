import { NextResponse } from 'next/server';

const isDevelopment = process.env.NODE_ENV !== 'production';
const turnstileOrigin = 'https://challenges.cloudflare.com';

function cmsApiOrigin() {
  const apiBase = process.env.NEXT_PUBLIC_CMS_API_BASE_URL || 'http://127.0.0.1:8000/api/v1/cms';

  try {
    return new URL(apiBase).origin;
  } catch {
    return null;
  }
}

export function proxy(request) {
  const nonce = Buffer.from(crypto.randomUUID()).toString('base64');
  const apiOrigin = cmsApiOrigin();
  const connectOrigins = ["'self'", apiOrigin, turnstileOrigin].filter(Boolean).join(' ');
  const csp = [
    "default-src 'self'",
    `script-src 'self' 'nonce-${nonce}' 'strict-dynamic' ${turnstileOrigin}${isDevelopment ? " 'unsafe-eval'" : ''}`,
    `style-src 'self' 'nonce-${nonce}'${isDevelopment ? " 'unsafe-inline'" : ''}`,
    "img-src 'self' data: blob: https:",
    "media-src 'self' blob: https:",
    "font-src 'self' data:",
    `connect-src ${connectOrigins}`,
    `frame-src 'self' ${turnstileOrigin}`,
    "frame-ancestors 'none'",
    "form-action 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    "worker-src 'self' blob:",
  ];
  if (!isDevelopment) csp.push('upgrade-insecure-requests');
  const policy = csp.join('; ');

  const requestHeaders = new Headers(request.headers);
  requestHeaders.set('x-nonce', nonce);
  requestHeaders.set('Content-Security-Policy', policy);

  const response = NextResponse.next({ request: { headers: requestHeaders } });
  response.headers.set('Content-Security-Policy', policy);
  response.headers.set('X-Content-Type-Options', 'nosniff');
  response.headers.set('X-Frame-Options', 'DENY');
  response.headers.set('Referrer-Policy', 'strict-origin-when-cross-origin');
  response.headers.set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

  if (!isDevelopment) {
    response.headers.set('Strict-Transport-Security', 'max-age=31536000');
  }

  return response;
}

export const config = {
  matcher: [
    '/((?!api|_next/static|_next/image|favicon.ico|robots.txt|sitemap.xml).*)',
  ],
};
