// Форма «Ваш заказ» без ключа Яндекс.Карт: доставка до ПВЗ не предлагается,
// курьер СДЭК работает. Настоящие cart.js + cart-drawer.js в jsdom против
// поддельного Store API по реальным ответам (тестовый режим СДЭК, 2026-09-18).
// Запуск — tools/qa/js/run.sh (аргументы: страница, каталог JS темы).
const fs = require('fs'); const { JSDOM } = require('jsdom');
const [htmlFile, jsDir] = process.argv.slice(2);
const html = fs.readFileSync(htmlFile, 'utf8').replace(/<script[\s\S]*?<\/script>/g, '');
const sleep = ms => new Promise(r => setTimeout(r, ms));
const ok = (c, m) => { console.log((c ? '  ✓ ' : '  ✗ ') + m); if (!c) process.exitCode = 1; };
const POINTS = [
  { code: 'SPB55', name: 'SPB55, Санкт-Петербург, пр-т 2-й Муринский', type: 'PVZ', location: { address: 'пр-т 2-й Муринский, 49' } },
  { code: 'SPB12', name: 'SPB12, Санкт-Петербург, Невский', type: 'PVZ', location: { address: 'Невский пр-т, 100' } },
  { code: 'SPB900', name: 'Постамат', type: 'POSTAMAT', location: { address: 'ул. Постаматная, 1' } },
];
function boot() {
  const dom = new JSDOM(html, { runScripts: 'outside-only', url: 'http://localhost/', pretendToBeVisual: true });
  const w = dom.window, doc = w.document;
  const srv = { city: '', postcode: '', selected: 'flat_rate:1', log: [], checkoutBody: null };
  const rate = (id, name, price, desc, time) => ({ rate_id: id, method_id: id.split(':')[0], name, price: String(price), description: desc, delivery_time: time || '', selected: srv.selected === id });
  const cart = () => {
    const cdekOk = srv.city === 'Санкт-Петербург';
    const rates = [];
    if (cdekOk) rates.push(rate('official_cdek:137', 'Доставка курьером', 39000, 'СДЭК доставит заказ по адресу', '1-2 дн.'));
    rates.push(rate('flat_rate:1', 'Экспресс доставка', 150000, 'день в день по Москве'), rate('local_pickup:2', 'Самовывоз', 0, 'Москва, ул. Олеко Дундича, 3'));
    if (!rates.some(r => r.selected)) { rates.find(r => r.rate_id === 'flat_rate:1').selected = true; srv.selected = 'flat_rate:1'; }
    const sel = rates.find(r => r.selected);
    return {
      items: [{ key: 'k1', id: 118, quantity: 1, name: 'Ремешок', totals: { line_total: '1999000', line_subtotal: '1999000' }, prices: { price: '1999000' }, variation: [], images: [] }],
      items_count: 1, needs_shipping: true, payment_methods: ['cheque'],
      shipping_address: { city: srv.city, postcode: srv.postcode, country: 'RU' },
      shipping_rates: [{ package_id: 0, shipping_rates: rates }],
      totals: { total_items: '1999000', total_shipping: sel.price, total_price: String(1999000 + +sel.price), currency_minor_unit: 2, currency_symbol: '₽', currency_thousand_separator: ' ', currency_decimal_separator: ',' },
      extensions: {
        official_cdek: { points: cdekOk ? JSON.stringify(POINTS) : '[]' },
        whoopstore: { cdek: {
          tiles: [{ kind: 'door', title: 'Доставка курьером', description: 'СДЭК доставит заказ по адресу' }],
          rates: cdekOk ? { 'official_cdek:137': { kind: 'door', pointType: null } } : {},
          widget: { script: '/widget.js', apiKey: '', lang: 'rus' },
          noPoints: 'В этом направлении нет пунктов выдачи СДЭК — выберите другой способ доставки.',
          unavailable: srv.city && !cdekOk ? 'Не удалось определить населенный пункт ' + srv.city : '',
        } },
      },
    };
  };
  w.fetch = (url, opts = {}) => {
    const path = url.replace(/^.*store\/v1\//, ''); const body = opts.body ? JSON.parse(opts.body) : {};
    srv.log.push((opts.method || 'GET') + ' ' + path + (path === 'cart/update-customer' ? ' ' + body.shipping_address.city + '/' + body.shipping_address.postcode : '') + (path === 'cart/select-shipping-rate' ? ' ' + body.rate_id : ''));
    if (path === 'cart/update-customer') { srv.city = body.shipping_address.city; srv.postcode = body.shipping_address.postcode; }
    if (path === 'cart/select-shipping-rate') srv.selected = body.rate_id;
    let out = cart();
    if (path === 'checkout') { srv.checkoutBody = body; out = { order_id: 501, order_number: '501', payment_result: { redirect_url: 'http://localhost/checkout/order-received/501/' } }; }
    return new Promise(res => setTimeout(() => res({ ok: true, status: 200, headers: { get: () => null }, json: () => Promise.resolve(out) }), 30));
  };
  const widget = { created: 0, opens: 0, closes: 0, params: null, updates: [] };
  w.CDEKWidget = function (params) { widget.created++; widget.params = params; this.open = () => widget.opens++; this.close = () => widget.closes++; this.updateOfficesRaw = p => widget.updates.push(p.length); this.updateLocation = () => {}; this.clearSelection = () => {}; };
  w.whoopstoreCartData = { root: '/wp-json/wc/store/v1/', nonce: 'n1' };
  for (const f of ['cart.js', 'cart-drawer.js']) w.eval(fs.readFileSync(jsDir + '/' + f, 'utf8'));
  const form = doc.querySelector('.checkout-form');
  const tiles = () => [...doc.querySelectorAll('.checkout-shipping__option')].map(o => (o.querySelector('input').checked ? '●' : '○') + ' ' + o.querySelector('.checkout-shipping__title').textContent + ' — ' + o.querySelector('.checkout-shipping__text').textContent);
  const tile = t => [...doc.querySelectorAll('.checkout-shipping__option')].find(o => o.querySelector('.checkout-shipping__title').textContent === t);
  const pick = t => tile(t).querySelector('.checkout-shipping__tile').click();
  const shown = () => [...doc.querySelectorAll('[data-address-field]')].filter(f => !f.hidden).map(f => f.querySelector('input').name + (f.querySelector('input').required ? '*' : '')).join(',');
  const type = (name, v) => { const el = form.elements[name]; el.value = v; el.dispatchEvent(new w.Event('input', { bubbles: true })); };
  const fill = () => { form.elements.full_name.value = 'Тест'; form.elements.phone.value = '9001234567'; form.elements.email.value = 't@example.com'; form.elements.privacy_consent.checked = true; };
  const submit = () => form.dispatchEvent(new w.Event('submit', { cancelable: true }));
  const err = () => { const e = doc.querySelector('.checkout-form__error'); return e.hidden ? '' : e.textContent; };
  const totals = () => ({ ship: doc.querySelector('.checkout-totals__row--shipping').hidden ? '(скрыто)' : doc.querySelector('.checkout-totals__shipping').textContent, term: doc.querySelector('.checkout-totals__row--delivery').hidden ? '(скрыто)' : doc.querySelector('.checkout-totals__delivery').textContent, grand: doc.querySelector('.checkout-totals__grand').textContent });
  return { w, doc, srv, widget, form, tiles, pick, shown, type, fill, submit, err, totals, open: () => doc.querySelector('.cart-drawer__checkout').click() };
}
(async () => {
  console.log('Без ключа Яндекс.Карт: ПВЗ не предлагается');
  let t = boot(); await sleep(300); t.open(); await sleep(300);
  console.log('   плитки:', t.tiles());
  ok(t.tiles().length === 3 && /Доставка курьером/.test(t.tiles()[0]) && !t.tiles().some(x => /ПВЗ/.test(x)), '3 плитки: курьер, «Экспресс», «Самовывоз»');
  t.type('city', 'Санкт-Петербург'); t.type('postcode', '190000'); await sleep(1200);
  console.log('   плитки:', t.tiles());
  ok(/○ Доставка курьером — 390 ₽/.test(t.tiles()[0]) && t.tiles().length === 3 && /● Экспресс/.test(t.tiles()[1]), 'после города — цена курьера, ПВЗ нет, «Экспресс» выбран');
  t.pick('Доставка курьером'); await sleep(300); t.fill(); t.form.elements.street.value = 'Невский'; t.form.elements.house.value = '1'; t.submit(); await sleep(500);
  const b = t.srv.checkoutBody || {};
  ok(t.srv.selected === 'official_cdek:137' && b.shipping_address && b.shipping_address.address_1 === 'Невский, 1' && !b.extensions && t.widget.created === 0, 'заказ курьером: полный адрес, без полей ПВЗ, карта не грузилась');
})();
