(function () {
	'use strict';

	var app = document.getElementById('pos-app');
	if (!app) return;

	var apiBase = app.getAttribute('data-api') || '';
	var token = app.getAttribute('data-token') || '';
	var cardUrl = app.getAttribute('data-card-url') || '';
	var hasCardGateway = app.getAttribute('data-has-card-gateway') === '1';
	var fullscreenAuto = app.getAttribute('data-fullscreen-auto') === '1';
	var hideOutOfStock = app.getAttribute('data-hide-oos') === '1';
	var allowOutOfStockSale = app.getAttribute('data-allow-oos-sale') === '1';
	var paymentAdjustments = {};
	var cargos = [];

	function parseJsonScript(id) {
		var el = document.getElementById(id);
		if (!el) return null;
		try {
			return JSON.parse(el.textContent || 'null');
		} catch (e) {
			return null;
		}
	}

	paymentAdjustments = parseJsonScript('pos-payment-adjustments') || {};
	cargos = parseJsonScript('pos-cargos-data') || [];
	if (!paymentAdjustments || typeof paymentAdjustments !== 'object') paymentAdjustments = {};
	if (!Array.isArray(cargos)) cargos = [];

	try {
		if (!Object.keys(paymentAdjustments).length) {
			paymentAdjustments = JSON.parse(app.getAttribute('data-payment-adjustments') || '{}') || {};
		}
	} catch (e) {
		paymentAdjustments = paymentAdjustments || {};
	}
	try {
		if (!cargos.length) {
			cargos = JSON.parse(app.getAttribute('data-cargos') || '[]') || [];
		}
	} catch (e) {
		cargos = cargos || [];
	}
	var storeLabel = app.getAttribute('data-store') || 'Mağaza Satış';
	var siteName = (function () {
		var brand = app.querySelector('.pos-brand');
		if (brand) return brand.textContent.trim();
		var logo = app.querySelector('.pos-brand-logo');
		return (logo && logo.getAttribute('alt')) || 'FShop';
	})();

	var state = {
		category: 0,
		query: '',
		page: 1,
		pages: 1,
		cart: { items: [], subtotal: 0, subtotal_formatted: '₺0,00', count: 0, empty: true },
		customer: { id_user: 0, label: 'Ziyaretçi', name: 'Ziyaretçi', phone: '', email: '', addresses: [] },
		payMethod: 'pos_cash',
		saleType: 'store',
		payStep: 1,
		shippingFee: 0,
		cashInput: '',
		lastReceipt: null,
	};

	var audioCtx;
	var els = {};

	function playBeep() {
		try {
			if (!audioCtx) {
				audioCtx = new (window.AudioContext || window.webkitAudioContext)();
			}
			var osc = audioCtx.createOscillator();
			var gain = audioCtx.createGain();
			osc.connect(gain);
			gain.connect(audioCtx.destination);
			osc.frequency.value = 920;
			osc.type = 'sine';
			gain.gain.setValueAtTime(0.12, audioCtx.currentTime);
			gain.gain.exponentialRampToValueAtTime(0.001, audioCtx.currentTime + 0.07);
			osc.start(audioCtx.currentTime);
			osc.stop(audioCtx.currentTime + 0.07);
		} catch (e) { /* sessiz */ }
	}

	function isModalOpen() {
		var modals = [els.payModal, els.customerModal, els.varModal, els.receiptModal, els.searchModal, els.dayEndPinModal, els.dayEndModal];
		for (var i = 0; i < modals.length; i++) {
			if (modals[i] && !modals[i].hidden) return true;
		}
		return false;
	}

	function shouldFocusBarcode() {
		if (isModalOpen()) return false;
		var active = document.activeElement;
		if (active && active !== els.barcode && active !== els.qty && active !== document.body) {
			var tag = active.tagName;
			if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT' || active.isContentEditable) {
				return false;
			}
		}
		return true;
	}

	function initEls() {
		els = {
			qty: document.getElementById('pos-qty'),
			qtyMinus: document.getElementById('pos-qty-minus'),
			qtyPlus: document.getElementById('pos-qty-plus'),
			barcode: document.getElementById('pos-barcode'),
			openSearch: document.getElementById('pos-open-search'),
			searchModal: document.getElementById('pos-search-modal'),
			searchInput: document.getElementById('pos-search-input'),
			searchResults: document.getElementById('pos-search-results'),
			products: document.getElementById('pos-products'),
			productsEmpty: document.getElementById('pos-products-empty'),
			productsLoading: document.getElementById('pos-products-loading'),
			prev: document.getElementById('pos-prev'),
			next: document.getElementById('pos-next'),
			pageInfo: document.getElementById('pos-page-info'),
			cartItems: document.getElementById('pos-cart-items'),
			cartEmpty: document.getElementById('pos-cart-empty'),
			cartTotal: document.getElementById('pos-cart-total'),
			cartLines: document.getElementById('pos-cart-lines'),
			cartBadge: document.getElementById('pos-cart-badge'),
			itemQty: document.getElementById('pos-item-qty'),
			clearCart: document.getElementById('pos-clear-cart'),
			openPay: document.getElementById('pos-open-pay'),
			toast: document.getElementById('pos-toast'),
			clock: document.getElementById('pos-clock'),
			fullscreen: document.getElementById('pos-fullscreen'),
			lockScreen: document.getElementById('pos-lock-screen'),
			lockScreenMobile: document.getElementById('pos-lock-screen-mobile'),
			menuToggle: document.getElementById('pos-menu-toggle'),
			cartToggle: document.getElementById('pos-cart-toggle'),
			cats: document.getElementById('pos-cats'),
			catsClose: document.getElementById('pos-cats-close'),
			cartPanel: document.getElementById('pos-cart'),
			cartClose: document.getElementById('pos-cart-close'),
			drawerBackdrop: document.getElementById('pos-drawer-backdrop'),
			varModal: document.getElementById('pos-var-modal'),
			varTitle: document.getElementById('pos-var-title'),
			varBody: document.getElementById('pos-var-body'),
			payModal: document.getElementById('pos-pay-modal'),
			payStepLabel: document.getElementById('pos-pay-step-label'),
			payTitle: document.getElementById('pos-pay-title'),
			payStep1: document.getElementById('pos-pay-step-1'),
			payStepShip: document.getElementById('pos-pay-step-ship'),
			payStepPay: document.getElementById('pos-pay-step-pay'),
			payBack: document.getElementById('pos-pay-back'),
			shipBack: document.getElementById('pos-ship-back'),
			shipNext: document.getElementById('pos-ship-next'),
			payCustomer: document.getElementById('pos-pay-customer'),
			shipCustomer: document.getElementById('pos-ship-customer'),
			changeCustomerShip: document.getElementById('pos-change-customer-ship'),
			paySubtotal: document.getElementById('pos-pay-subtotal'),
			payShippingRow: document.getElementById('pos-pay-shipping-row'),
			payShippingAmount: document.getElementById('pos-pay-shipping-amount'),
			payAdjustmentRow: document.getElementById('pos-pay-adjustment-row'),
			payAdjustmentLabel: document.getElementById('pos-pay-adjustment-label'),
			payAdjustmentAmount: document.getElementById('pos-pay-adjustment-amount'),
			payTotal: document.getElementById('pos-pay-total'),
			shippingSection: document.getElementById('pos-shipping-section'),
			cargoSelect: document.getElementById('pos-cargo'),
			shipAddressSelect: document.getElementById('pos-ship-address-select'),
			shipCity: document.getElementById('pos-ship-city'),
			shipDistrict: document.getElementById('pos-ship-district'),
			shipAddress: document.getElementById('pos-ship-address'),
			shipPhone: document.getElementById('pos-ship-phone'),
			cashSection: document.getElementById('pos-cash-section'),
			cardSection: document.getElementById('pos-card-section'),
			transferSection: document.getElementById('pos-transfer-section'),
			cashInput: document.getElementById('pos-cash-input'),
			changeRow: document.getElementById('pos-change-row'),
			changeAmount: document.getElementById('pos-change-amount'),
			payReset: document.getElementById('pos-pay-reset'),
			completeSale: document.getElementById('pos-complete-sale'),
			changeCustomer: document.getElementById('pos-change-customer'),
			customerModal: document.getElementById('pos-customer-modal'),
			customerSearch: document.getElementById('pos-customer-search'),
			customerResults: document.getElementById('pos-customer-results'),
			customerVisitor: document.getElementById('pos-customer-visitor'),
			customerCreateToggle: document.getElementById('pos-customer-create-toggle'),
			customerCreateForm: document.getElementById('pos-customer-create-form'),
			customerCreateName: document.getElementById('pos-customer-create-name'),
			customerCreatePhone: document.getElementById('pos-customer-create-phone'),
			customerCreateEmail: document.getElementById('pos-customer-create-email'),
			customerCreateCity: document.getElementById('pos-customer-create-city'),
			customerCreateDistrict: document.getElementById('pos-customer-create-district'),
			customerCreateAddress: document.getElementById('pos-customer-create-address'),
			customerCreateSave: document.getElementById('pos-customer-create-save'),
			exactAmount: document.getElementById('pos-exact-amount'),
			receiptModal: document.getElementById('pos-receipt-modal'),
			receiptContent: document.getElementById('pos-receipt-content'),
			receiptPrint: document.getElementById('pos-receipt-print'),
			printArea: document.getElementById('pos-print-area'),
			dayEndBtn: document.getElementById('pos-day-end'),
			dayEndBtnMobile: document.getElementById('pos-day-end-mobile'),
			dayEndPinModal: document.getElementById('pos-dayend-pin-modal'),
			dayEndPinBoxes: document.getElementById('pos-dayend-pin-boxes'),
			dayEndPinSubmit: document.getElementById('pos-dayend-pin-submit'),
			dayEndModal: document.getElementById('pos-dayend-modal'),
			dayEndContent: document.getElementById('pos-dayend-content'),
			dayEndPrint: document.getElementById('pos-dayend-print'),
			dayEndPrintDetail: document.getElementById('pos-dayend-print-detail'),
			dayEndCloseRegister: document.getElementById('pos-dayend-close-register'),
		};
	}

	function apiUrl(action, params) {
		var url = apiBase + encodeURIComponent(action);
		if (params) {
			var qs = Object.keys(params)
				.filter(function (k) { return params[k] !== undefined && params[k] !== ''; })
				.map(function (k) { return encodeURIComponent(k) + '=' + encodeURIComponent(params[k]); })
				.join('&');
			if (qs) url += (url.indexOf('?') >= 0 ? '&' : '?') + qs;
		}
		return url;
	}

	function request(action, options) {
		options = options || {};
		var init = {
			method: options.method || 'GET',
			headers: { 'X-CSRF-Token': token, 'Accept': 'application/json' },
			credentials: 'same-origin',
		};
		if (options.body) {
			init.headers['Content-Type'] = 'application/x-www-form-urlencoded; charset=UTF-8';
			var body = new URLSearchParams(options.body);
			body.set('token', token);
			init.body = body.toString();
		}
		return fetch(apiUrl(action, options.params), init).then(function (res) {
			return res.json().then(function (data) {
				if (data && data.screen_locked) {
					window.location.reload();
					throw new Error('locked');
				}
				if (data && data.auth_required) {
					window.location.reload();
					throw new Error('auth');
				}
				return data;
			});
		});
	}

	var toastTimer;
	function toast(msg, type) {
		if (!els.toast) return;
		els.toast.textContent = msg;
		els.toast.className = 'pos-toast' + (type ? ' is-' + type : '');
		els.toast.hidden = false;
		clearTimeout(toastTimer);
		toastTimer = setTimeout(function () { els.toast.hidden = true; }, 3200);
	}

	function esc(s) {
		return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
	}

	function escAttr(s) { return esc(s).replace(/'/g, '&#39;'); }

	function parseMoney(str) {
		if (!str) return 0;
		var s = String(str).replace(/\s/g, '').replace(/\./g, '').replace(',', '.');
		var n = parseFloat(s);
		return isNaN(n) ? 0 : n;
	}

	function formatMoney(num) {
		return Number(num || 0).toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
	}

	function formatDisplay(num) {
		return '₺' + formatMoney(num);
	}

	function getScanQty() {
		var n = els.qty ? parseInt(els.qty.value, 10) : 1;
		if (!n || n < 1) n = 1;
		if (n > 999) n = 999;
		if (els.qty) els.qty.value = String(n);
		return n;
	}

	function setScanQty(n) {
		n = parseInt(n, 10);
		if (!n || n < 1) n = 1;
		if (n > 999) n = 999;
		if (els.qty) els.qty.value = String(n);
		return n;
	}

	function bumpScanQty(delta) {
		return setScanQty(getScanQty() + delta);
	}

	function focusBarcode(selectAll) {
		if (!els.barcode || !shouldFocusBarcode()) return;
		try {
			els.barcode.focus({ preventScroll: true });
			if (selectAll) {
				els.barcode.select();
			}
		} catch (e) {
			els.barcode.focus();
		}
	}

	function scheduleFocusBarcode(selectAll) {
		if (document.activeElement === els.barcode && els.barcode.value !== '' && !selectAll) {
			return;
		}
		setTimeout(function () { focusBarcode(!!selectAll); }, 50);
	}

	function closeDrawers() {
		if (els.cats) els.cats.classList.remove('is-open');
		if (els.cartPanel) els.cartPanel.classList.remove('is-open');
		if (els.drawerBackdrop) els.drawerBackdrop.hidden = true;
		document.body.classList.remove('pos-drawer-open');
	}

	function openCatsDrawer() {
		if (els.cartPanel) els.cartPanel.classList.remove('is-open');
		if (els.cats) els.cats.classList.add('is-open');
		if (els.drawerBackdrop) els.drawerBackdrop.hidden = false;
		document.body.classList.add('pos-drawer-open');
	}

	function openCartDrawer() {
		if (els.cats) els.cats.classList.remove('is-open');
		if (els.cartPanel) els.cartPanel.classList.add('is-open');
		if (els.drawerBackdrop) els.drawerBackdrop.hidden = false;
		document.body.classList.add('pos-drawer-open');
	}

	function updateCartBadge() {
		if (!els.cartBadge) return;
		var n = state.cart.count || 0;
		els.cartBadge.textContent = String(n);
		els.cartBadge.hidden = n <= 0;
	}

	function calcCargoFee(idCargo, amount) {
		idCargo = parseInt(idCargo || 0, 10) || 0;
		amount = Math.max(0, parseFloat(amount || 0));
		if (idCargo <= 0 || !cargos.length) return 0;
		var cargo = null;
		for (var i = 0; i < cargos.length; i++) {
			if (parseInt(cargos[i].id_cargo, 10) === idCargo) {
				cargo = cargos[i];
				break;
			}
		}
		if (!cargo || !cargo.rates || !cargo.rates.length) return 0;
		var matched = null;
		cargo.rates.forEach(function (rate) {
			var min = parseFloat(rate.min_amount || 0);
			var max = parseFloat(rate.max_amount || 0);
			if (amount < min) return;
			if (max > 0 && amount > max) return;
			if (!matched || min >= parseFloat(matched.min_amount || 0)) {
				matched = rate;
			}
		});
		return matched ? Math.max(0, parseFloat(matched.fee || 0)) : 0;
	}

	function getShippingFee() {
		if (state.saleType !== 'shipping' || !els.cargoSelect) {
			state.shippingFee = 0;
			return 0;
		}
		var fee = calcCargoFee(els.cargoSelect.value, state.cart.subtotal || 0);
		state.shippingFee = fee;
		return fee;
	}

	/* CODE39 barkod (sipariş no) */
	var CODE39_MAP = {
		'0': 'nnnwwnwnn', '1': 'wnnnwwnnn', '2': 'nnwnwwnnn', '3': 'wnwnnwnnn', '4': 'nnnnwwnnn',
		'5': 'wnnnnwwnn', '6': 'nnwnnwwnn', '7': 'nnnnnwwnn', '8': 'wnwnwnnnn', '9': 'nnwnwnnnn',
		'A': 'wnnnnnnwn', 'B': 'nnwnnnnwn', 'C': 'wnwnnnnwn', 'D': 'nnnnnnwwn', 'E': 'wnnnnnwwn',
		'F': 'nnwnnnwwn', 'G': 'nnnnwnwwn', 'H': 'wnnnwnwwn', 'I': 'nnwnwnwwn', 'J': 'nnnnnnwww',
		'K': 'wnnnnnwww', 'L': 'nnwnnnwww', 'M': 'wnwnnnwww', 'N': 'nnnnwnwww', 'O': 'wnnnwnwww',
		'P': 'nnwnwnwww', 'Q': 'nnnnnnnww', 'R': 'wnnnnnnww', 'S': 'nnwnnnnww', 'T': 'wnwnnnnww',
		'U': 'nnnnwnnww', 'V': 'wnnnwnnww', 'W': 'nnwnwnnww', 'X': 'nnnnnnwww', 'Y': 'wnnnnnwww',
		'Z': 'nnwnnnwww', '-': 'wnwnnnwww', '.': 'nnnnwnwww', ' ': 'wnnnwnwww', '$': 'nnwnwnwww',
		'/': 'nnnnnnnww', '+': 'wnnnnnnww', '%': 'nnwnnnnww', '*': 'nwnnnwnnn'
	};

	function formatPercent(num) {
		var s = Number(num || 0).toFixed(2).replace(/\.?0+$/, '');
		return s;
	}

	function calculatePayable(subtotal, method) {
		subtotal = Math.max(0, parseFloat(subtotal || 0));
		var rule = paymentAdjustments[method] || { type: 'none', percent: 0 };
		var type = String(rule.type || 'none').toLowerCase();
		var percent = Math.max(0, Math.min(100, parseFloat(rule.percent || 0)));
		var adjustment = 0;
		var label = '';

		if (percent > 0 && type === 'discount') {
			adjustment = -Math.round((subtotal * percent / 100) * 100) / 100;
			label = method === 'pos_card' ? 'Kart indirimi' : 'Havale indirimi';
			label += ' (%' + formatPercent(percent) + ')';
		} else if (percent > 0 && type === 'commission') {
			adjustment = Math.round((subtotal * percent / 100) * 100) / 100;
			label = method === 'pos_card' ? 'Kart komisyonu' : 'Havale komisyonu';
			label += ' (%' + formatPercent(percent) + ')';
		}

		var total = Math.max(0, Math.round((subtotal + adjustment) * 100) / 100);
		var sign = adjustment >= 0 ? '+' : '−';

		return {
			subtotal: subtotal,
			adjustment: adjustment,
			label: label,
			total: total,
			adjustmentDisplay: Math.abs(adjustment) > 0.009 ? sign + formatDisplay(Math.abs(adjustment)) : '',
		};
	}

	function getPayableTotal() {
		var payable = calculatePayable(state.cart.subtotal || 0, state.payMethod);
		var ship = state.saleType === 'shipping' ? getShippingFee() : 0;
		state.shippingFee = ship;
		return Math.round((payable.total + ship) * 100) / 100;
	}

	function updatePayUi() {
		var payable = calculatePayable(state.cart.subtotal || 0, state.payMethod);
		var ship = state.saleType === 'shipping' ? getShippingFee() : 0;
		state.shippingFee = ship;
		var total = Math.round((payable.total + ship) * 100) / 100;

		if (els.paySubtotal) els.paySubtotal.textContent = formatDisplay(payable.subtotal);
		if (els.payTotal) els.payTotal.textContent = formatDisplay(total);

		if (els.payShippingRow) {
			els.payShippingRow.hidden = state.saleType !== 'shipping';
			if (els.payShippingAmount && state.saleType === 'shipping') {
				els.payShippingAmount.textContent = formatDisplay(ship);
			}
		}

		if (els.payAdjustmentRow) {
			var showAdj = Math.abs(payable.adjustment) > 0.009;
			els.payAdjustmentRow.hidden = !showAdj;
			if (els.payAdjustmentLabel) els.payAdjustmentLabel.textContent = payable.label;
			if (els.payAdjustmentAmount) els.payAdjustmentAmount.textContent = payable.adjustmentDisplay;
		}

		updateCashUi();
	}

	function code39Svg(text) {
		text = String(text || '').toUpperCase();
		var narrow = 2;
		var wide = 5;
		var x = 0;
		var bars = [];

		function drawPattern(pattern) {
			if (!pattern) return;
			for (var i = 0; i < pattern.length; i++) {
				var w = pattern.charAt(i) === 'w' ? wide : narrow;
				if (i % 2 === 0) {
					bars.push('<rect x="' + x + '" y="0" width="' + w + '" height="50" fill="#000"/>');
				}
				x += w;
			}
			x += narrow;
		}

		drawPattern(CODE39_MAP['*']);
		for (var c = 0; c < text.length; c++) {
			drawPattern(CODE39_MAP[text.charAt(c)]);
		}
		drawPattern(CODE39_MAP['*']);

		return '<svg xmlns="http://www.w3.org/2000/svg" width="' + x + '" height="50" viewBox="0 0 ' + x + ' 50">' + bars.join('') + '</svg>';
	}

	function renderReceiptHtml(receipt) {
		if (!receipt) return '';

		var itemsHtml = (receipt.items || []).map(function (item) {
			var varLine = item.variation_label
				? '<div class="pos-receipt__item-var">' + esc(item.variation_label) + '</div>'
				: '';
			return '<div class="pos-receipt__item">' +
				'<div class="pos-receipt__item-name">' + esc(item.product_name) + '</div>' +
				varLine +
				'<div class="pos-receipt__item-qty">' + esc(String(item.qty)) + ' x ' + esc(item.price_formatted || '') + '</div>' +
				'<div class="pos-receipt__item-total">' + esc(item.line_total_formatted || '') + '</div>' +
				'</div>';
		}).join('');

		var cashLines = '';
		if (receipt.cash_paid_formatted) {
			cashLines += '<div class="pos-receipt__line"><span>Alınan</span><strong>' + esc(receipt.cash_paid_formatted) + '</strong></div>';
		}
		if (receipt.change_formatted) {
			cashLines += '<div class="pos-receipt__line"><span>Para üstü</span><strong>' + esc(receipt.change_formatted) + '</strong></div>';
		}

		var phoneLine = receipt.customer_phone
			? '<div class="pos-receipt__line"><span>Telefon</span><span>' + esc(receipt.customer_phone) + '</span></div>'
			: '';

		var adjustmentLine = '';
		if (Math.abs(parseFloat(receipt.adjustment || 0)) > 0.009 && receipt.adjustment_label) {
			adjustmentLine = '<div class="pos-receipt__line"><span>' + esc(receipt.adjustment_label) + '</span><strong>' +
				esc(receipt.adjustment_signed_formatted || receipt.adjustment_formatted || '') + '</strong></div>';
		}

		return '<div class="pos-receipt__head">' +
			'<p class="pos-receipt__store">' + esc(receipt.site_name || siteName) + '</p>' +
			'<p class="pos-receipt__meta">' + esc(receipt.store_label || storeLabel) + '</p>' +
			'<p class="pos-receipt__meta">' + esc(receipt.date || '') + ' · Kasiyer: ' + esc(receipt.cashier || '') + '</p>' +
			'</div>' +
			'<div class="pos-receipt__barcode">' + code39Svg(receipt.reference || '') +
			'<div class="pos-receipt__ref">' + esc(receipt.reference || '') + '</div></div>' +
			'<div class="pos-receipt__line"><span>Alıcı</span><strong>' + esc(receipt.customer_name || 'Ziyaretçi') + '</strong></div>' +
			phoneLine +
			'<div class="pos-receipt__line"><span>Ödeme</span><strong>' + esc(receipt.payment_label || '') + '</strong></div>' +
			'<div class="pos-receipt__divider"></div>' +
			'<div class="pos-receipt__items">' + itemsHtml + '</div>' +
			'<div class="pos-receipt__divider"></div>' +
			'<div class="pos-receipt__line"><span>Ürün adedi</span><span>' + esc(String(receipt.item_count || 0)) + '</span></div>' +
			'<div class="pos-receipt__line"><span>Ara toplam</span><span>' + esc(receipt.subtotal_formatted || '') + '</span></div>' +
			adjustmentLine +
			cashLines +
			'<div class="pos-receipt__total"><span>TOPLAM</span><span>' + esc(receipt.total_formatted || receipt.subtotal_formatted || '') + '</span></div>' +
			'<div class="pos-receipt__foot">Teşekkür ederiz · İyi günler dileriz</div>';
	}

	function openReceiptModal(receipt) {
		if (!receipt || !els.receiptModal) return;
		state.lastReceipt = receipt;
		if (els.receiptContent) els.receiptContent.innerHTML = renderReceiptHtml(receipt);
		els.receiptModal.hidden = false;
	}

	function closeReceiptModal() {
		if (els.receiptModal) els.receiptModal.hidden = true;
		scheduleFocusBarcode();
	}

	function printReceipt() {
		if (!els.receiptContent) return;

		var printRoot = els.printArea || document.getElementById('pos-print-area');
		if (!printRoot) {
			window.print();
			return;
		}

		printRoot.innerHTML = '<div class="pos-receipt">' + els.receiptContent.innerHTML + '</div>';
		printRoot.setAttribute('aria-hidden', 'false');

		window.print();

		setTimeout(function () {
			printRoot.innerHTML = '';
			printRoot.setAttribute('aria-hidden', 'true');
		}, 300);
	}

	function loadReceiptByReference(reference) {
		if (!reference) return;
		request('receipt', { params: { reference: reference } }).then(function (data) {
			if (data.success && data.receipt) {
				openReceiptModal(data.receipt);
			}
		});
	}

	function updateClock() {
		if (!els.clock) return;
		els.clock.textContent = new Date().toLocaleString('tr-TR', {
			weekday: 'long', day: 'numeric', month: 'long', year: 'numeric',
			hour: '2-digit', minute: '2-digit',
		});
	}

	var lastDayEndStats = null;
	var lastManagerPin = '';

	function getDayEndPinBoxes() {
		return els.dayEndPinBoxes
			? Array.prototype.slice.call(els.dayEndPinBoxes.querySelectorAll('.pos-pin-box'))
			: [];
	}

	function getDayEndPinValue() {
		return getDayEndPinBoxes().map(function (box) {
			return String(box.value || '').replace(/\D/g, '').charAt(0) || '';
		}).join('');
	}

	function clearDayEndPinBoxes() {
		getDayEndPinBoxes().forEach(function (box) { box.value = ''; });
	}

	function focusDayEndPinBox(idx) {
		var boxes = getDayEndPinBoxes();
		if (!boxes.length) return;
		idx = Math.max(0, Math.min(boxes.length - 1, idx));
		try {
			boxes[idx].focus();
			boxes[idx].select();
		} catch (e) { /* ignore */ }
	}

	function bindDayEndPinBoxes() {
		var boxes = getDayEndPinBoxes();
		boxes.forEach(function (box, idx) {
			box.addEventListener('input', function () {
				var digit = String(box.value || '').replace(/\D/g, '').slice(-1);
				box.value = digit;
				if (digit && idx < boxes.length - 1) {
					focusDayEndPinBox(idx + 1);
				} else if (digit && idx === boxes.length - 1 && getDayEndPinValue().length === 6) {
					submitDayEndPin();
				}
			});
			box.addEventListener('keydown', function (e) {
				if (e.key === 'Backspace' && !box.value && idx > 0) {
					e.preventDefault();
					boxes[idx - 1].value = '';
					focusDayEndPinBox(idx - 1);
				} else if (e.key === 'ArrowLeft' && idx > 0) {
					e.preventDefault();
					focusDayEndPinBox(idx - 1);
				} else if (e.key === 'ArrowRight' && idx < boxes.length - 1) {
					e.preventDefault();
					focusDayEndPinBox(idx + 1);
				} else if (e.key === 'Enter') {
					e.preventDefault();
					submitDayEndPin();
				}
			});
			box.addEventListener('paste', function (e) {
				e.preventDefault();
				var text = ((e.clipboardData || window.clipboardData).getData('text') || '').replace(/\D/g, '').slice(0, 6);
				if (!text) return;
				for (var i = 0; i < boxes.length; i++) {
					boxes[i].value = text.charAt(i) || '';
				}
				if (text.length >= 6) {
					submitDayEndPin();
				} else {
					focusDayEndPinBox(text.length);
				}
			});
		});
	}

	function renderDayEndHtml(stats) {
		if (!stats) return '';
		var cash = stats.cash || {};
		var card = stats.card || {};
		var transfer = stats.transfer || {};
		var pending = stats.transfer_pending || {};

		return '<div class="pos-dayend__meta">' +
			'<div><strong>' + esc(stats.site_name || siteName) + '</strong> · ' + esc(stats.store_label || storeLabel) + '</div>' +
			'<div>Dönem: ' + esc(stats.register_since_formatted || '') + ' → ' + esc(stats.generated_at || '') + '</div>' +
			'<div>Kasiyer: ' + esc(stats.cashier || '') + '</div>' +
			'</div>' +
			'<div class="pos-dayend__row"><span>Nakit <span class="pos-dayend__count">(' + esc(String(cash.count || 0)) + ' adet)</span></span><strong>' + esc(cash.total_formatted || '₺0,00') + '</strong></div>' +
			'<div class="pos-dayend__row"><span>Kredi Kartı / POS <span class="pos-dayend__count">(' + esc(String(card.count || 0)) + ' adet)</span></span><strong>' + esc(card.total_formatted || '₺0,00') + '</strong></div>' +
			'<div class="pos-dayend__row"><span>Havale <span class="pos-dayend__count">(' + esc(String(transfer.count || 0)) + ' adet)</span></span><strong>' + esc(transfer.total_formatted || '₺0,00') + '</strong></div>' +
			(pending.count ? '<div class="pos-dayend__row"><span>↳ Havale bekleyen <span class="pos-dayend__count">(' + esc(String(pending.count || 0)) + ')</span></span><strong>' + esc(pending.total_formatted || '₺0,00') + '</strong></div>' : '') +
			'<div class="pos-dayend__row pos-dayend__row--total"><span>Toplam satış <span class="pos-dayend__count">(' + esc(String(stats.sale_count || 0)) + ' adet)</span></span><strong>' + esc(stats.grand_total_formatted || '₺0,00') + '</strong></div>';
	}

	function renderDayEndDetailHtml(stats) {
		var summary = renderDayEndHtml(stats);
		var orders = (stats && stats.orders) || [];
		if (!orders.length) {
			return summary + '<p class="pos-pay-note" style="margin-top:16px">Bu dönemde sipariş yok.</p>';
		}

		var ordersHtml = orders.map(function (order) {
			var items = (order.items || []).map(function (item) {
				var name = esc(item.product_name || '');
				if (item.variation_label) {
					name += '<div class="pos-dayend-order__item-var">' + esc(item.variation_label) + '</div>';
				}
				return '<div class="pos-dayend-order__item"><div>' + esc(String(item.qty || 0)) + '× ' + name + '</div><strong>' + esc(item.total_formatted || '') + '</strong></div>';
			}).join('');

			var addressLine = order.address
				? '<div>Adres: ' + esc(order.address) + '</div>'
				: '';

			return '<div class="pos-dayend-order">' +
				'<div class="pos-dayend-order__head"><span>' + esc(order.reference || '') + '</span><span>' + esc(order.total_formatted || '') + '</span></div>' +
				'<div class="pos-dayend-order__meta">' +
				'<div>' + esc(order.date || '') + ' · ' + esc(order.sale_type_label || '') + ' · ' + esc(order.payment_label || '') + '</div>' +
				'<div>Müşteri: ' + esc(order.customer_name || 'Ziyaretçi') +
				(order.customer_phone ? ' · ' + esc(order.customer_phone) : '') + '</div>' +
				addressLine +
				'</div>' +
				items +
				(order.shipping > 0 ? '<div class="pos-dayend-order__item"><span>Kargo</span><strong>' + esc(order.shipping_formatted || '') + '</strong></div>' : '') +
				'<div class="pos-dayend-order__total"><span>Sipariş toplamı</span><span>' + esc(order.total_formatted || '') + '</span></div>' +
				'</div>';
		}).join('');

		return summary + '<h4 style="margin:18px 0 8px;font-size:1rem">Sipariş detayları</h4>' + ordersHtml;
	}

	function openDayEndPinModal() {
		closeDrawers();
		lastDayEndStats = null;
		lastManagerPin = '';
		clearDayEndPinBoxes();
		if (els.dayEndPinModal) els.dayEndPinModal.hidden = false;
		setTimeout(function () { focusDayEndPinBox(0); }, 50);
	}

	function closeDayEndPinModal() {
		if (els.dayEndPinModal) els.dayEndPinModal.hidden = true;
		scheduleFocusBarcode(false);
	}

	function submitDayEndPin() {
		var pin = getDayEndPinValue();
		if (!/^\d{6}$/.test(pin)) {
			toast('6 haneli yönetici şifresi girin', 'error');
			focusDayEndPinBox(0);
			return;
		}
		if (els.dayEndPinSubmit) els.dayEndPinSubmit.disabled = true;
		request('day-end', { method: 'POST', body: { manager_pin: pin } }).then(function (data) {
			if (els.dayEndPinSubmit) els.dayEndPinSubmit.disabled = false;
			if (!data.success) {
				toast(data.message || 'Şifre hatalı', 'error');
				clearDayEndPinBoxes();
				focusDayEndPinBox(0);
				return;
			}
			lastManagerPin = pin;
			lastDayEndStats = data.stats || null;
			closeDayEndPinModal();
			openDayEndModal(lastDayEndStats);
		}).catch(function () {
			if (els.dayEndPinSubmit) els.dayEndPinSubmit.disabled = false;
			toast('Bağlantı hatası', 'error');
		});
	}

	function openDayEndModal(stats) {
		if (els.dayEndContent) els.dayEndContent.innerHTML = renderDayEndHtml(stats);
		if (els.dayEndModal) els.dayEndModal.hidden = false;
	}

	function closeDayEndModal() {
		if (els.dayEndModal) els.dayEndModal.hidden = true;
		scheduleFocusBarcode(false);
	}

	function printDayEndReport(detailed) {
		if (!lastDayEndStats) {
			toast('Önce raporu açın', 'error');
			return;
		}
		var html = detailed ? renderDayEndDetailHtml(lastDayEndStats) : renderDayEndHtml(lastDayEndStats);
		var title = detailed ? 'Gün Sonu Detaylı Rapor' : 'Gün Sonu Raporu';
		var printRoot = els.printArea || document.getElementById('pos-print-area');
		if (!printRoot) {
			window.print();
			return;
		}
		printRoot.innerHTML = '<div class="pos-receipt"><h3 style="text-align:center;margin:0 0 12px">' + esc(title) + '</h3>' + html + '</div>';
		printRoot.setAttribute('aria-hidden', 'false');
		window.print();
		setTimeout(function () {
			printRoot.innerHTML = '';
			printRoot.setAttribute('aria-hidden', 'true');
		}, 300);
	}

	function printDayEnd() {
		printDayEndReport(false);
	}

	function printDayEndDetail() {
		printDayEndReport(true);
	}

	function closeRegister() {
		if (!lastManagerPin) {
			toast('Yönetici şifresi gerekli', 'error');
			return;
		}
		if (!confirm('Kasayı kapatmak istiyor musunuz? Sonraki satışlar sıfırdan sayılır.')) return;
		if (els.dayEndCloseRegister) els.dayEndCloseRegister.disabled = true;
		request('close-register', { method: 'POST', body: { manager_pin: lastManagerPin } }).then(function (data) {
			if (els.dayEndCloseRegister) els.dayEndCloseRegister.disabled = false;
			if (!data.success) {
				toast(data.message || 'Kapatılamadı', 'error');
				return;
			}
			toast(data.message || 'Kasa kapatıldı', 'success');
			lastDayEndStats = data.stats || null;
			lastManagerPin = '';
			closeDayEndModal();
		}).catch(function () {
			if (els.dayEndCloseRegister) els.dayEndCloseRegister.disabled = false;
		});
	}

	function loadCustomer() {
		request('customer').then(function (data) {
			if (data.success && data.customer) {
				state.customer = data.customer;
				updateCustomerUi();
			}
		});
	}

	function updateCustomerUi() {
		var label = state.customer.label || state.customer.name || 'Ziyaretçi';
		if (els.payCustomer) els.payCustomer.textContent = label;
		if (els.shipCustomer) els.shipCustomer.textContent = label;
		fillAddressSelect();
		if (els.shipPhone && (!els.shipPhone.value || state.customer.phone)) {
			els.shipPhone.value = state.customer.phone || els.shipPhone.value || '';
		}
	}

	function fillAddressSelect() {
		if (!els.shipAddressSelect) return;
		var addresses = state.customer.addresses || [];
		els.shipAddressSelect.innerHTML = '<option value="0">Yeni adres gir</option>';
		addresses.forEach(function (a) {
			var opt = document.createElement('option');
			opt.value = String(a.id_address || 0);
			opt.textContent = (a.label ? a.label + ' — ' : '') + (a.summary || (a.city + ' / ' + a.district));
			opt.setAttribute('data-city', a.city || '');
			opt.setAttribute('data-district', a.district || '');
			opt.setAttribute('data-address', a.address_text || '');
			opt.setAttribute('data-phone', a.phone || '');
			els.shipAddressSelect.appendChild(opt);
		});
		var def = addresses.find(function (a) { return a.is_default; }) || addresses[0];
		if (def && state.saleType === 'shipping') {
			els.shipAddressSelect.value = String(def.id_address);
			applySelectedAddress();
		}
	}

	function applySelectedAddress() {
		if (!els.shipAddressSelect) return;
		var opt = els.shipAddressSelect.options[els.shipAddressSelect.selectedIndex];
		if (!opt || opt.value === '0') return;
		if (els.shipCity) els.shipCity.value = opt.getAttribute('data-city') || '';
		if (els.shipDistrict) els.shipDistrict.value = opt.getAttribute('data-district') || '';
		if (els.shipAddress) els.shipAddress.value = opt.getAttribute('data-address') || '';
		var phone = opt.getAttribute('data-phone') || '';
		if (phone && els.shipPhone) els.shipPhone.value = phone;
	}

	function getPayStepTotal() {
		return state.saleType === 'shipping' ? 3 : 2;
	}

	function setSaleType(type, advance) {
		state.saleType = type === 'shipping' ? 'shipping' : 'store';
		document.querySelectorAll('.pos-sale-type').forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-sale-type') === state.saleType);
		});
		if (state.saleType === 'shipping') {
			fillAddressSelect();
			if (els.shipPhone && !els.shipPhone.value) {
				els.shipPhone.value = state.customer.phone || '';
			}
		}
		updatePayUi();
		if (advance) {
			if (state.saleType === 'shipping') {
				showPayStep('ship');
			} else {
				showPayStep('pay');
			}
		}
	}

	function showPayStep(step) {
		var total = getPayStepTotal();
		var current = 1;
		var title = 'Satış tipi';

		if (step === 'ship') {
			state.payStep = 'ship';
			current = 2;
			title = 'Müşteri & kargo';
		} else if (step === 'pay') {
			state.payStep = 'pay';
			current = state.saleType === 'shipping' ? 3 : 2;
			title = 'Ödeme';
		} else {
			state.payStep = 1;
			current = 1;
			title = 'Satış tipi';
		}

		if (els.payStep1) els.payStep1.hidden = state.payStep !== 1;
		if (els.payStepShip) els.payStepShip.hidden = state.payStep !== 'ship';
		if (els.payStepPay) els.payStepPay.hidden = state.payStep !== 'pay';
		if (els.payStepLabel) els.payStepLabel.textContent = 'Adım ' + current + ' / ' + total;
		if (els.payTitle) els.payTitle.textContent = title;

		if (state.payStep === 'ship' || state.payStep === 'pay') {
			updateCustomerUi();
			updatePayUi();
		}
	}

	function validateShippingStep() {
		if (!(state.customer.id_user > 0)) {
			toast('Kargo satışı için müşteri seçin veya ekleyin', 'error');
			openCustomerModal();
			return false;
		}
		var idCargo = els.cargoSelect ? parseInt(els.cargoSelect.value || '0', 10) : 0;
		var city = els.shipCity ? els.shipCity.value.trim() : '';
		var district = els.shipDistrict ? els.shipDistrict.value.trim() : '';
		var address = els.shipAddress ? els.shipAddress.value.trim() : '';
		var phone = els.shipPhone ? els.shipPhone.value.trim() : '';

		if (idCargo <= 0) {
			toast('Kargo firması seçin', 'error');
			return false;
		}
		if (!city || !district || !address) {
			toast('İl, ilçe ve adres gerekli', 'error');
			return false;
		}
		if (!phone) {
			toast('Telefon gerekli', 'error');
			return false;
		}
		return true;
	}

	function setCustomer(idUser) {
		request('customer', {
			method: 'POST',
			body: { customer_op: 'set', id_user: idUser },
		}).then(function (data) {
			if (!data.success) {
				toast(data.message || 'Hata', 'error');
				return;
			}
			state.customer = data.customer;
			updateCustomerUi();
			closeCustomerModal();
			toast('Müşteri seçildi', 'success');
		});
	}

	function resetCustomer() {
		request('customer', { method: 'POST', body: { customer_op: 'reset' } }).then(function (data) {
			if (data.success) {
				state.customer = data.customer;
				updateCustomerUi();
				closeCustomerModal();
				toast('Ziyaretçi seçildi', 'success');
			}
		});
	}

	function searchCustomers(q) {
		if (!els.customerResults) return;
		if (q.length < 2) {
			els.customerResults.innerHTML = '<p style="color:#64748b;font-size:.88rem">En az 2 karakter yazın</p>';
			return;
		}
		request('customers', { params: { q: q } }).then(function (data) {
			if (!data.success || !data.customers.length) {
				els.customerResults.innerHTML = '<p style="color:#64748b;font-size:.88rem">Müşteri bulunamadı</p>';
				return;
			}
			els.customerResults.innerHTML = '';
			data.customers.forEach(function (c) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'pos-customer-item';
				btn.innerHTML = '<strong>' + esc(c.name) + '</strong><span>' + esc(c.phone || c.email || '') + '</span>';
				btn.addEventListener('click', function () { setCustomer(c.id_user); });
				els.customerResults.appendChild(btn);
			});
		});
	}

	function setLoading(on) {
		if (els.productsLoading) els.productsLoading.hidden = !on;
	}

	function renderProducts(list) {
		if (!els.products) return;
		els.products.innerHTML = '';
		if (!list || !list.length) {
			if (els.productsEmpty) els.productsEmpty.hidden = false;
			return;
		}
		if (els.productsEmpty) els.productsEmpty.hidden = true;
		list.forEach(function (p) {
			var canSell = allowOutOfStockSale || p.in_stock || p.has_variations;
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'pos-product' + (canSell ? '' : ' is-out');
			btn.innerHTML =
				'<img class="pos-product__img" src="' + escAttr(p.image_url) + '" alt="">' +
				'<div class="pos-product__meta">' +
				'<p class="pos-product__name">' + esc(p.product_name) + '</p>' +
				'<div class="pos-product__price">' + esc(p.price_formatted) + '</div>' +
				(!p.in_stock && !p.has_variations && allowOutOfStockSale ? '<div class="pos-product__oos">Stok yok</div>' : '') +
				'</div>';
			btn.addEventListener('click', function () { onProductClick(p); });
			els.products.appendChild(btn);
		});
	}

	function renderCart() {
		var cart = state.cart;
		var items = cart.items || [];
		var lineCount = items.length;
		var qtyTotal = cart.count || items.reduce(function (s, i) { return s + i.qty; }, 0);

		if (els.cartTotal) els.cartTotal.textContent = formatDisplay(cart.subtotal || 0);
		if (els.cartLines) els.cartLines.textContent = String(lineCount);
		if (els.itemQty) els.itemQty.textContent = String(qtyTotal);
		if (els.openPay) els.openPay.disabled = !!cart.empty;
		updateCartBadge();
		if (els.payModal && !els.payModal.hidden) {
			updatePayUi();
		}

		if (!items.length) {
			if (els.cartEmpty) els.cartEmpty.hidden = false;
			if (els.cartItems) els.cartItems.innerHTML = '';
			return;
		}
		if (els.cartEmpty) els.cartEmpty.hidden = true;
		if (!els.cartItems) return;

		els.cartItems.innerHTML = '';
		items.forEach(function (item) {
			var row = document.createElement('div');
			row.className = 'pos-cart-line';
			row.innerHTML =
				'<div><div class="pos-cart-line__name">' + esc(item.product_name) + '</div>' +
				(item.variation_label ? '<div class="pos-cart-line__meta">' + esc(item.variation_label) + '</div>' : '') +
				'<div class="pos-cart-line__price">' + esc(item.line_total_formatted || item.price_formatted) + '</div></div>';

			var ctrl = document.createElement('div');
			ctrl.className = 'pos-cart-line__ctrl';
			var minus = document.createElement('button');
			minus.type = 'button';
			minus.className = 'pos-mini-btn';
			minus.textContent = '−';
			minus.addEventListener('click', function () { updateQty(item.key, item.qty - 1); });
			var qty = document.createElement('span');
			qty.className = 'pos-cart-line__qty';
			qty.textContent = String(item.qty);
			var plus = document.createElement('button');
			plus.type = 'button';
			plus.className = 'pos-mini-btn';
			plus.textContent = '+';
			plus.addEventListener('click', function () { updateQty(item.key, item.qty + 1); });
			ctrl.appendChild(minus);
			ctrl.appendChild(qty);
			ctrl.appendChild(plus);
			row.appendChild(ctrl);
			els.cartItems.appendChild(row);
		});
	}

	function loadProducts() {
		setLoading(true);
		var params = { page: state.page, limit: 24 };
		if (state.query.length >= 2) params.q = state.query;
		else if (state.category > 0) params.id_category = state.category;

		request('products', { params: params }).then(function (data) {
			setLoading(false);
			if (!data.success) { toast(data.message || 'Hata', 'error'); return; }
			if (data.stock_rules) {
				hideOutOfStock = !!data.stock_rules.hide_out_of_stock;
				allowOutOfStockSale = !!data.stock_rules.allow_out_of_stock_sale;
			}
			state.pages = Math.max(1, (data.pagination && data.pagination.pages) || 1);
			if (els.pageInfo) els.pageInfo.textContent = state.page + ' / ' + state.pages;
			if (els.prev) els.prev.disabled = state.page <= 1;
			if (els.next) els.next.disabled = state.page >= state.pages;
			renderProducts(data.products || []);
			scheduleFocusBarcode();
		}).catch(function () { setLoading(false); scheduleFocusBarcode(); });
	}

	function loadCart() {
		request('cart', { method: 'POST', body: { cart_op: 'get' } }).then(function (data) {
			if (data.success && data.cart) {
				state.cart = data.cart;
				renderCart();
			}
		});
	}

	function onProductClick(p) {
		if (!allowOutOfStockSale && !p.in_stock && !p.has_variations) {
			toast('Stokta yok', 'error');
			scheduleFocusBarcode();
			return;
		}
		if (p.has_variations) { openVariationModal(p.id_product); return; }
		addToCart(p.id_product, getScanQty(), 0);
	}

	function openVariationModal(idProduct) {
		request('product', { params: { id_product: idProduct } }).then(function (data) {
			if (!data.success || !data.product) { toast(data.message || 'Hata', 'error'); return; }
			var product = data.product;
			if (els.varTitle) els.varTitle.textContent = product.product_name;
			if (els.varBody) els.varBody.innerHTML = '';
			(product.variations || []).forEach(function (v) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'pos-var-item';
				btn.disabled = !allowOutOfStockSale && !v.in_stock;
				btn.innerHTML = '<span>' + esc(v.label || 'Varyasyon') + '</span><strong>' + esc(v.price_formatted) + '</strong>' +
					(!v.in_stock && allowOutOfStockSale ? ' <em class="pos-var-oos">(stok yok)</em>' : '');
				btn.addEventListener('click', function () {
					closeVarModal();
					addToCart(product.id_product, getScanQty(), v.id_variation);
				});
				if (els.varBody) els.varBody.appendChild(btn);
			});
			if (els.varModal) els.varModal.hidden = false;
		});
	}

	function addToCart(idProduct, qty, idVariation) {
		request('cart', {
			method: 'POST',
			body: { cart_op: 'add', id_product: idProduct, qty: qty, id_variation: idVariation || 0 },
		}).then(function (data) {
			if (!data.success) {
				if (data.needs_variation) { openVariationModal(idProduct); return; }
				toast(data.message || 'Eklenemedi', 'error');
				scheduleFocusBarcode();
				return;
			}
			state.cart = data.cart;
			renderCart();
			playBeep();
			toast('Sepete eklendi', 'success');
			scheduleFocusBarcode();
		});
	}

	function scanBarcode(code) {
		code = String(code || '').trim();
		if (!code) return;
		var qty = getScanQty();
		request('barcode', { method: 'POST', body: { barcode: code, qty: qty } }).then(function (data) {
			if (els.barcode) els.barcode.value = '';
			if (!data.success) {
				toast(data.message || 'Barkod bulunamadı', 'error');
				scheduleFocusBarcode(false);
				return;
			}
			state.cart = data.cart;
			renderCart();
			playBeep();
			toast('Okutuldu — sepete eklendi', 'success');
			scheduleFocusBarcode(false);
		}).catch(function () {
			if (els.barcode) els.barcode.value = '';
			scheduleFocusBarcode(false);
		});
	}

	function openSearchModal() {
		if (els.searchModal) els.searchModal.hidden = false;
		if (els.searchInput) {
			els.searchInput.value = '';
			els.searchInput.focus();
		}
		if (els.searchResults) els.searchResults.innerHTML = '';
	}

	function closeSearchModal() {
		if (els.searchModal) els.searchModal.hidden = true;
		scheduleFocusBarcode(false);
	}

	function searchProducts(q) {
		if (!els.searchResults) return;
		q = String(q || '').trim();
		if (q.length < 2) {
			els.searchResults.innerHTML = '<p class="pos-pay-note">En az 2 karakter yazın</p>';
			return;
		}
		request('products', { params: { q: q, page: 1, limit: 20 } }).then(function (data) {
			if (!data.success || !(data.products || []).length) {
				els.searchResults.innerHTML = '<p class="pos-pay-note">Ürün bulunamadı</p>';
				return;
			}
			els.searchResults.innerHTML = '';
			(data.products || []).forEach(function (p) {
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'pos-search-item';
				btn.innerHTML = '<strong>' + esc(p.product_name) + '</strong><span>' + esc(p.price_formatted || '') + '</span>';
				btn.addEventListener('click', function () {
					closeSearchModal();
					onProductClick(p);
				});
				els.searchResults.appendChild(btn);
			});
		});
	}

	function updateQty(key, qty) {
		request('cart', { method: 'POST', body: { cart_op: 'update', key: key, qty: qty } }).then(function (data) {
			if (!data.success) { toast(data.message || 'Hata', 'error'); scheduleFocusBarcode(); return; }
			state.cart = data.cart;
			renderCart();
			scheduleFocusBarcode();
		});
	}

	function clearCart() {
		if (!confirm('Sepeti temizlemek istiyor musunuz?')) { scheduleFocusBarcode(); return; }
		request('cart', { method: 'POST', body: { cart_op: 'clear' } }).then(function (data) {
			if (data.success) { state.cart = data.cart; renderCart(); toast('Sepet temizlendi', 'success'); }
			scheduleFocusBarcode();
		});
	}

	function setPayMethod(method) {
		state.payMethod = method;
		document.querySelectorAll('.pos-pay-method').forEach(function (btn) {
			btn.classList.toggle('is-active', btn.getAttribute('data-method') === method);
		});
		if (els.cashSection) els.cashSection.hidden = method !== 'pos_cash';
		if (els.cardSection) els.cardSection.hidden = method !== 'pos_card';
		if (els.transferSection) els.transferSection.hidden = method !== 'pos_transfer';
		if (els.completeSale) {
			els.completeSale.textContent = method === 'pos_card' ? 'POS Tahsilatı Tamamla' : 'Siparişi Tamamla';
		}
		updatePayUi();
	}

	function updateCashUi() {
		var paid = parseMoney(state.cashInput);
		var total = getPayableTotal();
		if (els.cashInput) els.cashInput.value = state.cashInput;

		if (els.changeAmount) {
			var change = Math.max(0, paid - total);
			els.changeAmount.textContent = formatDisplay(change);
			if (paid > 0 && change >= 0 && paid >= total) {
				els.changeAmount.style.color = '#16a34a';
			} else if (paid > 0 && paid < total) {
				els.changeAmount.style.color = '#ef4444';
				els.changeAmount.textContent = formatDisplay(paid - total);
			}
		}
	}

	function resetPayForm() {
		state.cashInput = '';
		if (els.cashInput) els.cashInput.value = '';
		setPayMethod('pos_cash');
		state.saleType = 'store';
		document.querySelectorAll('.pos-sale-type').forEach(function (btn) {
			btn.classList.remove('is-active');
		});
		if (els.cargoSelect) els.cargoSelect.value = '0';
		if (els.shipCity) els.shipCity.value = '';
		if (els.shipDistrict) els.shipDistrict.value = '';
		if (els.shipAddress) els.shipAddress.value = '';
		if (els.shipPhone) els.shipPhone.value = state.customer.phone || '';
		showPayStep(1);
		updateCashUi();
	}

	function openPayModal() {
		if (state.cart.empty) { toast('Sepet boş', 'error'); return; }
		updateCustomerUi();
		resetPayForm();
		if (els.payModal) els.payModal.hidden = false;
	}

	function closePayModal() {
		if (els.payModal) els.payModal.hidden = true;
		scheduleFocusBarcode();
	}
	function closeVarModal() {
		if (els.varModal) els.varModal.hidden = true;
		scheduleFocusBarcode();
	}
	function openCustomerModal() {
		if (els.customerModal) {
			var fromPay = els.payModal && !els.payModal.hidden;
			els.customerModal.classList.toggle('is-stacked', fromPay);
			els.customerModal.hidden = false;
		}
		if (els.customerSearch) { els.customerSearch.value = ''; els.customerSearch.focus(); }
		if (els.customerResults) els.customerResults.innerHTML = '';
		if (els.customerCreateForm) els.customerCreateForm.hidden = true;
	}
	function closeCustomerModal() {
		if (els.customerModal) {
			els.customerModal.hidden = true;
			els.customerModal.classList.remove('is-stacked');
		}
		scheduleFocusBarcode();
	}

	function createCustomerFromModal() {
		var name = els.customerCreateName ? els.customerCreateName.value.trim() : '';
		var phone = els.customerCreatePhone ? els.customerCreatePhone.value.trim() : '';
		var email = els.customerCreateEmail ? els.customerCreateEmail.value.trim() : '';
		var city = els.customerCreateCity ? els.customerCreateCity.value.trim() : '';
		var district = els.customerCreateDistrict ? els.customerCreateDistrict.value.trim() : '';
		var addressText = els.customerCreateAddress ? els.customerCreateAddress.value.trim() : '';

		if (name === '' || phone === '') {
			toast('Ad soyad ve telefon zorunludur', 'error');
			return;
		}

		request('customer', {
			method: 'POST',
			body: {
				customer_op: 'create',
				name: name,
				phone: phone,
				email: email,
				city: city,
				district: district,
				address_text: addressText,
			},
		}).then(function (data) {
			if (!data.success) {
				toast(data.message || 'Müşteri eklenemedi', 'error');
				return;
			}

			if (data.customer) {
				state.customer = data.customer;
				updateCustomerUi();
				closeCustomerModal();
				toast('Müşteri eklendi', 'success');
				return;
			}

			var idUser = parseInt(data.id_user || 0, 10);
			if (idUser > 0) {
				setCustomer(idUser);
				return;
			}

			toast(data.message || 'Müşteri eklendi', 'success');
		});
	}

	function toggleFullscreen() {
		if (!document.fullscreenElement) {
			document.documentElement.requestFullscreen().catch(function () {});
			if (els.fullscreen) els.fullscreen.textContent = 'Tam Ekrandan Çık';
		} else {
			document.exitFullscreen();
			if (els.fullscreen) els.fullscreen.textContent = 'Tam Ekran';
		}
	}

	function prepareOnlineCard() {
		/* Eski sanal POS akışı kaldırıldı — fiziksel POS kullanın */
	}

	function lockScreen() {
		request('lock', { method: 'POST', body: {} }).then(function (data) {
			if (data.success) {
				window.location.reload();
			} else {
				toast(data.message || 'Kilitlenemedi', 'error');
			}
		});
	}

	function completeSale() {
		if (state.cart.empty) { toast('Sepet boş', 'error'); return; }

		var payment = state.payMethod;
		var cashPaid = 0;
		var saleType = state.saleType;
		var idCargo = 0;
		var addressCity = '';
		var addressDistrict = '';
		var addressText = '';
		var customerPhone = state.customer.phone || '';

		if (saleType === 'shipping') {
			if (!(state.customer.id_user > 0)) {
				toast('Kargo satışı için müşteri seçin veya ekleyin', 'error');
				openCustomerModal();
				return;
			}
			idCargo = els.cargoSelect ? parseInt(els.cargoSelect.value || '0', 10) : 0;
			addressCity = els.shipCity ? els.shipCity.value.trim() : '';
			addressDistrict = els.shipDistrict ? els.shipDistrict.value.trim() : '';
			addressText = els.shipAddress ? els.shipAddress.value.trim() : '';
			customerPhone = els.shipPhone ? els.shipPhone.value.trim() : customerPhone;

			if (idCargo <= 0) {
				toast('Kargo firması seçin', 'error');
				return;
			}
			if (!addressCity || !addressDistrict || !addressText) {
				toast('İl, ilçe ve adres gerekli', 'error');
				return;
			}
			if (!customerPhone) {
				toast('Telefon gerekli', 'error');
				return;
			}
		}

		if (payment === 'pos_cash') {
			cashPaid = parseMoney(state.cashInput);
			if (cashPaid + 0.009 < getPayableTotal()) {
				toast('Alınan nakit tutarı yetersiz', 'error');
				return;
			}
		} else if (payment === 'pos_card') {
			if (!confirm('POS cihazından ödeme alındı mı? Sipariş oluşturulacak.')) return;
		} else if (payment === 'pos_transfer') {
			if (!confirm('Havale bekleniyor olarak sipariş oluşturulsun mu?')) return;
		}

		if (els.completeSale) els.completeSale.disabled = true;

		request('complete', {
			method: 'POST',
			body: {
				payment_method: payment,
				cash_paid: cashPaid,
				customer_name: state.customer.name || '',
				customer_phone: customerPhone,
				customer_email: state.customer.email || '',
				note: '',
				sale_type: saleType,
				id_cargo: saleType === 'shipping' ? idCargo : 0,
				id_address: saleType === 'shipping' && els.shipAddressSelect ? parseInt(els.shipAddressSelect.value || '0', 10) : 0,
				address_city: saleType === 'shipping' ? addressCity : '',
				address_district: saleType === 'shipping' ? addressDistrict : '',
				address_text: saleType === 'shipping' ? addressText : '',
				shipping_fee: saleType === 'shipping' ? (state.shippingFee || 0) : 0,
			},
		}).then(function (data) {
			if (els.completeSale) els.completeSale.disabled = false;
			if (!data.success) { toast(data.message || 'Hata', 'error'); return; }

			state.cart = data.cart || state.cart;
			renderCart();
			closePayModal();
			loadCustomer();
			if (data.receipt) {
				openReceiptModal(data.receipt);
			}
			toast((data.reference || 'Satış') + ' tamamlandı', 'success');
			loadProducts();
		}).catch(function () {
			if (els.completeSale) els.completeSale.disabled = false;
		});
	}

	function bindEvents() {
		if (els.barcode) {
			els.barcode.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					var code = els.barcode.value.trim();
					if (code) scanBarcode(code);
				}
			});
			focusBarcode(false);
		}

		if (els.qtyMinus) {
			els.qtyMinus.addEventListener('click', function () {
				bumpScanQty(-1);
			});
		}
		if (els.qtyPlus) {
			els.qtyPlus.addEventListener('click', function () {
				bumpScanQty(1);
			});
		}
		if (els.qty) {
			els.qty.addEventListener('change', function () { getScanQty(); });
			els.qty.addEventListener('blur', function () { getScanQty(); });
		}

		if (els.openSearch) els.openSearch.addEventListener('click', openSearchModal);

		if (els.searchInput) {
			var searchTimer;
			els.searchInput.addEventListener('input', function () {
				clearTimeout(searchTimer);
				searchTimer = setTimeout(function () { searchProducts(els.searchInput.value); }, 280);
			});
			els.searchInput.addEventListener('keydown', function (e) {
				if (e.key === 'Enter') {
					e.preventDefault();
					searchProducts(els.searchInput.value);
				}
			});
		}

		document.querySelectorAll('.pos-sale-type').forEach(function (btn) {
			btn.addEventListener('click', function () {
				setSaleType(btn.getAttribute('data-sale-type') || 'store', true);
			});
		});

		if (els.shipBack) {
			els.shipBack.addEventListener('click', function () {
				showPayStep(1);
			});
		}
		if (els.shipNext) {
			els.shipNext.addEventListener('click', function () {
				if (!validateShippingStep()) return;
				showPayStep('pay');
			});
		}
		if (els.payBack) {
			els.payBack.addEventListener('click', function () {
				if (state.saleType === 'shipping') {
					showPayStep('ship');
				} else {
					showPayStep(1);
				}
			});
		}
		if (els.changeCustomerShip) {
			els.changeCustomerShip.addEventListener('click', openCustomerModal);
		}

		if (els.cargoSelect) {
			els.cargoSelect.addEventListener('change', updatePayUi);
		}
		if (els.shipAddressSelect) {
			els.shipAddressSelect.addEventListener('change', applySelectedAddress);
		}

		if (els.menuToggle) els.menuToggle.addEventListener('click', openCatsDrawer);
		if (els.cartToggle) els.cartToggle.addEventListener('click', openCartDrawer);
		if (els.catsClose) els.catsClose.addEventListener('click', closeDrawers);
		if (els.cartClose) els.cartClose.addEventListener('click', closeDrawers);
		if (els.drawerBackdrop) els.drawerBackdrop.addEventListener('click', closeDrawers);
		if (els.lockScreenMobile) els.lockScreenMobile.addEventListener('click', lockScreen);

		document.querySelectorAll('.pos-cat').forEach(function (btn) {
			btn.addEventListener('click', function () {
				document.querySelectorAll('.pos-cat').forEach(function (b) { b.classList.remove('is-active'); });
				btn.classList.add('is-active');
				state.category = parseInt(btn.getAttribute('data-category') || '0', 10) || 0;
				state.page = 1;
				state.query = '';
				loadProducts();
				closeDrawers();
				scheduleFocusBarcode(false);
			});
		});

		if (els.prev) els.prev.addEventListener('click', function () { if (state.page > 1) { state.page--; loadProducts(); } scheduleFocusBarcode(false); });
		if (els.next) els.next.addEventListener('click', function () { if (state.page < state.pages) { state.page++; loadProducts(); } scheduleFocusBarcode(false); });
		if (els.clearCart) els.clearCart.addEventListener('click', clearCart);
		if (els.openPay) els.openPay.addEventListener('click', function () {
			closeDrawers();
			openPayModal();
		});
		if (els.changeCustomer) els.changeCustomer.addEventListener('click', openCustomerModal);
		if (els.customerVisitor) els.customerVisitor.addEventListener('click', resetCustomer);
		if (els.customerCreateToggle && els.customerCreateForm) {
			els.customerCreateToggle.addEventListener('click', function () {
				els.customerCreateForm.hidden = !els.customerCreateForm.hidden;
			});
		}
		if (els.customerCreateSave) els.customerCreateSave.addEventListener('click', createCustomerFromModal);
		if (els.payReset) els.payReset.addEventListener('click', resetPayForm);
		if (els.completeSale) els.completeSale.addEventListener('click', completeSale);
		if (els.lockScreen) els.lockScreen.addEventListener('click', lockScreen);

		if (els.dayEndBtn) els.dayEndBtn.addEventListener('click', openDayEndPinModal);
		if (els.dayEndBtnMobile) els.dayEndBtnMobile.addEventListener('click', openDayEndPinModal);
		if (els.dayEndPinSubmit) els.dayEndPinSubmit.addEventListener('click', submitDayEndPin);
		bindDayEndPinBoxes();
		if (els.dayEndPrint) els.dayEndPrint.addEventListener('click', printDayEnd);
		if (els.dayEndPrintDetail) els.dayEndPrintDetail.addEventListener('click', printDayEndDetail);
		if (els.dayEndCloseRegister) els.dayEndCloseRegister.addEventListener('click', closeRegister);

		if (els.customerSearch) {
			var custTimer;
			els.customerSearch.addEventListener('input', function () {
				clearTimeout(custTimer);
				custTimer = setTimeout(function () { searchCustomers(els.customerSearch.value.trim()); }, 300);
			});
		}

		document.querySelectorAll('.pos-pay-method').forEach(function (btn) {
			btn.addEventListener('click', function () { setPayMethod(btn.getAttribute('data-method') || 'pos_cash'); });
		});

		document.querySelectorAll('.pos-quick').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var amount = btn.getAttribute('data-amount');
				if (amount) state.cashInput = formatMoney(parseFloat(amount));
				updateCashUi();
			});
		});

		if (els.exactAmount) {
			els.exactAmount.addEventListener('click', function () {
				state.cashInput = formatMoney(getPayableTotal());
				updateCashUi();
			});
		}

		if (els.cashInput) {
			els.cashInput.addEventListener('input', function () {
				state.cashInput = els.cashInput.value;
				updateCashUi();
			});
		}

		document.querySelectorAll('[data-close]').forEach(function (el) {
			el.addEventListener('click', function () {
				var target = el.getAttribute('data-close');
				if (target === 'pay') closePayModal();
				if (target === 'var') closeVarModal();
				if (target === 'customer') closeCustomerModal();
				if (target === 'receipt') closeReceiptModal();
				if (target === 'search') closeSearchModal();
				if (target === 'dayend-pin') closeDayEndPinModal();
				if (target === 'dayend') closeDayEndModal();
			});
		});

		if (els.receiptPrint) els.receiptPrint.addEventListener('click', printReceipt);

		document.addEventListener('visibilitychange', function () {
			if (!document.hidden) scheduleFocusBarcode(false);
		});

		window.addEventListener('focus', function () { scheduleFocusBarcode(false); });

		if (els.fullscreen) {
			els.fullscreen.addEventListener('click', toggleFullscreen);
			document.addEventListener('fullscreenchange', function () {
				if (els.fullscreen) {
					els.fullscreen.textContent = document.fullscreenElement ? 'Tam Ekrandan Çık' : 'Tam Ekran';
				}
			});
		}
	}

	initEls();
	bindEvents();
	updateClock();
	setInterval(updateClock, 30000);

	state.customer.label = app.getAttribute('data-customer') || 'Ziyaretçi';
	state.customer.name = state.customer.label;
	updateCustomerUi();

	loadProducts();
	loadCart();
	loadCustomer();

	if (fullscreenAuto) {
		setTimeout(function () {
			if (!document.fullscreenElement) {
				document.documentElement.requestFullscreen().catch(function () {});
			}
		}, 300);
	}

	var saleRef = new URLSearchParams(window.location.search).get('sale');
	if (saleRef) {
		loadReceiptByReference(saleRef);
		if (window.history.replaceState) {
			window.history.replaceState({}, document.title, window.location.pathname);
		}
	}

	scheduleFocusBarcode(false);
	setInterval(function () {
		if (!isModalOpen() && document.activeElement !== els.barcode && document.activeElement !== els.qty) {
			scheduleFocusBarcode(false);
		}
	}, 3000);
})();
