var PCM_TOOLBAR = {
	ajax_url: false,
	init: function(){
		var self = this;

		if(typeof ajaxurl != "undefined" || typeof pcm_ajaxurl != "undefined"){
			self.ajax_url = (typeof ajaxurl != "undefined") ? ajaxurl : pcm_ajaxurl;
		}else{
			alert("AjaxURL has NOT been defined");
		}

		jQuery("body").append('<div id="revert-loader-toolbar"></div>');

		jQuery("#wp-admin-bar-pcm-toolbar-parent-remove-branding-default li").click(function(e){
		var id = (typeof e.target.id != "undefined" && e.target.id) ? e.target.id : jQuery(e.target).parent("li").attr("id");
		var action = "";
		
		if(id == "wp-admin-bar-pcm-toolbar-parent-remove-branding-remove-branding"){
			if(jQuery("div[id^='pcm-modal-toolbarsettings-']").length === 0){
				self.open_settings();
			}
		}else{
			if(id == "wp-admin-bar-pcm-toolbar-parent-remove-branding-delete-cache"){
				action = "pcm_delete_cache";
			}else if(id == "wp-admin-bar-pcm-toolbar-parent-remove-branding-clear-cache-of-this-page"){
				action = "pcm_delete_current_page_cache";
			}

			PCM_TOOLBAR.send({"action": action, "path" : window.location.pathname});
		}
		});
	},
	open_settings: function(){
		var self = this;

		jQuery("#revert-loader-toolbar").show();
		jQuery.ajax({
			type: 'GET',
			url: self.ajax_url,
			data : {"action": "pcm_delete_current_page_cache", "path" : window.location.pathname},
			dataType : "json",
			cache: false, 
			success: function(data){
				if(data.success){
					var data_json = {"action": "pcm_toolbar_save_settings", "path" : window.location.pathname, "roles" : {}};

					pcm_New_Dialog.dialog("pcm-modal-toolbarsettings", {
						close: function(){
							pcm_New_Dialog.clone.remove();
						},
						finish: function(){
							jQuery("#" + pcm_New_Dialog.id).find("input[type='checkbox']:checked").each(function(i, e){
								data_json.roles[jQuery(e).attr("name")] = 1;
							});

							PCM_TOOLBAR.send(data_json);

							pcm_New_Dialog.clone.remove();
					}}, function(dialog){
						jQuery("#" + pcm_New_Dialog.id).find("input[type='checkbox']").each(function(i, e){
							if(typeof data.roles[jQuery(e).attr("name")] != "undefined"){
								jQuery(e).attr('checked', true);
							}
						});

						pcm_New_Dialog.show_button("close");
						pcm_New_Dialog.show_button("finish");

						setTimeout(function(){
							jQuery("#revert-loader-toolbar").hide();
						}, 500);
					});
				}else{
					alert("Toolbar Settings Error!")
				}
			}
		});
	},
	send: function(data_json){
		var self = this;

		if(typeof pcm_nonce != "undefined" && pcm_nonce){
			data_json.nonce = pcm_nonce;
		}

		jQuery("#revert-loader-toolbar").show();
		jQuery.ajax({
			type: 'GET',
			url: self.ajax_url,
			data : data_json,
			dataType : "json",
			cache: false, 
			success: function(data){
				if(data[1] == "error"){
					if(typeof data[2] != "undefined" && data[2] == "alert"){
						alert(data[0]);
					}else{
						pcm_New_Dialog.dialog("pcm-modal-permission", {close: "default"});
						pcm_New_Dialog.show_button("close");
					}
				}

				if(typeof pcmCacheStatics != "undefined"){
					pcmCacheStatics.update();
				}else{
					jQuery("#revert-loader-toolbar").hide();
				}
			}
		});
	}
};

window.addEventListener('load', function(){
	jQuery(document).ready(function(){
		PCM_TOOLBAR.init();
	});
});