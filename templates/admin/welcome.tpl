<div class="welcome-page">
	<section class="welcome-hero">
		<div class="welcome-hero__copy">
			<p class="welcome-kicker">{'Getting started'|adminT}</p>
			<h2 class="welcome-hero__title">{'Welcome to FriSay'|adminT}</h2>
			<p class="welcome-hero__lead">{'This page scores your store setup and SEO so you can see what to finish before selling.'|adminT}</p>
			<div class="welcome-hero__cta">
				<span class="welcome-chip">{$welcomeDoneCount|escape}/{$welcomeTotalChecks|escape} {'steps complete'|adminT}</span>
				{if $welcomeNext}
				<a class="welcome-btn" href="{$welcomeNext.action_url|escape}">
					{'Continue setup'|adminT}
					<i data-lucide="arrow-right"></i>
				</a>
				{else}
				<span class="welcome-chip welcome-chip--ok">{'All set'|adminT}</span>
				{/if}
			</div>
		</div>
		<div class="welcome-hero__scores">
			<div class="welcome-meter welcome-meter--{$welcomeSetupTone|escape}">
				<svg viewBox="0 0 36 36" aria-hidden="true">
					<path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
					<path class="welcome-meter__val" stroke-dasharray="{$welcomeSetupScore|escape}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
				</svg>
				<div class="welcome-meter__num">
					<strong>{$welcomeSetupScore|escape}</strong>
					<span>{'Setup score'|adminT}</span>
				</div>
			</div>
			<div class="welcome-meter welcome-meter--{$welcomeSeoAverageTone|escape}">
				<svg viewBox="0 0 36 36" aria-hidden="true">
					<path d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
					<path class="welcome-meter__val" stroke-dasharray="{$welcomeSeoAverage|escape}, 100" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
				</svg>
				<div class="welcome-meter__num">
					<strong>{$welcomeSeoAverage|escape}</strong>
					<span>{'SEO average'|adminT}</span>
				</div>
			</div>
		</div>
	</section>

	<div class="welcome-grid">
		<section class="welcome-panel">
			<header class="welcome-panel__head">
				<div>
					<h3>{'Store setup'|adminT}</h3>
					<p>{'Each item is worth an equal share of 100 points. Complete them all to reach 100.'|adminT}</p>
				</div>
			</header>
			<div class="welcome-checks">
				{foreach $welcomeSetupItems as $item}
				<article class="welcome-check{if $item.done} is-done{/if}">
					<span class="welcome-check__mark" aria-hidden="true">
						{if $item.done}<i data-lucide="check"></i>{else}<i data-lucide="{$item.icon|escape}"></i>{/if}
					</span>
					<div class="welcome-check__body">
						<div class="welcome-check__head">
							<h4>{$item.title|adminT}</h4>
							{if $item.done}
							<span class="welcome-badge welcome-badge--ok">{'Done'|adminT}</span>
							{else}
							<span class="welcome-badge">{'Not done'|adminT}</span>
							{/if}
						</div>
						<p>{$item.help|adminT}</p>
						<div class="welcome-check__meta">
							<span>{$item.detail|escape}</span>
							<a href="{$item.action_url|escape}">{$item.action|adminT} <i data-lucide="arrow-up-right"></i></a>
						</div>
						<div class="welcome-bar" aria-hidden="true"><i style="width:{$item.percent}%"></i></div>
					</div>
				</article>
				{/foreach}
			</div>
		</section>

		<div class="welcome-side">
			<section class="welcome-panel">
				<header class="welcome-panel__head">
					<div>
						<h3>{'SEO scores'|adminT}</h3>
						<p>{'A meta description is sufficient from 120 characters. Shorter texts count as incomplete. Empty texts score 0.'|adminT}</p>
					</div>
				</header>
				<div class="welcome-seo-list">
					{foreach $welcomeSeoCards as $card}
					<article class="welcome-seo">
						<div class="welcome-seo__top">
							<div>
								<h4>{$card.label|adminT}</h4>
								<p>{$card.row.total|escape} {'records'|adminT}</p>
							</div>
							<strong class="welcome-seo__score welcome-seo__score--{$card.row.tone|escape}">{$card.row.score|escape}</strong>
						</div>
						{if $card.row.total == 0}
						<p class="welcome-seo__empty">{'Add records first to calculate this score.'|adminT}</p>
						{else}
						<div class="welcome-seg" aria-hidden="true">
							<i class="is-ok" style="width:{$card.row.ok_pct}%"></i>
							<i class="is-mid" style="width:{$card.row.incomplete_pct}%"></i>
							<i class="is-off" style="width:{$card.row.empty_pct}%"></i>
						</div>
						<ul class="welcome-seo__stats">
							<li><b>{$card.row.ok|escape}</b> {'Sufficient'|adminT}</li>
							<li><b>{$card.row.incomplete|escape}</b> {'Incomplete'|adminT}</li>
							<li><b>{$card.row.empty|escape}</b> {'Empty'|adminT}</li>
						</ul>
						{/if}
						<a class="welcome-seo__link" href="{$card.url|escape}">{$card.edit|adminT} <i data-lucide="arrow-up-right"></i></a>
					</article>
					{/foreach}
				</div>
			</section>

			<section class="welcome-panel" id="admin-url">
				<header class="welcome-panel__head">
					<div>
						<h3>{'Admin URL'|adminT}</h3>
						<p>{'Current admin path:'|adminT} <code>/{$welcomeAdminUri|escape}/</code></p>
					</div>
					{if $welcomeAdminChanged}
					<span class="welcome-badge welcome-badge--ok">{'Done'|adminT}</span>
					{else}
					<span class="welcome-badge welcome-badge--warn">{'Not done'|adminT}</span>
					{/if}
				</header>
				{if $welcomeAdminChanged}
				<p class="welcome-note welcome-note--ok">{'The default /admin path is hidden. Bookmark this address.'|adminT}</p>
				{else}
				<ol class="welcome-ol">
					<li>{'Open <em>config/env.php</em> and set for example:'|adminT nofilter} <code>'ADMIN_URI' => 'bo_9xK2m7',</code></li>
					<li>{'Use only letters, numbers, underscore and hyphen. Do not rename the physical <em>admin/</em> folder.'|adminT nofilter}</li>
					<li>{'Open the panel at the new URL and bookmark it. The old <em>/admin</em> path will return 404.'|adminT nofilter}</li>
				</ol>
				{/if}
			</section>
		</div>
	</div>

	{if $welcomePayments|@count}
	<section class="welcome-panel">
		<header class="welcome-panel__head">
			<div>
				<h3>{'Payment modules'|adminT}</h3>
			</div>
		</header>
		<div class="welcome-pay-list">
			{foreach $welcomePayments as $pay}
			<a class="welcome-pay{if $pay.active && $pay.configured} is-ok{elseif $pay.active} is-warn{/if}" href="{$pay.url|escape}">
				<span class="welcome-pay__dot" aria-hidden="true"></span>
				<strong>{$pay.title|escape}</strong>
				<span>
					{if !$pay.active}{'Installed, not enabled'|adminT}
					{elseif !$pay.configured}{'Enabled, missing settings'|adminT}
					{else}{'Ready'|adminT}{/if}
				</span>
			</a>
			{/foreach}
		</div>
	</section>
	{/if}

	<section class="welcome-panel">
		<header class="welcome-panel__head">
			<div>
				<h3>{'How to use FriSay'|adminT}</h3>
			</div>
		</header>
		<div class="welcome-guide">
			{foreach $welcomeGuide as $step}
			<article class="welcome-guide__item">
				<span class="welcome-guide__n">{$step.n|escape}</span>
				<div>
					<h4>{$step.title|adminT}</h4>
					<p>{$step.text|adminT}</p>
					<a href="{$step.url|escape}">{$step.label|adminT} <i data-lucide="arrow-up-right"></i></a>
				</div>
			</article>
			{/foreach}
		</div>
	</section>
</div>
