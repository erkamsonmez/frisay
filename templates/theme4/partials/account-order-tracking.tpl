{if $selectedOrder.cargo_company|default:'' ne '' || $selectedOrder.tracking_number|default:'' ne ''}
{assign var=cargoTitle value=$selectedOrder.cargo_company}
<div class="account-tracking-card mb-4">
	<div class="account-tracking-card__brand">
		{if $selectedOrder.cargo_logo_url|default:''}
		<img src="{$selectedOrder.cargo_logo_url|escape}" alt="{$cargoTitle|escape}">
		{else}
		<span class="account-tracking-card__icon" aria-hidden="true">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
				<rect x="1" y="7" width="15" height="13" rx="2"/>
				<path d="M16 10h4l3 4v6h-7V10z"/>
				<circle cx="5.5" cy="20.5" r="1.5"/>
				<circle cx="18.5" cy="20.5" r="1.5"/>
			</svg>
		</span>
		{/if}
	</div>
	<div class="account-tracking-card__info">
		<div class="account-tracking-card__label">{'Cargo'|translate}</div>
		{if $cargoTitle}
		<div class="account-tracking-card__company">{$cargoTitle|escape}</div>
		{/if}
		{if $selectedOrder.tracking_number|default:''}
		<div class="account-tracking-card__code">
			<span>{'Tracking number'|translate}</span>
			<code id="order-tracking-{$selectedOrder.id_order}">{$selectedOrder.tracking_number|escape}</code>
			<button type="button" class="account-tracking-card__copy" title="{'Copy'|translate}" aria-label="{'Copy'|translate}" data-copy-target="order-tracking-{$selectedOrder.id_order}">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
			</button>
		</div>
		{/if}
	</div>
	{if $selectedOrder.tracking_url|default:''}
	<a href="{$selectedOrder.tracking_url|escape}" class="account-tracking-card__btn" target="_blank" rel="noopener">
		<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
			<rect x="1" y="7" width="15" height="13" rx="2"/>
			<path d="M16 10h4l3 4v6h-7V10z"/>
			<circle cx="5.5" cy="20.5" r="1.5"/>
			<circle cx="18.5" cy="20.5" r="1.5"/>
		</svg>
		{'Track shipment'|translate}
	</a>
	{/if}
</div>
{literal}
<script>
(function () {
	document.querySelectorAll('.account-tracking-card__copy[data-copy-target]').forEach(function (btn) {
		if (btn.getAttribute('data-bound') === '1') {
			return;
		}
		btn.setAttribute('data-bound', '1');
		btn.addEventListener('click', function () {
			var el = document.getElementById(btn.getAttribute('data-copy-target'));
			if (!el || !navigator.clipboard) {
				return;
			}
			var prev = btn.innerHTML;
			navigator.clipboard.writeText((el.textContent || '').trim()).then(function () {
				btn.classList.add('is-copied');
				btn.innerHTML = '\u2713';
				setTimeout(function () {
					btn.classList.remove('is-copied');
					btn.innerHTML = prev;
				}, 1600);
			});
		});
	});
})();
</script>
{/literal}
{/if}
