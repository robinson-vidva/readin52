// Optional AI study companion. Dormant unless an API key is set.
// Provider-agnostic; defaults to Google Gemini (largest free context window).
// Configure with:
//   AI_API_KEY   (or GEMINI_API_KEY)   — required to enable
//   AI_PROVIDER  gemini | groq | openrouter | openai   (default: gemini)
//   AI_MODEL     model name (provider-specific; sensible default per provider)
//   AI_BASE_URL  override the OpenAI-compatible base URL (optional)
const PROVIDER = (process.env.AI_PROVIDER || 'gemini').toLowerCase();
const KEY = process.env.AI_API_KEY || process.env.GEMINI_API_KEY || '';

export function isEnabled() { return !!KEY; }
export function provider() { return PROVIDER; }

function buildPrompt(ref, text, question) {
  const persona = 'You are a warm, knowledgeable Bible study companion inside a Scripture-reading app. '
    + 'Explain passages clearly for a general audience: give the historical and cultural context, the flow of '
    + 'the passage, and its major themes. Be concise (about 120–180 words), pastoral, and non-denominational — '
    + 'avoid sectarian claims and never invent quotations. Plain text only, no markdown headings.';
  const passage = `Passage: ${ref}\nText: "${text}"`;
  const ask = question ? `The reader asks: ${question}` : 'Explain what this passage means and its context.';
  return `${persona}\n\n${passage}\n\n${ask}`;
}

export async function explain({ ref, text, question }) {
  if (!isEnabled()) return { error: 'The study companion is not configured.' };
  const prompt = buildPrompt(ref, text, (question || '').trim());
  try {
    return PROVIDER === 'gemini' ? await callGemini(prompt) : await callOpenAICompat(prompt);
  } catch {
    return { error: 'The study companion is unavailable right now.' };
  }
}

async function callGemini(prompt) {
  const model = process.env.AI_MODEL || 'gemini-2.0-flash';
  const url = `https://generativelanguage.googleapis.com/v1beta/models/${encodeURIComponent(model)}:generateContent?key=${encodeURIComponent(KEY)}`;
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      contents: [{ parts: [{ text: prompt }] }],
      generationConfig: { temperature: 0.6, maxOutputTokens: 500 },
    }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) return { error: (data && data.error && data.error.message) || 'AI request failed.' };
  const answer = (data.candidates?.[0]?.content?.parts || []).map((p) => p.text || '').join('').trim();
  return answer ? { answer } : { error: 'No response from the study companion.' };
}

async function callOpenAICompat(prompt) {
  const base = process.env.AI_BASE_URL
    || (PROVIDER === 'groq' ? 'https://api.groq.com/openai/v1'
      : PROVIDER === 'openrouter' ? 'https://openrouter.ai/api/v1'
      : 'https://api.openai.com/v1');
  const model = process.env.AI_MODEL
    || (PROVIDER === 'groq' ? 'llama-3.3-70b-versatile'
      : PROVIDER === 'openrouter' ? 'meta-llama/llama-3.3-70b-instruct'
      : 'gpt-4o-mini');
  const res = await fetch(base + '/chat/completions', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', Authorization: `Bearer ${KEY}` },
    body: JSON.stringify({ model, messages: [{ role: 'user', content: prompt }], temperature: 0.6, max_tokens: 500 }),
  });
  const data = await res.json().catch(() => ({}));
  if (!res.ok) return { error: (data && data.error && data.error.message) || 'AI request failed.' };
  const answer = data.choices?.[0]?.message?.content?.trim();
  return answer ? { answer } : { error: 'No response from the study companion.' };
}
