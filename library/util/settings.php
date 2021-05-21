<?php
function load_settings($path){
    $settings_raw = file_get_contents($path);
    if(!$settings_raw){
        print("error reading settings file");
        die();
    }

    $settings = json_decode($settings_raw,true);
    if($settings == ""){
        print("empty settings file");
        die();
    }
    return $settings;
}

?>