<div class="sm-container cnc-home">
  <section class="cnc-hero" aria-labelledby="cncHeroTitle">
    <div class="cnc-hero__text">
      <div class="cnc-hero__index"><span class="cnc-hero__dash"></span> CNC / TAKIMLAMA / ÖLÇÜM</div>
      <h1 id="cncHeroTitle">Üretimin ritmini<br><em>doğru ekipman</em> belirler.</h1>
      <p>Kesici takımlar, takım tutucular ve hassas ölçüm ekipmanları. Aradığınız ürüne teknik özellikleriyle ulaşın.</p>
      <div class="cnc-hero__buttons">
        <a class="cnc-btn cnc-btn--primary" href="{$domain}catalog">Ürünleri keşfet <span aria-hidden="true">↗</span></a>
        <a class="cnc-btn cnc-btn--ghost" href="{$domain}contact">Bize ulaşın <span aria-hidden="true">→</span></a>
      </div>
      <div class="cnc-hero__foot"><span>01 / 03</span><span class="cnc-hero__line"></span><span>ENDÜSTRİYEL ÇÖZÜMLER</span></div>
    </div>
    <div class="cnc-hero__visual" aria-hidden="true">
      <div class="cnc-blueprint__grid"></div>
      <span class="cnc-blueprint__caption">TOOL HOLDER / SECTION A-A</span>
      <span class="cnc-blueprint__marker cnc-blueprint__marker--one">Ø 40</span>
      <span class="cnc-blueprint__marker cnc-blueprint__marker--two">AXIS Z</span>
      <svg class="cnc-blueprint__art" viewBox="0 0 580 420" fill="none" xmlns="http://www.w3.org/2000/svg">
        <circle cx="295" cy="207" r="171" stroke="#94A3B8" stroke-opacity=".19"/>
        <circle cx="295" cy="207" r="136" stroke="#94A3B8" stroke-opacity=".13" stroke-dasharray="4 9"/>
        <path d="M295 15v384M58 207h475" stroke="#94A3B8" stroke-opacity=".18" stroke-dasharray="6 7"/>
        <g transform="translate(187 31) rotate(17 110 172)">
          <path d="M86 26h48l10 60-10 26v40l29 21v27h-29v50l-16 25v74H102v-74l-16-25v-50H57v-27l29-21v-40L76 86z" fill="#263443" stroke="#708095" stroke-width="2"/>
          <path d="M94 35h32l7 49H87z" fill="#354759"/>
          <path d="M86 153h48l27 20H59z" fill="#40566B"/>
          <path d="M63 186h94" stroke="#A3CB64" stroke-width="7"/>
          <path d="M98 200h24v55h-24z" fill="#60758A"/>
          <path d="M106 270h8v80h-8z" fill="#8FA6B9"/>
          <path d="M84 112h52M75 221h70M101 257h18M102 286h16" stroke="#a5bacb" stroke-opacity=".45"/>
          <path d="M101 62h18" stroke="#A3CB64" stroke-width="3"/>
        </g>
        <path d="M105 69H38v94M457 338h82v-94" stroke="#A3CB64" stroke-opacity=".6"/>
        <circle cx="105" cy="69" r="3" fill="#A3CB64"/>
        <circle cx="457" cy="338" r="3" fill="#A3CB64"/>
        <path d="M45 365h116M45 358v14M161 358v14" stroke="#94A3B8" stroke-opacity=".5"/>
        <text x="103" y="382" text-anchor="middle" fill="#94A3B8" font-family="monospace" font-size="11">REF. BT40</text>
      </svg>
      <span class="cnc-blueprint__serial">FIG — 001 <br> PRECISION COMPONENT</span>
    </div>
  </section>

  <div class="cnc-trust-strip" aria-label="Mağaza özellikleri">
    <div><span class="cnc-trust-strip__icon">01</span><strong>Teknik odaklı katalog</strong><small>Ölçü ve özelliklerle seçim</small></div>
    <div><span class="cnc-trust-strip__icon">02</span><strong>Profesyonel ekipmanlar</strong><small>CNC ve talaşlı imalat için</small></div>
    <div><span class="cnc-trust-strip__icon">03</span><strong>Kolay sipariş takibi</strong><small>Sipariş durumunu görüntüleyin</small></div>
  </div>

  {if $homeCategories|@count}
  <section class="cnc-categories" aria-labelledby="cncCategoriesTitle">
    <div class="cnc-section-heading">
      <div><span class="cnc-kicker">01 / ÜRÜN GRUPLARI</span><h2 id="cncCategoriesTitle">Kategoriler</h2></div>
      <a href="{$domain}catalog" class="cnc-text-link">Tüm ürünler <span aria-hidden="true">↗</span></a>
    </div>
    <div class="cnc-categories__grid">
      {foreach $homeCategories as $catRow name=cncCats}
        {if $smarty.foreach.cncCats.iteration > 6}{break}{/if}
        <a class="cnc-category-tile" href="{$catRow.url|escape}">
          <span class="cnc-category-tile__serial">0{$smarty.foreach.cncCats.iteration}</span>
          <span class="cnc-category-tile__icon" aria-hidden="true"><svg width="44" height="44" viewBox="0 0 48 48" fill="none" stroke="currentColor" stroke-width="1.3"><rect x="10" y="10" width="28" height="28" rx="3"/><circle cx="24" cy="24" r="8"/><path d="M24 2v8M24 38v8M2 24h8M38 24h8M7 7l6 6M35 35l6 6"/></svg></span>
          <strong>{$catRow.category.category_name|escape}</strong><span class="cnc-category-tile__arrow" aria-hidden="true">↗</span>
        </a>
      {/foreach}
    </div>
  </section>
  {/if}

  {if $featuredProducts|@count}
  <section class="cnc-products" aria-labelledby="cncFeaturedTitle">
    <div class="cnc-section-heading">
      <div><span class="cnc-kicker">02 / ÖNE ÇIKAN SEÇİMLER</span><h2 id="cncFeaturedTitle">Öne çıkan ürünler</h2></div>
      <a href="{$domain}catalog" class="cnc-text-link">Tüm ürünleri gör <span aria-hidden="true">↗</span></a>
    </div>
    {include file='./productList.tpl' products=$featuredProducts id='cnc-featured'}
  </section>
  {/if}
  {if $dealProducts|@count}
  <section class="cnc-products" aria-labelledby="cncDealsTitle">
    <div class="cnc-section-heading">
      <div><span class="cnc-kicker">03 / AVANTAJLI ÜRÜNLER</span><h2 id="cncDealsTitle">Fırsat ürünleri</h2></div>
      <a href="{$domain}special" class="cnc-text-link">Fırsatları incele <span aria-hidden="true">↗</span></a>
    </div>
    {include file='./productList.tpl' products=$dealProducts id='cnc-deals'}
  </section>
  {/if}
  {if $hooks.home_slider}
  <section class="cnc-home-hooks">{$hooks.home_slider nofilter}</section>
  {/if}
  {if $hooks.home_promo_slider}
  <section class="cnc-home-hooks">{$hooks.home_promo_slider nofilter}</section>
  {/if}
  {foreach $categoryBlocks as $block name=cncBlocks}
    {if $smarty.foreach.cncBlocks.iteration > 2}{break}{/if}
    <section class="cnc-products">
      <div class="cnc-section-heading">
        <div><span class="cnc-kicker">SEÇİLİ KATEGORİLER</span><h2>{$block.category.category_name|escape}</h2></div>
        <a class="cnc-text-link" href="{$block.url|escape}">Kategoriyi gör <span aria-hidden="true">↗</span></a>
      </div>
      {include file='./productList.tpl' products=$block.products id=$block.category.id_category}
    </section>
  {/foreach}
  {if $featuredProducts|@count == 0 && $dealProducts|@count == 0 && $homeCategories|@count == 0 && $categoryBlocks|@count == 0}
    <div class="cnc-empty-shop"><span>YENİ KOLEKSİYON</span><h2>Ürün kataloğumuz hazırlanıyor.</h2><p>Teknik ürün gruplarımız yakında burada olacak.</p><a href="{$domain}contact">İletişime geçin ↗</a></div>
  {/if}
  <section class="cnc-help-banner">
    <div><span class="cnc-kicker">DOĞRU EKİPMANI BULUN</span><h2>Aradığınız ürün kataloğumuzda yok mu?</h2><p>Ürün kodunu veya teknik ihtiyacınızı bize iletin.</p></div>
    <a href="{$domain}contact">İletişime geçin <span aria-hidden="true">↗</span></a>
  </section>
  {if $hooks.home_bottom}<div class="cnc-home-hooks">{$hooks.home_bottom nofilter}</div>{/if}
</div>