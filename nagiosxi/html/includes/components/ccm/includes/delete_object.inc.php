<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: delete_object.inc.php
//  Desc: Handles the deletion of objects.
//

/**
 * Deletes a single object configuration from the nagiosql database, also removes relations
 *
 * @param string  $table       the appropriate object database table
 * @param int     $id          the object id/primary key for the nagios object
 * @param bool    $audit
 *
 * @global object $myDataClass nagiosql data handler
 * @return array array(int $intReturn, string $strMessage) return data for browser output
 */
function delete_object($table, $id, $audit=false) 
{
    global $myDataClass;
    global $ccmDB;
    global $myDBClass;
    global $myConfigClass;

    // Bail if missing id 
    if (!$id) {
        return _("Cannot delete data, no object ID specified!")."<br />";
    }

    if ($table == 'log') { $table = 'logbook'; }
    $strMessage = '';

    $intReturn = $myDataClass->dataDeleteFull("tbl_".$table, $id, 0, $audit);
    $strMessage .= $myDataClass->strDBMessage;
    if ($audit) {
        audit_log(AUDITLOGTYPE_DELETE, $strMessage);
    }
    
    // If the above row delete was successful, remove config files for things that may need to be rewritten
    if ($intReturn == 0) {
        foreach($myDataClass->arrDBIds as $data) {
            if ($data[0] == "tbl_host" || $data[0] == "tbl_service") {
                $strUpdSQL = "UPDATE `".$data[0]."` SET `last_modified`=NOW() WHERE `id` = '".$data[1]."' ";
                $intUpdate = $myDataClass->dataInsert($strUpdSQL, $intInsertId);
                if ($intUpdate != 0) {
                    $myDataClass->writeLog(_('Problem detected updating object name on relative: '.$data[0].'('.$data[1].')')." ".$chkInsName);
                }
            }
        }
    }

    // Return success or failure message 
    return array($intReturn, $strMessage); 
}

/**
 * Deletes multiple object configurations from the nagiosql database, also removes relations
 *
 * @param string $table the appropriate object database table
 * @global array $_REQUEST['checked'] array of $ids of the objects, id/primary key for the nagios object 
 *
 * @return array array(int $intReturn, string $strMessage) return data for browser output 
*/ 
function delete_multi($table)
{
    $checks = ccm_grab_request_var('checked', array());
    $failMessage= '';
    $itemsDeleted = 0;
    $itemsFailed = 0;
    
    foreach ($checks as $c) {
        $r = delete_object($table, $c, false);
        if ($r[0] == 0) {
            $itemsDeleted++;
        } else {
            $itemsFailed++;
            $failMessage .= $r[1]; // Append DB return messages  
        }
    }
    
    $intReturn = 0;
    $returnMessage = '';
    if ($itemsFailed == 0 && $itemsDeleted == 0) { $returnMessage .= _("No items were deleted from the database.")."<br />"; }
    if ($itemsDeleted > 0) { $returnMessage .= $itemsDeleted." "._("items deleted from database")."<br />"; }
    if ($itemsFailed > 0) {
        $returnMessage .= "<strong>".$itemsFailed." "._("items failed to delete.")."</strong><br />
                                                    "._("Items may have dependent relationships that prevent deletion").".<br /> 
                                                    "._("Use the 'info'  button to see all relationships.")."
                                                    <img src='/nagiosql/images/info.gif' alt='' /><br />   
                                                    $failMessage"; 
        $intReturn = 1;     
    }   
    
    // Audit log
    if ($itemsDeleted > 0) {
        audit_log(AUDITLOGTYPE_DELETE, $returnMessage);
    }
    
    // Return success or failure message 
    return array($intReturn, $returnMessage); 
}