<?php
// Nagios BPI (Business Process Intelligence) 
// Copyright (c) 2010-2016 Nagios Enterprises, LLC.
//
// LICENSE:
//
// This work is made available to you under the terms of Version 2 of
// the GNU General Public License. A copy of that license should have
// been provided with this software, but in any event can be obtained
// from http://www.fsf.org.
// This work is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
// General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA
// 02110-1301 or visit their web page on the internet at
// http://www.fsf.org.
//
//
// CONTRIBUTION POLICY:
//
// (The following paragraph is not intended to limit the rights granted
// to you to modify and distribute this software under the terms of
// licenses that may apply to the software.)
//
// Contributions to this software are subject to your understanding and acceptance of
// the terms and conditions of the Nagios Contributor Agreement, which can be found 
// online at:
//
// http://www.nagios.com/legal/contributoragreement/
//
//
// DISCLAIMER:
//
// THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
// INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
// PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
// HOLDERS BE LIABLE FOR ANY CLAIM FOR DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
// OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE 
// GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) OR OTHER
// LIABILITY, WHETHER IN AN ACTION OF CONTRACT, STRICT LIABILITY, TORT (INCLUDING 
// NEGLIGENCE OR OTHERWISE) OR OTHER ACTION, ARISING FROM, OUT OF OR IN CONNECTION 
// WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


/**
 * Main page routing NOTE: there is a second page routing function on bpi_display.php that routes the ajax based content.
 *
 * @param string $cmd a command option so this function can be specified from the command-line API
 *
 * @global        $bpi_options
 * @return string $content pre-built html output to pass to the browser
 */
function bpi_page_router($cmd = false)
{
    global $bpi_options;

    // Processes $_GET and $_POST data
    if (!$cmd) $cmd = grab_request_var('cmd', false);
    $tab = grab_request_var('tab', 'high');
    $tab = preg_replace('/[^A-Za-z0-9\-]/', '', $tab);
    $msg = grab_request_var('msg', '');
    $valid_tabs = array('low', 'medium', 'high', 'hostgroups', 'servicegroups', 'default', 'all', 'add');

    // Page content string
    $content = '';
    $content .= unserialize($msg);

    if ($cmd) {
        $errors = 0;

        // Auth check
        if (CLI == false && !can_control_bpi_groups()) {
            return "<div class='error'>" . _('You are not authorized to access this feature.') . "</div>";
        }

        switch ($cmd) {

            case 'delete':
                list($errors, $msg) = handle_delete_command($content);
                break;

            case 'edit':
                $init_msg = bpi_init('all', false);
                list($errors, $msg) = handle_edit_command($content);
                break;

            case 'add':
                $init_msg = bpi_init('all', false);
                list($errors, $msg) = handle_add_command($content);
                break;

            case 'fixconfig':
                $init_msg = bpi_init('all', false);
                $content .= $init_msg;
                list($errors, $msg) = handle_fixconfig_command($content);
                break;

            case 'synchostgroups':
                if (!enterprise_features_enabled()) {
                    $msg = _('Hostgroup syncing is only available for Nagios XI Enterprise Edition');
                    $errors++;
                } else {
                    $init_msg = bpi_init('all', false);
                    list($errors, $msg) = build_bpi_hostgroups();
                }
                break;

            case 'syncservicegroups':
                if (!enterprise_features_enabled()) {
                    $msg = _('Servicegroup syncing is only available for Nagios XI Enterprise Edition');
                    $errors++;
                } else {
                    $init_msg = bpi_init('all', false);
                    list($errors, $msg) = build_bpi_servicegroups();
                }
                break;

            case 'checkgroupstatus':
                if (CLI == false) die('Illegal action!');
                break;

            default:
                return _("Unknown command");

        }

        // Generic error handler
        if ($errors > 0) {
            $content .= "<div class='error'><strong>" . _("Error") . "</strong>: $msg</div>";
        }

        // Display a generic success message for a config change?
        if ($errors == 0 &&
            (isset($_REQUEST['addSubmitted']) ||
                isset($_REQUEST['editSubmitted']) ||
                isset($_REQUEST['configeditor']) ||
                $cmd == 'delete' ||
                $cmd == 'synchostgroups' ||
                $cmd == 'syncservicegroups'
            )) {
            $content .= "<div class='success'>$msg</div>";
        }

        return $content;
    }

    if (in_array($tab, $valid_tabs)) {
        $_SESSION['tab'] = $tab;
        $content .= '<div id="notes" class="note"><i class="fa fa-dot-circle-o bpi-tt-bind" title="'._('Essential member').'"></i> - ' . _("Essential group members");
        if ($bpi_options['IGNORE_PROBLEMS'] == true)
            $content .= "<br />" . _("Handled problems are denoted with") . ": <img src='images/enable_small2.png' height='10' width='10' alt='' />";

        $content .= "<br /></div>
        <script type='text/javascript'>
            $(document).ready(function() {
                bpi_load(); 
            }); 
        </script>
        <div id='bpiContent'><i class='fa fa-spinner fa-spin' style='font-size: 16px;'></i></div>";
    } else {
        echo _("Invalid tab");
    }

    return $content;
}
