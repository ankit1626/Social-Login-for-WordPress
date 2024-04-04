function login(response) {
    if( 'connected' === response.status ) {
        FB.api('/me', {fields: 'first_name,last_name,name,email,picture'}, function(response) {
            let data = {
                action: 'continue_with_fb',
                res: response,
                nonce: ajax_object.nonce
            };
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    window.location.replace( response.data );
                },
                error: function(xhr, status, error) {
                    console.log(xhr);
                    window.location.replace( xhr.responseJSON.data );
                    // alert(  );
                }
            });
        });
    }
}