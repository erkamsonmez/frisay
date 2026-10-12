{*
	$col — sort key
	$label — header text
	$sortLinks — from Pagination::adminColumnSort()['links']
	$class — optional wrapper class
*}
<span class="adm-sort-label{if $sortLinks[$col].active} is-sorted is-{$sortLinks[$col].dir}{/if}{if $class|default:'' != ''} {$class|escape}{/if}">
	<a href="{$sortLinks[$col].url|escape}" class="adm-sort-link" title="{if $sortLinks[$col].next_dir == 'asc'}ASC{else}DESC{/if}">
		<span>{$label|escape}</span>
		<span class="adm-sort-icon" aria-hidden="true"></span>
	</a>
</span>
