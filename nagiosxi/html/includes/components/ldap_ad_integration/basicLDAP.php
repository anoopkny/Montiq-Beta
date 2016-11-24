<?php
//
// Basic LDAP class to mimic adLDAP functionality for easier usage of the LDAP/AD component
// Copyright 2014-2016 - Nagios Enterprises, LLC. All rights reserved.
//

class basicLDAP {

    const LDAP_FOLDER = 'OU';
    const LDAP_CONTAINER = 'CN';

    public $type = "ldap";

    protected $host = "";
    protected $port = "389";
    protected $security = "";

    // Connection objects
    protected $ldapConnection;
    protected $ldapBind;
    protected $baseDn;
    
    function __construct($host, $port, $baseDn="", $security="")
    {
        if (!empty($host)) { $this->host = $host; }
        if (!empty($port)) { $this->port = $port; }
        if (!empty($security)) { $this->security = $security; }
        if (!empty($baseDn)) { $this->baseDn = $baseDn; }

        return $this->connect();
    }

    // Connects to the LDAP server
    protected function connect()
    {
        if ($this->security == "ssl") {
            $this->ldapConnection = ldap_connect("ldaps://" . $this->host, $this->port);
        } else {
            $this->ldapConnection = ldap_connect($this->host, $this->port);
        }

        // Start TLS if we are using it (close connection if we can't use TLS)
        if ($this->security == "tls") {
            $v = ldap_start_tls($this->ldapConnection);
            if (!$v) {
                $this->close();
                return false;
            }
        }

        ldap_set_option($this->ldapConnection, LDAP_OPT_PROTOCOL_VERSION, 3);

        return true;
    }

    // Authenticates witih the LDAP server
    public function authenticate($dn, $password)
    {
        // Prevent null binding
        if ($dn === NULL || $password === NULL) { return false; } 
        if (empty($dn) || empty($password)) { return false; }

        if (strpos(strtolower($dn), strtolower($this->baseDn)) === false) {
            $dn = 'CN='.$dn.','.$this->baseDn;
        }

        // Bind as the user
        $ret = false;
        $this->ldapBind = @ldap_bind($this->ldapConnection, $dn, $password);
        if ($this->ldapBind){ 
            $ret = true; 
        }

        if ($ret) {
            $this->baseDn = $this->findBaseDn();
        }

        return $ret;
    }

    // Closes the LDAP connection
    public function close() {
        if ($this->ldapConnection) {
            @ldap_close($this->ldapConnection);
        }
    }

    public function findBaseDn() 
    {
        $namingContext = $this->getRootDse(array('namingcontexts'));
        return $namingContext[0]['namingcontexts'][0];
    }
    
    public function getRootDse($attributes = array("*", "+")) {
        if (!$this->ldapBind){ return (false); }
        
        $sr = @ldap_read($this->ldapConnection, NULL, 'objectClass=*', $attributes);
        $entries = @ldap_get_entries($this->ldapConnection, $sr);
        return $entries;
    }

    public function getLdapConnection() {
        return $this->ldapConnection;
    }

    public function getLdapBind() {
        return $this->ldapBind;
    }
    
    // DIRECTORY STRUCTURE

    public function folder_listing($folderName = NULL, $dnType = basicLDAP::LDAP_FOLDER)
    {
        if (!$this->ldapBind) { return false; }
        $filter = '(&(objectClass=*)';

        // If the folder name is null then we will search the root level of AD
        // This requires us to not have an OU= part, just the base_dn
        $searchOu = $this->baseDn;
        if (is_array($folderName)) {
            $ou = $dnType . '="' . implode('",' . $dnType . '="', $folderName) . '"';
            $ou = str_replace(array('(', ')'), array('\\28', '\\29'), $ou);
            $filter .= '(!(distinguishedname=' . $ou . ',' . $this->baseDn . ')))';
            $searchOu = $ou . ',' . $this->baseDn;
        } else {
            $bdn = str_replace(array('(', ')'), array('\\28', '\\29'), $this->baseDn);
            $filter .= '(!(distinguishedname=' . $bdn . ')))';
        }

        $sr = ldap_list($this->ldapConnection, $searchOu, $filter);
        $entries = ldap_get_entries($this->ldapConnection, $sr);

        if (is_array($entries)) {
            return $entries;
        }
        return false;
    }

    public function user_info($dn)
    {
        $sr = ldap_search($this->ldapConnection, $dn, '(objectclass=*)');
        $entries = ldap_get_entries($this->ldapConnection, $sr);

        if (is_array($entries)) {
            return $entries;
        } 
        return false;
    }
}