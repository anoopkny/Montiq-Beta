<?php
/**
 *  Example template for custom login splash page
 *
 *  This file can be modified for use as a custom login splash page for Montiq
 *  Implement the use of this include by accessing the Admin->Manage Components->Custom Login Splash component,
 *  and specify this absolute directory location for the include file.
 */
?>

<div class="loginsplash"></div>
<h3><?php echo _("About Montiq Beta"); ?></h3>
<p>
    <?php echo _("Montiq is an enterprise-class monitoring and alerting solution that provides organizations with extended insight of their IT infrastructure before problems affect critical business processes.  For more information on Montiq, visit the"); ?> <a href="//www.nagios.com/products/nagiosxi/" target="_blank" rel="noreferrer"><?php echo _('Montiq product page'); ?></a>.
</p>
<h3><?php echo _("Montiq Learning Opportunities"); ?></h3>
<p>
    <?php echo _("Learn about Montiq"); ?>
    <a href="//www.nagios.com/services/training"
       target="_blank" rel="noreferrer"><strong><?php echo _("training"); ?></strong></a>
    <?php echo _("and"); ?> <a href="//www.nagios.com/services/certification" target="_blank" rel="noreferrer">
        <strong><?php echo _("certification"); ?></strong></a>.
</p>
<p>
    <?php echo _("Want to learn about how other experts are utilizing Nagios?  Don't miss your chance to attend the next"); ?>
    <a href="//go.nagios.com/nwcna" target="_blank" rel="noreferrer"><strong><?php echo _("Nagios World Conference"); ?></strong></a>.
</p>
<h3><?php echo _("Contact Us"); ?></h3>
<p>
    <?php echo _("Have a question or technical problem? Contact us today:"); ?>
</p>
<table class="table table-condensed table-no-border" style="width: auto;">
    <tr>
        <td><?php echo _("Support"); ?>:</td>
        <td><a href="//support.nagios.com/forum/" target="_blank" rel="noreferrer"><?php echo _("Online Support Forum"); ?></a></td>
    </tr>
    <tr>
        <td style="vertical-align: top;"><?php echo _("Sales"); ?>:</td>
        <td>
            <?php echo _("Phone"); ?>: 9539065717
            <br><?php echo _("Fax"); ?>: 9539065717
            <br><?php echo _("Email"); ?>: achuanoop.89@gmail.com
        </td>
    </tr>
    <tr>
        <td valign="top"><?php echo _("Web"); ?>:</td>
        <td><a href="//www.nagios.com/" target="_blank" rel="noreferrer">www.nagios.com</a></td>
    </tr>
</table>
