// Мгновенный отклик корзины в drawer корзины: настоящие cart.js + cart-drawer.js в
// jsdom против поддельного Store API со случайной задержкой 300–900 мс (ответы
// приходят не по порядку). Проверяет мгновенное обновление, накопление
// нажатий, один запрос на серию, строго последовательные запросы, отбрасывание
// устаревших ответов и отправку изменений до оформления заказа.
// Запуск — tools/qa/js/run.sh (аргументы: страница, каталог JS, ответ корзины).
const fs = require('fs'); const { JSDOM } = require('jsdom');
const [htmlFile, jsDir, seedFile] = process.argv.slice(2);
const html = fs.readFileSync(htmlFile, 'utf8').replace(/<script[\s\S]*?<\/script>/g, '');
const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/', pretendToBeVisual: true });
const w = dom.window, doc = w.document;
const seed = JSON.parse(fs.readFileSync(seedFile, 'utf8'));
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

  console.log('\n1) Три быстрых «+» (по 40 мс)');
  for (let i = 0; i < 3; i++) { click('.cart-stepper__button--plus'); console.log(`   сразу после клика ${i + 1}: количество ${qtyShown()}, счётчик ${badge()}, итог ${total()}`); await sleep(40); }
  await sleep(2000);
  ok(qtyShown() === '4' && server.qty === 4, `итог: на экране ${qtyShown()}, на сервере ${server.qty} (ожидается 4)`);
  ok(log.length === 1 && /update-item q=4/.test(log[0]), `запросов: ${log.length} — ${log.join('; ')}`);

  console.log('\n2) Четыре быстрых «−» с 4 (последний удаляет позицию)');
  log.length = 0;
  for (let i = 0; i < 4; i++) { click('.cart-stepper__button--minus'); console.log(`   сразу после клика ${i + 1}: количество ${qtyShown()}, пустая корзина: ${empty()}, счётчик ${badge()}`); await sleep(40); }
  await sleep(2000);
  ok(empty() && server.qty === 0, `итог: пустая корзина на экране ${empty()}, на сервере q=${server.qty}`);
  ok(log.length === 1 && /remove-item/.test(log[0]), `запросов: ${log.length} — ${log.join('; ')}`);

  console.log('\n3) Нажатие во время ответа: «+», пауза 350 мс (запрос уже ушёл), ещё «+»');
  server.qty = 2; await w.whoopstoreCart.refresh(); log.length = 0;
  click('.cart-stepper__button--plus'); await sleep(350); click('.cart-stepper__button--plus');
  console.log(`   сразу после второго клика: ${qtyShown()}`);
  await sleep(2500);
  ok(qtyShown() === '4' && server.qty === 4, `итог: на экране ${qtyShown()}, на сервере ${server.qty} (ожидается 4) | запросы: ${log.join('; ')}`);

  console.log('\n4) «Очистить корзину»');
  log.length = 0; doc.querySelector('.cart-drawer__clear').click();
  console.log(`   сразу после клика: пустая корзина ${empty()}, счётчик ${badge()}`);
  await sleep(2500);
  ok(empty() && server.qty === 0, `итог: пусто на экране ${empty()}, на сервере q=${server.qty} | запросы: ${log.join('; ')}`);

  console.log('\n5) «+» и сразу оформление заказа — изменение должно уйти раньше заказа');
  server.qty = 1; await w.whoopstoreCart.refresh(); log.length = 0;
  click('.cart-stepper__button--plus'); const done = w.whoopstoreCart.checkout({});
  await done; await sleep(200);
  ok(log[0] && /update-item q=2/.test(log[0]) && /checkout/.test(log[1] || ''), `порядок: ${log.join('; ')}`);

  console.log('\n6) Одновременных запросов к серверу за весь тест (должно быть 1)');
  ok(maxInflight === 1, `максимум одновременно: ${maxInflight}`);
})();
