<a class="account-menu__link bws-account-link{if $bws_is_current} account-menu__link--active{/if}" id="my-subscriptions_link" href="{$my_subscriptions_page|escape:'html':'UTF-8'}"{if $bws_is_current} aria-current="page"{/if}>
  <i class="account-menu__icon material-icons" aria-hidden="true">&#xE042;</i>
  {l s='My Subscriptions' mod='billwerksubscription'}
</a>
