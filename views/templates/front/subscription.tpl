{if $data['sb_action_success']}
    <div class="bootstrap">
        <div class="module_confirmation conf confirm alert alert-success">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {$data['sb_action_success']}
        </div>
    </div>
{/if}

{if $data['sb_action_error']}
    <div class="bootstrap">
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {$data['sb_action_error']}
        </div>
    </div>
{/if}

{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
        {l s='My account'}
    </a>
    <a href="{$data['my-subscription-page']|escape:'html':'UTF-8'}">
        {l s='My Subscriptions'}
    </a>
    <a href="{$data['my-subscription-link']|escape:'html':'UTF-8'}">
        {l s={$data['handle']}}
    </a>
{/capture}

<div class="block-center" id="block-history">
    {if $data['error']}
        <p class="alert alert-warning">{l s='Subscription not found'}</p>
    {/if}

    <table id="order-list" class="table table-bordered footab" style="width: 50%">
        <tr>
            <td> <label> {l s='Status'} </label> </td>
            <td> {$data['state_name']}</td>
        </tr>
        <tr>
            <td> <label>{l s='Plan'} </label> </td>
            <td> {$data['plan']} {if $data['in_trial'] && $data['state'] == 'active'} &nbsp; <b>[Trial period is active]</b> {/if}  </td>
        </tr>

        {if $data['in_trial'] && $data['state'] == 'active'}
            <tr>
                <td> <label> {l s='Trial period start'} </label> </td>
                <td> {$data['trial_start']} </td>
            </tr>
            <tr>
                <td> <label> {l s='Trial period end'} </label> </td>
                <td> {$data['trial_end']} </td>
            </tr>
        {/if}

        <tr>
            <td> <label>{l s='Start Payment date'}</label> </td>
            <td> {$data['first_period_start']} </td>
        </tr>
        <tr>
            <td> <label>{l s='Last Payment date'}</label> </td>
            <td> {$data['current_period_start']} </td>
        </tr>
        <tr>
            <td> <label>{l s='Next Payment date'}</label> </td>
            <td> {$data['next_period_start']} </td>
        </tr>
        <tr>
            <td> <label>{l s='Payment methods'}</label> </td>
            <td>  </td>
        </tr>

        {if $data['payment_method_added']}
            <tr>
                <td> {$data['payment_method']['masked_card']} </td>
                <td> card </td>
            </tr>
        {/if}

        <tr>
            <td> <label>{l s='Action'}</label> </td>
            {if $data['state'] == 'active' }
                <form method="POST" action="{$data['manage_subscription_link']}">
                        <input type="hidden" name="handle" value="{$data['handle']}">
                        <td>

                            {if $data['enable_on_hold'] && $data['in_trial'] == false}
                                <div class="form-group" style="float:left;">
                                        <button type="submit" name="action" value="pause" class="btn btn-default button button-small">
                                            <span>{l s='Pause'}</span>
                                        </button>
                                </div>
                            {/if}

                            {if $data['enable_cancel'] }
                                <div class="form-group" style="float:left; margin-left: 5px;">
                                            <button type="submit" name="action" value="cancel" class="btn btn-default button button-small">
                                                <span>{l s='Cancel'}</span>
                                            </button>
                                </div>
                            {/if}
                                <div class="form-group" style="float:left; margin-left: 5px;">
                                        <button type="submit" name="action" value="change-payment" class="btn btn-default button button-small">
                                            <span>{l s='Change Payment'}</span>
                                        </button>
                                </div>
                        </td>
                </form>
            {/if}
            {if $data['state'] == 'pending' }
                <form method="POST" action="{$data['manage_subscription_link']}">
                    <input type="hidden" name="handle" value="{$data['handle']}">
                    <td>
                        <div class="form-group" style="float:left; margin-left: 5px;">
                            <button type="submit" name="action" value="add-payment" class="btn btn-default button button-small">
                                <span>{l s='Add Payment'}</span>
                            </button>
                        </div>
                    </td>
                </form>
            {/if}

            {if $data['state'] == 'on_hold'}
                <form method="POST" action="{$data['manage_subscription_link']}">
                    <input type="hidden" name="handle" value="{$data['handle']}">
                    <td>
                        <div class="form-group" style="float:left; margin-left: 5px;">
                            <button type="submit" name="action" value="activate" class="btn btn-default button button-small">
                                <span>{l s='Activate'}</span>
                            </button>
                        </div>
                    </td>
                </form>
            {/if}

            {if $data['state'] == 'cancelled'}
                <form method="POST" action="{$data['manage_subscription_link']}">
                    <input type="hidden" name="handle" value="{$data['handle']}">
                    <td>
                        <div class="form-group" style="float:left; margin-left: 5px;">
                            <button type="submit" name="action" value="uncancel" class="btn btn-default button button-small">
                                <span>{l s='Restart'}</span>
                            </button>
                        </div>
                    </td>
                </form>
            {/if}
        </tr>
    </table>

</div>