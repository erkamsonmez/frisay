<header class="sm-header cnc-header{if $themeOptions.header|default:'header1' == 'header2'} cnc-header--compact{/if}">
  <div class="cnc-utility">
    <div class="sm-container cnc-utility__inner">
      <span class="cnc-utility__eyebrow"><span class="cnc-pulse"></span> {$themeOptions.announcement|escape}</span>
      <div class="cnc-utility__links">
        <a href="{$domain}contact">İletişim</a><span aria-hidden="true">/</span>
        <a href="{$domain}truck">Sipariş takibi</a>
      </div>
    </div>
  </div>
  <div class="sm-container">
    <div class="sm-header-desktop">
      <div class="sm-header-desktop__top cnc-header__main">
        <a href="{$domain}" class="cnc-brand" aria-label="{$siteName|escape} ana sayfa">
          <span class="cnc-brand__symbol" aria-hidden="true"><svg viewBox="0 0 44 44" fill="none"><path d="M8 8h28v28H8z" stroke="currentColor" stroke-width="1.5"/><path d="M14 30V14h16M14 24h11M30 14v16" stroke="currentColor" stroke-width="3"/><circle cx="30" cy="30" r="3" fill="currentColor"/></svg></span>
          {if $themeOptions.brand_logo_show|default:"0" == "1" && $siteLogos.header}<img class="cnc-brand__uploaded-logo" src="{$siteLogos.header|escape}" alt="{$siteName|escape}">{else}<span class="cnc-brand__wordmark">{$siteName|escape}<small>{$themeOptions.tagline|escape}</small></span>{/if}
        </a>
        <div class="sm-search-desktop cnc-header__search">
          {include file='cnc-industrial/plugin/header-search.tpl' smSearchInputId='cncSearchDesktop'}
          <span class="cnc-header__search-note">{$themeOptions.header_search_hint|escape}</span>
        </div>
        <div class="sm-header__actions cnc-header__actions">
          {if $isLoggedIn}
          <div class="sm-header__action-slot">{include file='cnc-industrial/plugin/notifications-dropdown.tpl'}</div>
          {/if}
          <a href="{$domain}{if $isLoggedIn}my-account{else}login{/if}" class="cnc-account-link">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
            <span>Hesabım</span>
          </a>
          <a href="{$domain}cart" class="cnc-cart-link" aria-label="Sepete git">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 4h2l2.2 11h11.6l2-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
            <span>Sepetim</span>
            <span class="cnc-cart-link__count sm-cart-count-badge{if $cart.count|default:0 == 0} d-none{/if}">{$cart.count|default:0}</span>
          </a>
        </div>
      </div>
    </div>
    <div class="sm-header-mobile cnc-header-mobile">
      <div class="sm-header__row">
        <button type="button" class="sm-icon-btn" data-bs-toggle="offcanvas" data-bs-target="#primeMobileMenu" aria-label="Menüyü aç">
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>
        <a href="{$domain}" class="cnc-brand cnc-brand--mobile" aria-label="{$siteName|escape} ana sayfa">
          <span class="cnc-brand__symbol" aria-hidden="true"><svg viewBox="0 0 44 44" fill="none"><path d="M8 8h28v28H8z" stroke="currentColor" stroke-width="1.5"/><path d="M14 30V14h16M14 24h11M30 14v16" stroke="currentColor" stroke-width="3"/><circle cx="30" cy="30" r="3" fill="currentColor"/></svg></span>
          {if $themeOptions.brand_logo_show|default:"0" == "1" && $siteLogos.header}<img class="cnc-brand__uploaded-logo" src="{$siteLogos.header|escape}" alt="{$siteName|escape}">{else}<span class="cnc-brand__wordmark">{$siteName|escape}</span>{/if}
        </a>
        <div class="sm-header__actions">
          <a class="sm-icon-btn" href="{$domain}my-account" aria-label="Hesabım">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><circle cx="12" cy="8" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/></svg>
          </a>
          <a class="sm-icon-btn cnc-mobile-cart" href="{$domain}cart" aria-label="Sepetim">
            <svg width="21" height="21" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><path d="M3 4h2l2.2 11h11.6l2-8H6"/><circle cx="9" cy="20" r="1"/><circle cx="18" cy="20" r="1"/></svg>
            <span class="sm-icon-btn__badge sm-cart-count-badge{if $cart.count|default:0 == 0} d-none{/if}">{$cart.count|default:0}</span>
          </a>
        </div>
      </div>
      <div class="sm-search-wrap">
        {include file='cnc-industrial/plugin/header-search.tpl' smSearchInputId='cncSearchMobile'}
      </div>
    </div>
    <nav class="sm-header-menu cnc-header-nav" aria-label="Ürün kategorileri">
      <ul class="sm-header-menu__list">
        <li><a href="{$domain}catalog" class="cnc-nav-all"><span aria-hidden="true">☷</span> TÜM ÜRÜNLER</a></li>
        {if $hooks.main_menu}
          {$hooks.main_menu nofilter}
        {else}
          {foreach $menuCategories as $cat}
            {if $cat.id_parent == 1}
              <li><a class="sm-header-menu__link" href="{$domain}{$cat.category_link|escape}">{$cat.category_name|escape}</a></li>
            {/if}
          {/foreach}
        {/if}
        <li class="cnc-nav-contact"><a href="{$domain}contact">{$themeOptions.header_support_label|escape} <span aria-hidden="true">↗</span></a></li>
      </ul>
    </nav>
  </div>
</header>