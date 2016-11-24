var component_base = base_url + '/includes/components/birdseye/';
var api_url = component_base + 'ajaxreqs.php';
//var api_url = component_base + 'up.html';

var num = '';
var HOSTS;
var MASONRY;
var show_handled = 0;
var show_soft = 0;

$(document).ready(function () {

    // Lights on/off
    $("#toggle-lights").click(function () {
        var b = $('body');
        if (b.hasClass('day')) {
            b.removeClass('day').addClass('night');
            $(this).attr("src", "images/lightbulb_off.png");
            $(this).attr("title", "Turn lights on!");
        } else {
            b.removeClass('night').addClass('day');
            $(this).attr("src", "images/lightbulb.png");
            $(this).attr("title", "Turn lights off!");
        }
    });

    // Display simple JS clock
    setInterval('update_clock()', 1000);

    // Display updates
    setInterval('update_display_view(show_handled)', 10000);
    setInterval('update_display_view(show_soft)', 10000);
    setInterval('update_display_list(show_handled)', 10000);
    setInterval('update_display_list(show_soft)', 10000);

    create_display_view(show_handled);
    create_display_view(show_soft);
    update_display_list(show_handled);
    update_display_list(show_soft);
    update_clock();
});

function do_masonry() {
    var container = $('#be-hosts');
    container.masonry();
    MASONRY = container.data('masonry');
}

function change_handled() {
    if (show_handled == 0){
        show_handled = 1;
        $(".handled").html('Showing Handled');
    } else {
        show_handled = 0;
        $(".handled").html('Hiding Handled');
    }
    
    update_display_view(show_handled);
}

function change_soft() {
    if (show_soft == 0){
        show_soft = 1;
        $(".soft").html('Showing Soft States');
    } else {
        show_soft = 0;
        $(".soft").html('Hiding Soft States');
    }

    update_display_view(show_soft);
}

function create_display_view() {
    $.getJSON(api_url, {"mode": "get_all_down", "show_handled": show_handled, "show_soft": show_soft}, function (data) {
        HOSTS = data;
        $.each(data, function (key, host) {
            if (!$('#'+ host.host_id).length) {
                var newblock = create_host_div(host);
                newblock.appendTo('#be-hosts');
            }
        });

        // Generate the MASONRY
        do_masonry();
    });
}

function update_display_view() {
    $.getJSON(api_url, {"mode": "get_all_down", "show_handled": show_handled, "show_soft": show_soft}, function (data) {

        // Loop through new data and add any hosts/services that are now down
        $.each(data, function (key, host) {

            if (!$('#' + host.host_id).length) {
                var newblock = create_host_div(host);
                add_to_masonry(newblock);
            }

        });

        // Loop through old data and remove and hosts/service not down anymore
        $('#be-hosts .bl').each(function () {
            var block = $(this);
            var host_id = $(this).attr('id');
            var del = true;

            $.each(data, function (key, host) {

                if (host_id == host.host_id) {
                    del = false;

                    var be_services = $('#' + host_id + ' p a.service');
                    if (be_services.length > 0) {

                        // Add a service if it doesn't exist
                        $.each(host.down_services, function (key, service) {
                            var add_service = true;
                            be_services.each(function () {
                                be_service_name = $(this).html();
                                if (service.service_name == be_service_name) {
                                    add_service = false;
                                }
                            });

                            if (add_service) {
                                create_service_p(host, service).appendTo($('#' + host_id + ' .be-host-block'));
                                MASONRY.layout();
                            }
                        });

                        // Remove a service if it no longer exists
                        be_services.each(function () {
                            var del_service = true;
                            be_service_name = $(this).html();
                            $.each(host.down_services, function (key, service) {
                                if (service.service_name == be_service_name) {
                                    del_service = false;
                                }
                            });

                            if (del_service) {
                                // Remove the service
                                $(this).parent().remove();
                            }
                        });
                    }

                }
            });

            if (del) {
                remove_form_masonry($('#' + host_id));
            }
        });

    });
}

function create_host_div(host) {
    // Create a new div
    var block = $('<div>', {id: host.host_id, 'class': 'bl'});
    var inside = $('<div class="be-host-block ' + host.type + '"></div>').appendTo(block);
    $('<h4><a class="host" href="../xicore/status.php?show=hostdetail&host=' + host.name + '">' + host.name + '</a></h4>').appendTo(inside);

    // Loop through services
    if (host.down_services.length > 0) {
        $.each(host.down_services, function (key, x) {
            create_service_p(host, x).appendTo(inside);
        });
    }

    return block;
}

function create_service_p(host, x) {
    return $('<p><a href="../xicore/status.php?show=servicedetail&host=' + host.name + '&service=' + x.service_name + '" class="service">' + x.service_name + '</a></p>')
}

// Remove from MASONRY
function remove_form_masonry(elm) {
    MASONRY.remove(elm);
    MASONRY.layout();
}

// Add to MASONRY
function add_to_masonry(elm) {
    elm.appendTo('#be-hosts');
    MASONRY.appended(elm);
    MASONRY.layout();
}

function update_display_list() {
    $.get(api_url, {mode: "get_state_history"}, function (sh) {

        if (sh.msg) {
            $('#be-status').html(sh.msg);
        } else {
            var blocks = '';
            $.each(sh, function (k, v) {
                var newblock = '';
                newblock += '<div class="state">';
                newblock += '<strong style="line-height: 20px; font-size: 13px;"><img src="images/' + v.state_image + '.png" style="vertical-align: text-top; margin-right: 6px;">' + v.host_name;
                if (v.service_description) {
                    newblock += ' - ' + v.service_description;
                }
                newblock += '</strong>';
                newblock += '<p>' + v.state_time + ' - ' + v.output + '</p>';
                newblock += '</div>';
                blocks += newblock;
            });
            $('#be-status').html(blocks);
        }

    }, 'json');
}

function update_clock() {
    var time = new Date();
    var hours = time.getHours();
    var minutes = time.getMinutes();
    var seconds = time.getSeconds();

    minutes = ( minutes < 10 ? "0" : "" ) + minutes;
    seconds = ( seconds < 10 ? "0" : "" ) + seconds;

    var post = ( hours < 12 ) ? "AM" : "PM";
    hours = ( hours > 12) ? hours - 12 : hours;
    hours = ( hours == 0 ) ? 12 : hours;

    var str = hours + ":" + minutes + ":" + seconds + " " + post;
    $('#clock').html(str);
}
