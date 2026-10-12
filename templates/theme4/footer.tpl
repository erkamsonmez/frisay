{assign var=t4IsAuthScreen value=(isset($authMode) && ($authMode == 'gate' || $authMode == 'login' || $authMode == 'register' || $authMode == 'forgot' || $authMode == 'reset'))}
{if !$t4IsAuthScreen}
{if $pageName != 'home'}</div>{/if}
</section>

{include file='./plugin/schema-jsonld.tpl'}
{include file='./_mini/footer1.tpl'}
{include file='./plugin/cart.tpl'}
{include file='./plugin/auth-modal.tpl'}
{/if}
<script type="text/javascript" src="{($js_dir|cat:'jquery-3.2.1.min.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'bootstrap.bundle.min.js')|asset_url}"></script>
<script type="text/javascript" src="{($js_dir|cat:'style.js')|asset_url}"></script>
{if !$t4IsAuthScreen}
<script type="text/javascript" src="{($js_dir|cat:'shopmore.js')|asset_url}"></script>
{/if}
{if $recaptchaConfigJson}
<script>window.fshopRecaptcha={$recaptchaConfigJson nofilter};</script>
{/if}
{if !$t4IsAuthScreen}
<script src="https://cdn.onesignal.com/sdks/web/v16/OneSignalSDK.page.js" defer></script>
{/if}
{foreach $moduleAssets.js as $moduleJs}
<script src="{$moduleJs}"></script>
{/foreach}
{if $js}
	<script src="{($js_dir|cat:$js)|asset_url}"></script>
{/if}
{if $isLoggedIn && !$t4IsAuthScreen}
<script src="{($js_dir|cat:'notifications.js')|asset_url}"></script>
{/if}
{if !$isLoggedIn}
<script src="{($js_dir|cat:'auth-pages.js')|asset_url}"></script>
{if !$t4IsAuthScreen}
<script src="{($js_dir|cat:'auth-modal.js')|asset_url}"></script>
{/if}
{/if}
{if !$t4IsAuthScreen}
<script src="{($js_dir|cat:'custom.js')|asset_url}"></script>
{include file='./_mini/priceAllert.tpl'}
{/if}
<div id="tostAlert" class="toast align-items-center border-0" role="alert" aria-live="assertive" aria-atomic="true">
  <div class="d-flex">
	<div class="toast-body">
	  
	</div>
	<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
  </div>
</div>
</body>
</html>
