{capture name=path}
    <a href="{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}"
       title="{l s='Go back to the Checkout' mod='billwerksubscription'}" xmlns="http://www.w3.org/1999/html">{l s='Checkout' mod='billwerksubscription'}</a><span class="navigation-pipe">{$navigationPipe}</span>{l s='Frisbii Optimize' mod='billwerksubscription'}
{/capture}

<h1 class="page-heading">{l s='Order summary' mod='billwerksubscription'}</h1>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $is_error}
    <div class="bootstrap">
        <div class="alert alert-danger">
            <button type="button" class="close" data-dismiss="alert">×</button>
            {$is_error}
        </div>
    </div>
{/if}

<form action="{$link->getModuleLink('billwerksubscription', 'validation', [], true)|escape:'html'}" method="post">
<input type="hidden" name="is_submit" value="1">
<div class="box">
     <h3 class="page-subheading">{l s='Frisbii Optimize' mod='billwerksubscription'}</h3>
    <div style="height: 100px;">
        <img id="hp" src="{{$img}}" style="margin-right:20px; width: 100px; height: 100px;"/>
        <strong class="dark"> Press confirm order button to finish order with Frisbii Optimize </strong>
        <p> You will be redirected to Frisbii Optimize payment window.  </p>
        <p>After payment is finished you will be redirected back to the shop</p>
    </div>
</div>

<p class="cart_navigation clearfix" id="cart_navigation">
    <a href="" class="button-exclusive btn btn-default">
        <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='billwerksubscription'}
    </a>

    <button type="submit" class="button btn btn-default button-medium">
        <span>{l s='I confirm my order' mod='cheque'}<i class="icon-chevron-right right"></i></span>
    </button>
</p>
</form>