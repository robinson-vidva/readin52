// Transactional email via Resend (https://resend.com).
// Configure with RESEND_API_KEY and EMAIL_FROM (e.g. "ReadIn52 <noreply@askdevotions.com>").
const API = 'https://api.resend.com/emails';

export function isConfigured() {
  return !!process.env.RESEND_API_KEY && !!process.env.EMAIL_FROM;
}

export async function send({ to, subject, html, text, replyTo }) {
  if (!isConfigured()) return { success: false, error: 'Email is not configured' };
  try {
    const res = await fetch(API, {
      method: 'POST',
      headers: {
        Authorization: `Bearer ${process.env.RESEND_API_KEY}`,
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        from: process.env.EMAIL_FROM,
        to: Array.isArray(to) ? to : [to],
        subject,
        html,
        text: text || html.replace(/<[^>]+>/g, ''),
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

// ---- Branded template ----
function layout(appName, heading, bodyHtml, button) {
  const btn = button
    ? `<a href="${button.href}" style="display:inline-block;background:#b0603a;color:#fff;text-decoration:none;padding:12px 24px;border-radius:10px;font-weight:600;margin:8px 0">${button.label}</a>`
    : '';
  return `<!DOCTYPE html><html><body style="margin:0;background:#f7f3ec;font-family:-apple-system,Segoe UI,Roboto,Helvetica,Arial,sans-serif;color:#2a231d">
    <div style="max-width:520px;margin:0 auto;padding:32px 20px">
      <div style="font-family:Georgia,serif;font-size:22px;font-weight:700;color:#7a3e22;margin-bottom:24px">📖 ${appName}</div>
      <div style="background:#fff;border:1px solid #e7ddcf;border-radius:16px;padding:28px">
        <h1 style="font-family:Georgia,serif;font-size:22px;margin:0 0 12px">${heading}</h1>
        ${bodyHtml}
        ${btn}
      </div>
      <p style="color:#7c7266;font-size:12px;margin-top:20px;text-align:center">${appName} · Journey Through Scripture in 52 Weeks</p>
    </div></body></html>`;
}

export function sendPasswordReset(to, name, link, appName = 'ReadIn52') {
  return send({
    to,
    subject: `Reset your ${appName} password`,
    html: layout(appName, 'Reset your password',
      `<p>Hi ${escapeHtml(name || 'there')},</p>
       <p>We received a request to reset your password. Click the button below to choose a new one. This link expires in 1 hour.</p>`,
      { href: link, label: 'Reset password' }) +
      `<p style="color:#7c7266;font-size:12px">If you didn't request this, you can safely ignore this email.</p>`,
  });
}

export function sendEmailVerification(to, name, link, appName = 'ReadIn52') {
  return send({
    to,
    subject: `Confirm your new email for ${appName}`,
    html: layout(appName, 'Confirm your email',
      `<p>Hi ${escapeHtml(name || 'there')},</p>
       <p>Please confirm this email address to finish updating your ${appName} account. This link expires in 1 hour.</p>`,
      { href: link, label: 'Confirm email' }),
  });
}

export function sendWelcome(to, name, link, appName = 'ReadIn52') {
  return send({
    to,
    subject: `Welcome to ${appName}`,
    html: layout(appName, `Welcome, ${escapeHtml(name || 'friend')}!`,
      `<p>Your account is ready. Read the whole Bible in 52 weeks across four tracks, track your progress, and take notes as you go.</p>`,
      { href: link, label: 'Start reading' }),
  });
}

function escapeHtml(s) {
  return String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
}
