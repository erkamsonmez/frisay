<div class="psib-options" role="group" aria-label="{'Options'|translate}">
	<div class="psib-options__label">{'Options'|translate}:</div>
	<div class="psib-options__list">
		{foreach $siblings as $sib}
		{if $sib.current}
		<span class="psib-option is-active" aria-current="true">
			<span class="psib-option__media">
				<img src="{$sib.image_url|escape}" alt="{$sib.label|escape}" loading="lazy" width="48" height="48">
			</span>
			<span class="psib-option__body">
				<strong title="{$sib.label|escape}">{$sib.label|truncate:28:'…':true|escape}</strong>
				<small>{$sib.price_formatted|escape}</small>
			</span>
		</span>
		{else}
		<a class="psib-option" href="{$sib.url|escape}" title="{$sib.product_name|escape}">
			<span class="psib-option__media">
				<img src="{$sib.image_url|escape}" alt="{$sib.label|escape}" loading="lazy" width="48" height="48">
			</span>
			<span class="psib-option__body">
				<strong title="{$sib.label|escape}">{$sib.label|truncate:28:'…':true|escape}</strong>
				<small>{$sib.price_formatted|escape}</small>
			</span>
		</a>
		{/if}
		{/foreach}
	</div>
</div>
