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
    if(0 !== strpos($settings["baseurl"], "https://")){
    	throw new Exception("Error: settings[hosting][baseurl] does not start with 'https://'. This will break links. settings[hosting][baseurl] contains: ". $settings["baseurl"], __FILE__, __LINE__);
    }
    return $settings;
}

function test_settings(){
    // test access
    foreach(["img/store", "/tmp", "sessions", "translations"] as $dir){
        if (!is_writable($dir)){
            log::die("$dir is not writable", __FILE__, __LINE__);
        }
    }
    foreach(["css", "js", "library", "private", "public", "vendor"] as $dir){
        if (!is_readable($dir)){
            log::die("$dir is not readable", __FILE__, __LINE__);
        }
    }
    // 
}

?>
