<header class="sm-header t4-header">
	{assign var=_bar1 value=$t4Chrome.header_bar_1|default:''}
	{assign var=_bar2 value=$t4Chrome.header_bar_2|default:''}
	{assign var=_bar3 value=$t4Chrome.header_bar_3|default:''}
	{if $_bar1 != '' || $_bar2 != '' || $_bar3 != ''}
	<div class="t4-topbar"{if $t4PreviewMode|default:false} data-t4-chrome="header"{/if} style="--t4-topbar-bg: {$t4Chrome.header_bar_bg|default:'#0f172a'|escape}; --t4-topbar-color: {$t4Chrome.header_bar_color|default:'#ffffff'|escape};">
		<div class="sm-container t4-topbar__inner">
			<div class="t4-topbar__left">
				{if $_bar1 != ''}<span>{$t4Chrome.header_bar_1|escape}</span>{/if}
				{if $_bar2 != ''}<span>{$t4Chrome.header_bar_2|escape}</span>{/if}
				{if $_bar3 != ''}<span class="d-none d-md-inline">{$t4Chrome.header_bar_3|escape}</span>{/if}
			</div>
			<a href="{$domain}truck" class="t4-topbar__track">
				<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.624l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>
				{'Order Traking'|translate}
			</a>
		</div>
	</div>
	{/if}
	<div class="sm-container t4-header__inner">
		{if $t4UseHeaderBuilder|default:false && $t4HeaderHtml|default:'' != ''}
			{$t4HeaderHtml nofilter}
		{else}
		{* Mobile *}
		<div class="sm-header-mobile t4-header-mobile">
			<div class="sm-header__row t4-header-mobile__row">
				<button type="button" class="sm-icon-btn t4-icon-btn" data-bs-toggle="offcanvas" data-bs-target="#primeMobileMenu" aria-label="{'Menu'|translate}">
					<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16"/></svg>
				</button>
				<a href="{$domain}" class="sm-logo t4-logo" title="{$siteName|escape}"{if $t4PreviewMode|default:false} data-t4-chrome="logo"{/if}>
					<img src="{if $t4Logo|default:'' != ''}{$t4Logo|escape}{else}{$siteLogos.header|escape}{/if}?v={$minute|default:1}" alt="{$siteName|escape}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
					<span class="t4-logo__text" style="display:none">{$siteName|escape}</span>
				</a>
				<div class="sm-header__actions t4-header-mobile__actions">
					{if $isLoggedIn}
					<div class="sm-header__action-slot">
						{include file='theme4/plugin/notifications-dropdown.tpl'}
					</div>
					{/if}
					<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="sm-icon-btn t4-icon-btn" title="{'My Account'|translate}" aria-label="{'My Account'|translate}">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</a>
					<a href="#" class="sm-icon-btn t4-icon-btn js-show-cart" title="{'Cart'|translate}" aria-label="{'Cart'|translate}">
						<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
						{if $cart.count|default:0 > 0}<span class="sm-icon-btn__badge sm-cart-count-badge">{$cart.count}</span>{else}<span class="sm-icon-btn__badge sm-cart-count-badge d-none">0</span>{/if}
					</a>
				</div>
			</div>
			<div class="sm-search-wrap">
				{include file='theme4/plugin/header-search.tpl' smSearchInputId='smSearchMobile'}
			</div>
		</div>

		{* Desktop *}
		<div class="sm-header-desktop t4-header-desktop">
			<div class="sm-header-desktop__top t4-header-top">
				<a href="{$domain}" class="sm-logo t4-logo" title="{$siteName|escape}"{if $t4PreviewMode|default:false} data-t4-chrome="logo"{/if}>
					<img src="{if $t4Logo|default:'' != ''}{$t4Logo|escape}{else}{$siteLogos.header|escape}{/if}?v={$minute|default:1}" alt="{$siteName|escape}" onerror="this.style.display='none';this.nextElementSibling.style.display='inline'">
					<span class="t4-logo__text" style="display:none">{$siteName|escape}</span>
				</a>

				<div class="sm-search-desktop t4-header-top__search">
					{include file='theme4/plugin/header-search.tpl' smSearchInputId='smSearchDesktop'}
				</div>

				<div class="t4-header-info"{if $t4PreviewMode|default:false} data-t4-chrome="header"{/if}>
					<div class="t4-header-info__item">
						<span class="t4-header-info__icon" aria-hidden="true">
							<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11v3a1 1 0 0 0 1 1h1a2 2 0 0 0 2-2v-1a2 2 0 0 0-2-2H4a1 1 0 0 0-1 1Z"/><path d="M21 11v3a1 1 0 0 1-1 1h-1a2 2 0 0 1-2-2v-1a2 2 0 0 1 2-2h1a1 1 0 0 1 1 1Z"/><path d="M4 14v1a6 6 0 0 0 6 6h0"/><path d="M20 14v1a6 6 0 0 1-6 6h0"/><path d="M12 5a3 3 0 0 0-3 3v2"/><path d="M12 5a3 3 0 0 1 3 3v2"/></svg>
						</span>
						<span class="t4-header-info__text">
							<span class="t4-header-info__label">{if $t4Chrome.header_support_label|default:'' != ''}{$t4Chrome.header_support_label|escape}{else}7/24 {'Live Support'|translate|default:'Canlı Destek'}{/if}</span>
							{if $t4Chrome.header_support_value|default:'' != ''}
							<a href="tel:{$t4Chrome.header_support_tel|default:$t4Chrome.header_support_value|escape}" class="t4-header-info__value">{$t4Chrome.header_support_value|escape}</a>
							{elseif $contactPhone}
							<a href="tel:{$contactPhoneTel|default:$contactPhone|escape}" class="t4-header-info__value">{$contactPhone|escape}</a>
							{else}
							<span class="t4-header-info__value">0 (850) 241 74 84</span>
							{/if}
						</span>
					</div>
					<div class="t4-header-info__item">
						<span class="t4-header-info__icon" aria-hidden="true">
							<svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m4.9 4.9 1.4 1.4"/><path d="m17.7 17.7 1.4 1.4"/></svg>
						</span>
						<span class="t4-header-info__text">
							<span class="t4-header-info__label">{if $t4Chrome.header_delivery_label|default:'' != ''}{$t4Chrome.header_delivery_label|escape}{else}{'Automatic Delivery'|translate|default:'Otomatik Teslimat'}{/if}</span>
							<span class="t4-header-info__value">{if $t4Chrome.header_delivery_value|default:'' != ''}{$t4Chrome.header_delivery_value|escape}{else}{'Instant Delivery'|translate|default:'Anında Teslim'}{/if}</span>
						</span>
					</div>
				</div>
			</div>

			<nav class="sm-header-menu t4-header-nav" aria-label="{'Menu'|translate}">
				<div class="t4-header-nav__left">
					<div class="dropdown t4-cats-dropdown">
						<button type="button" class="t4-cats-btn" id="t4CatsBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" aria-haspopup="true">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 5h16M4 12h16M4 19h16"/></svg>
							<span>{'Categories'|translate}</span>
						</button>
						<div class="dropdown-menu t4-mega-menu" aria-labelledby="t4CatsBtn">
							{foreach $menuCategories as $cat}
							<a href="{$domain}{$cat.category_link|escape}" class="t4-mega-menu__link{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">{$cat.category_name|escape}</a>
							{/foreach}
						</div>
					</div>

					{if $hooks.main_menu}
					<ul class="sm-header-menu__list sm-header-menu__list--hook t4-nav-list">
						{$hooks.main_menu nofilter}
					</ul>
					{else}
					<ul class="sm-header-menu__list t4-nav-list">
						<li>
							<a href="{$domain}" class="sm-header-menu__link t4-nav-link{if $pageName == 'home'} is-active{/if}">{'Home Page'|translate}</a>
						</li>
						<li>
							<a href="{$domain}special" class="sm-header-menu__link t4-nav-link{if $pageName == 'special'} is-active{/if}">{'All Products'|translate|default:'Tüm Ürünler'}</a>
						</li>
						<li>
							<a href="{$domain}contact" class="sm-header-menu__link t4-nav-link{if $pageName == 'contact'} is-active{/if}">{'Contact Us'|translate}</a>
						</li>
					</ul>
					{/if}
				</div>

				<div class="t4-header-tools">
					{if $isLoggedIn}
					<div class="sm-header__action-slot t4-header-tools__notify">
						{include file='theme4/plugin/notifications-dropdown.tpl'}
					</div>
					{/if}
					<a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="t4-tool-icon" title="{'My Account'|translate}" aria-label="{'My Account'|translate}">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					</a>
					<a href="{$domain}favorites" class="t4-tool-icon" title="{'Favorites'|translate}" aria-label="{'Favorites'|translate}">
						<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
						{if $favoriteCount|default:0 > 0}
						<span class="t4-tool-icon__badge">{$favoriteCount}</span>
						{else}
						<span class="t4-tool-icon__badge">0</span>
						{/if}
					</a>
					<a href="#" class="t4-cart-btn js-show-cart" title="{'Cart'|translate}" aria-label="{'Cart'|translate}">
						<span class="t4-cart-btn__icon">
							<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
							{if $cart.count|default:0 > 0}
							<span class="t4-cart-btn__badge sm-cart-count-badge">{$cart.count}</span>
							{else}
							<span class="t4-cart-btn__badge sm-cart-count-badge">0</span>
							{/if}
						</span>
						<span class="t4-cart-btn__total">{$cart.total_formatted|default:'0,00 ₺'}</span>
					</a>
				</div>
			</nav>
		</div>
		{/if}
	</div>
</header>
