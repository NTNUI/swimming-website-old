<?php

declare(strict_types=1);

/**
 * Print default content block with optional description image
 *
 * @param string $header Header of the content block
 * @param string $description paragraph
 * @param string $image_path if set and image file exists 
 * @param string $image_text image caption
 * @param string $email if set email address will be added below image
 * @param string $image_class add style class to image
 * @param string $image_license if not set images will be defaulted to "NTNUI (CC BY 4.0)"
 * @return void
 */
function print_content_block(string $header, string $description, string $image_path, string $image_text, string $email = "", string $image_class = "", string $image_license = "NTNUI (CC BY 4.0)")
{
    global $settings;
?>
    <div class="box">
        <div class="<?php print $image_path ? "max-60 " : ""; ?>contents">
            <h2><?php print($header); ?></h2>
            <p><?php print($description); ?></p>
        </div>

        <?php
        // Should file_exists throw an error? It's not a problem in the source but in the content.
        if ($image_path && !file_exists($image_path)) {
            log::message("Warning: Cannot find image : " . $image_path, __FILE__, __LINE__);
        }

        if ($image_path && file_exists($image_path)) {
        ?>
            <div class="card">
                <img class="<?php print($image_class); ?>" src="<?php print($settings["baseurl"] . "/" . $image_path); ?>" alt="<?php print($image_text); ?>">
                <label class="card_content"><?php print($image_text); ?></label>
                <?php
                if ($email) {
                    print("<a class='email card_content' href='mailto:$email'>$email</a>");
                }
                ?>
                <p class="license card_content"><span class="emoji">📷</span><?php print $image_license ?></p>
            </div>
        <?php
        }
        ?>

    </div>
<?php
}

/**
 * Print default content header
 *
 * @param string $main_header
 * @param string $sub_header
 * @return void
 */
function print_content_header(string $main_header, string $sub_header)
{
?>
    <div class="box">
        <h1><?php print($main_header); ?></h1>
        <p><?php print($sub_header); ?></p>
    </div>
<?php
}

/**
 * prints style and script tags if those files exists
 *
 * @param string $caller just pass inn __FILE__ when calling
 * @return void
 */
function style_and_script(string $caller)
{
    $caller = str_replace(".php", "", basename($caller));
    $js_path = "js/" . $caller . ".js";
    $css_path = "css/" . $caller . ".css";
    
    global $settings;
    if (file_exists($js_path)) {
        print("<script defer type='text/javascript' src='${settings["baseurl"]}/$js_path'></script>");
    }
    if (file_exists($css_path)) {
        print("<link rel='stylesheet' type='text/css' href='${settings["baseurl"]}/$css_path'></link>");
    }
}
