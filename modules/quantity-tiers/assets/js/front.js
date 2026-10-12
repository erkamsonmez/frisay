(function () {
	'use strict';

	var root = document.querySelector('[data-qty-tiers]');
	if (!root) {
		return;
	}

	var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-tier-qty]'));
	var qtyInput = document.getElementById('qty-input');
	var priceEls = document.querySelectorAll('[data-product-price], .product-price .current-price, .sn-pdp-price__current, #productPrice');

	function parseQty() {
		if (!qtyInput) {
			return 1;
		}
		var n = parseFloat(String(qtyInput.value || '1').replace(',', '.'));
		return isNaN(n) || n < 1 ? 1 : n;
	}

	function setQty(qty) {
		if (!qtyInput || qtyInput.getAttribute('data-sale-unit') === 'm2') {
			return;
		}
		var max = parseFloat(qtyInput.getAttribute('data-max') || '0');
		qty = Math.max(1, Math.floor(qty));
		if (max > 0) {
			qty = Math.min(qty, max);
		}
		qtyInput.value = String(qty);
		try {
			qtyInput.dispatchEvent(new Event('change', { bubbles: true }));
			qtyInput.dispatchEvent(new Event('input', { bubbles: true }));
		} catch (err) {}
	}

	function activeTierForQty(qty) {
		var best = null;
		buttons.forEach(function (btn) {
			var min = parseInt(btn.getAttribute('data-tier-qty') || '0', 10);
			if (min > 0 && qty >= min) {
				best = btn;
			}
		});
		return best || buttons[0] || null;
	}

	function syncActive() {
		var qty = parseQty();
		var active = activeTierForQty(qty);
		buttons.forEach(function (btn) {
			btn.classList.toggle('is-active', btn === active);
		});
	}

	buttons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			var qty = parseInt(btn.getAttribute('data-tier-qty') || '1', 10);
			setQty(qty);
			syncActive();
		});
	});

	if (qtyInput) {
		qtyInput.addEventListener('change', syncActive);
		qtyInput.addEventListener('input', syncActive);
	}

	// Qty buttons call updateQty globally — poll lightly via MutationObserver-free interval on click bubble
	document.addEventListener('click', function (e) {
		if (e.target && (e.target.closest('.qty-btn') || e.target.closest('#qtyPicker'))) {
			setTimeout(syncActive, 0);
		}
	});

	syncActive();
})();
