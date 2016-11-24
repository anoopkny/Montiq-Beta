<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: hostservice.inc.php
//  Desc: Functions for processing submitting changes/new host/service and host/service
//        template objects.
//

/**
 * Handles form submissions for all host, service, host tempate, and service template object
 * configurations from our submitted forms.
 *
 * @global object $myDataClass nagiosql data handler
 * @global object $myConfigClass nagiosql config handler   
 * @global object $myDBClass nagiosql database handler
 * @return array array(int $returnCode,string $returnMessage) return output for browser
 */
function process_ccm_submission()
{
    global $myDataClass;
    global $myConfigClass;
    global $myDBClass;
        
    // Declaring variables
    $strMessage = "";
    $errors = 0;
    
    // Request variables
    $chkModus = ccm_grab_request_var('mode');
    $chkDataId = ccm_grab_request_var('hidId');
    $exactType = ccm_grab_request_var('exactType');
    $genericType = ccm_grab_request_var('genericType');
    $ucType = ucfirst($exactType);
    
    //grabbing all $_REQUEST variables 
    // =================
    $chkTfSearch      = ccm_grab_request_var('txtSearch');
    $chkTfName        = trim(ccm_grab_request_var('tfName',''));
    $chkOldHost       = ccm_grab_request_var('hidName','');
    $chkServiceDesc   = ccm_grab_request_var('tfServiceDescription', '');

    $chkTfFriendly    = ccm_grab_request_var('tfFriendly','');
    $chkTfDisplay     = ccm_grab_request_var('tfDisplayName','');
    $chkTfAddress     = ccm_grab_request_var('tfAddress','') ;
    $chkTfGenericName = ccm_grab_request_var('tfGenericName','');
    
    //host assignsments 
    $chkSelHosts        = ccm_grab_request_var('hosts', array(""));
    $chkSelHostsExc     = ccm_grab_request_var('hosts_exc', array());
    $chkRadHosts        = ccm_grab_request_var('radHost', 2);        

    //servicegroups
    $chkSelServiceGroups    = ccm_grab_request_var('servicegroups', array(""));
    $chkRadServiceGroups    = ccm_grab_request_var('radServicegroup',2); 
    //$chkSelParents      = ccm_grab_request_var('selParents'])       ? $_POST['selParents']            : array("");
    $chkSelParents    = ccm_grab_request_var('parents', array(''));    
    $chkRadParent     = ccm_grab_request_var('radParent', 2); 
    //$chkSelHostGroups     = ccm_grab_request_var('selHostGroups'])    ? $_POST['selHostGroups']           : array("");
    $chkSelHostGroups = ccm_grab_request_var('hostgroups', array(''));
    $chkSelHostGroupsExc = ccm_grab_request_var('hostgroups_exc', array(''));
    $chkRadHostGroups = ccm_grab_request_var('radHostgroup', 2);
    $chkSelHostCommand= ccm_grab_request_var('selHostCommand',0);
    $chkTfArg1        = ccm_grab_request_var('tfArg1','');
    $chkTfArg2        = ccm_grab_request_var('tfArg2','');
    $chkTfArg3        = ccm_grab_request_var('tfArg3','');
    $chkTfArg4        = ccm_grab_request_var('tfArg4','');
    $chkTfArg5        = ccm_grab_request_var('tfArg5','');
    $chkTfArg6        = ccm_grab_request_var('tfArg6','');
    $chkTfArg7        = ccm_grab_request_var('tfArg7','');
    $chkTfArg8        = ccm_grab_request_var('tfArg8','');
    $chkRadTemplates  = ccm_grab_request_var('radTemplate',2);   

    //TODO - fix this, logic won't be right 
    $chkTfRetryInterval     = checkNull(ccm_grab_request_var('tfRetryInterval', 'NULL'));   // && ($_POST['tfRetryInterval'] != ""))   ? $myVisClass->checkNull($_POST['tfRetryInterval'])   : "NULL";
    $chkTfMaxCheckAttempts  = checkNull(ccm_grab_request_var('tfMaxCheckAttempts', 'NULL')); 
    $chkTfCheckInterval     = checkNull(ccm_grab_request_var('tfCheckInterval', 'NULL')); 
    
    //Active/Passive checks 
    $chkActiveChecks    = ccm_grab_request_var('radActiveChecksEnabled',2);
    $chkPassiveChecks   = ccm_grab_request_var('radPassiveChecksEnabled',2);  
    $chkSelCheckPeriod    = ccm_grab_request_var('selCheckPeriod',2);    
    $chkTfFreshTreshold   = checkNull(ccm_grab_request_var('tfFreshThreshold','NULL') );   
    $chkFreshness       = ccm_grab_request_var('radFreshness',2);
    $chkObsess          = ccm_grab_request_var('radObsess',2); 
    $chkSelEventHandler = checkNull( ccm_grab_request_var('selEventHandler','NULL')  );
    $chkEventEnable     = ccm_grab_request_var('radEventEnable',2); 
    $chkTfLowFlat       = checkNull( ccm_grab_request_var('tfLowFlat','NULL') );    
    $chkTfHighFlat      = checkNull(ccm_grab_request_var('tfHighFlat', 'NULL') ) ;
    $chkFlapEnable      = ccm_grab_request_var('radFlapEnable',2); 
    $chkIsVolatile      = ccm_grab_request_var('radIsVolatile',2); 
    
    //////////////////////////////////////////////////////////////////////
    //options checkboxes: flapping, stalking, notification options  
    $strFL = get_FL_string($exactType); 
    $strST = get_ST_string($exactType);
    $strNO = get_NO_string($exactType); 
    
    $strIS = (ccm_grab_request_var('radIS','') =='') ? '' : ccm_grab_request_var('radIS');
    //retain status 
    $chkStatusInfos     = intval(ccm_grab_request_var('radStatusInfos',2)); 
    $chkNonStatusInfos  = intval(ccm_grab_request_var('radNoStatusInfos',2)); 
    $chkPerfData        = intval(ccm_grab_request_var('radPerfData',2)); 
    
    //contacts 
    $chkSelContacts       = ccm_grab_request_var('contacts', array(''));  
    $chkSelContactGroups  = ccm_grab_request_var('contactgroups', array('') );   
    $chkRadContacts       = intval(ccm_grab_request_var('radContact',2));
    $chkRadContactGroups  = intval(ccm_grab_request_var('radContactgroup',2)); 
    
    //notifications 
    $chkSelNotifPeriod  = ccm_grab_request_var('selNotifPeriod',2)+0; 
    $chkNotifInterval   = checkNull( ccm_grab_request_var('tfNotifInterval', 'NULL') );  
    $chkNotifDelay      = checkNull( ccm_grab_request_var('tfFirstNotifDelay', 'NULL') );
    $chkNotifEnabled    = ccm_grab_request_var('radNotifEnabled',2);
    
    // misc settings 
    $chkTfNotes         = ccm_grab_request_var('tfNotes','');
    $chkTfVmrlImage     = ccm_grab_request_var('tfVmrlImage','');
    $chkTfNotesURL      = ccm_grab_request_var('tfNotesURL','');
    $chkTfStatusImage   = ccm_grab_request_var('tfStatusImage','');
    $chkTfActionURL     = ccm_grab_request_var('tfActionURL','');
    $chkTfIconImage     = ccm_grab_request_var('tfIconImage','');
    $chkTfD2Coords      = ccm_grab_request_var('tfD2Coords','');
    $chkTfIconImageAlt  = ccm_grab_request_var('tfIconImageAlt','');
    $chkTfD3Coords      = ccm_grab_request_var('tfD3Coords','');
    //active? 
    $chkActive              = ccm_grab_request_var('chbActive',0);  
    //hidden debugger 
    //$showsql = ccm_grab_request_var
    

    // Check for templates 
    // =================================
    $templates = ccm_grab_request_var('templates',array()); 
    //are templates being used? 
    $intTemplates = (count($templates) > 0) ? 1 : 0;  
    
    //check for Free Variables 
    // ================================     
    $variables = ccm_grab_request_var('variables', array()) ; 
    $definitions = ccm_grab_request_var('variabledefs', array()); 
    
    //ccm_array_dump($definitions);
    
    //freeform variables being used?  
    $intVariables = (count($variables) ) > 0 ? 1 : 0;  
    
    //domain ID for now 
    $chkDomainId = $_SESSION['domain']; //domain is localhost 

    // Data post-processing
    // =================
    //if ($chkISnull == "") {$strIS = substr($chkISo.$chkISd.$chkISu,0,-1);} else {$strIS = "null";}
    //if ($chkFLnull == "") {$strFL = substr($chkFLo.$chkFLd.$chkFLu,0,-1);} else {$strFL = "null";}
    //if ($chkNOnull == "") {$strNO = substr($chkNOd.$chkNOu.$chkNOr.$chkNOf.$chkNOs,0,-1);} else {$strNO = "null";}
    //if ($chkSTnull == "") {$strST = substr($chkSTo.$chkSTd.$chkSTu,0,-1);} else {$strST = "null";}
    
    if (($chkSelParents[0] == "")     || ($chkSelParents[0] == "0"))     {$intSelParents = 0;}     else {$intSelParents = 1;}
    if (($chkSelHostGroups[0] == "")    || ($chkSelHostGroups[0] == "0"))    {$intSelHostGroups = 0;}    else {$intSelHostGroups = 1;}
    if (($chkSelContacts[0] == "")    || ($chkSelContacts[0] == "0"))    {$intSelContacts = 0;}    else {$intSelContacts = 1;}
    if (in_array("*",$chkSelContacts))        $intSelContacts = 2;
    if (($chkSelContactGroups[0] == "") || ($chkSelContactGroups[0] == "0")) {$intSelContactGroups = 0;} else {$intSelContactGroups = 1;}
    if (in_array("*",$chkSelContactGroups))     $intSelContactGroups = 2;
        //service relationships 
    if (($chkSelHosts[0] == "")       || ($chkSelHosts[0] == "0"))  {$intSelHosts = 0;}     else {$intSelHosts = 1;}
    if (in_array("*",$chkSelHosts))     $intSelHosts = 2;
    if (($chkSelServiceGroups[0] == "") || ($chkSelServiceGroups[0] == "0"))  {$intSelServiceGroups = 0;} else {$intSelServiceGroups = 1;}

    // Check Command compile
    $strCheckCommand = $chkSelHostCommand;
    if ($chkSelHostCommand != "") {
      for ($i=1;$i<=8;$i++) {
        // XI MOD 02-10-2010 EG - Added support for empty $ARGx$ macros
        $strCheckCommand .= "!".${"chkTfArg$i"};
        /*
        if (${"chkTfArg$i"} != "") $strCheckCommand .= "!".${"chkTfArg$i"};
        */   
      }
    }
        
    
        
    /////////////////////////////////////INSERT/MODIFY/////////////////////////////////// 
        
    // Modify or add files
    if (($chkModus == "insert") || ($chkModus == "modify")) 
    {
    
    // validate Active
    if($chkActive == 0){
        include_once(INCDIR.'activate.inc.php');
        $returnContent = can_be_deactivated($chkDataId,$exactType,$chkActive);  
        
        if($returnContent[0] != 0){ 
                 return $returnContent; 
            }
        }
        $table = 'tbl_'.$exactType;     
        //begin SQL query build 
      $strSQLx = "`$table` SET ";
      //define field entries based on $exactType 
      //host specific
      if($exactType=='host') $strSQLx .= "`host_name`='$chkTfName', `alias`='$chkTfFriendly', `address`='$chkTfAddress', 
                                            `parents`=$intSelParents, `parents_tploptions`=$chkRadParent, \n";
      //hosttemplate specific 
      if($exactType=='hosttemplate' ) 
            $strSQLx.=" `parents`=$intSelParents, `parents_tploptions`=$chkRadParent,`alias`='$chkTfFriendly',";                                                    
      //template specific 
      if($exactType=='hosttemplate' || $exactType=='servicetemplate') 
            $strSQLx .= "`template_name`='$chkTfName',\n";    
      //display name field 
      if($exactType=='host' || $exactType=='service' || $exactType=='servicetemplate') $strSQLx .="`display_name`='$chkTfDisplay',\n";
      
      if($exactType=='service') $strSQLx .="`config_name`='$chkTfName',\n";  
      if($exactType=='service' || $exactType=='servicetemplate') $strSQLx .="`service_description`='$chkServiceDesc',"; 
      
      //common fields 
    //  $strSQLx.="
      //      `name`='$chkTfGenericName', ";
            
      if($exactType=='host' || $exactType =='hosttemplate')
        $strSQLx.= "`hostgroups`=$intSelHostGroups, `hostgroups_tploptions`=$chkRadHostGroups, `obsess_over_host`=$chkObsess,\n";   
                
      if($exactType=='service' || $exactType =='servicetemplate')
      {
            $strSQLx.= "`hostgroup_name`=$intSelHostGroups, `hostgroup_name_tploptions`=$chkRadHostGroups,\n
                            `servicegroups`=$intSelServiceGroups, `servicegroups_tploptions`=$chkRadServiceGroups, 
                                `host_name`='$intSelHosts', `host_name_tploptions`='$chkRadHosts', `is_volatile`=$chkIsVolatile, `obsess_over_service`=$chkObsess, ";
       }
        
      $strSQLx .="  
            `check_command`='$strCheckCommand', `use_template`=$intTemplates,\n
            `use_template_tploptions`=$chkRadTemplates, `initial_state`='$strIS', `max_check_attempts`=$chkTfMaxCheckAttempts,\n
            `check_interval`=$chkTfCheckInterval, `retry_interval`=$chkTfRetryInterval, `active_checks_enabled`=$chkActiveChecks,\n
            `passive_checks_enabled`=$chkPassiveChecks, `check_period`=$chkSelCheckPeriod, \n
            `check_freshness`=$chkFreshness, `freshness_threshold`=$chkTfFreshTreshold, `event_handler`=$chkSelEventHandler,\n
            `event_handler_enabled`=$chkEventEnable, `low_flap_threshold`=$chkTfLowFlat, `high_flap_threshold`=$chkTfHighFlat,\n
            `flap_detection_enabled`=$chkFlapEnable, `flap_detection_options`='$strFL', `process_perf_data`=$chkPerfData,\n
            `retain_status_information`=$chkStatusInfos, `retain_nonstatus_information`=$chkNonStatusInfos,`contacts`=$intSelContacts,\n
            `contacts_tploptions`=$chkRadContacts, `contact_groups`=$intSelContactGroups, `contact_groups_tploptions`=$chkRadContactGroups,\n
            `notification_interval`=$chkNotifInterval, `notification_period`=$chkSelNotifPeriod,\n
            `first_notification_delay`=$chkNotifDelay, `notification_options`='$strNO', `notifications_enabled`=$chkNotifEnabled,\n
            `stalking_options`='$strST', `notes`='$chkTfNotes', `notes_url`='$chkTfNotesURL', `action_url`='$chkTfActionURL',\n
            `icon_image`='$chkTfIconImage', `icon_image_alt`='$chkTfIconImageAlt',`active`='$chkActive',`use_variables`=$intVariables,\n
            `config_id`=$chkDomainId, `last_modified`=NOW() \n";
       
      if($exactType=='host' || $exactType=='service')  $strSQLx .=",`name`='$chkTfGenericName' "; 
      //fields for host and hosttemplate  
      if($exactType=='host' || $exactType=='hosttemplate')     
        $strSQLx.=",`vrml_image`='$chkTfVmrlImage',`statusmap_image`='$chkTfStatusImage', `2d_coords`='$chkTfD2Coords', `3d_coords`='$chkTfD3Coords' \n";

    // Define $strSQL
    if ($chkModus == "insert") {
        $strSQL = "INSERT INTO ".$strSQLx;
    } else {
        $strSQL = "UPDATE ".$strSQLx." WHERE `id`=$chkDataId";
    }

    if ($chkTfName != "") {
        if ($chkModus != "insert" && ($exactType == 'hosttemplate' || $exactType == 'servicetemplate')) {

            // Save original chkTfName so that we can check if it has changed
            $strRelSQL  = "SELECT `id`,`template_name` FROM `tbl_".$exactType."` WHERE `id` = '$chkDataId' ";
            $myDBClass->getDataArray($strRelSQL, $arrData, $intDataCount);
            $chkTfOldName = $arrData[0]['template_name'];

            // Run relation check to generate arrDBIds (array of related table,ids)
            $myDataClass->infoRelation("tbl_".$exactType, $chkDataId, "id");

            $intInsert = $myDataClass->dataInsert($strSQL, $intInsertId);

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
        } else {
            $intInsert = $myDataClass->dataInsert($strSQL, $intInsertId);
        }

        if ($intInsert > 0) {
            $strMessage .= $myDataClass->strDBMessage;
            $errors++;
            return array($errors, $strMessage);
        }

        if ($chkModus == "insert") {
            $chkDataId = $intInsertId;
        }
        
        if ($intInsert == 1) {
            $strMessage = $myDataClass->strDBMessage;
            $intReturn = 1;
        } else {
            if ($chkModus == "insert") $myDataClass->writeLog(_('New '.$exactType.' inserted:')." ".$chkTfName);
            if ($chkModus == "modify") $myDataClass->writeLog(_($ucType.' modified:')." ".$chkTfName);
         
            // Create table relations on insert
            if ($chkModus == "insert") {

                // Service
                if ($exactType == 'service' || $exactType == 'servicetemplate') {
                    if ($intSelServiceGroups == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToServicegroup", $chkDataId, $chkSelServiceGroups);
                    if ($intSelHosts == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToHost", $chkDataId, $chkSelHosts, 0, $chkSelHostsExc);
                }

                // Host
                if (($exactType == 'host' || $exactType == 'hosttemplate' ) && $intSelParents == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToHost", $chkDataId, $chkSelParents);
                if ($intSelHostGroups == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToHostgroup", $chkDataId, $chkSelHostGroups, 0, $chkSelHostGroupsExc);
                if ($intSelContacts == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToContact", $chkDataId, $chkSelContacts);
                if ($intSelContactGroups == 1) $myDataClass->dataInsertRelation("tbl_lnk".$ucType."ToContactgroup", $chkDataId, $chkSelContactGroups);

            // Modify table relations on update
            } else if ($chkModus == "modify") { 

                if ($exactType == 'host' || $exactType == 'hosttemplate') {
                    // Parents
                    if ($intSelParents == 1) $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToHost", $chkDataId, $chkSelParents);                                    
                    else $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToHost", $chkDataId);
                }

                // Hostgroups 
                if ($intSelHostGroups == 1) $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToHostgroup", $chkDataId, $chkSelHostGroups, 0, $chkSelHostGroupsExc);
                else $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToHostgroup", $chkDataId);
       
                // Contacts 
                if ($intSelContacts == 1) $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToContact", $chkDataId, $chkSelContacts);
                else $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToContact", $chkDataId);

                // Contact groups
                if ($intSelContactGroups == 1) $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToContactgroup", $chkDataId, $chkSelContactGroups);
                else $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToContactgroup", $chkDataId);
            
                // Services
                if ($exactType == 'service' || $exactType == 'servicetemplate') {
                    
                    // Hosts
                    if ($intSelHosts == 1) $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToHost", $chkDataId, $chkSelHosts, 0, $chkSelHostsExc);
                    else $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToHost", $chkDataId);

                    // Servicegroups 
                    if ($intSelServiceGroups == 1) $myDataClass->dataUpdateRelation("tbl_lnk".$ucType."ToServicegroup", $chkDataId, $chkSelServiceGroups);
                    else $myDataClass->dataDeleteRelation("tbl_lnk".$ucType."ToServicegroup", $chkDataId);
                }
            }
          
            // ------------------------------------
            // If the host/config name was changed, delete old configuration
            // ------------------------------------
            if (($chkModus == "modify") && ($chkOldHost != $chkTfName) && ($exactType == 'host' || $exactType == 'service'))  {
                $intReturn = $myConfigClass->moveFile($exactType, $chkOldHost.".cfg");
                $intReturnNew = $myConfigClass->moveFile($exactType, $chkTfName.".cfg");

                // Need to also delete everything that has a relation to the host or service...
                if ($exactType == "host") {

                    // Get related service names and delete the service config files as needed
                    $strRelSQL = "SELECT tbl_service.config_name FROM tbl_service
LEFT JOIN tbl_lnkServiceToHost
ON tbl_service.id = tbl_lnkServiceToHost.idMaster
LEFT JOIN tbl_host
ON tbl_lnkServiceToHost.idSlave = tbl_host.id
WHERE tbl_host.host_name = '".$chkTfName."'";
                    $myDBClass->getDataArray($strRelSQL, $arrData, $intDataCount);

                    $service_configs = array();
                    foreach ($arrData as $service) {
                        if (!in_array($service['config_name'], $service_configs)) {
                            $service_configs[] = $service['config_name'];
                        }
                    }

                    foreach ($service_configs as $sconfig) {
                        $myConfigClass->moveFile('service', $sconfig.".cfg");
                    }

                    // Get the related child hosts and delete the host config files as needed
                    $strRelSQL = "SELECT tbl_host.host_name FROM tbl_host
LEFT JOIN tbl_lnkHostToHost
ON tbl_host.id = tbl_lnkHostToHost.idMaster
WHERE tbl_lnkHostToHost.idSlave = (SELECT tbl_host.id FROM tbl_host WHERE tbl_host.host_name = '".$chkTfName."');";
                    $myDBClass->getDataArray($strRelSQL, $arrData, $intDataCount);

                    $host_configs = array();
                    foreach ($arrData as $host) {
                        if (!in_array($host['host_name'], $host_configs)) {
                            $host_configs[] = $host['host_name'];
                        }
                    }

                    foreach ($host_configs as $hconfig) {
                        $myConfigClass->moveFile('host', $hconfig.".cfg");
                    }
                }

                if ($intReturn == 0 &&  $intReturnNew == 0) {
                    $strMessage .= '<div>'._('The assigned, no longer used configuration files were deleted successfully!').'</div>';
                    $myDataClass->writeLog(_('Configuration file deleted:')." ".$chkOldHost.".cfg");
                } else {
                    $strMessage .= '<div>'._('Errors while deleting the old configuration file - please check!:')."</div>".$myConfigClass->strDBMessage;
                    $errors++;
                }
            }

            // ------------------------------------
            // If the host has been disabled - delete file
            // ------------------------------------
            if (($chkModus == "modify") && ($chkActive == 0) && ($exactType == 'host' || $exactType == 'service')) {
                $moveType = $exactType; 
                $cfg = $chkTfName.".cfg";

                $intReturn = $myConfigClass->moveFile($moveType, $cfg);
                if ($intReturn == 0) {
                    $strMessage .=  _('The assigned, no longer used configuration files were deleted successfully!<br />');
                    $myDataClass->writeLog(_('Config file deleted:')." ".$cfg);
                } else {
                    $strMessage .=  _('Errors while deleting the old configuration file: '.$cfg.' - please check permissions!')."<br>".$myConfigClass->strDBMessage;
                    $errors++; 
                }
            }

            // ------------------------------------
            // Update template relationships 
            // ------------------------------------
            $tblTemplate = ($exactType =='hosttemplate' || $exactType == 'host') ? 'Hosttemplate' : 'Servicetemplate';
          
            // Clear out previous template relationships
            if ($chkModus == "modify") {
                $strSQL = "DELETE FROM `tbl_lnk".$ucType."To".$tblTemplate."` WHERE `idMaster`=$chkDataId";
                $booReturn = $myDataClass->dataInsert($strSQL, $intInsertId);
            }
          
            if ($intTemplates = 1) {
            
                // Increment counter
                $intSortId = 1;

                // Templates are passed as $_SESSION vars
                /*
                    Template array needs:
                    $chkDataId = current host ID 
                    $idtSortId - array index starting at 1 
                    $t['status'] - NO LONGER USED, only active elements will be sent to form  
                    $t['idSlave'] - template ID number 
                    $t['idTable'] - appears to do NOTHING, always == 1 --> done for backwards compatibility?? -> 'template_name' vs 'name'
                */
                foreach ($templates as $elem) {
                    $idTable = 1;

                    if (strpos($elem, '::2')) {
                        $idTable = 2;
                        $elem = str_replace('::2', '', $elem);            
                    }

                    $strSQL = "INSERT INTO `tbl_lnk".$ucType."To".$tblTemplate."` (`idMaster`,`idSlave`,`idTable`,`idSort`)
                       VALUES ($chkDataId, $elem, $idTable , $intSortId)";
                    $booReturn = $myDataClass->dataInsert($strSQL, $intInsertId);   
                    $intSortId++;
                }
            }

            // ------------------------------------
            // Update variable definition relationships 
            // ------------------------------------

            // Clear out old variable definition 
            if ($chkModus == "modify") {
                $strSQL = "SELECT * FROM `tbl_lnk".$ucType."ToVariabledefinition` WHERE `idMaster`=$chkDataId";
                $booReturn = $myDBClass->getDataArray($strSQL, $arrData, $intDataCount);
                if ($intDataCount != 0) {
                    foreach ($arrData AS $elem) {
                        $strSQL = "DELETE FROM `tbl_variabledefinition` WHERE `id`=".$elem['idSlave'];
                        $booReturn = $myDataClass->dataInsert($strSQL, $intInsertId);
                    }
                }
                $strSQL = "DELETE FROM `tbl_lnk".$ucType."ToVariabledefinition` WHERE `idMaster`=$chkDataId";
                $booReturn = $myDataClass->dataInsert($strSQL, $intInsertId);
            }

            // If there are variables to insert...
            if ($intVariables == 1 ) {
                $vars = $variables;
                $defs = $definitions;
                $count = 0;

                for ($i = 0; $i < count($vars); $i++) {
                    $strSQL = "INSERT INTO `tbl_variabledefinition` (`name`,`value`,`last_modified`)
                               VALUES ('{$vars[$i]}','".html_entity_decode($defs[$i])."',now())";
                    $booReturn = $myDataClass->dataInsert($strSQL, $intInsertId);
                    $strSQL = "INSERT INTO `tbl_lnk".$ucType."ToVariabledefinition` (`idMaster`,`idSlave`)
                               VALUES ($chkDataId,$intInsertId)";
                    $booReturn = $myDataClass->dataInsert($strSQL, $intInsertId);
                }
            }
            $intReturn = 0;
        }
        } else {
            $strMessage .= _('Database entry failed! Not all necessary data filled in!');
            $errors++; 
        }
    }

    // Check if there are any errors... if there aren't then let's display the success message
    if ($errors == 0) {
        $strMessage .= _("Database entry for {$exactType} {$chkTfName} successfully updated!");

        if (ENVIRONMENT == "nagiosxi" && ($chkModus == "modify" || $chkModus == "insert")) {
            set_option("ccm_apply_config_needed", 1);
        }
    }

    return array($errors, $strMessage);
}


/**
 * Get flap detection object string
 * @param $type
 * @return string
 */
function get_FL_string($type) {
    if ($type == 'host') {
        $chkFLo = (ccm_grab_request_var('chbFLo','') =='') ? '' : ccm_grab_request_var('chbFLo').',';
        $chkFLd = (ccm_grab_request_var('chbFLd','') =='') ? '' : ccm_grab_request_var('chbFLd').',';
        $chkFLu = (ccm_grab_request_var('chbFLu','') =='') ? '' : ccm_grab_request_var('chbFLu').',';
        $strFL = $chkFLo.$chkFLd.$chkFLu;
    } else {
        $chkFLo = (ccm_grab_request_var('chbFLo','') =='') ? '' : ccm_grab_request_var('chbFLo').',';
        $chkFLw = (ccm_grab_request_var('chbFLw','') =='') ? '' : ccm_grab_request_var('chbFLw').',';
        $chkFLc = (ccm_grab_request_var('chbFLc','') =='') ? '' : ccm_grab_request_var('chbFLc').',';
        $chkFLu = (ccm_grab_request_var('chbFLu','') =='') ? '' : ccm_grab_request_var('chbFLu').',';
        $strFL = $chkFLo.$chkFLw.$chkFLc.$chkFLu;
    }
    return $strFL; 
}


/**
 * Get notification options string
 *
 * @param $type
 * @return string
 */
function get_NO_string($type) {
    if ($type == 'host' || $type == 'hosttemplate') {
        $chkNOd = (ccm_grab_request_var('chbNOd','') =='') ? '' : ccm_grab_request_var('chbNOd').',';
        $chkNOu = (ccm_grab_request_var('chbNOu','') =='') ? '' : ccm_grab_request_var('chbNOu').',';
        $strNO = $chkNOd.$chkNOu;
    } else {
        $chkNOw = (ccm_grab_request_var('chbNOw','') =='') ? '' : ccm_grab_request_var('chbNOw').',';
        $chkNOc = (ccm_grab_request_var('chbNOc','') =='') ? '' : ccm_grab_request_var('chbNOc').',';
        $chkNOu = (ccm_grab_request_var('chbNOu','') =='') ? '' : ccm_grab_request_var('chbNOu').',';
        $chkNOo = (ccm_grab_request_var('chbNOo','') =='') ? '' : ccm_grab_request_var('chbNOo').',';
        $strNO = $chkNOo.$chkNOw.$chkNOc.$chkNOu;
    }
    $chkNOr = (ccm_grab_request_var('chbNOr','') =='') ? '' : ccm_grab_request_var('chbNOr').',';
    $chkNOf = (ccm_grab_request_var('chbNOf','') =='') ? '' : ccm_grab_request_var('chbNOf').',';
    $chkNOs = (ccm_grab_request_var('chbNOs','') =='') ? '' : ccm_grab_request_var('chbNOs').',';
    $strNO .= $chkNOr.$chkNOf.$chkNOs;
    return $strNO;
}


/**
 * Get stalking objects string
 *
 * @param $type
 * @return string
 */
function get_ST_string($type) {
    if ($type == 'host') {
        $chkSTo = (ccm_grab_request_var('chbSTo','') =='') ? '' : ccm_grab_request_var('chbSTo').',';
        $chkSTd = (ccm_grab_request_var('chbSTd','') =='') ? '' : ccm_grab_request_var('chbSTd').',';
        $chkSTu = (ccm_grab_request_var('chbSTu','') =='') ? '' : ccm_grab_request_var('chbSTu').',';
        $strST = $chkSTo.$chkSTd.$chkSTu;
    } else {
        $chkSTo = (ccm_grab_request_var('chbSTo','') =='') ? '' : ccm_grab_request_var('chbSTo').',';
        $chkSTw = (ccm_grab_request_var('chbSTw','') =='') ? '' : ccm_grab_request_var('chbSTw').',';
        $chkSTc = (ccm_grab_request_var('chbSTc','') =='') ? '' : ccm_grab_request_var('chbSTc').',';
        $chkSTu = (ccm_grab_request_var('chbSTu','') =='') ? '' : ccm_grab_request_var('chbSTu').',';
        $strST = $chkSTo.$chkSTw.$chkSTc.$chkSTu;
    }
    return $strST; 
}