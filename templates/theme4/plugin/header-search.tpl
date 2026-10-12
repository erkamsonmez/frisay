{assign var=smSearchInputId value=$smSearchInputId|default:'smSearchInput'}
<div class="sm-header-search t4-search" data-sm-search>
	<form class="sm-search t4-search__form" action="{$domain}search" method="get" role="search" autocomplete="off">
		<input
			type="search"
			name="q"
			id="{$smSearchInputId|escape}"
			class="t4-search__input"
			placeholder="{'Search product..'|translate}"
			value="{$searchQuery|default:''|escape}"
			autocomplete="off"
			aria-label="{'Search product..'|translate}"
			data-sm-search-input
		>
		<button type="submit" class="t4-search__btn" aria-label="{'Search'|translate}">
			<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
		</button>
	</form>
	<div class="sm-header-search__suggest" data-sm-search-results role="listbox" hidden></div>
</div>
