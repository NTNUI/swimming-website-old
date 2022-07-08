<?php
declare(strict_types=1);
?>
    </div>
        <footer>
            <div>
                <?php
                // Make image containers
                $files = glob('img/sponsors/*.svg');
                $settings = Settings::get_instance();
                foreach ($files as $file) {
                    $url = $settings->get_baseurl() . "/" . $file;
                    $name = basename($url, ".svg");
                    $link = $t->get_translation("link_$name", "sponsors");

                    print("<a rel='noreferrer noopener' target='_blank' href='$link'>");
                    print("<img class='img-sponsor' src='$url' alt='$name'>");
                    print("</a>");
                }
                ?>

            </div>
            <div>
                <?php
                    // TODO: generate this from db
                ?>
                <a rel="noreferrer noopener" target="_blank" href="https://ntnui.no">NTNUI</a>
                <a rel="noreferrer noopener" target="_blank" href="https://github.com/ntnui">GitHub</a>
                <a rel="noreferrer noopener" target="_blank" href="https://mediearkiv.ntnui.no/">Mediearkivet</a>
                <a rel="noreferrer noopener" target="_blank" href="https://instagram.com/ntnuisvommegruppa/">Instagram</a>
                <a rel="noreferrer noopener" target="_blank" href="https://www.facebook.com/NTNUISvomming">Facebook</a>
            </div>
            <div>
                <p>
                    © <?php print Date("Y"); ?> NTNUI
                </p>
        </div>
        </footer>
        </body>

        </html>