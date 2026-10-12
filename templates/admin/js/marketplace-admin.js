/**
 * Trendyol admin: marka / kategori arama seçicileri + özellik formu
 */
(function (window, document) {
	'use strict';

	var timers = {};

	function esc(s) {
		return String(s == null ? '' : s)
			.replace(/&/g, '&amp;')
			.replace(/</g, '&lt;')
			.replace(/>/g, '&gt;')
			.replace(/"/g, '&quot;');
	}

	function apiUrl(key) {
		if (key === 'brands') return window.trendyolBrandsApiUrl || '';
		if (key === 'categories') return window.trendyolCategoriesApiUrl || '';
		if (key === 'attributes') return window.trendyolAttributesApiUrl || '';
		return '';
	}

	function getJson(url) {
		return fetch(url, { credentials: 'same-origin' }).then(function (r) { return r.json(); });
	}

	function debounce(key, fn, ms) {
		if (timers[key]) clearTimeout(timers[key]);
		timers[key] = setTimeout(fn, ms || 400);
	}

	function setBusy(el, on) {
		if (!el) {
			return;
		}

		if (window.AdminBusy) {
			if (on) {
				AdminBusy.start(el);
			} else {
				AdminBusy.stop(el);
			}
			return;
		}

		el.disabled = !!on;
	}

	function findPicker(el) {
		return el.closest('.ty-picker');
	}

	function setSelected(picker, id, label, silent) {
		var idInput = picker.querySelector('.ty-picker-id');
		var nameInput = picker.querySelector('.ty-picker-name');
		var labelEl = picker.querySelector('.ty-picker-selected');
		var query = picker.querySelector('.ty-picker-query');
		var results = picker.querySelector('.ty-picker-results');

		if (idInput) idInput.value = id || '';
		if (nameInput) nameInput.value = label || '';
		if (labelEl) {
			if (id) {
				labelEl.innerHTML = '<span class="badge text-bg-success">' + esc(label || ('#' + id)) + '</span>' +
					' <span class="text-muted small">#' + esc(id) + '</span>';
			} else {
				labelEl.innerHTML = '<span class="text-muted small">Seçilmedi</span>';
			}
		}
		if (query) query.value = '';
		if (results) results.innerHTML = '';

		if (silent) {
			return;
		}

		picker.dispatchEvent(new CustomEvent('ty:selected', {
			bubbles: true,
			detail: { id: id, label: label, type: picker.getAttribute('data-type'), silent: !!silent }
		}));
	}

	function renderResults(picker, items, type) {
		var results = picker.querySelector('.ty-picker-results');
		if (!results) return;

		if (!items.length) {
			results.innerHTML = '<div class="text-muted small p-2">Sonuç yok</div>';
			return;
		}

		var html = '<div class="list-group list-group-flush border rounded ty-picker-list">';
		items.forEach(function (item) {
			html += '<button type="button" class="list-group-item list-group-item-action ty-picker-item"' +
				' data-id="' + esc(item.id) + '" data-label="' + esc(item.label) + '">' +
				'<div class="fw-semibold small">' + esc(item.label) + '</div>' +
				'<div class="text-muted" style="font-size:11px">#' + esc(item.id) + '</div>' +
				'</button>';
		});
		html += '</div>';
		results.innerHTML = html;
	}

	function searchPicker(picker) {
		var type = picker.getAttribute('data-type');
		var queryEl = picker.querySelector('.ty-picker-query');
		var results = picker.querySelector('.ty-picker-results');
		var q = queryEl ? queryEl.value.trim() : '';

		if (!results) return;

		if (q.length < 2) {
			results.innerHTML = '<div class="text-muted small p-1">En az 2 karakter yazın…</div>';
			return;
		}

		var base = apiUrl(type === 'brand' ? 'brands' : 'categories');
		if (!base) {
			results.innerHTML = '<div class="text-danger small p-1">API adresi yok</div>';
			return;
		}

		results.innerHTML = '<div class="text-muted small p-1">Aranıyor…</div>';
		var sep = base.indexOf('?') >= 0 ? '&' : '?';

		getJson(base + sep + 'name=' + encodeURIComponent(q))
			.then(function (res) {
				if (!res.success) {
					results.innerHTML = '<div class="text-danger small p-1">' + esc(res.message || 'Hata') + '</div>';
					return;
				}

				var items = [];
				if (type === 'brand') {
					(res.brands || []).slice(0, 20).forEach(function (b) {
						var id = b.id || b.brandId || '';
						var name = b.name || b.brandName || '';
						if (id) items.push({ id: id, label: name || ('#' + id) });
					});
				} else {
					(res.categories || []).forEach(function (c) {
						items.push({
							id: c.id,
							label: c.path || c.name || ('#' + c.id)
						});
					});
				}

				renderResults(picker, items, type);
			})
			.catch(function () {
				results.innerHTML = '<div class="text-danger small p-1">İstek başarısız</div>';
			});
	}

	function collectAttributes(panel) {
		var hidden = panel.querySelector('.ty-attributes');
		var map = {};
		panel.querySelectorAll('.ty-attr-input').forEach(function (el) {
			if (el.getAttribute('data-varianter') === '1') return;
			var aid = el.getAttribute('data-attr-id');
			var val = (el.value || '').trim();
			if (aid && val !== '') map[aid] = val;
		});
		if (hidden) hidden.value = JSON.stringify(map);
		return map;
	}

	function collectVariants(panel) {
		var rows = [];
		if (!panel) return rows;
		panel.querySelectorAll('.ty-variant-row').forEach(function (tr) {
			var id = parseInt(tr.getAttribute('data-id-variation') || '0', 10) || 0;
			if (!id) return;
			var sale = tr.querySelector('.ty-variant-sale');
			var stock = tr.querySelector('.ty-variant-stock');
			var parentSale = panel.querySelector('.ty-sale-price-input');
			var saleVal = sale && sale.value !== '' ? sale.value : (parentSale ? parentSale.value : '');
			rows.push({
				id_variation: id,
				sale_price: saleVal,
				stock: stock ? stock.value : ''
			});
		});
		return rows;
	}

	function isAttrRequired(row) {
		return !!(row && (row.required || row.mandatory || row.isMandatory));
	}

	function sortCategoryAttrs(rows) {
		return (rows || []).slice().sort(function (a, b) {
			var ra = isAttrRequired(a) ? 0 : 1;
			var rb = isAttrRequired(b) ? 0 : 1;
			if (ra !== rb) return ra - rb;
			var na = String((a.attribute && a.attribute.name) || a.name || '');
			var nb = String((b.attribute && b.attribute.name) || b.name || '');
			return na.localeCompare(nb, 'tr');
		});
	}

	function isOriginAttrName(name) {
		return /men[sş]ei|mensei|\borigin\b|menşe/i.test(String(name || ''));
	}

	function pickDefaultOriginValueId(values) {
		var fallback = '';
		var i;
		for (i = 0; i < values.length; i++) {
			var v = values[i] || {};
			var vid = v.id != null ? v.id : (v.attributeValueId != null ? v.attributeValueId : '');
			var vname = String(v.name || v.attributeValueName || v.attributeValue || '').toLowerCase();
			if (vid === '' || vid == null) continue;
			if (fallback === '') fallback = vid;
			if (/t[uü]rkiye|turkey|^tr$/.test(vname)) return vid;
		}
		return fallback;
	}

	function renderAttributeForm(panel, categoryAttributes, selected) {
		var box = panel.querySelector('.ty-attr-form');
		var hidden = panel.querySelector('.ty-attributes');
		if (!box) return;

		selected = selected || {};
		if (hidden && hidden.value) {
			try {
				var parsed = JSON.parse(hidden.value);
				if (parsed && typeof parsed === 'object') {
					Object.keys(parsed).forEach(function (k) {
						if (selected[k] == null) selected[k] = parsed[k];
					});
				}
			} catch (e) { /* ignore */ }
		}

		if (!categoryAttributes || !categoryAttributes.length) {
			box.innerHTML = '<div class="text-muted small">Bu kategori için özellik listesi boş.</div>';
			return;
		}

		var variantCount = parseInt(panel.getAttribute('data-variant-count') || '0', 10) || 0;
		var sorted = sortCategoryAttrs(categoryAttributes);
		var html = '';
		var sawRequired = false;
		var sawOptional = false;

		sorted.forEach(function (row) {
			var attr = row.attribute || {};
			var aid = attr.id;
			if (!aid) return;
			var aname = attr.name || ('#' + aid);
			var required = isAttrRequired(row);
			var varianter = !!(row.varianter || attr.varianter);
			if (varianter && variantCount > 0) required = false;
			if (required && !sawRequired) {
				sawRequired = true;
				html += '<div class="col-12"><p class="small text-muted mb-1 mt-1">Zorunlu özellikler</p></div>';
			}
			if (!required && !sawOptional) {
				sawOptional = true;
				html += '<div class="col-12"><p class="small text-muted mb-1 mt-2">İsteğe bağlı özellikler</p></div>';
			}
			var allowCustom = !!row.allowCustom;
			var values = row.attributeValues || row.values || [];
			var cur = selected[String(aid)] != null ? selected[String(aid)] : '';
			if (cur === '' && isOriginAttrName(aname)) {
				if (values.length) {
					cur = pickDefaultOriginValueId(values);
				} else {
					cur = 'Türkiye';
				}
			}

			html += '<div class="col-md-6 ty-attr-wrap"' +
				(required ? ' data-required="1" data-attr-name="' + esc(aname) + '"' : '') +
				'><label class="form-label small mb-0">' + esc(aname);
			if (required) html += ' <span class="text-danger">*</span>';
			if (varianter && variantCount > 0) html += ' <span class="text-muted">(varyasyonlardan otomatik)</span>';
			html += '</label>';

			if (varianter && variantCount > 0) {
				html += '<input type="hidden" class="ty-attr-input" data-attr-id="' + esc(aid) + '" data-varianter="1" value="">';
				html += '<div class="form-control form-control-sm bg-light text-muted">Varyasyon değerleri otomatik yazılır</div>';
			} else if (values.length) {
				html += '<select class="form-select form-select-sm ty-attr-input" data-attr-id="' + esc(aid) + '"' + (required ? ' required' : '') + '>';
				html += '<option value="">Seçin…</option>';
				values.forEach(function (v) {
					var vid = v.id != null ? v.id : (v.attributeValueId != null ? v.attributeValueId : '');
					var vname = v.name || v.attributeValueName || v.attributeValue || ('#' + vid);
					var sel = String(cur) === String(vid) ? ' selected' : '';
					html += '<option value="' + esc(vid) + '"' + sel + '>' + esc(vname) + '</option>';
				});
				html += '</select>';
			} else {
				html += '<input type="text" class="form-control form-control-sm ty-attr-input" data-attr-id="' +
					esc(aid) + '" value="' + esc(cur) + '" placeholder="' + (allowCustom ? 'Serbest metin' : '') + '"' +
					(required ? ' required' : '') + '>';
			}
			html += '</div>';
		});

		box.innerHTML = '<div class="row g-2">' + html + '</div>';
		collectAttributes(panel);
	}

	function loadAttributesForPanel(panel, categoryId) {
		var box = panel.querySelector('.ty-attr-form');
		var url = apiUrl('attributes');
		if (!box || !url || !categoryId) return;

		box.innerHTML = '<div class="text-muted small">Özellikler yükleniyor…</div>';
		var sep = url.indexOf('?') >= 0 ? '&' : '?';

		getJson(url + sep + 'category_id=' + encodeURIComponent(categoryId))
			.then(function (res) {
				if (!res.success) {
					box.innerHTML = '<div class="text-danger small">' + esc(res.message || 'Özellikler alınamadı') + '</div>';
					return;
				}
				renderAttributeForm(panel, res.categoryAttributes || [], {});
			})
			.catch(function () {
				box.innerHTML = '<div class="text-danger small">Özellik isteği başarısız</div>';
			});
	}

	document.addEventListener('input', function (e) {
		var query = e.target.closest('.ty-picker-query');
		if (!query) return;
		var picker = findPicker(query);
		if (!picker) return;
		var key = picker.getAttribute('data-type') + ':' + (picker.getAttribute('data-key') || 'x');
		debounce(key, function () { searchPicker(picker); }, 350);
	});

	document.addEventListener('keydown', function (e) {
		if (e.key !== 'Enter') return;
		var query = e.target.closest('.ty-picker-query');
		if (!query) return;
		e.preventDefault();
		var picker = findPicker(query);
		if (picker) searchPicker(picker);
	});

	document.addEventListener('click', function (e) {
		var item = e.target.closest('.ty-picker-item');
		if (item) {
			var picker = findPicker(item);
			if (picker) {
				setSelected(picker, item.getAttribute('data-id'), item.getAttribute('data-label'));
			}
			return;
		}

		var clearBtn = e.target.closest('.ty-picker-clear');
		if (clearBtn) {
			var picker = findPicker(clearBtn);
			if (picker) setSelected(picker, '', '');
			return;
		}

		if (!e.target.closest('.ty-picker')) {
			document.querySelectorAll('.ty-picker-results').forEach(function (el) {
				el.innerHTML = '';
			});
		}
	});

	document.addEventListener('ty:selected', function (e) {
		var picker = e.target.closest ? e.target.closest('.ty-picker') : null;
		if (!picker || e.detail.type !== 'category') return;
		var panel = picker.closest('.trendyol-product-panel, .ty-category-map-row, .admin-panel');
		if (!panel || !panel.querySelector('.ty-attr-form')) return;
		if (e.detail.id) loadAttributesForPanel(panel, e.detail.id);
	});

	document.addEventListener('change', function (e) {
		if (!e.target.classList.contains('ty-attr-input')) return;
		var panel = e.target.closest('.trendyol-product-panel, .ty-category-map-row, .admin-panel');
		if (panel) collectAttributes(panel);
	});

	document.addEventListener('input', function (e) {
		if (!e.target.classList.contains('ty-attr-input')) return;
		var panel = e.target.closest('.trendyol-product-panel, .ty-category-map-row, .admin-panel');
		if (panel) collectAttributes(panel);
	});

	function adminCsrfToken() {
		if (window.__adminOrderStatus && window.__adminOrderStatus.token) {
			return String(window.__adminOrderStatus.token);
		}
		if (window.__adminCsrfToken) {
			return String(window.__adminCsrfToken);
		}
		return '';
	}

	function post(url, body) {
		body = body || {};
		if (!body.token) {
			body.token = adminCsrfToken();
		}
		return fetch(url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
			credentials: 'same-origin',
			body: new URLSearchParams(body).toString()
		}).then(function (r) { return r.json(); });
	}

	function panelMsg(panel, text, ok) {
		var el = panel.querySelector('.ty-action-msg');
		if (!el) return;
		el.textContent = text || '';
		el.className = 'small d-block mt-2 ty-action-msg ' + (ok ? 'text-success' : 'text-danger');
	}

	function missingRequiredAttrs(panel) {
		var missing = [];
		if (!panel) return missing;
		panel.querySelectorAll('.ty-attr-wrap[data-required="1"]').forEach(function (wrap) {
			var filled = false;
			wrap.querySelectorAll('.ty-attr-input').forEach(function (inp) {
				if (inp.getAttribute('data-varianter') === '1') {
					filled = true;
					return;
				}
				if ((inp.value || '').trim() !== '') filled = true;
			});
			if (!filled) missing.push(wrap.getAttribute('data-attr-name') || 'Özellik');
		});
		return missing;
	}

	function hydratePickers(root) {
		(root || document).querySelectorAll('.ty-picker').forEach(function (picker) {
			var idInput = picker.querySelector('.ty-picker-id');
			var nameInput = picker.querySelector('.ty-picker-name');
			var id = idInput ? String(idInput.value || '').trim() : '';
			if (!id) return;
			var label = nameInput ? String(nameInput.value || '').trim() : '';
			setSelected(picker, id, label || ('#' + id), true);
		});
	}

	function bindProductActions() {
		hydratePickers();
		document.querySelectorAll('.ty-sync-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				if (panel) collectAttributes(panel);
				var body = { id_product: btn.getAttribute('data-id') };
				var brand = panel && panel.querySelector('.ty-brand-id');
				var cat = panel && panel.querySelector('.ty-category-id');
				var attrs = panel && panel.querySelector('.ty-attributes');
				var sale = panel && panel.querySelector('.ty-sale-price-input');
				var list = panel && panel.querySelector('.ty-list-price-input');
				if (!brand || !brand.value || !cat || !cat.value) {
					panelMsg(panel, 'Marka ve kategori seçin.', false);
					return;
				}
				if (!sale || sale.value === '' || parseFloat(sale.value) <= 0) {
					panelMsg(panel, 'Satış fiyatı girin.', false);
					return;
				}
				var missing = missingRequiredAttrs(panel);
				if (missing.length) {
					panelMsg(panel, 'Zorunlu özellikler eksik: ' + missing.join(', '), false);
					return;
				}
				if (brand && brand.value) body.brand_id = brand.value;
				if (cat && cat.value) body.category_id = cat.value;
				if (attrs && attrs.value) body.attributes = attrs.value;
				if (sale && sale.value !== '') body.sale_price = sale.value;
				if (list && list.value !== '') body.list_price = list.value;
				var variants = collectVariants(panel);
				if (variants.length) {
					var missingPrice = variants.filter(function (v) {
						return !(parseFloat(v.sale_price) > 0);
					});
					if (missingPrice.length) {
						panelMsg(panel, 'Her varyasyon için satış fiyatı girin.', false);
						return;
					}
					body.variants = JSON.stringify(variants);
				}
				setBusy(btn, true);
				panelMsg(panel, 'Gönderiliyor…', true);
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 800);
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ty-price-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				var body = { id_product: btn.getAttribute('data-id') };
				var sale = panel && panel.querySelector('.ty-sale-price-input');
				var list = panel && panel.querySelector('.ty-list-price-input');
				if (sale && sale.value !== '') body.sale_price = sale.value;
				if (list && list.value !== '') body.list_price = list.value;
				setBusy(btn, true);
				panelMsg(panel, 'Fiyat güncelleniyor…', true);
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success && res.mapping) {
							var q = panel.querySelector('.ty-qty');
							if (q) q.textContent = res.mapping.quantity;
							if (sale) sale.value = res.mapping.sale_price;
							if (list) list.value = res.mapping.list_price;
							var card = document.querySelector('.marketplace-connection--trendyol[data-product-id="' + btn.getAttribute('data-id') + '"]');
							var status = card && card.querySelector('.marketplace-connection-status');
							var cardPrice = Number(res.mapping.sale_price || 0);

							if (status) {
								status.textContent = 'Bağlı' + (cardPrice > 0
									? ' · ' + cardPrice.toLocaleString('tr-TR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' TL'
									: '');
							}
						}
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ty-refresh-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				setBusy(btn, true);
				panelMsg(panel, 'Yenileniyor…', true);
				post(btn.getAttribute('data-url'), { id_product: btn.getAttribute('data-id') })
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ty-unlink-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				var message = 'Trendyol bağlantısı silinecek. Ürün Trendyol mağazasında kalır; yalnızca FShop eşlemesi kaldırılır. Devam edilsin mi?';
				var ask = window.AdminConfirm && AdminConfirm.ask
					? AdminConfirm.ask({ message: message })
					: Promise.resolve(false);

				ask.then(function (ok) {
				if (!ok) {
					return;
				}

				setBusy(btn, true);
				panelMsg(panel, 'Bağlantı siliniyor…', true);
				post(btn.getAttribute('data-url'), { id_product: btn.getAttribute('data-id') })
					.then(function (res) {
						if (panel) {
							panelMsg(panel, res.message || '', !!res.success);
						}

				if (res.success) {
					if (document.querySelector('.ty-marketplace-catalog')) {
						setTimeout(function () { location.reload(); }, 600);
						return;
					}

					var row = btn.closest('tr');
					if (row) {
						row.remove();
						return;
					}

					setTimeout(function () { location.reload(); }, 700);
				}
					})
					.catch(function () {
						if (panel) {
							panelMsg(panel, 'İstek başarısız', false);
						}
					})
					.finally(function () {
						setBusy(btn, false);
					});
				});
			});
		});

		document.querySelectorAll('.ty-link-existing-btn').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var panel = btn.closest('.trendyol-product-panel');
				var input = panel && panel.querySelector('.ty-existing-barcode-input');
				var barcode = input ? input.value.trim() : '';

				if (!barcode) {
					panelMsg(panel, 'Trendyol barkodu gerekli', false);
					return;
				}

				setBusy(btn, true);
				panelMsg(panel, 'Trendyol ürünü doğrulanıyor…', true);
				post(btn.getAttribute('data-url'), { id_product: btn.getAttribute('data-id'), barcode: barcode })
					.then(function (res) {
						panelMsg(panel, res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () { panelMsg(panel, 'İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.quick-stock-btn').forEach(function (btn) {
			if (btn.dataset.qsBound) return;
			btn.dataset.qsBound = '1';
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				e.stopPropagation();
				var id = btn.getAttribute('data-id');
				var name = btn.getAttribute('data-name');
				var stock = btn.getAttribute('data-stock');

				var modalEl = document.getElementById('quickStockModal');
				if (!modalEl) return;

				var nameEl = modalEl.querySelector('.quick-stock-product-name');
				var idInput = modalEl.querySelector('#qs_id_product');
				var stockInput = modalEl.querySelector('#qs_stock');
				var msgEl = modalEl.querySelector('.qs-msg');

				if (nameEl) nameEl.textContent = name || ('#' + id);
				if (idInput) idInput.value = id || '0';
				if (stockInput) stockInput.value = stock != null ? stock : '0';
				if (msgEl) {
					msgEl.textContent = '';
					msgEl.className = 'qs-msg small mt-2';
				}

				if (window.bootstrap && window.bootstrap.Modal) {
					var bsModal = window.bootstrap.Modal.getOrCreateInstance(modalEl);
					bsModal.show();
				}
			});
		});

		var qsForm = document.getElementById('quickStockForm');
		if (qsForm && !qsForm.dataset.qsBound) {
			qsForm.dataset.qsBound = '1';
			qsForm.addEventListener('submit', function (e) {
				e.preventDefault();
				var modalEl = document.getElementById('quickStockModal');
				var submitBtn = qsForm.querySelector('.id-qs-submit-btn');
				var spinner = qsForm.querySelector('.qs-spinner');
				var msgEl = qsForm.querySelector('.qs-msg');
				var updateStockUrl = window.trendyolUpdateStockApiUrl || '';

				if (!updateStockUrl) {
					if (msgEl) {
						msgEl.textContent = 'API adresi bulunamadı.';
						msgEl.className = 'qs-msg small mt-2 text-danger';
					}
					return;
				}

				var formData = new FormData(qsForm);
				var body = {
					id_product: formData.get('id_product'),
					stock: formData.get('stock'),
					sync_marketplaces: formData.get('sync_marketplaces')
				};

				if (submitBtn) submitBtn.disabled = true;
				if (spinner) spinner.classList.remove('d-none');
				if (msgEl) {
					msgEl.textContent = 'Stok güncelleniyor…';
					msgEl.className = 'qs-msg small mt-2 text-info';
				}

				post(updateStockUrl, body)
					.then(function (res) {
						if (res.success) {
							if (msgEl) {
								msgEl.textContent = res.message || 'Stok güncellendi.';
								msgEl.className = 'qs-msg small mt-2 text-success';
							}
							var idProd = body.id_product;
							var newStock = res.stock;

							var item = document.getElementById('mp-item-' + idProd);
							if (item) {
								var stockValEl = item.querySelector('.stock-display-val');
								if (stockValEl) stockValEl.textContent = newStock;
								var btn = item.querySelector('.quick-stock-btn');
								if (btn) btn.setAttribute('data-stock', newStock);

								var tyQty = item.querySelector('.ty-qty');
								if (tyQty) tyQty.textContent = newStock;
							}

							setTimeout(function () {
								if (window.bootstrap && window.bootstrap.Modal) {
									var bsModal = window.bootstrap.Modal.getInstance(modalEl);
									if (bsModal) bsModal.hide();
								}
							}, 800);
						} else {
							if (msgEl) {
								msgEl.textContent = res.message || 'Güncelleme başarısız.';
								msgEl.className = 'qs-msg small mt-2 text-danger';
							}
						}
					})
					.catch(function () {
						if (msgEl) {
							msgEl.textContent = 'İstek başarısız oldu.';
							msgEl.className = 'qs-msg small mt-2 text-danger';
						}
					})
					.finally(function () {
						if (submitBtn) submitBtn.disabled = false;
						if (spinner) spinner.classList.add('d-none');
					});
			});
		}

		document.querySelectorAll('.trendyol-product-panel').forEach(function (panel) {
			var catId = panel.querySelector('.ty-category-id');
			var form = panel.querySelector('.ty-attr-form');
			if (catId && catId.value && form && !form.dataset.loaded) {
				form.dataset.loaded = '1';
				loadAttributesForPanel(panel, catId.value);
			}
		});

		bindHbN11PanelActions();
		bindListingMenus();
	}

	function mpPanelCfg(platform) {
		var map = {
			hepsiburada: { panel: '.hb-product-panel', sale: '.hb-sale-price-input', list: '.hb-list-price-input', msg: '.hb-action-msg', input: '.hb-merchant-sku-input', label: 'Hepsiburada' },
			n11: { panel: '.n11-product-panel', sale: '.n11-sale-price-input', list: '.n11-list-price-input', msg: '.n11-action-msg', input: '.n11-stock-code-input', label: 'N11' },
			prestashop: { panel: '.ps-product-panel', sale: '.ps-sale-price-input', list: null, msg: '.ps-action-msg', input: '.ps-product-id-input', label: 'PrestaShop' },
			frisay: { panel: '.frisay-product-panel', sale: '.frisay-sale-price-input', list: null, msg: '.frisay-action-msg', input: '.frisay-remote-id-input', label: 'FriSay' }
		};
		return map[platform] || map.hepsiburada;
	}

	function bindHbN11PanelActions() {
		document.querySelectorAll('.hb-price-btn, .n11-price-btn, .ps-price-btn, .frisay-price-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'hepsiburada';
				var cfg = mpPanelCfg(platform);
				var panel = btn.closest(cfg.panel);
				var body = {
					id_product: btn.getAttribute('data-id'),
					platform: platform
				};
				var sale = panel && panel.querySelector(cfg.sale);
				var list = cfg.list && panel && panel.querySelector(cfg.list);
				if (sale && sale.value !== '') body.sale_price = sale.value;
				if (list && list.value !== '') body.list_price = list.value;
				setBusy(btn, true);
				var msg = panel && panel.querySelector(cfg.msg);
				if (msg) { msg.textContent = 'Fiyat güncelleniyor…'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-muted'; }
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (cfg.msg.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.hb-unlink-btn, .n11-unlink-btn, .ps-unlink-btn, .frisay-unlink-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'hepsiburada';
				var cfg = mpPanelCfg(platform);
				var ask = window.AdminConfirm && AdminConfirm.ask
					? AdminConfirm.ask({ message: cfg.label + ' bağlantısı silinecek. Devam edilsin mi?' })
					: Promise.resolve(false);
				ask.then(function (ok) {
					if (!ok) return;
				var panel = btn.closest(cfg.panel);
				var msg = panel && panel.querySelector(cfg.msg);
				setBusy(btn, true);
				post(btn.getAttribute('data-url'), {
					id_product: btn.getAttribute('data-id'),
					platform: platform
				})
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (cfg.msg.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () {
						setBusy(btn, false);
					});
				});
			});
		});

		document.querySelectorAll('.hb-link-existing-btn, .n11-link-existing-btn, .ps-link-existing-btn, .frisay-link-existing-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'hepsiburada';
				var cfg = mpPanelCfg(platform);
				var panel = btn.closest(cfg.panel);
				var input = panel && panel.querySelector(cfg.input);
				var code = input ? input.value.trim() : '';
				var msg = panel && panel.querySelector(cfg.msg);
				var body = {
					id_product: btn.getAttribute('data-id'),
					platform: platform,
					barcode: code,
					merchant_sku: code,
					stock_code: code
				};
				setBusy(btn, true);
				if (msg) { msg.textContent = 'Eşleştiriliyor…'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-muted'; }
				post(btn.getAttribute('data-url'), body)
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (cfg.msg.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.ps-send-btn, .frisay-send-btn').forEach(function (btn) {
			if (btn.dataset.mpBound) return;
			btn.dataset.mpBound = '1';
			btn.addEventListener('click', function () {
				var platform = btn.getAttribute('data-platform') || 'prestashop';
				var cfg = mpPanelCfg(platform);
				var panel = btn.closest(cfg.panel);
				var msg = panel && panel.querySelector(cfg.msg);
				setBusy(btn, true);
				if (msg) { msg.textContent = 'Gönderiliyor…'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-muted'; }
				post(btn.getAttribute('data-url'), {
					id_product: btn.getAttribute('data-id'),
					platform: platform
				})
					.then(function (res) {
						if (msg) {
							msg.textContent = res.message || '';
							msg.className = (cfg.msg.replace('.', '') + ' small d-block mt-3 ') + (res.success ? 'text-success' : 'text-danger');
						}
						if (res.success) setTimeout(function () { location.reload(); }, 700);
					})
					.catch(function () {
						if (msg) { msg.textContent = 'İstek başarısız'; msg.className = cfg.msg.replace('.', '') + ' small d-block mt-3 text-danger'; }
					})
					.finally(function () { setBusy(btn, false); });
			});
		});
	}

	function listingToast(msg, ok) {
		if (window.AdminToast && AdminToast.show) {
			AdminToast.show(msg, ok ? 'success' : 'error');
			return;
		}
		window.alert(msg);
	}

	function listingCard(el) {
		return el.closest('.mp-listing-card');
	}

	function bindListingMenus() {
		document.querySelectorAll('.js-listing-sale-toggle').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var card = listingCard(btn);
				var id = card && card.getAttribute('data-product-id');
				var paused = btn.getAttribute('data-paused') === '1';
				var url = window.trendyolSalePauseApiUrl || '';
				if (!id || !url) return;
				setBusy(btn, true);
				post(url, { id_product: id, paused: paused ? '1' : '0', platform: card.getAttribute('data-marketplace') || 'trendyol' })
					.then(function (res) {
						listingToast(res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 600);
					})
					.catch(function () { listingToast('İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.js-listing-stock').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var card = listingCard(btn);
				var id = card && card.getAttribute('data-product-id');
				var marketplace = card ? (card.getAttribute('data-marketplace') || 'trendyol') : 'trendyol';
				if (btn.getAttribute('data-variants') === '1') {
					var url = window.trendyolPriceApiUrl || '';
					if (!id || !url) return;
					setBusy(btn, true);
					post(url, { id_product: id, platform: marketplace })
						.then(function (res) {
							listingToast(res.message || (res.success ? 'Stok gönderildi' : 'Hata'), !!res.success);
						})
						.catch(function () { listingToast('İstek başarısız', false); })
						.finally(function () { setBusy(btn, false); });
					return;
				}
				var stockBtn = document.querySelector('.quick-stock-btn[data-id="' + id + '"]');
				if (stockBtn) stockBtn.click();
			});
		});

		document.querySelectorAll('.js-listing-refresh').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var card = listingCard(btn);
				var id = card && card.getAttribute('data-product-id');
				var url = window.trendyolRefreshApiUrl || '';
				if (!id || !url) return;
				setBusy(btn, true);
				post(url, { id_product: id })
					.then(function (res) {
						listingToast(res.message || '', !!res.success);
						if (res.success) setTimeout(function () { location.reload(); }, 600);
					})
					.catch(function () { listingToast('İstek başarısız', false); })
					.finally(function () { setBusy(btn, false); });
			});
		});

		document.querySelectorAll('.js-listing-unlink').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				var card = listingCard(btn);
				var id = card && card.getAttribute('data-product-id');
				var marketplace = card ? (card.getAttribute('data-marketplace') || 'trendyol') : 'trendyol';
				var url = window.trendyolUnlinkApiUrl || '';
				if (!id || !url) return;
				var message = 'Bağlantı silinecek. Ürün pazaryerinde kalır; yalnızca FShop eşlemesi kaldırılır.';
				var ask = window.AdminConfirm && AdminConfirm.ask
					? AdminConfirm.ask({ message: message })
					: Promise.resolve(window.confirm(message));
				ask.then(function (ok) {
					if (!ok) return;
					setBusy(btn, true);
					post(url, { id_product: id, platform: marketplace })
						.then(function (res) {
							listingToast(res.message || '', !!res.success);
							if (res.success) setTimeout(function () { location.reload(); }, 600);
						})
						.catch(function () { listingToast('İstek başarısız', false); })
						.finally(function () { setBusy(btn, false); });
				});
			});
		});

		document.querySelectorAll('.js-listing-open').forEach(function (btn) {
			if (btn.dataset.tyBound) return;
			btn.dataset.tyBound = '1';
			btn.addEventListener('click', function () {
				listingToast('Pazaryeri bağlantısı henüz yok. Önce “Güncel durum al” deneyin.', false);
			});
		});
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', function () {
			bindProductActions();
			bindListingMenus();
		});
	} else {
		bindProductActions();
		bindListingMenus();
	}

	window.TrendyolAdmin = {
		setSelected: setSelected,
		loadAttributesForPanel: loadAttributesForPanel,
		bindProductActions: bindProductActions
	};
})(window, document);
