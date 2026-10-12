{assign var=_custName value=$customer.user_full_name|default:''}
{assign var=_custEmail value=$customer.email|default:''}
{assign var=_initials value='?'}
{if $_custName != ''}
	{assign var=_initials value=$_custName|upper|truncate:1:""}
{/if}

<div class="t4-drawer">
	<div class="t4-drawer__scroll">
		<div class="t4-drawer__card t4-drawer__profile">
			{if $isLoggedIn}
			<span class="t4-drawer__avatar" aria-hidden="true">{$_initials|escape}</span>
			<div class="t4-drawer__profile-body">
				<strong class="t4-drawer__profile-name">{$_custName|escape}</strong>
				{if $_custEmail != ''}<span class="t4-drawer__profile-meta">{$_custEmail|escape}</span>{/if}
				<a href="{$domain}my-account" class="t4-drawer__profile-edit">
					<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
					{'Edit Profile'|translate}
				</a>
			</div>
			{else}
			<span class="t4-drawer__avatar" aria-hidden="true">?</span>
			<div class="t4-drawer__profile-body">
				<strong class="t4-drawer__profile-name">{'Guest'|translate}</strong>
				<span class="t4-drawer__profile-meta">{'Sign in to continue'|translate}</span>
			</div>
			{/if}
		</div>

		<p class="t4-drawer__section-label">{'My Account'|translate}</p>
		<div class="t4-drawer__card">
			{if $isLoggedIn}
			<a href="{$domain}orders" class="t4-drawer__row">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
				<span class="t4-drawer__row-label">{'Orders'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			<a href="{$domain}favorites" class="t4-drawer__row">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg></span>
				<span class="t4-drawer__row-label">{'Favorites'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			<a href="{$domain}logout" class="t4-drawer__row">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="m16 17 5-5-5-5"/><path d="M21 12H9"/></svg></span>
				<span class="t4-drawer__row-label">{'Logout'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{else}
			<a href="{$domain}login" class="t4-drawer__row">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"/><path d="m10 17 5-5-5-5"/><path d="M15 12H3"/></svg></span>
				<span class="t4-drawer__row-label">{'Login'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			<a href="{$domain}register" class="t4-drawer__row">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M19 8v6"/><path d="M22 11h-6"/></svg></span>
				<span class="t4-drawer__row-label">{'Register'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{/if}
		</div>

		{* Categories *}
		<p class="t4-drawer__section-label">{'Categories'|translate}</p>
		<div class="t4-drawer__card">
			{if $menuCategories|default:[]|@count > 0}
			{foreach $menuCategories as $cat}
			{if $cat.subcategories|default:[]|@count > 0}
			<div class="t4-drawer__group" data-t4-drawer-group>
				<div class="t4-drawer__row t4-drawer__row--split">
					<a href="{$domain}{$cat.category_link|escape}" class="t4-drawer__row-main{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">
						<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></span>
						<span class="t4-drawer__row-label">{$cat.category_name|escape}</span>
					</a>
					<button type="button" class="t4-drawer__expand" data-t4-drawer-toggle aria-expanded="false" aria-label="{$cat.category_name|escape}">
						<span class="t4-drawer__chev" aria-hidden="true"></span>
					</button>
				</div>
				<div class="t4-drawer__children" hidden>
					{foreach $cat.subcategories as $sub}
					<a href="{$domain}{$sub.category_link|escape}" class="t4-drawer__row t4-drawer__row--child">
						<span class="t4-drawer__row-label">{$sub.category_name|escape}</span>
						<span class="t4-drawer__chev" aria-hidden="true"></span>
					</a>
					{/foreach}
				</div>
			</div>
			{else}
			<a href="{$domain}{$cat.category_link|escape}" class="t4-drawer__row{if isset($category) && $category.category_link == $cat.category_link} is-active{/if}">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/></svg></span>
				<span class="t4-drawer__row-label">{$cat.category_name|escape}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{/if}
			{/foreach}
			{else}
			<a href="{$domain}special" class="t4-drawer__row">
				<span class="t4-drawer__row-label">{'All Products'|translate|default:'Tüm Ürünler'}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{/if}
		</div>

		{* Pages *}
		<p class="t4-drawer__section-label">{'Pages'|translate}</p>
		<div class="t4-drawer__card">
			<a href="{$domain}" class="t4-drawer__row{if $pageName == 'home'} is-active{/if}">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg></span>
				<span class="t4-drawer__row-label">{'Home Page'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			<a href="{$domain}special" class="t4-drawer__row{if $pageName == 'special'} is-active{/if}">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12.586 2.586A2 2 0 0 0 11.172 2H4a2 2 0 0 0-2 2v7.172a2 2 0 0 0 .586 1.414l8.704 8.704a2.426 2.426 0 0 0 3.42 0l6.58-6.58a2.426 2.426 0 0 0 0-3.42z"/><circle cx="7.5" cy="7.5" r=".5" fill="currentColor"/></svg></span>
				<span class="t4-drawer__row-label">{'Specilas'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{foreach $cmsFooterLinks as $cmsLink}
			<a href="{$cmsLink.url|escape}" class="t4-drawer__row">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/></svg></span>
				<span class="t4-drawer__row-label">{$cmsLink.title|escape}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{/foreach}
			<a href="{$domain}contact" class="t4-drawer__row{if $pageName == 'contact'} is-active{/if}">
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/></svg></span>
				<span class="t4-drawer__row-label">{'Contact Us'|translate}</span>
				<span class="t4-drawer__chev" aria-hidden="true"></span>
			</a>
			{if $hooks.mobile_menu}
			<div class="t4-drawer__hook">{$hooks.mobile_menu nofilter}</div>
			{/if}
		</div>

		{* Languages — same card design *}
		{if $langSwitcher|default:[]|@count > 1}
		<p class="t4-drawer__section-label">{'Language'|translate}</p>
		<div class="t4-drawer__card">
			{foreach $langSwitcher as $langItem}
			<a href="{$langItem.url|escape}" class="t4-drawer__row{if $langItem.active} is-active{/if}" hreflang="{$langItem.code|escape}"{if $langItem.active} aria-current="true"{/if}>
				<span class="t4-drawer__row-icon" aria-hidden="true"><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><path d="M2 12h20"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg></span>
				<span class="t4-drawer__row-label">{$langItem.label|escape}</span>
				{if $langItem.active}
				<span class="t4-drawer__row-value">{'Active'|translate}</span>
				{else}
				<span class="t4-drawer__chev" aria-hidden="true"></span>
				{/if}
			</a>
			{/foreach}
		</div>
		{/if}
	</div>
</div>

<script>
(function () {
	document.querySelectorAll('[data-t4-drawer-toggle]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var group = btn.closest('[data-t4-drawer-group]');
			if (!group) return;
			var panel = group.querySelector('.t4-drawer__children');
			var open = btn.getAttribute('aria-expanded') === 'true';
			btn.setAttribute('aria-expanded', open ? 'false' : 'true');
			group.classList.toggle('is-open', !open);
			if (panel) panel.hidden = open;
		});
	});
})();
</script>
