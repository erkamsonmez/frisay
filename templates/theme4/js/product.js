function updateQty(val) {
	const input = document.getElementById('qty-input');
	if (!input) return;

	const max = parseInt(input.dataset.max, 10) || 99;
	let current = parseInt(input.value, 10) || 1;
	const next = current + val;

	if (next >= 1 && next <= max) {
		input.value = next;
	}
}

(function () {
	var mainImg = document.getElementById('main-display');
	var modalImg = document.getElementById('modal-display');
	var thumbs = Array.prototype.slice.call(document.querySelectorAll('.product-gallery__thumb'));
	var prevBtn = document.querySelector('.product-gallery__nav--prev');
	var nextBtn = document.querySelector('.product-gallery__nav--next');
	var modalPrev = document.getElementById('imageModalPrev');
	var modalNext = document.getElementById('imageModalNext');
	var modalCounter = document.getElementById('imageModalCounter');
	var currentIndex = 0;

	function getImageUrls() {
		if (thumbs.length) {
			return thumbs.map(function (thumb) {
				return thumb.getAttribute('data-image') || '';
			}).filter(Boolean);
		}

		return mainImg && mainImg.src ? [mainImg.src] : [];
	}

	function setActiveIndex(index) {
		var urls = getImageUrls();
		if (!urls.length) {
			return;
		}

		if (index < 0) {
			index = urls.length - 1;
		}
		if (index >= urls.length) {
			index = 0;
		}

		currentIndex = index;

		if (mainImg && urls[index]) {
			mainImg.src = urls[index];
		}
		if (modalImg && urls[index]) {
			modalImg.src = urls[index];
		}

		thumbs.forEach(function (thumb, i) {
			thumb.classList.toggle('active', i === index);
		});

		if (modalCounter) {
			modalCounter.textContent = String(index + 1);
		}
	}

	thumbs.forEach(function (thumb, index) {
		thumb.addEventListener('click', function () {
			setActiveIndex(index);
		});
	});

	function bindNav(btn, delta) {
		if (!btn) {
			return;
		}
		btn.addEventListener('click', function (event) {
			event.preventDefault();
			event.stopPropagation();
			setActiveIndex(currentIndex + delta);
		});
	}

	bindNav(prevBtn, -1);
	bindNav(nextBtn, 1);
	bindNav(modalPrev, -1);
	bindNav(modalNext, 1);

	if (mainImg) {
		mainImg.addEventListener('click', function () {
			if (modalImg && mainImg.src) {
				modalImg.src = mainImg.src;
			}
		});
	}

	var imageModal = document.getElementById('imageModal');
	if (imageModal) {
		imageModal.addEventListener('show.bs.modal', function () {
			if (modalImg && mainImg && mainImg.src) {
				modalImg.src = mainImg.src;
			}
			if (modalCounter) {
				modalCounter.textContent = String(currentIndex + 1);
			}
		});

		imageModal.addEventListener('keydown', function (event) {
			if (getImageUrls().length < 2) {
				return;
			}
			if (event.key === 'ArrowLeft') {
				event.preventDefault();
				setActiveIndex(currentIndex - 1);
			} else if (event.key === 'ArrowRight') {
				event.preventDefault();
				setActiveIndex(currentIndex + 1);
			}
		});
	}

	document.addEventListener('keydown', function (event) {
		if (!imageModal || !imageModal.classList.contains('show') || getImageUrls().length < 2) {
			return;
		}
		if (event.key === 'ArrowLeft') {
			event.preventDefault();
			setActiveIndex(currentIndex - 1);
		} else if (event.key === 'ArrowRight') {
			event.preventDefault();
			setActiveIndex(currentIndex + 1);
		}
	});
})();

(function () {
	var root = document.getElementById('productVariations');
	var dataEl = document.getElementById('variationItemsData');

	if (!root || !dataEl) {
		return;
	}

	var items = [];
	try {
		items = JSON.parse(dataEl.textContent || '[]');
	} catch (e) {
		items = [];
	}

	var groups = Array.prototype.slice.call(root.querySelectorAll('.product-variation-group'));
	var requiredGroups = groups.length;
	var selected = {};
	var hidden = document.getElementById('selectedVariationId');
	var priceEl = document.getElementById('productCurrentPrice');
	var qtyInput = document.getElementById('qty-input');
	var qtyPicker = document.getElementById('qtyPicker');
	var addBtns = Array.prototype.slice.call(document.querySelectorAll('.addtocart.requires-variation'));
	var hint = document.getElementById('variationHint');
	var summaryEl = document.getElementById('variationSummary');
	var basePriceHtml = priceEl ? priceEl.innerHTML : '';
	var selectHint = root.getAttribute('data-select-hint') || 'LÃ¼tfen seÃ§enekleri belirleyin';
	var outHint = root.getAttribute('data-out-hint') || 'TÃ¼kendi';

	function t(key, fallback) {
		if (key === 'selectOptions') {
			return selectHint;
		}

		if (key === 'outOfStock') {
			return outHint;
		}

		return fallback || key;
	}

	function optionKeys(obj) {
		return Object.keys(obj || {});
	}

	function getGroupOrder() {
		return groups.map(function (groupEl) {
			var btn = groupEl.querySelector('.product-variation-option');

			return btn ? btn.getAttribute('data-group') : '';
		}).filter(Boolean);
	}

	function findMatch() {
		var selectedKeys = optionKeys(selected);

		if (selectedKeys.length < requiredGroups) {
			return null;
		}

		return items.find(function (item) {
			var opts = item.options || {};
			var keys = optionKeys(opts);

			if (keys.length !== selectedKeys.length) {
				return false;
			}

			for (var i = 0; i < keys.length; i++) {
				var key = keys[i];

				if (opts[key] !== selected[key]) {
					return false;
				}
			}

			return true;
		}) || null;
	}

	function isOptionAvailable(group, value) {
		var trial = Object.assign({}, selected);
		trial[group] = value;

		return items.some(function (item) {
			var opts = item.options || {};

			for (var key in trial) {
				if (trial[key] === '') {
					continue;
				}

				if (opts[key] !== trial[key]) {
					return false;
				}
			}

			return item.in_stock;
		});
	}

	function updateGroupLabel(group, value) {
		var label = root.querySelector('.product-variation-selected[data-group-label="' + group + '"]');

		if (label) {
			label.textContent = value ? ': ' + value : '';
		}
	}

	function clearInvalidSelections() {
		var order = getGroupOrder();
		var changed = false;

		order.forEach(function (group) {
			var value = selected[group];

			if (!value) {
				return;
			}

			if (!isOptionAvailable(group, value)) {
				delete selected[group];
				changed = true;
			}
		});

		return changed;
	}

	function refreshOptionStates() {
		root.querySelectorAll('.product-variation-option').forEach(function (btn) {
			var group = btn.getAttribute('data-group');
			var value = btn.getAttribute('data-value');
			var available = isOptionAvailable(group, value);
			var isSelected = selected[group] === value;

			btn.disabled = !available;
			btn.classList.toggle('active', isSelected);
			btn.setAttribute('aria-checked', isSelected ? 'true' : 'false');
		});

		getGroupOrder().forEach(function (group) {
			updateGroupLabel(group, selected[group] || '');
		});
	}

	function updateSummary(match) {
		if (!summaryEl) {
			return;
		}

		var parts = getGroupOrder().map(function (group) {
			return selected[group] || '';
		}).filter(Boolean);

		if (parts.length === 0) {
			summaryEl.classList.add('d-none');
			summaryEl.textContent = '';
			return;
		}

		summaryEl.classList.remove('d-none');
		summaryEl.textContent = parts.join(' / ');

		if (match && match.sku) {
			summaryEl.textContent += ' (' + match.sku + ')';
		}
	}

	function updateUI() {
		while (clearInvalidSelections()) {
			// BoÅŸaltÄ±lan seÃ§imler sonrasÄ± diÄŸer gruplarÄ± yeniden kontrol et
		}

		var match = findMatch();

		refreshOptionStates();

		if (match) {
			if (hidden) {
				hidden.value = String(match.id_variation);
			}

			if (priceEl && match.price_formatted) {
				priceEl.innerHTML = match.price_formatted;
			}

			if (qtyInput) {
				qtyInput.dataset.max = String(match.stock);
				var current = parseInt(qtyInput.value, 10) || 1;
				qtyInput.value = String(Math.min(Math.max(1, current), match.stock));
			}

			if (qtyPicker) {
				qtyPicker.classList.remove('d-none');
			}

			addBtns.forEach(function (addBtn) {
				addBtn.disabled = !match.in_stock;
				addBtn.dataset.variation = String(match.id_variation);
			});

			if (hint) {
				hint.textContent = match.in_stock ? '' : t('outOfStock', 'TÃ¼kendi');
			}

			updateSummary(match);
		} else {
			if (hidden) {
				hidden.value = '0';
			}

			if (priceEl) {
				priceEl.innerHTML = basePriceHtml;
			}

			if (qtyPicker) {
				qtyPicker.classList.add('d-none');
			}

			addBtns.forEach(function (addBtn) {
				addBtn.disabled = true;
				addBtn.dataset.variation = '0';
			});

			if (hint) {
				hint.textContent = optionKeys(selected).length > 0 && optionKeys(selected).length < requiredGroups
					? t('selectOptions', 'LÃ¼tfen tÃ¼m seÃ§enekleri belirleyin')
					: t('selectOptions', 'LÃ¼tfen seÃ§enekleri belirleyin');
			}

			updateSummary(null);
		}
	}

	root.addEventListener('click', function (event) {
		var btn = event.target.closest('.product-variation-option');

		if (!btn || btn.disabled) {
			return;
		}

		var group = btn.getAttribute('data-group');
		var value = btn.getAttribute('data-value');

		if (!group || !value) {
			return;
		}

		selected[group] = value;
		updateUI();
	});

	updateUI();
})();

(function () {
	'use strict';

	var root = document.querySelector('[data-qty-tiers]');
	if (!root) {
		return;
	}

	var buttons = Array.prototype.slice.call(root.querySelectorAll('[data-tier-qty]'));
	var qtyInput = document.getElementById('qty-input');

	function parseQty() {
		if (!qtyInput) {
			return 1;
		}
		var n = parseFloat(String(qtyInput.value || '1').replace(',', '.'));
		return isNaN(n) || n < 1 ? 1 : n;
	}

	function setQty(qty) {
		if (!qtyInput) {
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

	function syncActive() {
		var qty = parseQty();
		var best = null;
		buttons.forEach(function (btn) {
			var min = parseInt(btn.getAttribute('data-tier-qty') || '0', 10);
			if (min <= qty && (!best || min > parseInt(best.getAttribute('data-tier-qty') || '0', 10))) {
				best = btn;
			}
		});
		buttons.forEach(function (btn) {
			btn.classList.toggle('is-active', btn === best);
		});
	}

	buttons.forEach(function (btn) {
		btn.addEventListener('click', function () {
			var min = parseInt(btn.getAttribute('data-tier-qty') || '1', 10);
			setQty(min);
			syncActive();
		});
	});

	if (qtyInput) {
		qtyInput.addEventListener('change', syncActive);
		qtyInput.addEventListener('input', syncActive);
	}

	syncActive();
})();

(function () {
	'use strict';

	document.querySelectorAll('[data-t4-rail]').forEach(function (rail) {
		if (rail.getAttribute('data-t4-rail-ready') === '1') {
			return;
		}
		rail.setAttribute('data-t4-rail-ready', '1');

		var track = rail.querySelector('.t4-rail__track') || rail;
		var section = rail.closest('[data-t4-rail-section]') || rail.parentElement;
		var prev = section ? section.querySelector('[data-t4-rail-prev]') : null;
		var next = section ? section.querySelector('[data-t4-rail-next]') : null;

		function cardStep() {
			var card = track.querySelector('.t4-rail__card');
			if (!card) {
				return Math.max(160, track.clientWidth * 0.2);
			}
			var style = window.getComputedStyle(track);
			var gap = parseFloat(style.columnGap || style.gap) || 14;
			return card.getBoundingClientRect().width + gap;
		}

		function syncButtons() {
			var max = Math.max(0, track.scrollWidth - track.clientWidth - 2);
			if (prev) {
				prev.disabled = track.scrollLeft <= 2;
			}
			if (next) {
				next.disabled = track.scrollLeft >= max;
			}
		}

		if (prev) {
			prev.addEventListener('click', function () {
				track.scrollBy({ left: -cardStep(), behavior: 'smooth' });
			});
		}
		if (next) {
			next.addEventListener('click', function () {
				track.scrollBy({ left: cardStep(), behavior: 'smooth' });
			});
		}
		track.addEventListener('scroll', syncButtons, { passive: true });
		window.addEventListener('resize', syncButtons);
		syncButtons();
	});
})();

(function () {
	var bar = document.getElementById('t4StickyBuy');
	var trigger = document.querySelector('.t4-product__cart.addtocart')
		|| document.querySelector('.t4-product__actions .addtocart')
		|| document.querySelector('.t4-product__actions');
	if (!bar || !trigger) {
		return;
	}

	var stickyImg = document.getElementById('t4StickyBuyImg');
	var stickyPrice = document.getElementById('t4StickyBuyPrice');
	var mainImg = document.getElementById('main-display');
	var priceEl = document.getElementById('productCurrentPrice');
	var stickyCart = bar.querySelector('.t4-sticky-buy__cart');
	var mainCart = document.querySelector('.t4-product__cart.addtocart') || trigger;
	var ticking = false;

	function syncStickyMeta() {
		if (stickyImg && mainImg && mainImg.src) {
			stickyImg.src = mainImg.src;
		}
		if (stickyPrice && priceEl) {
			stickyPrice.textContent = (priceEl.textContent || '').trim();
		}
		if (stickyCart && mainCart) {
			stickyCart.setAttribute('data-variation', mainCart.getAttribute('data-variation') || '0');
			stickyCart.disabled = !!mainCart.disabled;
		}
	}

	function setVisible(on) {
		bar.classList.toggle('is-visible', !!on);
		bar.setAttribute('aria-hidden', on ? 'false' : 'true');
	}

	function updateSticky() {
		ticking = false;
		var rect = trigger.getBoundingClientRect();
		// Sepete ekle butonu ekranın üstünden tamamen çıkınca şerit gelsin
		var shouldShow = rect.bottom <= 0;
		setVisible(shouldShow);
		if (shouldShow) {
			syncStickyMeta();
		}
	}

	function onScrollOrResize() {
		if (ticking) {
			return;
		}
		ticking = true;
		window.requestAnimationFrame(updateSticky);
	}

	window.addEventListener('scroll', onScrollOrResize, { passive: true });
	window.addEventListener('resize', onScrollOrResize);
	updateSticky();

	if (priceEl && typeof MutationObserver !== 'undefined') {
		new MutationObserver(syncStickyMeta).observe(priceEl, { childList: true, characterData: true, subtree: true });
	}
	if (mainImg && typeof MutationObserver !== 'undefined') {
		new MutationObserver(syncStickyMeta).observe(mainImg, { attributes: true, attributeFilter: ['src'] });
	}
	if (mainCart && typeof MutationObserver !== 'undefined') {
		new MutationObserver(syncStickyMeta).observe(mainCart, { attributes: true, attributeFilter: ['disabled', 'data-variation'] });
	}

	syncStickyMeta();
})();
