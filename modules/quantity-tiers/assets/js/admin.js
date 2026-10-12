(function () {
	'use strict';

	var jsonInput = document.getElementById('quantityTiersJson');
	var body = document.getElementById('quantityTiersBody');
	var addBtn = document.getElementById('quantityTierAdd');
	if (!jsonInput || !body) {
		return;
	}

	var tiers = [];
	try {
		tiers = JSON.parse(jsonInput.value || '[]') || [];
	} catch (e) {
		tiers = [];
	}

	function sync() {
		jsonInput.value = JSON.stringify(tiers);
	}

	function render() {
		if (!tiers.length) {
			body.innerHTML = '<tr><td colspan="3" class="text-muted small">Kademe yok. En az iki kademe ekleyin (ör. 1 ve 3).</td></tr>';
			sync();
			return;
		}
		var html = '';
		tiers.forEach(function (tier, idx) {
			html += '<tr data-idx="' + idx + '">'
				+ '<td><input type="number" min="1" class="form-control form-control-sm" data-qt-qty value="' + (tier.min_qty || 1) + '"></td>'
				+ '<td><input type="text" class="form-control form-control-sm" data-qt-price value="' + (tier.unit_price != null ? tier.unit_price : '') + '" placeholder="0.00"></td>'
				+ '<td><button type="button" class="btn btn-sm btn-outline-danger" data-qt-remove>×</button></td>'
				+ '</tr>';
		});
		body.innerHTML = html;
		sync();
	}

	body.addEventListener('input', function (e) {
		var row = e.target.closest('tr');
		var idx = row ? Number(row.getAttribute('data-idx')) : -1;
		if (idx < 0 || !tiers[idx]) {
			return;
		}
		if (e.target.hasAttribute('data-qt-qty')) {
			tiers[idx].min_qty = Math.max(1, parseInt(e.target.value, 10) || 1);
		}
		if (e.target.hasAttribute('data-qt-price')) {
			tiers[idx].unit_price = e.target.value;
		}
		sync();
	});

	body.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-qt-remove]');
		if (!btn) {
			return;
		}
		var row = btn.closest('tr');
		var idx = row ? Number(row.getAttribute('data-idx')) : -1;
		if (idx >= 0) {
			tiers.splice(idx, 1);
			render();
		}
	});

	if (addBtn) {
		addBtn.addEventListener('click', function () {
			var nextQty = 1;
			tiers.forEach(function (t) {
				nextQty = Math.max(nextQty, (parseInt(t.min_qty, 10) || 0) + 1);
			});
			tiers.push({ min_qty: nextQty, unit_price: '' });
			render();
		});
	}

	render();
})();
