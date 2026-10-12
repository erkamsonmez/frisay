{if $themeCssBundleUrl}
<link rel="stylesheet" href="{$themeCssBundleUrl|escape}" />
{else}
<link rel="stylesheet" href="{($css_dir|cat:'colors.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'custom.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'style.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'pages.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'account-orders.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'account-hub.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'auth.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'notifications.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'cart-modal.css')|asset_url}" />
<link rel="stylesheet" href="{($css_dir|cat:'shopmore.css')|asset_url}" />
{if $css}
<link rel="stylesheet" href="{($css_dir|cat:$css)|asset_url}" />
{/if}
{if $themeOptions.header == 'header2'}
<link rel="stylesheet" href="{($css_dir|cat:'header2.css')|asset_url}" />
{/if}
{/if}
{if !$themeOptions.google_font_url|default:''}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
{if $t4FontGoogle|default:'' != ''}
<link href="{$t4FontGoogle|escape}" rel="stylesheet">
{/if}
{/if}
