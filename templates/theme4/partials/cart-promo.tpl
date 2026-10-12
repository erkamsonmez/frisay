{if $cartPromoOffers|default:[]|@count > 0}
<div class="qty-tiers" data-qty-tiers>
	<div class="qty-tiers__title">{'Buy more, pay less'|translate|default:'Çok Al Az Öde'}</div>
	<div class="qty-tiers__list" role="list">
		{foreach $cartPromoOffers as $tier}
		{if $tier.min_qty > 1}
		<button type="button" class="qty-tier" role="listitem"
			data-tier-qty="{$tier.min_qty|escape}"
			data-tier-unit="{$tier.unit_price|escape}"
			data-tier-total="{$tier.total|escape}">
			{if $tier.badge|default:'' != ''}
			<span class="qty-tier__badge">{$tier.badge|escape}</span>
			{/if}
			<span class="qty-tier__main">
				{$tier.min_qty|escape} x {$tier.unit_price_formatted|escape}
				<span class="qty-tier__sep">•</span>
				<span class="qty-tier__total">{$tier.total_formatted|escape}</span>
			</span>
		</button>
		{/if}
		{/foreach}
	</div>
</div>
{/if}
