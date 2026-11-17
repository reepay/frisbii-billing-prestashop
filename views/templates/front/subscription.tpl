{extends file='customer/page.tpl'}

{block name='page_title'}
  {l s='Subscription' mod='billwerksubscription'} {$data['handle']|escape:'html':'UTF-8'}
{/block}

{block name='account_link'}
  <a class="btn btn-outline-tertiary account-menu__back" href="{$data['my-subscription-page']|escape:'html':'UTF-8'}">
    <i class="material-icons" aria-hidden="true">&#xE5C4;</i>
    {l s='My Subscriptions' mod='billwerksubscription'}
  </a>
{/block}

{block name='page_content'}
  {if $data['sb_action_success']}
    <div class="alert alert-success">{$data['sb_action_success']|escape:'html':'UTF-8'}</div>
  {/if}
  {if $data['sb_action_error']}
    <div class="alert alert-danger">{$data['sb_action_error']|escape:'html':'UTF-8'}</div>
  {/if}
  {if $data['error']}
    <p class="alert alert-warning">{l s='Subscription not found' mod='billwerksubscription'}</p>
  {/if}

  <div class="bws-card">
    <div class="bws-card__head">
      <span class="bws-card__handle">{$data['plan']|escape:'html':'UTF-8'}</span>
      <span class="bws-badge bws-badge--{$data['state']|escape:'html':'UTF-8'}">{$data['state_name']|escape:'html':'UTF-8'}</span>
    </div>

    <dl class="bws-card__facts">
      {if $data['in_trial'] && $data['state'] == 'active'}
        <div><dt>{l s='Trial period start' mod='billwerksubscription'}</dt><dd>{$data['trial_start']|escape:'html':'UTF-8'}</dd></div>
        <div><dt>{l s='Trial period end' mod='billwerksubscription'}</dt><dd>{$data['trial_end']|escape:'html':'UTF-8'}</dd></div>
      {/if}
      <div><dt>{l s='Start Payment date' mod='billwerksubscription'}</dt><dd>{$data['first_period_start']|escape:'html':'UTF-8'}</dd></div>
      <div><dt>{l s='Last Payment date' mod='billwerksubscription'}</dt><dd>{$data['current_period_start']|escape:'html':'UTF-8'}</dd></div>
      <div><dt>{l s='Next Payment date' mod='billwerksubscription'}</dt><dd>{$data['next_period_start']|escape:'html':'UTF-8'}</dd></div>
      <div>
        <dt>{l s='Payment methods' mod='billwerksubscription'}</dt>
        <dd>{if $data['payment_method_added'] && isset($data['payment_method'])}{$data['payment_method']['masked_card']|escape:'html':'UTF-8'}{else}-{/if}</dd>
      </div>
    </dl>

    <form method="POST" action="{$data['manage_subscription_link']|escape:'html':'UTF-8'}" class="bws-actions">
      <input type="hidden" name="handle" value="{$data['handle']|escape:'html':'UTF-8'}">
      <input type="hidden" name="token" value="{$data['token']|escape:'html':'UTF-8'}">
      {if $data['state'] == 'active'}
        {if $data['enable_on_hold'] && $data['in_trial'] == false}
          <button type="submit" name="action" value="pause" class="btn btn-outline-primary btn-sm">{l s='Pause' mod='billwerksubscription'}</button>
        {/if}
        {if $data['enable_cancel']}
          <button type="submit" name="action" value="cancel" class="btn btn-outline-danger btn-sm">{l s='Cancel' mod='billwerksubscription'}</button>
        {/if}
        <button type="submit" name="action" value="change-payment" class="btn btn-primary btn-sm">{l s='Change Payment' mod='billwerksubscription'}</button>
      {elseif $data['state'] == 'pending'}
        <button type="submit" name="action" value="add-payment" class="btn btn-primary btn-sm">{l s='Add Payment' mod='billwerksubscription'}</button>
      {elseif $data['state'] == 'on_hold'}
        <button type="submit" name="action" value="activate" class="btn btn-primary btn-sm">{l s='Activate' mod='billwerksubscription'}</button>
      {elseif $data['state'] == 'cancelled'}
        <button type="submit" name="action" value="uncancel" class="btn btn-primary btn-sm">{l s='Restart' mod='billwerksubscription'}</button>
      {/if}
    </form>
  </div>
{/block}
