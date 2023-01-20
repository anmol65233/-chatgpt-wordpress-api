jQuery(document).ready(function($) {
    $('#simple-form-ajax').on('submit', function(e) {
        e.preventDefault();
        //var formData = $(this).serialize();
        var formData = $('#question').val();
        $.ajax({
            type: 'POST',
            url: simple_form_ajax_params.ajax_url,
            data: {
                action: 'simple_form_ajax_submit',
                form_data: formData
            },
            success: function(response) {
                console.log(response);
                // var returnedData = JSON.parse(response);
                // $('#output').html(returnedData.succress);
                // Do something with the response
            }
        });
    });
});
