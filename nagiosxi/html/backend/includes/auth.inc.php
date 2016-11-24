<?php
// BACKEND AUTHENTICATION FUNCTIONS
//
// Copyright (c) 2008-2015 Nagios Enterprises, LLC.  All rights reserved.
//  
// $Id$

// Bail out if user is not authenticated
function check_backend_authentication()
{
    if (is_backend_authenticated() == false) {
        handle_backend_error("Authentication Failure");
    }
}

// checks if user is authenticated
// MOVED TO utils-backend.inc.php  11/19/09
// function is_backend_authenticated()
