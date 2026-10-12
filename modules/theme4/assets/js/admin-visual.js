(function () {
	'use strict';

	var modalEl = document.getElementById('t4EditModal');
	var source = document.querySelector('.t4-visual-source');
	var chromePanel = document.getElementById('t4ChromePanel');
	var drawerTitle = document.querySelector('[data-t4-drawer-title]');
	var sectionActions = document.querySelector('[data-t4-section-actions]');
	var frame = document.getElementById('t4LiveFrame');
	var selected = { zone: '', row: '', widget: '', chrome: '' };
	var modalSnapshot = null;
	var modalDidSave = false;
	var modalKeepChanges = false;

	if (!modalEl || !source) {
		return;
	}

	function getModal() {
		if (!window.bootstrap || !bootstrap.Modal) {
			return null;
		}
		return bootstrap.Modal.getOrCreateInstance(modalEl);
	}

	function builder(zone) {
		return document.querySelector('[data-t4-builder="' + zone + '"]');
	}

	function getPreviewConfig() {
		var el = document.getElementById('t4PreviewConfig');
		if (!el) return null;
		try {
			return JSON.parse(el.textContent || '{}');
		} catch (err) {
			return null;
		}
	}

	function setSectionActions(visible) {
		if (sectionActions) {
			sectionActions.hidden = !visible;
		}
	}

	function setChromeSection(kind) {
		if (!chromePanel) {
			return;
		}
		chromePanel.hidden = !kind;
		chromePanel.querySelectorAll('[data-t4-chrome-section]').forEach(function (el) {
			el.hidden = kind ? el.getAttribute('data-t4-chrome-section') !== kind : true;
		});
	}

	function clearInspect() {
		document.querySelectorAll('.is-inspecting, .is-inspect-row, .t4-inspect-mode, .t4-inspect-container').forEach(function (el) {
			el.classList.remove('is-inspecting', 'is-inspect-row', 't4-inspect-mode', 't4-inspect-container');
		});
		source.classList.remove('is-open', 'is-colors', 'is-custom', 'is-footer', 'is-header', 'is-home', 'is-chrome', 'is-theme');
		setChromeSection('');
		setSectionActions(false);
	}

	function openModal(title) {
		pauseLivePreview();
		source.classList.add('is-open');
		if (drawerTitle) {
			drawerTitle.textContent = title || 'Düzenle';
		}
		var modal = getModal();
		if (modal) {
			modal.show();
		}
	}

	function closeModal() {
		clearInspect();
		selected = { zone: '', row: '', widget: '', chrome: '' };
		var modal = getModal();
		if (modal) {
			modal.hide();
		}
	}

	function cloneJson(value) {
		try {
			return JSON.parse(JSON.stringify(value));
		} catch (err) {
			return value;
		}
	}

	function takeModalSnapshot() {
		var chrome = {};
		document.querySelectorAll('[data-t4-chrome-f]').forEach(function (el) {
			var key = el.getAttribute('data-t4-chrome-f');
			if (key) {
				chrome[key] = el.value;
			}
		});
		var layouts = {};
		document.querySelectorAll('[data-t4-builder]').forEach(function (el) {
			var id = el.getAttribute('data-t4-builder');
			layouts[id] = {
				layout: typeof el._t4Serialize === 'function' ? cloneJson(el._t4Serialize()) : { rows: [] },
				dirty: !!el._t4Dirty
			};
		});
		var colors = {};
		document.querySelectorAll('input[name^="colors["]').forEach(function (input) {
			colors[input.name] = input.value;
		});
		var copyEl = document.getElementById('t4CopyrightInput');
		var copyChrome = document.getElementById('t4CopyrightChrome');
		var logoEl = document.getElementById('t4LogoInput');
		var favEl = document.getElementById('t4FaviconInput');
		return {
			chrome: chrome,
			layouts: layouts,
			colors: colors,
			copyright: copyEl ? copyEl.value : '',
			copyrightChrome: copyChrome ? copyChrome.value : '',
			logo: logoEl ? logoEl.value : '',
			favicon: favEl ? favEl.value : ''
		};
	}

	function restoreModalSnapshot(snap) {
		if (!snap) {
			return;
		}
		document.querySelectorAll('[data-t4-chrome-f]').forEach(function (el) {
			var key = el.getAttribute('data-t4-chrome-f');
			if (key && Object.prototype.hasOwnProperty.call(snap.chrome, key)) {
				el.value = snap.chrome[key];
			}
		});
		document.querySelectorAll('[data-t4-builder]').forEach(function (el) {
			var id = el.getAttribute('data-t4-builder');
			var saved = snap.layouts[id];
			if (!saved) {
				return;
			}
			if (typeof el._t4Load === 'function') {
				el._t4Load(saved.layout);
			}
			el._t4Dirty = !!saved.dirty;
		});
		document.querySelectorAll('input[name^="colors["]').forEach(function (input) {
			if (Object.prototype.hasOwnProperty.call(snap.colors, input.name)) {
				input.value = snap.colors[input.name];
				var picker = document.querySelector('[data-t4-color-picker][data-target="' + input.id + '"]');
				if (picker && /^#[0-9a-fA-F]{6}$/.test(input.value)) {
					picker.value = input.value;
				}
			}
		});
		var copyEl = document.getElementById('t4CopyrightInput');
		var copyChrome = document.getElementById('t4CopyrightChrome');
		if (copyEl) copyEl.value = snap.copyright;
		if (copyChrome) copyChrome.value = snap.copyrightChrome;
		var logoEl = document.getElementById('t4LogoInput');
		var favEl = document.getElementById('t4FaviconInput');
		var logoPreview = document.getElementById('t4LogoPreview');
		var favPreview = document.getElementById('t4FaviconPreview');
		if (logoEl && Object.prototype.hasOwnProperty.call(snap, 'logo')) {
			logoEl.value = snap.logo;
			if (logoPreview) {
				if (snap.logo) {
					logoPreview.src = snap.logo;
					logoPreview.classList.remove('d-none');
				} else {
					logoPreview.removeAttribute('src');
					logoPreview.classList.add('d-none');
				}
			}
		}
		if (favEl && Object.prototype.hasOwnProperty.call(snap, 'favicon')) {
			favEl.value = snap.favicon;
			if (favPreview) {
				if (snap.favicon) {
					favPreview.src = snap.favicon;
					favPreview.classList.remove('d-none');
				} else {
					favPreview.removeAttribute('src');
					favPreview.classList.add('d-none');
				}
			}
		}
	}

	function pauseLivePreview() {
		if (typeof window.t4PauseLivePreview === 'function') {
			window.t4PauseLivePreview();
		} else {
			window.t4PreviewPaused = true;
		}
	}

	function resumeLivePreview() {
		if (typeof window.t4ResumeLivePreview === 'function') {
			window.t4ResumeLivePreview();
		} else {
			window.t4PreviewPaused = false;
		}
	}

	function isContainerRow(row) {
		if (!row) {
			return false;
		}
		if (row.classList && row.classList.contains('t4-container')) {
			return true;
		}
		return !!(row._row && /(^|\s)t4-container(\s|$)/.test(String(row._row.class || '')));
	}

	function inspectContainer(zone, rowId) {
		clearInspect();
		var root = builder(zone);
		if (!root) {
			return;
		}
		var row = typeof root._t4FindRow === 'function' ? root._t4FindRow(rowId) : null;
		if (!row && rowId) {
			row = root.querySelector('[data-row-id="' + String(rowId).replace(/"/g, '') + '"]');
		}
		if (!row) {
			return;
		}
		source.classList.add('is-' + zone);
		row.classList.add('is-inspect-row');
		root.classList.add('t4-inspect-mode', 't4-inspect-container');
		setSectionActions(true);
		openModal('Container');
		selected = { zone: zone, row: row.getAttribute('data-row-id') || rowId || '', widget: '', chrome: '' };
	}

	function inspectWidget(zone, widgetId, rowId) {
		var root = builder(zone);
		if (!root) {
			return;
		}
		var card = typeof root._t4FindWidget === 'function' ? root._t4FindWidget(widgetId) : null;
		var row = card ? card.closest('[data-row]') : null;
		if (!row && rowId && typeof root._t4FindRow === 'function') {
			row = root._t4FindRow(rowId);
		}
		if (isContainerRow(row)) {
			inspectContainer(zone, row.getAttribute('data-row-id') || rowId);
			return;
		}

		clearInspect();
		source.classList.add('is-' + zone);
		if (card) {
			card.classList.add('is-inspecting');
			if (row) {
				row.classList.add('is-inspect-row');
				rowId = row.getAttribute('data-row-id') || rowId;
			}
		}

		if (zone === 'home' && widgetId) {
			root.classList.add('t4-inspect-mode');
			setSectionActions(false);
		}

		var title = 'Bölüm';
		if (zone === 'footer') title = 'Footer';
		if (zone === 'header') title = 'Header';
		openModal(title);
		selected = { zone: zone, row: rowId || '', widget: widgetId || '', chrome: '' };

		if (card && zone !== 'home') {
			try {
				card.scrollIntoView({ block: 'nearest' });
			} catch (err) {}
		}

		if (frame && frame.contentWindow && widgetId) {
			try {
				frame.contentWindow.postMessage({ source: 't4-admin', op: 'highlight', widget: widgetId }, '*');
			} catch (err2) {}
		}
	}

	function inspectColors() {
		clearInspect();
		source.classList.add('is-colors');
		setSectionActions(false);
		openModal('Renkler');
		selected = { zone: 'colors', row: '', widget: '', chrome: '' };
	}

	function inspectCustom() {
		clearInspect();
		source.classList.add('is-custom');
		setSectionActions(false);
		openModal('Özel');
		selected = { zone: 'custom', row: '', widget: '', chrome: '' };
	}

	function inspectTheme() {
		clearInspect();
		source.classList.add('is-theme');
		setSectionActions(false);
		openModal('Logo');
		selected = { zone: 'theme', row: '', widget: '', chrome: '' };
	}

	function inspectChrome(kind) {
		clearInspect();
		source.classList.add('is-chrome');
		var titles = {
			trust: 'Güven şeridi',
			footer: 'Footer',
			header: 'Üst yazılar'
		};
		if (kind !== 'trust' && kind !== 'footer') {
			kind = 'header';
		}
		setChromeSection(kind);
		setSectionActions(false);
		openModal(titles[kind] || 'Düzenle');
		selected = { zone: 'chrome', row: '', widget: '', chrome: kind };
	}

	modalEl.addEventListener('show.bs.modal', function () {
		pauseLivePreview();
		modalDidSave = false;
		modalKeepChanges = false;
		modalSnapshot = takeModalSnapshot();
	});

	modalEl.addEventListener('hidden.bs.modal', function () {
		if (!modalDidSave && !modalKeepChanges) {
			restoreModalSnapshot(modalSnapshot);
		}
		modalSnapshot = null;
		modalDidSave = false;
		modalKeepChanges = false;
		resumeLivePreview();
		clearInspect();
		selected = { zone: '', row: '', widget: '', chrome: '' };
	});

	window.addEventListener('message', function (e) {
		var data = e.data;
		if (!data || data.source !== 't4-preview' || data.op !== 'select') {
			return;
		}
		if (data.chrome === 'logo' || data.chrome === 'theme') {
			inspectTheme();
			return;
		}
		if (data.chrome === 'header' || data.chrome === 'trust' || data.chrome === 'footer') {
			inspectChrome(data.chrome);
			return;
		}
		var zone = data.zone || 'home';
		if (zone !== 'home' && zone !== 'header' && zone !== 'footer') {
			zone = 'home';
		}
		if (zone === 'footer' && !data.widget && !data.row) {
			inspectChrome('footer');
			return;
		}
		if (data.widget) {
			inspectWidget(zone, data.widget, data.row);
			return;
		}
		if (data.row) {
			var hitRoot = builder(zone);
			var hitRow = hitRoot && typeof hitRoot._t4FindRow === 'function'
				? hitRoot._t4FindRow(data.row)
				: null;
			if (isContainerRow(hitRow)) {
				inspectContainer(zone, data.row);
				return;
			}
		}
		if (zone === 'header') {
			inspectChrome('header');
		}
	});

	document.querySelectorAll('[data-t4-add-section]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var type = btn.getAttribute('data-t4-add-section') || 'category_products';
			var root = builder('home');
			if (!root) {
				return;
			}
			root._t4Dirty = true;
			if (type === 'container') {
				if (typeof root._t4AddContainerRow !== 'function') {
					return;
				}
				var rowId = root._t4AddContainerRow();
				inspectContainer('home', rowId);
				return;
			}
			if (typeof root._t4AddFullRowWidget !== 'function') {
				return;
			}
			var id = root._t4AddFullRowWidget(type);
			inspectWidget('home', id, '');
		});
	});

	document.querySelectorAll('[data-t4-panel]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var panel = btn.getAttribute('data-t4-panel');
			if (panel === 'colors') {
				inspectColors();
			} else if (panel === 'custom') {
				inspectCustom();
			} else if (panel === 'theme') {
				inspectTheme();
			} else if (panel === 'chrome-header') {
				inspectChrome('header');
			} else if (panel === 'chrome-trust') {
				inspectChrome('trust');
			} else if (panel === 'chrome-footer') {
				inspectChrome('footer');
			}
		});
	});

	document.querySelectorAll('[data-t4-move]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			if (!selected.zone || !selected.row) {
				return;
			}
			var root = builder(selected.zone);
			if (root && typeof root._t4MoveRow === 'function') {
				root._t4Dirty = true;
				root._t4MoveRow(selected.row, btn.getAttribute('data-t4-move'));
			}
		});
	});

	var delBtn = document.querySelector('[data-t4-delete-section]');
	if (delBtn) {
		delBtn.addEventListener('click', function () {
			if (!selected.zone) {
				return;
			}
			var root = builder(selected.zone);
			if (!root) {
				return;
			}
			if (selected.row && (!selected.widget || isContainerRow(root._t4FindRow ? root._t4FindRow(selected.row) : null))) {
				if (typeof root._t4DeleteRow === 'function') {
					root._t4Dirty = true;
					root._t4DeleteRow(selected.row);
					modalKeepChanges = true;
					closeModal();
					return;
				}
			}
			if (!selected.widget) {
				return;
			}
			if (typeof root._t4DeleteWidget === 'function') {
				root._t4Dirty = true;
				root._t4DeleteWidget(selected.widget);
				modalKeepChanges = true;
				closeModal();
			}
		});
	}

	function appendChrome(payload) {
		if (typeof window.t4AppendChrome === 'function') {
			window.t4AppendChrome(payload);
			return;
		}
		document.querySelectorAll('[data-t4-chrome-f]').forEach(function (el) {
			var key = el.getAttribute('data-t4-chrome-f');
			if (key) {
				payload.append('chrome[' + key + ']', el.value || '');
			}
		});
	}

	function publishLayout(done) {
		var cfg = getPreviewConfig();
		if (!cfg || !cfg.ajaxUrl) {
			if (done) done(false);
			return;
		}
		var payload = new FormData();
		payload.append('token', cfg.token || '');
		payload.append('ajax', '1');
		payload.append('action', 'publish');
		document.querySelectorAll('[data-t4-builder]').forEach(function (el) {
			var id = el.getAttribute('data-t4-builder');
			var publishedEmpty = el.getAttribute('data-published-empty') === '1';
			var layout = { rows: [] };
			if (publishedEmpty && !el._t4Dirty) {
				layout = { rows: [] };
			} else if (typeof el._t4Serialize === 'function') {
				layout = el._t4Serialize();
			}
			payload.append(id, JSON.stringify(layout));
		});
		var copyEl = document.getElementById('t4CopyrightInput');
		var copyChrome = document.getElementById('t4CopyrightChrome');
		if (copyChrome && copyEl) {
			copyEl.value = copyChrome.value;
		}
		payload.append('copyright', copyEl ? copyEl.value : (copyChrome ? copyChrome.value : ''));
		appendChrome(payload);
		document.querySelectorAll('input[name^="colors["]').forEach(function (input) {
			payload.append(input.name, input.value);
		});
		var customRoot = document.getElementById('t4TabCustom');
		if (customRoot) {
			var widthEl = customRoot.querySelector('[name="site_width"]');
			var fontEl = customRoot.querySelector('[name="font_family"]');
			var cssEl = customRoot.querySelector('[name="custom_css"]');
			var jsEl = customRoot.querySelector('[name="custom_js"]');
			if (widthEl) payload.append('site_width', widthEl.value || '');
			if (fontEl) payload.append('font_family', fontEl.value || '');
			if (cssEl) payload.append('custom_css', cssEl.value || '');
			if (jsEl) payload.append('custom_js', jsEl.value || '');
		}
		var logoEl = document.getElementById('t4LogoInput');
		var favEl = document.getElementById('t4FaviconInput');
		if (logoEl) payload.append('logo', logoEl.value || '');
		if (favEl) payload.append('favicon', favEl.value || '');
		var status = document.querySelector('[data-t4-preview-status]');
		if (status) status.textContent = '…';
		fetch(cfg.ajaxUrl, {
			method: 'POST',
			body: payload,
			credentials: 'same-origin',
			headers: {
				'X-Requested-With': 'XMLHttpRequest',
				'Accept': 'application/json'
			}
		}).then(function (res) {
			if (!res.ok) {
				throw new Error('save failed');
			}
			return res.json();
		}).then(function (data) {
			var ok = !!(data && data.ok);
			if (status) {
				status.textContent = ok ? 'Kaydedildi' : '';
			}
			if (ok && frame && cfg.previewUrl) {
				var sep = cfg.previewUrl.indexOf('?') >= 0 ? '&' : '?';
				frame.src = cfg.previewUrl + sep + '_=' + Date.now();
			}
			if (done) done(ok);
		}).catch(function () {
			if (status) status.textContent = '';
			if (done) done(false);
		});
	}

	var publishBtn = document.querySelector('[data-t4-publish]');
	if (publishBtn) {
		publishBtn.addEventListener('click', function () {
			publishLayout();
		});
	}

	var modalSave = document.querySelector('[data-t4-modal-save]');
	if (modalSave) {
		modalSave.addEventListener('click', function () {
			modalSave.disabled = true;
			publishLayout(function (ok) {
				modalSave.disabled = false;
				if (ok) {
					modalDidSave = true;
					closeModal();
				}
			});
		});
	}
})();
