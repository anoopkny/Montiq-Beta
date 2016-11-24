#!/usr/bin/perl -w
#
# Script to print guests from a VM host.
#
# Bassed off of:
# Nagios plugin to monitor vmware esx servers
#
# License: GPL
# Copyright (c) 2008 op5 AB
# Author: Kostyantyn Gushchyn <dev@op5.com>
# Contributor(s): Patrick Müller, Jeremy Martin, Eric Jonsson, stumpr, John Cavanaugh, Libor Klepac
#
# For direct contact with any of the op5 developers send a mail to
# dev@op5.com
# Discussions are directed to the mailing list op5-users@op5.com,
# see http://lists.op5.com/mailman/listinfo/op5-users
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License version 2 as
# published by the Free Software Foundation.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
# $Id$

use strict;
use warnings;
use vars qw($PROGNAME $VERSION $output $values $result);
use Getopt::Std;
use File::Basename;
my $perl_module_instructions="
Download the latest version of Perl Toolkit from VMware support page.
In this example we use VMware-vSphere-SDK-for-Perl-4.0.0-161974.x86_64.tar.gz,
but the instructions should apply to newer versions as well.

Upload the file to your op5 Monitor server's /root dir and execute:

    cd /root
    tar xvzf VMware-vSphere-SDK-for-Perl-4.0.0-161974.x86_64.tar.gz
    cd vmware-vsphere-cli-distrib/
    ./vmware-install.pl

Follow the on screen instructions, described below:

  \"Creating a new vSphere CLI installer database using the tar4 format.

  Installing vSphere CLI.

  Installing version 161974 of vSphere CLI

  You must read and accept the vSphere CLI End User License Agreement to
  continue.
  Press enter to display it.\"

    <ENTER>

  \"Read through the License Agreement\"
  \"Do you accept? (yes/no)

    yes


  \"The following Perl modules were found on the system but may be too old to work
  with VIPerl:

  Crypt::SSLeay
  Compress::Zlib\"

  \"In which directory do you want to install the executable files? [/usr/bin]\"

    <ENTER>

  \"Please wait while copying vSphere CLI files...

  The installation of vSphere CLI 4.0.0 build-161974 for Linux completed
  successfully. You can decide to remove this software from your system at any
  time by invoking the following command:
  \"/usr/bin/vmware-uninstall-vSphere-CLI.pl\".

  This installer has successfully installed both vSphere CLI and the vSphere SDK
  for Perl.
  Enjoy,

  --the VMware team\"

Note: \"Crypt::SSLeay\" and \"Compress::Zlib\" are not needed for check_esx3 to work.
";

eval {
        require VMware::VIRuntime
} or
die "Missing perl module VMware::VIRuntime. Download and install \'VMware Infrastructure (VI) Perl Toolkit\', available at http://www.vmware.com/download/sdk/\n"
        ."$perl_module_instructions";

use VMware::VIRuntime;
use VMware::VILib;

$PROGNAME = basename($0);
$VERSION = '0.0.1';

my %opts;
getopt('DHfup', \%opts);

eval
{
        die "Provide either Password/Username or Auth file\n" if (
				(!defined($opts{'p'}) || !defined($opts{'u'}) || defined($opts{'f'})) &&
				(defined($opts{'p'}) || defined($opts{'u'}) || !defined($opts{'f'})));
        if (defined($opts{'f'}))
        {
                open (AUTH_FILE, $opts{'f'}) || die
						"Unable to open auth file \"$opts{'f'}\"\n";
                while( <AUTH_FILE> ) {
                        if(s/^[ \t]*username[ \t]*=//){
                                s/^\s+//;s/\s+$//;
                                $opts{'u'} = $_;
                        }
                        if(s/^[ \t]*password[ \t]*=//){
                                s/^\s+//;s/\s+$//;
                                $opts{'p'} = $_;
                        }
                }
                die "Auth file must contain both username and password\n" if (
						!(defined($opts{'u'}) && defined($opts{'p'})));
        }

        if (defined($opts{'D'}))
        {
		Opts::set_option('datacenter',$opts{'D'});
		Opts::set_option('username',$opts{'u'});
		Opts::set_option('password',$opts{'p'});
        }
        elsif (defined($opts{'H'}))
        {
		Opts::set_option('server',$opts{'H'});
		Opts::set_option('username',$opts{'u'});
		Opts::set_option('password',$opts{'p'});
        }
        else
        {
                die "No Host or Datacenter specified";
        }
};

Opts::parse();
Opts::validate();
Util::connect();

# get all inventory objects of the specified type
foreach my $vm (@{Vim::find_entity_views(view_type => 'VirtualMachine')}) {
	$vm->update_view_data();
	defined $vm->name and print $vm->name;
	print "\0";
	defined $vm->config->alternateGuestName and print $vm->config->alternateGuestName;
	print "\0";
	defined $vm->guest->ipAddress and print $vm->guest->ipAddress;
	print "\0";
	defined $vm->runtime->powerState->val and print $vm->runtime->powerState->val;
	print "\n";
}

Util::disconnect();
