<?php
///////////////////////////////////////////////////////////////////////////////
//
// Core Config Manager
//
///////////////////////////////////////////////////////////////////////////////
//
// (c) 2009 - 2014 by Nagios Enterprises, LLC
//
// Project   : CCM
// Component : Data Class
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// Class: Data Manipulation
//
///////////////////////////////////////////////////////////////////////////////////////////////
//
// All of the functions necessary for the manipulation of the configuration data within
// the CCM (NagiosQL) database
//
// Name: nagdata
//
// Class variables:
// -----------------
// $arrSettings:  Multidimensional array with the global configuration settings
// $myDBClass:    Database class object
// $strDBMessage  Releases of the database server
//
// External Functions
// ------------------
// 
//
///////////////////////////////////////////////////////////////////////////////////////////////

class nagdata
{  
    // Declare class variables
    var $arrSettings;       // Is filled in the class
    var $intDomainId = 0;   // Is filled in the class
    var $myDBClass;         // Is defined in the file prepend_adm.php
    var $strDBMessage = ""; // Classes will be filled internally

    // Stores the table name and id of the related/dependent object
    // Used for updating the last_modified time for services and hosts
    // when a related config name is changed - forcing the ccm to write
    // the related host and service files to disk.
    var $arrDBIds = array();

    // Stores information about the dependant relationships in array format
    var $arrRR = array();
    var $hasDepRels = false;

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Constructor
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Activities during class initialization
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    function nagdata() {
        // Globale Einstellungen einlesen
        $this->arrSettings = $_SESSION['SETS'];
        if (isset($_SESSION['domain'])) { $this->intDomainId = $_SESSION['domain']; }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Write data to the database
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Sends the given string to the SQL database server and evaluates the return
    //  of the server.
    //
    //  Transfer parameters: $strSQL - SQL command
    //
    //  Return value: $intDataID - ID of the last inserted record
    //                $this->strDBMessage - Error message
    //
    //  Return code: 0 on success / 1 on failure
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param $strSQL
     * @param $intDataID
     *
     * @return int
     */
    function dataInsert($strSQL, &$intDataID) {
        // Send data to the database server
        $boolReturn = $this->myDBClass->insertData($strSQL);
        $intDataID = $this->myDBClass->intLastId;
    
        // Could the record be inserted successfully?
        if ($boolReturn == true) {
            // Success
            $this->strDBMessage = _('Data successfully inserted into the database!');
            return(0);
        } else {
            // Failure
            $this->strDBMessage = _('Error while inserting data into the database:')."<br>".$this->myDBClass->strDBError;
            return(1);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Delete data easy
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    // Deletes a record or multiple records from a data table. Alternatively,
    // A single record ID to be specified, or the values ​​of ['chbId_n'] with $ _POST
    // Parameters passed are evaluated, where "n" is the record ID must match.
    //
    // This function only deletes the data from a single table!
    //
    // Parameters: $ table_name strTableName
    // $strKeyField key field (field name that contains the record ID)
    // $_POST [] Output form (check boxes "chbId_n" DBID = n)
    // $intDataId individual record ID, which is to be deleted
    // $intTableId tables in special relationships (templates)
    //
    // Return value: 0 on success / failure in one
    // Success - / strDBMessage error message via variable class
    // 
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param      $strTableName
     * @param      $strKeyField
     * @param int  $intDataId
     * @param int  $intTableId
     * @param bool $audit
     *
     * @return int
     */
    function dataDeleteEasy($strTableName, $strKeyField, $intDataId = 0, $intTableId = 0, $audit=true) {
        // Variable declarations
        $this->strDBMessage = "";
    
        // Special rule for tables with "nodelete" cells
        if (($strTableName == "tbl_domain") || ($strTableName == "tbl_user")) {
            $strNoDelete = "AND `nodelete` <> '1'";
        } else {
            $strNoDelete = "";
        }
    
        // Special rule for template link table
        if ($intTableId != 0) {
            $strTableId = "AND `idTable` = $intTableId";
        } else {
            $strTableId = "";
        }

        // Delete a single record
        if ($intDataId != 0) {

            // For hosts also delete the configuration file
            if ($strTableName == "tbl_host") {
                $strSQL = "SELECT `host_name` FROM `tbl_host` WHERE `id` = $intDataId";
                $strHost = $this->myDBClass->getFieldData($strSQL);

                $intReturn = $this->myConfigClass->moveFile("host", $strHost.".cfg");
                if ($intReturn == 0) {
                    $this->strMessage .=  _('The assigned, no longer used configuration files were deleted successfully!');
                    $this->writeLog(_('Host file deleted:')." ".$strHost.".cfg");
                } else {
                    $this->strMessage .=  _('Errors while deleting the old configuration file - please check!:')."<br>".$this->myConfigClass->strDBMessage;
                }
            }

            // If service, also delete the service configuration file
            if ($strTableName == "tbl_service") {
                $strSQL = "SELECT `config_name` FROM `tbl_service` WHERE `id` = $intDataId";
                $strService = $this->myDBClass->getFieldData($strSQL);
                $strSQL = "SELECT * FROM `tbl_service` WHERE `config_name` = '$strService'";
                $booReturn = $this->myDBClass->getDataArray($strSQL, $arrData, $intDataCount);
                if ($intDataCount == 1) {
                    $intReturn = $this->myConfigClass->moveFile("service", $strService.".cfg");
                    if ($intReturn == 0) {
                        $this->strMessage .=  _('The assigned, no longer used configuration files were deleted successfully!');
                        $this->writeLog(_('Host file deleted:')." ".$strService.".cfg");
                    } else {
                        $this->strMessage .=  _('Errors while deleting the old configuration file - please check!:')."<br>".$this->myConfigClass->strDBMessage;
                    }
                }
            }

            $strSQL = "DELETE FROM `".$strTableName."` WHERE `".$strKeyField."` = $intDataId $strNoDelete $strTableId";
            $booReturn = $this->myDBClass->insertData($strSQL);
      
            // Error handling
            if ($booReturn == false) {
                $this->strDBMessage .= _('Delete failed because a database error:')."<br>".mysql_error();
                return(1);
            } else if ($this->myDBClass->intAffectedRows == 0) {
                //$this->strDBMessage .= _('No data deleted. Probably the dataset does not exist or it is protected from delete.');
                return(0);
            } else {
                $this->strDBMessage .= _('Dataset successfully deleted. Affected rows:')." ".$this->myDBClass->intAffectedRows;
                $this->writeLog(_('Delete dataset id:')." $intDataId "._('- from table:')." $strTableName "._('- with affected rows:')." ".$this->myDBClass->intAffectedRows);
                return(0);
            }

        } else {
            
            // Delete multiple records
            $strSQL = "SELECT `id` FROM `".$strTableName."` WHERE 1=1 $strNoDelete";
            $booReturn = $this->myDBClass->getDataArray($strSQL, $arrData, $intDataCount);
            if ($intDataCount != 0) {
                $intDeleteCount = 0;
                foreach ($arrData AS $elem) {
                    $strChbName = "chbId_".$elem['id'];
                    
                    // The current record has been marked for deletion?
                    if (isset($_POST[$strChbName]) && ($_POST[$strChbName] == "on")) {
                        
                        // For hosts also delete the configuration file
                        if ($strTableName == "tbl_host") {
                            $strSQL = "SELECT `host_name` FROM `tbl_host` WHERE `id` = ".$elem['id'];
                            $strHost = $this->myDBClass->getFieldData($strSQL);
                            $intReturn = $this->myConfigClass->moveFile("host", $strHost.".cfg");
                            if ($intReturn == 0) {
                                if ($intDeleteCount == 0) {
                                    $this->strMessage .=  _('The assigned, no longer used configuration files were deleted successfully!');
                                }
                                $this->writeLog(_('Host file deleted:')." ".$strHost.".cfg");
                            } else {
                                $this->strMessage .=  _('Errors while deleting the old configuration file - please check!:')."<br>".$this->myConfigClass->strDBMessage;
                            }
                        }

                        // Delete services and the configuration file
                        if ($strTableName == "tbl_service") {
                          $strSQL = "SELECT `config_name` FROM `tbl_service` WHERE `id` = ".$elem['id'];
                          $strService = $this->myDBClass->getFieldData($strSQL);
                          $strSQL = "SELECT * FROM `tbl_service` WHERE `config_name` = '$strService'";
                          $booReturn = $this->myDBClass->getDataArray($strSQL, $arrData, $intDataCount);
                          if ($intDataCount == 1) {
                                $intReturn = $this->myConfigClass->moveFile("service", $strService.".cfg");
                                if ($intReturn == 0) {
                                    if ($intDeleteCount == 0) {
                                        $this->strMessage .=  _('The assigned, no longer used configuration files were deleted successfully!');
                                    }
                                    $this->writeLog(_('Service file deleted:')." ".$strService.".cfg");
                                } else {
                                    $this->strMessage .=  _('Errors while deleting the old configuration file - please check!:')."<br>".$this->myConfigClass->strDBMessage;
                                }
                            }
                        }

                        $strSQL = "DELETE FROM `".$strTableName."` WHERE `".$strKeyField."` = ".$elem['id']." $strTableId";
                        $booReturn = $this->myDBClass->insertData($strSQL);
            
                        // Error handling
                        if ($booReturn == false) {
                            $this->strDBMessage .= _('Delete failed because a database error:')."<br>".mysql_error();
                            return(1);
                        } else {
                            $intDeleteCount = $intDeleteCount + $this->myDBClass->intAffectedRows;
                        }
                    }
                }
        
                // Mitteilungen ausgeben
                if ($intDeleteCount == 0) {
                    //$this->strDBMessage .= _('No data deleted. Probably the dataset does not exist or it is protected from delete.');
                    return(0);
                } else {
                    $this->strDBMessage .= _('Dataset successfully deleted. Affected rows:')." ".$intDeleteCount;
                    $this->writeLog(_('Delete data from table:')." $strTableName "._('- with affected rows:')." ".$this->myDBClass->intAffectedRows);
                    return(0);
                }

            } else {
                $this->strDBMessage .= _('No data deleted. Probably the dataset does not exist or it is protected from delete.');
                return(1);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Delete full data
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    // Deletes only one main entrie and all of it's relations.
    //
    // Parameters: $ table_name strTableName
    // $ _POST [] form output (checkbox "chbId_n n = DBID)
    // $ intDataId Unique record ID, which is to be deleted
    // Delete $ intForce force 0 = No, 1 = Yes
    //
    // Return value: 0 for success or 1 for failure
    // success - / strDBMessage error message via variable class
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param      $strTableName
     * @param int  $intDataId
     * @param int  $intForce
     * @param bool $audit
     *
     * @return int
     */
    function dataDeleteFull($strTableName, $intDataId=0, $intForce=0, $audit=true)
    {
        global $ccmDB; 
        $protected = false;

        // Find all DB relationships     
        $this->fullTableRelations($strTableName, $arrRelations);

        // Check for item existence 
        $strSQL = "SELECT COUNT(`id`) FROM `".$strTableName."` WHERE `id`='".$intDataId."';";
        $this->myDBClass->getSingleDataset($strSQL, $results);
        $count = $results['COUNT(`id`)']; 
                 
        // Must have a valid id and exist in the DB
        if ($intDataId != 0 && $count == 1)  
        {                     
            $intDeleteCount = 0;
            $intFileRemoved = 0;
            $strFileMessage = "";

            // Verify that the host can be deleted (has no dependant relationships)
            $bool = $this->infoRelation($strTableName, $intDataId, "id");
            if ($bool == 0)
            {
                // Handle file removal for hosts and services 
                if ($strTableName == 'tbl_host' || $strTableName == 'tbl_service') {
                    list($fileReturn, $strFileMessage) = $this->handleFiles($intDataId, $strTableName);
                }
                
                // Clear any existing relations 
                $this->purgeRelations($intDataId, $strTableName, $arrRelations);
                $strSQL = "DELETE FROM `".$strTableName."` WHERE `id`='".$intDataId."' LIMIT 1;";
                $booReturn = $this->myDBClass->insertData($strSQL);
                if ($booReturn == 1) { $intDeleteCount++; }
            }
            else // Can not be deleted yet
            {
                $this->strDBMessage .= _("Object still has dependent relationships!"); 
            }

            // Return output
            if ($intDeleteCount == 0)
            {
                $this->strDBMessage .= _('<strong>Item was not deleted. </strong><br />');
                return(1);
            } 
            else 
            {
                $this->strDBMessage .= _('Dataset successfully deleted. Affected rows:')." ".$intDeleteCount;
                if ($audit) {
                    $this->writeLog(_('Delete data from table:')." ".$strTableName." "._('- with affected rows:')." ".$this->myDBClass->intAffectedRows,false);
                }
                $this->strDBMessage .= $strFileMessage;
                return(0);
            }           
      
        } // End main IF item exists         
        else
        {
            $this->strDBMessage .= _('Invalid object')." <strong>ID: $intDataId</strong>. "._('Item may not exist.')."<br />";
            return(1);
        }
    }
    ///////////////// end dataDeleteFull() ////////////////////////////

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Purge relations
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    // Removes a relationship from the database.
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param       $intDataId
     * @param       $strTableName
     * @param       $arrRelations
     * @param int   $intDataCount
     * @param array $arrData
     */
    function purgeRelations($intDataId, $strTableName, $arrRelations, &$intDataCount=1, &$arrData=array())
    {
        $this->strDBMessage = "";
                                                
        // Delete relations
        foreach($arrRelations as $rel) {
            $strSQL = "";

            // Dissolve flags
            $arrFlags = explode(",", $rel['flags']);
            if ($arrFlags[3] == 1) {
                $strSQL = "DELETE FROM `".$rel['tableName']."` WHERE `".$rel['fieldName']."`=".$intDataId;
            }
            
            if ($arrFlags[3] == 0)  {
                if ($arrFlags[2] == 0) {
                    $strSQL = "DELETE FROM `".$rel['tableName']."` WHERE `".$rel['fieldName']."`=".$intDataId;
                }
                if ($arrFlags[2] == 2) {
                    $strSQL = "UPDATE `".$rel['tableName']."` SET `".$rel['fieldName']."`=0 WHERE `".$rel['fieldName']."`=".$intDataId;
                }
            }

            if ($arrFlags[3] == 2) {
                $strSQL = "SELECT * FROM `".$rel['tableName']."` WHERE `".$rel['fieldName']."`=".$intDataId;
                $booReturn = $this->myDBClass->getDataArray($strSQL, $arrData, $intDataCount);
                if ($intDataCount != 0) {
                    foreach ($arrData AS $vardata) {
                        $strSQL = "DELETE FROM ".$rel['target']." WHERE `id`=".$vardata['idSlave'];
                        $booReturn = $this->myDBClass->insertData($strSQL);
                    }
                }
                $strSQL = "DELETE FROM `".$rel['tableName']."` WHERE `".$rel['fieldName']."`=".$intDataId;
            }
            
            if ($arrFlags[3] == 3) {
                $strSQL = "DELETE FROM `tbl_timedefinition` WHERE `tipId`=".$intDataId;
                $booReturn = $this->myDBClass->insertData($strSQL);
            }

            if ($strSQL != "") {
                $booReturn  = $this->myDBClass->insertData($strSQL);
            }
        }
    }
    // End: Purge relations

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Handle files
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    // Removes a relationship from the database.
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param $intDataId
     * @param $strTableName
     *
     * @return array
     */
    function handleFiles($intDataId, $strTableName)
    {
        $strFileMessage = ''; 
        $intFileRemoved = 1;

        // Delete the host configuration file
        if ($strTableName == "tbl_host") {
            $strSQL = "SELECT `host_name` FROM `tbl_host` WHERE `id`=".$intDataId;
            $strHost = $this->myDBClass->getFieldData($strSQL);
            $intReturn = $this->myConfigClass->moveFile("host", $strHost.".cfg");
            if ($intReturn == 0) {
                $intFileRemoved = 1;
                $strFileMessage .=  "<br />"._('Host file').': <strong>'.$strHost.'.cfg</strong> '._('was deleted').'<br />';  
                $this->writeLog(_('Host file deleted').": ".$strHost.".cfg");
            } else {
                $intFileRemoved = 2;
                $strFileMessage .=  "<br><span class='dependency'>"._('Errors while deleting the old configuration file - please check!:')."</span><br>".$this->myConfigClass->strDBMessage;
            }
        }

        // Delete the service configuration file so it's rewritten
        if ($strTableName == "tbl_service") {
            $strSQL = "SELECT `config_name` FROM `tbl_service` WHERE `id`=".$intDataId; 
            $strService = $this->myDBClass->getFieldData($strSQL);
            $intReturn = $this->myConfigClass->moveFile("service", $strService.".cfg");
            if ($intReturn == 0) {
                $intFileRemoved = 1;
                $strFileMessage .=  "<br />"._('Service file').': <strong>'.$strService.'.cfg</strong> '._('was deleted successfully!').'<br />';
                $this->writeLog(_('Service file deleted').": ".$strService.".cfg");
            } else {
                $intFileRemoved = 2;
                $strFileMessage .=  "<br>"._('Errors while deleting the old configuration file - please check!').":<br>".$this->myConfigClass->strDBMessage;
            }
        }
        
        return array($intFileRemoved, $strFileMessage);
    }
    // End: Handle files 

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Get key fields
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    // Fetches the appropriate name or description field for the object $type
    //
    //  @param string - $type object type (host, service, contact, etc)
    //  @param boolean - $desc boolean to return either the name or the description field
    //  @return string - $keyField name | desc 
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param      $type
     * @param bool $desc
     *
     * @return bool|string
     */
    function getKeyField($type, $desc=false)
    {
        switch($type) {

            case 'host':
            case 'hostgroup':
            case 'servicegroup':
            case 'contact':
            case 'contactgroup':
            case 'timeperiod':
                // Define table and sql args
                $typeName = $type.'_name';
                $typeDesc = 'alias';
                break;

            case 'hostdependency':
            case 'hostescalation':
            case 'servicedependency':
            case 'serviceescalation':
            case 'service':
                // Define table and sql args
                $typeName = 'config_name';
                $typeDesc = 'service_description';
                break;

            case 'command':
                $typeName = 'command_name';
                $typeDesc = 'command_line';
                break;  
            
            case 'servicetemplate': 
            case 'hosttemplate': 
            case 'contacttemplate':
                // Define table and sql args
                $typeName = 'template_name';
                $typeDesc = 'alias';
                if ($type == 'servicetemplate') { $typeDesc = 'display_name'; }
                break;
            
            default:
                return false;
                break;
        }

        // Return either name or description field 
        if ($desc) {
            return $typeDesc;
        } else { 
            return $typeName;
        }
    }
    // End: Get key fields 

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function: Copy records
    ////////////////////////////////////////////////// /////////////////////////////////////////
    //
    // Copy one or more records in a data table. Optionally, a
    // Single record ID to be specified, or the values ​​of ['chbId_n'] with $ _POST
    // Parameters passed are evaluated, where "n" is the record ID must match.
    //
    // Parameters: $ table_name strTableName
    // $ StrKeyField The key field of table
    // $ _POST [] Output form (check boxes "chbId_n" DBID = n)
    // $ IntDataId individual record ID, which is to be deleted
    //
    // Return value: 0 on success / failure in one
    // Success - / strDBMessage error message via variable class
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param     $strTableName
     * @param     $strKeyField
     * @param int $intDataId
     *
     * @return int
     */
    function dataCopyEasy($strTableName, $strKeyField, $intDataId = 0)
    {
        // Declare variables 
        $intError = 0;
        $intNumber= 0;
        $this->strDBMessage = "";

        // All record IDs of the target table query
        $booReturn = $this->myDBClass->getDataArray("SELECT `id` FROM `".$strTableName."` ORDER BY `id`",$arrData,$intDataCount);
        if ($booReturn == false) {
            $this->strDBMessage = _('Error while selecting data from database:')."<br>".$this->myDBClass->strDBError."<br>";
            return(1);
        } else if ($intDataCount != 0) {
            
            // Records returned
            for ($i=0; $i < $intDataCount; $i++) {
                
                // Form transfer parameters compose
                $strChbName = "chbId_".$arrData[$i]['id'];
        
                // If provided with a $ _POST parameter mountain just with this name or explicitly, this ID
                if (($intDataId == 0) || ($intDataId == $arrData[$i]['id']) ) {
          
                    // Data entry of the corresponding fetch
                    $this->myDBClass->getSingleDataset("SELECT * FROM `".$strTableName."` WHERE `id`=".$arrData[$i]['id'],$arrData[$i]);
                    
                    // Suffix create
                    for ($y=1; $y <= $intDataCount; $y++) {
                        $strNewName = $arrData[$i][$strKeyField]."_copy_$y";
                        $booReturn = $this->myDBClass->getFieldData("SELECT `id` FROM `".$strTableName."` WHERE `".$strKeyField."`='$strNewName'");
                        if ($booReturn == false) { break; } // If the new name is unique to cancel
                    }

                    // According assemble the table name with the database insert command
                    $strSQLInsert = "INSERT INTO `".$strTableName."` SET `".$strKeyField."`='$strNewName',";
                    foreach($arrData[$i] AS $type => $value) {
                        if (($type != $strKeyField) && ($type != "active") && ($type != "last_modified") && ($type != "id")) {
                            // NULL Depreciations field data set
                            if (($type == "normal_check_interval") && ($value == "")) $value="NULL";
                            if (($type == "retry_check_interval") && ($value == "")) $value="NULL";
                            if (($type == "max_check_attempts") && ($value == "")) $value="NULL";
                            if (($type == "low_flap_threshold") && ($value == "")) $value="NULL";
                            if (($type == "high_flap_threshold") && ($value == "")) $value="NULL";
                            if (($type == "freshness_threshold") && ($value == "")) $value="NULL";
                            if (($type == "notification_interval") && ($value == "")) $value="NULL";
                            if (($type == "first_notification_delay")&& ($value == "")) $value="NULL";
                            if (($type == "check_interval") && ($value == "")) $value="NULL";
                            if (($type == "retry_interval") && ($value == "")) $value="NULL";
                            if (($type == "access_rights") && ($value == "")) $value="NULL";
                            
                            // NULL Values ​​set by table name
                            if (($strTableName == "tbl_hostextinfo") && ($type == "host_name")) $value="NULL";
                            if (($strTableName == "tbl_serviceextinfo") && ($type == "host_name")) $value="NULL";
              
                            // Password for user copied not Apply
                            if (($strTableName == "tbl_user") && ($type == "password")) $value="xxxxxxx";
              
                            // Erase protection / Webserverauthentification not accept
                            if ($type == "nodelete") $value = "0";
                            if ($type == "wsauth") $value = "0";
              
                            // If the data value is not "NULL", include the data value in quotes
                            if ($value != "NULL") {
                                $strSQLInsert .= "`".$type."`='".addslashes($value)."',";
                            } else {
                                $strSQLInsert .= "`".$type."`=".$value.",";
                            }
                        }
                    }
          
                    $strSQLInsert .= "`active`='0', `last_modified`=NOW()";
          
                    // Copy into the database
                    $intCheck = 0;
                    $booReturn = $this->myDBClass->insertData($strSQLInsert);
                    $intMasterId = $this->myDBClass->intLastId;
                    if ($booReturn == false) { $intCheck++; }

                    // Copy any existing relationships
                    if (($this->tableRelations($strTableName, $arrRelations) != 0) && ($intCheck == 0)) {
                        foreach ($arrRelations AS $elem) {
                            if (($elem['type'] != "3") && ($elem['type'] != "5") && ($elem['type'] != "1")) {
                
                                // Field is not set to "None" or "*"?
                                if ($arrData[$i][$elem['fieldName']] == 1) {
                                    $strSQL = "SELECT `idSlave` FROM `".$elem['linktable']."` WHERE `idMaster` = ".$arrData[$i]['id'];
                                    $booReturn = $this->myDBClass->getDataArray($strSQL, $arrRelData, $intRelDataCount);
                                    if ($intRelDataCount != 0) {
                                        for ($y=0; $y < $intRelDataCount; $y++) {
                                            if ($elem['type'] == 4) { // Special case for custom variables 
                                                // Clone the variable itself
                                                $strSQL = "INSERT INTO `tbl_variabledefinition` (`name`,`value`,`last_modified`) 
                                                           SELECT `name`,`value`,`last_modified` FROM tbl_variabledefinition WHERE id=".$arrRelData[$y]['idSlave'];
                                                $booReturn = $this->myDBClass->insertData($strSQL); 
                                                $id = $this->myDBClass->intLastId;
                                                if (!empty($id)) {
                                                    $strSQLRel = "INSERT INTO `".$elem['linktable']."` SET `idMaster`=$intMasterId, `idSlave`=".$id;
                                                    $booReturn = $this->myDBClass->insertData($strSQLRel);    
                                                } else {
                                                    $booReturn = false; 
                                                }
                                            } else {
                                                $strSQLRel = "INSERT INTO `".$elem['linktable']."` SET `idMaster`=$intMasterId, `idSlave`=".$arrRelData[$y]['idSlave'];
                                                $booReturn = $this->myDBClass->insertData($strSQLRel);
                                            }

                                            if ($booReturn == false) { $intCheck++; }                 
                                        }
                                    }
                                }
                            } else if (($elem['type'] != "5") && ($elem['type'] != "1") &&($elem['type'] != 4)) {
                                // Field is not set to "None" or "*"?
                                // XI MOD - Fixed variable copying
                                if ($arrData[$i][$elem['fieldName']] == 1) {
                                    $strSQL = "SELECT `idSlave`,`idSort`,`idTable` FROM `".$elem['linktable']."` WHERE `idMaster` = ".$arrData[$i]['id'];
                                    $booReturn = $this->myDBClass->getDataArray($strSQL, $arrRelData, $intRelDataCount);
                                    if ($intRelDataCount != 0) {
                                        for ($y=0; $y < $intRelDataCount; $y++) {
                                            $strSQLRel = "INSERT INTO `".$elem['linktable']."` SET `idMaster`=$intMasterId, `idSlave`=".$arrRelData[$y]['idSlave'].",`idTable`=".$arrRelData[$y]['idTable'].", `idSort`=".$arrRelData[$y]['idSort'];
                                            $booReturn   = $this->myDBClass->insertData($strSQLRel);
                                            if ($booReturn == false) { $intCheck++; }
                                        }
                                    }
                                }
                            } else if ($elem['type'] != "1") {
                                // Field is not set to "None" or "*"?
                                if ($arrData[$i][$elem['fieldName']] == 1) {
                                    $strSQL = "SELECT `idSlaveH`,`idSlaveHG`,`idSlaveS` FROM `".$elem['linktable']."` WHERE `idMaster` = ".$arrData[$i]['id'];
                                    $booReturn = $this->myDBClass->getDataArray($strSQL, $arrRelData, $intRelDataCount);
                                    if ($intRelDataCount != 0) {
                                        for ($y=0; $y < $intRelDataCount; $y++) {
                                            $strSQLRel = "INSERT INTO `".$elem['linktable']."` SET `idMaster`=$intMasterId, `idSlaveH`=".$arrRelData[$y]['idSlaveH'].",`idSlaveHG`=".$arrRelData[$y]['idSlaveHG'].",`idSlaveS`=".$arrRelData[$y]['idSlaveS'];
                                            $booReturn = $this->myDBClass->insertData($strSQLRel);
                                            if ($booReturn == false) { $intCheck++; }
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Under Copy table values ​​at tbl_timeperiod
                    if ($strTableName == "tbl_timeperiod") {
                        $strSQL = "SELECT * FROM `tbl_timedefinition` WHERE `tipId`=".$arrData[$i]['id'];
                        $booReturn = $this->myDBClass->getDataArray($strSQL, $arrRelDataTP, $intRelDataCountTP);
                        if ($intRelDataCountTP != 0) {
                            foreach ($arrRelDataTP AS $elem) {
                                $strSQLRel = "INSERT INTO `tbl_timedefinition` (`tipId`,`definition`,`range`,`last_modified`) VALUES ($intMasterId,'".$elem['definition']."','".$elem['range']."',now())";
                                $booReturn = $this->myDBClass->insertData($strSQLRel);
                                if ($booReturn == false) { $intCheck++; }
                            }
                        }
                    }

                    // Write Log 
                    if ($intCheck != 0) {
                        // Failure
                        $intError++;
                        $this->writeLog(_('Data set copy failed - table [new name]:')." ".$strTableName." [".$strNewName."]");
                    } else {
                        // Success 
                        $this->writeLog(_('Data set copied - table [new name]:')." ".$strTableName." [".$strNewName."]");
                    }
                    $intNumber++;
                }
            }
        }

        // Return data 
        if ($intNumber > 0) {
            if ($intError == 0) {
                // Success 
                $this->strDBMessage = _("Data successfully inserted to the database! Object <strong>$strNewName</strong> created.");
                return(0);
            } else {
                // Failure 
                $this->strDBMessage = _('Error while inserting the data to the database:')."<br>".$this->myDBClass->strDBError;
                return(1);
            }
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Write to log
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Saves the given string in the log file
    //
    //  Function paramaters: $strMessage Message
    //                       $_SESSION['username'] User name 
    //
    //  Return code: 0 on success, 1 on failure
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param      $strMessage
     * @param bool $audit
     *
     * @return int
     */
    function writeLog($strMessage, $audit=true) {
        // Logstring in Datenbank schreiben
        $strUserName = (isset($_SESSION['username']) && ($_SESSION['username'] != "")) ? $_SESSION['username'] : "unknown";
        $strDomain = $this->myDBClass->getFieldData("SELECT `domain` FROM `tbl_domain` WHERE `id`=".$this->intDomainId);
        $booReturn = $this->myDBClass->insertData("INSERT INTO `tbl_logbook` SET `user`='".$strUserName."',`time`=NOW(), `ipadress`='".$_SERVER["REMOTE_ADDR"]."', `domain`='$strDomain', `entry`='".addslashes(utf8_encode($strMessage))."'");

        // XI audit logging
        if ($audit) {
            audit_log(AUDITLOGTYPE_MODIFY, $strMessage);
        }

        if ($booReturn == false) { return(1); }
        return(0);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Must check data
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Checks whether a relation exists with the supplied record in another table, which
    //  must not be deleted. All relationships found are returned as results array.
    //
    //  Function paramaters: $strTable Table name
    //                       $intDataId Table's ID
    //
    //  Return value: $arrInfo Array The affected data fields (table name)
    //
    //  Return code: 0 if no relation was found
    //               1 if at least one relation was found
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param $strTableName
     * @param $intDataId
     * @param $arrInfo
     *
     * @return int
     */
    function checkMustdata($strTableName, $intDataId, &$arrInfo) {
        // TODO: Create a new set of rules
        return 0;
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Funktion: Relationen einer Datentabelle zurückliefern
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Gibt eine Liste aus mit allen Datenfeldern einer Tabelle, die eine 1:1 oder 1:n
    //  Beziehung zu einer anderen Tabelle haben.
    //
    //  Übergabeparameter:  $strTable   Tabellenname
    //
    //  Rückgabewert:   $arrRelations Array mit den betroffenen Datenfeldern
    //
    //  Returnwert:     0 bei keinem Feld mit Relation
    //            1 bei mindestens einem Feld mit Relation
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param $strTable
     * @param $arrRelations
     *
     * @return int
     */
    function tableRelations($strTable, &$arrRelations) {
        $arrRelations = "";
        switch ($strTable) {
            
            case "tbl_command":
                return(0);

            case "tbl_timeperiod":
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "exclude",
                                        'target' => "timeperiod_name",
                                        'linktable' => "tbl_lnkTimeperiodToTimeperiod",
                                        'type' => 2);
                return(1);

            case "tbl_contact":
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "host_notification_commands",
                                        'target' => "command_name",
                                        'linktable' => "tbl_lnkContactToCommandHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "service_notification_commands",
                                        'target' => "command_name",
                                        'linktable' => "tbl_lnkContactToCommandService",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contactgroups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkContactToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "host_notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "service_notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName1' => "tbl_contacttemplate",
                                        'tableName2' => "tbl_contact",
                                        'fieldName' => "use_template",
                                        'target1' => "template_name",
                                        'target2' => "name",
                                        'linktable' => "tbl_lnkContactToContacttemplate",
                                        'type' => 3);
                $arrRelations[] = array('tableName' => "tbl_variabledefinition",
                                        'fieldName' => "use_variables",
                                        'target' => "name",
                                        'linktable' => "tbl_lnkContactToVariabledefinition",
                                        'type' => 4);
                return(1);

            case "tbl_contacttemplate":
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "host_notification_commands",
                                        'target' => "command_name",
                                        'linktable' => "tbl_lnkContacttemplateToCommandHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "service_notification_commands",
                                        'target' => "command_name",
                                        'linktable' => "tbl_lnkContacttemplateToCommandService",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contactgroups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkContacttemplateToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "host_notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "service_notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName1' => "tbl_contacttemplate",
                                        'tableName2' => "tbl_contact",
                                        'fieldName' => "use_template",
                                        'target1' => "template_name",
                                        'target2' => "name",
                                        'linktable' => "tbl_lnkContacttemplateToContacttemplate",
                                        'type' => 3);
                $arrRelations[] = array('tableName' => "tbl_variabledefinition",
                                        'fieldName' => "use_variables",
                                        'target' => "name",
                                        'linktable' => "tbl_lnkContacttemplateToVariabledefinition",
                                        'type' => 4);
                return(1);

            case "tbl_contactgroup":
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "members",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkContactgroupToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contactgroup_members",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkContactgroupToContactgroup",
                                        'type' => 2);
                return(1);

            case "tbl_hosttemplate":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "parents",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkHosttemplateToHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroups",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkHosttemplateToHostgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contact_groups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkHosttemplateToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "contacts",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkHosttemplateToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "check_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "check_command",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "event_handler",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName1' => "tbl_hosttemplate",
                                        'tableName2' => "tbl_host",
                                        'fieldName' => "use_template",
                                        'target1' => "template_name",
                                        'target2' => "name",
                                        'linktable' => "tbl_lnkHosttemplateToHosttemplate",
                                        'type' => 3);
                $arrRelations[] = array('tableName' => "tbl_variabledefinition",
                                        'fieldName' => "use_variables",
                                        'target' => "name",
                                        'linktable' => "tbl_lnkHosttemplateToVariabledefinition",
                                        'type' => 4);
                return(1);

            case "tbl_host":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "parents",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkHostToHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroups",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkHostToHostgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contact_groups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkHostToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "contacts",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkHostToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "check_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "check_command",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "event_handler",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName1' => "tbl_hosttemplate",
                                        'tableName2' => "tbl_host",
                                        'fieldName' => "use_template",
                                        'target1' => "template_name",
                                        'target2' => "name",
                                        'linktable' => "tbl_lnkHostToHosttemplate",
                                        'type' => 3);
                $arrRelations[] = array('tableName' => "tbl_variabledefinition",
                                        'fieldName' => "use_variables",
                                        'target' => "name",
                                        'linktable' => "tbl_lnkHostToVariabledefinition",
                                        'type' => 4);
                return(1);

            case "tbl_hostgroup":
                $arrRelations[] = array('tableName' => "tbl_host",
                                  'fieldName' => "members",
                                  'target'  => "host_name",
                                  'linktable' => "tbl_lnkHostgroupToHost",
                                  'type'    => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                  'fieldName' => "hostgroup_members",
                                  'target'  => "hostgroup_name",
                                  'linktable' => "tbl_lnkHostgroupToHostgroup",
                                  'type'    => 2);
                return(1);
            
            case "tbl_servicetemplate":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkServicetemplateToHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkServicetemplateToHostgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_servicegroup",
                                        'fieldName' => "servicegroups",
                                        'target' => "servicegroup_name",
                                        'linktable' => "tbl_lnkServicetemplateToServicegroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contact_groups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkServicetemplateToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "contacts",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkServicetemplateToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "check_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "check_command",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "event_handler",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName1' => "tbl_servicetemplate",
                                        'tableName2' => "tbl_service",
                                        'fieldName' => "use_template",
                                        'target1' => "template_name",
                                        'target2' => "name",
                                        'linktable' => "tbl_lnkServicetemplateToServicetemplate",
                                        'type' => 3);
                $arrRelations[] = array('tableName' => "tbl_variabledefinition",
                                        'fieldName' => "use_variables",
                                        'target' => "name",
                                        'linktable' => "tbl_lnkServicetemplateToVariabledefinition",
                                        'type' => 4);
                return(1);

            case "tbl_service":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkServiceToHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkServiceToHostgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_servicegroup",
                                        'fieldName' => "servicegroups",
                                        'target' => "servicegroup_name",
                                        'linktable' => "tbl_lnkServiceToServicegroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contact_groups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkServiceToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "contacts",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkServiceToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "check_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "check_command",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "notification_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_command",
                                        'fieldName' => "event_handler",
                                        'target' => "command_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName1' => "tbl_servicetemplate",
                                        'tableName2' => "tbl_service",
                                        'fieldName' => "use_template",
                                        'target1' => "template_name",
                                        'target2' => "name",
                                        'linktable' => "tbl_lnkServiceToServicetemplate",
                                        'type' => 3);
                $arrRelations[] = array('tableName' => "tbl_variabledefinition",
                                        'fieldName' => "use_variables",
                                        'target'  => "name",
                                        'linktable' => "tbl_lnkServiceToVariabledefinition",
                                        'type'    => 4);
                return(1);

            case "tbl_servicegroup": 
                $arrRelations[] = array('tableName1' => "tbl_host",
                                        'tableName2' => "tbl_service",
                                        'fieldName' => "members",
                                        'target1' => "host_name",
                                        'target2' => "service_description",
                                        'linktable' => "tbl_lnkServicegroupToService",
                                        'type' => 5);
                $arrRelations[] = array('tableName' => "tbl_servicegroup",
                                        'fieldName' => "servicegroup_members",
                                        'target' => "servicegroup_name",
                                        'linktable' => "tbl_lnkServicegroupToServicegroup",
                                        'type' => 2);
                return(1);

            case "tbl_hostdependency":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "dependent_host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkHostdependencyToHost_DH",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkHostdependencyToHost_H",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "dependent_hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkHostdependencyToHostgroup_DH",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkHostdependencyToHostgroup_H",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "dependency_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                return(1);

            case "tbl_hostescalation":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkHostescalationToHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkHostescalationToHostgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "contacts",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkHostescalationToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contact_groups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkHostescalationToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "escalation_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                return(1);
        
            case "tbl_hostextinfo":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "",
                                        'type' => 1);
                return(1);

            case "tbl_servicedependency":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "dependent_host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkServicedependencyToHost_DH",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkServicedependencyToHost_H",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "dependent_hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkServicedependencyToHostgroup_DH",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkServicedependencyToHostgroup_H",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_service",
                                        'fieldName' => "dependent_service_description",
                                        'target' => "service_description",
                                        'linktable' => "tbl_lnkServicedependencyToService_DS",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_service",
                                        'fieldName' => "service_description",
                                        'target' => "service_description",
                                        'linktable' => "tbl_lnkServicedependencyToService_S",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "dependency_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                return(1);

            case "tbl_serviceescalation":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "tbl_lnkServiceescalationToHost",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_hostgroup",
                                        'fieldName' => "hostgroup_name",
                                        'target' => "hostgroup_name",
                                        'linktable' => "tbl_lnkServiceescalationToHostgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_service",
                                        'fieldName' => "service_description",
                                        'target' => "service_description",
                                        'linktable' => "tbl_lnkServiceescalationToService",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contact",
                                        'fieldName' => "contacts",
                                        'target' => "contact_name",
                                        'linktable' => "tbl_lnkServiceescalationToContact",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_contactgroup",
                                        'fieldName' => "contact_groups",
                                        'target' => "contactgroup_name",
                                        'linktable' => "tbl_lnkServiceescalationToContactgroup",
                                        'type' => 2);
                $arrRelations[] = array('tableName' => "tbl_timeperiod",
                                        'fieldName' => "escalation_period",
                                        'target' => "timeperiod_name",
                                        'linktable' => "",
                                        'type' => 1);
                return(1);

            case "tbl_serviceextinfo":
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "host_name",
                                        'target' => "host_name",
                                        'linktable' => "",
                                        'type' => 1);
                $arrRelations[] = array('tableName' => "tbl_service",
                                        'fieldNamw' => "service_description",
                                        'target' => "service_description",
                                        'linktable' => "",
                                        'type' => 1);
                return(1);
            
            default:
                return(0);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Write relations in the database
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    // Does the necessary relationships for a 1: n (Optional 1: n: n) relationship in the
    // Relations table
    //
    // Parameters: $ intTable name of the link table
    // $ IntMasterId table ID of the main table
    // $ ArrSlaveId array of record IDs of the sub-table
    // $ IntMulti 0 = normal 1: n / a = 1: n: n relationship
    //
    // Return value: 0 on success / failure in one
    // Success - / strDBMessage error message via variable class
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param     $intTable
     * @param     $intMasterId
     * @param     $arrSlaveId
     * @param int $intMulti
     * @param arr $arrExcIds - Exclude IDs that will be given a ! in the config
     *
     * @return int
     */
    function dataInsertRelation($intTable, $intMasterId, $arrSlaveId, $intMulti=0, $arrExcIds=array()) {
        // Make for each array position an entry in the relation table
        foreach ($arrSlaveId AS $elem) {
            // Hide empty values
            if ($elem == '0' || $elem=='*') continue;

            // SQL Statement
            if ($intMulti != 0) {
                $arrValues = "";
                $arrValues = explode("::", $elem);
                $strSQL = "INSERT INTO `".$intTable."` SET `idMaster`=$intMasterId, `idSlaveH`=".$arrValues[0].", `idSlaveHG`=".$arrValues[1].", `idSlaveS`=".$arrValues[2];
            } else {
                $exclude = '';
                if (!empty($arrExcIds)) {
                    if (in_array($elem, $arrExcIds)) {
                        $exclude = ', `exclude`=1';
                    }
                }
                $strSQL = "INSERT INTO `".$intTable."` SET `idMaster`=$intMasterId, `idSlave`=$elem".$exclude;
            }

            // Send data to the database server
            $intReturn = $this->dataInsert($strSQL, $intDataID);
            if ($intReturn != 0) {  
                return(1);
            }   
        }

        return(0);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Update relations in the database
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Changes the relations for a 1: n (optonal 1: n: n) relationship within 
    //  the relational table
    //
    //  Transfer parameters: $intTable     Name of link table
    //                       $intMasterId  Table ID of the main table
    //                       $arrSlaveId   Array of all the record IDs of the table
    //                       $intMulti     0 = normal 1:n / 1 = 1:n:n relationship
    //
    //  Return values: 0 on success / 1 on failure
    //                 $this->strDBMessage Error message
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param     $intTable
     * @param     $intMasterId
     * @param     $arrSlaveId
     * @param int $intMulti
     * @param arr $arrExcIds - Excluded ids that will be given a ! in the config file
     *
     * @return int
     */
    function dataUpdateRelation($intTable, $intMasterId, $arrSlaveId, $intMulti=0, $arrExcIds=array()) {

        // Delete old relations
        $intReturn1 = $this->dataDeleteRelation($intTable, $intMasterId);
        if ($intReturn1 != 0) { return(1); }

        // Submit new relations
        $intReturn2 = $this->dataInsertRelation($intTable, $intMasterId, $arrSlaveId, $intMulti, $arrExcIds);
        if ($intReturn2 != 0) { return(1); }
        return(0);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Delete relationships in the database
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Removes a relation from the relation table
    //
    //  Transfer parameters: $intTable     Name of link table
    //                       $intMasterId  Table ID of the main table
    //
    //  Return values: 0 on success / 1 on failure
    //                 $this->strDBMessage Error message
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param $intTable
     * @param $intMasterId
     *
     * @return int
     */
    function dataDeleteRelation($intTable, $intMasterId) {
        // SQL Statement
        $strSQL = "DELETE FROM `".$intTable."` WHERE `idMaster`=$intMasterId";
        
        // Send data to the database server
        $intReturn = $this->dataInsert($strSQL, $intDataID);
        if ($intReturn != 0) { return(1); }
        return(0);
    }

    ///////////////////////////////////////////////////////////////////////////////////////////
    // Function: Read the relations in the database
    ////////////////////////////////////////////////// /////////////////////////////////////////
    //
    // Finds all relations from the database
    //
    // Parameters: $intTable name of the main table
    //             $intMasterId table ID of the main table
    //             $strMasterfield Name field of the main entry
    //             $intReporting text output - 0 = yes 1 = no
    //
    // Return values: 0 no relations / 1 relations
    //                 $this->strDBMessage Error message
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param     $strTable
     * @param     $intMasterId
     * @param     $strMasterfield
     * @param int $intReporting
     *
     * @return int
     */
    function infoRelation($strTable, $intMasterId, $strMasterfield, $intReporting=0, $service_only=false)
    {
        $intReturn = $this->fullTableRelations($strTable, $arrRelations);
        $intDeletion = 0;

        if ($intReturn == 1) {
            $strNewMasterfield = str_replace(',', '`,`', $strMasterfield);
            $strSQL = "SELECT `".$strNewMasterfield."` FROM `".$strTable."` WHERE `id` = $intMasterId";
            $this->myDBClass->getSingleDataset($strSQL, $arrSource);
            
            ///////////////////MOD -MG ////////////
            if (count($arrSource) ==0) { return(0); } // Bail if there are no relations, deletion possible 
            ////////////////////////////////////////
      
            if (substr_count($strMasterfield, ",") != 0) {
                $arrTarget = explode(",", $strMasterfield);
                $strName = $arrSource[$arrTarget[0]]."-".$arrSource[$arrTarget[1]];
            } else {
                $strName = $arrSource[$strMasterfield];
            }

            $this->strDBMessage = "<span class='relationInfo'>Object ID: <strong>".$strName."</strong> of table <strong>".$strTable.":</strong><br /></span>\n";
            foreach ($arrRelations AS $elem) {

                $arrFlags = explode(",", $elem['flags']);
                if ($elem['fieldName'] == "check_command") {
                    $strSQL = "SELECT * FROM `".$elem['tableName']."` WHERE SUBSTRING_INDEX(`".$elem['fieldName']."`,'!',1)= $intMasterId";
                } else {
                    $strSQL = "SELECT * FROM `".$elem['tableName']."` WHERE `".$elem['fieldName']."`= $intMasterId";
                }
                $booReturn = $this->myDBClass->getDataArray($strSQL, $arrData, $intDataCount);

                // Display links in use only
                if ($intDataCount != 0) {

                    // Link type
                    if ($arrFlags[3] == 1) {

                        if ($elem['target'] == 'tbl_service' && $strTable == 'tbl_serviceescalation') {
                            $service_only = true;
                        }

                        foreach ($arrData AS $data) {

                            if ($elem['fieldName'] == "idMaster") {
                                $strRef = "idSlave";
                                if ($elem['target'] == "tbl_service") {
                                    if ($elem['tableName'] == "tbl_lnkServicegroupToService") {
                                        $strRef = "idSlaveS";
                                    }
                                } else if ($elem['target'] == "tbl_host") {
                                    if ($elem['tableName'] == "tbl_lnkServicegroupToService") {
                                        $strRef = "idSlaveH";
                                    }
                                } else if ($elem['target'] == "tbl_hostgroup") {
                                    if ($elem['tableName'] == "tbl_lnkServicegroupToService") {
                                        $strRef = "idSlaveHG";
                                    }
                                }
                            } else {
                                $strRef = "idMaster";
                            }
              
                            // Fetch data
                            $strSQL = "SELECT * FROM `".$elem['tableName']."`
                                       LEFT JOIN `".$elem['target']."` ON `".$strRef."` = `id`
                                       WHERE `".$elem['fieldName']."` = ".$data[$elem['fieldName']]."
                                       AND `".$strRef."`=".$data[$strRef]." AND ".$elem['target'].".active = '1'";
                       
                            $this->myDBClass->getSingleDataset($strSQL, $arrDSTarget);
                            $full_name = substr($elem['target'], 4, strlen($elem['target']));
                            
                            if (substr_count($elem['targetKey'], ",") != 0) {
                                $arrTarget = explode(",", $elem['targetKey']);
                                if ($service_only) {
                                    $c = '';
                                    $s = $arrDSTarget[$arrTarget[1]];
                                    $strTarget = $s;
                                } else {
                                    $c = $arrDSTarget[$arrTarget[0]];
                                    $s = $arrDSTarget[$arrTarget[1]];
                                    $strTarget = $c."-".$s;
                                }
                            } else {
                                $strTarget = isset($arrDSTarget[$elem['targetKey']]) ? $arrDSTarget[$elem['targetKey']] : '';
                            }

                            // Consider the case of "must do" box, if multiple entries
                            if (($arrFlags[0] == 1) && ($strTarget != "-")) {
                                $strSQL = "SELECT * FROM `".$elem['tableName']."`
                                           WHERE `".$strRef."` = ".$arrDSTarget[$strRef];
                                $booReturn = $this->myDBClass->getDataArray($strSQL, $arrDSCount, $intDCCount);
                                if ($intDCCount > 0) {
                                    $this->strDBMessage .= _("Relation to <strong>").ucfirst($full_name)._("s</strong>, entry: <strong>").$strTarget." - </strong><span class='dependent'>"._("Dependent relationship")."</span><br />\n";
                                    $a = array('dependent' => 1);
                                    $this->hasDepRels = true;
                                    if (!empty($s)) { $a['cfg'] = $c; $a['service'] = $s; }
                                    else { $a['name'] = $strTarget; }
                                    $this->arrRR[$full_name][$arrDSTarget['id']] = $a;
                                    $this->arrDBIds[] = array($elem['target'], $arrDSTarget['idMaster']);
                                    $intDeletion = 1;
                                }
                            } else if ($strTarget != "-") {
                                // Removed extra output 
                                if ($intReporting != 0) {
                                    $this->strDBMessage .= _("Relation to <strong>").ucfirst($full_name)._("s</strong>, entry: <strong>").$strTarget."</strong><br>\n";
                                }
                                $this->arrRR[$full_name][$arrDSTarget['id']] = $strTarget;
                                $this->arrDBIds[] = array($elem['target'], $arrDSTarget['idMaster']);
                            }
                        }
                    } else if ($arrFlags[3] == 0) {
                        $friendlyName = ucfirst(substr($elem['target'], 4, strlen($elem['target'])));

                        // Get peers entry
                        $strSQL = "SELECT * FROM `".$elem['tableName']."` WHERE `".$elem['fieldName']."`=$intMasterId";
                        $booReturn = $this->myDBClass->getDataArray($strSQL, $arrDataCheck, $intDCCheck);
                        foreach ($arrDataCheck AS $data) {
                            if (substr_count($elem['targetKey'], ",") != 0) {
                                $arrTarget = explode(",", $elem['targetKey']);
                                $strTarget = $data[$arrTarget[0]]."-".$data[$arrTarget[1]];
                            } else {
                                $strTarget = $data[$elem['targetKey']];
                            }
                            if ($arrFlags[0] == 1) {
                                $this->strDBMessage .= _("Relation to <strong>").$elem['tableName']._("</strong>, entry: <strong>").$strTarget." - </strong><span class='dependent'>"._("Dependent relationship")."</span><br>\n";
                                $this->arrDBIds[] = array($elem['tableName'], $data['id']);
                                $intDeletion = 1;
                            } else {
                                // Remove extra log output 
                                if ($intReporting != 0) {
                                    $this->strDBMessage .= _("Relation to <strong>").$elem['tableName']._("</strong>, entry: <strong>").$strTarget."</strong><br />\n";
                                }
                                $this->arrDBIds[] = array($elem['tableName'], $data['id']);
                            }
                        }
                    }
                }
            }
        }

        return($intDeletion);
    }


    ///////////////////////////////////////////////////////////////////////////////////////////
    //  Function: Return full relations of a data table
    ///////////////////////////////////////////////////////////////////////////////////////////
    //
    //  Returns a list with all data fields in a table that have a relation to another table.
    //  Here passive relations are returned that do not yet have to be written in a
    //  configuration consist separation, eg. Relations to be written by other configurations,
    //  but the specified table involves.
    //
    //  This function is used on a configuration entry to completely delete or to determine
    //  whether the current configuration is used elsewhere.
    //
    //  Transfer parameters: $strTable Table name
    //
    //  Return Value: $arrRelations Array the affected data fields
    //                -> tableName - Contains the table name of the linked ID
    //                -> fieldName - Table field that contains the linked ID
    //                -> flags Pos1 -> 0 = normal field, 1 = required field [Field type]
    //                         Pos2 -> 0 = delete, 1 = leave, 2 = set to 0 [Delete if normal]
    //                         Pos3 -> 0 = delete, 2 = set to 0 [Forced?]
    //                         Pos4 -> 0 = 1:1, 1=1:n, 2=1:nVar, 3=1:nTime [Link type]
    //
    //  Return Value: 0 No field with relation
    //                1 At least one field with relation
    //
    ///////////////////////////////////////////////////////////////////////////////////////////
    /**
     * @param $strTable
     * @param $arrRelations
     *
     * @return int
     */
    function fullTableRelations($strTable, &$arrRelations) {
        $arrRelations = "";
        switch ($strTable) {
            
            case "tbl_command": 
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToCommandHost",
                                        'fieldName' => "idSlave",
                                        'target'    => "tbl_contacttemplate",
                                        'targetKey' => "template_name",
                                        'flags'     => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToCommandService",
                                        'fieldName' => "idSlave",
                                        'target'    => "tbl_contacttemplate",
                                        'targetKey' => "template_name",
                                        'flags'     => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToCommandHost",
                                        'fieldName' => "idSlave",
                                        'target'    => "tbl_contact",
                                        'targetKey' => "contact_name",
                                        'flags'     => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToCommandService",
                                        'fieldName' => "idSlave",
                                        'target'    => "tbl_contact",
                                        'targetKey' => "contact_name",
                                        'flags'     => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "check_command",
                                        'target'    => "",
                                        'targetKey' => "host_name",
                                        'flags'     => "1,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_host",
                                        'fieldName' => "event_handler",
                                        'target'    => "",
                                        'targetKey' => "host_name",
                                        'flags'     => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_service",
                                        'fieldName' => "check_command",
                                        'target'    => "",
                                        'targetKey' => "config_name,service_description",
                                        'flags'     => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_service",
                                        'fieldName' => "event_handler",
                                        'target'    => "",
                                        'targetKey' => "config_name,service_description",
                                        'flags'     => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_hosttemplate",
                                        'fieldName' => "check_command",
                                        'target'    => "",
                                        'targetKey' => "template_name",
                                        'flags'     => "1,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_servicetemplate",
                                        'fieldName' => "check_command",
                                        'target'    => "",
                                        'targetKey' => "template_name",
                                        'flags'     => "1,2,2,0");
                return(1);
      
            case "tbl_contact":
                $arrRelations[] = array('tableName' => "tbl_lnkContactgroupToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "1,2,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToCommandHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_command",
                                  'targetKey' => "command_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToCommandService",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_command",
                                  'targetKey' => "command_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToContacttemplate",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contacttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToVariabledefinition",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_variabledefinition",
                                  'targetKey' => "name",
                                  'flags'   => "0,0,0,2");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_serviceescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToContact",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "1,1,0,1");
                return(1);

            case "tbl_contactgroup":
                $arrRelations[] = array('tableName' => "tbl_lnkContactgroupToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactgroupToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactgroupToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_contacttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_serviceescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToContactgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "1,1,0,1");
                return(1);

            case "tbl_contacttemplate":
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToCommandHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_command",
                                  'targetKey' => "command_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToCommandService",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_command",
                                  'targetKey' => "command_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToContacttemplate",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contacttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToContacttemplate",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_contacttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkContacttemplateToVariabledefinition",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_variabledefinition",
                                  'targetKey' => "name",
                                  'flags'   => "0,0,0,2");
                $arrRelations[] = array('tableName' => "tbl_lnkContactToContacttemplate",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_host":
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHost_DH",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostdependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHost_H",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostdependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostgroupToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToHosttemplate",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToVariabledefinition",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_variabledefinition",
                                  'targetKey' => "name",
                                  'flags'   => "0,0,0,2");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHost_DH",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicedependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHost_H",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicedependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_serviceescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToHost",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicegroupToService",
                                  'fieldName' => "idSlaveH",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_hostextinfo",
                                  'fieldName' => "host_name",
                                  'target'  => "",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,0");
                $arrRelations[] = array('tableName' => "tbl_serviceextinfo",
                                  'fieldName' => "host_name",
                                  'target'  => "",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,0");
                return(1);

            case "tbl_hostdependency":
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHostgroup_DH",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHostgroup_H",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHost_DH",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHost_H",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_hostescalation":
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_hostextinfo":
                return(0);

            case "tbl_hostgroup":
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHostgroup_DH",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostdependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostdependencyToHostgroup_H",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostdependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostescalationToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostgroupToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostgroupToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostgroupToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHostgroup_DH",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicedependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHostgroup_H",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicedependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_serviceescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToHostgroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicegroupToService",
                                  'fieldName' => "idSlaveHG",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_hosttemplate":
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToHosttemplate",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToHosttemplate",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_hosttemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkHosttemplateToVariabledefinition",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_variabledefinition",
                                  'targetKey' => "name",
                                  'flags'   => "0,0,0,2");
                $arrRelations[] = array('tableName' => "tbl_lnkHostToHosttemplate",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_service":
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToService_DS",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicedependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToService_S",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicedependency",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToService",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_serviceescalation",
                                  'targetKey' => "config_name",
                                  'flags'   => "1,1,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicegroupToService",
                                  'fieldName' => "idSlaveS",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToServicegroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToServicetemplate",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToVariabledefinition",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_variabledefinition",
                                  'targetKey' => "name",
                                  'flags'   => "0,0,0,2");
                $arrRelations[] = array('tableName' => "tbl_serviceextinfo",
                                  'fieldName' => "service_description",
                                  'target'  => "",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,0");
                return(1);

            case "tbl_servicedependency":
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHostgroup_DH",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHostgroup_H",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHost_DH",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToHost_H",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToService_DS",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicedependencyToService_S",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_serviceescalation":
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceescalationToService",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_serviceextinfo":
                return(0);

            case "tbl_servicegroup":
                $arrRelations[] = array('tableName' => "tbl_lnkServicegroupToService",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicegroupToServicegroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicegroupToServicegroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToServicegroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToServicegroup",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_servicetemplate":
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToContact",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contact",
                                  'targetKey' => "contact_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToContactgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_contactgroup",
                                  'targetKey' => "contactgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToHost",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_host",
                                  'targetKey' => "host_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToHostgroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_hostgroup",
                                  'targetKey' => "hostgroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToServicegroup",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_servicegroup",
                                  'targetKey' => "servicegroup_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToServicetemplate",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToServicetemplate",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_servicetemplate",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkServicetemplateToVariabledefinition",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_variabledefinition",
                                  'targetKey' => "name",
                                  'flags'   => "0,0,0,2");
                $arrRelations[] = array('tableName' => "tbl_lnkServiceToServicetemplate",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_service",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,0,0,1");
                return(1);

            case "tbl_timeperiod":
                $arrRelations[] = array('tableName' => "tbl_lnkTimeperiodToTimeperiod",
                                  'fieldName' => "idMaster",
                                  'target'  => "tbl_timeperiod",
                                  'targetKey' => "timeperiod_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_lnkTimeperiodToTimeperiod",
                                  'fieldName' => "idSlave",
                                  'target'  => "tbl_timeperiod",
                                  'targetKey' => "timeperiod_name",
                                  'flags'   => "0,0,0,1");
                $arrRelations[] = array('tableName' => "tbl_contact",
                                  'fieldName' => "host_notification_period",
                                  'target'  => "",
                                  'targetKey' => "contact_name",
                                  'flags'   => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_contact",
                                  'fieldName' => "service_notification_period",
                                  'target'  => "",
                                  'targetKey' => "contact_name",
                                  'flags'   => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_contacttemplate",
                                  'fieldName' => "host_notification_period",
                                  'target'  => "",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_contacttemplate",
                                  'fieldName' => "service_notification_period",
                                  'target'  => "",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_host",
                                  'fieldName' => "check_period",
                                  'target'  => "",
                                  'targetKey' => "host_name",
                                  'flags'   => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_host",
                                  'fieldName' => "notification_period",
                                  'target'  => "",
                                  'targetKey' => "host_name",
                                  'flags'   => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_hosttemplate",
                                  'fieldName' => "check_period",
                                  'target'  => "",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_hosttemplate",
                                  'fieldName' => "notification_period",
                                  'target'  => "",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_hostdependency",
                                  'fieldName' => "dependency_period",
                                  'target'  => "",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_hostescalation",
                                  'fieldName' => "escalation_period",
                                  'target'  => "",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_service",
                                  'fieldName' => "check_period",
                                  'target'  => "",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_service",
                                  'fieldName' => "notification_period",
                                  'target'  => "",
                                  'targetKey' => "config_name,service_description",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_servicetemplate",
                                  'fieldName' => "check_period",
                                  'target'  => "",
                                  'targetKey' => "template_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_servicetemplate",
                                  'fieldName' => "notification_period",
                                  'target'  => "",
                                  'targetKey' => "template_name",
                                  'flags'   => "1,1,2,0");
                $arrRelations[] = array('tableName' => "tbl_servicedependency",
                                  'fieldName' => "dependency_period",
                                  'target'  => "",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_serviceescalation",
                                  'fieldName' => "escalation_period",
                                  'target'  => "",
                                  'targetKey' => "config_name",
                                  'flags'   => "0,2,2,0");
                $arrRelations[] = array('tableName' => "tbl_timedefinition",
                                  'fieldName' => "tipId",
                                  'target'  => "",
                                  'targetKey' => "id",
                                  'flags'   => "0,0,0,3");
                return(1);

            default:
                return(0);
        }
    }
}