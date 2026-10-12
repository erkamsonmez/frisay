{*
	$col — sort key (id, price, …)
	$label — header text
	$sortLinks — from Pagination::adminColumnSort()['links']
	$class — optional extra th class
*}
<th class="adm-sort-th{if $sortLinks[$col].active} is-sorted is-{$sortLinks[$col].dir}{/if}{if $class|default:'' != ''} {$class|escape}{/if}">
	<a href="{$sortLinks[$col].url|escape}" class="adm-sort-link" title="{if $sortLinks[$col].next_dir == 'asc'}ASC{else}DESC{/if}">
		<span>{$label|adminT}</span>
		<span class="adm-sort-icon" aria-hidden="true"></span>
	</a>
</th>
