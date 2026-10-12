<!DOCTYPE html>
<html lang="tr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{if $printOrders|@count > 1}{'Shipping labels'|adminT} ({$printOrders|@count}){else}{'Shipping label'|adminT} #{$printOrders[0].reference|escape}{/if}</title>
{literal}
	<style>
		*{ box-sizing:border-box; margin:0; padding:0; }
		@page{
			size: A6 portrait;
			margin: 0;
		}
		html,body{
			background:#d1d5db;
			color:#000;
			font-family: Arial, Helvetica, "Segoe UI", sans-serif;
			-webkit-print-color-adjust:exact;
			print-color-adjust:exact;
		}
		body{ padding:16px 12px 24px; }
		.toolbar{
			width:105mm;
			max-width:100%;
			margin:0 auto 14px;
			display:flex;
			gap:8px;
		}
		.toolbar button{
			border:0;
			background:#111;
			color:#fff;
			border-radius:8px;
			padding:9px 16px;
			font:600 13px/1 Arial,sans-serif;
			cursor:pointer;
		}
		.toolbar button.secondary{
			background:#fff;
			color:#111;
			border:1px solid #cbd5e1;
		}
		/* A6 sheet */
		.label{
			width:105mm;
			height:148mm;
			max-width:100%;
			margin:0 auto 16px;
			background:#fff;
			padding:4.5mm 5mm 4mm;
			display:flex;
			flex-direction:column;
			box-shadow:0 8px 28px rgba(15,23,42,.18);
			overflow:hidden;
			page-break-after:always;
			break-after:page;
			page-break-inside:avoid;
			break-inside:avoid;
		}
		.label:last-of-type{
			margin-bottom:0;
			page-break-after:auto;
			break-after:auto;
		}
		/* —— Header —— */
		.head{
			display:flex;
			justify-content:space-between;
			align-items:stretch;
			gap:4mm;
			padding-bottom:3.2mm;
			border-bottom:2.2px solid #000;
		}
		.brand{
			flex:0 1 42%;
			display:flex;
			flex-direction:column;
			justify-content:flex-start;
			gap:3.2mm;
			min-width:0;
			padding-top:0.5mm;
		}
		.brand-logo{
			display:block;
			max-width:100%;
			width:auto;
			height:9.5mm;
			max-height:9.5mm;
			object-fit:contain;
			object-position:left center;
		}
		.brand-name{
			font-size:5.2mm;
			font-weight:800;
			letter-spacing:-0.02em;
			line-height:1.05;
			color:#000;
		}
		.brand-cargo-logo{
			display:block;
			max-width:100%;
			width:auto;
			height:8mm;
			max-height:8mm;
			object-fit:contain;
			object-position:left center;
		}
		.brand-cargo-name{
			font-size:4.2mm;
			font-weight:800;
			color:#0033a0;
			line-height:1.1;
			letter-spacing:-0.01em;
		}
		.barcode-col{
			flex:1 1 55%;
			display:flex;
			flex-direction:column;
			align-items:flex-end;
			justify-content:flex-start;
			min-width:0;
			text-align:right;
		}
		.barcode-svg{
			width:100%;
			display:flex;
			justify-content:flex-end;
		}
		.barcode-svg svg{
			display:block;
			width:100%;
			max-width:54mm;
			height:14mm;
		}
		.barcode-code{
			margin-top:1.2mm;
			font-size:2.7mm;
			font-weight:700;
			letter-spacing:0.04em;
			font-variant-numeric:tabular-nums;
			line-height:1.2;
			word-break:break-all;
		}
		.shop-tag{
			margin-top:auto;
			padding-top:2mm;
			font-size:3mm;
			font-weight:600;
			color:#111;
			line-height:1;
		}
		/* —— Sections —— */
		.block{
			padding:2.8mm 0 2.6mm;
			border-bottom:2.2px solid #000;
		}
		.block-title{
			font-size:3.4mm;
			font-weight:800;
			margin-bottom:2mm;
			letter-spacing:0;
		}
		.field{
			display:grid;
			grid-template-columns:18mm 1fr;
			column-gap:1.5mm;
			align-items:start;
			margin-bottom:1.6mm;
			font-size:2.9mm;
			line-height:1.35;
		}
		.field:last-child{ margin-bottom:0; }
		.field-k{
			font-weight:400;
			color:#111;
			white-space:nowrap;
		}
		.field-v{
			font-weight:400;
			color:#111;
			word-break:break-word;
		}
		.field-v.is-name{
			font-weight:800;
			font-size:3.1mm;
		}
		.meta{
			display:flex;
			justify-content:space-between;
			align-items:baseline;
			gap:3mm;
			font-size:2.9mm;
			line-height:1.35;
			font-weight:400;
		}
		.meta strong{ font-weight:700; }
		/* —— Items —— */
		.items{
			flex:1 1 auto;
			min-height:0;
			padding-top:2.5mm;
			overflow:hidden;
		}
		.item{
			display:grid;
			grid-template-columns:11mm 9mm 1fr;
			column-gap:2.2mm;
			align-items:center;
			padding:2mm 0 2.4mm;
			border-bottom:1px dashed #666;
		}
		.item:last-child{ border-bottom:1px dashed #666; }
		.item-img,
		.item-img-empty{
			width:11mm;
			height:11mm;
			object-fit:contain;
			background:#fff;
		}
		.item-img-empty{
			border:1px solid #e5e7eb;
			background:#f8fafc;
		}
		.item-qty{
			font-size:4.2mm;
			font-weight:800;
			line-height:1;
			letter-spacing:-0.02em;
			white-space:nowrap;
		}
		.item-meta{ min-width:0; }
		.item-sku{
			font-size:2.7mm;
			font-weight:700;
			line-height:1.2;
			color:#111;
		}
		.item-name{
			margin-top:0.6mm;
			font-size:2.8mm;
			font-weight:500;
			line-height:1.25;
			color:#111;
		}
		@media print{
			html,body{ background:#fff; }
			body{ padding:0; }
			.toolbar{ display:none !important; }
			.label{
				width:105mm;
				height:148mm;
				margin:0;
				box-shadow:none;
				page-break-after:always;
				break-after:page;
				page-break-inside:avoid;
				break-inside:avoid;
			}
			.label:last-of-type{
				page-break-after:auto;
				break-after:auto;
			}
		}
	</style>
{/literal}
</head>
<body>
	<div class="toolbar">
		<button type="button" onclick="window.print()">{'Print'|adminT}</button>
		<button type="button" class="secondary" onclick="window.close()">{'Close'|adminT}</button>
	</div>

	{foreach $printOrders as $order}
	<section class="label" aria-label="{'Shipping label'|adminT} #{$order.reference|escape}">
		<header class="head">
			<div class="brand">
				{if $printStoreLogo}
				<img class="brand-logo" src="{$printStoreLogo|escape}" alt="{$printSiteName|escape}">
				{else}
				<div class="brand-name">{$printSiteName|escape}</div>
				{/if}
				{if $order.cargo_logo_url}
				<img class="brand-cargo-logo" src="{$order.cargo_logo_url|escape}" alt="{$order.cargo_name|escape}">
				{elseif $order.cargo_name}
				<div class="brand-cargo-name">{$order.cargo_name|escape}</div>
				{/if}
			</div>
			<div class="barcode-col">
				<div class="barcode-svg">{$order.barcode_svg nofilter}</div>
				<div class="barcode-code">{$order.barcode_value|escape}</div>
				<div class="shop-tag">{$printSiteName|escape}</div>
			</div>
		</header>

		<section class="block">
			<div class="block-title">Alıcı Bilgileri</div>
			<div class="field">
				<div class="field-k">Ad/Soyad :</div>
				<div class="field-v is-name">{$order.customer_name|escape}</div>
			</div>
			<div class="field">
				<div class="field-k">Adres :</div>
				<div class="field-v">{$order.label_address|escape}</div>
			</div>
			<div class="field">
				<div class="field-k">Telefon :</div>
				<div class="field-v">{if $order.customer_phone}{$order.customer_phone|escape}{else}&nbsp;{/if}</div>
			</div>
		</section>

		<section class="block">
			<div class="block-title">Sipariş Bilgileri</div>
			<div class="meta">
				<span>Sipariş No : <strong>{$order.reference|escape}</strong></span>
				<span>Sipariş Tarihi : <strong>{$order.label_date|escape}</strong></span>
			</div>
		</section>

		<section class="items">
			{foreach $order.items as $item}
			<article class="item">
				{if $item.image_url}
				<img class="item-img" src="{$item.image_url|escape}" alt="">
				{else}
				<div class="item-img-empty" aria-hidden="true"></div>
				{/if}
				<div class="item-qty">{$item.qty_display|escape} x</div>
				<div class="item-meta">
					{if $item.sku}<div class="item-sku">{$item.sku|escape}</div>{/if}
					<div class="item-name">{$item.product_name|escape}</div>
				</div>
			</article>
			{/foreach}
		</section>
	</section>
	{/foreach}

	{if $printAuto}
	<script>window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 280); });</script>
	{/if}
</body>
</html>
