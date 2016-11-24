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
// 
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
 * Fetches servicegroups from XI backend, adds or syncs them depending on if they exist or not.
 *
 * @return mixed array(int $errors, string $message)
 */
function build_bpi_servicegroups()
{
    global $bpi_objects;
    $errors = 0;
    $msg = '';
    $addString = '';
    $xml = get_xml_servicegroup_member_objects();

    foreach ($xml->servicegroup as $servicegroup) {
        $key = "$servicegroup->servicegroup_name";
        $id = str_replace(' ', '', $key);
        $array = array('groupTitle' => 'SG: ' . $key,
            'groupID' => $id, //strip whitespace
            'members' => array(),
            'groupDisplay' => 0,
            'groupPrimary' => 1,
            'groupType' => 'servicegroup',
        );

        foreach ($servicegroup->members->service as $member) {
            $array['members'][] = "{$member->host_name};{$member->service_description}";
        }

        // Add or edit
        if (!isset($bpi_objects[$id])) {
            $addString .= process_post($array);
        } else {

            // Add existing group properties back into array for data persistance
            $array['critical'] = array();
            $obj = $bpi_objects[$id];

            // Handle existing members
            $members = $obj->get_memberlist();
            foreach ($members as $member) {
                if ($member['option'] == '|') $array['critical'][] = $member['host_name'] . ';' . $member['service_description'];
            }

            // Handle existing thresholds
            $array['groupWarn'] = $obj->warning_threshold;
            $array['groupCrit'] = $obj->critical_threshold;

            // Handle group description
            $array['groupDesc'] = $obj->desc;

            // Handle existing display priority
            $array['groupPrimary'] = $obj->primary;

            // Handle primary display group
            $array['groupDisplay'] = $obj->priority;

            // Handle info URL
            $array['groupInfoUrl'] = $obj->info;

            $editString = process_post($array);

            // Commit the changes
            list($error, $errmsg) = edit_group($id, $editString);
            if ($error > 0) $msg .= $errmsg;
            $errors += $error;
        }
    }

    list($error, $errmsg) = add_group($addString);
    $errors += $error;
    $msg .= $errmsg;

    return array($errors, $msg);
}


/**
 * Authorization wrapper for viewing bpi servicegroups.
 *
 * @return string $content main html content string for bpi servicegroups
 */
function bpi_view_servicegroups()
{
    $content = '';
    $content .= enterprise_message() . make_enterprise_only_feature(null, true);

    // Auth check
    if (can_control_bpi_groups($_SESSION['username']) && enterprise_features_enabled()) {
        $content .= '<div class="syncmessage"><a href="index.php?cmd=syncservicegroups" class="btn btn-sm btn-primary" title="' . _('Sync Servicegroups') . '"><i class="fa fa-refresh l"></i> ' . _('Sync Servicegroups') . '</a></div>';
    }

    // Explain why features are hidden
    if (!enterprise_features_enabled()) {
        $content .= '<div class="syncmessage">' . _('BPI servicegroups feature is only available for Nagios XI Enterprise Edition') . '</div>';
        return $content;
    }

    $content .= bpi_view_object_html('servicegroup');
    return $content;
}
