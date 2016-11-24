<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: alert_settings.php
//  Desc: Creates the HTML for the "Alert Settings" tab in object management pages. Used in the
//        form class to output the area where everything is defined.
//

if ($this->exactType == 'service' || $this->exactType == 'servicetemplate') {
    $url = '?cmd=modify&type=servicetemplate&id=';
} else {

}

?>
    <div id="tab5" class="inheritance">

        <div><?php echo _("Lists out the inheritance order for this object including inherited object's inherited objects."); ?></div>

        <div>
            <div class="fl well inheritance-box">

                <?php foreach ($FIELDS['inheritance'] as $in) { ?>

                    <?php if (!empty($in['parents'])) {
                            foreach ($in['parents'] as $p) { ?>

                                <div><a href="<?php echo $url.$p['id']; ?>"><?php echo $p['template_name']; ?></a></div>

                            <?php } ?>
                            <div class="in-1"><span><i class="fa fa-level-up fa-14 fa-rotate-90"></i></span><a href="<?php echo $url.$in['id']; ?>"><?php echo $in['template_name']; ?></a></div>
                    <?php } else { ?>

                        <div class="in-1 nudge"><a href="<?php echo $url.$in['id']; ?>"><?php echo $in['template_name']; ?></a></div>

                    <?php } ?>
                <?php } ?>

                <div class="in-2"><span><i class="fa fa-level-up fa-14 fa-rotate-90"></i></span><?php echo _('Current Object'); ?></div>
            </div>
            <div class="clear"></div>
        </div>

    </div>