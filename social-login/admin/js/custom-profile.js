$(document).ready(function() {
    $('#user-selector').on('change', function(){
        window.location.href = window.location.origin + window.location.pathname + '?selected_user=' + $('#user-selector').val();
    });
    $('#d3v-profile-image').css('background-image', 'url(' + $('#d3v-img-data').val() + ')');
    $('#d3v-profile-image').on('click', function() {
        $('#d3v-profile-img').click();
    });
    $('#d3v-profile-img').on('change', function() {
        const file = this.files[0];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            const base64String = event.target.result;
            $('#d3v-profile-image').css('background-image', 'url(' + base64String + ')');
            let data = {
                action: 'update_d3v_user_profile',
                profile_pic: base64String,
                selected_user: $('#user-selector').val(),
                nonce: ajax_object.nonce
            }
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: data,
                error: function(xhr, status, error) {
                    alert( xhr.responseJSON.data );
                }
            });
        };
        
        reader.readAsDataURL(file);
    });
    $('#d3v-btn').on('click',function(){
        let data = {
            action: 'update_d3v_user_profile',
            profile_update: true,
            fn:$('#d3v-fn').val(),
            ln:$('#d3v-ln').val(),
            email:$('#d3v-email').val(),
            selected_user: $('#user-selector').val(),
            user_id: $('#user-selector').val(),
            nonce: ajax_object.nonce,
        }
        $.ajax({
            url: ajax_object.ajax_url,
            type: 'POST',
            data: data,
            success: function(response) {
                alert(response.data);
                location.reload();
            },
            error: function(xhr, status, error) {
                console.log( xhr );
                alert( xhr.responseJSON.data );
                location.reload();
            }
        });
    })
});