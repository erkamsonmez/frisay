{* Service / trust bar *}
<section class="t4-service-bar"{if $t4PreviewMode|default:false} data-t4-chrome="trust"{/if} aria-label="{'Services'|translate}">
	<div class="sm-container">
		<div class="row t4-service-bar__row">
			<div class="col-12 col-md-4">
				<div class="t4-service-bar__item">
					<span class="t4-service-bar__icon" aria-hidden="true">
						<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="14" x="2" y="5" rx="2"/><path d="M2 10h20"/><path d="M6 15h2"/><path d="M18 3v2"/><path d="M18 19v2"/><path d="m16 2 2 2 2-2"/><path d="m16 22 2-2 2 2"/></svg>
					</span>
					<span class="t4-service-bar__text">
						<strong>{if $t4Chrome.trust1_title|default:'' != ''}{$t4Chrome.trust1_title|escape}{else}%100 {'Secure Payment'|translate|default:'GÜVENLİ ÖDEME'}{/if}</strong>
						<span>{if $t4Chrome.trust1_text|default:'' != ''}{$t4Chrome.trust1_text|escape}{else}3D Secure {'with Secure Payment'|translate|default:'ile Güvenli Ödeme'}{/if}</span>
					</span>
				</div>
			</div>
			<div class="col-12 col-md-4">
				<div class="t4-service-bar__item">
					<span class="t4-service-bar__icon" aria-hidden="true">
						<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v3a1 1 0 0 0 1 1h1a2 2 0 0 0 2-2v-1a2 2 0 0 0-2-2H4a1 1 0 0 0-1 1Z"/><path d="M21 11v3a1 1 0 0 1-1 1h-1a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h1a1 1 0 0 1 1 1Z"/><path d="M4 14v1a6 6 0 0 0 6 6h0"/><path d="M20 14v1a6 6 0 0 1-6 6h0"/><path d="M12 5a3 3 0 0 0-3 3v2"/><path d="M12 5a3 3 0 0 1 3 3v2"/></svg>
					</span>
					<span class="t4-service-bar__text">
						<strong>{if $t4Chrome.trust2_title|default:'' != ''}{$t4Chrome.trust2_title|escape}{else}{'Unlimited Support'|translate|default:'SINIRSIZ DESTEK'}{/if}</strong>
						<span>{if $t4Chrome.trust2_text|default:'' != ''}{$t4Chrome.trust2_text|escape}{else}{'Always with you'|translate|default:'Her Zaman Yanınızdayız'}{/if}</span>
					</span>
				</div>
			</div>
			<div class="col-12 col-md-4">
				<div class="t4-service-bar__item">
					<span class="t4-service-bar__icon" aria-hidden="true">
						<svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="13" r="8"/><path d="M12 9v4l2 2"/><path d="M5 3 2 6"/><path d="m22 6-3-3"/><path d="M12 5V2"/></svg>
					</span>
					<span class="t4-service-bar__text">
						<strong>{if $t4Chrome.trust3_title|default:'' != ''}{$t4Chrome.trust3_title|escape}{else}{'Fast Delivery'|translate|default:'HIZLI TESLİMAT'}{/if}</strong>
						<span>
							{if $t4Chrome.trust3_text|default:'' != ''}{$t4Chrome.trust3_text|escape}{else}{'Delivered within 15 minutes'|translate|default:'15 Dakika İçinde Teslim'}{/if}
							<svg class="t4-service-bar__info" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
						</span>
					</span>
				</div>
			</div>
		</div>
	</div>
</section>

<footer class="t4-footer" role="contentinfo">
	<div class="sm-container">
		{if $t4UseFooterBuilder|default:false && $t4FooterHtml|default:'' != ''}
			{$t4FooterHtml nofilter}
		{else}
		<div class="row t4-footer__grid g-4"{if $t4PreviewMode|default:false} data-t4-chrome="footer"{/if}>
			<div class="col-12 col-md-6 col-xl">
				<div class="t4-footer-brand">
					<a href="{$domain}" class="t4-footer-brand__logo" title="{$siteName|escape}">
						<img src="{if $t4FooterLogo|default:'' != ''}{$t4FooterLogo|escape}{elseif $t4Logo|default:'' != ''}{$t4Logo|escape}{else}{$siteLogos.footer|escape}{/if}?v={$minute|default:1}" alt="{$siteName|escape}" height="48" loading="lazy">
					</a>
					<p class="t4-footer-brand__text">
						{if $t4Chrome.footer_brand_text|default:'' != ''}
						{$t4Chrome.footer_brand_text|escape}
						{elseif $t4FooterText|default:'' != ''}
						{$t4FooterText|escape}
						{elseif $footerDescription|default:'' != ''}
						{$footerDescription|escape|truncate:120}
						{else}
						{'Footer description'|translate}
						{/if}
					</p>
				</div>
			</div>

			<div class="col-6 col-md-3 col-xl">
				<h3 class="t4-footer__title">{if $t4Chrome.footer_menu_title|default:'' != ''}{$t4Chrome.footer_menu_title|escape}{else}{'Menu'|translate|default:'Menü'}{/if}</h3>
				<ul class="t4-footer__list">
					{if $hooks.footer_menu}
					{$hooks.footer_menu nofilter}
					{else}
					<li><a href="{$domain}">{'Home Page'|translate}</a></li>
					<li><a href="{$domain}special">{'All Products'|translate|default:'Tüm Ürünler'}</a></li>
					{/if}
				</ul>
			</div>

			<div class="col-6 col-md-3 col-xl">
				<h3 class="t4-footer__title">{if $t4Chrome.footer_featured_title|default:'' != ''}{$t4Chrome.footer_featured_title|escape}{else}{'Featured'|translate|default:'Öne Çıkanlar'}{/if}</h3>
				<ul class="t4-footer__list">
					{foreach $menuCategories as $cat name=footerFeatured}
					{if $smarty.foreach.footerFeatured.iteration > 5}{break}{/if}
					<li><a href="{$domain}{$cat.category_link|escape}">{$cat.category_name|escape}</a></li>
					{/foreach}
				</ul>
			</div>

			<div class="col-6 col-md-6 col-xl">
				<h3 class="t4-footer__title">{if $t4Chrome.footer_corporate_title|default:'' != ''}{$t4Chrome.footer_corporate_title|escape}{else}{'Corporate'|translate}{/if}</h3>
				<ul class="t4-footer__list">
					{assign var=_hasContactCms value=false}
					{foreach $cmsFooterLinks as $cmsLink}
					{if $cmsLink.slug|default:'' == 'iletisim' || $cmsLink.slug|default:'' == 'contact'}
						{assign var=_hasContactCms value=true}
					{/if}
					<li><a href="{$cmsLink.url|escape}">{$cmsLink.title|escape}</a></li>
					{/foreach}
					{if !$_hasContactCms}
					<li><a href="{$domain}contact">{'Contact Us'|translate}</a></li>
					{/if}
				</ul>
			</div>

			<div class="col-6 col-md-6 col-xl">
				<h3 class="t4-footer__title">{if $t4Chrome.footer_service_title|default:'' != ''}{$t4Chrome.footer_service_title|escape}{else}{'Customer Service'|translate|default:'Müşteri Hizmetleri'}{/if}</h3>
				<p class="t4-footer__service-text">
					{if $t4Chrome.footer_service_text|default:'' != ''}{$t4Chrome.footer_service_text|escape}{else}{'Customer service hours'|translate|default:'Satın alma, destek ve diğer tüm konularda 08:00 - 23:00 saatleri arasında müşteri hizmetleri birimimize ulaşabilirsiniz.'}{/if}
				</p>
				<div class="t4-footer__contact">
					{if $contactPhone}
					<a href="tel:{$contactPhoneTel|default:$contactPhone|escape}">{$contactPhone|escape}</a>
					{/if}
					{if $contactEmail}
					<a href="mailto:{$contactEmail|escape}">{$contactEmail|escape}</a>
					{/if}
				</div>
				<div class="t4-footer__payments" aria-label="{'Payment logos'|translate}">
					<span class="t4-pay-badge" title="Mastercard" aria-label="Mastercard">
						<svg width="36" height="22" viewBox="0 0 36 22" aria-hidden="true"><circle cx="13" cy="11" r="8" fill="#EB001B"/><circle cx="23" cy="11" r="8" fill="#F79E1B"/><path d="M18 5.2a8 8 0 0 1 0 11.6 8 8 0 0 1 0-11.6z" fill="#FF5F00"/></svg>
					</span>
					<span class="t4-pay-badge" title="VISA" aria-label="VISA">
						<svg width="40" height="14" viewBox="0 0 40 14" aria-hidden="true"><text x="0" y="12" font-family="Arial,sans-serif" font-size="13" font-weight="700" fill="#1A1F71">VISA</text></svg>
					</span>
					<span class="t4-pay-badge" title="American Express" aria-label="American Express">
						<svg width="28" height="22" viewBox="0 0 28 22" aria-hidden="true"><rect width="28" height="22" rx="3" fill="#2E77BC"/><text x="4" y="15" font-family="Arial,sans-serif" font-size="8" font-weight="700" fill="#fff">AMEX</text></svg>
					</span>
					<span class="t4-pay-badge" title="Troy" aria-label="Troy">
						<svg width="36" height="16" viewBox="0 0 36 16" aria-hidden="true"><text x="0" y="13" font-family="Arial,sans-serif" font-size="12" font-weight="700" fill="#00A0E3">TROY</text></svg>
					</span>
				</div>
			</div>
		</div>
		{/if}

		<div class="t4-footer__bottom">
			<span>{if $t4Copyright|default:'' != ''}{$t4Copyright|escape}{else}&copy; {$year} {$siteName|escape}. {'All rights reserved.'|translate}{/if}</span>
		</div>
	</div>
</footer>

<script>
(function () {
	var btn = document.getElementById('t4FooterIntroToggle');
	var box = document.getElementById('t4FooterIntro');
	if (!btn || !box) return;
	btn.addEventListener('click', function () {
		var open = box.classList.toggle('is-open');
		btn.setAttribute('aria-expanded', open ? 'true' : 'false');
		btn.classList.toggle('is-open', open);
	});
})();
</script>
