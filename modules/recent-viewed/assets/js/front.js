(function () {
	'use strict';

	document.addEventListener('click', function (e) {
		var tab = e.target.closest('[data-rv-tab]');
		if (!tab) {
			return;
		}
		var root = tab.closest('[data-rv-cart]');
		if (!root) {
			return;
		}
		var name = tab.getAttribute('data-rv-tab') || '';
		root.querySelectorAll('[data-rv-tab]').forEach(function (btn) {
			var on = btn === tab;
			btn.classList.toggle('is-active', on);
			btn.setAttribute('aria-selected', on ? 'true' : 'false');
		});
		root.querySelectorAll('[data-rv-panel]').forEach(function (panel) {
			var on = panel.getAttribute('data-rv-panel') === name;
			panel.classList.toggle('is-active', on);
			if (on) {
				panel.removeAttribute('hidden');
			} else {
				panel.setAttribute('hidden', 'hidden');
			}
		});
	});
})();
