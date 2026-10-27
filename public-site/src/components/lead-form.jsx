'use client';

import Script from 'next/script';
import { useEffect, useRef, useState } from 'react';

export default function LeadForm({ section, pageSlug, settings }) {
  const content = section.content || {};
  const fields = Array.isArray(content.fields) ? content.fields : [];
  const siteKey = settings.turnstile_site_key;
  const widgetRef = useRef(null);
  const widgetId = useRef(null);
  const [captchaToken, setCaptchaToken] = useState('');
  const [captchaReady, setCaptchaReady] = useState(false);
  const [consent, setConsent] = useState(false);
  const [values, setValues] = useState({});
  const [message, setMessage] = useState('');
  const [sending, setSending] = useState(false);

  const renderWidget = () => {
    if (!siteKey || !window.turnstile || !widgetRef.current || widgetId.current !== null) return;
    widgetId.current = window.turnstile.render(widgetRef.current, {
      sitekey: siteKey,
      callback: (token) => setCaptchaToken(token),
      'expired-callback': () => setCaptchaToken(''),
      'error-callback': () => setCaptchaToken(''),
    });
    setCaptchaReady(true);
  };

  useEffect(() => {
    return () => {
      if (widgetId.current !== null && window.turnstile) window.turnstile.remove(widgetId.current);
    };
  }, []);

  async function submit(event) {
    event.preventDefault();
    setMessage('');
    if (!consent || !captchaToken) {
      setMessage('Please accept the consent notice and complete the verification.');
      return;
    }
    setSending(true);
    try {
      const response = await fetch(`${process.env.NEXT_PUBLIC_CMS_API_BASE_URL || 'http://127.0.0.1:8000/api/v1/cms'}/leads`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
        body: JSON.stringify({ page_slug: pageSlug, data: values, consent: true, captcha_token: captchaToken }),
      });
      if (!response.ok) {
        setMessage(response.status === 409
          ? 'An inquiry with these details may already exist. Please contact our team directly.'
          : 'We could not submit your inquiry right now. Please try again or call our team.');
        if (window.turnstile && widgetId.current !== null) window.turnstile.reset(widgetId.current);
        setCaptchaToken('');
        return;
      }
      setValues({});
      setConsent(false);
      setMessage(content.success_message || 'Thank you. Your inquiry has been received.');
      if (window.turnstile && widgetId.current !== null) window.turnstile.reset(widgetId.current);
      setCaptchaToken('');
    } catch {
      setMessage('We could not submit your inquiry right now. Please try again or call our team.');
    } finally {
      setSending(false);
    }
  }

  return (
    <>
      {siteKey && <Script src="https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit" strategy="afterInteractive" onLoad={renderWidget} />}
      <section className="content-section lead-section" aria-labelledby={`lead-${section.id}`}>
        <div className="section-inner lead-layout">
          <div>{content.eyebrow && <p className="eyebrow">{content.eyebrow}</p>}<h2 id={`lead-${section.id}`}>{content.title || 'How can we help?'}</h2>{content.body && <p className="lead-copy">{content.body}</p>}</div>
          <form className="lead-form" onSubmit={submit}>
            {fields.map((field) => {
              const key = field.key;
              if (!['full_name', 'phone', 'email', 'service_interest', 'area'].includes(key)) return null;
              const inputType = key === 'email' ? 'email' : key === 'phone' ? 'tel' : 'text';
              const isArea = key === 'area';
              return <label className="field" key={key}>{field.label || key}
                {isArea ? <select required={!!field.required} value={values[key] || ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })}>
                  <option value="">Choose an area</option>{(settings.service_areas || []).map((area) => <option key={area} value={area}>{area}</option>)}
                </select> : <input type={inputType} name={key} required={key === 'full_name' || key === 'phone' || !!field.required} autoComplete={key === 'full_name' ? 'name' : key === 'phone' ? 'tel' : key === 'email' ? 'email' : 'off'} value={values[key] || ''} onChange={(event) => setValues({ ...values, [key]: event.target.value })} />}
              </label>;
            })}
            {settings.lead_form_consent_text && <label className="consent"><input type="checkbox" checked={consent} onChange={(event) => setConsent(event.target.checked)} required /><span>{settings.lead_form_consent_text}</span></label>}
            {!settings.lead_form_consent_text && <p className="form-error">Inquiry form is not available until consent information is published.</p>}
            {siteKey ? <div ref={widgetRef} className="turnstile" aria-label="Spam protection" /> : <p className="form-error">Inquiry verification is not configured.</p>}
            <button className="button button-primary" type="submit" disabled={sending || !siteKey || !settings.lead_form_consent_text}>{sending ? 'Sending…' : content.submit_label || 'Send inquiry'}</button>
            <p className="form-status" aria-live="polite">{message || (siteKey && !captchaReady ? 'Loading verification…' : '')}</p>
          </form>
        </div>
      </section>
    </>
  );
}
