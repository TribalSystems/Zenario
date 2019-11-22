<?php
require '../visitorheader.inc.php';

if (!ze\user::can('design', 'schema', ze::$vars['schemaId'] ?? $_REQUEST['schemaId'])) {
   exit;
}


$html = $css = '';
$layoutId = 0;
$data = false;
$updatedData = [];

if (!empty($_REQUEST['data'])) {

	$data = json_decode($_REQUEST['data'], true);

    foreach($data as $cells => $slots){

        if(is_array($slots)){
            foreach($slots as $key =>$value){
        
                if(is_array($value) && array_key_exists("cells", $value)){
                
                    foreach($value as $k=>$val){

                        if(is_array($val)){
                            foreach($val as $k2=>$val2){

                                if($k2==0){//first element
                                    $newData['cols'] = $val2['width'] ;
                                }else {
                
                                    $newData['cols'] = -1;
                                }
                                $newData['class_name'] = $val2['class_name'] ?? "";
                                $newData['label'] = $val2['label'] ?? "";
                                $newData['framework'] = $val2['framework'] ?? "";
                                $newData['css_class'] = $val2['css_class'] ?? "";
                                $newData['small_screens'] = $val2['small_screens'] ?? "show" ;
                                $newData['settings'] = $val2['settings'] ?? [];
                                array_push($updatedData, $newData);
                            }
                        }
                    }
                }else {
                    $newData['cols'] = $value['width'] ;
                    $newData['class_name'] = $value['class_name'] ?? "";
                    $newData['label'] = $value['label'] ?? "";
                    $newData['framework'] = $value['framework'] ?? "";
                    $newData['css_class'] = $value['css_class'] ?? "";
                    $newData['small_screens'] = $value['small_screens'] ?? "show" ;
                    $newData['settings'] = $value['settings'] ?? [];
                    array_push($updatedData, $newData);
                } 
            }
        } 
    
    }

    $cells = json_encode($updatedData, JSON_FORCE_OBJECT);

    ze\row::set('slide_layouts',['data' =>$cells], ['id' =>$data['id']]);
} 
