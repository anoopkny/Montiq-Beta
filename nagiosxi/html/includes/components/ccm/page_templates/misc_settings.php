<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: misc_settings.php
//  Desc: Template of HTML for the layout of the 4th tab on host/services that allows
//        adding of misc. things such as free variables.
//
?>
    <div id="tab4">
        <div class="leftBox">
            <?php
            // Contacts or Contact Templates only
            if ($this->exactType != 'contact' && $this->exactType != 'contacttemplate') {
            ?>
            <div class="ccm-row">
                <label for="tfNotes"><?php echo _("Notes"); ?></label>
                <input name="tfNotes" class="form-control fc-fl" type="text" id="tfNotes" value="<?php @val($FIELDS['notes']); ?>">
            </div>
            <div class="ccm-row">
                <label for="tfVmrlImage"><?php echo _("VRML image"); ?></label>
                <input name="tfVmrlImage" class="form-control fc-fl" type="text" id="tfVmrlImage" value="<?php @val($FIELDS['vrml_image']); ?>">
            </div>
            <div class="ccm-row">
                <label for="tfNotesURL"><?php echo _("Notes URL"); ?></label>
                <input name="tfNotesURL" class="form-control fc-fl" type="text" id="tfNotesURL" value="<?php @val(htmlentities($FIELDS['notes_url'])); ?>">
            </div>
            <div class="ccm-row">
                <label for="tfStatusImage"><?php echo _("Status image"); ?></label>
                <input name="tfStatusImage" class="form-control fc-fl" type="text" id="tfStatusImage" value="<?php @val($FIELDS['statusmap_image']); ?>">
            </div>
            <div class="ccm-row">
                <label for="tfActionURL"><?php echo _("Action URL"); ?></label>
                <input name="tfActionURL" class="form-control fc-fl" type="text" id="tfActionURL" value="<?php @val(htmlentities($FIELDS['action_url'])); ?>">
            </div>
            <div class="ccm-row">
                <label for="tfIconImage"><?php echo _("Icon image"); ?></label>
                <input name="tfIconImage" class="form-control fc-fl" type="text" id="tfIconImage" value="<?php @val($FIELDS['icon_image']); ?>">
            </div>
            <div class="ccm-row">
                <label for="tfIconImageAlt"><?php echo _("Icon image 'alt' text"); ?></label>
                <input name="tfIconImageAlt" class="form-control fc-fl" type="text" id="tfIconImageAlt" value="<?php @val($FIELDS['icon_image_alt']); ?>">
            </div>
        </div>
        <div class="rightBox">
            <div class="ccm-row">
                <label for="tf2DCoords"><?php echo _("2D coords"); ?> </label>
                <input name="tfD2Coords" class="form-control fc-fl" type="text" id="tfD2Coords" placeholder="x,y" value="<?php @val($FIELDS['2d_coords']); ?>">
            </div>
            <div class="ccm-row spacer">
                <label for="tfD3Coords"><?php echo _("3D coords"); ?></label>
                <input name="tfD3Coords" class="form-control fc-fl" type="text" id="tfD3Coords" placeholder="x,y,z" value="<?php @val($FIELDS['3d_coords']); ?>">
            </div>
            <?php
            } // End if a Contact or Contact Template

            $fv = count($FIELDS['freeVariables']);
            ?>
            <div class="ccm-row spacer">
                <a class="btn btn-sm btn-info" href="javascript:overlay('variableBox')"><i class="fa fa-list"></i> <?php echo _("Manage Free Variables"); ?> <span class="badge"><?php echo $fv; ?></span></a>
            </div>

            <div class="ccm-row">
                <?php
                // If a Host, Service, or a Contact
                if ($this->exactType == 'host' || $this->exactType == 'service' || $this->exactType == 'contact') {
                ?>           
                <div><strong><?php echo _("Use this configuration as a template"); ?></strong></div>
                <label for="tfGenericName"><?php echo _("Generic name"); ?></label>
                <input type="text" class="form-control fc-fl" name="tfGenericName" id="tfGenericName" value="<?php @val($FIELDS['name']); ?>">
                <?php 
                }
                ?>
            </div>
        </div>
        <div class="clear"></div>
    </div>
    <!-- End of Tab 4 -->