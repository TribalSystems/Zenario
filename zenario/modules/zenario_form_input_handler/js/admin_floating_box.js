zenarioTab.copyRoutingRule = function(){

var tableElement;
var insertPlace;
var newRow;  
var maxIndex;
var tbody;
//var lastRow;
		tableElement= window.document.getElementById('form_input_handler_table');
		tbody = tableElement.getElementsByTagName("tbody")[0];
		newRow = (window.document.getElementById('form_input_handler_table_row_0')).cloneNode(true);
		
		maxIndex = 0;
		for (i=0;i<tableElement.childNodes.length;i++){
			if (tableElement.childNodes[i].nodeType==1){
				if (tableElement.childNodes[i].nodeName.toUpperCase()==('TBODY').toUpperCase()){
					insertPlace = tableElement.childNodes[i];
					for (j = 0;j<tableElement.childNodes[i].childNodes.length;j++){
						if (tableElement.childNodes[i].childNodes[j].nodeType==1){
							if (tableElement.childNodes[i].childNodes[j].getAttribute('index')!=''){
								if (maxIndex < parseInt(tableElement.childNodes[i].childNodes[j].getAttribute('index')))
									maxIndex = parseInt(tableElement.childNodes[i].childNodes[j].getAttribute('index'));
							}
						}
					}
				}
			}
		}




		newRow.removeAttribute('style');
		for (i=0;i<newRow.childNodes.length;i++){
			if (newRow.childNodes[i].nodeType==1){
				if (newRow.childNodes[i].nodeName.toUpperCase()==('TD').toUpperCase()){
					for (j = 0;j<newRow.childNodes[i].childNodes.length;j++){
						if (newRow.childNodes[i].childNodes[j].nodeType==1){
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')!='rembutton_0')
								newRow.childNodes[i].childNodes[j].value='';
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='rembutton_0')
								newRow.childNodes[i].childNodes[j].setAttribute('onclick',newRow.childNodes[i].childNodes[j].getAttribute('onclick').replace('__rule_number_to_remove__','\\\'rembutton_' + (maxIndex+1).toString() + '\\\''));

							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='cmp_type_0')
								newRow.childNodes[i].childNodes[j].setAttribute('name','cmp_type_' + (maxIndex+1).toString())
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='index_0')
								newRow.childNodes[i].childNodes[j].setAttribute('name','index_' + (maxIndex+1).toString())
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='cmp_value_0')
								newRow.childNodes[i].childNodes[j].setAttribute('name','cmp_value_' + (maxIndex+1).toString())
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='email_to_0')
								newRow.childNodes[i].childNodes[j].setAttribute('name','email_to_' + (maxIndex+1).toString())
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='template_no_0')
								newRow.childNodes[i].childNodes[j].setAttribute('name','template_no_' + (maxIndex+1).toString())
							if (newRow.childNodes[i].childNodes[j].getAttribute('name')=='rembutton_0')
								newRow.childNodes[i].childNodes[j].setAttribute('name','rembutton_' + (maxIndex+1).toString())
							if (newRow.childNodes[i].childNodes[j].getAttribute('id')=='rembutton_0')
								newRow.childNodes[i].childNodes[j].setAttribute('id','rembutton_' + (maxIndex+1).toString())
						}
						if (newRow.childNodes[i].childNodes[j].nodeType==3){
							if (newRow.childNodes[i].childNodes[j].nodeValue=='0:')
								newRow.childNodes[i].childNodes[j].nodeValue = (maxIndex+1).toString() + ':';
						}
					}
				}
			}
		}
		newRow.setAttribute('index',maxIndex+1);
		newRow.setAttribute('id','form_input_handler_table_row_' + (maxIndex +1).toString() );

		tbody.appendChild(newRow);
		
}


zenarioTab.removeRule = function(caller){
var nodeToRemove;
var nodeRemoveFrom;
												
	nodeToRemove = caller.parentNode.parentNode;
	nodeRemoveFrom = caller.parentNode.parentNode.parentNode;
	nodeRemoveFrom.removeChild(nodeToRemove);
	
}
