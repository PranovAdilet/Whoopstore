#!/bin/sh
# Интерфейс корзины, оформления и возврата из ЮKassa, а также валидность
# блоков редактора — без браузера: настоящие скрипты темы и бандлы WordPress
# исполняются в jsdom против поддельного Store API.
#
# jsdom в проект не входит и ставится ВНЕ его (зависимостей у темы нет):
#   npm install --prefix /tmp/whoopstore-qa-node jsdom@24.1.3
# Каталог берётся из WHOOPSTORE_QA_NODE (по умолчанию /tmp/whoopstore-qa-node).
# /tmp на хостинге могут очистить — тогда просто поставить заново.
#
# Запуск из корня сайта:
#   sh tools/qa/js/run.sh
# Код возврата 1 — есть непройденные проверки, 2 — не хватает окружения.

cd "$(dirname "$0")/../../.." || exit 2
ROOT=$(pwd)
PHP=${PHP:-/usr/local/php/cgi/8.2/bin/php}
NODE_DIR=${WHOOPSTORE_QA_NODE:-/tmp/whoopstore-qa-node}
QA=tools/qa/js
JS=wp-content/themes/whoopstore/assets/js

if [ -z "${NODE:-}" ]; then
	NODE=$(command -v node || ls "$HOME"/.nvm/versions/node/*/bin/node 2>/dev/null | tail -n 1)
fi

if [ -z "$NODE" ] || [ ! -x "$NODE" ]; then
	echo "Не найден node. Укажите путь: NODE=/путь/к/node sh $QA/run.sh"
	exit 2
fi

NODE_PATH="$NODE_DIR/node_modules${NODE_PATH:+:$NODE_PATH}"
export NODE_PATH

if ! "$NODE" -e "require('jsdom')" 2>/dev/null; then
	echo "Не найден jsdom в $NODE_DIR. Поставить вне проекта:"
	echo "  npm install --prefix $NODE_DIR jsdom@24.1.3"
	exit 2
fi

WORK=$(mktemp -d "${TMPDIR:-/tmp}/whoopstore-qa-js.XXXXXX") || exit 2
trap 'rm -rf "$WORK"' EXIT

"$PHP" "$QA/prepare.php" "$WORK" 2>/dev/null || { echo "Не удалось подготовить данные (tools/qa/js/prepare.php)."; exit 2; }
. "$WORK/env"

render() {
	"$PHP" tools/qa/render.php "$1" > "$2" 2>> "$WORK/render.log"
}

render / "$WORK/home.html"
render "$PRODUCT_PATH" "$WORK/product.html"
render "/?order-placed=$ORDER_ID&key=$ORDER_KEY" "$WORK/home-paid.html"
render "/?order-placed=$ORDER_ID&key=wc_order_wrong" "$WORK/home-wrong-key.html"

PASS=0
FAIL=0
FAILED=""

run() {
	name=$1
	shift
	echo
	echo "=== $name"

	if "$NODE" "$@"; then
		PASS=$((PASS + 1))
	else
		FAIL=$((FAIL + 1))
		FAILED="$FAILED $name"
	fi
}

run cart-drawer "$QA/cart-drawer.test.js" "$WORK/home.html" "$JS" "$QA/fixtures/cart.json"
run cart-product "$QA/cart-product.test.js" "$WORK/product.html" "$JS" "$QA/fixtures/cart.json"
run checkout-cdek "$QA/checkout-cdek.test.js" "$WORK/home.html" "$JS"
run checkout-cdek-nokey "$QA/checkout-cdek-nokey.test.js" "$WORK/home.html" "$JS"
run yookassa-submit-redirect "$QA/yookassa-submit.test.js" "$WORK/home.html" "$JS" "$QA/fixtures/cart.json" yookassa
run yookassa-submit-no-redirect "$QA/yookassa-submit.test.js" "$WORK/home.html" "$JS" "$QA/fixtures/cart.json" cheque
run yookassa-return "$QA/yookassa-return.test.js" "$WORK/home-paid.html" "$JS" "http://localhost/?order-placed=$ORDER_ID&key=$ORDER_KEY" yes "$ORDER_NUMBER"
run yookassa-return-wrong-key "$QA/yookassa-return.test.js" "$WORK/home-wrong-key.html" "$JS" "http://localhost/?order-placed=$ORDER_ID&key=wc_order_wrong" no "$ORDER_NUMBER"
WP_ROOT=$ROOT WP_SCRIPT_ORDER=$WORK/script-order.txt run blocks "$QA/blocks.test.js" "$WORK"/blocks/*.html

echo
echo "Тестов: $((PASS + FAIL)), пройдено: $PASS, не пройдено: $FAIL${FAILED:+ —$FAILED}"
[ "$FAIL" -eq 0 ]
