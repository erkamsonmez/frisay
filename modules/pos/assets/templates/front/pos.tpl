<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Kasa — {$posSiteName|escape}</title>
	<link rel="stylesheet" href="{$posCssUrl|escape}">
</head>
<body class="pos-body">
<div id="pos-app" class="pos-app"
	data-api="{$posApiBase|escape}"
	data-token="{$posToken|escape}"
	data-card-url="{$posCardUrl|escape}"
	data-has-card-gateway="{if $posHasCardGateway}1{else}0{/if}"
	data-fullscreen-auto="{if $posFullscreenAuto}1{else}0{/if}"
	data-hide-oos="{if $posHideOutOfStock}1{else}0{/if}"
	data-allow-oos-sale="{if $posAllowOutOfStockSale}1{else}0{/if}"
	data-store="{$posStoreLabel|escape}"
	data-customer="{$posCustomer.label|escape}">
<script type="application/json" id="pos-payment-adjustments">{$posPaymentAdjustmentsJson nofilter}</script>
<script type="application/json" id="pos-cargos-data">{$posCargosJson nofilter}</script>

	<header class="pos-hd">
		<button type="button" id="pos-menu-toggle" class="pos-hd-icon" aria-label="Menü" title="Menü">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
		</button>
		<div class="pos-hd__brand">
			{if $posLogoUrl|default:'' != ''}
			<img src="{$posLogoUrl|escape}" alt="{$posSiteName|escape}" class="pos-brand-logo">
			{else}
			<div class="pos-brand">{$posSiteName|escape}</div>
			{/if}
		</div>
		<div class="pos-hd__left pos-hd__desktop">
			<div id="pos-clock" class="pos-clock"></div>
			<div class="pos-user-pill">
				<span class="pos-user-pill__tag">Kasiyer</span>
				<span>{$posUserName|escape}</span>
			</div>
		</div>
		<div class="pos-hd__right pos-hd__desktop">
			<button type="button" id="pos-day-end" class="pos-hd-btn" title="Gün sonu">Gün Sonu</button>
			{if $posHasPin}
			<button type="button" id="pos-lock-screen" class="pos-hd-btn" title="Ekranı kilitle">Kilitle</button>
			{/if}
			<button type="button" id="pos-fullscreen" class="pos-hd-btn">Tam Ekran</button>
			{if $posIsAdmin}
			<a href="{$posExitUrl|escape}" class="pos-hd-btn pos-hd-btn--primary">Yönetim Paneli</a>
			{else}
			<form method="post" class="pos-inline-form">
				<input type="hidden" name="token" value="{$posToken|escape}">
				<button type="submit" name="posLogout" value="1" class="pos-hd-btn">Çıkış</button>
			</form>
			{/if}
		</div>
		<button type="button" id="pos-cart-toggle" class="pos-hd-icon pos-hd-icon--cart" aria-label="Sepet" title="Sepet">
			<svg width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/><path d="M6 6L5 3H2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/><circle cx="9" cy="20" r="1.5" fill="currentColor"/><circle cx="18" cy="20" r="1.5" fill="currentColor"/></svg>
			<span id="pos-cart-badge" class="pos-cart-badge" hidden>0</span>
		</button>
	</header>

	<div id="pos-drawer-backdrop" class="pos-drawer-backdrop" hidden></div>

	<div class="pos-workspace">
		<aside id="pos-cats" class="pos-cats">
			<div class="pos-drawer-head pos-drawer-head--mobile">
				<strong>Kategoriler</strong>
				<button type="button" class="pos-sheet__x" id="pos-cats-close" aria-label="Kapat">&times;</button>
			</div>
			{foreach $posCategories as $cat}
			<button type="button"
				class="pos-cat{if $cat.id_category == 0} is-active{/if}"
				data-category="{$cat.id_category}"
				style="--cat-color: {$cat.color|escape}">
				<span class="pos-cat__icon">{if $cat.id_category == 0}▦{else}{$cat.icon|escape}{/if}</span>
				<span class="pos-cat__name">{$cat.name|escape}</span>
			</button>
			{/foreach}
			<div class="pos-drawer-actions pos-drawer-head--mobile">
				<button type="button" id="pos-day-end-mobile" class="pos-hd-btn" style="width:100%;margin-bottom:8px;">Gün Sonu</button>
				{if $posHasPin}
				<button type="button" id="pos-lock-screen-mobile" class="pos-hd-btn" style="width:100%;margin-bottom:8px;">Kilitle</button>
				{/if}
				{if $posIsAdmin}
				<a href="{$posExitUrl|escape}" class="pos-hd-btn pos-hd-btn--primary" style="width:100%;justify-content:center;">Yönetim Paneli</a>
				{else}
				<form method="post" class="pos-inline-form">
					<input type="hidden" name="token" value="{$posToken|escape}">
					<button type="submit" name="posLogout" value="1" class="pos-hd-btn" style="width:100%;">Çıkış</button>
				</form>
				{/if}
			</div>
		</aside>

		<main class="pos-grid-wrap">
			<div class="pos-scan-bar">
				<div class="pos-qty-stepper" title="Adet">
					<button type="button" id="pos-qty-minus" class="pos-qty-stepper__btn" aria-label="Adet azalt">−</button>
					<input type="number" id="pos-qty" class="pos-qty-stepper__input" value="1" min="1" max="999" inputmode="numeric" aria-label="Adet">
					<button type="button" id="pos-qty-plus" class="pos-qty-stepper__btn" aria-label="Adet artır">+</button>
				</div>
				<input type="text" id="pos-barcode" class="pos-scan-bar__barcode" placeholder="Barkod okutun…" autocomplete="off" enterkeyhint="done" autofocus>
				<button type="button" id="pos-open-search" class="pos-scan-bar__search" title="Ürün ara">
					<svg width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/><path d="M20 20l-3-3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
					<span>Ara</span>
				</button>
			</div>
			<div id="pos-products-loading" class="pos-grid__loading">Yükleniyor…</div>
			<div id="pos-products-empty" class="pos-grid__empty" hidden>Ürün bulunamadı</div>
			<div id="pos-products" class="pos-grid"></div>
			<div class="pos-grid__pager">
				<button type="button" id="pos-prev" class="pos-mini-btn" disabled>‹</button>
				<span id="pos-page-info">1 / 1</span>
				<button type="button" id="pos-next" class="pos-mini-btn" disabled>›</button>
			</div>
		</main>

		<aside id="pos-cart" class="pos-cart">
			<div class="pos-drawer-head pos-drawer-head--mobile">
				<strong>Sepet</strong>
				<button type="button" class="pos-sheet__x" id="pos-cart-close" aria-label="Kapat">&times;</button>
			</div>

			<div class="pos-cart__head">
				<span>Sepet <strong id="pos-cart-lines">0</strong> kalem</span>
				<button type="button" id="pos-clear-cart" class="pos-link-danger">Sepeti Temizle</button>
			</div>

			<div id="pos-cart-empty" class="pos-cart__empty">Sepet boş</div>
			<div id="pos-cart-items" class="pos-cart__list"></div>

			<div class="pos-cart__summary">
				<div class="pos-cart__row"><span>Toplam ürün adedi</span><strong id="pos-item-qty">0</strong></div>
				<div class="pos-cart__row"><span>İndirim tutarı</span><strong>₺0,00</strong></div>
				<div class="pos-cart__payable">
					<span>Ödenecek tutar</span>
					<strong id="pos-cart-total">₺0,00</strong>
				</div>
			</div>

			<button type="button" id="pos-open-pay" class="pos-checkout" disabled>
				<svg width="20" height="20" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M2 10h20" stroke="currentColor" stroke-width="2"/></svg>
				Ödeme Al
			</button>
		</aside>
	</div>

	<div id="pos-toast" class="pos-toast" hidden></div>

	<!-- Ürün ara -->
	<div id="pos-search-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="search"></div>
		<div class="pos-sheet pos-sheet--md">
			<div class="pos-sheet__head">
				<h3>Ürün Ara</h3>
				<button type="button" class="pos-sheet__x" data-close="search">&times;</button>
			</div>
			<div class="pos-sheet__body">
				<input type="text" id="pos-search-input" class="pos-field" placeholder="Ürün adı, stok kodu…" autocomplete="off" enterkeyhint="search">
				<p class="pos-pay-note" style="margin-top:8px">Sonucu seçince sepete eklenir (adet: üstteki adet alanı).</p>
				<div id="pos-search-results" class="pos-search-results"></div>
			</div>
		</div>
	</div>

	<!-- Varyasyon -->
	<div id="pos-var-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="var"></div>
		<div class="pos-sheet pos-sheet--sm">
			<div class="pos-sheet__head">
				<h3 id="pos-var-title">Varyasyon seçin</h3>
				<button type="button" class="pos-sheet__x" data-close="var">&times;</button>
			</div>
			<div id="pos-var-body" class="pos-sheet__body"></div>
		</div>
	</div>

	<!-- Müşteri -->
	<div id="pos-customer-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="customer"></div>
		<div class="pos-sheet pos-sheet--md">
			<div class="pos-sheet__head">
				<h3>Müşteri Seç</h3>
				<button type="button" class="pos-sheet__x" data-close="customer">&times;</button>
			</div>
			<div class="pos-sheet__body">
				<input type="text" id="pos-customer-search" class="pos-field" placeholder="Ad, telefon veya e-posta ile ara…" autocomplete="off">
				<button type="button" id="pos-customer-visitor" class="pos-visitor-btn">Ziyaretçi (varsayılan)</button>
				<div id="pos-customer-results" class="pos-customer-list"></div>

				<div class="pos-customer-create">
					<button type="button" id="pos-customer-create-toggle" class="pos-outline-btn pos-outline-btn--sm">+ Yeni müşteri ekle</button>
					<div id="pos-customer-create-form" class="pos-customer-create__form" hidden>
						<input type="text" id="pos-customer-create-name" class="pos-field" placeholder="Ad soyad" autocomplete="off">
						<input type="text" id="pos-customer-create-phone" class="pos-field" placeholder="+90 / +1 / +44 …" autocomplete="off">
						<input type="email" id="pos-customer-create-email" class="pos-field" placeholder="E-posta (isteğe bağlı)" autocomplete="off">
						<input type="text" id="pos-customer-create-city" class="pos-field" placeholder="İl (kargo için)" autocomplete="off">
						<input type="text" id="pos-customer-create-district" class="pos-field" placeholder="İlçe (kargo için)" autocomplete="off">
						<textarea id="pos-customer-create-address" class="pos-field" rows="2" placeholder="Adres (kargo için, isteğe bağlı)"></textarea>
						<button type="button" id="pos-customer-create-save" class="pos-checkout pos-checkout--inline">Kaydet ve seç</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Ödeme -->
	<div id="pos-pay-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="pay"></div>
		<div class="pos-sheet pos-sheet--pay">
			<div class="pos-sheet__head pos-sheet__head--pay">
				<div>
					<span class="pos-step" id="pos-pay-step-label">Adım 1 / 2</span>
					<h3 id="pos-pay-title">Satış tipi</h3>
				</div>
				<button type="button" class="pos-sheet__x" data-close="pay">&times;</button>
			</div>
			<div class="pos-sheet__body pos-pay-body">
				<div id="pos-pay-step-1" class="pos-pay-step">
					<p class="pos-pay-label">Nasıl satıyorsunuz?</p>
					<div class="pos-sale-types pos-sale-types--hero">
						<button type="button" class="pos-sale-type" data-sale-type="store" id="pos-pick-store">
							<span class="pos-sale-type__icon" aria-hidden="true">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M4 10l2-5h12l2 5M4 10h16v9a1 1 0 01-1 1H5a1 1 0 01-1-1v-9z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 20v-5h6v5" stroke="currentColor" stroke-width="1.8"/></svg>
							</span>
							<span class="pos-sale-type__title">Mağaza satışı</span>
							<span class="pos-sale-type__sub">Müşteri mağazada teslim alır</span>
						</button>
						<button type="button" class="pos-sale-type" data-sale-type="shipping" id="pos-pick-shipping">
							<span class="pos-sale-type__icon" aria-hidden="true">
								<svg width="28" height="28" viewBox="0 0 24 24" fill="none"><path d="M3 7h11v10H3V7zM14 10h4l3 3v4h-7v-7z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><circle cx="7" cy="18.5" r="1.5" fill="currentColor"/><circle cx="17" cy="18.5" r="1.5" fill="currentColor"/></svg>
							</span>
							<span class="pos-sale-type__title">Kargo ile gönder</span>
							<span class="pos-sale-type__sub">Adres ve kargo seçilir</span>
						</button>
					</div>
				</div>

				<div id="pos-pay-step-ship" class="pos-pay-step" hidden>
					<div class="pos-customer-bar">
						<span>Müşteri: <strong id="pos-ship-customer">Ziyaretçi</strong></span>
						<button type="button" id="pos-change-customer-ship" class="pos-link">Seç / Ekle</button>
					</div>
					<p class="pos-pay-note" style="margin-top:0">Kargo satışı için müşteri, telefon ve teslimat adresi gerekli.</p>

					<div id="pos-shipping-section" class="pos-shipping">
						<label class="pos-field-label" for="pos-cargo">Kargo firması</label>
						<select id="pos-cargo" class="pos-field">
							<option value="0">Kargo seçin</option>
							{foreach $posCargos as $cargo}
							<option value="{$cargo.id_cargo}">{$cargo.name|escape}</option>
							{/foreach}
						</select>
						<label class="pos-field-label" for="pos-ship-address-select">Kayıtlı adres</label>
						<select id="pos-ship-address-select" class="pos-field">
							<option value="0">Yeni adres gir</option>
						</select>
						<label class="pos-field-label" for="pos-ship-city">İl</label>
						<input type="text" id="pos-ship-city" class="pos-field" placeholder="İl" autocomplete="address-level1">
						<label class="pos-field-label" for="pos-ship-district">İlçe</label>
						<input type="text" id="pos-ship-district" class="pos-field" placeholder="İlçe" autocomplete="address-level2">
						<label class="pos-field-label" for="pos-ship-address">Adres</label>
						<textarea id="pos-ship-address" class="pos-field" rows="2" placeholder="Açık adres"></textarea>
						<label class="pos-field-label" for="pos-ship-phone">Telefon</label>
						<input type="text" id="pos-ship-phone" class="pos-field" placeholder="05xx…" autocomplete="tel">
					</div>

					<div class="pos-pay-footer">
						<button type="button" id="pos-ship-back" class="pos-outline-btn">Geri</button>
						<button type="button" id="pos-ship-next" class="pos-checkout pos-checkout--inline">Ödemeye geç</button>
					</div>
				</div>

				<div id="pos-pay-step-pay" class="pos-pay-step" hidden>
					<div class="pos-customer-bar">
						<span>Müşteri: <strong id="pos-pay-customer">Ziyaretçi</strong></span>
						<button type="button" id="pos-change-customer" class="pos-link">Değiştir</button>
					</div>

					<p class="pos-pay-label">Ödeme yöntemi</p>
					<div class="pos-pay-methods">
						<button type="button" class="pos-pay-method is-active" data-method="pos_cash">
							<span class="pos-pay-method__icon" aria-hidden="true">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="2" y="6" width="20" height="12" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M12 15a2 2 0 100-4 2 2 0 000 4z" stroke="currentColor" stroke-width="1.8"/></svg>
							</span>
							<span>Nakit</span>
						</button>
						<button type="button" class="pos-pay-method" data-method="pos_card">
							<span class="pos-pay-method__icon" aria-hidden="true">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><rect x="2" y="5" width="20" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M2 10h20" stroke="currentColor" stroke-width="1.8"/></svg>
							</span>
							<span>POS (Kart)</span>
						</button>
						<button type="button" class="pos-pay-method" data-method="pos_transfer">
							<span class="pos-pay-method__icon" aria-hidden="true">
								<svg width="22" height="22" viewBox="0 0 24 24" fill="none"><path d="M3 10h18M5 10V8l7-4 7 4v2M6 10v8h3v-5h6v5h3v-8" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>
							</span>
							<span>Havale</span>
						</button>
					</div>

					<div class="pos-pay-total-box">
						<span>Ara toplam</span>
						<strong id="pos-pay-subtotal">₺0,00</strong>
					</div>
					<div id="pos-pay-shipping-row" class="pos-pay-adjustment" hidden>
						<span>Kargo</span>
						<strong id="pos-pay-shipping-amount">₺0,00</strong>
					</div>
					<div id="pos-pay-adjustment-row" class="pos-pay-adjustment" hidden>
						<span id="pos-pay-adjustment-label">İndirim</span>
						<strong id="pos-pay-adjustment-amount">₺0,00</strong>
					</div>
					<div class="pos-pay-total-box pos-pay-total-box--final">
						<span>Ödenecek tutar</span>
						<strong id="pos-pay-total">₺0,00</strong>
					</div>

					<div id="pos-cash-section">
						<div class="pos-quick-grid">
							<button type="button" class="pos-quick" data-amount="50">₺50</button>
							<button type="button" class="pos-quick" data-amount="100">₺100</button>
							<button type="button" class="pos-quick" data-amount="200">₺200</button>
							<button type="button" class="pos-quick" data-amount="500">₺500</button>
							<button type="button" class="pos-quick" data-amount="1000">₺1000</button>
							<button type="button" class="pos-quick" id="pos-exact-amount">Tam tutar</button>
						</div>

						<label class="pos-field-label" for="pos-cash-input">ALINAN NAKİT</label>
						<div class="pos-cash-field">
							<input type="text" id="pos-cash-input" class="pos-field pos-field--amount" inputmode="decimal" autocomplete="off">
							<span class="pos-cash-field__cur">₺</span>
						</div>

						<div id="pos-change-row" class="pos-change-row">
							<span>Para üstü</span>
							<strong id="pos-change-amount">₺0,00</strong>
						</div>
					</div>

					<div id="pos-card-section" hidden>
						<p class="pos-pay-note">Fiziksel POS cihazından tahsilatı alın. Onay sonrası aşağıdaki butonla siparişi tamamlayın.</p>
					</div>

					<div id="pos-transfer-section" hidden>
						<p class="pos-pay-note">Havale bekleniyor olarak sipariş oluşturulur. Tanımlı havale indirimi / komisyonu tutara yansır.</p>
					</div>

					<div class="pos-pay-footer">
						<button type="button" id="pos-pay-back" class="pos-outline-btn">Geri</button>
						<button type="button" id="pos-pay-reset" class="pos-outline-btn">Sıfırla</button>
						<button type="button" id="pos-complete-sale" class="pos-checkout pos-checkout--inline">Siparişi Tamamla</button>
					</div>
				</div>
			</div>
		</div>
	</div>

	<!-- Gün sonu — yönetici şifresi -->
	<div id="pos-dayend-pin-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="dayend-pin"></div>
		<div class="pos-sheet pos-sheet--sm">
			<div class="pos-sheet__head">
				<h3>Gün Sonu</h3>
				<button type="button" class="pos-sheet__x" data-close="dayend-pin">&times;</button>
			</div>
			<div class="pos-sheet__body">
				<p class="pos-pay-note">Yönetici şifresini girin (6 hane).</p>
				<form id="pos-dayend-pin-form" class="pos-pin-form" autocomplete="off" novalidate onsubmit="return false;">
					<input type="text" name="username" value="" autocomplete="username" tabindex="-1" aria-hidden="true" class="pos-autofill-decoy">
					<input type="password" name="password" value="" autocomplete="current-password" tabindex="-1" aria-hidden="true" class="pos-autofill-decoy">
					<div id="pos-dayend-pin-boxes" class="pos-pin-boxes" role="group" aria-label="Yönetici şifresi">
						<input type="text" class="pos-pin-box" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" name="pos_mgr_d0" data-pin-idx="0" aria-label="1. hane" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
						<input type="text" class="pos-pin-box" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" name="pos_mgr_d1" data-pin-idx="1" aria-label="2. hane" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
						<input type="text" class="pos-pin-box" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" name="pos_mgr_d2" data-pin-idx="2" aria-label="3. hane" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
						<input type="text" class="pos-pin-box" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" name="pos_mgr_d3" data-pin-idx="3" aria-label="4. hane" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
						<input type="text" class="pos-pin-box" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" name="pos_mgr_d4" data-pin-idx="4" aria-label="5. hane" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
						<input type="text" class="pos-pin-box" inputmode="numeric" maxlength="1" pattern="[0-9]*" autocomplete="one-time-code" name="pos_mgr_d5" data-pin-idx="5" aria-label="6. hane" data-lpignore="true" data-1p-ignore="true" data-form-type="other">
					</div>
					<button type="button" id="pos-dayend-pin-submit" class="pos-checkout pos-checkout--inline" style="margin-top:14px;width:100%;">Raporu Aç</button>
				</form>
			</div>
		</div>
	</div>

	<!-- Gün sonu raporu -->
	<div id="pos-dayend-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="dayend"></div>
		<div class="pos-sheet pos-sheet--md">
			<div class="pos-sheet__head">
				<h3>Gün Sonu Raporu</h3>
				<button type="button" class="pos-sheet__x" data-close="dayend">&times;</button>
			</div>
			<div class="pos-sheet__body">
				<div id="pos-dayend-content" class="pos-dayend"></div>
				<div class="pos-pay-footer" style="margin-top:16px;">
					<button type="button" id="pos-dayend-print" class="pos-outline-btn">Yazdır</button>
					<button type="button" id="pos-dayend-print-detail" class="pos-outline-btn">Detaylı Yazdır</button>
					<button type="button" id="pos-dayend-close-register" class="pos-checkout pos-checkout--inline">Kasayı Kapat</button>
				</div>
			</div>
		</div>
	</div>

	<!-- Fiş -->
	<div id="pos-receipt-modal" class="pos-overlay" hidden>
		<div class="pos-overlay__bg" data-close="receipt"></div>
		<div class="pos-sheet pos-sheet--receipt">
			<div class="pos-sheet__head">
				<h3>Satış Fişi</h3>
				<button type="button" class="pos-sheet__x" data-close="receipt">&times;</button>
			</div>
			<div class="pos-sheet__body pos-receipt-wrap">
				<div id="pos-receipt-content" class="pos-receipt"></div>
				<div class="pos-receipt-actions">
					<button type="button" id="pos-receipt-print" class="pos-checkout pos-checkout--inline">Yazdır</button>
					<button type="button" class="pos-outline-btn" data-close="receipt">Kapat</button>
				</div>
			</div>
		</div>
	</div>
</div>

<div id="pos-print-area" class="pos-print-area" aria-hidden="true"></div>

<script src="{$posJsUrl|escape}"></script>
</body>
</html>
