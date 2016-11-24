<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: group.inc.php
//  Desc: Handles submissions (modify/insert) of group objects (Host, Service, Contact) in the CCM.
//

/**
 * Handles form submissions for hostgroup, contactgroup, and servicegroup object configurations 
 *
 * @global object $myDataClass nagiosql data handler
 * @global object $myConfigClass nagiosql config handler   
 * @global object $myDBClass nagiosql database handler
 * @return array array(int $returnCode,string $returnMessage) return output for browser
 */
function process_ccm_group()
{
    global $myDataClass;
    global $myConfigClass;
    global $myDBClass;
    $strMessage = "";
    $errors = 0;

    // Process form variables 
    $chkModus = ccm_grab_request_var('mode');
    $chkDataId = ccm_grab_request_var('hidId');
    $exactType = ccm_grab_request_var('exactType');
    $genericType = ccm_grab_request_var('genericType');
    $ucType = ucfirst($exactType);
    $chkTfName = ccm_grab_request_var('tfName', '');
    $chkTfFriendly = ccm_grab_request_var('tfFriendly', '');
    
    // Changed from chckSelMembers 
    $chkSelHostMembers = ccm_grab_request_var('hosts', array(''));
    $chkSelHostMembersExc = ccm_grab_request_var('hosts_exc', array(''));
    $chkSelHostgroupMembers = ccm_grab_request_var('hostgroups', array(""));
    $chkSelHostgroupMembersExc = ccm_grab_request_var('hostgroups_exc', array(""));
    $chkSelServicegroupMembers = ccm_grab_request_var('servicegroups', array(""));
    $chkSelHostServiceMembers = ccm_grab_request_var('hostservices', array());
    
    // Contactgroup specific vars    
    $chkSelContactMembers = ccm_grab_request_var('contacts', array(''));
    $chkSelContactgroupMembers = ccm_grab_request_var('contactgroups', array(''));
    
    $chkTfNotes = ccm_grab_request_var('tfNotes', '');
    $chkTfNotesURL = ccm_grab_request_var('tfNotesURL', '');
    $chkTfActionURL = ccm_grab_request_var('tfActionURL', '');
    $chkActive = ccm_grab_request_var('chbActive', 0);  

    $chkDomainId = $_SESSION['domain'];

    // Handle Lists
    // =================
    //determine host memberships 
    if ($chkSelHostMembers[0] == "" || $chkSelHostMembers[0] == "0")     $intSelHostMembers = 0;       
    else $intSelHostMembers = 1;
    if (in_array("*",$chkSelHostMembers))     $intSelHostMembers = 2;
    
    //determine service memberships 
    if (count($chkSelHostServiceMembers) == 0)     $intSelHostServiceMembers = 0;       
    else $intSelHostServiceMembers = 1;
    if (is_array($chkSelHostServiceMembers) && in_array("*",$chkSelHostServiceMembers))     $intSelHostServiceMembers = 2;
    
    
    //determine hostgroup memberships 
    if ($chkSelHostgroupMembers[0] == ""  || $chkSelHostgroupMembers[0] == "0") $intSelHostgroupMembers = 0;  
    else $intSelHostgroupMembers = 1;
    if (in_array("*",$chkSelHostgroupMembers))  $intSelHostgroupMembers = 2;
    
    
    //determine servicegroup memberships 
    if ($chkSelServicegroupMembers[0] == ""  || $chkSelServicegroupMembers[0] == "0") $intSelServicegroupMembers = 0;  
    else $intSelServicegroupMembers = 1;
    if (in_array("*",$chkSelServicegroupMembers))  $intSelServicegroupMembers = 2;
    
    //determine contact memberships
    if ($chkSelContactMembers[0] == ""  || $chkSelContactMembers[0] == "0") $intSelContactMembers = 0;  
    else $intSelContactMembers = 1;
    if (in_array("*",$chkSelContactMembers))  $intSelContactMembers = 2;    
    
    //determine contactgroup memberships 
    if ($chkSelContactgroupMembers[0] == ""  || $chkSelContactgroupMembers[0] == "0") $intSelContactgroupMembers = 0;  
    else $intSelContactgroupMembers = 1;
    if (in_array("*",$chkSelContactgroupMembers))  $intSelContactgroupMembers = 2;  
    
    
    
    // Build SQL Query based on mode and object type 
    if (($chkModus == "insert") || ($chkModus == "modify")) 
    {
      
      $strSQLx = "`tbl_{$exactType}` SET `{$exactType}_name`='$chkTfName', `alias`='$chkTfFriendly', 
                        `active`='$chkActive', `config_id`=$chkDomainId, `last_modified`=NOW(), ";
      
      if($exactType != 'contactgroup') $strSQLx .="`notes`='$chkTfNotes', `notes_url`='$chkTfNotesURL',
            `action_url`='$chkTfActionURL', ";
      
      if($exactType=='hostgroup') $strSQLx .= "`members`=$intSelHostMembers,`{$exactType}_members`=$intSelHostgroupMembers";
      if($exactType=='servicegroup') $strSQLx .= "`members`=$intSelHostServiceMembers,`{$exactType}_members`=$intSelServicegroupMembers";
      if($exactType=='contactgroup') $strSQLx .= "`members`=$intSelContactMembers,`{$exactType}_members`=$intSelContactgroupMembers";
            
      if ($chkModus == "insert") 
      {
        $strSQL = "INSERT INTO ".$strSQLx;
        $intInsert = $myDataClass->dataInsert($strSQL,$intInsertId);
      } 
      else //mode is modify 
      {
        $strSQL = "UPDATE ".$strSQLx." WHERE `id`=$chkDataId";
          
                //echo "QUERY IS: <br />".$strSQL;    
          
          //if all required fields are present, continue 
          //if (($chkTfName != "") && ($chkTfFriendly != "") && (($intSelHostMembers != 0) || ($intVersion == 3))) 
          //{
            
        // Save original chkTfName so that we can check if it has changed
        $strRelSQL  = "SELECT `id`,`".$exactType."_name` FROM `tbl_".$exactType."` WHERE `id` = '$chkDataId' ";
        $myDBClass->getDataArray($strRelSQL,$arrData,$intDataCount);
        $chkTfOldName = $arrData[0][''.$exactType.'_name'];
        // Run relation check to generate arrDBIds (array of related table,ids)
        $myDataClass->infoRelation("tbl_".$exactType,$chkDataId,"id");
        
        $intInsert = $myDataClass->dataInsert($strSQL,$intInsertId);
        
        // If the above insert succeeded and the config value used for the config
        // file name has changed,  iterate through the relation table/ids
        // updating the last_modified time on related hosts and services so
        // the CCM can update those files
        if ($intInsert == 0 && $chkTfOldName != $chkTfName) {
            foreach($myDataClass->arrDBIds as $data) {
                if ($data[0] == "tbl_host" || $data[0] == "tbl_service") {
                    $strUpdSQL  = "UPDATE `".$data[0]."` SET `last_modified`=NOW() WHERE `id` = '".$data[1]."' ";
                    $intUpdate = $myDataClass->dataInsert($strUpdSQL, $intInsertId);
                        if ($intUpdate != 0) {
                            $myDataClass->writeLog(_('Problem detected updating object name on relative: '.$data[0].'('.$data[1].')')." ".$chkTfName);
                        }
                }
            }   
        }
    }
    
        //bail if initial insert fails 
         if($intInsert > 0) 
         {          
                //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>"; 
                $errors++; 
                $strMessage.=$myDataClass->strDBMessage; 
                return array($errors,$strMessage); 
         } 
        
        if ($chkModus == "insert") 
        {
          $chkDataId = $intInsertId;
        }
        
        if ($intInsert == 1) 
        {
          $intReturn = 1;
        } 
        else 
        {
          if ($chkModus  == "insert")   $myDataClass->writeLog(_('New host group inserted:')." ".$chkTfName);
          if ($chkModus  == "modify")   $myDataClass->writeLog(_('Host group modified:')." ".$chkTfName);
          //
          // Update Relations 
          // ============================
          if ($chkModus == "insert") 
          {
            if($intSelHostMembers  == 1)       $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToHost",$chkDataId,$chkSelHostMembers);
            if($intSelHostgroupMembers  == 1)  $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToHostgroup",$chkDataId,$chkSelHostgroupMembers);
            if($intSelServicegroupMembers == 1)$myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToServicegroup",$chkDataId,$chkSelServicegroupMembers);  
                    
            if($intSelHostServiceMembers == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToService",$chkDataId,$chkSelHostServiceMembers,1);

               if($intSelContactMembers == 1)     $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToContact",$chkDataId,$chkSelContactMembers);
               if($intSelContactgroupMembers == 1)$myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToContactgroup",$chkDataId,$chkSelContactgroupMembers);
                //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";
                            
            //update_sg_to_service_relations($chkModus,$chkDataId,$chkSelHostServiceMembers); 
          } 
     ///////////////////////////////////MODIFY//////////////////////////////////
          else if ($chkModus == "modify") 
          {
            switch($exactType)
            {
            
            case 'hostgroup':
                //host links 
                if ($intSelHostMembers == 1) 
                  $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToHost",$chkDataId,$chkSelHostMembers, 0, $chkSelHostMembersExc);
                else  $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToHost",$chkDataId);
    
                //hostgroup links 
                  //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";
    
                if ($intSelHostgroupMembers == 1) 
                  $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToHostgroup",$chkDataId,$chkSelHostgroupMembers, 0, $chkSelHostgroupMembersExc);
                else  $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToHostgroup",$chkDataId);
    
                  //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";
                break; //end 'hostgroup' case
    ////////////////////////////////////////////////////////
                case 'servicegroup':
    
                    //servicegroup links            
                   if ($intSelServicegroupMembers == 1) 
                      $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToServicegroup",$chkDataId,$chkSelServicegroupMembers);
                   else  $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToServicegroup",$chkDataId);     
                    
                    //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";  
                    //service links             
                   if ($intSelHostServiceMembers == 1) 
                      $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToService",$chkDataId,$chkSelHostServiceMembers,1);
                   else  $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToService",$chkDataId);         
                    
                    //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";   
               
             break;    
             case 'contactgroup':
                 //contact member links             
                   if ($intSelContactMembers == 1) 
                      $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToContact",$chkDataId,$chkSelContactMembers);
                   else  $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToContact",$chkDataId);      
                    
                     //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";
                     
                     //contactgroup links 
                     if($intSelContactgroupMembers == 1) 
                      $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToContactgroup",$chkDataId,$chkSelContactgroupMembers);
                   else  $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToContactgroup",$chkDataId);
                   
                //print "<p>SQL Response: ".mysql_error()."<br /> Rows affected: ".$myDBClass->intAffectedRows."</p>";  
                
             break;
                default:
                break;
            
                } //END SWITCH          
            
          }//end modify IF 
          $intReturn = 0;
        }

    }
     
    // log return status and send back to page router 
    if (isset($intReturn) && ($intReturn == 1)) $strMessage .= $myDataClass->strDBMessage;
    if (isset($intReturn) && ($intReturn == 0)) $strMessage .= $ucType." <strong>".$chkTfName."</strong>"._(" sucessfully updated") . ".";
    //
    // Last database update and file date
    // ======================================
    $myConfigClass->lastModified("tbl_".$exactType,$strLastModified,$strFileDate,$strOld);
    
    // Check if there are errors then set the Apply Configuration as needed
    if ($errors == 0) {
        if (ENVIRONMENT == "nagiosxi") {
            set_option("ccm_apply_config_needed", 1);
        }
    }
    
    return array($errors, $strMessage.'<br />');  
}