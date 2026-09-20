// Cloudflare Turnstile bot protection. Dormant unless both keys are set:
//   TURNSTILE_SITE_KEY (public), TURNSTILE_SECRET_KEY (secret).
const VERIFY = 'https://challenges.cloudflare.com/turnstile/v0/siteverify';

export function isEnabled() {
  return !!process.env.TURNSTILE_SITE_KEY && !!process.env.TURNSTILE_SECRET_KEY;
}
export function siteKey() {
  return process.env.TURNSTILE_SITE_KEY || '';
}

// Returns true when disabled (nothing to check) or when the token verifies.
export async function verify(token, ip) {
  if (!isEnabled()) return true;
  if (!token) return false;
  try {
    const body = new URLSearchParams({ secret: process.env.TURNSTILE_SECRET_KEY, response: token });
    if (ip) body.set('remoteip', ip);
    const res = await fetch(VERIFY, { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body });
    const data = await res.json().catch(() => ({}));
    return !!data.success;
  } catch {
    return false;
  }
}
