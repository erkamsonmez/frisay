(function () {
	'use strict';

	function isFormulaMode() {
		var mode = document.getElementById('adjustMode');

		return mode && mode.value === 'formula';
	}

	function toggleModeUi() {
		var formula = isFormulaMode();
		var simpleBlocks = document.querySelectorAll('.js-bp-simple');
		var fields = document.querySelector('.js-bp-fields');
		var formulaBlock = document.querySelector('.js-bp-formula');
		var i;

		for (i = 0; i < simpleBlocks.length; i++) {
			simpleBlocks[i].hidden = formula;
		}

		if (fields) {
			fields.hidden = formula;
		}

		if (formulaBlock) {
			formulaBlock.hidden = !formula;
		}

		updateHint();
	}

	function updateHint() {
		var mode = document.getElementById('adjustMode');
		var hint = document.getElementById('adjustValueHint');

		if (!mode || !hint) {
			return;
		}

		if (mode.value === 'fixed') {
			hint.textContent = 'Örn. satış fiyatına 50 TL zam için 50 yazın.';
		} else {
			hint.textContent = 'Örn. satış fiyatına %10 zam için 10 yazın.';
		}
	}

	function bindMarginRows() {
		var tbody = document.getElementById('bpRateBody');
		var addBtn = document.getElementById('bpRateAdd');

		if (!tbody || !addBtn) {
			return;
		}

		function rowHtml() {
			return '<tr>'
				+ '<td><input type="text" class="form-control form-control-sm" name="rate_min[]" value="" inputmode="decimal" placeholder="0"></td>'
				+ '<td><input type="text" class="form-control form-control-sm" name="rate_max[]" value="" inputmode="decimal" placeholder="∞"></td>'
				+ '<td><input type="text" class="form-control form-control-sm" name="rate_percent[]" value="" inputmode="decimal" placeholder="12"></td>'
				+ '<td><button type="button" class="btn btn-sm btn-outline-secondary js-bp-rate-del" aria-label="Satırı sil">&times;</button></td>'
				+ '</tr>';
		}

		addBtn.addEventListener('click', function () {
			tbody.insertAdjacentHTML('beforeend', rowHtml());
		});

		tbody.addEventListener('click', function (event) {
			var btn = event.target.closest ? event.target.closest('.js-bp-rate-del') : null;

			if (!btn) {
				return;
			}

			var tr = btn.closest('tr');

			if (!tr) {
				return;
			}

			if (tbody.querySelectorAll('tr').length <= 1) {
				var inputs = tr.querySelectorAll('input');
				var i;

				for (i = 0; i < inputs.length; i++) {
					inputs[i].value = '';
				}

				return;
			}

			tr.parentNode.removeChild(tr);
		});
	}

	function bindFormulaExamples() {
		var buttons = document.querySelectorAll('.js-bp-formula-ex');
		var input = document.getElementById('bpFormula');
		var i;

		if (!input) {
			return;
		}

		for (i = 0; i < buttons.length; i++) {
			buttons[i].addEventListener('click', function () {
				var formula = this.getAttribute('data-formula') || '';
				var mode = document.getElementById('adjustMode');

				if (mode && mode.value !== 'formula') {
					mode.value = 'formula';
					toggleModeUi();
				}

				input.value = formula;
				input.focus();
			});
		}
	}

	function bindApplyConfirm() {
		var btn = document.querySelector('.js-bulk-pricing-apply');

		if (!btn) {
			return;
		}

		btn.addEventListener('click', function (event) {
			var checkedFields = document.querySelectorAll('#bulkPricingForm input[name^="field_"]:checked').length;
			var formulaInput = document.getElementById('bpFormula');
			var formula = formulaInput ? String(formulaInput.value || '').trim() : '';

			if (!isFormulaMode() && checkedFields === 0) {
				event.preventDefault();
				if (window.AdminToast) {
					AdminToast.show('En az bir fiyat alanı seçin.', 'warning');
				}
				return;
			}

			if (isFormulaMode() && formula === '') {
				event.preventDefault();
				if (window.AdminToast) {
					AdminToast.show('Formül yazın. Örn. (a+75)/0.95', 'warning');
				}
				return;
			}

			if (btn.dataset.adminConfirmed === '1') {
				return;
			}

			event.preventDefault();
			var message = isFormulaMode()
				? 'Seçili filtrelere uyan ürünlerin satış fiyatı formüle göre güncellenecek. Devam edilsin mi?'
				: 'Seçili filtrelere uyan tüm ürünlerin fiyatları güncellenecek. Devam edilsin mi?';
			var ask = window.AdminConfirm && AdminConfirm.ask
				? AdminConfirm.ask({ message: message })
				: Promise.resolve(false);

			ask.then(function (ok) {
				if (!ok) {
					return;
				}

				btn.dataset.adminConfirmed = '1';
				var form = btn.form || btn.closest('form');
				if (form && typeof form.requestSubmit === 'function') {
					form.requestSubmit(btn);
				} else if (form) {
					form.submit();
				}
			});
		});
	}

	function bindExcelImportConfirm() {
		var btn = document.querySelector('.js-bulk-pricing-excel-import');

		if (!btn) {
			return;
		}

		btn.addEventListener('click', function (event) {
			var fileInput = document.getElementById('bulkPricingExcelFile');

			if (!fileInput || !fileInput.files || !fileInput.files.length) {
				event.preventDefault();
				if (window.AdminToast) {
					AdminToast.show('Önce bir Excel dosyası seçin.', 'warning');
				}
				return;
			}

			if (btn.dataset.adminConfirmed === '1') {
				return;
			}

			event.preventDefault();
			var message = 'Excel’deki fiyatlar ürünlere yazılacak. Devam edilsin mi?';
			var ask = window.AdminConfirm && AdminConfirm.ask
				? AdminConfirm.ask({ message: message })
				: Promise.resolve(window.confirm(message));

			ask.then(function (ok) {
				if (!ok) {
					return;
				}

				btn.dataset.adminConfirmed = '1';
				var form = btn.form || document.getElementById('bulkPricingExcelImportForm');
				if (form && typeof form.requestSubmit === 'function') {
					form.requestSubmit(btn);
				} else if (form) {
					form.submit();
				}
			});
		});
	}

	document.addEventListener('DOMContentLoaded', function () {
		toggleModeUi();
		bindApplyConfirm();
		bindExcelImportConfirm();
		bindFormulaExamples();
		bindMarginRows();

		var mode = document.getElementById('adjustMode');

		if (mode) {
			mode.addEventListener('change', toggleModeUi);
		}
	});
})();
