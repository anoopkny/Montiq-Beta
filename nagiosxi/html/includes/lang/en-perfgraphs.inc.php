<?php

// an array of friendy data source name (by template) used in performance graphs
$lstr['PerfGraphDatasourceNames'] = array(

    "defaults" => array( // defaults are used if a specific template name cannot be found
        "time" => _("Time"),
        "size" => _("Size"),
        "pl" => _("Packet Loss"),
        "rta" => _("Round Trip Average"),
        "load1" => _("1 Minute Load"),
        "load5" => _("5 Minute Load"),
        "load15" => _("15 Minute Load"),
        "users" => _("Users"),
    ),

    // specific template names
    "check_ping" => array(
        "rta" => _("Round Trip Average"),
        "pl" => _("Packet Loss"),
    ),
    "check_http" => array(
        "time" => _("Response Time"),
        "size" => _("Page Size"),
        "ds1" => _("Response Time"),
        "ds2" => _("Page Size"),
    ),
    "check_dns" => array(
        "time" => _("Response Time"),
    ),

    // custom template names
    "check_local_load" => array(
        "ds1" => _("CPU Load"),
    ),

);

