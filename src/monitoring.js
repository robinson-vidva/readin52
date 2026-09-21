// Optional error monitoring via Sentry. Dormant unless SENTRY_DSN is set.
let Sentry = null;

export async function init() {
  if (!process.env.SENTRY_DSN) return;
  try {
    const mod = await import('@sentry/node');
    mod.init({
      dsn: process.env.SENTRY_DSN,
      environment: process.env.NODE_ENV || 'production',
      tracesSampleRate: 0,
    });
    Sentry = mod;
    console.log('  🛡️  Error monitoring (Sentry) enabled');
  } catch (e) {
    console.error('Sentry init failed:', e.message);
  }
}

export function capture(err) {
  if (!Sentry) return;
  try { Sentry.captureException(err); } catch {}
}
