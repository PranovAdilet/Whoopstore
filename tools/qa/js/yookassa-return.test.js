// Возврат из ЮKassa: настоящие cart.js + cart-drawer.js в jsdom. Модалка «Заказ
// оформлен» открывается, только если сервер положил в разметку номер заказа,
// сверив ключ (inc/woocommerce/cart-ui.php).
// Запуск — tools/qa/js/run.sh (аргументы: страница, каталог JS, адрес
// страницы, yes|no — ждём ли модалку, номер заказа).
const fs = require('fs'); const { JSDOM } = require('jsdom');
const [htmlFile, jsDir, pageUrl, expectOpen, orderNumber] = process.argv.slice(2);
const html = fs.readFileSync(htmlFile, 'utf8').replace(/<script[\s\S]*?<\/script>/g, '');
const dom = new JSDOM(html, { runScripts: 'outside-only', url: pageUrl, pretendToBeVisual: true });
const w = dom.window, doc = w.document;
const emptyCart = { items: [], items_count: 0, needs_shipping: false, shipping_rates: [], payment_methods: ['cheque'], totals: { total_items: '0', total_price: '0', total_shipping: '0', currency_minor_unit: 2, currency_symbol: '₽' } };
const log = [];
w.fetch = (url, opts = {}) => { log.push((opts.method || 'GET') + ' ' + url.replace(/^.*store\/v1\//, '')); return Promise.resolve({ ok: true, status: 200, headers: { get: () => null }, json: () => Promise.resolve(emptyCart) }); };
w.whoopstoreCartData = { root: '/wp-json/wc/store/v1/', nonce: 'n1' };
for (const f of ['cart.js', 'cart-drawer.js']) w.eval(fs.readFileSync(jsDir + '/' + f, 'utf8'));
const ok = (c, m) => { console.log((c ? '  ✓ ' : '  ✗ ') + m); if (!c) process.exitCode = 1; };
setTimeout(() => {
  const modal = doc.querySelector('.order-modal');
  const open = !modal.hidden && modal.hasAttribute('data-order');
  console.log(pageUrl);
  if (expectOpen === 'yes') {
    ok(open, 'модалка открыта (hidden снят, data-order есть)');
    ok(modal.dataset.order === orderNumber && doc.querySelector('.order-modal__number-value').textContent === orderNumber, 'номер заказа ' + orderNumber + ' в модалке');
    ok(doc.body.classList.contains('has-cart-drawer'), 'прокрутка страницы заблокирована, как при обычном оформлении');
    ok(w.location.href === 'http://localhost/', 'из адреса убраны order-placed и key: ' + w.location.href);
    doc.querySelector('.cart-button.order-modal__close').click();
    ok(modal.hidden && !modal.hasAttribute('data-order') && !doc.body.classList.contains('has-cart-drawer'), '«Продолжить покупки» закрывает модалку');
  } else {
    ok(!open, 'модалка не открывается');
    ok(doc.querySelector('.order-modal__number-value').textContent === '', 'номер не подставлен');
    ok(w.location.href === pageUrl, 'адрес не трогаем: ' + w.location.href);
  }
  ok(doc.querySelector('.site-header__cart-badge') !== null, 'корзина загружена: ' + log.join(', '));
}, 500);
