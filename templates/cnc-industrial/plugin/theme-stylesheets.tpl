{if $themeCssBundleUrl}
<link rel="stylesheet" href="{$themeCssBundleUrl|escape}" />
{else}
<link rel="stylesheet" href="{($css_dir|cat:'colors.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'custom.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'style.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'pages.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'notifications.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'cart-modal.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'shopmore.css')|asset_url}">
{if $css}<link rel="stylesheet" href="{($css_dir|cat:$css)|asset_url}">{/if}
{if $themeOptions.header == 'header2'}<link rel="stylesheet" href="{($css_dir|cat:'header2.css')|asset_url}">{/if}
{/if}
{* Bundled builds need fresh editable colors after the cached CSS bundle.
   Unbundled builds already loaded these CSS files above. *}
{if $themeCssBundleUrl}
<link rel="stylesheet" href="{($css_dir|cat:'colors.css')|asset_url}">
<link rel="stylesheet" href="{($css_dir|cat:'custom.css')|asset_url}">
{/if}
<link rel="stylesheet" href="{($css_dir|cat:'cnc-industrial.css')|asset_url}">