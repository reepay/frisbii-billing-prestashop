{capture name=path}
    <a href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
        {l s='My account'}
    </a>
    <span class="navigation-pipe">{$navigationPipe}</span>
    <span class="navigation_page">{l s='My Subscriptions'}</span>
{/capture}

<div class="block-center" id="block-history">
    {if $subscriptions && count($subscriptions)}
        <table id="order-list" class="table table-bordered footab">
            <thead>
            <tr>
                <th class="item">{l s='Subscription ID'}</th>
                <th class="item">{l s='Plan'}</th>
                <th class="item">{l s='Status'}</th>
                <th class="item">{l s='Next Payment'}</th>
                <th class="item">{l s='Amount'}</th>
                <th class="item">{l s='Actions'}</th>
              </tr>
            </thead>
            <tbody>
            {foreach from = $subscriptions item=subscription name=subLoop}
                <tr>
                    <td> <a href="{$subscription['subscription']['subscription_link']}" target="_blank"> {$subscription['subscription']['sub_handle']} </a> </td>
                    <td> {$subscription['subscription']['plan_name']} </td>
                    <td> {$subscription['subscription']['state_name']} </td>
                    <td> {$subscription['subscription']['next_billing']} </td>
                    <td> {$subscription['subscription']['amount']} </td>
                    <td> <a href="{$subscription['subscription']['subscription_link']}" target="_blank"> {l s='View'} </a></td>
                </tr>
            {/foreach}
            </tbody>
        </table>
        <div id="block-order-detail" class="unvisible">&nbsp;</div>
    {else}
        <p class="alert alert-warning">{l s='You dont\'t have any subscriptions.'}</p>
    {/if}
</div>

<ul class="footer_links clearfix">
    <li>
        <a class="btn btn-default button button-small" href="{$link->getPageLink('my-account', true)|escape:'html':'UTF-8'}">
			<span>
				<i class="icon-chevron-left"></i> {l s='Back to Your Account'}
			</span>
        </a>
    </li>
    <li>
        <a class="btn btn-default button button-small" href="{$base_dir}">
            <span><i class="icon-chevron-left"></i> {l s='Home'}</span>
        </a>
    </li>
</ul>