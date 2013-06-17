<?php

#Refuse if a direct call has been made
if(!defined('Sprinklers')){header($_SERVER['SERVER_PROTOCOL'].' 404 Not Found', true, 404);exit();}

#Check if config exists, if not redirect to install
if (!file_exists("config.php")) header("Location: install.php"); 

#Include configuration
require_once("config.php");

#Get Base URL of Site
if (isset($_SERVER['SERVER_NAME'])) $base_url = (($force_ssl) ? "https://" : "http://").$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

#Call action if requested and allowed
if (isset($_REQUEST['action'])) {
	if (is_callable($_REQUEST['action'])) {
		if (($_REQUEST['action'] == "gettoken" || $_REQUEST['action'] == "checktoken" || $_REQUEST['action'] == "login") || is_auth()) {
			call_user_func($_REQUEST['action']);
		}
		exit();
	} else {
		exit();
	}
}

#OpenSprinkler functions

#Get station names
function get_stations() {
    global $os_ip;
    $stations = file_get_contents("http://".$os_ip."/vs");
    preg_match("/snames=\[(.*)\];/", $stations, $matches);
    $stations = str_replace("'", "", $matches[1]);
    $stations = explode(",", $stations);

    #Pop the last element off the array which is always an extra empty string
    array_pop($stations);
    return $stations;
}

#Get program information
function get_programs() {
    global $os_ip;
    $data = file_get_contents("http://".$os_ip."/gp?d=0");

    preg_match_all("/(nprogs|nboards|ipas|mnp)=[\w|\d|.\"]+/", $data, $opts);

    foreach ($opts[0] as $variable) {
        if ($variable === "") continue;
        $tmp = str_replace('"','',explode("=", $variable));
        $newdata[$tmp[0]] = $tmp[1];
    }

    preg_match("/pd=\[\];(.*);/", $data, $progs);
    if (empty($progs)) return $progs;
    $progs = explode(";", $progs[1]);

    $i = 0;
    foreach ($progs as $prog) {
        $tmp = explode("=", $prog);
        $tmp2 = str_replace("[", "", $tmp[1]);
        $tmp2 = str_replace("]", "", $tmp2); 
        $program = explode(",", $tmp2);

        #Reset variables
        $days0 = $program[1]; $days1 = $program[2]; $even = false; $odd = false; $interval = false; $days = ""; $stations = "";

        $newdata["programs"][$i]["en"] = $program[0];
        $newdata["programs"][$i]["start"] = $program[3];
        $newdata["programs"][$i]["end"] = $program[4];
        $newdata["programs"][$i]["interval"] = $program[5];
        $newdata["programs"][$i]["duration"] = $program[6];

        for ($n=0; $n < $newdata["nboards"]; $n++) {
            $stations .= strval(decbin($program[7+$n]));
        }

        $newdata["programs"][$i]["stations"] = $stations;

        if(($days0&0x80)&&($days1>1)){
            #This is an interval program
            $days=array($days1,$days0&0x7f);
            $interval = true;
        } else {
            #This is a weekly program 
            for($d=0;$d<7;$d++) {
                if ($days0&(1<<$d)) {
                    $days .= "1";
                } else {
                    $days .= "0";
                }
            }
            if(($days0&0x80)&&($days1==0))  {$even = true;}
            if(($days0&0x80)&&($days1==1))  {$odd = true;}
        }

        $newdata["programs"][$i]["days"] = $days;
        $newdata["programs"][$i]["is_even"] = $even;
        $newdata["programs"][$i]["is_odd"] = $odd;
        $newdata["programs"][$i]["is_interval"] = $interval;
        $i++;
    }
    return $newdata;
}

function get_preview() {
    process_programs($_REQUEST["m"],$_REQUEST["d"],$_REQUEST["y"]);
}

function process_programs($month,$day,$year) {
    global $os_ip;
    $newdata = array();

    $newdata["settings"] = get_settings();
    $newdata["stations"] = get_stations();

    $data = file_get_contents("http://".$os_ip."/gp?d=".$day."&m=".$month."&y=".$year);
    preg_match_all("/(seq|mas|wl|sdt|mton|mtoff|devday|devmin|dd|mm|yy|nprogs|nboards|ipas|mnp)=[\w|\d|.\"]+/", $data, $opts);

    foreach ($opts[0] as $variable) {
        if ($variable === "") continue;
        $tmp = str_replace('"','',explode("=", $variable));
        $newdata[$tmp[0]] = $tmp[1];
    }

    preg_match("/pd=\[\];(.*);/", $data, $progs);
    $progs = explode(";", $progs[1]);

    $i = 0;
    foreach ($progs as $prog) {
        $tmp = explode("=", $prog);
        $tmp2 = str_replace("[", "", $tmp[1]);
        $tmp2 = str_replace("]", "", $tmp2);
        $newdata["programs"][$i] = explode(",",$tmp2);
        $i++;
    }

    $simminutes=0;
    $simt=strtotime($newdata["mm"]."/".$newdata["dd"]."/".$newdata["yy"]);
    $simdate=date(DATE_RSS,$simt);
    $simday = ($simt/3600/24)>>0;
    $match=array(0,0);
    $st_array=array($newdata["nboards"]*8);
    $pid_array=array($newdata["nboards"]*8);
    $et_array=array($newdata["nboards"]*8);
    for($sid=0;$sid<$newdata["nboards"]*8;$sid++) {
        $st_array[$sid]=0;$pid_array[$sid]=0;$et_array[$sid]=0;
    }
    do {
        $busy=0;
        $match_found=0;
        for($pid=0;$pid<$newdata["nprogs"];$pid++) {
          $prog=$newdata["programs"][$pid];
          if(check_match($prog,$simminutes,$simdate,$simday,$newdata)) {
            for($sid=0;$sid<$newdata["nboards"]*8;$sid++) {
              $bid=$sid>>3;$s=$sid%8;
              if($newdata["mas"]==($sid+1)) continue; // skip master station
              if($prog[7+$bid]&(1<<$s)) {
                $et_array[$sid]=$prog[6]*$newdata["wl"]/100>>0;$pid_array[$sid]=$pid+1;
                $match_found=1;
              }
            }
          }
        }
        if($match_found) {
          $acctime=$simminutes*60;
          if($newdata["seq"]) {
            for($sid=0;$sid<$newdata["nboards"]*8;$sid++) {
              if($et_array[$sid]) {
                $st_array[$sid]=$acctime;$acctime+=$et_array[$sid];
                $et_array[$sid]=$acctime;$acctime+=$newdata["sdt"];
                $busy=1;
              }
            }
          } else {
            for($sid=0;$sid<$newdata["nboards"]*8;$sid++) {
              if($et_array[$sid]) {
                $st_array[$sid]=$simminutes*60;
                $et_array[$sid]=$simminutes*60+$et_array[$sid];
                $busy=1;
              }
            }
          }
        }
        if ($busy) {
          $endminutes=run_sched($simminutes*60,$st_array,$pid_array,$et_array,$newdata,$simt)/60>>0;
          if($newdata["seq"]&&$simminutes!=$endminutes) $simminutes=$endminutes;
          else $simminutes++;
          for($sid=0;$sid<$newdata["nboards"]*8;$sid++) {$st_array[$sid]=0;$pid_array[$sid]=0;$et_array[$sid]=0;}
        } else {
          $simminutes++;
        }
    } while($simminutes<24*60);
}

function check_match($prog,$simminutes,$simdate,$simday,$data) {
    if($prog[0]==0) return 0;
    if (($prog[1]&0x80)&&($prog[2]>1)) {
        $dn=$prog[2];$drem=$prog[1]&0x7f;
        if(($simday%$dn)!=(($data["devday"]+$drem)%$dn)) return 0;
    } else {
        $wd=(date("w",strtotime($simdate))+6)%7;
        if(($prog[1]&(1<<$wd))==0)  return 0;
        $dt=date("j",strtotime($simdate));
        if(($prog[1]&0x80)&&($prog[2]==0))  {if(($dt%2)!=0) return 0;}
        if(($prog[1]&0x80)&&($prog[2]==1))  {
          if($dt==31) return 0;
          else if ($dt==29 && date("n",strtotime($simdate))==2) return 0;
          else if (($dt%2)!=1) return 0;
        }
    }
    if($simminutes<$prog[3] || $simminutes>$prog[4]) return 0;
    if($prog[5]==0) return 0;
    if((($simminutes-$prog[3])/$prog[5]>>0)*$prog[5] == ($simminutes-$prog[3])) {
        return 1;
    }
        return 0;
}

function run_sched($simseconds,$st_array,$pid_array,$et_array,$data,$simt) {
  $endtime=$simseconds;
  for($sid=0;$sid<$data["nboards"]*8;$sid++) {
    if($pid_array[$sid]) {
      if($data["seq"]==1) {
        time_to_text($sid,$st_array[$sid],$pid_array[$sid],$et_array[$sid],$data,$simt);
//        echo "Station: ".$sid.", Start Time: ".$st_array[$sid].", Program ID: ".$pid_array[$sid].", End Time: ".$et_array[$sid]."\n<br>";
        if(($data["mas"]>0)&&($data["mas"]!=$sid+1)&&($data["masop"][$sid>>3]&(1<<($sid%8))))
            echo "Master Start: ".$st_array[$sid]+$data["mton"].", Master End: ".($et_array[$sid]+$data["mtoff"]-60)."\n<br>";
            $endtime=$et_array[$sid];
      } else {
        time_to_text($sid,$simseconds,$pid_array[$sid],$et_array[$sid],$data,$simt);
//        echo "Station: ".$sid.", Start Time: ".$simseconds.", Program ID: ".$pid_array[$sid].", End Time: ".$et_array[$sid]."\n<br>";
        if(($data["mas"]>0)&&($data["mas"]!=$sid+1)&&($data["masop"][$sid>>3]&(1<<($sid%8))))
          $endtime=($endtime>$et_array[$sid])?$endtime:$et_array[$sid];
      }
    }
  }
  if($data["seq"]==0&&$data["mas"]>0) echo "Master Start: ".$simseconds.", Master End: ".$endtime."\n<br>";
  return $endtime;
}

function time_to_text($sid,$start,$pid,$end,$data,$simt) {
    if (($data["settings"]["rd"]!=0)&&($simt+$start+($data["settings"]["tz"]-48)*900<=$data["settings"]["rdst"])) {
        $rain_color="red";
        $rain_skip="Skip";
    } else {
        $rain_color="black";
        $rain_skip="";
    }
    echo $data["stations"][$sid]." ".getrunstr($start,$end)." P".$pid." ".(($end-$start)/60>>0)."minutes ".$rain_skip."\n<br><br>";
}

function getrunstr($start,$end){
    $h=$start/3600>>0;
    $m=($start/60>>0)%60;
    $s=$start%60;

    $str=($h/10>>0).($h%10).":".($m/10>>0).($m%10).":".($s/10>>0).($s%10);

    $h=$end/3600>>0;
    $m=($end/60>>0)%60;
    $s=$end%60;
    $str = $str."->".($h/10>>0).($h%10).":".($m/10>>0).($m%10).":".($s/10>>0).($s%10);
    return $str;
} 

#Get OpenSprinkler options
function get_options() {
    global $os_ip;
    $data = file_get_contents("http://".$os_ip."/vo");
    preg_match("/var opts=\[(.*)\];/", $data,$opts);
    preg_match("/loc=\"(.*)\"/",$data,$loc);
    preg_match("/nopts=(\d+)/", $data, $nopts);

    $newdata["loc"] = $loc[1];
    $newdata["nopts"] = $nopts[1];

    $data = explode(",", $opts[1]);

    for ($i=3; $i <= count($data); $i=$i+4) {
        $o = intval($data[$i]);
        if (in_array($o, array(1,12,13,15,16,17,18,19,20,21,22,23,25))) $newdata[$o] = array("en" => $data[$i-2],"val" => $data[$i-1]);
    }

    $newdata = move_keys(array(15,17,19,20,23),$newdata);
    $newdata = move_keys(array(16,21,22,25),$newdata);
    return $newdata;
}

#Get OpenSprinkler settings
function get_settings() {
    global $os_ip;
    $data = file_get_contents("http://".$os_ip);
    preg_match_all("/(ver|devt|nbrd|tz|en|rd|rs|mm|rdst|mas|urs|wl|ipas)=[\w|\d|.\"]+/", $data, $matches);
    preg_match("/loc=\"(.*)\"/",$data,$loc);
    preg_match("/lrun=\[(.*)\]/", $data, $lrun);
    $newdata = array("lrun" => explode(",", $lrun[1]), "loc" => $loc[1]);
    foreach ($matches[0] as $variable) {
        if ($variable === "") continue;
        $tmp = str_replace('"','',explode("=", $variable));
        $newdata[$tmp[0]] = $tmp[1];
    }
    return $newdata;
}

function get_station_status() {
    global $os_ip;
    preg_match("/\d+/", file_get_contents("http://".$os_ip."/sn0"), $data);
    return str_split($data[0]);
}

#Check if operation is enabled
function is_en() {
    $settings = get_settings();
    if ($settings["en"] == 1) return "selected";
}

#Check if manual mode is enabled
function is_mm() {
    $settings = get_settings();
    if ($settings["mm"] == 1) return "selected";
}

#Send command to OpenSprinkler
function send_to_os($url) {
    $result = file_get_contents($url);
    if ($result === false) { echo 0; exit(); }
    echo 1;
}

#Updates a program
function update_program() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cp?pw=".$os_pw."&pid=".$_REQUEST["pid"]."&v=".$_REQUEST["data"]);
}

#Deletes a program
function delete_program() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/dp?pw=".$os_pw."&pid=".$_REQUEST["pid"]);
}

#Submit updated options
function submit_options() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cs?pw=".$os_pw."&".http_build_query(json_decode($_REQUEST["names"])));
    send_to_os("http://".$os_ip."/co?pw=".$os_pw."&".http_build_query(json_decode($_REQUEST["options"])));
}

#Submit run-once program
function runonce() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cr?pw=".$os_pw."&t=".$_REQUEST["data"]);    
}

#Submit rain delay
function raindelay() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&rd=".$_REQUEST["delay"]);
}

#Reset all stations (turn-off)
function rsn() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&rsn=1");
}

#Reboot OpenSprinkler
function rbt() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&rbt=1");
}

#Change operation to on
function en_on() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&en=1");
}

#Change operation to off
function en_off() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&en=0");
}

#Switch manual mode on
function mm_on() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&mm=1");
}

#Switch manual mode off
function mm_off() {
    global $os_ip, $os_pw;
    send_to_os("http://".$os_ip."/cv?pw=".$os_pw."&mm=0");
}

#Turn specific station on
function spon() {
    global $os_ip;
    send_to_os("http://".$os_ip."/sn".$_REQUEST["zone"]."=1&t=0");
}

#Turn specific station off
function spoff() {
    global $os_ip;
    send_to_os("http://".$os_ip."/sn".$_REQUEST["zone"]."=0");
}


#Content generation functions
function make_list_logs() {
    #Adapted from the script written by David B. Gustavson, 20121021
    global $timeViewWindow, $log_file;

    $list = "";
    $ValveName = get_stations();
    $tz = get_settings()["tz"] - 48;
    $tz = (($tz>=0) ? "+" : "-").((abs($tz)/4)*60*60)+(((abs($tz)%4)*15/10).((abs($tz)%4)*15%10) * 60);

    $SprinklerValveHistory=file_get_contents($log_file);
    $timeEarliest=strtotime(Date("Y-m-d H:i:s",strtotime("-".$timeViewWindow,time())));
    $Lines=explode("\n",$SprinklerValveHistory);

    for ($i=0;$i<count($Lines);$i++){
        $ELines[$i]=explode("--",$Lines[$i]);
        if (count($ELines[$i])>1){
            $timeThis=strtotime($ELines[$i][1]);
            if ($timeThis>$timeEarliest){
                $SprinklerPattern[]=str_split($ELines[$i][0]);
                $SprinklerTime[]=$ELines[$i][1];
                $SprinklerTimeConverted[]=strtotime($ELines[$i][1]);
            };
        };
    };

    for ($i=0;$i<count($SprinklerPattern);$i++){
        $ResultLine=" ";
        for ($j=0;$j<16;$j++){
            if (($i>0) && ($SprinklerPattern[$i-1][$j]=="1") && ($SprinklerPattern[$i][$j]=="0")|| ($i==count($SprinklerPattern)-1) && ($SprinklerPattern[$i][$j]=="1")) {
                $TimeNow = $SprinklerTimeConverted[$i];
                $TimeBegin = $TimeNow;

                for ($k=1;$k<$i;$k++) {
                    if ($SprinklerPattern[$i-$k][$j]=="1"){
                        $TimeBegin=$SprinklerTimeConverted[$i-$k];
                    } else { break; };
                };

                $TimeElapsed=$TimeNow-$TimeBegin;

                $ResultLine.=" ".$ValveName[$j].((($i==count($SprinklerPattern)-1) && ($SprinklerPattern[$i][$j]=="1")) ? " has been on for ":" was on for ").$TimeElapsed." seconds.  ";
                 
                $ValveHistory[$j][]= array($SprinklerTime[$i], $TimeElapsed, ((($i==count($SprinklerPattern)-1)&&($SprinklerPattern[$i][$j]=="1")) ? " Running Now" : ""));
            };
        };
    };
    for ($j=0;$j<16;$j++) {
        if (!isset($ValveHistory[$j])) continue;
        $ct=count($ValveHistory[$j]);
        $list .= "<li data-role='list-divider'>".$ValveName[$j]."<span class='ui-li-count'>".$ct.(($ct == 1) ? " run" : " runs" )."</span></li>";
        if ($ct>0) {
            for ($k=0;$k<count($ValveHistory[$j]);$k++){
                $theTime=date('D, n/j/Y g:i A',strtotime($ValveHistory[$j][$k][0])+$tz);
                $mins = ceil($ValveHistory[$j][$k][1]/60);
                $list .= "<li>".$theTime.$ValveHistory[$j][$k][2]."<span class='ui-li-aside'>".$mins.(($mins == 1) ? " min" : " mins")."</span></li>";
            };
        };
    };
    echo $list;
}

#Make run-once list
function make_runonce() {
    $list = "<p align='center'>Value is in minutes. Zero means the station will be excluded from the run-once program.</p><div data-role='fieldcontain'>";
    $n = 0;
    $stations = get_stations();
    foreach ($stations as $station) {
        $list .= "<label for='zone-".$n."'>".$station.":</label><input type='number' data-highlight='true' data-type='range' name='zone-".$n."' min='0' max='30' id='zone-".$n."' value='0'>";
        $n++;
    }
    echo $list."</div><button onclick='submit_runonce()'>Submit</button>";
}

#Make the list of all programs
function make_list_programs() {
    $data = get_programs();
    $stations = get_stations();
    $list = "<p align='center'>Click any program below to expand/edit. Be sure to save changes by hitting submit below.</p><div data-role='collapsible-set' data-theme='c' data-content-theme='d'>";
    $week = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");
    $n = 0;
    $total = count($data["programs"]);
    if ($total == 0) {
        echo "<p align='center'>You have no programs currently added. Tap the Add button on the top right corner to get started.</p>";
        return;
    }
    foreach ($data["programs"] as $program) {
        if (is_array($program["days"])) {
            $days = $program["days"];
        } else {
            $days = str_split($program["days"]);
        }
        $set_stations = str_split($program["stations"]);
        $list .= "<fieldset ".((!$n && $total == 1) ? "data-collapsed='false'" : "")." id='program-".$n."' data-role='collapsible' data-theme='b' data-content-theme='d'><legend>Program ".($n + 1)."</legend>";

        $list .= "<input type='checkbox' ".(($program["en"]) ? "checked='checked'" : "")." name='en-".$n."' id='en-".$n."'><label for='en-".$n."'>Enabled</label>";
        $list .= "<fieldset data-role='controlgroup' data-type='horizontal' style='text-align: center'>";
        $list .= "<input type='radio' name='rad_days-".$n."' id='days_week-".$n."' value='days_week-".$n."' ".(($program["is_interval"]) ? "" : "checked='checked'")."><label for='days_week-".$n."'>Weekly</label>";
        $list .= "<input type='radio' name='rad_days-".$n."' id='days_n-".$n."' value='days_n-".$n."' ".(($program["is_interval"]) ? "checked='checked'" : "")."><label for='days_n-".$n."'>Interval</label>";
        $list .= "</fieldset><div id='input_days_week-".$n."' ".(($program["is_interval"]) ? "style='display:none'" : "").">";

        $list .= "<fieldset data-role='controlgroup' data-type='horizontal' style='text-align: center'><p>Restrictions</p>";
        $list .= "<input type='radio' name='rad_rst-".$n."' id='days_norst-".$n."' value='days_norst-".$n."' ".((!$program["is_even"] && !$program["is_odd"]) ? "checked='checked'" : "")."><label for='days_norst-".$n."'>None</label>";
        $list .= "<input type='radio' name='rad_rst-".$n."' id='days_odd-".$n."' value='days_odd-".$n."' ".((!$program["is_even"] && $program["is_odd"]) ? "checked='checked'" : "")."><label for='days_odd-".$n."'>Odd</label>";
        $list .= "<input type='radio' name='rad_rst-".$n."' id='days_even-".$n."' value='days_even-".$n."' ".((!$program["is_odd"] && $program["is_even"]) ? "checked='checked'" : "")."><label for='days_even-".$n."'>Even</label>";
        $list .= "</fieldset>";

        $list .= "<fieldset data-role='controlgroup'><legend>Days:</legend>";
        $j = 0;            
        foreach ($week as $day) {
            $list .= "<input type='checkbox' ".((!$program["is_interval"] && $days[$j]) ? "checked='checked'" : "")." name='d".$j."-".$n."' id='d".$j."-".$n."'><label for='d".$j."-".$n."'>".$day."</label>";
            $j++;
        }
        $list .= "</fieldset></div>";

        $list .= "<div ".(($program["is_interval"]) ? "" : "style='display:none'")." id='input_days_n-".$n."' class='ui-grid-a'>";
        $list .= "<div class='ui-block-a'><label for='every-".$n."'>Day Interval</label><input type='number' name='every-".$n."' pattern='[0-9]*' id='every-".$n."' value='".$days[0]."'></div>";
        $list .= "<div class='ui-block-b'><label for='starting-".$n."'>Starting In</label><input type='number' name='starting-".$n."' pattern='[0-9]*' id='starting-".$n."' value='".$days[1]."'></div>";
        $list .= "</div>";

        $list .= "<fieldset data-role='controlgroup'><legend>Stations:</legend>";
        $j = 0;
        foreach ($stations as $station) {
            $list .= "<input type='checkbox' ".(($set_stations[$j]) ? "checked='checked'" : "")." name='station_".$j."-".$n."' id='station_".$j."-".$n."'><label for='station_".$j."-".$n."'>".$station."</label>";
            $j++;
        }
        $list .= "</fieldset>";

        $list .= "<fieldset data-role='controlgroup' data-type='horizontal' style='text-align: center'>";
        $list .= "<input type='reset' name='s_checkall-".$n."' id='s_checkall-".$n."' value='Check All' />";
        $list .= "<input type='reset' name='s_uncheckall-".$n."' id='s_uncheckall-".$n."' value='Uncheck All' />";
        $list .= "</fieldset>";

        $list .= "<div class='ui-grid-a'>";
        $list .= "<div class='ui-block-a'><label for='start-".$n."'>Start Time</label><input type='time' name='start-".$n."' id='start-".$n."' value='".gmdate("H:i", $program["start"]*60)."'></div>";
        $list .= "<div class='ui-block-b'><label for='end-".$n."'>End Time</label><input type='time' name='end-".$n."' id='end-".$n."' value='".gmdate("H:i", $program["end"]*60)."'></div>";
        $list .= "</div>";

        $list .= "<label for='duration-".$n."'>Duration (minutes)</label><input type='number' data-highlight='true' data-type='range' name='duration-".$n."' min='0' max='30' id='duration-".$n."' value='".($program["duration"]/60)."'>";
        $list .= "<label for='interval-".$n."'>Interval (minutes)</label><input type='number' data-highlight='true' data-type='range' name='interval-".$n."' min='0' max='1440' id='interval-".$n."' value='".($program["interval"])."'>";

        $list .= "<input type='submit' name='submit-".$n."' id='submit-".$n."' value='Save Changes to Program ".($n + 1)."'>";
        $list .= "<input data-theme='a' type='submit' name='delete-".$n."' id='delete-".$n."' value='Delete Program ".($n + 1)."'></fieldset>";

        $n++;
    }
    echo $list."</div>";
}

#Generate a new program view
function fresh_program() {
    $stations = get_stations();
    $week = array("Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday", "Sunday");

    $list = "<fieldset id='program-new' data-theme='b' data-content-theme='d'><input type='checkbox' name='en-new' id='en-new'><label for='en-new'>Enabled</label>";
    $list .= "<fieldset data-role='controlgroup' data-type='horizontal' style='text-align: center'>";
    $list .= "<input type='radio' name='rad_days-new' id='days_week-new' value='days_week-new' checked='checked'><label for='days_week-new'>Weekly</label>";
    $list .= "<input type='radio' name='rad_days-new' id='days_n-new' value='days_n-new'><label for='days_n-new'>Interval</label>";
    $list .= "</fieldset><div id='input_days_week-new'>";

    $list .= "<fieldset data-role='controlgroup' data-type='horizontal' style='text-align: center'><p>Restrictions</p>";
    $list .= "<input type='radio' name='rad_rst-new' id='days_norst-new' value='days_norst-new' checked='checked'><label for='days_norst-new'>None</label>";
    $list .= "<input type='radio' name='rad_rst-new' id='days_odd-new' value='days_odd-new'><label for='days_odd-new'>Odd</label>";
    $list .= "<input type='radio' name='rad_rst-new' id='days_even-new' value='days_even-new'><label for='days_even-new'>Even</label>";
    $list .= "</fieldset>";

    $list .= "<fieldset data-role='controlgroup'><legend>Days:</legend>";
    $j = 0;            
    foreach ($week as $day) {
        $list .= "<input type='checkbox' name='d".$j."-new' id='d".$j."-new'><label for='d".$j."-new'>".$day."</label>";
        $j++;
    }
    $list .= "</fieldset></div>";

    $list .= "<div style='display:none' id='input_days_n-new' class='ui-grid-a'>";
    $list .= "<div class='ui-block-a'><label for='every-new'>Day Interval</label><input type='number' name='every-new' pattern='[0-9]*' id='every-new'></div>";
    $list .= "<div class='ui-block-b'><label for='starting-new'>Starting In</label><input type='number' name='starting-new' pattern='[0-9]*' id='starting-new'></div>";
    $list .= "</div>";
    $list .= "<fieldset data-role='controlgroup'><legend>Stations:</legend>";
    $j = 0;
    foreach ($stations as $station) {
        $list .= "<input type='checkbox' name='station_".$j."-new' id='station_".$j."-new'><label for='station_".$j."-new'>".$station."</label>";
        $j++;
    }
    $list .= "</fieldset>";

    $list .= "<fieldset data-role='controlgroup' data-type='horizontal' style='text-align: center'>";
    $list .= "<input type='reset' name='s_checkall-new' id='s_checkall-new' value='Check All' />";
    $list .= "<input type='reset' name='s_uncheckall-new' id='s_uncheckall-new' value='Uncheck All' />";
    $list .= "</fieldset>";

    $list .= "<div class='ui-grid-a'>";
    $list .= "<div class='ui-block-a'><label for='start-new'>Start Time</label><input type='time' name='start-new' id='start-new'></div>";
    $list .= "<div class='ui-block-b'><label for='end-new'>End Time</label><input type='time' name='end-new' id='end-new'></div>";
    $list .= "</div>";

    $list .= "<label for='duration-new'>Duration (minutes)</label><input type='number' data-highlight='true' data-type='range' name='duration-new' min='0' max='30' id='duration-new'>";
    $list .= "<label for='interval-new'>Interval (minutes)</label><input type='number' data-highlight='true' data-type='range' name='interval-new' min='0' max='1440' id='interval-new'>";

    $list .= "<input type='submit' name='submit-new' id='submit-new' value='Save New Program'>";
    echo $list;
}

#Make the manual list
function make_list_manual() {
    $list = '<li data-role="list-divider">Sprinkler Stations</li>';
    $stations = get_stations();
    $status = get_station_status();
    $i = 0;

    foreach ($stations as $station) {
        $list .= '<li><a '.(($status[$i]) ? 'class="green" ' : '').'href="javascript:toggle()">'.$station.'</a></li>';
        $i++;
    }
    echo $list;
}

#Generate status page
function make_list_status() {
    global $os_ip;

    $settings = get_settings();
    $stations = get_stations();
    $status = get_station_status();

    $tz = $settings['tz']-48;
    $tz = (($tz>=0) ? "+" : "-").(abs($tz)/4).":".((abs($tz)%4)*15/10).((abs($tz)%4)*15%10);

    $list = '<li data-role="list-divider">Device Time</li><li>'.gmdate("D, d M Y H:i:s",$settings["devt"]).'</li>';

    $list .= '<li data-role="list-divider">Time Zone</li><li>GMT '.$tz.'</li>';

    $ver = join(".",str_split($settings["ver"]));
    
    $list .= '<li data-role="list-divider">Firmware Version</li><li>'.$ver.'</li>';

    $list .= '<li data-role="list-divider">System Enabled</li><li>'.(($settings["en"]==1) ? "Yes" : "No").'</li>';

    $list .= '<li data-role="list-divider">Rain Delay</li><li>'.(($settings["rd"]==0) ? "No" : "Until ".gmdate("D, d M Y H:i:s",$settings["rdst"])).'</li>';

    $list .= '<li data-role="list-divider">Rain Sensor</li><li>'.($settings["urs"] ? ($settings["rs"] ? "Rain Detected" : "No Rain Detected" ) : "Not Enabled").'</li>';

    $lrpid = $settings["lrun"][1]; $lrdur = $settings["lrun"][2];
    $pname="from program ".$lrpid;
    if($lrpid==255||$lrpid==99) $pname="from manual mode";
    if($lrpid==254||$lrpid==98) $pname="from a run-once program";

    $list .= '<li data-role="list-divider">Last Run</li>';
    $list .= '<li>'.$stations[$settings["lrun"][0]].' ran '.$pname.' for '.($lrdur/60>>0).'m '.($lrdur%60).'s on '.gmdate("D, d M Y H:i:s",$settings["lrun"][3]).'</li>';

    $list .= '<li data-role="list-divider">Sprinkler Stations</li>';
    $i = 0;
    foreach ($stations as $station) {
        if ($status[$i]) {
            $color = "green";
        } else {
            $color = "red";
        }
        $list .= '<li class="'.$color.'">'.$station.'</li>';
        $i++;
    }
    echo $list;
}

#Generate settings page
function make_settings_list() {
    $options = get_options();
    $stations = get_stations();
    $list = "<ul data-role='listview' data-inset='true'><li data-role='list-divider'>Primary Settings</li><li><div data-role='fieldcontain'><fieldset>";
    foreach ($options as $key => $data) {
        if (!is_int($key)) continue;
        switch ($key) {
            case 1:
                $timezones = array("-12:00","-11:30","-11:00","-10:00","-09:30","-09:00","-08:30","-08:00","-07:00","-06:00","-05:00","-04:30","-04:00","-03:30","-03:00","-02:30","-02:00","+00:00","+01:00","+02:00","+03:00","+03:30","+04:00","+04:30","+05:00","+05:30","+05:45","+06:00","+06:30","+07:00","+08:00","+08:45","+09:00","+09:30","+10:00","+10:30","+11:00","+11:30","+12:00","+12:45","+13:00","+13:45","+14:00");
                $tz = $data["val"]-48;
                $tz = (($tz>=0) ? "+" : "-").sprintf("%02d", strval(abs($tz)/4)).":".strval(((abs($tz)%4)*15/10).((abs($tz)%4)*15%10));
                $list .= "<label for='o1' class='select'>Timezone</label><select id='o1'>";
                foreach ($timezones as $timezone) {
                    $list .= "<option ".(($timezone == $tz) ? "selected" : "")." value='".$timezone."'>".$timezone."</option>";
                }
                $list .= "</select>";
                continue 2;
            case 12:
#                $http = $options[13]["val"]*256+$data["val"];
#                $list .= "<label for='o12'>HTTP Port</label><input type='number' pattern='[0-9]*' id='o12' value='".$http."' />";
                continue 2;
            case 15:
                $list .= "<label for='o15'>Extension Boards</label><input type='number' pattern='[0-9]*' data-type='range' min='0' max='3' id='o15' value='".$data["val"]."' />";
                continue 2;
            case 16:
                $list .= "<input id='o16' type='checkbox' ".(($data["val"] == "1") ? "checked='checked'" : "")." /><label for='o16'>Sequential</label>";
                continue 2;
            case 17:
                $list .= "<label for='o17'>Station Delay (seconds)</label><input type='number' pattern='[0-9]*' data-type='range' min='0' max='240' id='o17' value='".$data["val"]."' />";
                continue 2;
            case 18:
                $list .= "<label for='o18' class='select'>Master Station</label><select id='o18'><option value='0'>None</option>";
                $i = 1;
                foreach ($stations as $station) {
                    $list .= "<option ".(($i == $data["val"]) ? "selected" : "")." value='".$i."'>".$station."</option>";
                    if ($i == 8) break;
                    $i++;
                }
                $list .= "</select><label for='loc'>Location</label><input type='text' id='loc' value='".$options["loc"]."' />";
                continue 2;
            case 19:
                $list .= "<label for='o19'>Master On Delay</label><input type='number' pattern='[0-9]*' data-type='range' min='0' max='60' id='o19' value='".$data["val"]."' />";
                continue 2;
            case 20:
                $list .= "<label for='o20'>Master Off Delay</label><input type='number' pattern='[0-9]*' data-type='range' min='-60' max='60' id='o20' value='".$data["val"]."' />";
                continue 2;
            case 21:
                $list .= "<input id='o21' type='checkbox' ".(($data["val"] == "1") ? "checked='checked'" : "")." /><label for='o21'>Use Rain Sensor</label>";
                continue 2;
            case 22:
                $list .= "<input id='o22' type='checkbox' ".(($data["val"] == "1") ? "checked='checked'" : "")." /><label for='o22'>Normally Open (Rain Sensor)</label>";
                continue 2;
            case 23:
                $list .= "<label for='o23'>Water Level</label><input type='number' pattern='[0-9]*' data-type='range' min='0' max='250' id='o23' value='".$data["val"]."' />";
                continue 2;
            case 25:
                $list .= "<input id='o25' type='checkbox' ".(($data["val"] == "1") ? "checked='checked'" : "")." /><label for='o25'>Ignore Password</label>";
                continue 2;
        }
    }
    $list .= "</fieldset></div></li></ul><ul data-role='listview' data-inset='true'><li data-role='list-divider'>Station Names</li><li><fieldset>";
    $i = 0;
    foreach ($stations as $station) {
        if ($station == "") continue;
        $list .= "<input id='edit_station_".$i."' type='text' value='".$station."' />";
        $i++;
    }
    echo $list."</fieldset></li></ul>";
}

#Make slide panel
function make_panel($page) {
    $buttons = array(
        "Settings" => array(
            "icon" => "gear",
            "url" => "javascript:show_settings()"
        ),
        "Reboot OpenSprinkler" => array(
            "icon" => "alert",
            "url" => "javascript:rbt()"
        ),
        "Logout" => array(
            "icon" => "delete",
            "url" => "javascript:logout()"
        )
    );
    $opts = '';
    $panel = '<div data-role="panel" id="'.$page.'-settings" data-theme="a"'.$opts.'><ul data-role="listview" data-theme="a"><li>Logged in as: '.$_SESSION["username"].'</li><li><div class="ui-grid-a"><div class="ui-block-a"><br><label for="autologin">Auto Login</label></div><div class="ui-block-b"><select name="autologin" id="'.$page.'-autologin" data-role="slider"><option value="off">Off</option><option value="on">On</option></select></div></li>';
    foreach ($buttons as $button => $data) {
        if ($data["url"] == "close") {
            $url = '#" data-rel="close';
        } else {
            $url = $data["url"];
        }
        $panel .= '<li data-icon="'.$data["icon"].'"><a href="'.$url.'">'.$button.'</a></li>';
    }
    $panel .= '</ul></div>';
    return $panel;
}

#Authentication functions
function http_authenticate($user,$pass,$crypt_type='SHA'){
    global $pass_file;

    if (!ctype_alnum($user)) return FALSE;
    if (!ctype_alnum($pass)) return FALSE;

    if(file_exists($pass_file) && is_readable($pass_file)){
        if($fp=fopen($pass_file,'r')){
            while($line=fgets($fp)){
                $line=preg_replace('`[\r\n]$`','',$line);
                list($fuser,$fpass)=explode(':',$line);
                if($fuser==$user){
                    switch($crypt_type){
                        case 'DES':
                            $salt=substr($fpass,0,2);
                            $test_pw=crypt($pass,$salt);
                            break;
                        case 'PLAIN':
                            $test_pw=$pass;
                            break;
                        case 'SHA':
                            $test_pw=base64_encode(sha1($pass));
                            break;
                        case 'MD5':
                            $test_pw=md5($pass);
                            break;
                        default:
                            fclose($fp);
                            return FALSE;
                    }
                    if($test_pw == $fpass){
                        fclose($fp);
                        return TRUE;
                    }else{
                        fclose($fp);
                        return FALSE;
                    }
                }
            }
            fclose($fp);
        }else{
            return FALSE;
        }
    }else{
        return FALSE;
    }
}

#Sends the token to the app
function gettoken() {
    if (is_auth() && isset($_SESSION["token"])) {
        echo $_SESSION["token"];
        return;
    }
    login("token");
}

#Authenticate user
function login($tosend = "sprinklers") {
    global $webtitle, $cache_file;

    $starttime = explode(' ', microtime()); 
    $starttime = $starttime[1] + $starttime[0]; 
    
    $auth = base64_encode(hash("sha256",$_SERVER['REMOTE_ADDR']).hash("sha256",$starttime).hash("sha256",$_POST['username']));
    if (!http_authenticate($_POST['username'],$_POST['password'])) {
        echo 0; 
        exit();
    } else {
        if (isset($_POST['remember']) && $_POST['remember'] == "true") {
            $fh = fopen($cache_file, 'a+');
            fwrite($fh, $starttime." ".$auth." ".$_POST['username']."\n");
            fclose($fh);
            $_SESSION['sendtoken'] = true;
        }
        $_SESSION['token'] = $auth;
        $_SESSION['isauth'] = 1;
        $_SESSION['username'] = $_POST['username'];
        
        if ($tosend == "token") {
            if (isset($_SESSION["token"])) echo $_SESSION["token"];
        } else {
           include_once("sprinklers.php");
        }
    }
}

#Remove token from cache file
function remove_token() {
    global $cache_file;
    $hashs = file($cache_file);
    if (isset($_SESSION['token']) && count($hashs) !== 0) {
        $i = 0;
        foreach ($hashs as $hash){
            $hash = explode(" ",$hash);
            $hash[1] = str_replace("\n", "", $hash[1]);
            if ($hash[1] === $_SESSION['token']) {
                delLineFromFile($cache_file, $i);
                unset($_SESSION['token']);
            }
            $i++;
        }
    }
    unset($hashs);
}

#Logs out the user
function logout() {
    global $base_url;
    remove_token();
    $_SESSION = array();
    session_destroy();
    header('Location: '.$base_url);
}

#Check if token is valid
function check_localstorage($token) {
    global $cache_file;
    $starttime = explode(' ', microtime()); 
    $starttime = $starttime[1] + $starttime[0]; 
    $endtime = $starttime - 2592000;
    $hashs = file($cache_file);
    if (count($hashs) !== 0) {
        $i = 0;
        foreach ($hashs as $hash){
            $hash = explode(" ",$hash);
            $hash[2] = str_replace("\n", "", $hash[2]);
            if ($hash[0] <= $endtime) {
                delLineFromFile($cache_file, $i);
                return FALSE;
            }
            if ($token === $hash[1]) {
                $_SESSION['token'] = $token;
                $_SESSION['isauth'] = 1;
                $_SESSION['username'] = $hash[2];
                return TRUE;
            }
            $i++;
        }
    }

    return FALSE;
}

#Check if current user is authenticated
function is_auth() {
    is_ssl();
    if (isset($_SESSION['isauth']) && $_SESSION['isauth'] === 1) { return TRUE; }
    return FALSE;   
}

#Check if protocol is SSL and redirect if not
function is_ssl() {
    global $force_ssl;
    if($force_ssl && empty($_SERVER['HTTPS'])) {
        $newurl = 'https://'.$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];
        header("Location: ".$newurl);
        exit();
    }
    return TRUE;
}

#Check if token is valid and if not reject
function checktoken() {
    global $webtitle;

    if (check_localstorage($_POST['token'])) {
        include_once("sprinklers.php");
    } else {
        echo 0;
    }
    exit();
}

#Supplemental functions

#Delete a line from a file
function delLineFromFile($fileName, $lineNum){
    $arr = file($fileName);
    $lineToDelete = $lineNum;
    unset($arr["$lineToDelete"]);
    $fp = fopen($fileName, 'w+');
    foreach($arr as $line) { fwrite($fp,$line); }
    fclose($fp);
    return TRUE;
}

#Rearrange array by move the keys in $keys array to the end of $array
function move_keys($keys,$array) {
    foreach ($keys as $key) {
        $t = $array[$key];
        unset($array[$key]);
        $array[$key] = $t;
    }
    return $array;    
}