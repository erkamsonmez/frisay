/**
 * Theme4 in-place editor — runs inside the storefront iframe.
 */
(function () {
	'use strict';

	if (window.parent === window) {
		return;
	}

	document.documentElement.classList.add('t4-editing');

	function markBody() {
		if (document.body) {
			document.body.classList.add('t4-editing');
		}
	}

	if (document.body) {
		markBody();
	} else {
		document.addEventListener('DOMContentLoaded', markBody);
	}

	function send(payload) {
		window.parent.postMessage(Object.assign({ source: 't4-preview' }, payload), '*');
	}

	function clearHover() {
		document.querySelectorAll('.t4-edit-hover').forEach(function (el) {
			el.classList.remove('t4-edit-hover');
		});
	}

	document.addEventListener('mouseover', function (e) {
		var hit = e.target.closest('[data-t4-chrome], [data-t4-widget], [data-t4-zone-hit]');
		clearHover();
		if (hit) {
			hit.classList.add('t4-edit-hover');
		}
	}, true);

	document.addEventListener('mouseout', function (e) {
		if (!e.relatedTarget || !e.currentTarget.contains(e.relatedTarget)) {
			clearHover();
		}
	}, true);

	document.addEventListener('click', function (e) {
		var chromeHit = e.target.closest('[data-t4-chrome]');
		var hit = e.target.closest('[data-t4-widget], [data-t4-zone-hit]');
		var link = e.target.closest('a, button');

		if (link || hit || chromeHit) {
			e.preventDefault();
			e.stopPropagation();
		}

		if (chromeHit) {
			document.querySelectorAll('.t4-edit-selected').forEach(function (el) {
				el.classList.remove('t4-edit-selected');
			});
			chromeHit.classList.add('t4-edit-selected');
			send({
				op: 'select',
				chrome: chromeHit.getAttribute('data-t4-chrome') || ''
			});
			return;
		}

		if (!hit) {
			return;
		}

		document.querySelectorAll('.t4-edit-selected').forEach(function (el) {
			el.classList.remove('t4-edit-selected');
		});
		hit.classList.add('t4-edit-selected');

		send({
			op: 'select',
			zone: hit.getAttribute('data-t4-zone') || hit.getAttribute('data-t4-zone-hit') || '',
			row: hit.getAttribute('data-t4-row') || '',
			widget: hit.getAttribute('data-t4-widget') || ''
		});
	}, true);

	window.addEventListener('message', function (e) {
		var data = e.data;
		if (!data || data.source !== 't4-admin') {
			return;
		}

		if (data.op === 'highlight' && data.widget) {
			document.querySelectorAll('.t4-edit-selected').forEach(function (el) {
				el.classList.remove('t4-edit-selected');
			});
			var el = document.querySelector('[data-t4-widget="' + data.widget + '"]');
			if (el) {
				el.classList.add('t4-edit-selected');
				el.scrollIntoView({ block: 'center', behavior: 'smooth' });
			}
		}
	});
})();
