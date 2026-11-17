{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='My Subscriptions' mod='billwerksubscription'}
{/block}

{block name='page_content'}
  {if $subscriptions && count($subscriptions)}
    <div class="bws-list">
      {foreach from=$subscriptions item=row}
        {assign var=sub value=$row['subscription']}
        <div class="bws-card">
          <div class="bws-card__head">
            <a class="bws-card__handle" href="{$sub['subscription_link']|escape:'html':'UTF-8'}">{$sub['sub_handle']|escape:'html':'UTF-8'}</a>
            <span class="bws-badge bws-badge--{$sub['state']|escape:'html':'UTF-8'}">{$sub['state_name']|escape:'html':'UTF-8'}</span>
          </div>
          <dl class="bws-card__facts">
            <div><dt>{l s='Plan' mod='billwerksubscription'}</dt><dd>{$sub['plan_name']|escape:'html':'UTF-8'}</dd></div>
            <div><dt>{l s='Amount' mod='billwerksubscription'}</dt><dd>{$sub['amount']|escape:'html':'UTF-8'}</dd></div>
            <div><dt>{l s='Next Payment' mod='billwerksubscription'}</dt><dd>{$sub['next_billing']|escape:'html':'UTF-8'}</dd></div>
          </dl>
          <a class="btn btn-outline-primary btn-sm" href="{$sub['subscription_link']|escape:'html':'UTF-8'}">{l s='View' mod='billwerksubscription'}</a>
        </div>
      {/foreach}
    </div>
  {else}
    <p class="alert alert-info">{l s='You don\'t have any subscriptions.' mod='billwerksubscription'}</p>
  {/if}
{/block}
