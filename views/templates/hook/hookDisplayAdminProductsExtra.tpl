<div class="card billwerksubscription-panel">
    <div class="card-header">
        <h3><i class="material-icons">autorenew</i> {l s='Frisbii subscription plan' mod='billwerksubscription'}</h3>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="control-label col-lg-3">
                {l s='Attach subscription plan to the product' mod='billwerksubscription'}
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="billwerksubscription_plan_attach_to_product" id="billwerksubscription_plan_attach_to_product_on" value="1" {if $product_has_subscription == 1} checked="checked" {/if}/>
                    <label for="billwerksubscription_plan_attach_to_product_on">{l s='Yes' mod='billwerksubscription'}</label>
                    <input type="radio" name="billwerksubscription_plan_attach_to_product" id="billwerksubscription_plan_attach_to_product_off" value="0" {if $product_has_subscription == 0} checked="checked" {/if} />
                    <label for="billwerksubscription_plan_attach_to_product_off">{l s='No' mod='billwerksubscription'}</label>
                    <a class="slide-button btn"></a>
                </span>
                <p class="help-block">{l s='When enabled the product will be a subscription product' mod='billwerksubscription'}</p>
            </div>
        </div>

        <div class="form-group row">
            <label class="control-label col-lg-3" for="billwerk_select_plan">
                {l s='Choose plan' mod='billwerksubscription'}
            </label>
            <div class="col-lg-9">
                <div class="billwerksubscription-plan-toolbar">
                    <select name="plan" id="billwerk_select_plan" class="form-control">
                        <option value="">{l s='----------- No plan -----------' mod='billwerksubscription'}</option>
                        {foreach $plans as $plan}
                            <option value="{$plan->handle}" {if $plan_handle == $plan->handle} selected="selected" {/if} data-name="{$plan->name}">{$plan->name}</option>
                        {/foreach}
                    </select>
                    <input id="billwerk_plan_name" type="hidden" name="plan-name" value="{$plan->name}">
                    <button type="submit" name="submitState" class="btn btn-outline-secondary">
                        <i class="material-icons">refresh</i> {l s='Refresh list' mod='billwerksubscription'}
                    </button>
                    <button type="button" id="new-plan-create" class="btn btn-outline-secondary">
                        <i class="material-icons">add</i> {l s='Create new plan' mod='billwerksubscription'}
                    </button>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <div class="col-lg-3"></div>
            <div class="col-lg-9">
                <div id="billwerk-subscription-plan-details" {if $product_has_subscription == 0} style="display: none;" {/if}></div>
            </div>
        </div>
    </div>

    <div class="card-footer">
        <button type="submit" name="submitAddproduct" class="btn btn-primary pull-right"><i
                    class="process-icon-save"></i> {l s='Save' mod='billwerksubscription'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-primary pull-right"><i
                    class="process-icon-save"></i> {l s='Save and stay' mod='billwerksubscription'}</button>
    </div>
</div>
<link rel="stylesheet" type="text/css" href="{$pc_base_dir}views/css/admin/billwerksubscription-admin.css?hash={$hash}">
<script type="text/javascript">
    window.ajax_action_url = "{$ajax_action_url}";
</script>
<script type="text/javascript" src="{$pc_base_dir}views/js/admin/billwerksubscription.js?hash={$hash}"></script>
