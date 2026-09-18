// Валидность блоков в редакторе без браузера: собственные бандлы WordPress
// этой установки исполняются в окне jsdom, wp.blocks.parse() разбирает
// переданные файлы. Только чтение. Невалидный блок заказчик увидел бы в
// редакторе как «Блок содержит неожиданное содержимое».
// Запуск — tools/qa/js/run.sh (WP_ROOT, WP_SCRIPT_ORDER и файлы готовит он);
// VERBOSE=1 печатает дерево блоков и разницу разметки невалидных.
const fs = require('fs'), path = require('path'), vm = require('vm');
const { JSDOM, VirtualConsole } = require('jsdom');
// jsdom не разбирает @layer в стилях редактора — на проверку блоков это не влияет.
const vc = new VirtualConsole();
vc.on('jsdomError', e => { if (!/Could not parse CSS stylesheet/.test(e.message)) console.error(e.message); });
const ROOT = process.env.WP_ROOT, DIST = ROOT + '/wp-includes/js/dist/';
const dom = new JSDOM('<!doctype html><html><head></head><body></body></html>', { runScripts: 'outside-only', pretendToBeVisual: true, url: 'http://localhost/', virtualConsole: vc });
const w = dom.window;
const ctx = dom.getInternalVMContext();
w.matchMedia = w.matchMedia || (() => ({ matches: false, addListener() {}, removeListener() {}, addEventListener() {}, removeEventListener() {} }));
w.ResizeObserver = w.ResizeObserver || class { observe() {} unobserve() {} disconnect() {} };
w.IntersectionObserver = w.IntersectionObserver || class { observe() {} unobserve() {} disconnect() {} };
w.wp = {};
const files = { react: 'vendor/react.min.js', 'react-jsx-runtime': 'vendor/react-jsx-runtime.min.js', 'react-dom': 'vendor/react-dom.min.js', moment: 'vendor/moment.min.js', lodash: 'vendor/lodash.min.js' };
for (const h of ['lodash', ...fs.readFileSync(process.env.WP_SCRIPT_ORDER, 'utf8').trim().split('\n')]) {
  const f = files[h] || (h.startsWith('wp-') ? h.slice(3) + '.min.js' : null);
  if (!f || !fs.existsSync(DIST + f)) continue;
  try { new vm.Script(fs.readFileSync(DIST + f, 'utf8'), { filename: f }).runInContext(ctx); } catch (e) { console.error('load warn', h, String(e.message).slice(0, 120)); }
}
const wp = w.wp;
wp.blockLibrary.registerCoreBlocks();
w.console.warn = w.console.error = w.console.info = w.console.log = () => {};
console.log('блоков ядра:', wp.blocks.getBlockTypes().length);
const walk = (bs, d, out) => { for (const b of bs) { out.push([d, b]); walk(b.innerBlocks || [], d + 1, out); } return out; };
const verbose = process.env.VERBOSE === '1';
for (const file of process.argv.slice(2)) {
  const all = walk(wp.blocks.parse(fs.readFileSync(file, 'utf8')), 0, []);
  const bad = all.filter(([, b]) => b.isValid === false);
  console.log(`${bad.length ? '  ✗' : '  ✓'} ${path.basename(file)}: блоков ${all.length}, невалидных ${bad.length}`);
  if (bad.length) process.exitCode = 1;
  if (!verbose) continue;
  for (const [d, b] of all) console.log(`${'  '.repeat(d + 1)}${b.isValid === false ? '✗' : '✓'} ${b.name}${b.attributes && b.attributes.className ? ' .' + b.attributes.className : ''}${b.attributes && b.attributes.tagName ? ' <' + b.attributes.tagName + '>' : ''}`);
  for (const [, b] of bad) {
    console.log(`\n  ✗ ${b.name} .${b.attributes.className || ''}`);
    for (const i of (b.validationIssues || [])) {
      const a = i.args || [];
      if (String(a[0]).startsWith('Block validation failed')) {
        console.log('    save() ожидает:', String(a[3]).replace(/\s+/g, ' ').slice(0, 260));
        console.log('    в файле       :', String(a[4]).replace(/\s+/g, ' ').slice(0, 260));
      } else console.log('    причина:', a.map(x => typeof x === 'string' ? x.replace(/\s+/g, ' ') : JSON.stringify(x)).join(' | ').slice(0, 300));
    }
  }
}
