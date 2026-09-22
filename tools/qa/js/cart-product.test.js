// Мгновенный отклик корзины на странице товара (степпер «В корзине»):
// настоящие cart.js + cart-drawer.js в jsdom против поддельного Store API со
// случайной задержкой 300–900 мс (ответы приходят не по порядку). Проверяет
// мгновенное обновление, накопление нажатий, один запрос на серию, строго
// последовательные запросы, отбрасывание устаревших ответов и отправку
// изменений до оформления заказа.
// Запуск — tools/qa/js/run.sh (аргументы: страница, каталог JS, ответ корзины).
const fs = require('fs'); const { JSDOM } = require('jsdom');
const [htmlFile, jsDir, seedFile] = process.argv.slice(2);
const html = fs.readFileSync(htmlFile, 'utf8').replace(/<script[\s\S]*?<\/script>/g, '');
const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/', pretendToBeVisual: true });
const w = dom.window, doc = w.document;
const seed = JSON.parse(fs.readFileSync(seedFile, 'utf8'));
{ const b = doc.querySelector('form.cart button[name="add-to-cart"], form.cart input[name="product_id"]'); if (b) { seed.items[0].id = +b.value; } }
// ---- fake server ----
const unit = +seed.items[0].prices.price, ship = +seed.totals.total_shipping;
let server = { qty: 0 }; const log = []; let inflight = 0, maxInflight = 0;
const cartBody = () => { const c = JSON.parse(JSON.stringify(seed)); if (!server.qty) { c.items = []; c.items_count = 0; c.totals.total_items = '0'; c.totals.total_price = '0'; } else { const it = c.items[0]; it.quantity = server.qty; it.totals.line_total = it.totals.line_subtotal = String(unit * server.qty); c.items_count = server.qty; c.totals.total_items = String(unit * server.qty); c.totals.total_price = String(unit * server.qty + ship); } return c; };
w.fetch = (url, opts) => {
  const path = url.replace(/^.*store\/v1\//, ''); const body = opts.body ? JSON.parse(opts.body) : {};
  inflight++; maxInflight = Math.max(maxInflight, inflight);
  const delay = 300 + Math.random() * 600; const t0 = Date.now();
  return new Promise(res => setTimeout(() => {
    inflight--;
    if (path === 'cart/update-item' && server.limit && body.quantity > server.limit) { inflight; log.push(`POST ${path} q=${body.quantity} → ОШИБКА (лимит ${server.limit})`); return res({ ok: false, status: 400, headers: { get: () => null }, json: () => Promise.resolve({ message: 'limit' }) }); }
    if (path === 'cart/update-item') server.qty = body.quantity;
    if (path === 'cart/remove-item') server.qty = 0;
    if (path === 'cart/add-item') server.qty += body.quantity;
    if (path === 'cart/items' && opts.method === 'DELETE') server.qty = 0;
    log.push(`${opts.method || 'GET'} ${path}${body.quantity != null ? ' q=' + body.quantity : ''} → сервер q=${server.qty}`);
    const out = path === 'cart/items' ? [] : path === 'checkout' ? { order_id: 999, order_number: '999', payment_result: {} } : cartBody();
    res({ ok: true, status: 200, headers: { get: k => k === 'Nonce' ? 'n2' : null }, json: () => Promise.resolve(out) });
  }, delay));
};
w.whoopstoreCartData = { root: '/wp-json/wc/store/v1/', nonce: 'n1' };
w.CustomEvent = w.CustomEvent || class {};
for (const f of ['cart.js', 'cart-drawer.js']) w.eval(fs.readFileSync(jsDir + '/' + f, 'utf8'));
const sleep = ms => new Promise(r => setTimeout(r, ms));
const badge = () => doc.querySelector('.site-header__cart-badge').textContent;
const qtyShown = () => { const v = doc.querySelector('.cart-drawer__items .cart-stepper__value'); return v ? v.textContent : '(позиции нет)'; };
const total = () => doc.querySelector('.cart-total__value').textContent.replace(/\s+/g, ' ').trim();
const empty = () => !doc.querySelector('.cart-drawer__empty').hidden;
const click = sel => doc.querySelector('.cart-drawer__items ' + sel).click();
const ok = (c, m) => { console.log((c ? '  ✓ ' : '  ✗ ') + m); if (!c) process.exitCode = 1; };
(async () => {
  server.qty = 1;
  await new Promise(r => doc.readyState === 'complete' ? r() : w.addEventListener('load', r));
  await sleep(1500); console.log('загрузка страницы: запросов', log.length, '—', log.join('; ')); log.length = 0; maxInflight = 0;
  console.log('Старт: в корзине 1 шт., на экране', qtyShown(), '| счётчик', badge(), '|', total());

  const form = doc.querySelector('form.cart');
  if (form) {
    console.log('\n7) Страница товара: «В корзину», затем три быстрых «+» на странице');
    server.qty = 0; await w.whoopstoreCart.refresh(); log.length = 0;
    const btn = form.querySelector('.single_add_to_cart_button'), stepper = form.querySelector('.cart-stepper'), val = () => stepper.querySelector('.cart-stepper__value').textContent;
    form.dispatchEvent(new w.Event('submit', { cancelable: true, bubbles: true }));
    console.log(`   сразу после «В корзину»: кнопка занята ${btn.classList.contains('is-busy')}`);
    await sleep(1200);
    console.log(`   после ответа: степпер виден ${!stepper.hidden}, количество ${val()}, тост ${!!doc.querySelector('.cart-toast.is-visible')}`);
    for (let i = 0; i < 3; i++) { stepper.querySelector('.cart-stepper__button--plus').click(); await sleep(40); }
    console.log(`   сразу после трёх «+»: ${val()}`);
    await sleep(2000);
    ok(val() === '4' && server.qty === 4, `итог: на странице ${val()}, на сервере ${server.qty} | запросы: ${log.join('; ')}`);

    console.log('\n8) Ошибка сервера: лимит 5 шт., нажимаем «+» до 7');
    server.limit = 5; log.length = 0;
    for (let i = 0; i < 3; i++) { stepper.querySelector('.cart-stepper__button--plus').click(); await sleep(40); }
    console.log(`   сразу после кликов: ${val()} (оптимистично)`);
    await sleep(2500);
    ok(val() === '4' && server.qty === 4, `после ошибки откат к фактическому: на странице ${val()}, на сервере ${server.qty} | запросы: ${log.join('; ')}`);
    ok(maxInflight === 1, `одновременных запросов: ${maxInflight}`);
  }
})();
