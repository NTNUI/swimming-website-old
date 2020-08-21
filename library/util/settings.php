<?php 
function loadSettings($input){
    
    $settings_raw = file_get_contents("./settings/" . $input . ".json");
    if(!$settings_raw){
        print("error reading settings file");
    }

    return json_decode($settings_raw,true);

}



?>

