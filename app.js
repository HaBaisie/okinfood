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

let products = [];
let cart = (() => { try { return JSON.parse(localStorage.getItem('okin_cart')) || {}; } catch { return {}; } })();

function saveCart() {
  try { localStorage.setItem('okin_cart', JSON.stringify(cart)); } catch {}
  $('#badge').textContent = Object.values(cart).reduce((a, b) => a + b, 0);
}
function toast(msg) {
  const t = $('#toast'); t.textContent = msg; t.hidden = false;
  clearTimeout(toast.t); toast.t = setTimeout(() => (t.hidden = true), 2200);
}
function lines() {
  return Object.entries(cart)
    .map(([id, qty]) => ({ p: products.find(x => x.id == id), qty }))
    .filter(l => l.p)
    .map(l => ({ ...l, line: l.p.price * l.qty }));
}
const subtotalOf = ls => ls.reduce((a, l) => a + l.line, 0);
const feeFor = (sub, method) => (method === 'pickup' || sub >= STORE.freeOver ? 0 : STORE.deliveryFee);

function categories() {
  const m = new Map();
  products.forEach(p => {
    if (!m.has(p.category)) m.set(p.category, { category: p.category, emoji: p.emoji, n: 0 });
    m.get(p.category).n++;
  });
  return [...m.values()];
}

function card(p) {
  const img = p.image ? `<img src="${esc(p.image)}" alt="${esc(p.name)}" loading="lazy">` : `<span class="emoji">${esc(p.emoji)}</span>`;
  return `<article class="card"><div class="thumb">${img}</div><h3>${esc(p.name)}</h3><p class="unit">${esc(p.unit)}</p>
    <div class="row"><strong class="price">${naira(p.price)}</strong><button class="btn small" data-add="${p.id}">Add</button></div></article>`;
}

// ---------- views ----------
function viewHome() {
  const feat = products.filter(p => p.featured).slice(0, 12);
  return `
  <section class="hero"><div>
    <h1>Fresh groceries,<br>delivered to your door.</h1>
    <p>Rice, oil, provisions, fresh produce and more. Order online and we'll bring it to you, or pick it up in store.</p>
    <a class="btn" href="#/shop">Start shopping</a> <a class="btn ghost" href="#featured" onclick="document.getElementById('featured').scrollIntoView();return false">Today's picks</a>
  </div><div class="hero-art" aria-hidden="true">🥬🍅🍚🥛🍞</div></section>
  <section class="perks">
    <div>🚚 <b>Home delivery</b><span>Free over ${naira(STORE.freeOver)}</span></div>
    <div>💵 <b>Pay on delivery</b><span>Or by bank transfer</span></div>
    <div>🥇 <b>Quality guaranteed</b><span>Fresh, genuine products</span></div>
  </section>
  <h2>Shop by category</h2>
  <div class="catgrid">${categories().map(c => `<a class="cat" href="#/shop?cat=${encodeURIComponent(c.category)}"><span class="emoji">${esc(c.emoji)}</span><b>${esc(c.category)}</b><small>${c.n} items</small></a>`).join('')}</div>
  <h2 id="featured">Featured products</h2>
  <div class="grid">${feat.map(card).join('')}</div>`;
}

function viewShop(params) {
  const cat = params.get('cat') || '', q = (params.get('q') || '').trim().toLowerCase();
  const list = products.filter(p => (!cat || p.category === cat) && (!q || (p.name + ' ' + p.category).toLowerCase().includes(q)));
  const heading = cat || (q ? `Results for “${esc(params.get('q'))}”` : 'All products');
  return `<h1>${esc(heading)}</h1><p class="muted">${list.length} item${list.length === 1 ? '' : 's'}</p>` +
    (list.length ? `<div class="grid">${list.map(card).join('')}</div>` : `<p>No products found. <a href="#/shop">Browse everything</a>.</p>`);
}

function viewCart() {
  const ls = lines();
  if (!ls.length) return `<h1>Your cart</h1><p>Your cart is empty. <a class="btn" href="#/shop">Start shopping</a></p>`;
  return `<h1>Your cart</h1>
  <div class="tablewrap"><table class="tbl"><thead><tr><th>Product</th><th>Price</th><th>Qty</th><th>Total</th><th></th></tr></thead><tbody>
  ${ls.map(l => `<tr><td><span class="emoji sm">${esc(l.p.emoji)}</span> <b>${esc(l.p.name)}</b><br><small class="muted">${esc(l.p.unit)}</small></td>
    <td>${naira(l.p.price)}</td>
    <td><input class="qty" type="number" min="0" max="99" value="${l.qty}" data-qty="${l.p.id}"></td>
    <td>${naira(l.line)}</td><td><button class="link" data-rm="${l.p.id}">Remove</button></td></tr>`).join('')}
  </tbody></table></div>
  <div class="cartfoot"><button class="link" data-clear>Empty cart</button>
    <div class="sum"><p>Subtotal <b>${naira(subtotalOf(ls))}</b></p><small class="muted">Delivery calculated at checkout</small><br><br>
    <a class="btn" href="#/checkout">Checkout →</a></div></div>`;
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
    <label>Email (for follow-up, optional)<input name="email" type="email" autocomplete="email"></label>
    <fieldset><legend>How do you want your order?</legend>
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
  const method = () => form.method.value;
  const refresh = () => {
    const fee = feeFor(sub, method());
    $('#sFee').textContent = fee ? naira(fee) : 'Free';
    $('#sTotal').textContent = naira(sub + fee);
    $('#addrWrap').style.display = method() === 'pickup' ? 'none' : '';
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

    const fee = feeFor(sub, f.method), total = sub + fee;
    const d = new Date();
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
      cart = {}; saveCart();
      location.hash = '#/done';
    } catch (err) {
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
  return `<div class="done"><div class="tick">✅</div><h1>Thank you, ${esc(o.name)}!</h1>
    <p>Your order <b>${esc(o.ref)}</b> has been received. We'll contact you on <b>${esc(o.phone)}</b> to confirm.</p>
    ${o.payment === 'transfer' ? `<div class="flash">Please transfer <b>${naira(o.total)}</b>${b.account ? ` to <b>${esc(b.name)} · ${esc(b.account)}</b> (${esc(b.holder)})` : ''} using <b>${esc(o.ref)}</b> as the reference.</div>` : ''}
    <a class="btn" href="#/shop">Continue shopping</a></div>`;
}

// ---------- router ----------
function route() {
  const [path, qs] = location.hash.slice(1).split('?');
  const params = new URLSearchParams(qs || '');
  const views = { '/shop': () => viewShop(params), '/cart': viewCart, '/checkout': viewCheckout, '/done': viewDone };
  const html = (views[path] || viewHome)();
  $('#app').innerHTML = html;
  document.title = (path === '/shop' && params.get('cat') ? params.get('cat') + ' · ' : '') + STORE.name;
  if (path === '/checkout') bindCheckout();
  if (path !== '/' && path) window.scrollTo(0, 0);
}

document.addEventListener('click', e => {
  const add = e.target.closest('[data-add]');
  if (add) { const id = add.dataset.add; cart[id] = Math.min(99, (cart[id] || 0) + 1); saveCart(); toast('Added to your cart.'); }
  const rm = e.target.closest('[data-rm]');
  if (rm) { delete cart[rm.dataset.rm]; saveCart(); route(); }
  if (e.target.closest('[data-clear]')) { cart = {}; saveCart(); route(); }
});
document.addEventListener('change', e => {
  const q = e.target.closest('[data-qty]');
  if (q) { const n = Math.max(0, Math.min(99, parseInt(q.value) || 0)); n ? (cart[q.dataset.qty] = n) : delete cart[q.dataset.qty]; saveCart(); route(); }
});
$('#searchForm').addEventListener('submit', e => {
  e.preventDefault();
  location.hash = '#/shop?q=' + encodeURIComponent($('#q').value);
});

(async function init() {
  $('#yr').textContent = new Date().getFullYear();
  $('#footinfo').innerHTML = `<div><strong>${esc(STORE.name)}</strong><p>Fresh groceries delivered to your door</p></div>
    <div><p>📧 <a href="mailto:${esc(STORE.email)}">${esc(STORE.email)}</a></p>
    ${STORE.phone ? `<p>📞 <a href="https://wa.me/${esc(STORE.phone)}">WhatsApp us</a></p>` : ''}
    ${STORE.address ? `<p>📍 ${esc(STORE.address)}</p>` : ''}</div>`;
  try {
    products = (await (await fetch('products.json')).json()).filter(p => p.active !== false);
  } catch { $('#app').innerHTML = '<p>Sorry, products could not be loaded. Please refresh.</p>'; return; }
  $('#catnav').innerHTML = `<a href="#/shop">All</a>` + categories().map(c => `<a href="#/shop?cat=${encodeURIComponent(c.category)}">${esc(c.category)}</a>`).join('');
  saveCart(); route(); addEventListener('hashchange', route);
})();
