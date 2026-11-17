{*
 * Works on both theme families:
 *  - Classic (PS 1.7/8): a tile, <a class="col-lg-4 col-md-6 col-sm-6 col-xs-12"> with <span class="link-item">.
 *  - Hummingbird (PS 9): account-menu markup.
 * The theme is told apart by the template it ships for the account menu.
*}
{if isset($bws_is_hummingbird) && $bws_is_hummingbird}
<a class="account-menu__link bws-account-link{if $bws_is_current} account-menu__link--active{/if}" id="my-subscriptions_link" href="{$my_subscriptions_page|escape:'html':'UTF-8'}"{if $bws_is_current} aria-current="page"{/if}>
  <i class="account-menu__icon material-icons" aria-hidden="true">&#xE042;</i>
  {l s='My Subscriptions' mod='billwerksubscription'}
</a>
{else}
<a class="col-lg-4 col-md-6 col-sm-6 col-xs-12" id="my-subscriptions_link" href="{$my_subscriptions_page|escape:'html':'UTF-8'}">
  <span class="link-item">
    <i class="material-icons" aria-hidden="true">&#xE042;</i>
    {l s='My Subscriptions' mod='billwerksubscription'}
  </span>
</a>
{/if}
