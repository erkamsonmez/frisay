(function () {
	'use strict';
/*
	function initHeaderScroll() {
		var header = document.querySelector('.sm-header');
		var body = document.body;

		if (!header || !body.classList.contains('sm-body')) {
			return;
		}

		var lastY = window.scrollY || window.pageYOffset || 0;
		var ticking = false;
		var deltaThreshold = 6;
		var topReveal = 48;

		function syncHeaderOffset() {
			document.documentElement.style.setProperty('--sm-header-offset', header.offsetHeight + 'px');
		}

		function setHeaderVisible(visible) {
			if (visible) {
				header.classList.remove('is-hidden');
				body.classList.remove('sm-header-collapsed');
			} else {
				header.classList.add('is-hidden');
				body.classList.add('sm-header-collapsed');
			}
		}

		function updateHeader() {
			var currentY = window.scrollY || window.pageYOffset || 0;
			var delta = currentY - lastY;

			if (currentY <= topReveal) {
				setHeaderVisible(true);
			} else if (delta > deltaThreshold) {
				setHeaderVisible(false);
			} else if (delta < -deltaThreshold) {
				setHeaderVisible(true);
			}

			lastY = currentY;
		}

		syncHeaderOffset();
		window.addEventListener('resize', syncHeaderOffset, { passive: true });
		window.addEventListener('scroll', function () {
			if (!ticking) {
				window.requestAnimationFrame(function () {
					updateHeader();
					ticking = false;
				});
				ticking = true;
			}
		}, { passive: true });
	}
*/
	function initSearchSuggest() {
		if (!window.searchSuggestUrl) {
			return;
		}

		document.querySelectorAll('[data-sm-search]').forEach(function (wrap) {
			var input = wrap.querySelector('[data-sm-search-input]');
			var box = wrap.querySelector('[data-sm-search-results]');
			if (!input || !box) {
				return;
			}

			var timer = null;

			function close() {
				box.classList.remove('is-open');
				box.innerHTML = '';
				box.hidden = true;
			}

			function escapeHtml(value) {
				return String(value || '')
					.replace(/&/g, '&amp;')
					.replace(/</g, '&lt;')
					.replace(/>/g, '&gt;')
					.replace(/"/g, '&quot;');
			}

			function render(items) {
				if (!items.length) {
					box.innerHTML = '<div class="sm-header-search__empty">Ürün bulunamadı</div>';
					box.hidden = false;
					box.classList.add('is-open');
					return;
				}

				box.innerHTML = items.map(function (item) {
					var image = item.image || (window.imgDir ? window.imgDir + 'no-image.png' : '');

					return '<a class="sm-header-search__item" href="' + escapeHtml(item.url) + '" role="option">' +
						(image ? '<img class="sm-header-search__thumb" src="' + escapeHtml(image) + '" alt="" loading="lazy">' : '') +
						'<div class="sm-header-search__body">' +
							'<div class="sm-header-search__name">' + escapeHtml(item.name) + '</div>' +
							(item.category ? '<div class="sm-header-search__meta">' + escapeHtml(item.category) + '</div>' : '') +
						'</div>' +
						'<div class="sm-header-search__price">' + escapeHtml(item.price) + '</div></a>';
				}).join('');
				box.hidden = false;
				box.classList.add('is-open');
			}

			input.addEventListener('input', function () {
				var q = input.value.trim();
				clearTimeout(timer);

				if (q.length < 2) {
					close();
					return;
				}

				timer = setTimeout(function () {
					fetch(window.searchSuggestUrl + '?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
						.then(function (r) { return r.json(); })
						.then(function (res) { render(res.items || []); })
						.catch(function () { close(); });
				}, 280);
			});

			document.addEventListener('click', function (e) {
				if (!wrap.contains(e.target)) {
					close();
				}
			});

			input.addEventListener('keydown', function (e) {
				if (e.key === 'Escape') {
					close();
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		initSearchSuggest();
		initFavoriteToggle();
	});

	function initFavoriteToggle() {
		if (!window.favoriteApiUrl) {
			return;
		}

		document.addEventListener('click', function (e) {
			var btn = e.target.closest('.toggle-favorite');
			if (!btn) {
				return;
			}

			e.preventDefault();
			e.stopPropagation();
			var id = btn.getAttribute('data-id');
			if (!id || btn.getAttribute('data-busy') === '1') {
				return;
			}

			btn.setAttribute('data-busy', '1');

			var body = new FormData();
			body.append('action', 'toggle');
			body.append('id_product', id);
			if (window.csrfToken) {
				body.append('token', window.csrfToken);
			}

			fetch(window.favoriteApiUrl, {
				method: 'POST',
				credentials: 'same-origin',
				body: body
			})
				.then(function (r) { return r.json(); })
				.then(function (res) {
					if (!res) {
						return;
					}
					if (res.login_required) {
						window.location.href = (window.baseDir || '/') + 'login';
						return;
					}
					if (res.success === false) {
						if (typeof window.showToast === 'function') {
							window.showToast(res.message || 'Error', 'danger');
						}
						return;
					}
					var active = !!res.is_favorite;
					document.querySelectorAll('.toggle-favorite[data-id="' + id + '"]').forEach(function (el) {
						el.classList.toggle('is-active', active);
						el.classList.toggle('active', active);
					});
					updateFavoriteBadges(res.count != null ? res.count : 0);
					if (typeof window.showToast === 'function') {
						window.showToast(res.message || (active ? 'Added' : 'Removed'), 'success');
					}
				})
				.catch(function () {
					if (typeof window.showToast === 'function') {
						window.showToast('Request failed', 'danger');
					}
				})
				.finally(function () {
					btn.removeAttribute('data-busy');
				});
		});
	}

	function updateFavoriteBadges(count) {
		var n = Math.max(0, parseInt(count, 10) || 0);
		document.querySelectorAll('.t4-tool-icon[href*="favorites"], a.t4-tool-icon[title]').forEach(function (link) {
			if (!link.getAttribute('href') || link.getAttribute('href').indexOf('favorites') === -1) {
				return;
			}
			var badge = link.querySelector('.t4-tool-icon__badge');
			if (n > 0) {
				if (!badge) {
					badge = document.createElement('span');
					badge.className = 't4-tool-icon__badge';
					link.appendChild(badge);
				}
				badge.textContent = String(n);
			} else if (badge) {
				badge.remove();
			}
		});
	}
})();
