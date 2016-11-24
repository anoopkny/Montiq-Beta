<?php
//
//  Nagios Core Config Manager
//  Copyright (c) 2010-2016 Nagios Enterprises, LLC
//
//  File: Db.php
//  Desc: Database handler for CCM... does some things instead of using the default
//        old NagiosQL database class.
//

class Db
{
    // Retain last query for debugging 
    var $last_query = '';
    var $affected_rows = 0;
    var $message = '';
    var $error = '';
    var $mydb;
    
    /**
     * Establishes DB connection upon initialization 
     */ 
    function __construct()
    {
        $this->connect_select(); 
    }
    
    /**
     * Close DB connection upon de-initialization 
     */ 
    function __deconstruct()
    {
        $this->mydb->close(); 
    }
     
    /**
     * Displays formatted error message
     *
     * @param string $query the query that was attempted 
     */
    function display_error($query)
    {
        print '<p class="error">Could complete the query because: <br />'.$this->mydb->error.'</p>';
        print '<p class="error">The query being run was: '.$query.'</p>';
    }

    /**
     * This function is not used in production
     *
     * @deprecated no longer used
     * @param string $msg
     */
    function success($msg='')
    {
        if ($msg != '') {
            print "<p class='success'>$msg</p>";    
        } else {
            print "<p>Your transaction was successful!</p>";
        }
    }
    
    /**
     * Establishes DB connection upon initialization 
     */ 
    private function connect_select()
    {
        global $CFG;

        if ($this->mydb = new MySQLi($CFG['db']['server'], $CFG['db']['username'], $CFG['db']['password'], $CFG['db']['database'], $CFG['db']['port'])) {
            $this->mydb->set_charset('utf8');
            $this->mydb->query("set names 'utf8'");
            return true;
        } else {
            print '<p class="error">Error connecting to database.</p>';
            return false;
        }
    }
        
    /**
     * Executes an SQL query, returns results as an associative array OR returns NULL
     *
     * @param string $query  the SQL query to be run
     * @param bool $return do we want the data back? 
     * @return mixed null | associative array with SQL results, if $return == false return mysql_error() string
     */ 
    function query($query, $return=true)
    {
        $result = $this->mydb->query($query);
        $this->last_query = $query;
        $this->affected_rows = $this->mydb->affected_rows;

        if ($result === false) {
            $this->error = $this->mydb->error;
            return false;
        } else if ($result === true) {
            return true;
        }

        if ($return) {
            $data = array();
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
            }
            $result->free();
            return $data;
        }
    }
    
    /**
     * Generic search $tbl WHERE $field = $keywork function 
     * 
     * @param $tbl
     * @param $field
     * @param $keyword
     * @return array
     */
    function search_query($tbl, $field, $keyword)
    {
        $query = "SELECT * FROM `$tbl` WHERE `$field`=$keyword;";
        $result = $this->mydb->query($query);
        $data = array();
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        return $data;
    }

    /**
     * Generic insert wrapper function
     *
     * @param $query
     */
    function insert_query($query)
    {
        // Execute the query
        if ($this->mydb->query($query) !== false) {
            print '<p>The DB entry has been added!</p>';
            print '<p><a href="index.php">Return To Main Page</a></p>';
        } else {
            $this->display_error($query);
        }
    }

    /**
     * Grabs id and name field from a selected table. Use for select lists.
     *
     * @param $type Nagios object type (host, service, etc)
     * @return array
     */
    function get_tbl_opts($type)
    {
        // Retrieve list of hostnames and id's from DB
        global $FIELDS; 
        $table = "tbl_".$type;

        // Change name directive for templates 
        if ($type == 'hosttemplate' || $type == 'servicetemplate' || $type == 'contacttemplate') {
            $type = 'template';
        }
        $query = "SELECT id,active,".$type."_name FROM `$table`";

        // Add WHERE clause so objects can't have a relationship to themselves 
        if (isset($FIELDS['exactType']) && $FIELDS['exactType'] == $type) {
            $query .="WHERE {$type}_name!='{$FIELDS['hidName']}'";
        }
        $query.=" ORDER BY {$type}_name ASC";

        $results = $this->query($query);
        return $results;
    }

    /**
     * Grabs all fields from commands table. Used for select lists.
     *
     * @param int $type Command type (check command, misc)
     * @return mixed
     */
    function get_command_opts($type=1)
    {
        $query = "SELECT * FROM `tbl_command` WHERE `command_type`=$type ORDER BY `command_name`";
        $results = $this->query($query) or die($this->display_error($query));
        return $results;
    }
    
    /**
     * Checks for table relationships, both master to slave, and slave to master 
     *
     * @param int $id Object id, primary key 
     * @param string $tbl lnkObjectToObject DB table to check 
     * @param bool $opt Used for special calls to get hosts/services/contacts with "use as template" fields
     * @param bool $master = boolean, master to slave, or slave to master?  
     * @return array $results assoc array of SQL results | empty array 
     */
    function find_links($id, $tbl, $master, $opt=false)
    {   
        $key = (($master == 'master') ? 'idMaster' : 'idSlave');
        $table = 'tbl_lnk'.$tbl;
        if ($opt == 2) {
            $query = "SELECT * FROM `$table` WHERE `$key`=$id AND idTable=2;"; // Named templates 
        } else if ($opt == 1) {
            $query = "SELECT * FROM `$table` WHERE `$key`=$id AND idTable=1;"; // Default template definition 
        } else {
            $query = "SELECT * FROM `$table` WHERE `$key`=$id;";
        }
        $results = $this->query($query);
        if ($results !== false) {
            return $results;
        }
        return array();
    }
    
    /**
     * Link finder for servicegroup to service relationships 
     *
     * @param int $id the object ID to find relationships for 
     * @return string $strings  a string in the following format (hostid::hostgroupID::serviceid) 
     */ 
    function find_service_links($id)
    {
        $table = 'tbl_lnkServicegroupToService';
        $query = "SELECT * FROM `$table` WHERE `idMaster`=$id;";
        $results = $this->query($query);
        if (count($results) == 0) {
            return array();
        } else {
            $strings = array();
            foreach ($results as $r) {
                $strings[] = $r['idSlaveH'].'::'.$r['idSlaveHG'].'::'.$r['idSlaveS'];
            }
            return $strings;
        }
    }

    /**
     * Retrieves array of H:host_name : service_description 
     *
     * @global object $ccmDB 
     * @return array returns a list of services formatted H:host_name : service_description
     */ 
    function get_hostservice_opts()
    {
        global $ccmDB;

        $hostServiceList = array();
        $query = "SELECT a.idSlave as host_id,b.host_name, a.idMaster as service_id,c.service_description, c.active as active FROM tbl_lnkServiceToHost a
            JOIN tbl_host b ON a.idSlave=b.id JOIN tbl_service c ON a.idMaster=c.id ORDER BY b.host_name,c.service_description";
        $links = $ccmDB->query($query);
    
        foreach($links as $lnk) {
            $key = $lnk['host_id'].'::0::'.$lnk['service_id'];
            $hostServiceList[$key] = array('name' => 'H:'.$lnk['host_name'].' : '.$lnk['service_description'], 'active' => $lnk['active']);
        }

        return $hostServiceList;
    }

    /**
     * Takes in an SQL query and retuns the count as an integer
     *
     * @param $query
     * @return int
     */
    function count_results($query)
    {
        $r = $this->query($query);

        if (isset($r[0]['count(*)'])) {
            return $r[0]['count(*)'];
        }
        if (isset($r[0]['COUNT(*)'])) {
            return $r[0]['COUNT(*)'];
        }
        return 0;
    }

    /**
     * Dimple data deletion function for SINGLE deletions. (UNRELIABLE?)
     *
     * @param $table
     * @param $field
     * @param $id
     * @return string
     */
    function delete_entry($table, $field, $id)
    {
        $query = "DELETE FROM tbl_{$table} WHERE `{$field}`='$id';";
        $this->query($query);

        if ($this->affected_rows == 0) {
            $message = "Item $id failed to delete. <br />".$this->mydb->error;
        } else { 
            $message = "Item $id deleted successfully!<br />";
        }

        return $message;
    }

    function escape_string($str) {
        return $this->mydb->real_escape_string($str);
    }

    function get_last_id()
    {
        return $this->mydb->insert_id;
    }
}