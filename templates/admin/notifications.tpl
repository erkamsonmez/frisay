<div class="d-flex flex-wrap gap-2 mb-3">
	<a href="{$adminUrl}notifications" class="admin-notif-filter{if $notifyType == ''} is-active{/if}">
		<span class="admin-notif-filter__dot" style="background:#111827"></span>
		{'All'|adminT}
		<span class="admin-notif-filter__count">{$unreadCount}</span>
	</a>
	{foreach $notifyTypeButtons as $btn}
	<a href="{$adminUrl}notifications?type={$btn.type|escape}" class="admin-notif-filter{if $notifyType == $btn.type} is-active{/if}">
		<span class="admin-notif-filter__dot" style="background:{$btn.color|escape}"></span>
		{$btn.label|adminT}
		<span class="admin-notif-filter__count">{$btn.count}</span>
	</a>
	{/foreach}
</div>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
	<p class="text-muted small mb-0">{if $unreadCount > 0}{$unreadCount}{' unread notifications'|adminT}{else}{'All notifications read'|adminT}{/if}</p>
	{if $unreadCount > 0}
	<form method="post" class="mb-0">
		<input type="hidden" name="token" value="{$adminToken}">
		{if $notifyType}<input type="hidden" name="type" value="{$notifyType|escape}">{/if}
		<button type="submit" name="markAllRead" value="1" class="btn btn-sm btn-outline-secondary">{'Mark all as read'|adminT}</button>
	</form>
	{/if}
</div>

{if $notifications|@count}
<div class="admin-notif-list">
	{foreach $notifications as $n}
	<div class="admin-notif-card{if !$n.is_read} is-unread{/if}">
		<span class="admin-notif-card__icon" style="background:{$n.color|escape}">
			<i data-lucide="{$n.icon|escape}"></i>
		</span>
		<span class="admin-notif-card__body">
			<strong class="admin-notif-card__title">{$n.type_label|escape}</strong>
			<span class="admin-notif-card__msg">{$n.message|escape}</span>
		</span>
		<span class="admin-notif-card__meta">
			<span class="admin-notif-card__time">{$n.time_ago|escape}</span>
			{if $n.link}
			<a href="{$n.link|escape}" class="admin-notif-card__link">{'View'|adminT}</a>
			{/if}
			{if !$n.is_read}
			<form method="post" class="mb-0">
				<input type="hidden" name="token" value="{$adminToken}">
				{if $notifyType}<input type="hidden" name="type" value="{$notifyType|escape}">{/if}
				<input type="hidden" name="id" value="{$n.id_notification}">
				<button type="submit" name="markRead" value="1" class="admin-notif-card__read">{'Mark as read'|adminT}</button>
			</form>
			{/if}
		</span>
	</div>
	{/foreach}
</div>
{else}
<p class="text-muted p-4 mb-0">{'No notifications.'|adminT}</p>
{/if}
