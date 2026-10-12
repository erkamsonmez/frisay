(function () {
	'use strict';

	function getSelectedVariationId() {
		var hidden = document.getElementById('selectedVariationId');
		if (hidden && hidden.value) {
			return parseInt(hidden.value, 10) || 0;
		}

		var selected = document.querySelector('[name="id_variation"]:checked, select[name="id_variation"]');
		if (!selected) {
			return 0;
		}

		return parseInt(selected.value, 10) || 0;
	}

	function showMessage(el, text, type) {
		if (!el) {
			return;
		}

		el.hidden = false;
		el.textContent = text;
		el.classList.remove('is-success', 'is-error');
		el.classList.add(type === 'success' ? 'is-success' : 'is-error');
	}

	document.querySelectorAll('[data-alert-stock-form]').forEach(function (form) {
		var wrap = form.closest('.alert-stock-notify');
		var apiUrl = wrap ? wrap.getAttribute('data-api-url') : '';
		var messageEl = wrap ? wrap.querySelector('[data-alert-stock-message]') : null;
		var variationInput = form.querySelector('[data-alert-variation-input]');

		form.addEventListener('submit', function (event) {
			event.preventDefault();

			if (!apiUrl) {
				return;
			}

			if (variationInput) {
				variationInput.value = String(getSelectedVariationId());
			}

			var emailInput = form.querySelector('[name="email"]');
			var email = emailInput ? String(emailInput.value || '').trim() : '';

			if (!email) {
				showMessage(messageEl, 'E-posta gerekli', 'error');
				if (emailInput) {
					emailInput.focus();
				}
				return;
			}

			var submitBtn = form.querySelector('[type="submit"]');

			if (submitBtn) {
				submitBtn.disabled = true;
			}

			var body = new FormData(form);

			fetch(apiUrl, {
				method: 'POST',
				body: body,
				credentials: 'same-origin',
				headers: {
					'X-Requested-With': 'XMLHttpRequest'
				}
			})
				.then(function (response) {
					return response.json();
				})
				.then(function (data) {
					showMessage(messageEl, data.message || '', data.success ? 'success' : 'error');

					if (data.success && typeof window.showToast === 'function') {
						window.showToast(data.message, 'success');
					}

					if (data.success && emailInput) {
						var savedEmail = emailInput.value;
						form.reset();
						emailInput.value = savedEmail;
					}
				})
				.catch(function () {
					showMessage(messageEl, 'Bir hata oluştu', 'error');
				})
				.finally(function () {
					if (submitBtn) {
						submitBtn.disabled = false;
					}
				});
		});
	});
})();
