zenario_content_list.generateZIP = function(el,requests,slotname)
{
	$('#link_to_download_page_'+slotname).hide();
	$('#generating_documents_'+slotname).show();
	zenario_content_list.refreshPluginSlot(el,requests);
}


