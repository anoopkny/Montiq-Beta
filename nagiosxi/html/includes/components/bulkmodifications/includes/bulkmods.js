/* General Overlay Functions */

$(document).ready(function () {

    $('#option_list').change(function() {
        var type = $('#option_list option:selected').data('type');
        var form_html = '';

        $('#timeperiod_config_option').hide();
        $('#inner_config_option').show();

        switch (type) {

            case 'field':
                var uom = $('#option_list option:selected').data('uom');
                form_html += '<label>Value: <input type="text" size="2" value="" class="form-control" name="field_value"> '+uom+'</label>';
                break;

            case 'oosn':
                form_html += '<label><input type="radio" value="1" name="oosn_value"> on</label>';
                form_html += '<label><input type="radio" value="0" name="oosn_value"> off</label>';
                form_html += '<label><input type="radio" value="2" name="oosn_value"> skip</label>';
                form_html += '<label><input type="radio" value="3" name="oosn_value"> null</label>';
                break;

            case 'dou':
                var input_type = 'checkbox';
                if ($('#option_list option:selected').data('r') == '1') {
                    input_type = 'radio';
                }
                form_html += '<div><label>Hosts:</label>';
                form_html += '<label><input type="'+input_type+'" value="d" name="host_opts_value[]"> d</label>';
                form_html += '<label><input type="'+input_type+'" value="o" name="host_opts_value[]"> o</label>';
                form_html += '<label><input type="'+input_type+'" value="u" name="host_opts_value[]"> u</label></div>';
                form_html += '<div><label>Services:</label>';
                form_html += '<label><input type="'+input_type+'" value="w" name="service_opts_value[]"> w</label>';
                form_html += '<label><input type="'+input_type+'" value="c" name="service_opts_value[]"> c</label>';
                form_html += '<label><input type="'+input_type+'" value="o" name="service_opts_value[]"> o</label>';
                form_html += '<label><input type="'+input_type+'" value="u" name="service_opts_value[]"> u</label></div>';
                break;

            case 'nopts':
                form_html += '<div><label>Hosts:</label>';
                form_html += '<label><input type="checkbox" value="d" name="host_opts_value[]"> d</label>';
                form_html += '<label><input type="checkbox" value="u" name="host_opts_value[]"> u</label>';
                form_html += '<label><input type="checkbox" value="r" name="host_opts_value[]"> r</label>';
                form_html += '<label><input type="checkbox" value="f" name="host_opts_value[]"> f</label>';
                form_html += '<label><input type="checkbox" value="s" name="host_opts_value[]"> s</label></div>';
                form_html += '<div><label>Services:</label>';
                form_html += '<label><input type="checkbox" value="w" name="service_opts_value[]"> w</label>';
                form_html += '<label><input type="checkbox" value="c" name="service_opts_value[]"> c</label>';
                form_html += '<label><input type="checkbox" value="u" name="service_opts_value[]"> u</label>';
                form_html += '<label><input type="checkbox" value="r" name="service_opts_value[]"> r</label>';
                form_html += '<label><input type="checkbox" value="f" name="service_opts_value[]"> f</label>';
                form_html += '<label><input type="checkbox" value="s" name="service_opts_value[]"> s</label></div>';
                break;

            case 'dd':
                if (!$('#timeperiod_config_option').is(':visible')) {
                    $('#timeperiod_config_option').show();
                    $('#inner_config_option').hide();
                }
                break;

            default:
                form_html = 'Not implemented yet.';
                break;

        }

         $('#inner_config_option').html(form_html);
    });

    // When command selection changes
    $('#commands').change(function() {
        var id = $('#commands option:selected').val();
        if (id != 'blank' && id != '') {
            $('#fullcommand').html(command_list[id]);
            $('#command-box').show();
        } else {
            $('#command-box').hide();
        }
        if (id == 'blank') {
            $('.arg-box input').attr('disabled', true);
        } else {
            $('.arg-box input').attr('disabled', false);
        }
    });

    // Host/Service type selection in modify templates
    $('.hs-template-select').click(function() {
        $('#change_templates_selector').hide();
        var type = $(this).data('type');
        $('#bulk_change_templates').show();
        if (type == 'host') {
            $('#templates-hosts').show();
        } else if (type == 'service') {
            $('#templates-services').show();
        }
    });

});

// Get all contact relationships
function getContactRelationships()
{
    var id = $('#contact').val();
    var contact = encodeURI($("#contact option:selected").text());
    url = 'ajaxreqs.php?cmd=getcontacts&contact=' + contact + '&id=' + id;
    $('#relationships').load(url);
}
// Get all contact group relationships
function getContactGroupRelationships()
{
    var id = $('#contactgroup').val();
    var contactgroup = encodeURI($("#contactgroup option:selected").text());
    url = 'ajaxreqs.php?cmd=getcontactgroups&contactgroup=' + contactgroup + '&id=' + id;
    $('#relationships').load(url);
}
function getHostgroupRelationships()
{
    var id = $('#hostgroup').val();
    var hostgroup = encodeURI($("#hostgroup option:selected").text());
    url = 'ajaxreqs.php?cmd=gethostgroups&hostgroup=' + hostgroup + '&id=' + id;
    $('#relationships').load(url);
}
function getParentHostRelationships()
{
    var id = $('#parenthost').val();
    var parenthost = encodeURI($("#parenthost option:selected").text());
    url = 'ajaxreqs.php?cmd=getparentshosts&parenthost=' + parenthost + '&id=' + id;
    $('#relationships').load(url);
}

// Hide all lists that can be toggled
function hide() {
    $(".hidden").hide();
}