// Отправка «Ваш заказ»: настоящие cart.js + cart-drawer.js в jsdom. Без перехода
// к оплате открывается модалка с номером; с переходом на страницу ЮKassa
// браузер уходит туда, а модалка остаётся закрытой.
// Запуск — tools/qa/js/run.sh (аргументы: страница, каталог JS, ответ корзины,
// yookassa|cheque).
const fs = require('fs'); const { JSDOM, VirtualConsole } = require('jsdom');
const [htmlFile, jsDir, seedFile, mode] = process.argv.slice(2);
const html = fs.readFileSync(htmlFile, 'utf8').replace(/<script[\s\S]*?<\/script>/g, '');
const vc = new VirtualConsole(); const nav = [];
vc.on('jsdomError', e => { if (/navigation/i.test(e.message)) nav.push(e.message); else console.log('jsdomError', e.message); });
const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/', pretendToBeVisual: true, virtualConsole: vc });
const w = dom.window, doc = w.document;
const seed = JSON.parse(fs.readFileSync(seedFile, 'utf8'));
const redirect = mode === 'yookassa' ? 'https://yoomoney.ru/checkout/payments/v2/contract?orderId=test' : 'http://localhost/checkout/order-received/999/?key=wc_order_x';
let sent = null;
w.fetch = (url, opts = {}) => {
  const path = url.replace(/^.*store\/v1\//, '');
  if (path === 'checkout') sent = JSON.parse(opts.body);
  const out = path === 'checkout' ? { order_id: 999, order_number: '999', status: 'pending', payment_result: { payment_status: 'success', redirect_url: redirect } } : seed;
  return Promise.resolve({ ok: true, status: 200, headers: { get: () => null }, json: () => Promise.resolve(out) });
};
w.whoopstoreCartData = { root: '/wp-json/wc/store/v1/', nonce: 'n1' };
for (const f of ['cart.js', 'cart-drawer.js']) w.eval(fs.readFileSync(jsDir + '/' + f, 'utf8'));
const ok = (c, m) => { console.log((c ? '  ✓ ' : '  ✗ ') + m); if (!c) process.exitCode = 1; };
setTimeout(() => {
  const form = doc.querySelector('.checkout-form');
  form.elements.full_name.value = 'Тест'; form.elements.phone.value = '9001234567'; form.elements.email.value = 't@example.com';
  form.elements.privacy_consent.checked = true;
  form.reportValidity = () => true;
  form.dispatchEvent(new w.Event('submit', { cancelable: true }));
  setTimeout(() => {
    const modal = doc.querySelector('.order-modal');
    console.log('режим:', mode);
    ok(sent !== null, 'заказ ушёл в Store API (способ оплаты: ' + (sent && sent.payment_method) + ')');
    if (mode === 'yookassa') {
      ok(nav.length === 1, 'переход на страницу оплаты ЮKassa (jsdom: ' + (nav[0] || 'нет') + ')');
      ok(modal.hidden && !modal.hasAttribute('data-order'), 'модалка не открывается');
    } else {
      ok(nav.length === 0, 'остаёмся на сайте');
      ok(!modal.hidden && modal.dataset.order === '999' && doc.querySelector('.order-modal__number-value').textContent === '999', 'модалка «Заказ оформлен» с номером 999');
      ok(doc.querySelector('.cart-drawer').hidden && doc.body.classList.contains('has-cart-drawer'), 'drawer закрыт, прокрутка заблокирована модалкой');
    }
  }, 500);
}, 500);
