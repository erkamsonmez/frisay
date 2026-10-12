$(document).ready(function () {
	if (window.history.replaceState) {
		window.history.replaceState(null, null, window.location.href);
	}
});

function toggleSearch() {
	const searchOverlay = document.getElementById('searchOverlay');
	searchOverlay.classList.toggle('active');
	if (searchOverlay.classList.contains('active')) {
		searchOverlay.querySelector('input').focus();
	}
}

document.addEventListener('DOMContentLoaded', function () {
	if ('IntersectionObserver' in window) {
		const imageObserver = new IntersectionObserver((entries, observer) => {
			entries.forEach(entry => {
				if (entry.isIntersecting) {
					const img = entry.target;
					loadImage(img);
					observer.unobserve(img);
				}
			});
		}, {
			rootMargin: '100px',
			threshold: 0.01
		});

		document.querySelectorAll('img.lazy').forEach(img => {
			imageObserver.observe(img);
		});
	} else {
		loadImagesOnScroll();
	}
});

function loadImage(img) {
	const src = img.getAttribute('data-src');
	if (!src) return;

	const newImg = new Image();
	newImg.onload = function () {
		img.src = src;
		img.classList.add('loaded');
		img.classList.remove('lazy');
	};
	newImg.onerror = function () {
		img.src = '/images/placeholder-error.jpg';
		img.classList.add('error');
	};
	newImg.src = src;
}

function scrollContent(id, direction) {
	const container = document.getElementById(id);
	const scrollAmount = 300;

	if (direction === 'left') {
		container.scrollBy({ left: -scrollAmount, behavior: 'smooth' });
	} else {
		container.scrollBy({ left: scrollAmount, behavior: 'smooth' });
	}
}

function escapeHtml(text) {
	return $('<div>').text(text).html();
}

function cartT(key) {
	var i18n = window.cartI18n || {};

	return i18n[key] || key;
}

function showCart() {
	hideCartAdded();
	$('#cartOverlay, #cartPanel').addClass('show');
	$('#cartOverlay').attr('aria-hidden', 'false');
	$('body').addClass('cart-modal-open');
}

function hideCart() {
	$('#cartOverlay, #cartPanel').removeClass('show');
	$('#cartOverlay').attr('aria-hidden', 'true');
	if (!$('#cartAddedModal').hasClass('show')) {
		$('body').removeClass('cart-modal-open');
	}
}

function showCartAdded(data, idProduct, idVariation) {
	hideCart();

	var items = data.items || [];
	var item = null;
	var i;
	var pid = parseInt(idProduct, 10) || 0;
	var vid = parseInt(idVariation, 10) || 0;

	for (i = 0; i < items.length; i++) {
		if (parseInt(items[i].id_product, 10) === pid && (parseInt(items[i].id_variation, 10) || 0) === vid) {
			item = items[i];
			break;
		}
	}
	if (!item && items.length) {
		item = items[items.length - 1];
	}

	var count = data.count || 0;
	var countTpl = cartT('cartItemsCount') || 'There are %d items in your cart.';
	var label = cartT('productLabel') || 'Product';
	var name = item ? item.product_name : '';
	var price = item ? (item.price_formatted || '') : '';
	var image = item ? (item.image_url || '') : '';

	$('.sm-cart-added__lead').text(cartT('productAddedLead'));
	$('#cartAddedName').text(label + ': ' + name);
	$('#cartAddedPrice').text(price);
	$('#cartAddedImage').attr('src', image).attr('alt', name);
	$('#cartAddedCount').text(countTpl.replace('%d', String(count)));
	$('#cartAddedGoCart').text(cartT('goToCart'));
	$('#cartAddedContinue').text(cartT('continueShopping'));

	$('#cartAddedModal').prop('hidden', false).addClass('show');
	$('#cartAddedOverlay').addClass('show').attr('aria-hidden', 'false');
	$('body').addClass('cart-modal-open');
}

function hideCartAdded() {
	$('#cartAddedModal').removeClass('show').prop('hidden', true);
	$('#cartAddedOverlay').removeClass('show').attr('aria-hidden', 'true');
	if (!$('#cartPanel').hasClass('show')) {
		$('body').removeClass('cart-modal-open');
	}
}

function renderCartItems(items) {
	if (!items || !items.length) {
		return (
			'<div class="sm-cart-empty">' +
				'<svg xmlns="http://www.w3.org/2000/svg" width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>' +
				'<p>' + escapeHtml(cartT('empty')) + '</p>' +
				'<a href="' + domain + '" class="sm-cart-btn sm-cart-btn--primary">' + escapeHtml(cartT('startShopping')) + '</a>' +
			'</div>'
		);
	}

	return items.map(function (item) {
		var cartKey = item.cart_key || '';
		var qtyStep = item.qty_step || 1;
		var qtyText = (item.sale_unit === 'm2')
			? (Math.round(parseFloat(item.qty) * 1000) / 1000).toString()
			: item.qty;
		return (
			'<div class="sm-cart-item cart-item" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" data-max-qty="' + (item.max_qty || item.stock || 99) + '" data-qty-step="' + qtyStep + '">' +
				'<a href="' + item.url + '" class="sm-cart-item__thumb cart-item-image">' +
					'<img src="' + item.image_url + '" alt="' + escapeHtml(item.product_name) + '">' +
				'</a>' +
				'<div class="sm-cart-item__content cart-item-info">' +
					'<div class="sm-cart-item__top">' +
						'<div class="sm-cart-item__details">' +
							'<a href="' + item.url + '" class="sm-cart-item__name cart-item-name">' + escapeHtml(item.product_name) + '</a>' +
							'<button type="button" class="sm-cart-item__remove cart-remove-btn" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '">' +
								escapeHtml(cartT('remove')) +
							'</button>' +
						'</div>' +
						'<div class="sm-cart-item__price cart-item-price">' + item.price_formatted + '</div>' +
					'</div>' +
					'<div class="sm-cart-item__bottom">' +
						'<span class="sm-cart-item__line cart-item-total">' + escapeHtml(cartT('total')) + ': ' + item.line_total_formatted + '</span>' +
						'<div class="sm-cart-item__qty cart-item-actions">' +
							'<button type="button" class="cart-qty-btn" data-action="decrease" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" aria-label="' + escapeHtml(cartT('decrease')) + '">-</button>' +
							'<span class="cart-qty-value">' + qtyText + '</span>' +
							'<button type="button" class="cart-qty-btn" data-action="increase" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-cart-key="' + escapeHtml(cartKey) + '" aria-label="' + escapeHtml(cartT('increase')) + '">+</button>' +
						'</div>' +
					'</div>' +
				'</div>' +
			'</div>'
		);
	}).join('');
}

function renderCartPageItems(items) {
	if (!items || !items.length) {
		return '';
	}

	return items.map(function (item) {
		var variationMeta = item.variation_label
			? '<p class="prime-cart-card__meta">' + escapeHtml(item.variation_label) + '</p>'
			: '';

		return (
			'<article class="prime-cart-card cart-item" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" data-max-qty="' + (item.max_qty || item.stock || 99) + '">' +
				'<a href="' + item.url + '" class="prime-cart-card__thumb cart-item-image">' +
					'<img src="' + item.image_url + '" alt="' + escapeHtml(item.product_name) + '">' +
				'</a>' +
				'<div class="prime-cart-card__body cart-item-info">' +
					'<a href="' + item.url + '" class="prime-cart-card__name cart-item-name">' + escapeHtml(item.product_name) + '</a>' +
					variationMeta +
					'<div class="prime-cart-card__unit cart-item-price">' + item.price_formatted + '</div>' +
					'<div class="prime-cart-card__actions cart-item-actions">' +
						'<div class="prime-cart-qty">' +
							'<button type="button" class="cart-qty-btn" data-action="decrease" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" aria-label="' + escapeHtml(cartT('decrease')) + '">−</button>' +
							'<span class="cart-qty-value">' + item.qty + '</span>' +
							'<button type="button" class="cart-qty-btn" data-action="increase" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '" aria-label="' + escapeHtml(cartT('increase')) + '">+</button>' +
						'</div>' +
						'<button type="button" class="prime-cart-card__remove cart-remove-btn" data-id="' + item.id_product + '" data-variation="' + (item.id_variation || 0) + '">' + escapeHtml(cartT('remove')) + '</button>' +
					'</div>' +
				'</div>' +
				'<div class="prime-cart-card__total cart-item-total">' +
					'<span class="prime-cart-card__total-label">' + escapeHtml(cartT('total')) + '</span>' +
					'<strong>' + item.line_total_formatted + '</strong>' +
				'</div>' +
			'</article>'
		);
	}).join('');
}

function updateCartUI(data) {
	var count = data.count || 0;

	if ($('#cartPageList').length) {
		if (!data.items || !data.items.length) {
			location.reload();
			return;
		}
		$('#cartPageList').html(renderCartPageItems(data.items));
	}

	if ($('#cartBody').length) {
		$('#cartBody').html(renderCartItems(data.items));
	}

	$('#cartSubtotal, #cartPageSubtotal').text(data.subtotal_formatted || data.total_formatted || '');
	var $promoLines = $('#cartPromotionLines');
	if ($promoLines.length) {
		if (data.promotion_lines && data.promotion_lines.length) {
			$promoLines.html(data.promotion_lines.map(function (line) {
				return '<div class="prime-cart-summary__row prime-cart-summary__row--promo"><span>' +
					$('<div>').text(line.name || '').html() +
					'</span><span>-' + (line.discount_formatted || '') + '</span></div>';
			}).join(''));
		} else if ((data.promotion_discount || 0) > 0) {
			$promoLines.html(
				'<div class="prime-cart-summary__row prime-cart-summary__row--promo"><span>' +
				$('<div>').text(data.promotion_name || '').html() +
				'</span><span>-' + (data.promotion_discount_formatted || '') + '</span></div>'
			);
		} else {
			$promoLines.empty();
		}
	} else if (data.promotion_discount > 0) {
		$('.prime-cart-summary__row--promo').removeClass('d-none');
		$('#cartPagePromotionName').text(data.promotion_name || '');
		$('#cartPagePromotion').text('-' + (data.promotion_discount_formatted || ''));
	} else {
		$('.prime-cart-summary__row--promo').addClass('d-none');
	}
	$('#cartShipping, #cartPageShipping').text(data.shipping_formatted || cartT('free'));
	$('#cartTotal, #cartPageTotal, #cartStickyTotal').text(data.grand_total_formatted || data.total_formatted || '');

	$('#cartCount, #cartCountLabel, #items, #mobileCartBadge, .sm-cart-count-badge').text(count);
	$('.t4-cart-btn__total').text(data.total_formatted || data.grand_total_formatted || '0,00 ₺');
	if (count > 0) {
		$('#cartCount, #items, #mobileCartBadge, .sm-cart-count-badge').removeClass('d-none').show();
		$('#cartSummary, #cartFooter').prop('hidden', false);
		$('#cartClearBtn, #cartPageClearBtn').show();
	} else {
		$('#cartCount, #items, #mobileCartBadge').addClass('d-none').hide();
		$('.sm-header-mobile .sm-cart-count-badge').addClass('d-none').hide();
		$('.t4-cart-btn__badge.sm-cart-count-badge').removeClass('d-none').show().text('0');
		$('#cartSummary, #cartFooter').prop('hidden', true);
		$('#cartClearBtn, #cartPageClearBtn').hide();
		if ($('#cartPageList').length && !$('#cartPageList').children().length) {
			location.reload();
		}
	}
}

function cartRequest(action, idProduct, qty, idVariation, options) {
	options = options || {};
	return $.ajax({
		url: cartApiUrl,
		method: 'POST',
		dataType: 'json',
		data: {
			action: action,
			id_product: idProduct || 0,
			id_variation: idVariation || 0,
			qty: qty || 1,
			token: csrfToken
		}
	}).done(function (data) {
		if (data.success) {
			updateCartUI(data);
			if (action === 'add') {
				if (options.buynow) {
					window.location.href = (typeof domain !== 'undefined' ? domain : '/') + 'checkout';
					return;
				}
				showCartAdded(data, idProduct, idVariation);
			}
		} else {
			showToast(data.message || cartT('genericError'), 'danger');
		}
	}).fail(function () {
		showToast(cartT('connectionError'), 'danger');
	});
}

$(document).on('click', '.addtocart', function () {
	var idProduct = $(this).data('id');
	var idVariation = parseInt($(this).data('variation'), 10) || 0;
	var qty = 1;
	var qtyInput = document.getElementById('qty-input');
	var buynow = $(this).data('buynow') == 1 || $(this).attr('data-buynow') === '1';

	if (qtyInput) {
		qty = parseInt(qtyInput.value, 10) || 1;
	}

	if ($(this).hasClass('requires-variation') && idVariation <= 0) {
		showToast(cartT('selectVariation') || 'Lütfen seçenekleri belirleyin', 'danger');
		return;
	}

	cartRequest('add', idProduct, qty, idVariation, { buynow: buynow });
});

$(document).on('click', '.cart-qty-btn', function () {
	var idProduct = $(this).data('id');
	var idVariation = parseInt($(this).data('variation'), 10) || 0;
	var action = $(this).data('action');
	var $item = $(this).closest('.cart-item');
	var step = parseFloat($item.attr('data-qty-step')) || 1;
	if (step <= 0) {
		step = 1;
	}
	var currentQty = parseFloat(String($item.find('.cart-qty-value').text()).replace(',', '.')) || 1;
	var maxQty = parseFloat($item.data('max-qty')) || 99;
	var newQty = action === 'increase' ? currentQty + step : currentQty - step;
	newQty = Math.round(newQty * 1000) / 1000;

	if (action === 'increase' && newQty > maxQty) {
		showToast(cartT('stockLimit') + ' (' + maxQty + ')', 'danger');
		return;
	}

	cartRequest('update', idProduct, newQty, idVariation);
});

$(document).on('click', '.cart-remove-btn', function () {
	cartRequest('remove', $(this).data('id'), 1, parseInt($(this).data('variation'), 10) || 0);
});

$(document).on('click', '#cartClearBtn, #cartPageClearBtn', function () {
	if (!confirm(cartT('clearConfirm'))) {
		return;
	}

	$.ajax({
		url: cartApiUrl,
		method: 'POST',
		dataType: 'json',
		data: { action: 'clear', token: csrfToken }
	}).done(function (data) {
		updateCartUI(data);
		showToast(data.message || '', 'success');
		if ($('#cartPageList').length) {
			location.reload();
		}
	});
});

$(document).on('click', '.js-show-cart', function (e) {
	e.preventDefault();
	showCart();
});

$(document).on('click', '.cartHide', function () {
	hideCart();
});

$(document).on('click', '#cartOverlay', function () {
	hideCart();
});

$(document).on('click', '#cartAddedContinue, #cartAddedClose, #cartAddedOverlay', function () {
	hideCartAdded();
});

$(document).on('click', '#cartAddedGoCart', function () {
	hideCartAdded();
});

$(document).on('keydown', function (e) {
	if (e.key !== 'Escape') {
		return;
	}
	if ($('#cartAddedModal').hasClass('show')) {
		hideCartAdded();
		return;
	}
	if ($('#cartPanel').hasClass('show')) {
		hideCart();
	}
});

function showToast(message, cl) {
	if (!message) {
		return;
	}

	cl = cl || 'success';
	$('#tostAlert').removeClass('danger success');
	$('#tostAlert').addClass(cl);
	var toastEl = document.getElementById('tostAlert');
	$(toastEl).find('.toast-body').html(message);
	var toast = new bootstrap.Toast(toastEl);
	toast.show();
}
$('.priceAllertButton').click( function(){
	var idProduct 	= $(this).data('id');
	var price 		= $(this).data('price');
	
	$('#selectedProductId').val(idProduct);
	$('#selectedPrice').val(price);
});

$(document).on('click', '[data-copy-license]', function () {
	var btn = this;
	var text = btn.getAttribute('data-copy-license') || '';
	var label = btn.querySelector('.license-key-row__copy-label');
	var original = label ? label.textContent : '';

	function markCopied() {
		btn.classList.add('is-copied');
		if (label) {
			label.textContent = '✓';
		}
		if (typeof showToast === 'function') {
			showToast('Kopyalandı', 'success');
		}
		setTimeout(function () {
			btn.classList.remove('is-copied');
			if (label) {
				label.textContent = original;
			}
		}, 1600);
	}

	if (!text) {
		return;
	}

	if (navigator.clipboard && navigator.clipboard.writeText) {
		navigator.clipboard.writeText(text).then(markCopied).catch(function () {
			fallbackCopy(text, markCopied);
		});
	} else {
		fallbackCopy(text, markCopied);
	}
});

function fallbackCopy(text, onSuccess) {
	var area = document.createElement('textarea');
	area.value = text;
	area.setAttribute('readonly', '');
	area.style.position = 'absolute';
	area.style.left = '-9999px';
	document.body.appendChild(area);
	area.select();
	try {
		document.execCommand('copy');
		if (typeof onSuccess === 'function') {
			onSuccess();
		}
	} catch (e) {}
	document.body.removeChild(area);
}

(function () {
	function initCartStickyCheckout() {
		var bar = document.getElementById('cartStickyCheckout');
		var summary = document.querySelector('.prime-cart-summary');

		if (!bar || !summary || !('IntersectionObserver' in window)) {
			return;
		}

		var media = window.matchMedia('(max-width: 991.98px)');
		var observer = new IntersectionObserver(function (entries) {
			if (!media.matches) {
				bar.classList.remove('is-hidden');
				return;
			}

			var visible = !!(entries[0] && entries[0].isIntersecting);
			bar.classList.toggle('is-hidden', visible);
			bar.setAttribute('aria-hidden', visible ? 'true' : 'false');
		}, {
			threshold: 0.18,
			rootMargin: '0px 0px -12% 0px'
		});

		function sync() {
			observer.disconnect();

			if (!media.matches) {
				bar.classList.remove('is-hidden');
				bar.setAttribute('aria-hidden', 'true');
				return;
			}

			observer.observe(summary);
		}

		if (media.addEventListener) {
			media.addEventListener('change', sync);
		} else if (media.addListener) {
			media.addListener(sync);
		}

		sync();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initCartStickyCheckout);
	} else {
		initCartStickyCheckout();
	}
})();
