<?php

function isValidURL($URL_part)
{
    if (preg_match("#\.\./#", $URL_part)) {
        // contains directory climbing code
        return false;
    }

    // Contains charactars that are not numbers, letters and {_-.} only. Just block that shit.
    if (!preg_match("#^[-a-zA-Z0-9_.]+$#i", $URL_part)) {
        return false;
    }


    return true;
}


function printIllegalRequest(){
    print("<div class='box'><h2>Illegal request</h2></div>");
}