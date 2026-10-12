<div class="prime-container prime-page checkout-page">
	<div class="checkout-page__hero">
		<div>
			<h1 class="checkout-page__title">{'Checkout page title'|translate}</h1>
			<p class="checkout-page__lead">{'Checkout page description'|translate}</p>
		</div>
		<nav class="checkout-steps" aria-label="Checkout">
			<span class="checkout-steps__item is-done">{'My Cart'|translate}</span>
			<span class="checkout-steps__sep"></span>
			<span class="checkout-steps__item is-active">{'Checkout page title'|translate}</span>
			<span class="checkout-steps__sep"></span>
			<span class="checkout-steps__item">{'Order confirmation'|translate}</span>
		</nav>
	</div>

{if $orderError}
<div class="visually-hidden" id="checkoutOrderErrorText">{$orderError|escape}</div>
{/if}

<div class="row g-4 checkout-page__grid">
	<div class="col-lg-7">
		{if $isLoggedIn}
		<div class="checkout-member-bar">
			<div class="checkout-member-bar__icon">
			<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-user-icon lucide-user"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
			</div>
			<div>
				<strong>{'Checkout logged in as'|translate}</strong>
				<span>{$customer.user_full_name|escape}</span>
			</div>
			<a href="{$domain}my-account" class="checkout-member-bar__link">{'My Account'|translate}</a>
		</div>
		{/if}

		<form method="post" action="{$domain}checkout" class="checkout-form" id="checkoutForm" data-msg-email-taken="{'This email is already registered'|translate|escape}" data-msg-phone-taken="{'This phone is already registered'|translate|escape}" data-login-label="{'Sign In'|translate|escape}">
			<input type="hidden" name="placeOrder" value="1">
			<input type="hidden" name="token" value="{$token}">

			<section class="checkout-section">
				<header class="checkout-section__head">
					<span class="checkout-section__step">1</span>
					<h2>{'Checkout step delivery'|translate}</h2>
					{if !$isLoggedIn}
					<button type="button" class="checkout-section__login" data-auth-modal="login">
						{'Sign In'|translate}
					</button>
					{/if}
				</header>
				<div class="checkout-section__body">
					{if $addresses|@count}
					<div class="saved-address-list mb-3">
						{foreach $addresses as $addr}
						<label class="checkout-address-option">
							<input type="radio" name="id_address" value="{$addr.id_address}" class="checkout-address-radio"
								data-full-name="{$addr.full_name|escape}"
								data-phone="{$addr.phone|escape}"
								data-city="{$addr.city|escape}"
								data-district="{$addr.district|escape}"
								data-address-text="{$addr.address_text|escape}"
								data-company-name="{$addr.company_name|escape}"
								data-tax-office="{$addr.tax_office|escape}"
								data-tax-number="{$addr.tax_number|escape}"
								{if $selectedAddressId == $addr.id_address} checked{/if}>
							<span class="checkout-address-option__content">
								<strong>{if $addr.label}{$addr.label|escape}{else}{'Address'|translate}{/if}</strong>
								{if $addr.is_default}<span class="checkout-address-option__tag">{'Default'|translate}</span>{/if}
								<span class="checkout-address-option__meta">
									{$addr.full_name|escape} · {$addr.phone|escape}<br>
									{$addr.city|escape} / {$addr.district|escape} — {$addr.address_text|escape}
								</span>
							</span>
						</label>
						{/foreach}
						<label class="checkout-address-option">
							<input type="radio" name="id_address" value="0" class="checkout-address-radio"
								{if $selectedAddressId == 0} checked{/if}>
							<span class="checkout-address-option__content">
								<strong>{'Use new address'|translate}</strong>
							</span>
						</label>
					</div>
					{else}
					<input type="hidden" name="id_address" value="0">
					{/if}

					<div id="checkoutAddressFields" class="checkout-fields">
						<div class="row g-3">
							<div class="col-6 col-md-6">
								<label class="form-label">{'Full Name'|translate}</label>
								<input type="text" name="customer_name" id="checkoutCustomerName" class="form-control checkout-field" value="{$formData.customer_name|escape}" required>
							</div>
							<div class="col-6 col-md-6">
								<label class="form-label">{'Phone'|translate}</label>
								<input type="tel" name="customer_phone" id="checkoutCustomerPhone" class="form-control phone-input checkout-field" value="{$formData.customer_phone|escape}" required>
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Email'|translate}{if !$isLoggedIn} <span class="text-danger">*</span>{/if}</label>
								<input type="email" name="customer_email" id="checkoutCustomerEmail" class="form-control checkout-field" value="{$formData.customer_email|escape}" placeholder="{'Email placeholder'|translate}"{if !$isLoggedIn} required{/if} autocomplete="email">
								{if !$isLoggedIn}
								<div class="form-text" id="checkoutGuestEmailHint">{'Checkout guest email hint'|translate}</div>
								{/if}
							</div>
							<div class="col-6 col-md-6">
								<label class="form-label">{'City'|translate}</label>
								<input type="text" name="address_city" id="checkoutCity" class="form-control checkout-field" value="{$formData.address_city|escape}" placeholder="{'City placeholder'|translate}" required>
							</div>
							<div class="col-6 col-md-6">
								<label class="form-label">{'District'|translate}</label>
								<input type="text" name="address_district" id="checkoutDistrict" class="form-control checkout-field" value="{$formData.address_district|escape}" placeholder="{'District placeholder'|translate}" required>
							</div>
							<div class="col-12">
								<label class="form-label">{'Street Address'|translate}</label>
								<textarea name="address_text" id="checkoutAddressText" class="form-control checkout-field" rows="3" placeholder="{'Street placeholder'|translate}" required>{$formData.address_text|escape}</textarea>
							</div>
							{if $isLoggedIn}
							<div class="col-12" id="saveAddressBlock">
								<div class="form-check mb-2">
									<input class="form-check-input" type="checkbox" name="save_address" id="saveAddressCheck" value="1">
									<label class="form-check-label" for="saveAddressCheck">{'Save this address'|translate}</label>
								</div>
								<div id="saveAddressExtra" class="d-none">
									<label class="form-label">{'Address Title'|translate}</label>
									<input type="text" name="address_label" class="form-control mb-2" placeholder="{'Address label placeholder'|translate}" value="{$formData.address_label|escape}">
									<div class="form-check">
										<input class="form-check-input" type="checkbox" name="set_default_address" id="setDefaultAddressCheck" value="1">
										<label class="form-check-label" for="setDefaultAddressCheck">{'Set as default address'|translate}</label>
									</div>
								</div>
							</div>
							{/if}
							<div class="col-12">
								<label class="form-label">{'Order Note Optional'|translate}</label>
								<textarea name="note" class="form-control" rows="2" placeholder="{'Order note placeholder'|translate}">{$formData.note|escape}</textarea>
							</div>
						</div>
					</div>
				</div>
			</section>

			<details class="checkout-section checkout-section--billing"{if $billingOpen|default:false} open{/if}>
				<summary class="checkout-section__head">
					<span class="checkout-section__step">2</span>
					<h2>{'Checkout step billing'|translate} <small>({'Billing Information Optional'|translate})</small></h2>
					<svg class="checkout-section__chevron" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
				</summary>
				<div class="checkout-section__body">
					<p class="checkout-section__hint">{'Billing invoice hint'|translate}</p>
					<div class="row g-3">
						<div class="col-12">
							<label class="form-label">{'Company Name'|translate}</label>
							<input type="text" name="company_name" class="form-control" value="{$formData.company_name|escape}" placeholder="{'Company placeholder'|translate}">
						</div>
						<div class="col-6 col-md-6">
							<label class="form-label">{'Tax Office'|translate}</label>
							<input type="text" name="tax_office" class="form-control" value="{$formData.tax_office|escape}" placeholder="{'Tax office placeholder'|translate}">
						</div>
						<div class="col-6 col-md-6">
							<label class="form-label">{'Tax ID'|translate}</label>
							<input type="text" name="tax_number" class="form-control" value="{$formData.tax_number|escape}" placeholder="{'Tax id placeholder'|translate}" maxlength="20" inputmode="numeric">
						</div>
					</div>
				</div>
			</details>

			{if $cartRequiresShipping && $cargoOptions}
			<section class="checkout-section">
				<header class="checkout-section__head">
					<span class="checkout-section__step">3</span>
					<h2>{'Cargo'|translate}</h2>
				</header>
				<div class="checkout-section__body">
					<div class="checkout-payment-list" id="checkoutCargoList">
						{foreach $cargoOptions as $cargoOpt}
						<label class="checkout-payment-card">
							<input type="radio" name="id_cargo" value="{$cargoOpt.id_cargo}"{if $cargoOpt.selected || $formData.id_cargo == $cargoOpt.id_cargo} checked{/if} required>
							<span class="checkout-payment-card__body">
								<span class="checkout-payment-card__icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
								</span>
								<span>
									<strong>{$cargoOpt.name|escape}</strong>
									<small class="checkout-cargo-fee" data-cargo-id="{$cargoOpt.id_cargo}">{$cargoOpt.fee_formatted}</small>
								</span>
							</span>
						</label>
						{/foreach}
					</div>
				</div>
			</section>
			{/if}

			<section class="checkout-section">
				<header class="checkout-section__head">
					<span class="checkout-section__step">{if $cartRequiresShipping && $cargoOptions}4{else}3{/if}</span>
					<h2>{'Checkout step payment'|translate}</h2>
				</header>
				<div class="checkout-section__body">
					{if $hooks.order_payment}
					<div class="checkout-payment-list" id="checkoutPaymentList" data-default-payment="{$formData.payment_method|escape}">
						{$hooks.order_payment nofilter}
					</div>
					{else}
					<div class="checkout-payment-list" id="checkoutPaymentList" data-default-payment="{$formData.payment_method|escape}">
						{if $paymentMethods|@count > 0}
						{foreach $paymentMethods as $method}
						{if $method.id == 'cash_on_delivery' && $cartHasVirtual}{continue}{/if}
						<label class="checkout-payment-card">
							<input type="radio" name="payment_method" value="{$method.id|escape}"{if ($formData.payment_method|default:$selectedPaymentMethod) == $method.id} checked{/if} required>
							<span class="checkout-payment-card__body">
								<span class="checkout-payment-card__icon">
									<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-landmark-icon lucide-landmark"><path d="M10 18v-7"/><path d="M11.119 2.205a2 2 0 0 1 1.762 0l7.84 3.846A.5.5 0 0 1 20.5 7h-17a.5.5 0 0 1-.22-.949z"/><path d="M14 18v-7"/><path d="M18 18v-7"/><path d="M3 22h18"/><path d="M6 18v-7"/></svg>
								</span>
								<span>
									<strong>{$method.label|escape}</strong>
								</span>
							</span>
						</label>
						{/foreach}
						{else}
						<p class="text-muted mb-0">{'No payment methods available'|translate}</p>
						{/if}
					</div>
					{/if}

					<div class="checkout-terms">
						<input class="form-check-input" type="checkbox" name="accept_terms" id="acceptTerms" value="1" required>
						<label class="form-check-label" for="acceptTerms">
							<a href="{$domain}mesafeli-satis" class="checkout-legal-link" data-bs-toggle="modal" data-bs-target="#checkoutLegalDistanceModal">{'Distance Selling Agreement'|translate}</a> {'and'|translate}
							<a href="{$domain}gizlilik" class="checkout-legal-link" data-bs-toggle="modal" data-bs-target="#checkoutLegalPrivacyModal">{'Privacy Policy'|translate}</a>. {'Accept terms'|translate}
						</label>
					</div>

					<button type="submit" class="checkout-submit">
						<span>{'Complete Checkout'|translate}</span>
						<strong id="checkoutSubmitTotal">{$checkoutTotals.total_formatted}</strong>
					</button>
					<p class="checkout-secure">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-shield-check-icon lucide-shield-check"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.67-.01C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.51 3.81 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
					{'Checkout secure note'|translate}</p>
				</div>
			</section>
		</form>
	</div>

	<div class="col-lg-5">
		<aside class="checkout-summary">
			<div class="checkout-summary__head">
				<h2>{'Order Summary'|translate}</h2>
				<span class="checkout-summary__count">{$cart.items|@count} {'products'|translate}</span>
			</div>
			<div class="checkout-summary__items">
				{foreach $cart.items as $item}
				<div class="checkout-summary__item">
					<div class="checkout-summary__thumb">
						<img src="{$item.image_url|escape}" alt="{$item.product_name|escape}" loading="lazy">
						<span class="checkout-summary__qty">{$item.qty}</span>
					</div>
					<div class="checkout-summary__info">
						<span class="checkout-summary__name">{$item.product_name|escape}</span>
						<span class="checkout-summary__price">{$item.line_total_formatted}</span>
					</div>
				</div>
				{/foreach}
			</div>

			<div class="checkout-summary__coupon">
				<label class="form-label">{'Coupon Code'|translate}</label>
				<div class="input-group">
					<input type="text" id="couponCodeInput" class="form-control text-uppercase" placeholder="{'Coupon Code'|translate}" value="{$checkoutTotals.coupon_code|escape}">
					<button type="button" class="btn btn-dark" id="applyCouponBtn">{'Apply'|translate}</button>
				</div>
				{if $checkoutTotals.has_coupon}
				<div class="checkout-summary__coupon-applied">
					<span class="text-success">{'Coupon applied'|translate}: {$checkoutTotals.coupon_code|escape}</span>
					<button type="button" class="btn btn-link btn-sm p-0 text-danger" id="removeCouponBtn">{'Delete'|translate}</button>
				</div>
				{/if}
			</div>

			<div class="checkout-summary__totals">
				<div class="checkout-summary__row">
					<span>{'Subtotal'|translate}</span>
					<span id="checkoutSubtotal">{$checkoutTotals.subtotal_formatted}</span>
				</div>
				{if $checkoutTotals.promotion_lines|@count}
				<div id="checkoutPromotionLines">
					{foreach $checkoutTotals.promotion_lines as $promoLine}
					<div class="checkout-summary__row checkout-summary__row--discount">
						<span>{$promoLine.name|escape}</span>
						<span>-{$promoLine.discount_formatted}</span>
					</div>
					{/foreach}
				</div>
				{elseif $checkoutTotals.has_promotion}
				<div id="checkoutPromotionLines">
				<div class="checkout-summary__row checkout-summary__row--discount" id="checkoutPromotionRow">
					<span>{$checkoutTotals.promotion_name|escape}</span>
					<span id="checkoutPromotion">-{$checkoutTotals.promotion_discount_formatted}</span>
				</div>
				</div>
				{else}
				<div id="checkoutPromotionLines"></div>
				<div class="checkout-summary__row checkout-summary__row--discount d-none" id="checkoutPromotionRow">
					<span id="checkoutPromotionName"></span>
					<span id="checkoutPromotion"></span>
				</div>
				{/if}
				{if $checkoutTotals.has_coupon}
				<div class="checkout-summary__row checkout-summary__row--discount" id="checkoutCouponDiscountRow">
					<span>{'Coupon'|translate}: {$checkoutTotals.coupon_code|escape}</span>
					<span id="checkoutCouponDiscount">-{$checkoutTotals.coupon_discount_formatted}</span>
				</div>
				{else}
				<div class="checkout-summary__row checkout-summary__row--discount d-none" id="checkoutCouponDiscountRow">
					<span id="checkoutCouponLabel"></span>
					<span id="checkoutCouponDiscount"></span>
				</div>
				{/if}
				{if $checkoutTotals.discount > 0}
				<div class="checkout-summary__row checkout-summary__row--discount" id="checkoutDiscountRow">
					<span>{'Discount'|translate}</span>
					<span id="checkoutDiscount">-{$checkoutTotals.discount_formatted}</span>
				</div>
				{else}
				<div class="checkout-summary__row checkout-summary__row--discount d-none" id="checkoutDiscountRow">
					<span>{'Discount'|translate}</span>
					<span id="checkoutDiscount"></span>
				</div>
				{/if}
				{if $checkoutTotals.has_payment_discount}
				<div class="checkout-summary__row checkout-summary__row--discount" id="checkoutPaymentDiscountRow">
					<span id="checkoutPaymentDiscountLabel">{$checkoutTotals.payment_discount_label|escape}</span>
					<span id="checkoutPaymentDiscount">-{$checkoutTotals.payment_discount_formatted}</span>
				</div>
				{else}
				<div class="checkout-summary__row checkout-summary__row--discount d-none" id="checkoutPaymentDiscountRow">
					<span id="checkoutPaymentDiscountLabel"></span>
					<span id="checkoutPaymentDiscount"></span>
				</div>
				{/if}
				{if $cartRequiresShipping}
				<div class="checkout-summary__row" id="checkoutShippingRow">
					<span>{'Cargo'|translate}</span>
					<span id="checkoutShipping">{if $checkoutTotals.shipping > 0}{$checkoutTotals.shipping_formatted}{else}{'Free'|translate}{/if}</span>
				</div>
				{/if}
				<div class="checkout-summary__row checkout-summary__row--total">
					<span>{'Total'|translate}</span>
					<span id="checkoutTotal">{$checkoutTotals.total_formatted}</span>
				</div>
			</div>
		</aside>
	</div>
</div>

<div class="modal fade checkout-legal-modal" id="checkoutLegalDistanceModal" tabindex="-1" aria-labelledby="checkoutLegalDistanceTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title h5 mb-0" id="checkoutLegalDistanceTitle">{if $legalDistance}{$legalDistance.title|escape}{else}{'Distance Selling Agreement'|translate}{/if}</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|translate}"></button>
			</div>
			<div class="modal-body cms-page-body">
				{if $legalDistance && $legalDistance.content}
					{$legalDistance.content nofilter}
				{else}
					{include file='./cms/mesafeli-satis.tpl'}
				{/if}
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-dark" data-bs-dismiss="modal">{'Close'|translate}</button>
			</div>
		</div>
	</div>
</div>

<div class="modal fade checkout-legal-modal" id="checkoutLegalPrivacyModal" tabindex="-1" aria-labelledby="checkoutLegalPrivacyTitle" aria-hidden="true">
	<div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
		<div class="modal-content">
			<div class="modal-header">
				<h2 class="modal-title h5 mb-0" id="checkoutLegalPrivacyTitle">{if $legalPrivacy}{$legalPrivacy.title|escape}{else}{'Privacy Policy'|translate}{/if}</h2>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Close'|translate}"></button>
			</div>
			<div class="modal-body cms-page-body">
				{if $legalPrivacy && $legalPrivacy.content}
					{$legalPrivacy.content nofilter}
				{else}
					{include file='./cms/gizlilik.tpl'}
				{/if}
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-dark" data-bs-dismiss="modal">{'Close'|translate}</button>
			</div>
		</div>
	</div>
</div>
</div>
