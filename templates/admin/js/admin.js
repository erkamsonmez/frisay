document.addEventListener('DOMContentLoaded', function () {
	if (window.history.replaceState) {
		window.history.replaceState(null, null, window.location.href);
	}

	var mobileMenuBtn = document.getElementById('mobileMenuBtn');
	var sidebar = document.getElementById('adminSidebar');
	var backdrop = document.getElementById('sidebarBackdrop');
	var collapseBtn = document.getElementById('sidebarCollapseBtn');
	var collapseStorageKey = 'fshop.admin.sidebarCollapsed';

	function openSidebar() {
		if (!sidebar) {
			return;
		}
		sidebar.classList.add('active');
		if (backdrop) {
			backdrop.hidden = false;
		}
		document.body.classList.add('admin-sidebar-open');
	}

	function closeSidebar() {
		if (!sidebar) {
			return;
		}
		sidebar.classList.remove('active');
		if (backdrop) {
			backdrop.hidden = true;
		}
		document.body.classList.remove('admin-sidebar-open');
		if (mobileMenuBtn) {
			mobileMenuBtn.classList.remove('open');
		}
	}

	function toggleSidebar() {
		if (sidebar && sidebar.classList.contains('active')) {
			closeSidebar();
		} else {
			openSidebar();
		}
	}

	function setSidebarCollapsed(collapsed) {
		document.body.classList.toggle('admin-sidebar-collapsed', !!collapsed);
		if (collapseBtn) {
			collapseBtn.setAttribute('aria-expanded', collapsed ? 'false' : 'true');
		}
		try {
			localStorage.setItem(collapseStorageKey, collapsed ? '1' : '0');
		} catch (e) {}
		closeAdminSidebarFlyout();
	}

	function initSidebarCollapse() {
		var saved = false;
		try {
			saved = localStorage.getItem(collapseStorageKey) === '1';
		} catch (e) {}
		if (window.innerWidth >= 992 && saved) {
			setSidebarCollapsed(true);
		}
		if (collapseBtn) {
			collapseBtn.addEventListener('click', function () {
				if (window.innerWidth < 992) {
					return;
				}
				setSidebarCollapsed(!document.body.classList.contains('admin-sidebar-collapsed'));
			});
		}
	}

	if (mobileMenuBtn) {
		mobileMenuBtn.addEventListener('click', function () {
			this.classList.toggle('open');
			toggleSidebar();
		});
	}

	if (backdrop) {
		backdrop.addEventListener('click', closeSidebar);
	}

	window.addEventListener('resize', function () {
		if (window.innerWidth >= 992) {
			closeSidebar();
			try {
				if (localStorage.getItem(collapseStorageKey) === '1') {
					document.body.classList.add('admin-sidebar-collapsed');
				}
			} catch (e) {}
		} else {
			document.body.classList.remove('admin-sidebar-collapsed');
		}
	});

	initSidebarCollapse();
	initSidebarAccordion();
	initAdminFavorites();
	initModuleListFilters();
	initAdminConfirmBindings();
	initAdminBusyBindings();
	initAdminToasts();
	initAutoHideAlerts();
	initLivePoll();
});

function closeAdminSidebarFlyout() {
	var flyout = document.getElementById('adminSidebarFlyout');
	if (!flyout) {
		return;
	}
	flyout.hidden = true;
	flyout.classList.remove('is-open');
	var menu = document.getElementById('adminSidebarMenu');
	if (menu) {
		Array.prototype.forEach.call(menu.querySelectorAll('.nav-accordion.is-flyout-open'), function (group) {
			group.classList.remove('is-flyout-open');
		});
	}
}

function initSidebarAccordion() {
	var menu = document.getElementById('adminSidebarMenu');
	if (!menu) {
		return;
	}

	var groups = Array.prototype.slice.call(menu.querySelectorAll('.nav-accordion'));
	if (!groups.length) {
		return;
	}

	var flyout = document.getElementById('adminSidebarFlyout');
	var flyoutTitle = flyout ? flyout.querySelector('.sidebar-flyout__title') : null;
	var flyoutItems = flyout ? flyout.querySelector('.sidebar-flyout__items') : null;
	var flyoutCloseTimer = null;
	var flyoutOpenGroup = null;

	function isCollapsedDesktop() {
		return document.body.classList.contains('admin-sidebar-collapsed') && window.innerWidth >= 992;
	}

	function setOpen(group, open) {
		group.classList.toggle('is-open', !!open);
		var btn = group.querySelector('.nav-accordion__toggle');
		if (btn) {
			btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		}
	}

	function openOnly(target) {
		groups.forEach(function (group) {
			if (group.getAttribute('data-accordion-group') === 'favorites' && group !== target) {
				return;
			}
			setOpen(group, group === target);
		});
	}

	function positionFlyout(toggle) {
		if (!flyout || !toggle) {
			return;
		}
		var sidebar = document.getElementById('adminSidebar');
		var rect = toggle.getBoundingClientRect();
		var left = sidebar ? sidebar.getBoundingClientRect().right + 8 : rect.right + 8;
		flyout.style.left = Math.round(left) + 'px';
		flyout.hidden = false;
		flyout.classList.add('is-open');
		var height = flyout.offsetHeight;
		var top = rect.top;
		if (top + height > window.innerHeight - 12) {
			top = Math.max(12, window.innerHeight - height - 12);
		}
		flyout.style.top = Math.round(top) + 'px';
	}

	function openFlyout(group) {
		if (!flyout || !flyoutItems || !isCollapsedDesktop()) {
			return;
		}
		var body = group.querySelector('.nav-accordion__body');
		var label = group.querySelector('.nav-accordion__label');
		if (!body) {
			return;
		}
		if (flyoutTitle) {
			flyoutTitle.textContent = label ? label.textContent.trim() : '';
		}
		flyoutItems.innerHTML = '';
		Array.prototype.forEach.call(body.querySelectorAll('.menu-item'), function (item) {
			flyoutItems.appendChild(item.cloneNode(true));
		});
		groups.forEach(function (g) {
			g.classList.toggle('is-flyout-open', g === group);
		});
		flyoutOpenGroup = group;
		positionFlyout(group.querySelector('.nav-accordion__toggle'));
		if (window.lucide && typeof window.lucide.createIcons === 'function') {
			window.lucide.createIcons();
		}
	}

	function scheduleCloseFlyout() {
		clearTimeout(flyoutCloseTimer);
		flyoutCloseTimer = setTimeout(function () {
			closeAdminSidebarFlyout();
			flyoutOpenGroup = null;
		}, 180);
	}

	function cancelCloseFlyout() {
		clearTimeout(flyoutCloseTimer);
	}

	groups.forEach(function (group) {
		if (group.querySelector('.menu-item.active')) {
			group.classList.add('has-active');
		}

		var btn = group.querySelector('.nav-accordion__toggle');
		if (!btn) {
			return;
		}

		btn.addEventListener('click', function (e) {
			if (isCollapsedDesktop()) {
				e.preventDefault();
				e.stopPropagation();
				cancelCloseFlyout();
				if (flyoutOpenGroup === group && flyout && !flyout.hidden) {
					closeAdminSidebarFlyout();
					flyoutOpenGroup = null;
					return;
				}
				openFlyout(group);
				return;
			}
			if (group.classList.contains('is-open')) {
				setOpen(group, false);
				return;
			}
			openOnly(group);
		});

		btn.addEventListener('mouseenter', function () {
			if (!isCollapsedDesktop()) {
				return;
			}
			cancelCloseFlyout();
			openFlyout(group);
		});

		btn.addEventListener('mouseleave', function () {
			if (!isCollapsedDesktop()) {
				return;
			}
			scheduleCloseFlyout();
		});
	});

	if (flyout) {
		flyout.addEventListener('mouseenter', cancelCloseFlyout);
		flyout.addEventListener('mouseleave', function () {
			if (!isCollapsedDesktop()) {
				return;
			}
			scheduleCloseFlyout();
		});
	}

	document.addEventListener('mousedown', function (e) {
		if (!flyout || flyout.hidden) {
			return;
		}
		if (flyout.contains(e.target) || (e.target.closest && e.target.closest('.nav-accordion'))) {
			return;
		}
		closeAdminSidebarFlyout();
		flyoutOpenGroup = null;
	});

	document.addEventListener('keydown', function (e) {
		if (e.key === 'Escape') {
			closeAdminSidebarFlyout();
			flyoutOpenGroup = null;
		}
	});

	window.addEventListener('resize', function () {
		if (!isCollapsedDesktop()) {
			closeAdminSidebarFlyout();
			flyoutOpenGroup = null;
			return;
		}
		if (flyoutOpenGroup) {
			positionFlyout(flyoutOpenGroup.querySelector('.nav-accordion__toggle'));
		}
	});

	var activeItem = menu.querySelector('.menu-item.active');
	var activeGroup = activeItem ? activeItem.closest('.nav-accordion') : null;
	if (activeGroup) {
		openOnly(activeGroup);
		try {
			activeItem.scrollIntoView({ block: 'nearest' });
		} catch (e) {}
	} else {
		openOnly(groups[0]);
	}
}

function initAdminFavorites() {
	var cfg = window.__adminFavorites || {};
	var menu = document.getElementById('adminSidebarMenu');
	if (!menu) {
		return;
	}

	var keys = Array.isArray(cfg.keys) ? cfg.keys.slice() : [];
	var i18n = window.__adminI18n || {};
	var favList = document.getElementById('adminFavList');
	var starSvg = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>';

	function adminUrlPrefix() {
		var live = window.__adminLivePoll || {};
		return String(live.adminUrl || '');
	}

	function keyFromLink(a) {
		if (!a) {
			return '';
		}
		if (a.getAttribute('data-menu-key')) {
			return a.getAttribute('data-menu-key');
		}
		var href = a.getAttribute('href') || '';
		var prefix = adminUrlPrefix();
		if (prefix && href.indexOf(prefix) === 0) {
			var rest = href.slice(prefix.length).split('?')[0].replace(/\/+$/, '');
			return rest.split('/')[0] || '';
		}
		if (href.indexOf('http') === 0) {
			return 'module-store';
		}
		return '';
	}

	function isOn(key) {
		return keys.indexOf(key) !== -1;
	}

	function syncStars() {
		Array.prototype.forEach.call(menu.querySelectorAll('.menu-fav-star'), function (el) {
			var key = el.getAttribute('data-menu-key') || keyFromLink(el.closest('a.menu-item'));
			if (!key) {
				return;
			}
			el.classList.toggle('is-on', isOn(key));
			el.setAttribute('aria-pressed', isOn(key) ? 'true' : 'false');
			el.setAttribute('aria-label', isOn(key) ? (i18n.favoritesUnstar || i18n.favoritesRemove || '') : (i18n.favoritesStar || ''));
			el.title = isOn(key) ? (i18n.favoritesUnstar || i18n.favoritesRemove || '') : (i18n.favoritesStar || '');
		});
	}

	function decorateLinks() {
		Array.prototype.forEach.call(menu.querySelectorAll('a.menu-item'), function (a) {
			if (favList && favList.contains(a)) {
				return;
			}
			if (a.querySelector('.menu-fav-star')) {
				return;
			}
			var key = keyFromLink(a);
			if (!key) {
				return;
			}
			a.setAttribute('data-menu-key', key);
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'menu-fav-star' + (isOn(key) ? ' is-on' : '');
			btn.setAttribute('data-menu-key', key);
			btn.setAttribute('aria-label', isOn(key) ? (i18n.favoritesUnstar || i18n.favoritesRemove || '') : (i18n.favoritesStar || 'Star'));
			btn.innerHTML = starSvg;
			a.appendChild(btn);
		});
		syncStars();
	}

	function save(nextKeys, toastType) {
		if (!cfg.url) {
			if (typeof showAdminLiveToast === 'function') {
				showAdminLiveToast({ type: 'danger', message: i18n.confirmMessage || '' });
			}
			return;
		}
		var body = new URLSearchParams();
		body.set('token', cfg.token || '');
		body.set('action', 'save');
		body.set('keys', JSON.stringify(nextKeys));
		return fetch(cfg.url, {
			method: 'POST',
			headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
			body: body.toString(),
			credentials: 'same-origin'
		}).then(function (res) { return res.json(); }).then(function (data) {
			if (!data || !data.success) {
				throw new Error((data && data.message) || 'error');
			}
			keys = Array.isArray(data.keys) ? data.keys : nextKeys;
			cfg.keys = keys;
			if (typeof showAdminLiveToast === 'function') {
				showAdminLiveToast({
					type: toastType || 'success',
					message: toastType === 'info' ? (i18n.favoritesRemove || '') : (i18n.favoritesAdd || '')
				});
			}
			window.location.reload();
		}).catch(function () {
			if (typeof showAdminLiveToast === 'function') {
				showAdminLiveToast({ type: 'danger', message: i18n.confirmMessage || '' });
			}
		});
	}

	function toggleKey(key) {
		if (!key) {
			return;
		}
		var next = keys.slice();
		var idx = next.indexOf(key);
		if (idx === -1) {
			next.push(key);
			save(next, 'success');
			return;
		}
		next.splice(idx, 1);
		save(next, 'info');
	}

	decorateLinks();

	document.addEventListener('click', function (e) {
		var action = e.target.closest ? e.target.closest('.menu-fav-star') : null;
		if (!action) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		toggleKey(action.getAttribute('data-menu-key') || keyFromLink(action.closest('a.menu-item')));
	});
}

function initAdminToasts() {
	document.querySelectorAll('.frisay-toast').forEach(function (el) {
		if (el.classList.contains('admin-push-toast')) {
			el.querySelectorAll('.btn-close').forEach(function (btn) { btn.remove(); });
			bindAdminToast(el);
			return;
		}
		var titleEl = el.querySelector('.toast-title');
		var msgEl = el.querySelector('.toast-message');
		fillAdminPushToast(el, {
			type: toastTypeClass(el),
			title: titleEl ? titleEl.textContent : '',
			message: msgEl ? msgEl.textContent : (el.textContent || '')
		});
		bindAdminToast(el);
	});
}

function initAutoHideAlerts() {
	var alerts = document.querySelectorAll('.admin-content > .alert, .admin-login-alert');
	if (!alerts.length) {
		return;
	}

	alerts.forEach(function (el) {
		if (el.classList.contains('alert-light') || el.classList.contains('adminAlert')) {
			return;
		}
		if (el.querySelector('form, table, ul, ol, .btn')) {
			return;
		}
		var text = String(el.textContent || '').replace(/\s+/g, ' ').trim();
		if (!text || text.length > 220) {
			return;
		}
		showAdminLiveToast({
			type: toastTypeClass(el),
			title: text
		});
		el.remove();
	});
}

function ensureLiveToastContainer() {
	var el = document.getElementById('adminLiveToastContainer');
	if (el) {
		return el;
	}
	el = document.createElement('div');
	el.id = 'adminLiveToastContainer';
	el.className = 'toast-container position-fixed bottom-0 end-0 p-4';
	el.style.zIndex = '9999';
	document.body.appendChild(el);
	return el;
}

function toastTypeClass(el) {
	var type = 'info';
	['success', 'warning', 'danger', 'info', 'dark', 'error'].forEach(function (name) {
		if (el.classList.contains(name) || el.classList.contains('alert-' + name)) {
			type = name;
		}
	});
	return type === 'error' ? 'danger' : type;
}

function adminPushTypeMeta(type) {
	var t = String(type || 'info').toLowerCase();
	if (t === 'error') {
		t = 'danger';
	}
	var check = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M20 6 9 17l-5-5"/></svg>';
	var map = {
		order: {
			color: '#f97316',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>'
		},
		contact: {
			color: '#3b82f6',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
		},
		cancel_request: {
			color: '#ef4444',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>'
		},
		return_request: {
			color: '#f59e0b',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 7v6h6"/><path d="M3 13a9 9 0 1 0 3-7.7L3 8"/></svg>'
		},
		stock: {
			color: '#111827',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m7.5 4.27 9 5.15"/><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>'
		},
		success: { color: '#10b981', icon: check },
		dark: { color: '#111827', icon: check },
		danger: {
			color: '#ef4444',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="m15 9-6 6"/><path d="m9 9 6 6"/></svg>'
		},
		warning: {
			color: '#f59e0b',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>'
		},
		info: {
			color: '#6b7280',
			icon: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>'
		}
	};
	return map[t] || map.info;
}

function fillAdminPushToast(toastEl, opts) {
	opts = opts || {};
	var title = String(opts.title || '').trim();
	var message = String(opts.message || '').trim();
	if (!title && message) {
		title = message;
		message = '';
	}
	var push = adminPushTypeMeta(opts.type || 'info');
	var time = opts.time || (window.__adminI18n && window.__adminI18n.justNow) || 'şimdi';
	toastEl.className = 'toast admin-push-toast';
	toastEl.setAttribute('role', 'alert');
	toastEl.innerHTML =
		'<span class="admin-push-toast__icon" style="background:' + push.color + '">' + (opts.iconHtml || push.icon) + '</span>' +
		'<div class="admin-push-toast__text">' +
			(title ? '<div class="admin-push-toast__title"></div>' : '') +
			(message ? '<div class="admin-push-toast__msg"></div>' : '') +
		'</div>' +
		'<span class="admin-push-toast__time"></span>';
	var timeEl = toastEl.querySelector('.admin-push-toast__time');
	if (timeEl) {
		timeEl.textContent = time;
	}
	var titleEl = toastEl.querySelector('.admin-push-toast__title');
	var msgEl = toastEl.querySelector('.admin-push-toast__msg');
	if (titleEl) {
		titleEl.textContent = title;
	}
	if (msgEl) {
		msgEl.textContent = message;
	}
	if (opts.href) {
		toastEl.style.cursor = 'pointer';
		toastEl.addEventListener('click', function () {
			window.location.href = opts.href;
		});
	}
}

function bindAdminToast(toastEl) {
	if (window.bootstrap && bootstrap.Toast) {
		var toast = new bootstrap.Toast(toastEl, { delay: 6000, autohide: true });
		toastEl.addEventListener('hidden.bs.toast', function () {
			toastEl.remove();
		});
		toast.show();
	} else {
		toastEl.classList.add('show');
		setTimeout(function () {
			toastEl.remove();
		}, 6000);
	}
}

function showAdminLiveToast(opts) {
	opts = opts || {};
	var container = ensureLiveToastContainer();
	var toastEl = document.createElement('div');
	fillAdminPushToast(toastEl, opts);
	container.appendChild(toastEl);
	bindAdminToast(toastEl);
}

function updateNavBadge(key, count) {
	count = parseInt(count, 10) || 0;
	var selectors = [
		'.menu-item[href$="/' + key + '"] .nav-badge',
		'.menu-item[href$="' + key + '"] .nav-badge',
		'.header-icon-btn[href$="/' + key + '"] .header-icon-btn__badge',
		'.header-icon-btn[href$="' + key + '"] .header-icon-btn__badge'
	];
	document.querySelectorAll(selectors.join(',')).forEach(function (badge) {
		if (count > 0) {
			badge.textContent = String(count);
			badge.hidden = false;
			badge.style.display = '';
		} else {
			badge.hidden = true;
			badge.style.display = 'none';
		}
	});
}

function playNewOrderSound() {
	try {
		var Ctx = window.AudioContext || window.webkitAudioContext;
		if (!Ctx) {
			return;
		}
		if (!window.__adminAudioCtx) {
			window.__adminAudioCtx = new Ctx();
		}
		var ctx = window.__adminAudioCtx;
		if (ctx.state === 'suspended') {
			ctx.resume();
		}

		var now = ctx.currentTime;
		var notes = [880, 1174.7]; // A5 + D6 — kısa “ding-ding”

		notes.forEach(function (freq, i) {
			var osc = ctx.createOscillator();
			var gain = ctx.createGain();
			osc.type = 'sine';
			osc.frequency.value = freq;
			gain.gain.setValueAtTime(0.0001, now);
			gain.gain.exponentialRampToValueAtTime(0.18, now + 0.02 + i * 0.12);
			gain.gain.exponentialRampToValueAtTime(0.0001, now + 0.28 + i * 0.12);
			osc.connect(gain);
			gain.connect(ctx.destination);
			osc.start(now + i * 0.12);
			osc.stop(now + 0.35 + i * 0.12);
		});
	} catch (e) {
		/* ses engellenirse sessizce geç */
	}
}

function initLivePoll() {
	var cfg = window.__adminLivePoll;
	if (!cfg || !cfg.url) {
		return;
	}

	var storageKey = 'fshop_admin_live_poll_v1';
	var state = null;

	try {
		state = JSON.parse(sessionStorage.getItem(storageKey) || 'null');
	} catch (e) {
		state = null;
	}

	var i18n = window.__adminI18n || {};
	var icons = {
		order: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>',
		message: '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>'
	};

	function poll() {
		if (document.hidden) {
			return;
		}

		fetch(cfg.url, {
			method: 'GET',
			credentials: 'same-origin',
			headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
		})
			.then(function (res) {
				if (!res.ok) {
					throw new Error('poll failed');
				}
				return res.json();
			})
			.then(function (data) {
				if (!data || !data.success) {
					return;
				}

				var latest = data.latest || {};
				var badges = data.badges || {};

				if (badges.orders !== undefined) {
					updateNavBadge('orders', badges.orders);
				}
				if (badges.messages !== undefined) {
					updateNavBadge('messages', badges.messages);
				}
				if (badges.notifications !== undefined) {
					updateNavBadge('notifications', badges.notifications);
				}
				if (badges.returns !== undefined) {
					updateNavBadge('returns', badges.returns);
				}
				if (badges.cancellations !== undefined) {
					updateNavBadge('cancellations', badges.cancellations);
				}

				if (!state) {
					state = {
						order_id: latest.order_id || 0,
						message_id: latest.message_id || 0,
						notification_id: latest.notification_id || 0
					};
					sessionStorage.setItem(storageKey, JSON.stringify(state));
					return;
				}

				if ((latest.notification_id || 0) > (state.notification_id || 0)) {
					var n = data.notification || {};
					var kind = n.type || 'info';
					if (kind === 'order') {
						playNewOrderSound();
						state.order_id = latest.order_id || state.order_id;
					}
					if (kind === 'contact') {
						state.message_id = latest.message_id || state.message_id;
					}
					showAdminLiveToast({
						variant: 'push',
						type: kind,
						title: n.type_label || n.title || i18n.newNotificationTitle || 'Yeni bildirim',
						message: n.message || n.title || '',
						href: n.link || ((cfg.adminUrl || '') + 'notifications'),
						time: i18n.justNow || 'şimdi'
					});
					state.notification_id = latest.notification_id;
				}

				if ((latest.order_id || 0) > (state.order_id || 0)) {
					var ref = latest.order_ref ? (' #' + latest.order_ref) : '';
					playNewOrderSound();
					showAdminLiveToast({
						variant: 'push',
						type: 'order',
						title: i18n.newOrderTitle || 'Yeni sipariş',
						message: (i18n.newOrderMessage || 'Mağazanıza yeni bir sipariş geldi') + ref,
						href: (cfg.adminUrl || '') + 'orders'
					});
					state.order_id = latest.order_id;
				}

				if ((latest.message_id || 0) > (state.message_id || 0)) {
					showAdminLiveToast({
						variant: 'push',
						type: 'contact',
						title: i18n.newMessageTitle || 'Yeni mesaj',
						message: i18n.newMessageMessage || 'Okunmamış yeni bir müşteri mesajı var',
						href: (cfg.adminUrl || '') + 'messages',
						iconHtml: icons.message
					});
					state.message_id = latest.message_id;
				}

				sessionStorage.setItem(storageKey, JSON.stringify(state));
			})
			.catch(function () {
				/* sessizce yut — geçici ağ hataları */
			});
	}

	poll();
	setInterval(poll, parseInt(cfg.intervalMs, 10) || 30000);
}

function adminI18n(key, fallback) {
	var pack = window.__adminI18n || {};
	return pack[key] || fallback;
}

function ensureSubmitterField(form, submitter) {
	if (!form || !submitter || submitter.tagName === 'A') {
		return;
	}

	var name = submitter.getAttribute('name');

	if (!name) {
		return;
	}

	var value = submitter.getAttribute('value');

	if (value === null) {
		value = '1';
	}

	var safeName = name.replace(/\\/g, '\\\\').replace(/"/g, '\\"');
	var existing = form.querySelector('input[type="hidden"][data-admin-confirm-proxy="1"][name="' + safeName + '"]');

	if (!existing) {
		existing = document.createElement('input');
		existing.type = 'hidden';
		existing.name = name;
		existing.setAttribute('data-admin-confirm-proxy', '1');
		form.appendChild(existing);
	}

	existing.value = value;
}

window.AdminBusy = {
	start: function (el) {
		if (!el || el.dataset.adminBusy === '1') {
			return;
		}

		var form = el.form || (el.closest ? el.closest('form') : null);

		if (form) {
			ensureSubmitterField(form, el);
		}

		el.dataset.adminBusy = '1';
		el.classList.add('is-busy');

		if ('disabled' in el) {
			el.disabled = true;
		}

		if (!el.querySelector('.adm-btn-spinner')) {
			var spin = document.createElement('span');
			spin.className = 'adm-btn-spinner';
			spin.setAttribute('aria-hidden', 'true');
			el.insertBefore(spin, el.firstChild);
		}
	},
	stop: function (el) {
		if (!el || el.dataset.adminBusy !== '1') {
			return;
		}

		delete el.dataset.adminBusy;
		el.classList.remove('is-busy');

		if ('disabled' in el) {
			el.disabled = false;
		}

		var spin = el.querySelector('.adm-btn-spinner');

		if (spin) {
			spin.remove();
		}
	}
};

window.AdminToast = {
	show: function (message, type) {
		showAdminLiveToast({
			message: message || '',
			type: type || 'info'
		});
	}
};

window.AdminConfirm = {
	_pending: null,
	_bound: false,
	ensureModal: function () {
		var modalEl = document.getElementById('admin-confirm-modal');

		if (modalEl) {
			if (modalEl.parentNode !== document.body) {
				document.body.appendChild(modalEl);
			}
		} else {
			modalEl = document.createElement('div');
			modalEl.className = 'modal fade';
			modalEl.id = 'admin-confirm-modal';
			modalEl.tabIndex = -1;
			modalEl.setAttribute('aria-hidden', 'true');
			modalEl.innerHTML =
				'<div class="modal-dialog modal-dialog-centered">' +
					'<div class="modal-content">' +
						'<div class="modal-header py-2 bg-light">' +
							'<h5 class="modal-title h6 mb-0 text-dark" id="admin-confirm-title"></h5>' +
							'<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>' +
						'</div>' +
						'<div class="modal-body py-3" id="admin-confirm-message"></div>' +
						'<div class="modal-footer py-2">' +
							'<button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal" id="admin-confirm-cancel">' +
								adminI18n('confirmCancel', 'Cancel') +
							'</button>' +
							'<button type="button" class="btn btn-danger btn-sm" id="admin-confirm-btn">' +
								adminI18n('confirmYes', 'Yes, confirm') +
							'</button>' +
						'</div>' +
					'</div>' +
				'</div>';
			document.body.appendChild(modalEl);
		}

		if (!AdminConfirm._bound) {
			AdminConfirm._bound = true;
			modalEl.addEventListener('hidden.bs.modal', function () {
				var pending = AdminConfirm._pending;
				AdminConfirm._pending = null;

				if (pending && typeof pending.resolve === 'function') {
					pending.resolve(false);
				}
			});
		}

		return modalEl;
	},
	ask: function (opts) {
		if (typeof opts === 'string') {
			opts = { message: opts };
		}

		opts = opts || {};

		return new Promise(function (resolve) {
			var modalEl = AdminConfirm.ensureModal();
			var titleEl = document.getElementById('admin-confirm-title');
			var messageEl = document.getElementById('admin-confirm-message');
			var confirmBtn = document.getElementById('admin-confirm-btn');

			if (AdminConfirm._pending && typeof AdminConfirm._pending.resolve === 'function') {
				AdminConfirm._pending.resolve(false);
			}

			AdminConfirm._pending = { resolve: resolve };

			if (titleEl) {
				titleEl.textContent = opts.title || adminI18n('confirmTitle', 'Confirm action');
			}

			if (messageEl) {
				messageEl.textContent = opts.message || adminI18n('confirmMessage', 'Are you sure you want to perform this action?');
			}

			if (confirmBtn) {
				confirmBtn.textContent = opts.confirmLabel || adminI18n('confirmYes', 'Yes, confirm');
				confirmBtn.className = 'btn btn-sm ' + (opts.danger === false ? 'btn-dark' : 'btn-danger');
				var freshBtn = confirmBtn.cloneNode(true);
				confirmBtn.parentNode.replaceChild(freshBtn, confirmBtn);
				confirmBtn = freshBtn;
				confirmBtn.addEventListener('click', function () {
					var pending = AdminConfirm._pending;
					AdminConfirm._pending = null;

					if (window.bootstrap && bootstrap.Modal) {
						var instance = bootstrap.Modal.getInstance(modalEl);

						if (instance) {
							instance.hide();
						}
					} else {
						modalEl.classList.remove('show');
						modalEl.style.display = 'none';
					}

					if (pending && typeof pending.resolve === 'function') {
						pending.resolve(true);
					}
				});
			}

			if (window.bootstrap && bootstrap.Modal) {
				bootstrap.Modal.getOrCreateInstance(modalEl).show();
			} else {
				modalEl.classList.add('show');
				modalEl.style.display = 'block';
			}
		});
	},
	show: function (title, message, onConfirm) {
		return AdminConfirm.ask({ title: title, message: message }).then(function (ok) {
			if (ok && typeof onConfirm === 'function') {
				onConfirm();
			}

			return ok;
		});
	}
};

function submitAdminForm(form, submitter) {
	if (!form) {
		return;
	}

	form.dataset.adminConfirmed = '1';

	if (submitter) {
		ensureSubmitterField(form, submitter);
		AdminBusy.start(submitter);
	}

	form.submit();
}

function initAdminConfirmBindings() {
	document.addEventListener('click', function (e) {
		var btn = e.target.closest('.js-admin-confirm');

		if (!btn) {
			return;
		}

		e.preventDefault();
		e.stopPropagation();

		var requireChecked = btn.getAttribute('data-confirm-require-checked');

		if (requireChecked) {
			/* Checkboxes may use form="…" and live outside the <form> in the DOM. */
			if (!document.querySelectorAll(requireChecked + ':checked').length) {
				if (window.AdminToast) {
					AdminToast.show(btn.getAttribute('data-confirm-empty-message') || adminI18n('confirmMessage', 'Select at least one item'), 'warning');
				}
				return;
			}
		}

		var title = btn.getAttribute('data-confirm-title') || adminI18n('confirmTitle', 'Confirm action');
		var message = btn.getAttribute('data-confirm-message') || adminI18n('confirmMessage', 'Are you sure you want to perform this action?');
		var form = btn.form || btn.closest('form');

		AdminConfirm.ask({ title: title, message: message }).then(function (ok) {
			if (!ok) {
				return;
			}

			if (btn.tagName === 'A' && btn.getAttribute('href')) {
				AdminBusy.start(btn);
				window.location.href = btn.getAttribute('href');
				return;
			}

			submitAdminForm(form, btn);
		});
	}, true);
}

function initAdminBusyBindings() {
	document.addEventListener('submit', function (e) {
		var form = e.target;

		if (!form || form.tagName !== 'FORM') {
			return;
		}

		var submitter = e.submitter || null;
		var confirmMessage = form.getAttribute('data-confirm-message');

		if (form.dataset.adminConfirmed === '1') {
			if (submitter && (submitter.classList.contains('js-admin-busy') || confirmMessage)) {
				AdminBusy.start(submitter);
			}

			return;
		}

		if (submitter && submitter.classList.contains('js-admin-confirm')) {
			return;
		}

		if (confirmMessage) {
			e.preventDefault();
			AdminConfirm.ask({
				title: form.getAttribute('data-confirm-title') || adminI18n('confirmTitle', 'Confirm action'),
				message: confirmMessage
			}).then(function (ok) {
				if (!ok) {
					return;
				}

				submitAdminForm(form, submitter && form.contains(submitter) ? submitter : null);
			});

			return;
		}

		if (submitter && submitter.classList.contains('js-admin-busy')) {
			AdminBusy.start(submitter);
		}
	}, true);
}

function initModuleListFilters() {
	var list = document.getElementById('moduleList');
	var search = document.getElementById('moduleSearch');
	var filter = document.getElementById('moduleStatusFilter');
	var emptyState = document.getElementById('moduleListEmpty');

	if (!list) {
		return;
	}

	function normalize(value) {
		return (value || '').toLocaleLowerCase('tr-TR').trim();
	}

	function matchesStatus(rowStatus, statusFilter) {
		if (statusFilter === 'all') {
			return true;
		}

		if (statusFilter === 'installed') {
			return rowStatus === 'installed' || rowStatus === 'active';
		}

		return rowStatus === statusFilter;
	}

	function applyFilters() {
		var query = normalize(search ? search.value : '');
		var status = filter ? filter.value : 'all';
		var rows = list.querySelectorAll('.module-row');
		var visibleCount = 0;

		rows.forEach(function (row) {
			var text = normalize(row.getAttribute('data-module-search'));
			var rowStatus = row.getAttribute('data-module-status') || '';
			var matchQuery = query === '' || text.indexOf(query) !== -1;
			var matchStatus = matchesStatus(rowStatus, status);
			var visible = matchQuery && matchStatus;

			row.classList.toggle('d-none', !visible);

			if (visible) {
				visibleCount++;
			}
		});

		if (emptyState) {
			emptyState.classList.toggle('d-none', visibleCount > 0 || rows.length === 0);
		}
	}

	if (search) {
		search.addEventListener('input', applyFilters);
		search.addEventListener('search', applyFilters);
	}

	if (filter) {
		filter.addEventListener('change', applyFilters);
	}

	var params = new URLSearchParams(window.location.search);
	var uploadedModule = (params.get('uploaded') || '').trim();
	var shouldFilterUploaded = uploadedModule !== '' && params.get('refreshed') === '1';

	if (shouldFilterUploaded) {
		if (search) {
			search.value = uploadedModule;
		}

		if (filter) {
			filter.value = 'not_installed';
		}

		applyFilters();

		var rowSelector = '.module-row[data-module-name="' + uploadedModule.replace(/\\/g, '\\\\').replace(/"/g, '\\"') + '"]';
		var uploadedRow = list.querySelector(rowSelector);

		if (!uploadedRow) {
			if (filter) {
				filter.value = 'all';
			}

			applyFilters();
			uploadedRow = list.querySelector(rowSelector);
		}

		if (uploadedRow) {
			uploadedRow.classList.add('module-row--highlight');
			uploadedRow.scrollIntoView({ behavior: 'smooth', block: 'center' });
		}

		if (window.history.replaceState) {
			var cleanUrl = new URL(window.location.href);
			cleanUrl.searchParams.delete('uploaded');
			cleanUrl.searchParams.delete('refreshed');
			window.history.replaceState(null, '', cleanUrl.toString());
		}
	} else {
		applyFilters();
	}
}

document.querySelectorAll('.js-filter-collapse').forEach(function (card) {
	var toggle = card.querySelector('.js-filter-collapse-toggle');
	var body = card.querySelector('.js-filter-collapse-body');

	if (!toggle || !body) {
		return;
	}

	var defaults = {
		'': true,
		all: true,
		'0': true,
		date_desc: true
	};

	function hasActiveFilters() {
		var form = card.querySelector('form');

		if (!form) {
			return false;
		}

		return Array.prototype.some.call(form.querySelectorAll('input, select, textarea'), function (el) {
			if (!el.name || el.type === 'hidden' || el.disabled) {
				return false;
			}

			var value = String(el.value || '').trim().toLowerCase();

			return value !== '' && !defaults[value];
		});
	}

	function setOpen(open) {
		card.classList.toggle('is-open', open);
		toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
		body.hidden = !open;
	}

	setOpen(hasActiveFilters());

	toggle.addEventListener('click', function () {
		setOpen(body.hidden);
	});
});
