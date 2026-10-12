(function () {
	'use strict';

	var panel = document.getElementById('productSiblingsAdminPanel');
	if (!panel) {
		return;
	}

	var jsonInput = document.getElementById('siblingItemsJson');
	var body = document.getElementById('siblingItemsBody');
	var searchInput = document.getElementById('siblingProductSearch');
	var resultsEl = document.getElementById('siblingSearchResults');
	var searchApi = panel.getAttribute('data-search-api') || '';
	var token = panel.getAttribute('data-token') || '';
	var excludeId = Number(panel.getAttribute('data-product-id') || 0);
	var items = [];
	var searchTimer = null;

	try {
		items = JSON.parse(jsonInput && jsonInput.value ? jsonInput.value : '[]') || [];
	} catch (e) {
		items = [];
	}

	function escapeHtml(value) {
		return String(value)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function syncJson() {
		if (jsonInput) {
			jsonInput.value = JSON.stringify(items);
		}
	}

	function render() {
		if (!body) {
			return;
		}
		if (!items.length) {
			body.innerHTML = '<tr><td colspan="4" class="text-muted small">Henüz seçenek yok. Ürün arayıp ekleyin.</td></tr>';
			syncJson();
			return;
		}
		var html = '';
		items.forEach(function (item, idx) {
			html += '<tr data-idx="' + idx + '">'
				+ '<td><strong>#' + item.id_product + '</strong> ' + escapeHtml(item.product_name || '') + '</td>'
				+ '<td><input type="text" class="form-control form-control-sm" data-sib-label value="' + escapeHtml(item.label || '') + '" placeholder="ör. 60 kapsül"></td>'
				+ '<td class="small text-muted">' + escapeHtml(item.price_formatted || '') + '</td>'
				+ '<td><button type="button" class="btn btn-sm btn-outline-danger" data-sib-remove title="Kaldır">×</button></td>'
				+ '</tr>';
		});
		body.innerHTML = html;
		syncJson();
	}

	function addItem(item) {
		var id = Number(item.id_product || 0);
		if (!id) {
			return;
		}
		for (var i = 0; i < items.length; i++) {
			if (Number(items[i].id_product) === id) {
				return;
			}
		}
		items.push({
			id_product: id,
			product_name: item.product_name || '',
			label: item.label || '',
			price: item.price || 0,
			price_formatted: item.price_formatted || ''
		});
		render();
	}

	if (body) {
		body.addEventListener('input', function (e) {
			var input = e.target.closest('[data-sib-label]');
			if (!input) {
				return;
			}
			var row = input.closest('tr');
			var idx = row ? Number(row.getAttribute('data-idx')) : -1;
			if (idx >= 0 && items[idx]) {
				items[idx].label = input.value;
				syncJson();
			}
		});
		body.addEventListener('click', function (e) {
			var btn = e.target.closest('[data-sib-remove]');
			if (!btn) {
				return;
			}
			var row = btn.closest('tr');
			var idx = row ? Number(row.getAttribute('data-idx')) : -1;
			if (idx >= 0) {
				items.splice(idx, 1);
				render();
			}
		});
	}

	function renderSearch(list) {
		if (!resultsEl) {
			return;
		}
		if (!list.length) {
			resultsEl.innerHTML = '';
			return;
		}
		var html = '<div class="list-group list-group-flush border rounded">';
		list.forEach(function (item) {
			html += '<button type="button" class="list-group-item list-group-item-action py-2" data-sib-add="' + item.id_product + '">'
				+ '<strong>#' + item.id_product + '</strong> ' + escapeHtml(item.product_name)
				+ ' <span class="text-muted small">' + escapeHtml(item.price_formatted || '') + '</span>'
				+ '</button>';
		});
		html += '</div>';
		resultsEl.innerHTML = html;
		resultsEl.querySelectorAll('[data-sib-add]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var id = Number(btn.getAttribute('data-sib-add'));
				var found = null;
				list.forEach(function (row) {
					if (Number(row.id_product) === id) {
						found = row;
					}
				});
				if (found) {
					addItem(found);
				}
				resultsEl.innerHTML = '';
				if (searchInput) {
					searchInput.value = '';
				}
			});
		});
	}

	if (searchInput) {
		searchInput.addEventListener('input', function () {
			clearTimeout(searchTimer);
			var q = searchInput.value.trim();
			if (q.length < 1) {
				renderSearch([]);
				return;
			}
			searchTimer = setTimeout(function () {
				var url = searchApi
					+ (searchApi.indexOf('?') >= 0 ? '&' : '?')
					+ 'q=' + encodeURIComponent(q)
					+ '&exclude=' + encodeURIComponent(excludeId)
					+ '&token=' + encodeURIComponent(token);
				fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
					.then(function (res) { return res.json(); })
					.then(function (data) {
						renderSearch((data && data.items) || []);
					})
					.catch(function () {
						renderSearch([]);
					});
			}, 220);
		});
	}

	render();
})();
