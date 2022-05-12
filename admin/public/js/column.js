if(window.attachEvent) {
    window.attachEvent('onload', flush_object_cache_column_button_action);
} else {
    if(window.onload) {
        var curronload_1 = window.onload;
        var newonload_1 = function(evt) {
            curronload_1(evt);
            flush_object_cache_column_button_action(evt);
        };
        window.onload = newonload_1;
    } else {
        window.onload = flush_object_cache_column_button_action;
    }
}
function flush_object_cache_column_button_action(){
    jQuery(document).ready(function(){
        jQuery("a[id^='flush-object-cache-url']").click(function(e){
            var post_id = jQuery(e.target).attr("data-id");
            var nonce = jQuery(e.target).attr("data-nonce");

            jQuery("#flush-object-cache-url-" + post_id).css('cursor', 'wait');

            jQuery.ajax({
                type: 'GET',
                url: ajaxurl,
                data : {"action": "pcm_flush_object_cache_column", "id" : post_id, "nonce" : nonce},
                dataType : "json",
                cache: false, 
                success: function(data){
                    jQuery("#flush-object-cache-url-" + post_id).css('cursor', 'pointer');

                    if(typeof data.success != "undefined" && data.success == true){
                        //
                        alert("Batcache flushed successfully :)");
                    }else{
                        alert("Something went wrong while trying to flush object cache for this page");
                    }
                }
            });

            return false;
        });
    });
}