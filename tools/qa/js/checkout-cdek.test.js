// Форма «Ваш заказ» со СДЭК: настоящие cart.js + cart-drawer.js в jsdom против
// поддельного Store API, собранного по реальным ответам (тестовый режим СДЭК,
// 2026-09-18). Виджет карты ПВЗ — заглушка. Выбор способа доставки ведёт себя
// как на сервере (mu-plugins/whoopstore-commerce/cdek.php): пропавший тариф не
// подменяется другим, а возвращается, когда снова доступен.
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
  const srv = { city: '', postcode: '', selected: 'flat_rate:1', lost: '', log: [], checkoutBody: null };
  const rate = (id, name, price, desc, time) => ({ rate_id: id, method_id: id.split(':')[0], name, price: String(price), description: desc, delivery_time: time || '', selected: srv.selected === id });
  const cart = () => {
    const cdekOk = srv.city === 'Санкт-Петербург';
    const rates = [];
    if (cdekOk) rates.push(rate('official_cdek:137', 'Доставка курьером', 39000, 'СДЭК доставит заказ по адресу', '1-2 дн.'), rate('official_cdek:136', 'ПВЗ CDEK', 22200, 'Выберите пункт выдачи на карте', '1-2 дн.'));
    rates.push(rate('flat_rate:1', 'Экспресс доставка', 150000, 'день в день по Москве'), rate('local_pickup:2', 'Самовывоз', 0, 'Москва, ул. Олеко Дундича, 3'));
    const ids = rates.map(r => r.rate_id);
    if (srv.selected && !ids.includes(srv.selected)) { srv.lost = srv.selected; srv.selected = ''; }
    else if (!srv.selected && srv.lost && ids.includes(srv.lost)) { srv.selected = srv.lost; srv.lost = ''; }
    rates.forEach(r => { r.selected = r.rate_id === srv.selected; });
    const sel = rates.find(r => r.selected);
    const shipping = sel ? +sel.price : 0;
    return {
      items: [{ key: 'k1', id: 118, quantity: 1, name: 'Ремешок', totals: { line_total: '1999000', line_subtotal: '1999000' }, prices: { price: '1999000' }, variation: [], images: [] }],
      items_count: 1, needs_shipping: true, payment_methods: ['cheque'],
      shipping_address: { city: srv.city, postcode: srv.postcode, country: 'RU' },
      shipping_rates: [{ package_id: 0, shipping_rates: rates }],
      totals: { total_items: '1999000', total_shipping: String(shipping), total_price: String(1999000 + shipping), currency_minor_unit: 2, currency_symbol: '₽', currency_thousand_separator: ' ', currency_decimal_separator: ',' },
      extensions: {
        official_cdek: { points: cdekOk ? JSON.stringify(POINTS) : '[]' },
        whoopstore: { cdek: {
          tiles: [{ kind: 'door', title: 'Доставка курьером', description: 'СДЭК доставит заказ по адресу' }, { kind: 'office', title: 'ПВЗ CDEK', description: 'Выберите пункт выдачи на карте' }],
          rates: cdekOk ? { 'official_cdek:137': { kind: 'door', pointType: null }, 'official_cdek:136': { kind: 'office', pointType: 'PVZ' } } : {},
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
    if (path === 'cart/select-shipping-rate') { srv.selected = body.rate_id; srv.lost = ''; }
    let out = cart(); let status = 200;
    if (path === 'checkout') {
      srv.checkoutBody = body;
      out = srv.selected ? { order_id: 501, order_number: '501', payment_result: { redirect_url: 'http://localhost/checkout/order-received/501/' } } : { code: 'woocommerce_rest_invalid_shipping_option', message: 'Для этого заказа необходимо выбрать способ доставки.' };
      status = srv.selected ? 200 : 400;
    }
    return new Promise(res => setTimeout(() => res({ ok: status < 400, status, headers: { get: () => null }, json: () => Promise.resolve(out) }), 30));
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
  console.log('A) Экспресс по умолчанию → ввод города → ПВЗ → пункт → оформление');
  let t = boot(); await sleep(300); t.open(); await sleep(300);
  console.log('   плитки:', t.tiles());
  ok(t.tiles().length === 4 && /○ Доставка курьером — СДЭК доставит заказ по адресу/.test(t.tiles()[0]) && /○ ПВЗ CDEK — Выберите пункт/.test(t.tiles()[1]) && /● Экспресс/.test(t.tiles()[2]), '4 плитки в порядке макета, СДЭК — с подписями из макета, выбран «Экспресс»');
  ok(t.shown() === 'city*,postcode*,street*,house*,apartment', 'поля: весь адрес — ' + t.shown());
  t.srv.log.length = 0; t.type('city', 'Санкт-Пет'); await sleep(200); t.type('city', 'Санкт-Петербург'); t.type('postcode', '1900'); await sleep(900);
  ok(!t.srv.log.some(l => /update-customer/.test(l)), 'неполный индекс не отправляется: ' + (t.srv.log.join('; ') || 'запросов нет'));
  t.type('postcode', '190000'); await sleep(1100);
  ok(t.srv.log.filter(l => /update-customer/.test(l)).length === 1, 'адрес ушёл один раз: ' + t.srv.log.join('; '));
  console.log('   плитки:', t.tiles());
  ok(/○ Доставка курьером — 390 ₽ · СДЭК/.test(t.tiles()[0]) && /○ ПВЗ CDEK — 222 ₽ · Выберите/.test(t.tiles()[1]) && /● Экспресс/.test(t.tiles()[2]), 'у СДЭК появились цены, «Экспресс» остался выбранным');
  t.srv.log.length = 0; t.pick('ПВЗ CDEK'); await sleep(300);
  ok(t.srv.log.includes('POST cart/select-shipping-rate official_cdek:136'), 'выбран тариф 136');
  ok(t.widget.opens === 1 && t.widget.params.officesRaw.length === 2 && t.widget.params.officesRaw.every(p => p.type === 'PVZ') && t.widget.params.defaultLocation === 'Санкт-Петербург' && t.widget.params.popup === true && t.widget.params.hideDeliveryOptions.door === true && !('apiKey' in t.widget.params), 'карта открылась: 2 ПВЗ без постамата, город, popup, без двери, ключ по умолчанию');
  ok(t.shown() === 'city*,postcode', 'для ПВЗ — только город (обязателен) и индекс: ' + t.shown());
  ok(t.totals().ship === '₽ 222,00' && t.totals().term === '1-2 дн.', 'итоги: доставка ' + t.totals().ship + ', срок ' + t.totals().term + ', итого ' + t.totals().grand);
  t.widget.params.onChoose('office', {}, { code: 'SPB55', name: 'SPB55, Санкт-Петербург, пр-т 2-й Муринский' }); await sleep(50);
  ok(/● ПВЗ CDEK — 222 ₽ · пр-т 2-й Муринский, 49/.test(t.tiles()[1]) && t.widget.closes === 1, 'пункт выбран, адрес в плитке, карта закрыта: ' + t.tiles()[1]);
  t.pick('ПВЗ CDEK'); await sleep(50);
  ok(t.widget.opens === 2 && t.widget.created === 1, 'повторное нажатие на выбранную плитку снова открывает карту (тот же виджет)');
  t.fill(); t.srv.log.length = 0; t.submit(); await sleep(400);
  const b = t.srv.checkoutBody || {};
  ok(b.extensions && b.extensions.official_cdek.office_code === 'SPB55' && b.shipping_address.address_1 === '' && b.shipping_address.city === 'Санкт-Петербург' && b.shipping_address.postcode === '190000', 'в заказ ушли код ПВЗ SPB55, город и индекс, без улицы');
  ok(!t.doc.querySelector('.order-modal').hidden, 'модалка «Заказ оформлен»');

  console.log('\nB) Плитка ПВЗ до ввода города');
  t = boot(); await sleep(300); t.open(); await sleep(300);
  t.srv.log.length = 0; t.pick('ПВЗ CDEK'); await sleep(200);
  ok(/● ПВЗ CDEK — Выберите пункт/.test(t.tiles()[1]) && t.shown() === 'city*,postcode', 'плитка отмечена, поля — город и индекс');
  ok(t.totals().ship === '(скрыто)' && t.totals().term === '(скрыто)' && t.totals().grand === '₽ 19 990,00', 'в итогах нет доставки до расчёта: итого ' + t.totals().grand);
  ok(t.doc.activeElement === t.form.elements.city, 'фокус в поле «Город»');
  t.type('city', 'Санкт-Петербург'); await sleep(1300);
  ok(t.srv.log.includes('POST cart/select-shipping-rate official_cdek:136') && /● ПВЗ CDEK — 222 ₽/.test(t.tiles()[1]), 'тариф посчитан и выбран автоматически: ' + t.tiles()[1]);
  ok(t.widget.opens === 1, 'карта открылась сама');
  t.fill(); t.srv.log.length = 0; t.submit(); await sleep(400);
  ok(!t.srv.log.some(l => /checkout/.test(l)) && t.widget.opens === 2, 'оформление без пункта: заказ не отправлен, снова карта');

  console.log('\nC) Курьер до ввода города, город не найден');
  t = boot(); await sleep(300); t.open(); await sleep(300);
  t.pick('Доставка курьером'); await sleep(100);
  ok(t.shown() === 'city*,postcode*,street*,house*,apartment', 'курьер — весь адрес: ' + t.shown());
  t.type('city', 'Нигде'); await sleep(1300);
  ok(/Не удалось определить населенный пункт Нигде/.test(t.err()), 'сообщение: ' + t.err());
  console.log('   плитки:', t.tiles());
  t.fill(); t.form.elements.postcode.value = '101000'; t.form.elements.street.value = 'ул. 1'; t.form.elements.house.value = '1'; t.srv.log.length = 0; t.submit(); await sleep(1500);
  ok(!t.srv.log.some(l => /checkout/.test(l)) && /Не удалось определить/.test(t.err()), 'заказ с курьером без тарифа не отправлен: ' + t.err());

  console.log('\nD) Регрессия: «Экспресс» и «Самовывоз»');
  t = boot(); await sleep(300); t.open(); await sleep(300); t.fill();
  ['city', 'postcode', 'street', 'house'].forEach((n, i) => t.form.elements[n].value = ['Москва', '101000', 'Тверская', '1'][i]);
  t.submit(); await sleep(400);
  let e = t.srv.checkoutBody || {};
  ok(e.shipping_address && e.shipping_address.address_1 === 'Тверская, 1' && !e.extensions && t.srv.selected === 'flat_rate:1', '«Экспресс»: полный адрес, без полей СДЭК');
  t = boot(); await sleep(300); t.open(); await sleep(300); t.pick('Самовывоз'); await sleep(200); t.fill();
  ok(t.shown() === '', 'самовывоз — без полей адреса');
  t.submit(); await sleep(400); e = t.srv.checkoutBody || {};
  ok(e.shipping_address && e.shipping_address.city === '' && !e.extensions && !t.srv.log.some(l => /update-customer/.test(l)), 'самовывоз: без адреса и без запросов адреса');

  console.log('\nE) Выбранный курьер СДЭК пропал: город сменили на неизвестный СДЭК (I3)');
  t = boot(); await sleep(300); t.open(); await sleep(300);
  t.type('city', 'Санкт-Петербург'); t.type('postcode', '190000'); await sleep(1300);
  t.pick('Доставка курьером'); await sleep(300);
  ok(t.srv.selected === 'official_cdek:137' && /● Доставка курьером/.test(t.tiles()[0]), 'курьер СДЭК выбран');
  t.type('city', 'Нигде'); await sleep(1300);
  console.log('   плитки:', t.tiles());
  ok(!t.tiles().some(x => x.startsWith('●')), 'ни одна плитка не отмечена — «Экспресс» молча не подставлен');
  ok(/^Способ доставки «Доставка курьером» недоступен для указанного адреса\. Выберите другой способ\.$/.test(t.err()), 'сообщение: ' + t.err());
  ok(t.totals().ship === '(скрыто)' && t.totals().term === '(скрыто)' && t.totals().grand === '₽ 19 990,00', 'в итогах нет доставки: итого ' + t.totals().grand);
  t.doc.querySelector('.cart-drawer__close').click(); t.open(); await sleep(300);
  ok(/недоступен для указанного адреса/.test(t.err()), 'сообщение видно и после повторного открытия формы');
  t.fill(); ['postcode', 'street', 'house'].forEach((n, i) => t.form.elements[n].value = ['101000', 'ул. 1', '1'][i]);
  t.srv.log.length = 0; t.submit(); await sleep(1500);
  ok(/выбрать способ доставки/.test(t.err()) && t.doc.querySelector('.order-modal').hidden, 'без способа заказ не оформлен: ' + t.err());
  t.pick('Самовывоз'); await sleep(300);
  ok(t.err() === '' && /● Самовывоз/.test(t.tiles().join('|')) && t.srv.selected === 'local_pickup:2', 'покупатель сам выбрал «Самовывоз» — сообщение исчезло');

  console.log('\nF) Город вернули — вернулся и выбор курьера');
  t = boot(); await sleep(300); t.open(); await sleep(300);
  t.type('city', 'Санкт-Петербург'); t.type('postcode', '190000'); await sleep(1300);
  t.pick('Доставка курьером'); await sleep(300);
  t.type('city', 'Нигде'); await sleep(1300);
  ok(/недоступен/.test(t.err()), 'выбор пропал, сообщение есть');
  t.type('city', 'Санкт-Петербург'); await sleep(1300);
  ok(t.err() === '' && /● Доставка курьером/.test(t.tiles()[0]) && t.totals().ship === '₽ 390,00', 'курьер снова выбран, сообщения нет, доставка ' + t.totals().ship);
})();
