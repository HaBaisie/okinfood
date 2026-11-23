// ===== Okin Food store settings: edit these =====
const STORE = {
  name: 'Okin Food Supermarket',
  email: 'info@okinfood.com.ng',
  phone: '',            // digits only, e.g. 2348012345678 (enables WhatsApp link)
  address: '',          // shop address shown in footer
  deliveryFee: 1500,    // flat fee in Naira
  freeOver: 50000,      // free delivery above this subtotal
  bank: { name: '', account: '', holder: 'Okin Food' }, // shown for bank-transfer orders
};

const $ = s => document.querySelector(s);
const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
const naira = n => '₦' + Number(n).toLocaleString('en-NG');
const slugify = s => String(s).toLowerCase().replace(/&/g, 'and').replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');

let products = [];
let cart = (() => { try { return JSON.parse(localStorage.getItem('okin_cart')) || {}; } catch { return {}; } })();

const count = () => Object.values(cart).reduce((a, b) => a + b, 0);
function lines() {
  return Object.entries(cart).map(([id, qty]) => ({ p: products.find(x => x.id == id), qty })).filter(l => l.p).map(l => ({ ...l, line: l.p.price * l.qty }));
}
const subtotalOf = ls => ls.reduce((a, l) => a + l.line, 0);
const feeFor = (sub, method) => (method === 'pickup' || sub >= STORE.freeOver ? 0 : STORE.deliveryFee);

function saveCart() {
  try { localStorage.setItem('okin_cart', JSON.stringify(cart)); } catch {}
  $('#badge').textContent = count();
  syncCtls(); renderCartBar();
}
function toast(msg) {
  const t = $('#toast'); t.textContent = msg; t.hidden = false;
  clearTimeout(toast.t); toast.t = setTimeout(() => (t.hidden = true), 1800);
}
function categories() {
  const m = new Map();
  products.forEach(p => { if (!m.has(p.category)) m.set(p.category, { name: p.category, n: 0 }); m.get(p.category).n++; });
  return [...m.values()];
}

// ---------- product card ----------
// Photos: drop a file named images/products/<slug>.jpg (see images/README.md). Missing photos fall back to a plain tile.
function imgSrc(p) { return p.image || `images/products/${p.slug || slugify(p.name)}.jpg`; }
function ctl(p) {
  const q = cart[p.id] || 0;
  return q
    ? `<div class="step"><button data-dec="${p.id}" aria-label="Remove one">−</button><span>${q}</span><button data-inc="${p.id}" aria-label="Add one">+</button></div>`
    : `<button class="add" data-add="${p.id}">Add</button>`;
}
function syncCtls() {
  document.querySelectorAll('[data-ctl]').forEach(el => {
    const p = products.find(x => x.id == el.dataset.ctl); if (p) el.innerHTML = ctl(p);
  });
}
function card(p) {
  const off = p.old ? Math.round((1 - p.price / p.old) * 100) : 0;
  return `<article class="card">
    <div class="photo">${off ? `<span class="tag">${off}% off</span>` : ''}
      <img src="${esc(imgSrc(p))}" alt="${esc(p.name)}" loading="lazy" onerror="this.parentNode.classList.add('noimg');this.remove()">
      <span class="ph">Photo coming soon</span></div>
    <div class="info"><h3>${esc(p.name)}</h3><p class="unit">${esc(p.unit)}</p>
    <div class="buy"><div class="pr"><b>${naira(p.price)}</b>${p.old ? `<s>${naira(p.old)}</s>` : ''}</div>
    <div data-ctl="${p.id}">${ctl(p)}</div></div></div></article>`;
}
function row(title, cat, list) {
  if (!list.length) return '';
  const link = cat ? `#/shop?cat=${encodeURIComponent(cat)}` : '#/shop';
  return `<section class="rowsec"><div class="rowhead"><h2>${esc(title)}</h2><a href="${link}">View all</a></div>
    <div class="hscroll">${list.slice(0, 12).map(card).join('')}</div></section>`;
}

// ---------- views ----------
function viewHome() {
  const by = c => products.filter(p => p.category === c);
  const deals = products.filter(p => p.old);
  return `
  <section class="hero" style="background-image:url(images/hero.jpg)">
    <div class="herotxt"><p class="eyebrow">Naija foodstuff, delivered</p>
    <h1>Everything for your soup pot, in one order.</h1>
    <p>Yam, plantain, pepper, egusi, rice and the rest of your market list. Order online and we bring it to your door, or you collect in store.</p>
    <a class="btn" href="#/shop?cat=${encodeURIComponent('Naija Ingredients')}">Shop Naija ingredients</a></div>
  </section>
  <section class="usp">
    <div><b>Wide range</b><span>Foodstuff, drinks, household</span></div>
    <div><b>Delivery to your door</b><span>Free over ${naira(STORE.freeOver)}</span></div>
    <div><b>Pay your way</b><span>On delivery or bank transfer</span></div>
  </section>
  <section class="tiles">${categories().map(c => `<a class="tile" href="#/shop?cat=${encodeURIComponent(c.name)}" style="background-image:url(images/categories/${slugify(c.name)}.jpg)"><span>${esc(c.name)}</span></a>`).join('')}</section>
  ${row('Naija Ingredients', 'Naija Ingredients', by('Naija Ingredients'))}
  ${deals.length ? row('Deals', '', deals) : ''}
  ${row('Rice, Beans & Staples', 'Rice, Beans & Staples', by('Rice, Beans & Staples'))}
  ${row('Soup & Cooking Essentials', 'Soup & Cooking Essentials', by('Soup & Cooking Essentials'))}
  ${row('Naija Snacks', 'Naija Snacks', by('Naija Snacks'))}
  ${row('Drinks & Dairy', 'Drinks & Dairy', by('Drinks & Dairy'))}`;
}

function viewShop(params) {
  const cat = params.get('cat') || '', q = (params.get('q') || '').trim().toLowerCase(), sort = params.get('sort') || '';
  let list = products.filter(p => (!cat || p.category === cat) && (!q || (p.name + ' ' + p.category).toLowerCase().includes(q)));
  if (sort === 'low') list.sort((a, b) => a.price - b.price);
  if (sort === 'high') list.sort((a, b) => b.price - a.price);
  if (sort === 'az') list.sort((a, b) => a.name.localeCompare(b.name));
  const heading = cat || (q ? `Results for “${esc(params.get('q'))}”` : 'All products');
  const base = (k, v) => { const u = new URLSearchParams(params); u.set(k, v); return '#/shop?' + u.toString(); };
  return `<div class="crumbs"><a href="#/">Home</a> / <span>${esc(cat || 'Shop')}</span></div>
  <div class="shoplayout">
    <aside class="side"><h4>Categories</h4>
      <a href="#/shop" class="${!cat ? 'on' : ''}">All products <small>${products.length}</small></a>
      ${categories().map(c => `<a href="#/shop?cat=${encodeURIComponent(c.name)}" class="${c.name === cat ? 'on' : ''}">${esc(c.name)} <small>${c.n}</small></a>`).join('')}
    </aside>
    <section><div class="shophead"><div><h1>${heading}</h1><p class="muted">${list.length} item${list.length === 1 ? '' : 's'}</p></div>
      <label class="sort">Sort <select id="sortSel">
        <option value="">Featured</option><option value="low" ${sort === 'low' ? 'selected' : ''}>Price: low to high</option>
        <option value="high" ${sort === 'high' ? 'selected' : ''}>Price: high to low</option><option value="az" ${sort === 'az' ? 'selected' : ''}>Name: A to Z</option></select></label></div>
      ${list.length ? `<div class="grid">${list.map(card).join('')}</div>` : `<p>No products found. <a href="#/shop">Browse everything</a>.</p>`}
    </section></div>`;
}

function viewCart() {
  const ls = lines();
  if (!ls.length) return `<h1>Your basket</h1><p>Your basket is empty. <a class="btn" href="#/shop">Start shopping</a></p>`;
  return `<h1>Your basket</h1><div class="checkout"><div class="clist">
  ${ls.map(l => `<div class="citem"><div class="cthumb"><img src="${esc(imgSrc(l.p))}" alt="" onerror="this.remove()"></div>
    <div class="cname"><b>${esc(l.p.name)}</b><small class="muted">${esc(l.p.unit)} · ${naira(l.p.price)}</small>
    <button class="link" data-rm="${l.p.id}">Remove</button></div>
    <div data-ctl="${l.p.id}">${ctl(l.p)}</div><div class="cline">${naira(l.line)}</div></div>`).join('')}
  <button class="link" data-clear>Empty basket</button></div>
  <aside class="summary"><h3>Summary</h3><p class="li"><span>Subtotal</span><b>${naira(subtotalOf(ls))}</b></p>
    <small class="muted">Delivery is worked out at checkout.</small><br><br><a class="btn big" href="#/checkout">Checkout</a></aside></div>`;
}

function viewCheckout() {
  const ls = lines();
  if (!ls.length) { location.hash = '#/cart'; return ''; }
  const sub = subtotalOf(ls);
  return `<h1>Checkout</h1><div id="errs"></div>
  <div class="checkout">
  <form id="orderForm" class="form" novalidate>
    <input type="text" name="bot-field" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
    <label>Full name<input name="name" required autocomplete="name"></label>
    <label>Phone number<input name="phone" type="tel" required autocomplete="tel"></label>
    <label>Email (optional)<input name="email" type="email" autocomplete="email"></label>
    <fieldset><legend>Delivery or pickup</legend>
      <label class="opt"><input type="radio" name="method" value="delivery" checked> Home delivery</label>
      <label class="opt"><input type="radio" name="method" value="pickup"> Pick up in store (free)</label></fieldset>
    <label id="addrWrap">Delivery address<textarea name="address" rows="3" placeholder="House number, street, area, city"></textarea></label>
    <fieldset><legend>Payment</legend>
      <label class="opt"><input type="radio" name="payment" value="cod" checked> Pay on delivery / pickup</label>
      <label class="opt"><input type="radio" name="payment" value="transfer"> Bank transfer</label></fieldset>
    <label>Order note (optional)<textarea name="note" rows="2"></textarea></label>
    <button class="btn big" id="placeBtn">Place order</button>
  </form>
  <aside class="summary"><h3>Order summary</h3>
    ${ls.map(l => `<p class="li"><span>${l.qty} × ${esc(l.p.name)}</span><b>${naira(l.line)}</b></p>`).join('')}
    <hr><p class="li"><span>Subtotal</span><b>${naira(sub)}</b></p>
    <p class="li"><span>Delivery</span><b id="sFee"></b></p>
    <p class="li total"><span>Total</span><b id="sTotal"></b></p></aside></div>`;
}

function bindCheckout() {
  const form = $('#orderForm'); if (!form) return;
  const ls = lines(), sub = subtotalOf(ls);
  const refresh = () => {
    const fee = feeFor(sub, form.method.value);
    $('#sFee').textContent = fee ? naira(fee) : 'Free';
    $('#sTotal').textContent = naira(sub + fee);
    $('#addrWrap').style.display = form.method.value === 'pickup' ? 'none' : '';
  };
  form.addEventListener('change', refresh); refresh();

  form.addEventListener('submit', async e => {
    e.preventDefault();
    const f = Object.fromEntries(new FormData(form));
    const errs = [];
    if ((f.name || '').trim().length < 2) errs.push('Please enter your full name.');
    if (!/^[0-9+\s-]{7,20}$/.test((f.phone || '').trim())) errs.push('Please enter a valid phone number.');
    if (f.email && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(f.email)) errs.push('That email address looks invalid.');
    if (f.method === 'delivery' && (f.address || '').trim().length < 6) errs.push('Please enter your delivery address.');
    $('#errs').innerHTML = errs.map(m => `<div class="flash err">${esc(m)}</div>`).join('');
    if (errs.length) return window.scrollTo(0, 0);

    const fee = feeFor(sub, f.method), total = sub + fee, d = new Date();
    const ref = 'OKF-' + String(d.getFullYear()).slice(2) + String(d.getMonth() + 1).padStart(2, '0') + String(d.getDate()).padStart(2, '0') + '-' + Math.random().toString(36).slice(2, 7).toUpperCase();
    const items = ls.map(l => `${l.qty} x ${l.p.name} (${l.p.unit}) @ ${naira(l.p.price)} = ${naira(l.line)}`).join('\n');
    const payload = { 'form-name': 'order', 'bot-field': f['bot-field'] || '', ref, name: f.name, phone: f.phone, email: f.email || '',
      method: f.method === 'pickup' ? 'Store pickup' : 'Home delivery', address: f.address || '', payment: f.payment === 'transfer' ? 'Bank transfer' : 'Pay on delivery / pickup',
      note: f.note || '', items, subtotal: naira(sub), delivery_fee: naira(fee), total: naira(total) };

    const btn = $('#placeBtn'); btn.disabled = true; btn.textContent = 'Placing order…';
    try {
      const res = await fetch('/', { method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams(payload).toString() });
      if (!res.ok) throw new Error('HTTP ' + res.status);
      try { sessionStorage.setItem('okin_last', JSON.stringify({ ref, name: f.name, phone: f.phone, payment: f.payment, total })); } catch {}
      cart = {}; saveCart(); location.hash = '#/done';
    } catch {
      btn.disabled = false; btn.textContent = 'Place order';
      $('#errs').innerHTML = `<div class="flash err">Sorry, we couldn't send your order. Please try again${STORE.phone ? ' or message us on WhatsApp' : ''}.</div>`;
      window.scrollTo(0, 0);
    }
  });
}

function viewDone() {
  let o; try { o = JSON.parse(sessionStorage.getItem('okin_last')); } catch {}
  if (!o) return `<p><a href="#/">Back to home</a></p>`;
  const b = STORE.bank;
  return `<div class="done"><h1>Thank you, ${esc(o.name)}.</h1>
    <p>We've received your order <b>${esc(o.ref)}</b> and will call <b>${esc(o.phone)}</b> to confirm it.</p>
    ${o.payment === 'transfer' ? `<div class="flash">Please transfer <b>${naira(o.total)}</b>${b.account ? ` to <b>${esc(b.name)} · ${esc(b.account)}</b> (${esc(b.holder)})` : ''} and use <b>${esc(o.ref)}</b> as the reference.</div>` : ''}
    <a class="btn" href="#/shop">Continue shopping</a></div>`;
}

// ---------- chrome ----------
function renderCartBar() {
  const bar = $('#cartbar'), ls = lines(), onCheckout = /^#\/(checkout|cart|done)/.test(location.hash);
  if (!ls.length || onCheckout) { bar.hidden = true; return; }
  bar.hidden = false;
  bar.innerHTML = `<a href="#/cart"><span>${count()} item${count() === 1 ? '' : 's'}</span><b>View basket · ${naira(subtotalOf(ls))}</b></a>`;
}

function route() {
  const [path, qs] = location.hash.slice(1).split('?');
  const params = new URLSearchParams(qs || '');
  const views = { '/shop': () => viewShop(params), '/cart': viewCart, '/checkout': viewCheckout, '/done': viewDone };
  $('#app').innerHTML = (views[path] || viewHome)();
  document.title = (path === '/shop' && params.get('cat') ? params.get('cat') + ' · ' : '') + STORE.name;
  if (path === '/checkout') bindCheckout();
  const sel = $('#sortSel'); if (sel) sel.onchange = () => { params.set('sort', sel.value); location.hash = '#/shop?' + params.toString(); };
  document.querySelectorAll('#catnav a').forEach(a => a.classList.toggle('on', a.getAttribute('href') === location.hash));
  $('#catnav').classList.remove('open');
  renderCartBar();
  if (path) window.scrollTo(0, 0);
}

document.addEventListener('click', e => {
  const t = e.target.closest('button,a'); if (!t) return;
  const d = t.dataset;
  if (d.add) { cart[d.add] = 1; saveCart(); }
  else if (d.inc) { cart[d.inc] = Math.min(99, (cart[d.inc] || 0) + 1); saveCart(); }
  else if (d.dec) { cart[d.dec] = (cart[d.dec] || 0) - 1; if (cart[d.dec] <= 0) { delete cart[d.dec]; if (/^#\/cart/.test(location.hash)) { saveCart(); return route(); } } saveCart(); }
  else if (d.rm) { delete cart[d.rm]; saveCart(); route(); }
  else if (d.clear !== undefined) { cart = {}; saveCart(); route(); }
});
$('#searchForm').addEventListener('submit', e => { e.preventDefault(); location.hash = '#/shop?q=' + encodeURIComponent($('#q').value); });
$('#menuBtn').addEventListener('click', () => $('#catnav').classList.toggle('open'));

(async function init() {
  $('#yr').textContent = new Date().getFullYear();
  $('#announce').innerHTML = `Free delivery on orders over ${naira(STORE.freeOver)}` + (STORE.phone ? ` · <a href="https://wa.me/${esc(STORE.phone)}">Order on WhatsApp</a>` : '');
  $('#footinfo').innerHTML = `<div><b class="fh">${esc(STORE.name)}</b><p>Nigerian foodstuff, groceries and household items, delivered.</p></div>
    <div><b class="fh">Contact</b><p><a href="mailto:${esc(STORE.email)}">${esc(STORE.email)}</a></p>
    ${STORE.phone ? `<p><a href="https://wa.me/${esc(STORE.phone)}">WhatsApp</a></p>` : ''}${STORE.address ? `<p>${esc(STORE.address)}</p>` : ''}</div>
    <div><b class="fh">Shop</b><p><a href="#/shop">All products</a></p><p><a href="#/shop?cat=Naija%20Ingredients">Naija ingredients</a></p><p><a href="#/cart">Basket</a></p></div>`;
  try { products = (await (await fetch('products.json')).json()).filter(p => p.active !== false); }
  catch { $('#app').innerHTML = '<p>Sorry, products could not be loaded. Please refresh.</p>'; return; }
  $('#catnav').innerHTML = `<a href="#/shop">All</a>` + categories().map(c => `<a href="#/shop?cat=${encodeURIComponent(c.name)}">${esc(c.name)}</a>`).join('');
  $('#badge').textContent = count(); route(); addEventListener('hashchange', route);
})();
