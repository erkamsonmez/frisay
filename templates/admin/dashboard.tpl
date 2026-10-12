<div class="dash-page dash-corp">
	{if $adminHooks.admin_dashboard_top}
	<div class="dash-hook dash-hook--top mb-3">
		{$adminHooks.admin_dashboard_top nofilter}
	</div>
	{/if}

	<div class="dash-corp-head">
		<nav class="dash-corp-period" aria-label="{'Data period'|adminT}">
			{foreach $periodPills as $pill}
			<a href="{$adminUrl}dashboard?period={$pill.key|escape}" class="dash-corp-period__btn{if $period == $pill.key} is-active{/if}">{$pill.label|adminT}</a>
			{/foreach}
		</nav>
		<p class="dash-corp-head__sub mb-0">{$analytics.period_label|escape}</p>
	</div>

	<div class="dash-corp-kpis">
		<a href="{$adminUrl}orders" class="dash-corp-kpi dash-corp-kpi--solid dash-corp-kpi--blue">
			<span class="dash-corp-kpi__icon"><i data-lucide="shopping-bag"></i></span>
			<strong class="dash-corp-kpi__value">{$analytics.kpi.orders_total|escape}</strong>
			<span class="dash-corp-kpi__label">{'Total orders'|adminT}</span>
			{if $ordersTrend}
			<span class="dash-corp-kpi__trend">{if $ordersTrend > 0}+{/if}{$ordersTrend|escape}%</span>
			{/if}
		</a>
		<a href="{$adminUrl}products" class="dash-corp-kpi dash-corp-kpi--solid dash-corp-kpi--coral">
			<span class="dash-corp-kpi__icon"><i data-lucide="wallet"></i></span>
			<strong class="dash-corp-kpi__value">{$analytics.kpi.inventory_value_formatted|escape}</strong>
			<span class="dash-corp-kpi__label">{'Inventory value'|adminT}</span>
		</a>
		<a href="{$adminUrl}products" class="dash-corp-kpi">
			<span class="dash-corp-kpi__icon dash-corp-kpi__icon--blue"><i data-lucide="package"></i></span>
			<strong class="dash-corp-kpi__value">{$analytics.kpi.products_total|escape}</strong>
			<span class="dash-corp-kpi__label">{'Total products'|adminT}</span>
		</a>
		<a href="{$adminUrl}marketplace-products" class="dash-corp-kpi">
			<span class="dash-corp-kpi__icon dash-corp-kpi__icon--orange"><i data-lucide="layers"></i></span>
			<strong class="dash-corp-kpi__value">{$analytics.kpi.listings_total|escape}</strong>
			<span class="dash-corp-kpi__label">{'Total listings'|adminT}</span>
		</a>
		<a href="{$adminUrl}customers" class="dash-corp-kpi">
			<span class="dash-corp-kpi__icon dash-corp-kpi__icon--green"><i data-lucide="users"></i></span>
			<strong class="dash-corp-kpi__value">{$analytics.kpi.customers_total|escape}</strong>
			<span class="dash-corp-kpi__label">{'Customers'|adminT}</span>
		</a>
	</div>

	{if $adminHooks.admin_dashboard_kpi}
	<div class="dash-hook dash-hook--kpi mb-3">
		{$adminHooks.admin_dashboard_kpi nofilter}
	</div>
	{/if}

	<div class="row g-3 mb-3">
		<div class="col-xl-8">
			<div class="dash-panel h-100">
				<div class="dash-panel__head">
					<div>
						<h2 class="dash-panel__title">{'Daily sales'|adminT}</h2>
						<p class="dash-panel__sub mb-0">{$analytics.period_label|escape}</p>
					</div>
				</div>
				<div class="dash-panel__body">
					<div class="dash-chart-wrap">
						<canvas id="chartDailySales" height="280"></canvas>
					</div>
				</div>
			</div>
		</div>
		<div class="col-xl-4">
			<div class="dash-panel h-100 dash-period">
				<div class="dash-panel__head">
					<div>
						<h2 class="dash-panel__title">{'Data period'|adminT}</h2>
						<p class="dash-panel__sub mb-0">{$analytics.period_label|escape}</p>
					</div>
				</div>
				<div class="dash-panel__body p-0">
					<div class="dash-period-hero">
						<a href="{$adminUrl}orders" class="dash-period-hero__stat">
							<span class="dash-period-hero__label">{'Order count'|adminT}</span>
							<strong>{$analytics.period_stats.orders|escape}</strong>
						</a>
						<div class="dash-period-hero__stat">
							<span class="dash-period-hero__label">{'Turnover'|adminT}</span>
							<strong>{$analytics.period_stats.revenue_formatted|escape}</strong>
						</div>
						<div class="dash-period-hero__stat dash-period-hero__stat--profit">
							<span class="dash-period-hero__label">{'Profit'|adminT}</span>
							<strong>{$analytics.period_stats.profit_formatted|escape}</strong>
							<em>%{$analytics.period_stats.profit_rate|escape}</em>
						</div>
					</div>

					<div class="dash-period-ops">
						<a href="{$adminUrl}orders?status={$statusPending}" class="dash-period-ops__row">
							<span class="dash-period-ops__icon"><i data-lucide="clock"></i></span>
							<span>{'Awaiting shipment'|adminT}</span>
							<strong>{$analytics.period_stats.awaiting_shipment|escape}</strong>
						</a>
						<a href="{$adminUrl}orders?status={$statusShipped}" class="dash-period-ops__row">
							<span class="dash-period-ops__icon"><i data-lucide="truck"></i></span>
							<span>{'Shipped'|adminT}</span>
							<strong>{$analytics.period_stats.shipped|escape}</strong>
						</a>
						<a href="{$adminUrl}orders?status={$statusProcessing}" class="dash-period-ops__row">
							<span class="dash-period-ops__icon"><i data-lucide="loader"></i></span>
							<span>{'Processing'|adminT}</span>
							<strong>{$analytics.period_stats.processing|escape}</strong>
						</a>
						<div class="dash-period-ops__row">
							<span class="dash-period-ops__icon"><i data-lucide="package"></i></span>
							<span>{'Units sold'|adminT}</span>
							<strong>{$analytics.period_stats.sold_qty|escape}</strong>
						</div>
					</div>

					<div class="dash-period-risk">
						<div class="dash-period-risk__card">
							<span>{'Cancellations'|adminT}</span>
							<strong>{$analytics.period_stats.cancel_count|escape}</strong>
							<small>{$analytics.period_stats.cancel_amount_formatted|escape} · %{$analytics.period_stats.cancel_rate|escape}</small>
						</div>
						<div class="dash-period-risk__card">
							<span>{'Returns'|adminT}</span>
							<strong>{$analytics.period_stats.return_count|escape}</strong>
							<small>{$analytics.period_stats.return_amount_formatted|escape} · %{$analytics.period_stats.return_rate|escape}</small>
						</div>
						<div class="dash-period-risk__card dash-period-risk__card--basket">
							<span>{'Avg. basket'|adminT}</span>
							<strong>{$analytics.period_stats.avg_basket_formatted|escape}</strong>
							<small>{'Avg. cost'|adminT} {$analytics.period_stats.avg_cost_formatted|escape}</small>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-panel">
				<div class="dash-panel__head dash-panel__head--split">
					<h2 class="dash-panel__title">{'Last 50 Orders'|adminT}</h2>
					<a href="{$adminUrl}orders" class="dash-panel__link">{'View all'|adminT}</a>
				</div>
				<div class="dash-panel__body">
					{if $recentOrders|@count}
					<div class="ps-order-list">
						{include file='admin/partials/order-rows.tpl' orders=$recentOrders orderListMode='dashboard'}
					</div>
					{else}
					<p class="text-muted p-4 mb-0">{'No orders yet.'|adminT}</p>
					{/if}
				</div>
			</div>

			{if $adminHooks.admin_dashboard_main_left}
			<div class="dash-hook dash-hook--main-left mt-4">
				{$adminHooks.admin_dashboard_main_left nofilter}
			</div>
			{/if}
		</div>
	</div>

	<div class="dash-panel mb-4">
		<div class="dash-panel__head dash-panel__head--split">
			<div>
				<h2 class="dash-panel__title">{'Sales revenue'|adminT}</h2>
				<p class="dash-panel__sub mb-0">{'Marketplace-based period revenue'|adminT}</p>
			</div>
			<span class="dash-period-chip">{$analytics.period_range_label|escape}</span>
		</div>
		<div class="dash-panel__body">
			{if $analytics.mp_sales_chart.items|@count}
			<div class="dash-mp-chart-wrap">
				<canvas id="chartMpSales"></canvas>
			</div>
			{else}
			<p class="text-muted mb-0">{'No sales data yet.'|adminT}</p>
			{/if}
		</div>
	</div>

	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-panel">
				<div class="dash-panel__head dash-panel__head--split">
					<h2 class="dash-panel__title">{'Marketplace Orders'|adminT}</h2>
					<a href="{$adminUrl}marketplace-orders" class="dash-panel__link">{'View all'|adminT}</a>
				</div>
				<div class="dash-panel__body">
					{if $recentMarketplaceOrders|@count}
					<div class="ps-order-list">
						{include file='admin/partials/marketplace-order-rows.tpl' marketplaceOrders=$recentMarketplaceOrders mpOrderListMode='dashboard'}
					</div>
					{else}
					<div class="mp-order-empty p-4 mb-0">{'No marketplace orders yet.'|adminT}</div>
					{/if}
				</div>
			</div>
		</div>
	</div>

	<div class="row g-3 mb-3">
		{if $analytics.questions|@count}
		<div class="col-xl-3">
			<div class="dash-panel h-100">
				<div class="dash-panel__head">
					<h2 class="dash-panel__title">{'Questions'|adminT}</h2>
				</div>
				<div class="dash-panel__body p-0">
					<div class="dash-corp-qna dash-corp-qna--stack">
						{foreach $analytics.questions as $q}
						<a href="{$q.url|escape}" class="dash-corp-qna__cell">
							<span class="dash-corp-qna__label">
								{if $q.icon_url|default:''}
								<img src="{$q.icon_url|escape}" alt="" width="18" height="18" loading="lazy" onerror="this.style.display='none'">
								{/if}
								{$q.label|escape}
							</span>
							<strong>{$q.unanswered|escape}<span>/{$q.total|escape}</span></strong>
						</a>
						{/foreach}
					</div>
				</div>
			</div>
		</div>
		{/if}
		<div class="{if $analytics.questions|@count}col-xl-9{else}col-12{/if}">
			<div class="dash-panel h-100">
				<div class="dash-panel__head dash-panel__head--split">
					<div>
						<h2 class="dash-panel__title">{'Product performance'|adminT}</h2>
						<p class="dash-panel__sub mb-0">{'Period revenue and units sold'|adminT}</p>
					</div>
					<a href="{$adminUrl}products" class="dash-panel__link">{'All products'|adminT}</a>
				</div>
				<div class="dash-panel__body p-0">
					{if $analytics.product_performance|@count}
					<div class="table-responsive">
						<table class="dash-corp-table">
							<thead>
								<tr>
									<th>{'Product'|adminT}</th>
									<th>{'Turnover'|adminT}</th>
									<th>{'Qty'|adminT}</th>
									<th>{'Stock'|adminT}</th>
								</tr>
							</thead>
							<tbody>
								{foreach $analytics.product_performance as $prod}
								<tr>
									<td>
										<a href="{$prod.edit_url|escape}" class="dash-corp-product">
											<img src="{$prod.image_url|escape}" alt="" width="40" height="40">
											<span>
												<strong>{$prod.product_name|escape}</strong>
												<small>#{$prod.id_product|escape}</small>
											</span>
										</a>
									</td>
									<td>{$prod.revenue_formatted|escape}</td>
									<td>{$prod.sold_qty|escape} {'pcs'|adminT}</td>
									<td>{$prod.stock_formatted|escape}</td>
								</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
					{else}
					<p class="text-muted p-4 mb-0">{'No sales data yet.'|adminT}</p>
					{/if}
				</div>
			</div>
		</div>
	</div>

	{if $adminHooks.admin_dashboard_main_right}
	<div class="row g-4 mb-4">
		<div class="col-12">
			<div class="dash-hook dash-hook--main-right">
				{$adminHooks.admin_dashboard_main_right nofilter}
			</div>
		</div>
	</div>
	{/if}

	{if $adminHooks.admin_dashboard_bottom}
	<div class="dash-hook dash-hook--bottom">
		{$adminHooks.admin_dashboard_bottom nofilter}
	</div>
	{/if}
</div>

<script>
window.__dashboardCharts = {
	dailySales: {$chartDailySales nofilter},
	mpSales: {$chartMpSales nofilter}
};

(function () {
	function toggleDetail(id) {
		var row = document.querySelector('.adm-order-row[data-order-id="' + id + '"]');
		var detail = document.getElementById('adm-order-detail-' + id);
		if (!detail) return;
		var open = !detail.classList.contains('is-open');
		detail.classList.toggle('is-open', open);
		detail.hidden = !open;
		if (row) row.classList.toggle('is-open', open);
		if (window.lucide) window.lucide.createIcons();
	}

	document.querySelectorAll('.adm-order-row').forEach(function (row) {
		row.addEventListener('click', function (e) {
			if (e.target.closest('a, button, input, select, .dropdown-menu, .adm-order-select-cell, .adm-order-action-cell')) {
				return;
			}
			toggleDetail(row.getAttribute('data-order-id'));
		});
		row.addEventListener('keydown', function (e) {
			if (e.key === 'Enter' || e.key === ' ') {
				e.preventDefault();
				toggleDetail(row.getAttribute('data-order-id'));
			}
		});
	});

	document.querySelectorAll('[data-order-expand]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.stopPropagation();
			toggleDetail(btn.getAttribute('data-order-expand'));
		});
	});

	if (window.lucide) window.lucide.createIcons();
})();
</script>
