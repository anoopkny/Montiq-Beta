<?php
require_once(dirname(__FILE__) . '/../../common.inc.php');

// Initialization stuff
pre_init();
init_session();

// Grab GET or POST variables and check pre-reqs
grab_request_vars();
check_prereqs();
check_authentication(false);

$refreshvalue = 10; // Value in seconds to refresh page
$pagetitle = _("Nagios Operations Screen");

    
$hide_ack_down = grab_request_var('hide_ack_down',"");
if ($hide_ack_down !== "")
     set_user_meta(0, 'opscreen_hide_ack_down', $hide_ack_down, false);
else
    $hide_ack_down = get_user_meta(0, 'opscreen_hide_ack_down');

$hide_soft_states = grab_request_var('hide_soft_states',"");
if ($hide_soft_states !== "")
     set_user_meta(0, 'opscreen_hide_soft_states', $hide_soft_states, false);
else
    $hide_soft_states = get_user_meta(0, 'opscreen_hide_soft_states');

do_page_start(array("page_title" => $pagetitle), true);

?>
<style type="text/css">
    * {
        margin: 0;
        padding: 0;
    }

    body.child {
        font-family: sans-serif;
        line-height: 1.4em;
        font-size: 1.2rem;
        overflow-x: hidden;
        background: #404040;
        padding: .5em 1em;
    }

    table {
        border-collapse: collapse;
        width: 100%;
    }

    td {
        padding: .3em .6em;
    }

    h1 {
        display: inline-block;
        margin-left: 10px;
    }

    h2 {
        margin: 0 0 .2em 0;
        color: white;
        text-shadow: 1px 1px 0 #000;
        font-size: 1em;
    }

    .clear {
        clear: both;
    }

    .head {
    }

    .head th {
    }

    .dash {
    }

    .dash_wrapper {
        background: white;
        padding: 1em;
        border-radius: .5em;
    }

    .dash_unhandled {
        width: 60%;
        float: left;
    }

    .dash_unhandled .dash_wrapper {
        margin-right: 1em;
        margin-bottom: 1em;
    }

    .dash_tactical_overview {
        width: 40%;
        float: left;
    }

    .dash_unhandled_service_problems {
        clear: both;
        margin-top: 0em;
    }

    .dash_table_head {
        background: linear-gradient(-180deg, #d3d3d3, #bdbdbd);
        border: 1px solid #888;
        color: #181818;
        text-shadow: 1px 1px 0 #ededed;
    }

    .dash_table_head th {
        padding: .3em .6em;
        border-bottom: 1px solid #757575;
    }

    .dash_table_head th:first-child {
        border-left: none;
    }

    .dash_table_head th:last-child {
        border-right: none;
    }

    .critical {
        background: red;
        background: -moz-linear-gradient(top center, #af1000 50%, #990000 50%);
        color: white;
        text-shadow: 1px 1px 0 #5f0000;
    }

    .unknown {
        background: -moz-linear-gradient(top center, #FFC45F 50%, #FFC45F 50%);
    }

    .unknown, .warning, .unknown a, .warning a {
        color: black;
        text-shadow: none;
    }

    .critical, .ok, .critical a, .ok a {
        color: white;
        text-shadow: 1px 1px 0 #5f0000;
    }

    .critical td {
        border-right: 1px solid #6f0000;
        border-bottom: 1px solid #6f0000;
    }

    .ok {
        background: green;
        background: -moz-linear-gradient(top center, #00b400 50%, #018f00 50%);
        color: white;
        text-shadow: 1px 1px 0 #015f00;
    }

    .warning {
        background: yellow;
        background: -moz-linear-gradient(top center, yellow 50%, #edef00 50%);
        color: black;
        text-shadow: -1px -1px 0 #feff5f;
    }

    .critical td,
    .ok td,
    .warning td {
    }

    .warning td {
        border-bottom: 1px solid #bdbf00;
        border-right: 1px solid #bdbf00;
    }

    .ok td {
        border-bottom: 1px solid #016f00;
        border-right: 1px solid #016f00;
    }

    .date {
        white-space: nowrap;

    }

    .logo {
        background: white;
        padding: 1em;
        border-radius: .5em;
        float: right;
        margin-top: 25px;
    }

    .logotext {
        font-weight: bold;
        font-size: 16pt;
        text-align: center;
        padding-top: 10px;
    }

    .statusinfo {
        font-size: 14px !important;
    }

    .nagios_statusbar {
        background: gray;
        position: fixed;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 40px;
        text-align: right;
        border-top: 1px solid #818181;
        opacity: .9;
    }

    .nagios_statusbar_item {
        border-left: 2px groove #000;
        height: 40px;
        line-height: 40px;
        padding: 0 1em;
        color: white;
        text-shadow: 1px 1px 0 black;
        position: relative;
        float: right;
    }

    #nagios_placeholder {
        margin-bottom: 55px;
    }

    #loading {
        width: 24px;
        height: 40px;
        position: absolute;
    }

    #refreshing {
        padding-left: 15px;
    }

    #refreshing_countdown {
    }

    #timestamp_wrap {
        cursor: default;
        font-size: 2em;
    }

    .timestamp_stamp {
    }
</style>
<script type="text/javascript">

    var placeHolder,
        refreshValue = <?php print $refreshvalue; ?>;

    $().ready(function () {
        placeHolder = $("#nagios_placeholder");
        updateNagiosData(placeHolder);
        window.setInterval(updateCountDown, 1000);
    });


    // timestamp stuff

    function createTimeStamp() {
        // create timestamp
        var ts = new Date();
        ts = ts.toTimeString();
        ts = ts.replace(/\s+GMT.+/ig, "");
        ts = ts.replace(/\:\d+(?=$)/ig, "");
        $("#timestamp_wrap").empty().append("<div class=\"timestamp_drop\"></div><div class=\"timestamp_stamp\">" + ts + "</div>");
    }

    function updateNagiosData(block) {
        $("#loading").fadeIn(200);
        block.load("./merlin.php", function (response) {
            $(this).html(response);
            $("#loading").fadeOut(200);
            createTimeStamp();
        });
    }

    function updateCountDown() {
        var countdown = $("#refreshing_countdown");
        var remaining = parseInt(countdown.text());
        if (remaining == 1) {
            updateNagiosData(placeHolder);
            countdown.text(remaining - 1);
        }
        else if (remaining == 0) {
            countdown.text(refreshValue);
        }
        else {
            countdown.text(remaining - 1);
        }
    }

</script>

<div id="nagios_placeholder"></div>
<div class="nagios_statusbar">

    <div class="nagios_statusbar_item">
        <div id="timestamp_wrap"></div>
    </div>
    <div class="nagios_statusbar_item">
        <div id="loading"><i class="fa fa-spinner fa-spin"></i></div>
        <p id="refreshing"><?php echo _('Refresh in'); ?> <span id="refreshing_countdown"><?php print $refreshvalue; ?></span> <?php echo _('seconds'); ?></p>
    </div>
    <div class="nagios_statusbar_item timestamp_stamp">
    <?php if ($hide_ack_down == 0) { ?>
        <a href="?hide_ack_down=1" style="color: white;"><?php echo _('Hide Handled'); ?></a>
    <?php } else { ?>
        <a href="?hide_ack_down=0" style="color: white;"><?php echo _('Show Handled'); ?></a>
    <?php } ?>
    </div>
    <div class="nagios_statusbar_item timestamp_stamp">
    <?php if ($hide_soft_states == 0) { ?>
        <a href="?hide_soft_states=1" style="color: white;"><?php echo _('Hide Soft States'); ?></a>
    <?php } else { ?>
        <a href="?hide_soft_states=0" style="color: white;"><?php echo _('Show Soft States'); ?></a>
    <?php } ?>
    </div>
</div>

<?php
do_page_end(true);