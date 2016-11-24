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
 * Main display function for all bpi group trees, filters group's processed by the $arg param.
 *
 * @param string $arg group type to be filtered (high, medium, low, hostgroup, servicegroup
 *
 * @return string $content processed html content based on $arg
 */
function bpi_view_object_html($arg)
{
    global $bpi_objects;
    global $bpi_unique;
    $tds = @unserialize(grab_request_var('tds'));
    $divs = @unserialize(grab_request_var('divs'));
    $sorts = @unserialize(grab_request_var('sorts'));
    $resultCount = 0;
    $content = '';

    // Create javascript for reload
    $content .= "<script type='text/javascript'>
        $(document).ready(function() { ";

    // Toggled groups
    if (is_array($divs)) {
        for ($i = 0; $i < count($divs); $i++) {
            $content .= "reShowHide('{$divs[$i]}','{$tds[$i]}');\n";
        }
    }

    // Sorted groups
    if (is_array($sorts)) {
        foreach ($sorts as $s) {
            $content .= "sortchildren('{$s}',true);";
        }
    }

    $content .= " }); \n\n </script>";
    
    foreach ($bpi_objects as $object) {

        if ($object->get_primary() > 0 && ($object->priority == $arg || $object->type == $arg)) {

            if (!is_authorized_for_bpi_group($object, $_SESSION['username'])) continue;

            $state = return_state($object->state);
            $state_css_class = return_state_css_class($object->state);
            $gpc_icon = '';
            if ($object->has_group_children == true) {
                $gpc_icon = '<td style="width: 28px; text-align: center;"><i class="fa fa-sitemap fa-14 bpi-tt-bind" title="'._('Contains child groups').'"></i></td>';
            }

            $td_id = 'td' . $bpi_unique;
            $info_th = $object->get_info_html();
            $desc_td = (trim($object->desc) == '') ? '' : "<td>{$object->desc}</td>";

            // Display for only primary groups. See the $object->display_tree() for subgroup displays
            $content .= "
             <table class='primary table table-striped table-bordered'>
                <tr>
                    <td class='{$state_css_class} fixedwidth'>{$state}</th>
                    <td class='group'>
                        <a id='{$td_id}' href='javascript:void(0)' title='Group ID: {$object->name}' onclick='showHide(\"{$object->name}\",\"$td_id\")' class='grouphide'>
                            <i class='fa fa-fw fa-chevron-right' style='padding-right: 3px;'></i>" . $object->get_title() . "
                        </a>
                    </td>
                    <td class='sort'>
                        <a class='sortlink bpi-tt-bind' title='"._('Sort by priority')."' href=\"javascript:sortchildren('{$object->name}',false);\">
                            <i class='fa fa-sort fa-14'></i>
                        </a>
                    </td>
                    {$gpc_icon}
                    {$info_th}
                    <td>{$object->status_text}</td>
                    {$desc_td}\n";

            // For auth_users with full permissions
            if (can_control_bpi_groups($_SESSION['username'])) {
                $content .= '<td class="actions"><a class="bpi-tt-bind" href="index.php?cmd=edit&arg='.$object->name.'" title="'._('Edit').'"><img src="'.theme_image('pencil.png').'"></a> <a class="bpi-tt-bind" href="javascript:deleteGroup(\'index.php?cmd=delete&arg='.$object->name.'\')" title="'._('Delete').'"><img src="'.theme_image('cross.png').'"></a></td>';
            }

            $content .= "
                </tr>
            </table>";

            $content .= "<div class='toplevel' id='{$object->name}' style='display:none;'>";

            $object->display_tree($content);
            $content .= "</div>\n\n";
            $bpi_unique++;
            $resultCount++;
        }
    }
    $content .= "";

    if ($resultCount == 0) $content .= "<div class='message'>" . _("No BPI Group results for this filter.") . "</div>";

    return $content;
}
