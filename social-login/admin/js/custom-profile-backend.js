$(document).ready(function() {
    $('#d3v-profile-img').on('change', function() {
        const file = this.files[0];
        const reader = new FileReader();
        
        reader.onload = function(event) {
            const base64String = event.target.result;
            let data = {
                action: 'update_d3v_user_profile',
                profile_pic: base64String,
                selected_user: ajax_object.selected_user,
                nonce: ajax_object.nonce
            }
            $.ajax({
                url: ajax_object.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    console.log(response)
                    alert('Profile Image Updated');
                    location.reload();
                },
                error: function(xhr, status, error) {
                    alert( xhr.responseJSON.data );
                }
            });

        };

        reader.readAsDataURL(file);
    });
});