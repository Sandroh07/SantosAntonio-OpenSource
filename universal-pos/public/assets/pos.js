(() => {
  'use strict';
  const catalog = JSON.parse(document.getElementById('catalogData').textContent);
  const byId = new Map(catalog.map(item => [Number(item.id), item]));
  const cart = new Map();
  const $ = id => document.getElementById(id);
  const rate = Number($('taxRate').value);
  const currency = $('currency').value;
  const stockEnabled = true; // Backend is always authoritative.
  const money = cents => `${currency} ${(cents / 100).toLocaleString('en-US', {minimumFractionDigits:2, maximumFractionDigits:2})}`;
  const amount = value => Math.round((Number(value) || 0) * 100);
  const safe = s => String(s).replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
  function renderCatalog() {
    const query = $('search').value.trim().toLowerCase();
    const matches = catalog.filter(p => !query || [p.name,p.sku,p.barcode].some(v => String(v||'').toLowerCase().includes(query))).slice(0,42);
    $('catalog').innerHTML = matches.length ? matches.map(p => `<div class="col-6 col-xl-4"><button type="button" class="card product-card w-100 p-3 text-start bg-white" data-add="${Number(p.id)}"><small class="text-secondary">${safe(p.sku || p.item_type)}</small><b class="my-1">${safe(p.name)}</b><span class="fw-bold text-primary">${money(Number(p.price_cents))}</span><small class="text-secondary">${p.item_type === 'service' ? 'Service' : `Stock: ${Number(p.stock_qty)}`}</small></button></div>`).join('') : '<p class="text-secondary">No items matched.</p>';
  }
  function renderCart() {
    let subtotal = 0;
    $('cartRows').innerHTML = cart.size ? [...cart].map(([id,qty]) => {
      const p = byId.get(id); const line = Number(p.price_cents)*qty; subtotal += line;
      return `<div class="d-flex justify-content-between align-items-center gap-2 py-2 border-bottom"><div class="flex-grow-1"><b>${safe(p.name)}</b><div class="text-secondary">${money(line)}</div></div><button class="btn btn-sm btn-outline-secondary" type="button" data-dec="${id}">−</button><span>${qty}</span><button class="btn btn-sm btn-outline-secondary" type="button" data-add="${id}">+</button></div>`;
    }).join('') : '<div class="text-secondary py-3">Add items to begin.</div>';
    let discount = Math.max(0,amount($('discount').value));
    const tax = Math.round(Math.max(0,subtotal-discount)*rate/100);
    const total = Math.max(0,subtotal-discount)+tax;
    const method = $('paymentMethod').value;
    if (method !== 'cash') { $('paid').value = (total/100).toFixed(2); $('paid').readOnly = true; }
    else $('paid').readOnly = false;
    $('subtotal').textContent=money(subtotal);$('tax').textContent=money(tax);$('grandTotal').textContent=money(total);
    $('change').textContent=money(Math.max(0,amount($('paid').value)-total));
    $('complete').disabled = cart.size === 0 || discount > subtotal || (method === 'cash' && amount($('paid').value) < total);
    $('cartJson').value = JSON.stringify([...cart].map(([id,qty]) => ({id,qty})));
  }
  document.addEventListener('click', event => {
    const plus = event.target.closest('[data-add]'); const minus = event.target.closest('[data-dec]');
    if (plus) {const id=Number(plus.dataset.add);cart.set(id,Math.min(9999,(cart.get(id)||0)+1));renderCart();}
    if (minus) {const id=Number(minus.dataset.dec);let q=(cart.get(id)||0)-1;if(q<=0)cart.delete(id);else cart.set(id,q);renderCart();}
  });
  $('search').addEventListener('input',renderCatalog);
  $('search').addEventListener('keydown',e => { if (e.key==='Enter') { e.preventDefault(); const first = catalog.find(p => String(p.barcode||'') === $('search').value.trim()); if (first) {cart.set(Number(first.id),(cart.get(Number(first.id))||0)+1);$('search').value='';renderCatalog();renderCart();}}});
  for(const id of ['discount','paymentMethod','paid']) $(id).addEventListener('input',renderCart);
  $('checkoutForm').addEventListener('submit',event => { if (!cart.size) event.preventDefault(); else $('complete').disabled=true; });
  renderCatalog();renderCart();
})();
