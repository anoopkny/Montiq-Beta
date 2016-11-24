<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: form_footer.php
//  Desc: Creates the bottom of the Form class, which closes anything that was opened.
//
?>

    </div>
    <!-- end tabs -->

        <div class="bottomButtons">
            <input class="btn btn-sm btn-primary" name="subForm" type="button" id="subForm1" value="<?php echo _("Save"); ?>">
            <input class="btn btn-sm btn-default" name="subAbort" type="button" id="subAbort1" onclick="abort('<?php echo $FIELDS['exactType']; ?>','<?php echo $FIELDS['returnUrl']; ?>')" value="<?php echo _("Cancel"); ?>">

            <input name="cmd" type="hidden" id="cmd" value="submit">
            <input name="mode" type="hidden" id="mode" value="<?php print $FIELDS['mode']; ?>">
            <input name="hidId" type="hidden" id="hidId" value="<?php print $FIELDS['hidId']; ?>">
            <input name="hidName" type="hidden" id="hidName" value="<?php print $FIELDS['hidName']; ?>">
            <input name="hostAddress" type="hidden" id="hostAddress" value="<?php print $FIELDS['hostAddress']; ?>">
            <input name="exactType" type="hidden" id="exactType" value="<?php print $FIELDS['exactType']; ?>">
            <input name="type" type="hidden" id="type" value="<?php print $FIELDS['exactType']; ?>">
            <input name="genericType" type="hidden" id="genericType" value="<?php print $FIELDS['genericType']; ?>"> 
            <input name="returnUrl" type="hidden" id="returnUrl" value="<?php print $FIELDS['returnUrl'] ?>">
            <input name="token" id="token" type="hidden" value="<?php print $_SESSION['token']; ?>">
        </div>
    </form>
<!-- End form -->

</div>
<!-- End main wrapper -->