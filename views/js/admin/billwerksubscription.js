$(document).ready(function () {
    var handle = $('#billwerk_select_plan').find(":selected").val();
    $('#billwerk_select_plan').on('change', function() {
        getPlan(this.value);
        $('#billwerk_plan_name').val($('option:selected',this).data('name'));
    });
    getPlan(handle);

    $('#new-plan-create').click(function (){
         window.open('https://app.frisbii.com/#/rp/config/plans/create');
    });

    $('#billwerk-refresh-plans').click(function () {
        refreshPlanList();
    });

});

// "Refresh list" used to be a form-submit button with no matching submit handler,
// so clicking it just reloaded/saved the whole product form instead of refreshing
// the plan list. Fetch the plans via AJAX (ajaxProcessGetPlans) and rebuild the
// <select> in place instead, preserving the currently selected plan if it's still present.
function refreshPlanList() {
    var $select = $('#billwerk_select_plan');
    var currentHandle = $select.val();

    $.ajax({
        url: window.ajax_get_plans_url,
        dataType: 'json',
    }).done(function (plans) {
        $select.find('option[value!=""]').remove();
        $.each(plans, function (i, plan) {
            var $option = $('<option></option>')
                .attr('value', plan.handle)
                .attr('data-name', plan.name)
                .text(plan.name);
            $select.append($option);
        });
        $select.val(currentHandle);
    }).fail(function () {
        alert("Sorry. Server unavailable. ");
    });
}

function getPlan(handle) {
    $.ajax({
        url: window.ajax_action_url + '&handle=' + handle,
        dataType: 'html',
    }).done(function(data) {
        $('#billwerk-subscription-plan-details').show();
        $('#billwerk-subscription-plan-details').html(data);
    }).fail(function() {
        alert("Sorry. Server unavailable. ");
    });
}