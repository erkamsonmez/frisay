<div class="qty-tiers" id="qtyTiers" data-qty-tiers>
	<div class="qty-tiers__title">{'Buy more, pay less'|translate}</div>
	<div class="qty-tiers__list" role="list">
		{foreach $tiers as $tier}
		<button type="button" class="qty-tier{if $tier@first} is-active{/if}" role="listitem"
			data-tier-qty="{$tier.min_qty|escape}"
			data-tier-unit="{$tier.unit_price|escape}"
			data-tier-total="{$tier.total|escape}">
			{if $tier.discount_pct > 0}
			<span class="qty-tier__badge">%{$tier.discount_pct|escape} {'discount'|translate}</span>
			{/if}
			<span class="qty-tier__main">
				{$tier.min_qty|escape} x {$tier.unit_price_formatted|escape}
				{if $tier.min_qty > 1}
				<span class="qty-tier__sep">•</span>
				<span class="qty-tier__total">{$tier.total_formatted|escape}</span>
				{/if}
			</span>
		</button>
		{/foreach}
	</div>
	<script type="application/json" id="qtyTiersData">{$tiersJson nofilter}</script>
</div>
