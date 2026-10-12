{if $flash}
	<div class="toast-container position-fixed bottom-0 end-0 p-4" style="z-index:9999;">
		<div class="toast frisay-toast {$flashType|default:'success'} show" role="alert">
			<div class="toast-icon">
				<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6 9 17l-5-5"/></svg>
			</div>
			<div class="toast-body p-0">
				<div class="toast-message">{$flash|escape}</div>
			</div>
			<button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
		</div>
	</div>
{/if}

<link rel="stylesheet" href="{$domain}templates/admin/css/media-library.css?v={$smarty.now}">

{capture assign=t4HooksJson}[{foreach $hookChoices as $h}"{$h|escape}"{if !$h@last},{/if}{/foreach}]{/capture}
{capture assign=t4WidgetsHomeJson}[{foreach $widgetsHome as $w}"{$w|escape}"{if !$w@last},{/if}{/foreach}]{/capture}
{capture assign=t4WidgetsHeaderJson}[{foreach $widgetsHeader as $w}"{$w|escape}"{if !$w@last},{/if}{/foreach}]{/capture}
{capture assign=t4WidgetsFooterJson}[{foreach $widgetsFooter as $w}"{$w|escape}"{if !$w@last},{/if}{/foreach}]{/capture}

<script type="application/json" id="t4PreviewConfig">{$t4PreviewConfigJson nofilter}</script>

<div class="t4-visual">
	<div class="t4-visual__bar">
		<a class="t4-visual__exit" href="{$t4ExitUrl|escape}">{'Exit'|t4l}</a>
		<strong class="t4-visual__brand">{'Theme Edit'|t4l}</strong>
		<div class="dropdown">
			<button type="button" class="btn btn-sm btn-dark dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">+ {'Add section'|t4l}</button>
			<ul class="dropdown-menu">
				<li><button type="button" class="dropdown-item" data-t4-add-section="container">{'Container'|t4l}</button></li>
				<li><button type="button" class="dropdown-item" data-t4-add-section="category_products">{'Category products'|t4l}</button></li>
				<li><button type="button" class="dropdown-item" data-t4-add-section="banner">{'Banner'|t4l}</button></li>
				<li><button type="button" class="dropdown-item" data-t4-add-section="text">{'Text / HTML'|t4l}</button></li>
				<li><button type="button" class="dropdown-item" data-t4-add-section="links">{'Links'|t4l}</button></li>
				<li><button type="button" class="dropdown-item" data-t4-add-section="hook">{'Hook'|t4l}</button></li>
			</ul>
		</div>
		<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-panel="theme">{'Logo'|t4l}</button>
		<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-panel="colors">{'Colors'|t4l}</button>
		<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-panel="custom">{'Custom'|t4l}</button>
		<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-panel="chrome-header">{'Header texts'|t4l}</button>
		<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-panel="chrome-trust">{'Service bar'|t4l}</button>
		<span class="text-muted small" data-t4-preview-status></span>
		<button type="button" class="btn btn-sm btn-primary ms-auto" data-t4-publish>{'Save'|t4l}</button>
	</div>
	<iframe id="t4LiveFrame" class="t4-visual__frame" src="{$t4LivePreviewUrl|escape}" title="{'Theme Edit'|t4l}"></iframe>
</div>

<div class="modal fade" id="t4EditModal" tabindex="-1" aria-labelledby="t4EditModalTitle" aria-hidden="true">
	<div class="modal-dialog modal-xl modal-dialog-scrollable modal-fullscreen-lg-down">
		<div class="modal-content">
			<div class="modal-header py-2">
				<strong class="modal-title" id="t4EditModalTitle" data-t4-drawer-title>{'Edit'|t4l}</strong>
				<div class="d-flex align-items-center gap-1 ms-auto">
					<div data-t4-section-actions hidden>
						<div class="d-flex gap-1">
							<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-move="up" title="{'Up'|t4l}">↑</button>
							<button type="button" class="btn btn-sm btn-outline-secondary" data-t4-move="down" title="{'Down'|t4l}">↓</button>
							<button type="button" class="btn btn-sm btn-outline-danger" data-t4-delete-section>{'Delete'|t4l}</button>
						</div>
					</div>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Cancel'|t4l}"></button>
				</div>
			</div>
			<div class="modal-body">
				{assign var=t4c value=$t4Chrome|default:[]}
				<div id="t4ChromePanel" class="t4-chrome-panel" hidden>
					<div data-t4-chrome-section="header">
						<h2 class="h5 mb-3">{'Header texts'|t4l}</h2>
						<div class="row g-3">
							<div class="col-md-6">
								<label class="form-label" for="t4ChromeSupportLabel">{'Support label'|t4l}</label>
								<input type="text" id="t4ChromeSupportLabel" class="form-control" data-t4-chrome-f="header_support_label" value="{$t4c.header_support_label|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="t4ChromeSupportValue">{'Support phone / value'|t4l}</label>
								<input type="text" id="t4ChromeSupportValue" class="form-control" data-t4-chrome-f="header_support_value" value="{$t4c.header_support_value|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="t4ChromeDeliveryLabel">{'Delivery label'|t4l}</label>
								<input type="text" id="t4ChromeDeliveryLabel" class="form-control" data-t4-chrome-f="header_delivery_label" value="{$t4c.header_delivery_label|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="t4ChromeDeliveryValue">{'Delivery value'|t4l}</label>
								<input type="text" id="t4ChromeDeliveryValue" class="form-control" data-t4-chrome-f="header_delivery_value" value="{$t4c.header_delivery_value|default:''|escape}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Top bar 1'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="header_bar_1" value="{$t4c.header_bar_1|default:''|escape}" placeholder="{'Free Delivery'|t4l}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Top bar 2'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="header_bar_2" value="{$t4c.header_bar_2|default:''|escape}" placeholder="{'Easy Returns'|t4l}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Top bar 3'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="header_bar_3" value="{$t4c.header_bar_3|default:''|escape}" placeholder="{'Support'|t4l}">
							</div>
							<div class="col-md-6">
								<label class="form-label" for="t4ChromeBarBg">{'Top bar background'|t4l}</label>
								<div class="input-group">
									<input type="color" class="form-control form-control-color" value="{$t4c.header_bar_bg|default:'#0f172a'|escape}" data-t4-color-picker data-target="t4ChromeBarBg" title="{'Top bar background'|t4l}">
									<input type="text" id="t4ChromeBarBg" class="form-control font-monospace" data-t4-chrome-f="header_bar_bg" value="{$t4c.header_bar_bg|default:'#0f172a'|escape}">
								</div>
							</div>
							<div class="col-md-6">
								<label class="form-label" for="t4ChromeBarColor">{'Top bar text'|t4l}</label>
								<div class="input-group">
									<input type="color" class="form-control form-control-color" value="{$t4c.header_bar_color|default:'#ffffff'|escape}" data-t4-color-picker data-target="t4ChromeBarColor" title="{'Top bar text'|t4l}">
									<input type="text" id="t4ChromeBarColor" class="form-control font-monospace" data-t4-chrome-f="header_bar_color" value="{$t4c.header_bar_color|default:'#ffffff'|escape}">
								</div>
							</div>
						</div>
					</div>
					<div data-t4-chrome-section="trust">
						<h2 class="h5 mb-3">{'Service bar'|t4l}</h2>
						<div class="row g-3">
							<div class="col-md-4">
								<label class="form-label">{'Item 1 title'|t4l}</label>
								<input type="text" class="form-control mb-2" data-t4-chrome-f="trust1_title" value="{$t4c.trust1_title|default:''|escape}">
								<label class="form-label">{'Item 1 text'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="trust1_text" value="{$t4c.trust1_text|default:''|escape}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Item 2 title'|t4l}</label>
								<input type="text" class="form-control mb-2" data-t4-chrome-f="trust2_title" value="{$t4c.trust2_title|default:''|escape}">
								<label class="form-label">{'Item 2 text'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="trust2_text" value="{$t4c.trust2_text|default:''|escape}">
							</div>
							<div class="col-md-4">
								<label class="form-label">{'Item 3 title'|t4l}</label>
								<input type="text" class="form-control mb-2" data-t4-chrome-f="trust3_title" value="{$t4c.trust3_title|default:''|escape}">
								<label class="form-label">{'Item 3 text'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="trust3_text" value="{$t4c.trust3_text|default:''|escape}">
							</div>
						</div>
					</div>
					<div data-t4-chrome-section="footer">
						<h2 class="h5 mb-3">{'Footer texts'|t4l}</h2>
						<div class="row g-3">
							<div class="col-12">
								<label class="form-label">{'About text'|t4l}</label>
								<textarea class="form-control" rows="2" data-t4-chrome-f="footer_brand_text">{$t4c.footer_brand_text|default:''|escape}</textarea>
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Menu title'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_menu_title" value="{$t4c.footer_menu_title|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Featured title'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_featured_title" value="{$t4c.footer_featured_title|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Corporate title'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_corporate_title" value="{$t4c.footer_corporate_title|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Customer service title'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_service_title" value="{$t4c.footer_service_title|default:''|escape}">
							</div>
							<div class="col-12">
								<label class="form-label">{'Customer service text'|t4l}</label>
								<textarea class="form-control" rows="2" data-t4-chrome-f="footer_service_text">{$t4c.footer_service_text|default:''|escape}</textarea>
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Newsletter title'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_news_title" value="{$t4c.footer_news_title|default:''|escape}">
							</div>
							<div class="col-md-6">
								<label class="form-label">{'Newsletter text'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_news_text" value="{$t4c.footer_news_text|default:''|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label">{'Customer Service'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_col_service" value="{$t4c.footer_col_service|default:''|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label">{'Company'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_col_company" value="{$t4c.footer_col_company|default:''|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label">{'My Account'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_col_account" value="{$t4c.footer_col_account|default:''|escape}">
							</div>
							<div class="col-md-3">
								<label class="form-label">{'Contact Us'|t4l}</label>
								<input type="text" class="form-control" data-t4-chrome-f="footer_col_contact" value="{$t4c.footer_col_contact|default:''|escape}">
							</div>
							<div class="col-12">
								<label class="form-label" for="t4CopyrightChrome">{'Copyright text'|t4l}</label>
								<input type="text" id="t4CopyrightChrome" class="form-control" data-t4-sync-copyright value="{$t4Copyright|escape}" placeholder="{literal}© {year} {site}. All rights reserved.{/literal}">
							</div>
						</div>
					</div>
				</div>
				<div class="t4-visual-source">

<div class="t4-studio">
<div class="t4-studio__editor">

<ul class="nav nav-tabs mb-3" role="tablist">
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'home'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabHome" role="tab">{'Homepage settings'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'header'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabHeader" role="tab">{'Header'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'footer'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabFooter" role="tab">{'Footer'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'theme'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabTheme" role="tab">{'Theme settings'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'colors'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabColors" role="tab">{'Colors'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'custom'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabCustom" role="tab">{'Custom CSS & JS'|t4l}</button>
	</li>
	<li class="nav-item" role="presentation">
		<button type="button" class="nav-link{if $t4ActiveTab == 'export'} active{/if}" data-bs-toggle="tab" data-bs-target="#t4TabExport" role="tab">{'Import / Export'|t4l}</button>
	</li>
</ul>

<div class="tab-content">

	{* ——— Homepage ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'home'} show active{/if}" id="t4TabHome" role="tabpanel">
		<div class="t4-builder" data-t4-builder="home" data-published-empty="{if $t4HomePublishedEmpty}1{else}0{/if}" data-hooks='{$t4HooksJson nofilter}' data-widgets='{$t4WidgetsHomeJson nofilter}'>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{'Homepage editor'|t4l}</h2>
					<p class="text-muted small mb-0">{'Add rows and widgets. Drag to reorder. Changes appear on the right as you edit, then save to publish.'|t4l}</p>
				</div>
				<div class="d-flex gap-2">
					<a href="{$theme4PreviewUrl|escape}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{'Preview'|t4l}</a>
					<button type="button" class="btn btn-sm btn-outline-dark" data-t4-add-row>+ {'Add row'|t4l}</button>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="saveTheme4Layout" value="1">
				<input type="hidden" name="tab" value="home">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="layout_json" data-layout-json value="">
				<script type="application/json" data-layout-boot>{$layoutJson nofilter}</script>
				<script type="application/json" data-layout-default>{$defaultHomeLayoutJson nofilter}</script>
				<div data-t4-rows class="t4-builder__rows"></div>
				<div class="mt-3 d-flex gap-2">
					<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
					<button type="button" class="btn btn-outline-secondary" data-t4-reset>{'Reset to default'|t4l}</button>
				</div>
			</form>
		</div>
	</div>

	{* ——— Header ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'header'} show active{/if}" id="t4TabHeader" role="tabpanel">
		<div class="t4-builder" data-t4-builder="header" data-published-empty="{if $t4HeaderPublishedEmpty}1{else}0{/if}" data-hooks='{$t4HooksJson nofilter}' data-widgets='{$t4WidgetsHeaderJson nofilter}'>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{'Header editor'|t4l}</h2>
					<p class="text-muted small mb-0">{'Build the header with logo, search, links, menu hook and tools. Same row/column options as the homepage.'|t4l}</p>
				</div>
				<div class="d-flex gap-2">
					<a href="{$theme4PreviewUrl|escape}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{'Preview'|t4l}</a>
					<button type="button" class="btn btn-sm btn-outline-dark" data-t4-add-row>+ {'Add row'|t4l}</button>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="saveTheme4Header" value="1">
				<input type="hidden" name="tab" value="header">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="layout_json" data-layout-json value="">
				<script type="application/json" data-layout-boot>{$headerLayoutJson nofilter}</script>
				<script type="application/json" data-layout-default>{$defaultHeaderLayoutJson nofilter}</script>
				<div data-t4-rows class="t4-builder__rows"></div>
				<div class="mt-3 d-flex gap-2">
					<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
					<button type="button" class="btn btn-outline-secondary" data-t4-reset>{'Reset to default'|t4l}</button>
				</div>
			</form>
		</div>
	</div>

	{* ——— Footer ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'footer'} show active{/if}" id="t4TabFooter" role="tabpanel">
		<div class="t4-builder" data-t4-builder="footer" data-published-empty="{if $t4FooterPublishedEmpty}1{else}0{/if}" data-hooks='{$t4HooksJson nofilter}' data-widgets='{$t4WidgetsFooterJson nofilter}'>
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
				<div>
					<h2 class="h5 mb-1">{'Footer editor'|t4l}</h2>
					<p class="text-muted small mb-0">{'Build footer columns with logo, text, links and the footer hook (e.g. social follow modules).'|t4l}</p>
				</div>
				<div class="d-flex gap-2">
					<a href="{$theme4PreviewUrl|escape}" class="btn btn-sm btn-outline-secondary" target="_blank" rel="noopener">{'Preview'|t4l}</a>
					<button type="button" class="btn btn-sm btn-outline-dark" data-t4-add-row>+ {'Add row'|t4l}</button>
				</div>
			</div>
			<form method="post">
				<input type="hidden" name="saveTheme4Footer" value="1">
				<input type="hidden" name="tab" value="footer">
				<input type="hidden" name="token" value="{$adminToken}">
				<input type="hidden" name="layout_json" data-layout-json value="">
				<script type="application/json" data-layout-boot>{$footerLayoutJson nofilter}</script>
				<script type="application/json" data-layout-default>{$defaultFooterLayoutJson nofilter}</script>
				<div data-t4-rows class="t4-builder__rows"></div>
				<div class="card border-0 shadow-sm mt-3">
					<div class="card-body py-3">
						<label class="form-label mb-1" for="t4CopyrightInput">{'Copyright text'|t4l}</label>
						<input type="text" name="copyright_text" id="t4CopyrightInput" class="form-control" value="{$t4Copyright|escape}" placeholder="{literal}© {year} {site}. All rights reserved.{/literal}">
						<div class="form-text">{'Shown at the bottom of the footer. Placeholders:'|t4l} {ldelim}year{rdelim}, {ldelim}site{rdelim}</div>
					</div>
				</div>
				<div class="mt-3 d-flex gap-2">
					<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
					<button type="button" class="btn btn-outline-secondary" data-t4-reset>{'Reset to default'|t4l}</button>
				</div>
			</form>
		</div>
	</div>

	{* ——— Theme settings ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'theme'} show active{/if}" id="t4TabTheme" role="tabpanel">
		<form method="post" class="card border-0 shadow-sm">
			<div class="card-body">
				<input type="hidden" name="saveTheme4Theme" value="1">
				<input type="hidden" name="tab" value="theme">
				<input type="hidden" name="token" value="{$adminToken}">

				<p class="text-muted small">{'Global theme options. Logo widgets fall back to the default logo when empty.'|t4l}</p>

				<div class="row g-3">
					<div class="col-md-6">
						<label class="form-label">{'Default logo'|t4l}</label>
						<div class="d-flex gap-2 flex-wrap align-items-start">
							<input type="text" name="logo" id="t4LogoInput" class="form-control" value="{$t4Theme.logo|escape}" placeholder="{'Select from media'|t4l}" readonly>
							<button type="button" class="btn btn-outline-dark text-nowrap" data-t4-media-pick data-input="#t4LogoInput" data-preview="#t4LogoPreview" data-confirm-label="{'Select logo'|t4l|escape}">{'Select logo'|t4l}</button>
						</div>
						{if $t4Theme.logo}
						<img src="{$t4Theme.logo|escape}" alt="" class="t4-banner-preview mt-2" id="t4LogoPreview">
						{else}
						<img src="" alt="" class="t4-banner-preview mt-2 d-none" id="t4LogoPreview">
						{/if}
					</div>
					<div class="col-md-6">
						<label class="form-label">{'Favicon'|t4l}</label>
						<div class="d-flex gap-2 flex-wrap align-items-start">
							<input type="text" name="favicon" id="t4FaviconInput" class="form-control" value="{$t4Theme.favicon|escape}" placeholder="{'Select from media'|t4l}" readonly>
							<button type="button" class="btn btn-outline-dark text-nowrap" data-t4-media-pick data-input="#t4FaviconInput" data-preview="#t4FaviconPreview" data-confirm-label="{'Select favicon'|t4l|escape}">{'Select favicon'|t4l}</button>
						</div>
						{if $t4Theme.favicon}
						<img src="{$t4Theme.favicon|escape}" alt="" class="t4-banner-preview mt-2" id="t4FaviconPreview" style="max-width:48px;max-height:48px">
						{else}
						<img src="" alt="" class="t4-banner-preview mt-2 d-none" id="t4FaviconPreview" style="max-width:48px;max-height:48px">
						{/if}
					</div>
				</div>
			</div>
			<div class="card-footer bg-white">
				<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
			</div>
		</form>
	</div>

	{* ——— Colors ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'colors'} show active{/if}" id="t4TabColors" role="tabpanel">
		<form method="post" class="card border-0 shadow-sm">
			<div class="card-body">
				<input type="hidden" name="saveTheme4Colors" value="1">
				<input type="hidden" name="tab" value="colors">
				<input type="hidden" name="token" value="{$adminToken}">

				<p class="text-muted small mb-3">{'These names map to :root variables in colors.css. Use them in style.css as var(--name).'|t4l}</p>

				{foreach $t4ColorGroups as $group}
				<div class="mb-4">
					<h3 class="h6 border-bottom pb-2">{$group.label|escape}</h3>
					<div class="row g-3">
						{foreach $group.fields as $field}
						<div class="col-md-6 col-lg-4">
							<label class="form-label small mb-1">{$field.label|escape}</label>
							<div class="input-group input-group-sm">
								<input type="color" class="form-control form-control-color" value="{$field.hex|escape}" data-t4-color-picker data-target="t4c_{$field.var|escape}" title="{$field.label|escape}">
								<span class="input-group-text font-monospace small">--{$field.var|escape}</span>
								<input type="text" name="colors[{$field.var|escape}]" id="t4c_{$field.var|escape}" class="form-control font-monospace" value="{$field.value|escape}">
							</div>
							<div class="form-text">{'CSS variable'|t4l}: <code>var(--{$field.var|escape})</code></div>
						</div>
						{/foreach}
					</div>
				</div>
				{/foreach}
			</div>
			<div class="card-footer bg-white">
				<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
			</div>
		</form>
	</div>

	{* ——— Custom: width, font, CSS / JS ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'custom'} show active{/if}" id="t4TabCustom" role="tabpanel">
		<form method="post" class="card border-0 shadow-sm">
			<div class="card-body">
				<input type="hidden" name="saveTheme4Custom" value="1">
				<input type="hidden" name="tab" value="custom">
				<input type="hidden" name="token" value="{$adminToken}">

				<div class="row g-3 mb-4">
					<div class="col-md-6">
						<label class="form-label" for="t4SiteWidth">{'Site width'|t4l}</label>
						<select name="site_width" id="t4SiteWidth" class="form-select">
							{foreach $t4SiteWidthOptions as $opt}
							<option value="{$opt.value|escape}"{if $t4Theme.site_width == $opt.value} selected{/if}>{$opt.label|escape}</option>
							{/foreach}
							{if !$t4SiteWidthMatched}
							<option value="{$t4Theme.site_width|escape}" selected>{$t4Theme.site_width|escape}</option>
							{/if}
						</select>
						<div class="form-text">{'Max container width'|t4l}</div>
					</div>
					<div class="col-md-6">
						<label class="form-label" for="t4FontFamily">{'Font family'|t4l}</label>
						<select name="font_family" id="t4FontFamily" class="form-select">
							{foreach $t4FontOptions as $opt}
							<option value="{$opt.value|escape}"{if $t4Theme.font_family == $opt.value} selected{/if}>{$opt.label|escape}</option>
							{/foreach}
							{if !$t4FontMatched}
							<option value="{$t4Theme.font_family|escape}" selected>{$t4Theme.font_family|escape}</option>
							{/if}
						</select>
						<div class="form-text">{'Storefront body font'|t4l}</div>
					</div>
				</div>

				<div class="mb-3">
					<label class="form-label">{'Custom CSS'|t4l}</label>
					<textarea name="custom_css" class="form-control font-monospace" rows="12" spellcheck="false">{$t4CustomCss|escape}</textarea>
					<div class="form-text">{'Written to templates/theme4/css/custom.css'|t4l}</div>
				</div>
				<div class="mb-0">
					<label class="form-label">{'Custom JS'|t4l}</label>
					<textarea name="custom_js" class="form-control font-monospace" rows="12" spellcheck="false">{$t4CustomJs|escape}</textarea>
					<div class="form-text">{'Written to templates/theme4/js/custom.js'|t4l}</div>
				</div>
			</div>
			<div class="card-footer bg-white">
				<button type="submit" class="btn btn-dark">{'Save'|t4l}</button>
			</div>
		</form>
	</div>

	{* ——— Import / Export ——— *}
	<div class="tab-pane fade{if $t4ActiveTab == 'export'} show active{/if}" id="t4TabExport" role="tabpanel">
		{*<div class="alert alert-light border mb-3">
			<div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
				<div>
					<strong>{'Premium License Store'|t4l}</strong>
					<div class="small text-muted mb-0">{'One-click premium look: navy palette, refined header/footer, banner cards and typography. Uses your media under img/media/.'|t4l}</div>
				</div>
				<form method="post" class="m-0">
					<input type="hidden" name="applyTheme4Premium" value="1">
					<input type="hidden" name="tab" value="export">
					<input type="hidden" name="token" value="{$adminToken}">
					<button type="submit" class="btn btn-primary js-admin-confirm js-admin-busy" data-confirm-message="{'Apply the Premium preset and overwrite current Theme4 settings?'|t4l|escape}">{'Apply Premium preset'|t4l}</button>
				</form>
			</div>
		</div>*}
		<div class="row g-3">
			<div class="col-lg-6">
				<form method="post" class="card border-0 shadow-sm h-100">
					<div class="card-body">
						<input type="hidden" name="exportTheme4" value="1">
						<input type="hidden" name="tab" value="export">
						<input type="hidden" name="token" value="{$adminToken}">
						<h2 class="h5">{'Export'|t4l}</h2>
						<p class="text-muted small">{'Download a JSON file with homepage, header, footer layouts, theme settings, colors and custom CSS/JS. Share it with another store running Theme4.'|t4l}</p>
						<ul class="small text-muted mb-0">
							<li>{'Layouts'|t4l} (home / header / footer)</li>
							<li>{'Theme settings'|t4l} + {'Copyright text'|t4l}</li>
							<li>{'Colors'|t4l}</li>
							<li>{'Custom CSS & JS'|t4l}</li>
						</ul>
					</div>
					<div class="card-footer bg-white">
						<button type="submit" class="btn btn-dark">{'Download JSON'|t4l}</button>
					</div>
				</form>
			</div>
			<div class="col-lg-6">
				<form method="post" enctype="multipart/form-data" class="card border-0 shadow-sm h-100">
					<div class="card-body">
						<input type="hidden" name="importTheme4" value="1">
						<input type="hidden" name="tab" value="export">
						<input type="hidden" name="token" value="{$adminToken}">
						<h2 class="h5">{'Import'|t4l}</h2>
						<p class="text-muted small">{'Upload a Theme4 JSON export or paste it below. Media paths and category IDs may need updating on the target shop.'|t4l}</p>

						<div class="mb-3">
							<label class="form-label">{'JSON file'|t4l}</label>
							<input type="file" name="import_file" class="form-control" accept=".json,application/json,text/plain">
						</div>
						<div class="mb-3">
							<label class="form-label">{'Or paste JSON'|t4l}</label>
							<textarea name="import_json" class="form-control font-monospace" rows="8" spellcheck="false" placeholder="{literal}{ &quot;format&quot;: &quot;fshop-theme4&quot;, ... }{/literal}"></textarea>
						</div>
						<div class="mb-0">
							<div class="fw-semibold small mb-2">{'Import sections'|t4l}</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_layouts" id="t4ImpLayouts" value="1" checked>
								<label class="form-check-label" for="t4ImpLayouts">{'Layouts'|t4l} (home / header / footer)</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_theme" id="t4ImpTheme" value="1" checked>
								<label class="form-check-label" for="t4ImpTheme">{'Theme settings'|t4l} + {'Copyright text'|t4l}</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_colors" id="t4ImpColors" value="1" checked>
								<label class="form-check-label" for="t4ImpColors">{'Colors'|t4l}</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" name="import_custom" id="t4ImpCustom" value="1" checked>
								<label class="form-check-label" for="t4ImpCustom">{'Custom CSS & JS'|t4l}</label>
							</div>
						</div>
					</div>
					<div class="card-footer bg-white">
						<button type="submit" class="btn btn-dark js-admin-confirm js-admin-busy" data-confirm-message="{'Overwrite selected settings with the import file?'|t4l|escape}">{'Import'|t4l}</button>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>

</div>
</div>
</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|t4l}</button>
				<button type="button" class="btn btn-sm btn-primary" data-t4-modal-save>{'Save'|t4l}</button>
			</div>
		</div>
	</div>
</div>

<script type="application/json" id="t4CategoriesJson">{$categoryOptionsJson nofilter}</script>
<script type="application/json" id="t4ColWidthsJson">{$colWidthOptionsJson nofilter}</script>
<script type="application/json" id="t4LinkOptionsJson">{$t4LinkOptionsJson nofilter}</script>
<script type="application/json" id="t4I18n">{$t4I18nJson nofilter}</script>

<template id="t4TplRow">
	<div class="t4-row card mb-3" data-row>
		<div class="card-header d-flex justify-content-between align-items-center py-2">
			<div class="d-flex align-items-center gap-2">
				<span class="t4-drag-handle" data-row-handle title="{'Drag'|t4l}" aria-hidden="true">⠿</span>
				<strong class="small">{'Row'|t4l}</strong>
				<span class="badge text-bg-secondary d-none" data-hide-badge>{'Hidden'|t4l}</span>
				<button type="button" class="btn btn-sm btn-link text-secondary p-0 t4-settings-btn" data-row-settings title="{'Settings'|t4l}" aria-label="{'Settings'|t4l}">
					<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.901 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.115 2.693l.319.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.115l-.094.319c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.693-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.693-1.115l.094-.319z"/></svg>
				</button>
			</div>
			<div class="d-flex gap-1">
				<button type="button" class="btn btn-sm btn-outline-primary" data-add-col>+ {'Add column'|t4l}</button>
				<button type="button" class="btn btn-sm btn-outline-danger" data-del-row>{'Delete'|t4l}</button>
			</div>
		</div>
		<div class="card-body">
			<div class="row g-2" data-cols></div>
		</div>
	</div>
</template>

<template id="t4TplCol">
	<div class="col-12 col-md-6 col-lg-6" data-col>
		<div class="t4-col border rounded p-2 h-100 bg-light">
			<div class="d-flex justify-content-between align-items-center mb-2 gap-2">
				<div class="d-flex align-items-center gap-2">
					<span class="t4-drag-handle" data-col-handle title="{'Drag'|t4l}" aria-hidden="true">⠿</span>
					<strong class="small">{'Column'|t4l}</strong>
					<span class="badge text-bg-secondary d-none" data-hide-badge>{'Hidden'|t4l}</span>
					<button type="button" class="btn btn-sm btn-link text-secondary p-0 t4-settings-btn" data-col-settings title="{'Settings'|t4l}" aria-label="{'Settings'|t4l}">
						<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.901 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.115 2.693l.319.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.115l-.094.319c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.693-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.377l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.693-1.115l.094-.319z"/></svg>
					</button>
				</div>
				<button type="button" class="btn btn-sm btn-outline-danger text-nowrap" data-del-col>{'Delete column'|t4l}</button>
			</div>
			<div class="vstack gap-2" data-widgets></div>
			<button type="button" class="btn btn-sm btn-dark mt-2" data-add-widget>+ {'Add widget'|t4l}</button>
		</div>
	</div>
</template>

<div class="modal fade" id="t4BlockSettingsModal" tabindex="-1" aria-hidden="true">
	<div class="modal-dialog">
		<div class="modal-content">
			<div class="modal-header py-2">
				<h5 class="modal-title" id="t4SetTitle">{'Settings'|t4l}</h5>
				<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{'Cancel'|t4l}"></button>
			</div>
			<div class="modal-body">
				<div class="mb-3">
					<label class="form-label" for="t4SetId">{'ID'|t4l}</label>
					<input type="text" class="form-control form-control-sm font-monospace" id="t4SetId" autocomplete="off" placeholder="hero">
					<div class="form-text">{'Letters, numbers, hyphen and underscore. Optional.'|t4l}</div>
				</div>
				<div class="mb-3">
					<label class="form-label" for="t4SetClass">{'CSS class'|t4l}</label>
					<input type="text" class="form-control form-control-sm font-monospace" id="t4SetClass" autocomplete="off" placeholder="my-section dark-bg">
					<div class="form-text">{'Space-separated class names'|t4l}</div>
				</div>
				<div id="t4SetWidths" class="mb-3 d-none">
					<div class="fw-semibold small mb-2">{'Widths'|t4l}</div>
					<div class="row g-2">
						<div class="col-4">
							<label class="form-label small mb-1" for="t4SetWMobile">{'Mobile'|t4l} <span class="text-muted font-monospace">col-*</span></label>
							<select class="form-select form-select-sm" id="t4SetWMobile"></select>
						</div>
						<div class="col-4">
							<label class="form-label small mb-1" for="t4SetWTablet">{'Tablet'|t4l} <span class="text-muted font-monospace">col-md-*</span></label>
							<select class="form-select form-select-sm" id="t4SetWTablet"></select>
						</div>
						<div class="col-4">
							<label class="form-label small mb-1" for="t4SetWDesktop">{'Desktop'|t4l} <span class="text-muted font-monospace">col-lg-*</span></label>
							<select class="form-select form-select-sm" id="t4SetWDesktop"></select>
						</div>
					</div>
				</div>
				<div>
					<div class="fw-semibold small mb-2">{'Visibility'|t4l}</div>
					<div class="d-flex flex-column gap-2">
						<div class="d-flex align-items-center justify-content-between gap-3 t4-switch-row">
							<label class="mb-0" for="t4SetHideMobile">{'Hide on mobile'|t4l}</label>
							<div class="d-flex align-items-center gap-2">
								<span class="small text-muted" data-switch-no>{'No'|t4l}</span>
								<div class="form-check form-switch m-0">
									<input class="form-check-input" type="checkbox" role="switch" id="t4SetHideMobile">
								</div>
								<span class="small text-muted" data-switch-yes>{'Yes'|t4l}</span>
							</div>
						</div>
						<div class="d-flex align-items-center justify-content-between gap-3 t4-switch-row">
							<label class="mb-0" for="t4SetHideTablet">{'Hide on tablet'|t4l}</label>
							<div class="d-flex align-items-center gap-2">
								<span class="small text-muted" data-switch-no>{'No'|t4l}</span>
								<div class="form-check form-switch m-0">
									<input class="form-check-input" type="checkbox" role="switch" id="t4SetHideTablet">
								</div>
								<span class="small text-muted" data-switch-yes>{'Yes'|t4l}</span>
							</div>
						</div>
						<div class="d-flex align-items-center justify-content-between gap-3 t4-switch-row">
							<label class="mb-0" for="t4SetHideDesktop">{'Hide on desktop'|t4l}</label>
							<div class="d-flex align-items-center gap-2">
								<span class="small text-muted" data-switch-no>{'No'|t4l}</span>
								<div class="form-check form-switch m-0">
									<input class="form-check-input" type="checkbox" role="switch" id="t4SetHideDesktop">
								</div>
								<span class="small text-muted" data-switch-yes>{'Yes'|t4l}</span>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="modal-footer py-2">
				<button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">{'Cancel'|t4l}</button>
				<button type="button" class="btn btn-sm btn-dark" id="t4SetApply">{'Apply'|t4l}</button>
			</div>
		</div>
	</div>
</div>

<template id="t4TplWidget">
	<div class="t4-widget-card border rounded bg-white p-2" data-widget>
		<div class="d-flex justify-content-between align-items-center mb-2 gap-2">
			<div class="d-flex align-items-center gap-2 flex-grow-1">
				<span class="t4-drag-handle" data-widget-handle title="{'Drag'|t4l}" aria-hidden="true">⠿</span>
				<select class="form-select form-select-sm" data-widget-type style="max-width:220px"></select>
			</div>
			<button type="button" class="btn btn-sm btn-outline-danger" data-del-widget>{'Delete'|t4l}</button>
		</div>
		<div data-widget-fields></div>
	</div>
</template>

{include file='admin/partials/media-library-modal.tpl'}
<script src="{$domain}templates/admin/js/media-picker.js?v={$smarty.now}"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
(function () {
	document.querySelectorAll('[data-t4-color-picker]').forEach(function (picker) {
		var targetId = picker.getAttribute('data-target');
		var input = targetId ? document.getElementById(targetId) : null;
		if (!input) return;
		picker.addEventListener('input', function () {
			input.value = picker.value;
			if (window.t4ApplyPreviewColors) {
				window.t4ApplyPreviewColors();
			}
		});
		input.addEventListener('change', function () {
			if (/^#[0-9a-fA-F]{6}$/.test(input.value)) {
				picker.value = input.value;
			}
		});
	});

	document.querySelectorAll('[data-t4-media-pick]').forEach(function (btn) {
		btn.addEventListener('click', function (e) {
			e.preventDefault();
			if (!window.FShopMediaPicker || !FShopMediaPicker.available) return;
			var inputSel = btn.getAttribute('data-input');
			var previewSel = btn.getAttribute('data-preview');
			FShopMediaPicker.open({
				multi: false,
				confirmLabel: btn.getAttribute('data-confirm-label') || 'Select',
				onSelect: function (items) {
					if (!items || !items.length) return;
					var url = items[0].url || '';
					var input = inputSel ? document.querySelector(inputSel) : null;
					var preview = previewSel ? document.querySelector(previewSel) : null;
					if (input) input.value = url;
					if (preview) {
						if (url) {
							preview.src = url;
							preview.classList.remove('d-none');
						} else {
							preview.removeAttribute('src');
							preview.classList.add('d-none');
						}
					}
				}
			});
		});
	});
})();
</script>
