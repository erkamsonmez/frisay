(function () {
	'use strict';

	function initRails() {
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
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initRails);
	} else {
		initRails();
	}
})();
