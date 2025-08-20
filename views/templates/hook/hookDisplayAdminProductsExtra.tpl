<div class="panel product-tab">
    <div class="row">
        <div class="col-md-12">
            <h2>{l s='Frisbii choose subscription plan' mod='billwerksubscription'}</h2>
            <br/>
            <div class="form-group">
                <label class="control-label col-lg-3">
                    Attach subscription plan to the product
                </label>
                <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                                                            <input type="radio" name="billwerksubscription_plan_attach_to_product" id="billwerksubscription_plan_attach_to_product_on" value="1"  {if $product_has_subscription == 1} checked="checked" {/if}/>
                    <label  for="billwerksubscription_plan_attach_to_product_on">Yes</label>
                                                            <input type="radio" name="billwerksubscription_plan_attach_to_product" id="billwerksubscription_plan_attach_to_product_off" value="0" {if $product_has_subscription == 0} checked="checked" {/if} />
                    <label  for="billwerksubscription_plan_attach_to_product_off">No</label>
                                                            <a class="slide-button btn"></a>
                </span>
                    <p class="help-block">
                        When enabled the product will be subscription product
                    </p>
                </div>
            </div>

            <div class="row">
                <div class="form-group">
                    <div class="col-lg-1">
                        <label class="control-label" for="plans">Choose plan</label>
                    </div>
                    <div class="col-lg-2">
                        <select name="plan" id="billwerk_select_plan">
                            <option value="">----------- No plan -----------</option>
                            {foreach $plans as $plan}
                                <option value="{$plan->handle}" {if $plan_handle == $plan->handle} selected="selected" {/if} data-name="{$plan->name}">{$plan->name}</option>
                            {/foreach}
                        </select>
                        <input id="billwerk_plan_name" type="hidden" name="plan-name" value="{$plan->name}">
                    </div>
                <div class="col-lg-1">
                    <button type="submit" name="submitState" class="btn btn-primary">
                        {l s='Refresh list'}
                    </button>
                </div>
                <div class="col-lg-1">
                    <button type="button" id="new-plan-create" name="createNewPlan" class="btn btn-primary">
                        {l s='Create new plan'}
                    </button>
                </div>
                </div>
            </div>

        </div>
        <div id="billwerk-subscription-plan-details" {if $product_has_subscription == 0} style="display: none;" {/if}>
        </div>
    </div>

    <div class="panel-footer">
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right"><i
                    class="process-icon-save"></i> {l s='Save' mod='billwerksubscription'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right"><i
                    class="process-icon-save"></i> {l s='Save and stay' mod='billwerksubscription'}</button>
    </div>

</div>
<script type="text/javascript">
    window.ajax_action_url = "{$ajax_action_url}";
</script>
<script type="text/javascript" src="{$pc_base_dir}views/js/admin/billwerksubscription.js?hash={$hash}"></script>