// Transactional email via Resend (https://resend.com).
// Configure with RESEND_API_KEY and EMAIL_FROM (e.g. "ReadIn52 <noreply@askdevotions.com>").
const API = 'https://api.resend.com/emails';

// ---- Brand palette (email-safe, all inline) ----
const C = {
  bg: '#f4efe6', card: '#ffffff', border: '#e7ddcf', ink: '#2a231d', muted: '#8a7e6d',
  brand: '#7a3e22', accent: '#b0603a', soft: '#faf6ef',
};

export function isConfigured() {
  return !!process.env.RESEND_API_KEY && !!process.env.EMAIL_FROM;
}

export async function send({ to, subject, html, text, replyTo }) {
  if (!isConfigured()) return { success: false, error: 'Email is not configured' };
  try {
    const res = await fetch(API, {
      method: 'POST',
      headers: { Authorization: `Bearer ${process.env.RESEND_API_KEY}`, 'Content-Type': 'application/json' },
      body: JSON.stringify({
        from: process.env.EMAIL_FROM,
        to: Array.isArray(to) ? to : [to],
        subject,
        html,
        text: text || stripTags(html),
        ...(replyTo ? { reply_to: replyTo } : {}),
      }),
    });
    if (!res.ok) {
      const body = await res.text().catch(() => '');
      return { success: false, error: `Resend ${res.status}: ${body}` };
    }
    const data = await res.json().catch(() => ({}));
    return { success: true, id: data.id };
  } catch (e) {
    return { success: false, error: e.message };
  }
}

// ---- Reusable, email-client-safe shell (tables + inline styles) ----
function shell({ appName, preheader, heading, lead, paras = [], button, altUrl, note }) {
  const serif = "Georgia,'Times New Roman',serif";
  const sans = "-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Helvetica,Arial,sans-serif";
  const body = paras.map((p) =>
    `<p style="margin:0 0 16px;font:400 16px/1.65 ${sans};color:${C.ink}">${p}</p>`).join('');

  const btn = button ? `
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:6px 0 20px">
      <tr><td align="center" bgcolor="${C.accent}" style="border-radius:12px">
        <a href="${button.href}" target="_blank"
           style="display:inline-block;padding:14px 30px;font:600 16px ${sans};color:#ffffff;text-decoration:none;border-radius:12px">
          ${button.label}
        </a>
      </td></tr>
    </table>` : '';

  const alt = altUrl ? `
    <p style="margin:0 0 4px;font:400 13px/1.5 ${sans};color:${C.muted}">Or paste this link into your browser:</p>
    <p style="margin:0 0 20px;font:400 13px/1.5 ${sans};word-break:break-all">
      <a href="${altUrl}" style="color:${C.accent}">${altUrl}</a></p>` : '';

  return `<!DOCTYPE html>
<html lang="en"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="color-scheme" content="light"><meta name="x-apple-disable-message-reformatting">
<title>${escAttr(heading)}</title></head>
<body style="margin:0;padding:0;background:${C.bg}">
  <span style="display:none!important;visibility:hidden;opacity:0;color:transparent;height:0;width:0;overflow:hidden">${escHtml(preheader || '')}</span>
  <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:${C.bg}">
    <tr><td align="center" style="padding:28px 16px 40px">
      <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px">

        <!-- Brand lockup -->
        <tr><td style="padding:8px 4px 18px">
          <table role="presentation" cellpadding="0" cellspacing="0" border="0"><tr>
            <td style="width:38px;height:38px;background:${C.brand};border-radius:10px;text-align:center;vertical-align:middle;font:700 15px ${serif};color:#fff">52</td>
            <td style="padding-left:12px;font:700 21px ${serif};color:${C.brand}">${escHtml(appName)}</td>
          </tr></table>
        </td></tr>

        <!-- Card -->
        <tr><td style="background:${C.card};border:1px solid ${C.border};border-radius:18px;padding:34px 34px 30px">
          <h1 style="margin:0 0 6px;font:600 24px/1.25 ${serif};color:${C.ink}">${escHtml(heading)}</h1>
          ${lead ? `<p style="margin:0 0 18px;font:400 15px ${sans};color:${C.muted}">${escHtml(lead)}</p>` : ''}
          ${body}
          ${btn}
          ${alt}
          ${note ? `<hr style="border:none;border-top:1px solid ${C.border};margin:8px 0 16px">
            <p style="margin:0;font:400 13px/1.6 ${sans};color:${C.muted}">${note}</p>` : ''}
        </td></tr>

        <!-- Footer -->
        <tr><td style="padding:22px 8px 0;text-align:center">
          <p style="margin:0 0 4px;font:600 13px ${serif};color:${C.brand}">${escHtml(appName)}</p>
          <p style="margin:0;font:400 12px ${sans};color:${C.muted}">Journey Through Scripture in 52 Weeks</p>
          <p style="margin:10px 0 0;font:italic 12px ${serif};color:${C.muted}">“Your word is a lamp for my feet, a light on my path.” — Psalm 119:105</p>
        </td></tr>

      </table>
    </td></tr>
  </table>
</body></html>`;
}

function textVersion({ appName, heading, paras = [], button, altUrl, note }) {
  const lines = [appName, '='.repeat(appName.length), '', heading, ''];
  paras.forEach((p) => { lines.push(stripTags(p)); lines.push(''); });
  if (button) { lines.push(`${button.label}: ${button.href}`); lines.push(''); }
  else if (altUrl) { lines.push(altUrl); lines.push(''); }
  if (note) { lines.push(stripTags(note)); lines.push(''); }
  lines.push('— ' + appName + ' · Journey Through Scripture in 52 Weeks');
  return lines.join('\n');
}

// ---- Specific emails ----
export function sendPasswordReset(to, name, link, appName = 'ReadIn52') {
  const opts = {
    appName,
    preheader: `Reset your ${appName} password — this link expires in 1 hour.`,
    heading: 'Reset your password',
    lead: `Hi ${name || 'there'},`,
    paras: [
      `We received a request to reset the password for your ${escHtml(appName)} account.`,
      `Click the button below to choose a new password. For your security, this link expires in <strong>1 hour</strong>.`,
    ],
    button: { href: link, label: 'Reset password' },
    altUrl: link,
    note: `If you didn’t request this, you can safely ignore this email — your password won’t change.`,
  };
  return send({ to, subject: `Reset your ${appName} password`, html: shell(opts), text: textVersion(opts) });
}

export function sendEmailVerification(to, name, link, appName = 'ReadIn52') {
  const opts = {
    appName,
    preheader: `Confirm your new email address for ${appName}.`,
    heading: 'Confirm your email',
    lead: `Hi ${name || 'there'},`,
    paras: [
      `Please confirm this email address to finish updating your ${escHtml(appName)} account.`,
      `This link expires in <strong>1 hour</strong>. Your email won’t change until you confirm.`,
    ],
    button: { href: link, label: 'Confirm email address' },
    altUrl: link,
    note: `If you didn’t request this change, you can ignore this email and nothing will happen.`,
  };
  return send({ to, subject: `Confirm your new email for ${appName}`, html: shell(opts), text: textVersion(opts) });
}

export function sendWelcome(to, name, link, appName = 'ReadIn52') {
  const opts = {
    appName,
    preheader: `Welcome to ${appName} — read the whole Bible in 52 weeks.`,
    heading: `Welcome, ${name || 'friend'}!`,
    paras: [
      `Your ${escHtml(appName)} account is ready. 🎉`,
      `Read through the whole Bible in a year across four balanced tracks — Psalms &amp; Wisdom, Law &amp; History, the Prophets, and the Gospels &amp; Letters — with a built-in reader, chapter-level progress, streaks, and notes.`,
    ],
    button: { href: link, label: 'Start reading' },
    note: `Blessings on your journey through Scripture.`,
  };
  return send({ to, subject: `Welcome to ${appName}`, html: shell(opts), text: textVersion(opts) });
}

// ---- helpers ----
function escHtml(s) { return String(s).replace(/[<>]/g, (c) => ({ '<': '&lt;', '>': '&gt;' }[c])); }
function escAttr(s) { return String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c])); }
function stripTags(s) { return String(s).replace(/<[^>]+>/g, '').replace(/&amp;/g, '&').replace(/&lt;/g, '<').replace(/&gt;/g, '>'); }
