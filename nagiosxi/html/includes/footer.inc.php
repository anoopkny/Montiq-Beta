<!-- FOOTER START -->
<div id="footer">

    <?php
    // XI 2014 styling
    $theme = get_theme();
    if (use_2014_features() && $theme == 'xi2014') {
        ?>

        <div class="well">
            <div class="row-fluid">
                <div class="span6 footer-left">
                    <?php echo get_product_name(); ?> <?php if (is_authenticated() === true || is_dev_mode()) echo get_product_version_display(); ?>
                    <?php if (is_debug_mode() || is_dev_mode()) { echo get_debug_toolbar(); } ?>
                    <?php if (is_admin()) { // Admins only for updates ?>
                        &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                        <a href="<?php echo get_update_check_url(); ?>" target="_blank" rel="noreferrer"><?php echo _("Check for Updates"); ?> <i class="icon-share"></i></a>
                    <?php } ?>
                </div>
                <div class="span6 footer-right">
                    <?php if (is_admin()) { ?>
                        <div id="tray_alerter">
                            <i class="fa fa-spinner fa-pulse"></i>
                        </div>
                        <div id="tray_alerter_popup">
                            <strong><?php echo _("Information and Alerts"); ?>:</strong>
                            <div id="tray_alerter_popup_content"
                                 style="overflow: auto; border: 1px solid white; margin: 10px 0 0 0; height: 100px;">
                                <img src="<?php echo theme_image("throbber.gif"); ?>"> Loading data...
                            </div>
                        </div>
                    <?php } ?>

                    <a href="<?php echo get_base_url(); ?>about/"><?php echo _("About"); ?></a> &nbsp;&nbsp;|&nbsp;&nbsp;
                    <a href="<?php echo get_base_url(); ?>about/?legal"><?php echo _("Legal"); ?></a> &nbsp;&nbsp;|&nbsp;&nbsp;
                    Copyright &copy; 2008-<?php echo date('Y'); ?> <a href="https://www.nagios.com/" target="_blank" rel="noreferrer">Nagios Enterprises, LLC</a>
                </div>
            </div>
        </div>

    <?php
    } else if ($theme == 'xi5') { // End XI 2014 style
    ?>

    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6 footer-left">
                <a href="http://nagios.com/products/nagiosxi" target="new"><strong><?php echo get_product_name(); ?></strong></a> <?php if (is_authenticated() === true || is_dev_mode()) echo get_product_version_display(); ?>
                <?php if (is_debug_mode() || is_dev_mode()) { echo get_debug_toolbar(); } ?>
                <?php if (is_admin()) { // Admins only for updates ?>
                    &nbsp;&nbsp;&bull;&nbsp;&nbsp;
                    <a href="<?php echo get_update_check_url(); ?>" target="_blank" rel="noreferrer"><?php echo _("Check for Updates"); ?></a>
                <?php } ?>
            </div>
            <div class="col-sm-6 footer-right">
                <?php if (is_admin()) { ?>
                    <div id="tray_alerter">
                        <i class="fa fa-spinner fa-pulse"></i>
                    </div>
                    <div id="tray_alerter_popup">
                        <strong><?php echo _("Information and Alerts"); ?>:</strong>

                        <div id="tray_alerter_popup_content"
                             style="overflow: auto; border: 1px solid white; margin: 10px 0 0 0; height: 100px;">
                            <img src="<?php echo theme_image("throbber.gif"); ?>"> Loading data...
                        </div>
                    </div>
                <?php } ?>

                <a href="<?php echo get_base_url(); ?>about/"><?php echo _("About"); ?></a> &nbsp;&nbsp;|&nbsp;&nbsp;
                <a href="<?php echo get_base_url(); ?>about/?legal"><?php echo _("Legal"); ?></a> &nbsp;&nbsp;|&nbsp;&nbsp;
                <?php echo _('Copyright'); ?> &copy; 2008-<?php echo date('Y'); ?> <a href="https://www.nagios.com/" target="_blank" rel="noreferrer">Nagios Enterprises, LLC</a>
            </div>
        </div>
    </div>

    <?php
    } else { // End XI 5 style
    ?>

        <div id="footermenucontainer">
            <div id="footernotice">
                <?php echo get_product_name(); ?> <?php if (is_authenticated() === true) echo get_product_version(); ?> &nbsp;-&nbsp;
                <?php if (is_admin()) { // Only display update link for admin users ?>
                    <a href="<?php echo get_update_check_url(); ?>" target="_blank" rel="noreferrer"><?php echo _("Check for Updates"); ?></a> &nbsp;-&nbsp;
                <?php } ?>
                Copyright &copy; 2008-<?php echo date('Y'); ?> <a href="https://www.nagios.com/" target="_blank" rel="noreferrer">Nagios
                    Enterprises, LLC</a>
            </div>

            <?php if (is_authenticated() === true) { ?>
                <div id="tray_alerter">
                    <i class="fa fa-spinner fa-pulse"></i>
                </div>
                <div id="tray_alerter_popup">
                    <strong><?php echo _("Information and Alerts"); ?>:</strong>

                    <div id="tray_alerter_popup_content"
                         style="overflow: auto; border: 1px solid white; margin: 10px 0 0 0; height: 100px;">
                        <img src="<?php echo theme_image("throbber.gif"); ?>"> Loading data...
                    </div>
                </div>
            <?php } ?>

            <ul class="footermenu">
                <li><a href="<?php echo get_base_url(); ?>about/"><?php echo _("About"); ?></a></li>
                <li><a href="<?php echo get_base_url(); ?>about/?legal"><?php echo _("Legal"); ?></a></li>
            </ul>
        </div>



    <?php
    } // End original classic style
    ?>

    <script type="text/javascript">

        function get_tray_alert_content() {
            var optsarr = {
                "func": "get_tray_alert_html",
                "args": ""
            }
            var opts = array2json(optsarr);
            get_ajax_data_with_callback("getxicoreajax", opts, "process_tray_alert_content");
        }

        function process_tray_alert_content(edata) {
            data = unescape(edata);
            $("#tray_alerter_popup_content").html(data);

            var status = $("#tray_alerter_status").html();
            $("#tray_alerter").html(status);
        }

        $(document).ready(function () {

            get_tray_alert_content();

            $("#tray_alerter").everyTime(<?php echo get_dashlet_refresh_rate(30, "tray_alert"); ?>, "timer-tray_alerter", function (i) {
                get_tray_alert_content();
            });

            $("#tray_alerter").click(function () {
                var vis = $("#tray_alerter_popup").css("visibility");
                if (vis == "hidden") {
                    $("#tray_alerter_popup").css("visibility", "visible");
                } else {
                    $("#tray_alerter_popup").css("visibility", "hidden");
                }
            });

        });
    </script>

    <div id="keepalive"></div>

</div> <!-- end footer div -->

<!-- FOOTER END -->
