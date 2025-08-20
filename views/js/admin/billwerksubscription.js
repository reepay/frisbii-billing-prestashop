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

});

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