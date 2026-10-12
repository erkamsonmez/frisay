<div class="prime-container prime-page">
	<div class="checkout-success text-center py-2">
		<div class="success-icon mb-3">✓</div>
		<h1 class="prime-page__title mb-2">{'Order received'|translate}</h1>
		<p class="text-muted mb-4">{'Your order number'|translate} <strong>{$order.reference}</strong></p>

		{if $orderSuccessNote|default:''}
		<div class="alert alert-success checkout-success-note mx-auto mb-4" role="status">{$orderSuccessNote|escape|nl2br}</div>
		{/if}

		<div class="prime-page-card text-start mx-auto mb-4" style="max-width: 640px;">
			<div class="row g-3 small">
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Status'|translate}</p>
					<p class="fw-semibold mb-0">{$order.status_label}</p>
				</div>
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Payment'|translate}</p>
					<p class="fw-semibold mb-0">{$order.payment_label}</p>
				</div>
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Total'|translate}</p>
					<p class="fw-semibold mb-0">{$order.total_formatted}</p>
				</div>
				<div class="col-md-6">
					<p class="mb-1 text-muted">{'Date'|translate}</p>
					<p class="fw-semibold mb-0">{$order.date_formatted}</p>
				</div>
			</div>

			{if $hooks.order_confirmation}{$hooks.order_confirmation nofilter}{/if}

			{assign var="hasVirtual" value=false}
			{foreach $order.items as $item}
				{if $item.is_virtual}{assign var="hasVirtual" value=true}{/if}
			{/foreach}
			{if $hasVirtual}
			<div class="alert alert-info small mt-3 mb-0">
				{'Virtual product checkout notice'|translate}
				<a href="{$domain}my-account?order={$order.id_order}">{'Order Detail'|translate}</a>.
			</div>
			{/if}
		</div>

		{if $guestRegisterNotice|default:''}
		<div class="alert alert-success mx-auto mb-4" style="max-width: 640px;">{$guestRegisterNotice|escape}</div>
		{/if}

		{if $isGuestOrder|default:false}
		<div class="alert alert-info checkout-success-invite mx-auto mb-3" role="status">
			{'Guest register invite'|translate}
		</div>
		<div class="checkout-success-split mx-auto mb-4">
			<a href="{$domain}my-account?order={$order.id_order}" class="prime-btn prime-btn--outline">{'My Orders'|translate}</a>
			<button type="button" class="prime-btn prime-btn--primary" data-bs-toggle="modal" data-bs-target="#guestRegisterModal">{'Register now'|translate}</button>
		</div>
		<p class="mb-0"><a href="{$domain}" class="small text-muted">{'Continue Shopping'|translate}</a></p>

		<div class="modal fade checkout-register-modal" id="guestRegisterModal" tabindex="-1" aria-labelledby="guestRegisterTitle" aria-hidden="true"{if $guestRegisterError} data-guest-register-open="1"{/if} data-field-error="{$guestFieldError|default:''|escape}" data-msg-email-taken="{'This email is already registered'|translate|escape}" data-msg-phone-taken="{'This phone is already registered'|translate|escape}" data-login-label="{'Sign In'|translate|escape}">
			<div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
				<div class="modal-content">
					<form method="post" id="guestRegisterForm" novalidate>
						<div class="modal-header">
							<h2 class="modal-title h5 mb-0" id="guestRegisterTitle">{'Register now'|translate}</h2>
							<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|translate}"></button>
						</div>
						<div class="modal-body text-start">
							<input type="hidden" name="token" value="{$token}">
							<p class="small text-muted mb-3">{'Guest order register hint'|translate}</p>
							{if $guestRegisterError}
							<div class="alert alert-danger small">
								{$guestRegisterError|escape}
								{if $guestRegisterCanLogin|default:false}
								<button type="button" class="btn btn-link btn-sm p-0 align-baseline ms-1" data-auth-modal="login" data-auth-email="{$guestForm.email|default:''|escape}">{'Sign In'|translate}</button>
								{/if}
							</div>
							{/if}
							<div id="guestRegLiveAlert" class="alert alert-danger small d-none" role="alert"></div>
							<div class="row g-3">
								<div class="col-md-6">
									<label class="form-label small" for="guestRegName">{'Full Name'|translate}</label>
									<input type="text" name="full_name" id="guestRegName" class="form-control" value="{$guestForm.full_name|default:$order.customer_name|escape}" required autocomplete="name">
								</div>
								<div class="col-md-6">
									<label class="form-label small" for="guestRegEmail">{'Email'|translate}</label>
									<input type="email" name="email" id="guestRegEmail" class="form-control{if ($guestFieldError|default:'') == 'email'} is-invalid{/if}" value="{$guestForm.email|default:$order.customer_email|escape}" required autocomplete="email">
								</div>
								<div class="col-md-6">
									<label class="form-label small" for="guestRegPhone">{'Phone'|translate}</label>
									<input type="tel" name="phone" id="guestRegPhone" class="form-control phone-input{if ($guestFieldError|default:'') == 'phone'} is-invalid{/if}" value="{$guestForm.phone|default:$order.customer_phone|escape}" required autocomplete="tel">
								</div>
								<div class="col-md-6">
									<label class="form-label small" for="guestRegCity">{'City'|translate}</label>
									<input type="text" name="address_city" id="guestRegCity" class="form-control" value="{$guestForm.address_city|default:$order.address_city|escape}" required>
								</div>
								<div class="col-md-6">
									<label class="form-label small" for="guestRegDistrict">{'District'|translate}</label>
									<input type="text" name="address_district" id="guestRegDistrict" class="form-control" value="{$guestForm.address_district|default:$order.address_district|escape}" required>
								</div>
								<div class="col-12">
									<label class="form-label small" for="guestRegAddress">{'Street Address'|translate}</label>
									<textarea name="address_text" id="guestRegAddress" class="form-control" rows="2" required>{$guestForm.address_text|default:$order.address_text|escape}</textarea>
								</div>
								<div class="col-md-6">
									<label class="form-label small" for="guestRegPassword">{'Password'|translate}</label>
									<div class="auth-password-wrap">
										<input type="password" name="password" id="guestRegPassword" class="form-control" required autocomplete="new-password" minlength="{$passwordPolicy.minLength|default:8}">
										<button type="button" class="auth-password-toggle" data-target="#guestRegPassword" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
										</button>
									</div>
									<div class="invalid-feedback" id="guestRegPasswordMsg"></div>
								</div>
								<div class="col-md-6">
									<label class="form-label small" for="guestRegPassword2">{'Password repeat'|translate}</label>
									<div class="auth-password-wrap">
										<input type="password" name="password2" id="guestRegPassword2" class="form-control" required autocomplete="new-password" minlength="{$passwordPolicy.minLength|default:8}">
										<button type="button" class="auth-password-toggle" data-target="#guestRegPassword2" data-show-label="{'Show password'|translate|escape}" data-hide-label="{'Hide password'|translate|escape}" aria-label="{'Show password'|translate}">
											<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0"/><circle cx="12" cy="12" r="3"/></svg>
										</button>
									</div>
									<div class="invalid-feedback" id="guestRegPassword2Msg"></div>
								</div>
							</div>
							<p class="small text-muted mt-2 mb-0" id="guestRegPasswordHint">{if $passwordPolicy.hint|default:''}{$passwordPolicy.hint|escape}{else}{'Password requirements hint'|translate}{/if}</p>
						</div>
						<div class="modal-footer">
							<button type="submit" name="registerFromOrder" value="1" class="prime-btn prime-btn--primary">{'Register now'|translate}</button>
						</div>
					</form>
				</div>
			</div>
		</div>
		{else}
		<div class="d-flex flex-wrap gap-2 justify-content-center">
			<a href="{$domain}my-account?order={$order.id_order}" class="prime-btn prime-btn--primary">{'Order Detail'|translate}</a>
			<a href="{$domain}my-account" class="prime-btn prime-btn--outline">{'My Orders'|translate}</a>
			<a href="{$domain}" class="prime-btn prime-btn--outline">{'Continue Shopping'|translate}</a>
		</div>
		{/if}
	</div>
</div>
{if $isGuestOrder|default:false}
<script>
document.addEventListener('DOMContentLoaded', function () {
	var el = document.getElementById('guestRegisterModal');
	var form = document.getElementById('guestRegisterForm');
	var pwd = document.getElementById('guestRegPassword');
	var pwd2 = document.getElementById('guestRegPassword2');
	var emailEl = document.getElementById('guestRegEmail');
	var phoneEl = document.getElementById('guestRegPhone');
	var liveAlert = document.getElementById('guestRegLiveAlert');
	var msg1 = document.getElementById('guestRegPasswordMsg');
	var msg2 = document.getElementById('guestRegPassword2Msg');
	var policy = {$passwordPolicyJson|default:'{}' nofilter};
	var taken = { email: false, phone: false };
	var emailTimer = null;
	var phoneTimer = null;
	var authUrl = (typeof authApiUrl !== 'undefined' ? authApiUrl : (domain + 'api/auth.php'));
	var csrf = (typeof csrfToken !== 'undefined' ? csrfToken : '');
	var msgEmailTaken = el ? (el.getAttribute('data-msg-email-taken') || '') : '';
	var msgPhoneTaken = el ? (el.getAttribute('data-msg-phone-taken') || '') : '';
	var loginLabel = el ? (el.getAttribute('data-login-label') || '') : '';
	var fieldError = el ? (el.getAttribute('data-field-error') || '') : '';

	function openGuestRegister() {
		if (!el || !window.bootstrap || !bootstrap.Modal) {
			return;
		}
		var inst = bootstrap.Modal.getInstance(el);
		if (!inst) {
			inst = new bootstrap.Modal(el);
		}
		inst.show();
	}

	function policyError(value) {
		var min = parseInt(policy.minLength, 10) || 8;
		if (!value || value.length < min) {
			return policy.tooShort || '';
		}
		if (policy.requireComplexity && (!/[A-Za-z]/.test(value) || !/[0-9]/.test(value))) {
			return policy.needComplex || '';
		}
		return '';
	}

	function validatePasswords(showEmpty) {
		if (!pwd || !pwd2) {
			return true;
		}
		var value = pwd.value || '';
		var value2 = pwd2.value || '';
		var err1 = '';
		var err2 = '';

		if (value !== '' || showEmpty) {
			err1 = policyError(value);
		}
		if ((value2 !== '' || showEmpty) && !err1) {
			if (value2 === '') {
				err2 = policy.mismatch || '';
			} else if (value !== value2) {
				err2 = policy.mismatch || '';
			}
		} else if (value2 !== '' && value !== value2) {
			err2 = policy.mismatch || '';
		}

		pwd.classList.toggle('is-invalid', !!err1);
		pwd.classList.toggle('is-ok', !err1 && value !== '');
		pwd2.classList.toggle('is-invalid', !!err2);
		pwd2.classList.toggle('is-ok', !err2 && value2 !== '' && value === value2);
		if (msg1) {
			msg1.textContent = err1;
			msg1.classList.toggle('d-block', !!err1);
		}
		if (msg2) {
			msg2.textContent = err2;
			msg2.classList.toggle('d-block', !!err2);
		}
		pwd.setCustomValidity(err1);
		pwd2.setCustomValidity(err2);

		return !err1 && !err2 && value !== '' && value2 !== '';
	}

	function showLiveAlert(message, withLogin) {
		if (!liveAlert) {
			return;
		}
		liveAlert.innerHTML = '';
		if (!message) {
			liveAlert.classList.add('d-none');
			return;
		}
		liveAlert.appendChild(document.createTextNode(message));
		if (withLogin && loginLabel) {
			liveAlert.appendChild(document.createTextNode(' '));
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'btn btn-link btn-sm p-0 align-baseline';
			btn.setAttribute('data-auth-modal', 'login');
			btn.textContent = loginLabel;
			liveAlert.appendChild(btn);
		}
		liveAlert.classList.remove('d-none');
	}

	function refreshTakenAlert() {
		if (taken.email) {
			showLiveAlert(msgEmailTaken, true);
			return;
		}
		if (taken.phone) {
			showLiveAlert(msgPhoneTaken, true);
			return;
		}
		showLiveAlert('');
	}

	function setEmailTaken(isTaken) {
		taken.email = !!isTaken;
		if (emailEl) {
			emailEl.classList.toggle('is-invalid', taken.email);
		}
		refreshTakenAlert();
	}

	function setPhoneTaken(isTaken) {
		taken.phone = !!isTaken;
		if (phoneEl) {
			phoneEl.classList.toggle('is-invalid', taken.phone);
		}
		refreshTakenAlert();
	}

	function checkGuestEmail() {
		if (!emailEl) {
			return;
		}
		var email = (emailEl.value || '').trim();
		if (!email || email.indexOf('@') < 1) {
			setEmailTaken(false);
			return;
		}
		if (window.jQuery) {
			jQuery.post(authUrl, { action: 'check-email', email: email, token: csrf }).done(function (res) {
				setEmailTaken(!!(res && res.exists));
			});
		}
	}

	function checkGuestPhone() {
		if (!phoneEl) {
			return;
		}
		var phone = (phoneEl.value || '').trim();
		var digits = phone.replace(/\D+/g, '');
		if (digits.length < 7) {
			setPhoneTaken(false);
			return;
		}
		if (window.jQuery) {
			jQuery.post(authUrl, { action: 'check-phone', phone: phone, token: csrf }).done(function (res) {
				setPhoneTaken(!!(res && res.exists));
			});
		}
	}

	function focusFirstError() {
		if (phoneEl && (taken.phone || fieldError === 'phone' || phoneEl.classList.contains('is-invalid'))) {
			phoneEl.focus();
			return;
		}
		if (emailEl && (taken.email || fieldError === 'email' || emailEl.classList.contains('is-invalid'))) {
			emailEl.focus();
			return;
		}
		if (pwd && pwd.classList.contains('is-invalid')) {
			pwd.focus();
			return;
		}
		if (pwd2 && pwd2.classList.contains('is-invalid')) {
			pwd2.focus();
			return;
		}
		if (pwd) {
			pwd.focus();
		}
	}

	if (el && window.bootstrap && bootstrap.Modal) {
		el.addEventListener('shown.bs.modal', function () {
			focusFirstError();
		});
		document.querySelectorAll('[data-bs-target="#guestRegisterModal"]').forEach(function (btn) {
			btn.addEventListener('click', function (e) {
				e.preventDefault();
				openGuestRegister();
			});
		});
		if (el.getAttribute('data-guest-register-open') === '1') {
			openGuestRegister();
		}
	}

	if (emailEl) {
		emailEl.addEventListener('blur', checkGuestEmail);
		emailEl.addEventListener('input', function () {
			if (emailTimer) {
				clearTimeout(emailTimer);
			}
			emailTimer = setTimeout(checkGuestEmail, 400);
		});
	}
	if (phoneEl) {
		phoneEl.addEventListener('blur', checkGuestPhone);
		phoneEl.addEventListener('input', function () {
			if (phoneTimer) {
				clearTimeout(phoneTimer);
			}
			phoneTimer = setTimeout(checkGuestPhone, 400);
		});
	}

	if (pwd) {
		pwd.addEventListener('input', function () {
			validatePasswords(false);
			if (!taken.email && !taken.phone) {
				showLiveAlert('');
			}
		});
	}
	if (pwd2) {
		pwd2.addEventListener('input', function () {
			validatePasswords(false);
			if (!taken.email && !taken.phone) {
				showLiveAlert('');
			}
		});
	}
	if (form) {
		form.addEventListener('submit', function (e) {
			if (taken.email || taken.phone) {
				e.preventDefault();
				refreshTakenAlert();
				openGuestRegister();
				focusFirstError();
				return;
			}
			if (validatePasswords(true)) {
				if (!taken.email && !taken.phone) {
					showLiveAlert('');
				}
				return;
			}
			e.preventDefault();
			var message = (pwd && pwd.validationMessage) || (pwd2 && pwd2.validationMessage) || policy.mismatch || '';
			showLiveAlert(message);
			openGuestRegister();
			focusFirstError();
		});
	}

	if (fieldError === 'phone') {
		taken.phone = true;
	} else if (fieldError === 'email') {
		taken.email = true;
	}
	checkGuestEmail();
	checkGuestPhone();
});
</script>
{/if}
