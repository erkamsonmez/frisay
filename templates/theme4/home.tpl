{if $hooks.home}
	<div class="t4-home">{$hooks.home nofilter}</div>
{else}
	<div class="t4-home">
		{if $hooks.home_slider}
			<div class="t4-home-slider">{$hooks.home_slider nofilter}</div>
		{elseif isset($featuredProducts) && $featuredProducts|@count > 0}
			{include file='./plugin/slide.tpl'}
		{/if}
		{if isset($featuredProducts) && $featuredProducts|@count > 0}
			{capture assign=homeFeatTitle}{'Featured products'|translate}{/capture}
			{include file='./partials/sm-category-products-section.tpl' products=$featuredProducts title=$homeFeatTitle url='' listId='homeFeatured'}
		{/if}
		{if isset($categoryBlocks)}
			{foreach $categoryBlocks as $block}
				{include file='./partials/sm-category-products-section.tpl' products=$block.products title=$block.category.category_name url=$block.url listId='homeCat'|cat:$block.category.id_category}
			{/foreach}
		{/if}
		{if $hooks.home_bottom}
			<div class="t4-home-bottom">{$hooks.home_bottom nofilter}</div>
		{/if}
	</div>
{/if}
