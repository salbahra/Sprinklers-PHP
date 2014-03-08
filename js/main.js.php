<?php
if(!defined('Sprinklers')) {
    #Start session
    if(!isset($_SESSION)) session_start();

    #Tell main we are calling it
    define('Sprinklers', TRUE);

    #Required files
    require_once "../main.php";

    header("Content-type: application/x-javascript");
}

#Kick if not authenticated
if (!is_auth()) {header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);exit();}

#Echo token so browser can cache it for automatic logins
if (isset($_SESSION['sendtoken']) && $_SESSION['sendtoken']) { echo "localStorage.setItem('token', '".$_SESSION['token']."');\n"; $_SESSION['sendtoken'] = false; }
?>
//Set AJAX timeout
$.ajaxSetup({
    timeout: 6000
});

//Handle timeout
$(document).ajaxError(function(x,t,m) {
    if(t.status==401) {
        location.reload();
    }
    if(t.statusText==="timeout") {
        if (m.url.search("action=get_weather")) {
            $("#weather-list").animate({
                "margin-left": "-1000px"
            },1000,function(){
                $(this).hide();
            })
        } else {
            showerror("<?php echo _("Connection timed-out. Please try again."); ?>")
        }
    }
});

//After main page is processed, hide loading message and change to the page
$(document).one("pagecreate","#sprinklers", function(){
    //Update login popup for use in-app. Logout forces page reload so we don't need to conserve original function
    $("#login form").attr("action","javascript:grab_token()");
    $("#login .ui-checkbox").hide();
    //Overlay was moved here since start page has no height causing the overlay to be too short on iPod/iPhone devices
    $("#login").popup("option","overlayTheme","b");

    update_weather();
    //Use the user's local time for preview and log range calculation
    var now = new Date();
    $("#log_start").val(new Date(now.getTime() - 604800000).toISOString().slice(0,10));
    $("#preview_date, #log_end").val(now.toISOString().slice(0,10));

    //Open the main page
    $("body").pagecontainer("change","#sprinklers",{transition:"none"});

    //Check for updates
    var curr = $("#commit").data("commit");
    if (curr !== null) {
        $.getJSON("https://api.github.com/repos/salbahra/OpenSprinkler-Controller/git/refs/heads/master").done(function(data){
            var newest = data.object.sha;
            if (newest != curr) $("#showupdate").slideDown().delay(5000).slideUp();
        })
    }

    //Indicate loading is complete
    $.mobile.loading("hide");
});

//Handle provider select change on weather settings
$(document).on("change","#weather_provider",function(){
    var val = $(this).val();
    if (val === "wunderground") {
        $("#wapikey").closest("label").show("fast");
    } else {
        $("#wapikey").closest("label").hide("fast");
    }
})

$(window).resize(function(){
    var currpage = $(".ui-page-active").attr("id");
    if (currpage == "logs") {
        showArrows();
        seriesChange();
    }
});

//Automatically update log viewer when switching graphing method
$("#logs input:radio[name='log_type'],#graph_sort input[name='g']").change(get_logs)

//Automatically update the log viewer when changing the date range
$("#log_start,#log_end").change(function(){
    clearTimeout(window.logtimeout);
    window.logtimeout = setTimeout(get_logs,500);
})

//Show tooltip (station name) when point is clicked on the graph
$("#placeholder").on("plothover", function(event, pos, item) {
    $("#tooltip").remove();
    clearTimeout(window.hovertimeout);
    if (item) window.hovertimeout = setTimeout(function(){showTooltip(item.pageX, item.pageY, item.series.label, item.series.color)}, 100);
});

//Update left/right arrows when zones are scrolled on log page
$("#zones").scroll(showArrows)

//Update the preview page on date change
$("#preview_date").change(function(){
    var id = $(".ui-page-active").attr("id");
    if (id == "preview") get_preview()
});

//Bind changes to the flip switches
var switching = false;
$("input[data-role='flipswitch']").change(function(){
    if (switching) return;

    var type = this.name,
        slide = $(this),
        defer, other;

    //Find out what the switch was changed to
    var changedTo = slide.is(":checked");

    //If changed to on
    if (changedTo) {
        //Autologin
        if (type === "autologin") {
            if (localStorage.getItem("token") !== null) return;
            $("#login").popup("open");
        }
        //OpenSprinkler Operation
        if (type === "en") defer = $.get("index.php","action=en_on");
        //Auto disable manual mode
        if (type === "auto_mm") defer = $.get("index.php","action=auto_mm_on");
        //Local assets
        if (type === "local_assets") defer = $.get("index.php","action=local_assets_on");
        //Manual mode, manual mode and settings page
        if (type === "mm" || type === "mmm") {
            other = ((slide.attr("id") === "mm") ? $("#mmm") : $("#mm"));
            defer = $.get("index.php","action=mm_on");
            other.prop("checked",changedTo);
            if (other.hasClass("ui-flipswitch-input")) other.flipswitch("refresh");
        }
    } else {
        //If changed to off
        if (type === "autologin") localStorage.removeItem("token");
        if (type === "en") defer = $.get("index.php","action=en_off");
        if (type === "auto_mm") defer = $.get("index.php","action=auto_mm_off");
        if (type === "local_assets") defer = $.get("index.php","action=local_assets_off");
        if (type === "mm" || type === "mmm") {
            other = ((slide.attr("id") === "mm") ? $("#mmm") : $("#mm"));
            defer = $.get("index.php","action=mm_off").done(function(){
                $("#manual a.green").removeClass("green");
            });
            other.prop("checked",changedTo);
            if (other.hasClass("ui-flipswitch-input")) other.flipswitch("refresh");
        }
    }

    $.when(defer).then(function(reply){
        if (reply == 0) {
            switching = true;
            setTimeout(function(){
                switching = false;
            },200);
            slide.prop("checked",!changedTo).flipswitch("refresh");
            switch (type) {
                case "auto_mm":
                    showerror("<?php echo _("Auto disable of manual mode was not changed. Check config.php permissions and try again."); ?>");
                    break;
                case "local_assets":
                    showerror("<?php echo _("Asset location was not changed. Check config.php permissions and try again."); ?>");
                    break;
                case "mm":
                case "mmm":
                    $.each([slide,other],function(i,m){
                        m.prop("checked",!changedTo)
                        if (m.hasClass("ui-flipswitch-input")) flipswitch("refresh");
                    })
                default:
                    comm_error();
                    break;
            }
        }
    })
});

function comm_error() {
    showerror("<?php echo _("Error communicating with OpenSprinkler. Please check your password is correct."); ?>")
}

$(document).on("pageshow",function(e,data){
    var newpage = "#"+e.target.id;

    if (newpage == "#sprinklers") {
        //Automatically update autologin slider on page load in settings panel
        if (localStorage.getItem("token")) $("#s-autologin").prop("checked",true).flipswitch("refresh");
    } else if (newpage == "#preview") {
        get_preview();
    } else if (newpage == "#logs") {
        get_logs();
    }

    bind_links(newpage);
});

$(document).on("pagebeforeshow",function(e,data){
    var newpage = e.target.id;

    //Remove lingering tooltip from preview page
    $("#tooltip").remove();

    //Remove any status timers that may be running
    if (window.interval_id !== undefined) clearInterval(window.interval_id);
    if (window.timeout_id !== undefined) clearTimeout(window.timeout_id);

    if (newpage == "sprinklers") {
        //Reset status bar to loading while an update is done
        $("#footer-running").html("<p class='ui-icon ui-icon-loading mini-load'></p>");
        setTimeout(check_status,1000);
    } else {
        var title = document.title;
        document.title = "OpenSprinkler: "+title;
    }
})

//Converts data-onclick attributes on page to vclick bound functions. This removes the 300ms lag on mobile devices (iOS/Android)
function bind_links(page) {
    var currpage = $(page);

    currpage.find("a[href='#"+currpage.attr('id')+"-settings']").unbind("vclick").on('vclick', function (e) {
        e.preventDefault(); e.stopImmediatePropagation();
        highlight(this);
        $(".ui-page-active [id$=settings]").panel("open");
    });
    currpage.find("a[data-onclick]").unbind("vclick").on('vclick', function (e) {
        e.preventDefault(); e.stopImmediatePropagation();
        var func = $(this).data("onclick");
        highlight(this);
        eval(func);
    });
}

function check_status() {
    //Check if a program is running
    $.get("index.php","action=current_status",function(data){
        var footer = $("#footer-running")
        if ($.trim(data) === "") {
            footer.slideUp();
            return;
        }
        data = JSON.parse(data);
        if (window.interval_id !== undefined) clearInterval(window.interval_id);
        if (window.timeout_id !== undefined) clearTimeout(window.timeout_id);
        if (data.seconds > 1) update_timer(data.seconds,data.sdelay);
        footer.removeClass().addClass(data.color).html(data.line).slideDown();
    })
}

function update_timer(total,sdelay) {
    window.lastCheck = new Date().getTime();
    window.interval_id = setInterval(function(){
        var now = new Date().getTime();
        var diff = now - window.lastCheck;
        if (diff > 3000) {
            clearInterval(window.interval_id);
            $("#footer-running").html("<p class='ui-icon ui-icon-loading mini-load'></p>");
            check_status();
        }
        window.lastCheck = now;

        if (total <= 0) {
            clearInterval(window.interval_id);
            $("#footer-running").slideUp().html("<p class='ui-icon ui-icon-loading mini-load'></p>");
            if (window.timeout_id !== undefined) clearTimeout(window.timeout_id);
            window.timeout_id = setTimeout(check_status,(sdelay*1000));
        }
        else
            --total;
            $("#countdown").text("(" + sec2hms(total) + " <?php echo _("remaining"); ?>)");
    },1000)
}

function update_timers(sdelay) {
    if (window.interval_id !== undefined) clearInterval(window.interval_id);
    if (window.timeout_id !== undefined) clearTimeout(window.timeout_id);
    window.lastCheck = new Date().getTime();
    window.interval_id = setInterval(function(){
        var now = new Date().getTime(),
            diff = now - window.lastCheck,
            page = $(".ui-page-active").attr("id");

        if (diff > 3000) {
            clearInterval(window.interval_id);
            if (page == "status") get_status();
        }
        window.lastCheck = now;
        $.each(window.totals,function(a,b){
            if (b <= 0) {
                delete window.totals[a];
                if (a == "p") {
                    if (page == "status") get_status();
                } else {
                    $("#countdown-"+a).parent("p").text("<?php echo _('Station delay'); ?>").parent("li").removeClass("green").addClass("red");
                    window.timeout_id = setTimeout(get_status,(sdelay*1000));
                }
            } else {
                if (a == "c") {
                    ++window.totals[a];
                    $("#clock-s").text(new Date(window.totals[a]*1000).toUTCString().slice(0,-4));
                } else {
                    --window.totals[a];
                    $("#countdown-"+a).text("(" + sec2hms(window.totals[a]) + " <?php echo _("remaining"); ?>)");
                }
            }
        })
    },1000)
}

function sec2hms(diff) {
    var str = "";
    var hours = parseInt( diff / 3600 ) % 24;
    var minutes = parseInt( diff / 60 ) % 60;
    var seconds = diff % 60;
    if (hours) str += pad(hours)+":";
    return str+pad(minutes)+":"+pad(seconds);
}

function highlight(button) {
    $(button).addClass("ui-btn-active").delay(150).queue(function(next){
        $(this).removeClass("ui-btn-active");
        next();
    });
}

function grab_token(){
    $("#login").popup("close");
    $.mobile.loading("show");
    var parameters = "action=gettoken&username=" + $('#username').val() + "&password=" + $('#password').val() + "&remember=true";
    $("#username, #password").val('');
    $.post("index.php",parameters,function(reply){
        $.mobile.loading("hide");
        reply = $.trim(reply);
        if (reply == 0) {
            showerror("<?php echo _("Invalid Login"); ?>");
            $("#s-autologin").prop("checked",false).flipswitch("refresh");
        } else {
            localStorage.setItem('token',reply);
        }
    }, "text");
}

function update_weather() {
    var $weather = $("#weather");
    $("#weather").unbind("click");
    $weather.html("<p class='ui-icon ui-icon-loading mini-load'></p>");
    $.get("index.php","action=get_weather",function(result){
        var weather = JSON.parse(result);
        if (weather["code"] == null) {
            $("#weather-list").animate({
                "margin-left": "-1000px"
            },1000,function(){
                $(this).hide();
            })
            return;
        }
        $weather.html("<div title='"+weather["text"]+"' class='wicon cond"+weather["code"]+"'></div><span>"+weather["temp"]+"</span><br><span class='location'>"+weather["location"]+"</span>");
        $("#weather").bind("click",get_forecast);
        $("#weather-list").animate({
            "margin-left": "0"
        },1000).show()
    })
}

function logout(){
    areYouSure("<?php echo _('Are you sure you want to logout?'); ?>", "", function() {
        $.get("index.php", "action=logout",function(){
            localStorage.removeItem('token');
            location.reload();
        });
    });
}

function gohome() {
    $("body").pagecontainer("change","#sprinklers",{reverse: true});
}

function changePage(toPage) {
    var curr = "#"+$("body").pagecontainer("getActivePage").attr("id");
    if (curr === toPage) {
        bind_links(curr);
    } else {
        $("body").pagecontainer("change",toPage);
    }
}

function changeFromPanel(func) {
    var $panel = $("#sprinklers-settings");
    $panel.one("panelclose", func);
    $panel.panel("close");
}

function show_about() {
    changePage("#about");
}

function open_popup(id) {
    var popup = $(id);

    popup.on("popupafteropen", function(){
        $(this).popup("reposition", {
            "positionTo": "window"
        });
    }).popup().enhanceWithin().popup("open");
}

function show_settings() {
    $.mobile.loading("show");
    $.get("index.php","action=make_settings_list",function(items){
        var list = $("#os-settings-list");
        list.html(items).enhanceWithin();
        if (list.hasClass("ui-listview")) list.listview("refresh");
        $.mobile.loading("hide");
        changePage("#os-settings");
    })
}

function show_weather_settings() {
    $.mobile.loading("show");
    $.get("index.php","action=get_weather_settings",function(data){
        var data = JSON.parse(data), $provider = $('#weather_provider');

        $provider.val(data.weather_provider);
        if (!$provider.parent().is("label")) $provider.selectmenu("refresh", true);

        if (data.weather_provider == "wunderground") {
            $("#wapikey").closest("label").show();
        } else {
            $("#wapikey").closest("label").hide();
        }
        $('#wapikey').val(data.wapikey);

        var $auto_delay = $("#auto_delay");
        $auto_delay.prop("checked",data["auto_delay"]);
        if ($auto_delay.hasClass("ui-flipswitch-input")) $auto_delay.flipswitch("refresh");

        $("#auto_delay_duration").val(data["auto_delay_duration"]);

        changePage("#weather-settings");
   });
}

function show_localization() {
    var popup = $("#localization").on("popupafteropen", function(){
        $(this).popup("reposition", {
            "positionTo": "window"
        });
    // enhance popup and open it
    }).popup().enhanceWithin().popup("open");
}

function show_stations() {
    $.mobile.loading("show");
    $.get("index.php","action=make_stations_list",function(items){
        var list = $("#os-stations-list");
        list.html(items).enhanceWithin();
        if (list.hasClass("ui-listview")) list.listview("refresh");
        $.mobile.loading("hide");
        changePage("#os-stations");
    })
}

function show_users() {
    $.mobile.loading("show");
    $.get("index.php","action=make_user_list",function(items){
        var list = $("#user-control-list");
        list.html(items).enhanceWithin();
        $.mobile.loading("hide");
        changePage("#user-control");
    })
}

function user_id_name(id) {
    var name = $("#user-"+id+" [role='heading'] a").text()
    name = name.replace(/ click to (collapse|expand) contents/g,"")
    return name;
}

function delete_user(id) {
    var name = user_id_name(id);
    areYouSure("<?php echo _('Are you sure you want to delete '); ?>"+name+"?", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=delete_user&name="+name,function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                show_users()
            }
        })
    })
}

function add_user() {
    var nameEl = $("#name"), passEl = $("#pass");
    var name = nameEl.val(), pass = passEl.val();

    if (pass != $("#pass-confirm").val()) {
        showerror("<?php echo _('Password confirmation doesn\'t match password.'); ?>");
        return;
    }

    nameEl.val(""), passEl.val("");
    $.mobile.loading("show");
    $("#add-user").popup("close");
    $.get("index.php","action=add_user&name="+name+"&pass="+pass,function(result){
        $.mobile.loading("hide");
        if (result == 0) {
            comm_error()
        } else if (result == 3) {
            showerror("<?php echo _("User already exists"); ?>")
        } else {
            show_users()
        }
    })
}

function change_user(id) {
    var name = user_id_name(id), cpu = $("#cpu-"+id);
    var pass = cpu.val();

    if (pass != $("#cpu-"+id+"-confirm").val()) {
        showerror("<?php echo _('Password confirmation doesn\'t match password.'); ?>");
        return;
    }

    cpu.val("");
    $.mobile.loading("show");
    $.get("index.php","action=change_user&name="+name+"&pass="+pass,function(result){
        $.mobile.loading("hide");
        if (result == 0) {
            comm_error()
        } else {
            showerror("<?php echo _("Password for"); ?> "+name+" <?php echo _("has been updated"); ?>")
        }
    })
}

function get_forecast() {
    $.mobile.loading("show");
    $.get("index.php","action=make_list_forecast",function(items){
        var list = $("#forecast_list");
        list.html(items).enhanceWithin();
        if (list.hasClass("ui-listview")) list.listview("refresh");
        $.mobile.loading("hide");
        changePage("#forecast");
    })
}

function get_status() {
    $.mobile.loading("show");
    $.get("index.php","action=make_list_status",function(items){
        var list = $("#status_list");
        items = JSON.parse(items)
        list.html(items.list);
        $("#status_header").html(items.header);
        $("#status_footer").html(items.footer);
        if (list.hasClass("ui-listview")) list.listview("refresh");
        window.totals = JSON.parse(items.totals);
        if (window.interval_id !== undefined) clearInterval(window.interval_id);
        if (window.timeout_id !== undefined) clearTimeout(window.timeout_id);
        $.mobile.loading("hide");
        changePage("#status");
        if (window.totals["d"] !== undefined) {
            delete window.totals["p"];
            setTimeout(get_status,window.totals["d"]*1000);
        }
        update_timers(items.sdelay);
    })
}

function get_logs() {
    $("#logs input").blur();
    $.mobile.loading("show");
    var parms = "action=make_list_logs&start=" + (new Date($("#log_start").val()).getTime() / 1000) + "&end=" + ((new Date($("#log_end").val()).getTime() / 1000) + 86340);

    if ($("#log_graph").prop("checked")) {
        var grouping=$("input:radio[name='g']:checked").val();
        switch(grouping){
            case "m":
                var sort = "&sort=month";
                break;
            case "n":
                var sort = "";
                break;
            case "h":
                var sort = "&sort=hour";
                break;
            case "d":
                var sort = "&sort=dow";
                break;
        }
        $.getJSON("index.php",parms+"&type=graph"+sort,function(items){
            var is_empty = true;
            $.each(items.data,function(a,b){
                if (b.length) {
                    is_empty = false;
                    return false;
                }
            })
            if (is_empty) {
                $("#placeholder").empty().hide();
                $("#log_options").collapsible("expand");
                $("#zones, #graph_sort").hide();
                $("#logs_list").show().html("<p class='center'><?php echo _('No entries found in the selected date range'); ?></p>");
            } else {
                $("#logs_list").empty().hide();
                var state = ($(window).height() > 680) ? "expand" : "collapse";
                setTimeout(function(){$("#log_options").collapsible(state)},100);
                $("#placeholder").show();
                var zones = $("#zones");
                var freshLoad = zones.find("table").length;
                zones.show(); $("#graph_sort").show();
                if (!freshLoad) {
                    var output = '<div onclick="scrollZone(this);" class="ui-btn ui-btn-icon-notext ui-icon-carat-l btn-no-border" id="graphScrollLeft"></div><div onclick="scrollZone(this);" class="ui-btn ui-btn-icon-notext ui-icon-carat-r btn-no-border" id="graphScrollRight"></div><table style="font-size:smaller"><tbody><tr>', k=0;
                    for (var i=0; i<items.stations.length; i++) {
                        output += '<td onclick="javascript:toggleZone(this)" class="legendColorBox"><div style="border:1px solid #ccc;padding:1px"><div style="width:4px;height:0;overflow:hidden"></div></div></td><td onclick="javascript:toggleZone(this)" id="z'+i+'" zone_num='+i+' name="'+items.stations[i] + '" class="legendLabel">'+items.stations[i]+'</td>';
                        k++;
                    }
                    output += '</tr></tbody></table>';
                    zones.empty().append(output).enhanceWithin();
                }
                window.plotdata = items.data;
                seriesChange();
                var i = 0;
                if (!freshLoad) {
                    zones.find("td.legendColorBox div div").each(function(a,b){
                        var border = $($("#placeholder .legendColorBox div div").get(i)).css("border");
                        //Firefox and IE fix
                        if (border == "") {
                            border = $($("#placeholder .legendColorBox div div").get(i)).attr("style").split(";");
                            $.each(border,function(a,b){
                                var c = b.split(":");
                                if (c[0] == "border") {
                                    border = c[1];
                                    return false;
                                }
                            })
                        }
                        $(b).css("border",border);
                        i++;
                    })
                    showArrows();
                }
            }
            $.mobile.loading("hide");
        });
        return;
    }

    $.get("index.php",parms,function(items){
        $("#placeholder").empty().hide();
        var list = $("#logs_list");
        $("#zones, #graph_sort").hide(); list.show();
        if (items == 0) {
            $("#log_options").collapsible("expand");
            list.html("<p class='center'><?php echo _('No entries found in the selected date range'); ?></p>");
        } else {
            $("#log_options").collapsible("collapse");
            list.html(items).enhanceWithin();
        }
        $.mobile.loading("hide");
    })
}

function scrollZone(dir) {
    dir = ($(dir).attr("id") == "graphScrollRight") ? "+=" : "-=";
    var zones = $("#zones");
    var w = zones.width();
    zones.animate({scrollLeft: dir+w})
}

function toggleZone(zone) {
    zone = $(zone);
    if (zone.hasClass("legendColorBox")) {
        zone.find("div div").toggleClass("hideZone");
        zone.next().toggleClass("unchecked");
    } else if (zone.hasClass("legendLabel")) {
        zone.prev().find("div div").toggleClass("hideZone");
        zone.toggleClass("unchecked");
    }
    seriesChange();
}

function showArrows() {
    var zones = $("#zones");
    var height = zones.height(), sleft = zones.scrollLeft();
    if (sleft > 13) {
        $("#graphScrollLeft").show().css("margin-top",(height/2)-12.5)
    } else {
        $("#graphScrollLeft").hide();
    }
    var total = zones.find("table").width(), container = zones.width();
    if ((total-container) > 0 && sleft < ((total-container) - 13)) {
        $("#graphScrollRight").show().css({
            "margin-top":(height/2)-12.5,
            "left":container
        })
    } else {
        $("#graphScrollRight").hide();
    }
}

function seriesChange() {
//Originally written by Richard Zimmerman
    var grouping=$("input:radio[name='g']:checked").val();
    var pData = [];
    $("td[zone_num]:not('.unchecked')").each(function () {
        var key = $(this).attr("zone_num");
        if (!window.plotdata[key].length) window.plotdata[key]=[[0,0]];
        if (key && window.plotdata[key]) {
            if ((grouping == 'h') || (grouping == 'm') || (grouping == 'd'))
                pData.push({
                    data:window.plotdata[key],
                    label:$(this).attr("name"),
                    color:parseInt(key),
                    bars: { order:key, show: true, barWidth:0.08}
                });
            else if (grouping == 'n')
                pData.push({
                    data:plotdata[key],
                    label:$(this).attr("name"),
                    color:parseInt(key),
                    lines: { show:true }
                });
        }
    });
    if (grouping=='h')
        $.plot($('#placeholder'), pData, {
            grid: { hoverable: true },
            yaxis: {min: 0, tickFormatter: function(val, axis) { return val < axis.max ? Math.round(val*100)/100 : "min";} },
            xaxis: { tickDecimals: 0, tickSize: 1 }
        });
    else if (grouping=='d')
        $.plot($('#placeholder'), pData, {
            grid: { hoverable: true },
            yaxis: {min: 0, tickFormatter: function(val, axis) { return val < axis.max ? Math.round(val*100)/100 : "min";} },
            xaxis: { tickDecimals: 0, min: -0.4, max: 6.4,
            tickFormatter: function(v) { var dow=["<?php echo _('Sun'); ?>","<?php echo _('Mon'); ?>","<?php echo _('Tue'); ?>","<?php echo _('Wed'); ?>","<?php echo _('Thr'); ?>","<?php echo _('Fri'); ?>","<?php echo _('Sat'); ?>"]; return dow[v]; } }
        });
    else if (grouping=='m')
        $.plot($('#placeholder'), pData, {
            grid: { hoverable: true },
            yaxis: {min: 0, tickFormatter: function(val, axis) { return val < axis.max ? Math.round(val*100)/100 : "min";} },
            xaxis: { tickDecimals: 0, min: 0.6, max: 12.4, tickSize: 1,
            tickFormatter: function(v) { var mon=["","<?php echo _('Jan'); ?>","<?php echo _('Feb'); ?>","<?php echo _('Mar'); ?>","<?php echo _('Apr'); ?>","<?php echo _('May'); ?>","<?php echo _('Jun'); ?>","<?php echo _('Jul'); ?>","<?php echo _('Aug'); ?>","<?php echo _('Sep'); ?>","<?php echo _('Oct'); ?>","<?php echo _('Nov'); ?>","<?php echo _('Dec'); ?>"]; return mon[v]; } }
        });
    else if (grouping=='n') {
        var minval = new Date($('#log_start').val()).getTime();
        var maxval = new Date($('#log_end').val());
        maxval.setDate(maxval.getDate() + 1);
        $.plot($('#placeholder'), pData, {
            grid: { hoverable: true },
            yaxis: {min: 0, tickFormatter: function(val, axis) { return val < axis.max ? Math.round(val*100)/100 : "min";} },
            xaxis: { mode: "time", min:minval, max:maxval.getTime()}
        });
    }
}

function get_manual() {
    $.mobile.loading("show");
    $.get("index.php","action=make_list_manual",function(items){
        var list = $("#mm_list");
        list.html(items);
        if (list.hasClass("ui-listview")) list.listview("refresh");
        $.mobile.loading("hide");
        changePage("#manual");
    })
}

function get_runonce() {
    $.mobile.loading("show");
    $.getJSON("index.php","action=make_runonce",function(items){
        window.rprogs = items.progs;
        var list = $("#runonce_list"),
            data, i=0;

        list.html(items.page);

        var progs = "<select data-mini='true' name='rprog' id='rprog'><option value='s'><?php echo _('Quick Programs'); ?></option>";
        var data = localStorage.getItem("runonce");
        if (data !== null) {
            data = JSON.parse(data);
            list.find(":input[data-type='range']").each(function(a,b){
                $(b).val(data[i]/60);
                i++;
            })
            window.rprogs["l"] = data;
            progs += "<option value='l' selected='selected'><?php echo _('Last Used Program'); ?></option>";
        }
        for (i=0; i<items.progs.length; i++) {
            progs += "<option value='"+i+"'><?php echo _('Program'); ?> "+(i+1)+"</option>";
        };
        progs += "</select>";
        $("#runonce_list p").after(progs);
        $("#rprog").change(function(){
            var prog = $(this).val();
            if (prog == "s") {
                reset_runonce()
                return;
            }
            if (window.rprogs[prog] == undefined) return;
            fill_runonce(list,window.rprogs[prog]);
        })

        list.enhanceWithin();
        $.mobile.loading("hide");
        changePage("#runonce");
    })
}

function fill_runonce(list,data){
    var i=0;
    list.find(":input[data-type='range']").each(function(a,b){
        $(b).val(data[i]/60).slider("refresh");
        i++;
    })
}

function get_preview() {
    $("#timeline").html("");
    $("#timeline-navigation").hide()
    var date = $("#preview_date").val();
    if (date === "") return;
    date = date.split("-");
    $.mobile.loading("show");
    $.get("index.php","action=get_preview&d="+date[2]+"&m="+date[1]+"&y="+date[0],function(items){
        var empty = true;
        if ($.trim(items) == "") {
            $("#timeline").html("<p align='center'><?php echo _('No stations set to run on this day.'); ?></p>")
        } else {
            empty = false;
            var data = eval("["+items.substring(0, items.length - 1)+"]");
            var shortnames = [];
            $.each(data, function(){
                this.start = new Date(date[0],date[1]-1,date[2],0,0,this.start);
                this.end = new Date(date[0],date[1]-1,date[2],0,0,this.end);
                shortnames[this.group] = this.shortname;
            });
            var options = {
                'width':  '100%',
                'editable': false,
                'axisOnTop': true,
                'eventMargin': 10,
                'eventMarginAxis': 0,
                'min': new Date(date[0],date[1]-1,date[2],0),
                'max': new Date(date[0],date[1]-1,date[2],24),
                'selectable': true,
                'showMajorLabels': false,
                'zoomMax': 1000 * 60 * 60 * 24,
                'zoomMin': 1000 * 60 * 60,
                'groupsChangeable': false,
                'showNavigation': false
            };

            window.timeline = new links.Timeline(document.getElementById('timeline'));
            links.events.addListener(timeline, "select", function(){
                var row = undefined;
                var sel = timeline.getSelection();
                if (sel.length) {
                    if (sel[0].row != undefined) {
                        row = sel[0].row;
                    }
                }
                if (row === undefined) return;
                var content = $(".timeline-event-content")[row];
                var pid = parseInt($(content).html().substr(1)) - 1;
                get_programs(pid);
            });
            $(window).on("resize",timeline_redraw);
            timeline.draw(data, options);
            if ($(window).width() <= 480) {
                var currRange = timeline.getVisibleChartRange();
                if ((currRange.end.getTime() - currRange.start.getTime()) > 6000000) timeline.setVisibleChartRange(currRange.start,new Date(currRange.start.getTime()+6000000))
            }
            $("#timeline .timeline-groups-text").each(function(a,b){
                var stn = $(b);
                var name = shortnames[stn.text()];
                stn.attr("data-shortname",name);
            })
            $("#timeline-navigation").show()
        }
        $.mobile.loading("hide");
    })
}

function timeline_redraw() {
    window.timeline.redraw();
}

function changeday(dir) {
    var inputBox = $("#preview_date");
    var date = inputBox.val();
    if (date === "") return;
    date = date.split("-");
    var nDate = new Date(date[0],date[1]-1,date[2]);
    nDate.setDate(nDate.getDate() + dir);
    var m = pad(nDate.getMonth()+1);
    var d = pad(nDate.getDate());
    inputBox.val(nDate.getFullYear() + "-" + m + "-" + d);
    get_preview();
}

function get_programs(pid) {
    $.mobile.loading("show");
    $.get("index.php","action=make_all_programs",function(items){
        var list = $("#programs_list");
        list.html(items);
        if (typeof pid !== 'undefined') {
            if (pid === false) {
                $.mobile.silentScroll(0)
            } else {
                $("#programs fieldset[data-collapsed='false']").attr("data-collapsed","true");
                $("#program-"+pid).attr("data-collapsed","false")
            }
        }
        $("#programs input[name^='rad_days']").change(function(){
            var progid = $(this).attr('id').split("-")[1], type = $(this).val().split("-")[0], old;
            type = type.split("_")[1];
            if (type == "n") {
                old = "week"
            } else {
                old = "n"
            }
            $("#input_days_"+type+"-"+progid).show()
            $("#input_days_"+old+"-"+progid).hide()
        })

        $("#programs [id^='submit-']").click(function(){
            submit_program($(this).attr("id").split("-")[1]);
        })
        $("#programs [id^='s_checkall-']").click(function(){
            var id = $(this).attr("id").split("-")[1]
            $("[id^='station_'][id$='-"+id+"']").prop("checked",true).checkboxradio("refresh");
        })
        $("#programs [id^='s_uncheckall-']").click(function(){
            var id = $(this).attr("id").split("-")[1]
            $("[id^='station_'][id$='-"+id+"']").prop("checked",false).checkboxradio("refresh");
        })
        $("#programs [id^='delete-']").click(function(){
            delete_program($(this).attr("id").split("-")[1]);
        })
        $("#programs [id^='run-']").click(function(){
            var id = $(this).attr("id").split("-")[1];
            var durr = parseInt($("#duration-"+id).val());
            var stations = $("[id^='station_'][id$='-"+id+"']");
            var runonce = [];
            $.each(stations,function(a,b){
                if ($(b).is(":checked")) runonce.push(durr*60);
            });
            runonce.push(0);
            submit_runonce(runonce);
        })
        changePage("#programs");
        $.mobile.loading("hide");
        $("#programs").enhanceWithin();
        update_program_header();
    })
}

function update_program_header() {
    $("#programs_list").find("[id^=program-]").each(function(a,b){
        var item = $(b)
        var id = item.attr('id').split("program-")[1]
        var en = $("#en-"+id).is(":checked")
        if (en) {
            item.find(".ui-collapsible-heading-toggle").removeClass("red")
        } else {
            item.find(".ui-collapsible-heading-toggle").addClass("red")
        }
    })
}

function add_program() {
    $.mobile.loading("show");
    $.get("index.php","action=fresh_program",function(items){
        var list = $("#newprogram");
        list.html(items);
        $("#addprogram input[name^='rad_days']").change(function(){
            var progid = "new", type = $(this).val().split("-")[0], old;
            type = type.split("_")[1];
            if (type == "n") {
                old = "week"
            } else {
                old = "n"
            }
            $("#input_days_"+type+"-"+progid).show()
            $("#input_days_"+old+"-"+progid).hide()
        })
        $("#addprogram [id^='s_checkall-']").click(function(){
            $("[id^='station_'][id$='-new']").prop("checked",true).checkboxradio("refresh");
        })
        $("#addprogram [id^='s_uncheckall-']").click(function(){
            $("[id^='station_'][id$='-new']").prop("checked",false).checkboxradio("refresh");
        })
        $("#addprogram [id^='submit-']").click(function(){
            submit_program("new");
        })
        changePage("#addprogram");
        $.mobile.loading("hide");
        $("#addprogram").enhanceWithin();
    })
}

function delete_program(id) {
    areYouSure("<?php echo _('Are you sure you want to delete program '); ?>"+(parseInt(id)+1)+"?", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=delete_program&pid="+id,function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                get_programs(false)
            }
        })
    })
}

function reset_runonce() {
    $("#runonce").find(":input[data-type='range']").val(0).slider("refresh")
}

function submit_program(id) {
    var program = [], days=[0,0]
    program[0] = ($("#en-"+id).is(':checked')) ? 1 : 0

    if($("#days_week-"+id).is(':checked')) {
        for(i=0;i<7;i++) {if($("#d"+i+"-"+id).is(':checked')) {days[0] |= (1<<i); }}
        if($("#days_odd-"+id).is(':checked')) {days[0]|=0x80; days[1]=1;}
        else if($("#days_even-"+id).is(':checked')) {days[0]|=0x80; days[1]=0;}
    } else if($("#days_n-"+id).is(':checked')) {
        days[1]=parseInt($("#every-"+id).val(),10);
        if(!(days[1]>=2&&days[1]<=128)) {showerror("<?php echo _('Error: Interval days must be between 2 and 128.'); ?>");return;}
        days[0]=parseInt($("#starting-"+id).val(),10);
        if(!(days[0]>=0&&days[0]<days[1])) {showerror("<?php echo _('Error: Starting in days wrong.'); ?>");return;}
        days[0]|=0x80;
    }
    program[1] = days[0]
    program[2] = days[1]

    var start = $("#start-"+id).val().split(":")
    program[3] = parseInt(start[0])*60+parseInt(start[1])
    var end = $("#end-"+id).val().split(":")
    program[4] = parseInt(end[0])*60+parseInt(end[1])

    if(!(program[3]<program[4])) {showerror("<?php echo _('Error: Start time must be prior to end time.'); ?>");return;}

    program[5] = parseInt($("#interval-"+id).val())
    program[6] = $("#duration-"+id).val() * 60

    var sel = $("[id^=station_][id$=-"+id+"]")
    var total = sel.length
    var nboards = total / 8


    var stations=[0],station_selected=0,bid, sid;
    for(bid=0;bid<nboards;bid++) {
        stations[bid]=0;
        for(s=0;s<8;s++) {
            sid=bid*8+s;
            if($("#station_"+sid+"-"+id).is(":checked")) {
                stations[bid] |= 1<<s; station_selected=1;
            }
        }
    }
    if(station_selected==0) {showerror("<?php echo _('Error: You have not selected any stations.'); ?>");return;}
    program = JSON.stringify(program.concat(stations))
    $.mobile.loading("show");
    if (id == "new") {
        $.get("index.php","action=update_program&pid=-1&data="+program,function(result){
            $.mobile.loading("hide");
            get_programs()
            if (result == 0) {
                setTimeout(comm_error,400)
            } else {
                setTimeout(function(){showerror("<?php echo _('Program added successfully'); ?>")},400)
            }
        });
    } else {
        $.get("index.php","action=update_program&pid="+id+"&data="+program,function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                update_program_header();
                showerror("<?php echo _('Program has been updated'); ?>")
            }
        });
    }
}

function submit_settings() {
    var opt = {}, invalid = false;
    $("#os-settings-list").find(":input").each(function(a,b){
        var $item = $(b), id = $item.attr('id'), data = $item.val();
        switch (id) {
            case "o1":
                var tz = data.split(":")
                tz[0] = parseInt(tz[0],10);
                tz[1] = parseInt(tz[1],10);
                tz[1]=(tz[1]/15>>0)/4.0;tz[0]=tz[0]+(tz[0]>=0?tz[1]:-tz[1]);
                data = ((tz[0]+12)*4)>>0
                break;
            case "o2":
            case "o14":
            case "o16":
            case "o21":
            case "o22":
            case "o25":
                data = $item.is(":checked") ? 1 : 0
                if (!data) return true
                break;
        }
        opt[id] = encodeURIComponent(data)
    })
    if (invalid) return
    $.mobile.loading("show");
    $.get("index.php","action=submit_options&options="+JSON.stringify(opt),function(result){
        $.mobile.loading("hide");
        changePage("#settings");
        if (result == 0) {
            comm_error()
        } else {
            showerror("<?php echo _('Settings have been saved'); ?>");
            update_weather();
        }
    })
}

function submit_stations() {
    var names = {}, invalid = false,v="";bid=0,s=0,m={},masop="";
    $("#os-stations-list").find(":input,p[id^='um_']").each(function(a,b){
        var $item = $(b), id = $item.attr('id'), data = $item.val();
        switch (id) {
            case "edit_station_" + id.slice("edit_station_".length):
                id = "s" + id.split("_")[2]
                if (data.length > 16) {
                    invalid = true
                    $item.focus()
                    showerror("<?php echo _('Station name must be 16 characters or less'); ?>")
                    return false
                }
                names[id] = encodeURIComponent(data)
                return true;
                break;
            case "um_" + id.slice("um_".length):
                v = ($item.is(":checked") || $item.prop("tagName") == "P") ? "1".concat(v) : "0".concat(v);
                s++;
                if (parseInt(s/8) > bid) {
                    m["m"+bid]=parseInt(v,2); bid++; s=0; v="";
                }
                return true;
                break;
        }
    })
    m["m"+bid]=parseInt(v,2);
    if ($("[id^='um_']").length) masop = "&masop="+JSON.stringify(m);
    if (invalid) return
    $.mobile.loading("show");
    $.get("index.php","action=submit_stations&names="+JSON.stringify(names)+masop,function(result){
        $.mobile.loading("hide");
        changePage("#settings");
        if (result == 0) {
            comm_error()
        } else {
            showerror("<?php echo _('Stations have been updated'); ?>")
        }
    })
}

function submit_runonce(runonce) {
    if (typeof runonce === 'undefined') {
        var runonce = []
        $("#runonce").find(":input[data-type='range']").each(function(a,b){
            runonce.push(parseInt($(b).val())*60)
        })
        runonce.push(0);
    }
    localStorage.setItem("runonce",JSON.stringify(runonce));
    $.get("index.php","action=runonce&data="+JSON.stringify(runonce),function(result){
        if (result == 0) {
            comm_error()
        } else {
            showerror("<?php echo _('Run-once program has been scheduled'); ?>")
        }
    })
    changePage("#sprinklers");
}

function submit_weather_settings() {
    $.mobile.loading("show");

    var params = {
        "weather_provider": $("#weather_provider").val(),
        "wapikey": $("#wapikey").val()
    }
    params = JSON.stringify(params)
    $.get("index.php","action=submit_weather_settings&options="+params,function(result){
        $.mobile.loading("hide");
        changePage("#settings");
        if (result == 2) {
            showerror("<?php echo _('Weather settings were not saved. Check config.php permissions and try again.'); ?>");
        } else {
            showerror("<?php echo _('Weather settings have been saved'); ?>");
            update_weather();
        }
    })
}

function submit_localization(locale) {
    $.mobile.loading("show");
    $.get("index.php","action=submit_localization&locale="+locale,function(result){
        $.mobile.loading("hide");
        $("#localization").popup("close");
        if (result == 0) {
            comm_error()
        } else {
            var lang = $("#lang");
            if (lang.data("language") !== locale) location.reload();
            showerror("<?php echo _('Localization settings have been saved'); ?>")
        }
    })
}

function toggle(anchor) {
    if (!$("#mm").is(":checked")) {
        showerror("<?php echo _('Manual mode is not enabled. Please enable manual mode then try again.'); ?>");
        return;
    }
    var $list = $("#mm_list");
    var $anchor = $(anchor);
    var $listitems = $list.children("li:not(li.ui-li-divider)");
    var $item = $anchor.closest("li:not(li.ui-li-divider)");
    var currPos = $listitems.index($item) + 1;
    var total = $listitems.length;
    if ($anchor.hasClass("green")) {
        $.get("index.php","action=spoff&zone="+currPos,function(result){
            if (result == 0) {
                $anchor.addClass("green");
                comm_error()
            }
        })
        $anchor.removeClass("green");
    } else {
        $.get("index.php","action=spon&zone="+currPos,function(result){
            if (result == 0) {
                $anchor.removeClass("green");
                comm_error()
            }
        })
        $anchor.addClass("green");
    }
}

function raindelay() {
    $.mobile.loading("show");
    $.get("index.php","action=raindelay&delay="+$("#delay").val(),function(result){
        $.mobile.loading("hide");
        $("#raindelay").popup("close");
        if (result == 0) {
            comm_error()
        } else {
            $("#footer-running").html("<p class='ui-icon ui-icon-loading mini-load'></p>");
            setTimeout(check_status,1000);
            showerror("<?php echo _('Rain delay has been successfully set'); ?>");
        }
    });
}

function auto_raindelay() {
    $.mobile.loading("show");
    var params = {
        "auto_delay": $("#auto_delay").is(":checked"),
        "auto_delay_duration": $("#auto_delay_duration").val()
    }
    params = JSON.stringify(params)
    $.get("index.php","action=submit_autodelay&autodelay="+params,function(result){
        $.mobile.loading("hide");
        changePage("#settings");
        if (result == 2) {
            showerror("<?php echo _('Auto-delay changes were not saved. Check config.php permissions and try again.'); ?>");
        } else {
            showerror("<?php echo _('Auto-delay changes have been saved'); ?>")
        }
    })
}

function clear_config() {
    areYouSure("<?php echo _('Are you sure you want to delete all settings and return to the default settings (this will delete the configuration file)?'); ?>", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=clear_config",function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                showerror("<?php echo _('Configuration has been deleted. Please wait while you are redirected to the installer.'); ?>");
                setTimeout(function(){location.reload()},2500);
            }
        });
    });
}

function clear_logs() {
    areYouSure("<?php echo _('Are you sure you want to clear all your log data?'); ?>", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=clear_logs",function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                showerror("<?php echo _('Logs have been cleared'); ?>")
            }
        });
    });
}

function rbt() {
    areYouSure("<?php echo _('Are you sure you want to reboot OpenSprinkler?'); ?>", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=rbt",function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                showerror("<?php echo _('OpenSprinkler is rebooting now'); ?>")
            }
        });
    });
}

function rsn() {
    areYouSure("<?php echo _('Are you sure you want to stop all stations?'); ?>", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=rsn",function(result){
            $.mobile.loading("hide");
            if (result == 0) {
                comm_error()
            } else {
                setTimeout(check_status,1000);
                showerror("<?php echo _('All stations have been stopped'); ?>")
            }
        });
    });
}

function export_config(toFile) {
    if (toFile) {
        if (!navigator.userAgent.match(/(iPad|iPhone|iPod)/g)) {
            window.location.href="index.php?action=export_config";
        } else {
            showerror("<?php echo _('File API is not supported by your browser'); ?>")
        }
        return;
    }
    $.mobile.loading("show");
    $.get("index.php","action=export_config",function(data){
        $.mobile.loading("hide");
        if (data === "") {
            comm_error()
        } else {
            localStorage.setItem("backup", JSON.stringify(data));
            showerror("<?php echo _('Backup saved to your device'); ?>");
        }
    })
}

function import_config(data) {
    if (typeof data === "undefined") {
        var data = localStorage.getItem("backup");
        if (data === null) {
            showerror("<?php echo _('No backup available on this device'); ?>");
            return;
        }
    }
    areYouSure("<?php echo _('Are you sure you want to restore the configuration?'); ?>", "", function() {
        $.mobile.loading("show");
        $.get("index.php","action=import_config&data="+data,function(reply){
            $.mobile.loading("hide");
            if (reply == 0) {
                comm_error()
            } else {
                showerror("<?php echo _('Backup restored to your device'); ?>");
            }
        })
    });
}

function getConfigFile() {
    if (navigator.userAgent.match(/(iPad|iPhone|iPod)/g) || !window.FileReader) {
        showerror("<?php echo _('File API is not supported by your browser'); ?>");
        return;
    }
    $('#configInput').click();
}

function handleConfig(files) {
    var config = files[0];
    var reader = new FileReader();
    reader.onload = function(e){
        try{
            var obj=JSON.parse($.trim(e.target.result));
            import_config(JSON.stringify(obj));
        }catch(e){
            showerror("<?php echo _('Unable to read the configuration file. Please check the file and try again.'); ?>");
        }
    };
    reader.readAsText(config);
}

function areYouSure(text1, text2, callback) {
    var popup = $('\
    <div data-role="popup" class="ui-content" data-overlay-theme="b" id="sure">\
        <h3 class="sure-1" style="text-align:center">'+text1+'</h3>\
        <p class="sure-2" style="text-align:center">'+text2+'</p>\
        <a class="sure-do ui-btn ui-btn-b ui-corner-all ui-shadow" href="#"><?php echo _("Yes"); ?></a>\
        <a class="sure-dont ui-btn ui-corner-all ui-shadow" href="#"><?php echo _("No"); ?></a>\
    </div>');

    $(".ui-page-active").append(popup);

    $("#sure").on("popupafterclose", function(){
        $(this).remove();
    }).on("popupafteropen", function(){
        $(this).popup("reposition", {
            "positionTo": "window"
        });
    }).popup().enhanceWithin().popup("open");

    //Bind buttons
    $("#sure .sure-do").on("click.sure", function() {
        $("#sure").popup("close");
        callback();
    });
    $("#sure .sure-dont").on("click.sure", function() {
        $("#sure").popup("close");
    });
}

function showTooltip(x, y, contents, color) {
    $('<div id="tooltip">' + contents + '</div>').css( {
        position: 'absolute',
        display: 'none',
        top: y + 5,
        left: x + 5,
        border: '1px solid #fdd',
        padding: '2px',
        'background-color': color,
        opacity: 0.80
    }).appendTo("body").fadeIn(200);
}
