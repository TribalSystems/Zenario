(function() {


	
	zenario_videos_fea.init = function(containerId, path, request, mode, pages, libraryName, idVarName) {
		
		//The Videos FEA does not need any custom modifications to the standard FEA class,
		//so its instances can be instances of the FEA class without needing to extend it.
		var feaClass = zenarioFEA;
		
		zenario_abstract_fea.setupAndInit(libraryName, feaClass, containerId, path, request, mode, pages, idVarName);
	};
	
	
	//Load the FEA lib - required for the "Delete video" button
	zenario_videos_fea.getLib = function(containerId) {
	
		var globalName = zenario_videos_fea.moduleClassName + '_' + containerId.replace(/\-/g, '__');
	
	
		if (!window[globalName]) {
			//zenario_videos_fea.init = function(containerId, path, request, mode, pages, libraryName, idVarName) {}
			zenario_videos_fea.init(containerId, "zenario_list_videos", -1, "", [], "zenario_videos_fea", "videoId");
		}
	
		return window[globalName];
	};
	
	zenario_videos_fea.deleteVideoConfirm = function(slotName, containerId, ajaxLink, videoId, title, message, confirmButtonText, cancelButtonText) {
		var lib = zenario_videos_fea.getLib(containerId),
			confirmObj = {
				title: title,
				message: message,
				button_message: confirmButtonText,
				button_css_class: "delete_button",
				cancel_button_message: cancelButtonText,
			};
		
		lib.confirm(confirmObj, function() {
			request = {
				action: 'delete',
				videoId: videoId
			};
			
			zenario.ajax(ajaxLink, request).after(function() {
				zenario_conductor.go(slotName, 'back');
			});
		});
	}
	
	zenario_videos_fea.changeView = function(button, containerId, view) {
		$('#video_view_toggle_wrap div').removeClass('on');
		$(button).addClass('on');
		
		$('#' + containerId + ' .zfea.zfea_blocks').toggleClass("zfea_block_list_like_table", view == 'list');
		$('#' + containerId + ' .zfea.zfea_blocks').toggleClass("zfea_block_like_block", view == 'grid');
		
		//Update height of blocks like in the microtemplate
		zenario_videos_fea.updateBlockSizes(containerId);
	};
	
	zenario_videos_fea.updateBlockSizes = function(containerId) {
		var maxHeight = 0;
		$('#' + containerId + ' .zfea_block_list_wrap .zfea_block')
			.height("")
			.each(function() { maxHeight = Math.max(maxHeight, $(this).height()); })
			.height(maxHeight);
	};
	
	$(window).on('load', function() {
		zenario_videos_fea.updateBlockSizes(zenario_videos_fea.containerId);
	});
	
})();