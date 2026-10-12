(function () {
	'use strict';

	function relocateToolbar() {
		var bar = document.getElementById('exportExcelToolbar');
		var mount = document.getElementById('exportExcelMount');

		if (!bar || !mount || bar.parentNode === mount) {
			return;
		}

		mount.appendChild(bar);
	}

	function syncExportFields() {
		var bar = document.getElementById('exportExcelToolbar');
		var formId = bar ? bar.getAttribute('data-filter-form') : '';
		var src = formId ? document.getElementById(formId) : null;
		var dests = document.querySelectorAll('.js-export-excel-form');

		if (!bar || !src || !dests.length) {
			return;
		}

		dests.forEach(function (dest) {
			src.querySelectorAll('input[name], select[name]').forEach(function (el) {
				copyField(el, dest);
			});
			document.querySelectorAll('[form="' + formId + '"]').forEach(function (el) {
				copyField(el, dest);
			});
		});

		function copyField(el, dest) {
			var name = el.getAttribute('name');

			if (!name) {
				return;
			}

			var target = dest.querySelector('[name="' + name + '"]');

			if (target) {
				target.value = el.value;
			}
		}
	}

	function bindExportSync() {
		document.querySelectorAll('.js-export-excel-form').forEach(function (dest) {
			if (dest.getAttribute('data-excel-bound') === '1') {
				return;
			}

			dest.setAttribute('data-excel-bound', '1');
			dest.addEventListener('submit', syncExportFields);
		});
	}

	function init() {
		relocateToolbar();
		bindExportSync();
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
