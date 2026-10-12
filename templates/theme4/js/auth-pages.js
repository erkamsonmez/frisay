(function () {
	'use strict';

	var eyeIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>';
	var eyeOffIcon = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49"/><path d="M14.084 14.158a3 3 0 0 1-4.242-4.242"/><path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.205"/><path d="m2 2 20 20"/></svg>';

	function resolvePasswordInput(btn) {
		var selector = btn.getAttribute('data-target') || btn.getAttribute('data-password-target') || '';
		var input = null;

		if (selector) {
			try {
				input = document.querySelector(selector);
			} catch (err) {
				input = null;
			}
		}

		if (!input) {
			var wrap = btn.closest('.auth-password-wrap');
			input = wrap ? wrap.querySelector('input') : null;
		}

		return input;
	}

	function togglePasswordVisibility(btn) {
		var input = resolvePasswordInput(btn);
		if (!input) {
			return;
		}

		var showAsText = (input.getAttribute('type') || input.type) === 'password';
		input.setAttribute('type', showAsText ? 'text' : 'password');
		try {
			input.type = showAsText ? 'text' : 'password';
		} catch (err) {
			/* older browsers may block type changes */
		}

		btn.classList.toggle('is-visible', showAsText);
		btn.innerHTML = showAsText ? eyeOffIcon : eyeIcon;

		var showLabel = btn.getAttribute('data-show-label') || 'Show password';
		var hideLabel = btn.getAttribute('data-hide-label') || 'Hide password';
		btn.setAttribute('aria-label', showAsText ? hideLabel : showLabel);
		btn.setAttribute('aria-pressed', showAsText ? 'true' : 'false');
	}

	document.addEventListener('click', function (e) {
		var btn = e.target && e.target.closest ? e.target.closest('.auth-password-toggle') : null;
		if (!btn) {
			return;
		}
		e.preventDefault();
		e.stopPropagation();
		togglePasswordVisibility(btn);
	});
})();
