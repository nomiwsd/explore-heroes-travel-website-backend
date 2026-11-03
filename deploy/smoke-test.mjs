#!/usr/bin/env node
/**
 * Explore Heroes smoke test — checks the client bug-sheet fixes against a
 * running site (Docker stack, staging or production). Read-only: it only
 * sends GET requests.
 *
 *   node deploy/smoke-test.mjs                      # http://localhost:8080
 *   node deploy/smoke-test.mjs https://exploreheroes.com
 *
 * Each check prints PASS / FAIL / SKIP with the bug-sheet ID. SKIP means the
 * data needed for the check isn't there (e.g. no tour with cities yet).
 * Exit code is 1 when any check fails.
 */

const BASE = (process.argv[2] || process.env.SITE_URL || 'http://localhost:8080').replace(/\/+$/, '');
const TIMEOUT = 30000;
const results = [];

async function get(path, { redirect = 'follow' } = {}) {
  const res = await fetch(BASE + path, {
    redirect,
    headers: { Accept: path.startsWith('/api') ? 'application/json' : 'text/html' },
    signal: AbortSignal.timeout(TIMEOUT),
  });
  const text = await res.text();
  let json;
  try { json = JSON.parse(text); } catch { /* html */ }
  return { status: res.status, headers: res.headers, text, json };
}

const listOf = (j) => (Array.isArray(j) ? j : Array.isArray(j?.data) ? j.data : []);
const isIso = (v) => typeof v === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(v);
const hasArabic = (s) => /[؀-ۿ]/.test(s);

async function check(id, name, fn) {
  try {
    const out = await fn();
    if (out === 'skip' || (out && out.skip)) {
      results.push(['SKIP', id, name, out.skip || 'no data']);
    } else {
      results.push(['PASS', id, name, typeof out === 'string' ? out : '']);
    }
  } catch (e) {
    results.push(['FAIL', id, name, e.message]);
  }
}
const assert = (cond, msg) => { if (!cond) throw new Error(msg); };
const skip = (why) => ({ skip: why });

// ---------------------------------------------------------------- backend ---

await check('-', 'API health', async () => {
  const r = await get('/api/health');
  assert(r.status === 200 && r.json?.status === 'ok', `status ${r.status}`);
});

await check('TL-01', 'Arabic translations served (admin build first)', async () => {
  const r = await get('/api/translations/ar');
  assert(r.status === 200, `status ${r.status}`);
  assert(r.json && typeof r.json === 'object' && !Array.isArray(r.json), 'not a JSON object');
  const n = Object.keys(r.json).length;
  assert(/max-age=\d+/.test(r.headers.get('cache-control') || ''), 'no Cache-Control max-age');
  return `${n} strings`;
});

await check('TL-01', 'Invalid locale rejected', async () => {
  // Traversal attempts may be stopped by nginx (403) before PHP; both are fine.
  const r = await get('/api/translations/..%2f..%2f.env');
  assert(r.status >= 400 && r.status < 500, `traversal status ${r.status}`);
  const v = await get('/api/translations/a.b');
  assert(v.status === 400, `bad locale status ${v.status}`);
});

await check('SEC', 'Public settings do not leak secrets', async () => {
  const bad = /(password|secret|token|gmap_key|^email_(host|username|port|driver))/i;
  for (const p of ['/api/settings/general', '/api/settings/email', '/api/module/core/seo/global']) {
    const r = await get(p);
    if (r.status !== 200 || !r.json) continue;
    const leaked = Object.keys(r.json).filter((k) => bad.test(k));
    assert(leaked.length === 0, `${p} leaks ${leaked.join(', ')}`);
  }
});

await check('ST-09', 'Vendor registration and user plans off', async () => {
  const r = await get('/api/settings/general');
  const v = r.json || {};
  if (!('vendor_enable' in v) && !('user_plans_enable' in v)) return skip('settings not present');
  assert(String(v.vendor_enable ?? '0') === '0', `vendor_enable=${v.vendor_enable}`);
  assert(String(v.user_plans_enable ?? '0') === '0', `user_plans_enable=${v.user_plans_enable}`);
});

await check('RD-01', 'Public redirects endpoint', async () => {
  const r = await get('/api/seo/redirects');
  assert(r.status === 200 && Array.isArray(r.json), `status ${r.status}`);
  for (const rule of r.json) {
    assert(rule.from && rule.to && [301, 302, 307, 308].includes(Number(rule.status)), `bad rule ${JSON.stringify(rule)}`);
  }
  return `${r.json.length} rule(s)`;
});

const tours = listOf((await get('/api/tours?per_page=100&lang=ar')).json);

await check('SM-05', 'Tours list has updated_at', async () => {
  if (!tours.length) return skip('no tours');
  assert(tours.every((t) => isIso(t.updated_at)), 'missing/invalid updated_at');
});

await check('SM-05', 'Destinations list has updated_at', async () => {
  const r = await get('/api/destinations');
  const list = listOf(r.json);
  if (r.json?.error) throw new Error(r.json.error);
  if (!list.length) return skip('no destinations');
  assert(list.every((d) => isIso(d.updated_at)), 'missing/invalid updated_at');
});

// TR-01 / DS-08 need a tour whose cities match a destination.
let cityTour = null;
let cityTourDetail = null;
for (const t of tours) {
  const d = (await get(`/api/tours/${encodeURIComponent(t.slug)}?lang=ar`)).json?.data;
  if (!d) continue;
  await check('SEC', `Tour "${t.slug}" hides expert email`, async () => {
    assert(!d.tour_expert || !('email' in d.tour_expert), 'tour_expert.email exposed');
  });
  if (Array.isArray(d.cities) && d.cities.some((c) => c.id)) {
    cityTour = t;
    cityTourDetail = d;
    break;
  }
}

await check('TR-01', 'Tour detail returns resolved cities', async () => {
  if (!tours.length) return skip('no tours');
  const d = cityTourDetail || (await get(`/api/tours/${encodeURIComponent(tours[0].slug)}?lang=ar`)).json?.data;
  assert(Array.isArray(d?.cities), '`cities` missing');
  if (!cityTourDetail) return skip('no tour has a city that matches a destination');
  const c = cityTourDetail.cities.find((x) => x.id);
  assert(c.name && c.slug && 'original' in c, 'matched city incomplete');
  return `${c.original} → ${c.name}${hasArabic(c.name) ? '' : ' (no Arabic name for this destination yet)'}`;
});

await check('DS-08', 'Destination filter includes tours by cities covered', async () => {
  if (!cityTourDetail) return skip('no tour with a matched city');
  const c = cityTourDetail.cities.find((x) => x.id);
  const r = await get(`/api/tours?destination_id=${c.id}&per_page=100`);
  assert(listOf(r.json).some((t) => t.id === cityTour.id), `tour ${cityTour.id} not listed for destination ${c.id}`);
});

await check('DS-08', 'Destination page lists tours by cities covered', async () => {
  if (!cityTourDetail) return skip('no tour with a matched city');
  const c = cityTourDetail.cities.find((x) => x.id);
  const r = await get(`/api/destinations/${encodeURIComponent(c.slug)}`);
  assert(r.status === 200, `status ${r.status} ${r.json?.error || ''}`);
  assert((r.json?.data?.tours || []).some((t) => t.id === cityTour.id), 'tour missing from destination');
});

await check('RV-01', 'Reviews: no emails, ISO dates', async () => {
  const r = await get('/api/reviews?lang=ar&per_page=20');
  const list = listOf(r.json);
  if (!list.length) return skip('no reviews');
  assert(list.every((x) => !('author_email' in x)), 'author_email exposed');
  assert(list.every((x) => 'date_iso' in x), 'date_iso missing');
});

const posts = listOf((await get('/api/news?per_page=20&lang=ar')).json);

await check('BL-01', 'Blog authors never show an email', async () => {
  if (!posts.length) return skip('no posts');
  for (const p of posts.slice(0, 5)) {
    const d = (await get(`/api/news/${encodeURIComponent(p.slug)}?lang=ar`)).json?.data;
    const name = d?.author?.display_name || d?.author?.name || '';
    assert(!name.includes('@'), `post ${p.slug} author "${name}"`);
    assert(!d?.author || !('email' in d.author), `post ${p.slug} exposes author.email`);
  }
});

await check('BL-02', 'Blog posts have ISO published_at', async () => {
  if (!posts.length) return skip('no posts');
  assert(posts.every((p) => isIso(p.published_at)), 'published_at missing');
});

// --------------------------------------------------------------- frontend ---

const pages = {};
for (const p of ['/', '/ar', '/ar/tours', '/ar/blogs', '/ar/success-stories', '/ar/destinations']) {
  pages[p] = await get(p).catch((e) => ({ status: 0, text: '', error: e.message }));
}

await check('-', 'Public pages respond 200', async () => {
  const bad = Object.entries(pages).filter(([, r]) => r.status !== 200).map(([p, r]) => `${p}=${r.status}`);
  assert(!bad.length, bad.join(', '));
});

await check('TL-03', '/ar is RTL Arabic in the server HTML', async () => {
  const h = pages['/ar'].text;
  assert(/<html[^>]*lang="ar"/.test(h) && /dir="rtl"/.test(h), 'html lang/dir not ar/rtl');
  assert(hasArabic(h), 'no Arabic text in server HTML');
});

await check('TR-05', '/ar/tours labels are Arabic before JavaScript runs', async () => {
  const h = pages['/ar/tours'].text;
  for (const s of ['الرئيسية', 'تصفية النتائج']) assert(h.includes(s), `missing "${s}"`);
});

await check('PG-05', 'Home headings use single keys', async () => {
  const h = pages['/ar'].text;
  assert(!/>\s*3\s*<span[^>]*>\s*(Vacation|عطلة)/.test(h), 'split "3 Vacation Options" heading');
});

await check('PG-03', 'No placeholder testimonials', async () => {
  for (const [p, r] of Object.entries(pages)) {
    assert(!/Ozonka|Amazing Bali Experience/.test(r.text), `placeholder review on ${p}`);
  }
});

await check('PG-01', 'No hard-coded CEO text', async () => {
  const r = await get('/about-us');
  if (r.status !== 200) return skip(`/about-us ${r.status}`);
  assert(!/Ravi believes/.test(r.text), '"Ravi believes" text present');
});

await check('PG-07', 'Contact page has no other company\'s details', async () => {
  const r = await get('/contact-us');
  if (r.status !== 200) return skip(`/contact-us ${r.status}`);
  assert(!/olankatravels/i.test(r.text), 'olankatravels.com present');
});

await check('PF-01', 'Pages do not load every country flag', async () => {
  const n = (pages['/ar/tours'].text.match(/flagcdn\.com/g) || []).length;
  assert(n < 10, `${n} flagcdn images`);
  return `${n} flagcdn reference(s)`;
});

await check('RD-01', 'Admin redirects applied by the site', async () => {
  const rules = (await get('/api/seo/redirects')).json || [];
  // Query-specific rules are intentionally not applied by the proxy.
  const rule = rules.find((x) => typeof x.from === 'string' && x.from.startsWith('/') && !x.from.includes('?'));
  if (!rule) return skip('no redirect rules configured');
  const r = await get(`${rule.from}?utm_source=smoke`, { redirect: 'manual' });
  assert([301, 302, 307, 308].includes(r.status), `${rule.from} → ${r.status}`);
  const loc = r.headers.get('location') || '';
  assert(loc.includes(rule.to.replace(/^https?:\/\/[^/]+/, '')), `wrong Location ${loc}`);
  if (!/^https?:/.test(rule.to)) assert(loc.includes('utm_source=smoke'), 'query string dropped');
  return `${rule.from} → ${loc}`;
});

await check('SEO', 'Sitemap served without looping', async () => {
  const api = await get('/api/seo/sitemap.xml');
  if (api.status === 404) return skip('sitemap not generated yet (Admin > SEO > Sitemap > Generate)');
  assert(api.status === 200, `api status ${api.status}`);
  const r = await get('/sitemap.xml');
  assert(r.status === 200 && r.text.includes('<urlset'), `/sitemap.xml status ${r.status}`);
});

await check('SE-02', 'Media URLs use the site domain', async () => {
  const list = listOf((await get('/api/destinations')).json);
  const urls = list.map((d) => d.image_url).filter(Boolean);
  if (!urls.length) return skip('no destination images');
  const host = new URL(BASE).host;
  const foreign = urls.filter((u) => /^https?:/.test(u) && new URL(u).host !== host);
  assert(!foreign.length, `foreign host: ${foreign[0]}`);
});

// ---------------------------------------------------------------- report ---

const pad = (s, n) => String(s).padEnd(n);
console.log(`\nSmoke test against ${BASE}\n`);
for (const [st, id, name, note] of results) {
  console.log(`${pad(st, 5)} ${pad(id, 6)} ${pad(name, 55)} ${note}`);
}
const count = (s) => results.filter((r) => r[0] === s).length;
console.log(`\n${count('PASS')} passed, ${count('FAIL')} failed, ${count('SKIP')} skipped`);
process.exit(count('FAIL') ? 1 : 0);
