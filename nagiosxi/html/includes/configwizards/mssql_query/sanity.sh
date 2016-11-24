#!/bin/bash

#~ 'zipit' is a special function. In the event you want the sanity checker
#~ to bundle certain files into a zip when the user is sending a zip file
#~ back to support, for example special log files or config files, simply
#~ echo their filenames in the zipit function.
#~ 
#~ In this example, I want to include every single file in the /tmp directory
#~ that starts with mssql- and ends with .tmp, so I use the * syntax to grab
#~ all such files.
#~ 
#~ This way, when the users requests a zip file from the web interface,
#~ it will include all of these mssql.tmp files in order to help us with 
#~ troubleshooting.
#~ 
#~ Please note that this function does not have to be declared. However, if
#~ you do declare it, it *MUST* be before the import of the sanitylib.sh
#~ below.
#~ 
#~ Another gotcha, is that this function cannot echo anything besides files
#~ to be zipped up.
function zipit() {
    echo /tmp/mssql-*.tmp
}

#~ Include general library (should go in all sanity scripts.)
if [ ! -f /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh ];then
    echo "Sanity Checks Component not installed"
    exit 1
else 
    . /usr/local/nagiosxi/html/includes/components/sanitychecks/sanitylib.sh
fi

#~ Make sure the plugin is executable by Nagios, we use the global 
#~ variable $LIBEXEC,  set in sanitylib.sh variable to save us the 
#~ trouble of typing out /usr/local/nagios/libexec before each file.
can_nagios_execute  $LIBEXEC/check_mssql

#~ Now we make sure the yum packages are installed. 
are_these_packages_installed freetds freetds-devel php php-mssql

#~ Next we'll do some specific checking, do the host templates for wizard
#~ exist? If they don't we want to know about it!
templates_exist xiwizard_mssqlquery_host xiwizard_mssqlquery_service

#~ Now we ensure that the command definitions installed by the wizard
#~ exist in the commands.cfg file, where the default location is.
commands_exist check_xi_mssql_query

#~ Notice we did not keep track of any of our errors, the script did.
#~ So we call print_results, and if there were any errors, they will be
#~ printed out at the end, and the script will exit out with the proper
#~ return codes.
print_results
