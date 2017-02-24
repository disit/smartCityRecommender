<?php

/* Recommender
  Copyright (C) 2017 DISIT Lab http://www.disit.org - University of Florence

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as
  published by the Free Software Foundation, either version 3 of the
  License, or (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see <http://www.gnu.org/licenses/>. */

function mdate($format, $microtime = null) {
    $microtime = explode(' ', ($microtime ? $microtime : microtime()));
    if (count($microtime) != 2) {
        return false;
    }
    $microtime[0] = $microtime[0] * 1000000;
    $format = str_replace('u', $microtime[0], $format);
    return date($format, $microtime[1]);
}

include_once "settings.php";
//DATABASE SETTINGS
//$config['host'] = "localhost";
//$config['user'] = "root";
//$config['pass'] = "centos";
//$config['database'] = "quartz";
//$config['nicefields'] = true; //true or false | "Field Name" or "field_name"
//$config['perpage'] = 10;
//$config['showpagenumbers'] = true; //true or false
//$config['showprevnext'] = true; //true or false
$config['table'] = "recommendations_log";

//QUERY FILTERS (sent by reload.php)
$query_filters = '';
$query_filters .= filter_input(INPUT_GET, 'FILTER_ID') != '' ? ($query_filters != "" ? " AND ID LIKE '%" . filter_input(INPUT_GET, 'FILTER_ID') . "%'" : " WHERE ID LIKE '%" . filter_input(INPUT_GET, 'FILTER_ID') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_USER') != '' ? ($query_filters != "" ? " AND USER LIKE '%" . filter_input(INPUT_GET, 'FILTER_USER') . "%'" : " WHERE USER LIKE '%" . filter_input(INPUT_GET, 'FILTER_USER') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_PROFILE') != '' ? ($query_filters != "" ? " AND PROFILE LIKE '%" . filter_input(INPUT_GET, 'FILTER_PROFILE') . "%'" : " WHERE PROFILE LIKE '%" . filter_input(INPUT_GET, 'FILTER_PROFILE') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_RECOMMENDATIONS') != '' ? ($query_filters != "" ? " AND RECOMMENDATIONS LIKE '%" . filter_input(INPUT_GET, 'FILTER_RECOMMENDATIONS') . "%'" : " WHERE RECOMMENDATIONS LIKE '%" . filter_input(INPUT_GET, 'FILTER_RECOMMENDATIONS') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_DISTANCE') != '' ? ($query_filters != "" ? " AND DISTANCE LIKE '%" . filter_input(INPUT_GET, 'FILTER_DISTANCE') . "%'" : " WHERE DISTANCE LIKE '%" . filter_input(INPUT_GET, 'FILTER_DISTANCE') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_LATITUDE') != '' ? ($query_filters != "" ? " AND LATITUDE LIKE '%" . filter_input(INPUT_GET, 'FILTER_LATITUDE') . "%'" : " WHERE LATITUDE LIKE '%" . filter_input(INPUT_GET, 'FILTER_LATITUDE') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_LONGITUDE') != '' ? ($query_filters != "" ? " AND LONGITUDE LIKE '%" . filter_input(INPUT_GET, 'FILTER_LONGITUDE') . "%'" : " WHERE LONGITUDE LIKE '%" . filter_input(INPUT_GET, 'FILTER_LONGITUDE') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_DISLIKEDSUBCLASSES') != '' ? ($query_filters != "" ? " AND DISLIKEDSUBCLASSES LIKE '%" . filter_input(INPUT_GET, 'FILTER_DISLIKEDSUBCLASSES') . "%'" : " WHERE DISLIKEDSUBCLASSES LIKE '%" . filter_input(INPUT_GET, 'FILTER_DISLIKEDSUBCLASSES') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_DISLIKEDGROUPS') != '' ? ($query_filters != "" ? " AND DISLIKEDGROUPS LIKE '%" . filter_input(INPUT_GET, 'FILTER_DISLIKEDGROUPS') . "%'" : " WHERE DISLIKEDGROUPS LIKE '%" . filter_input(INPUT_GET, 'FILTER_DISLIKEDGROUPS') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_REQUESTEDGROUP') != '' ? ($query_filters != "" ? " AND REQUESTEDGROUP LIKE '%" . filter_input(INPUT_GET, 'FILTER_REQUESTEDGROUP') . "%'" : " WHERE REQUESTEDGROUP LIKE '%" . filter_input(INPUT_GET, 'FILTER_REQUESTEDGROUP') . "%'") : "";
$query_filters .= filter_input(INPUT_GET, 'FILTER_TIMESTAMP') != '' ? ($query_filters != "" ? " AND TIMESTAMP LIKE '%" . filter_input(INPUT_GET, 'FILTER_TIMESTAMP') . "%'" : " WHERE TIMESTAMP LIKE '%" . filter_input(INPUT_GET, 'FILTER_TIMESTAMP') . "%'") : "";

include_once './Pagination-reload.php';
$Pagination = new Pagination();

//CONNECT
$link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
}

//get total rows
//$totalrows = mysqli_fetch_array(mysqli_query($link, "SELECT COUNT(*) AS total FROM " . $config['table'] . " Q LEFT OUTER JOIN " . $config['table'] . " Q2 ON (Q.job_name = Q2.job_name AND Q.job_group = Q2.job_group and Q.ID < Q2.ID) WHERE Q2.job_name IS NULL"));
if (isset($_GET['user'])) {
    $query_filters = ($query_filters == '' ? " WHERE user = '" . $_GET['user'] . "'" : " AND user = '" . $_GET['user'] . "'");
}
$totalrows = mysqli_fetch_array(mysqli_query($link, "SELECT COUNT(*) AS total FROM recommender." . $config['table'] . $query_filters));
//IF JOB TABLE IS EMPTY DISPLAY ONLY THE MENU
if ($totalrows['total'] == 0) {
    echo "Status List is empty.<br>";
    exit();
}

//limit per page, what is current page, define first record for page
$limit = $config['perpage'];
if (isset($_GET['page']) && is_numeric(trim($_GET['page']))) {
    //$page = mysqli_real_escape_string($_GET['page']);
    $page = $_GET['page'];
} else {
    $page = 1;
}
$startrow = $Pagination->getStartRow($page, $limit);

//create page links
if ($config['showpagenumbers'] == true) {
    $pagination_links = $Pagination->showPageNumbers($totalrows['total'], $page, $limit, $config['pagelinks']); // add $config['pagelinks'] as a fourth parameter, to print only the first N page links (default = 50)
} else {
    $pagination_links = null;
}

if ($config['showprevnext'] == true) {
    $prev_link = $Pagination->showPrev($totalrows['total'], $page, $limit);
    $prev_link_more = $Pagination->showPrevMore($totalrows['total'], $page, $limit);
    $next_link = $Pagination->showNext($totalrows['total'], $page, $limit);
    $next_link_more = $Pagination->showNextMore($totalrows['total'], $page, $limit);
} else {
    $prev_link = null;
    $prev_link_more = null;
    $next_link = null;
    $next_link_more = null;
}

//IF ORDERBY NOT SET, SET DEFAULT
if (!isset($_GET['orderby']) || trim($_GET['orderby']) == "") {
    //GET FIRST FIELD IN TABLE TO BE DEFAULT SORT
    $sql = "SELECT ID FROM " . $config['table'] . " LIMIT 1"; //USE ID AS THE DEFAULT SORT FIELD
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $array = mysqli_fetch_assoc($result);
    //first field
    $i = 0;
    foreach ($array as $key => $value) {
        if ($i > 0) {
            break;
        } else {
            $orderby = $key;
        }
        $i++;
    }
    //default sort
    $sort = "DESC";
} else {

    //$orderby = mysqli_real_escape_string($_GET['orderby']);
    $orderby = $_GET['orderby'];
}

//IF SORT NOT SET OR VALID, SET DEFAULT
if (!isset($_GET['sort']) || ($_GET['sort'] != "ASC" AND $_GET['sort'] != "DESC")) {
    //default sort
    $sort = "DESC";
} else {
    //$sort = mysqli_real_escape_string($_GET['sort']);
    $sort = $_GET['sort'];
}

//GET DATA
$sql = "SELECT * FROM " . $config['table'] . $query_filters . " ORDER BY $orderby $sort LIMIT $startrow,$limit";
$result = mysqli_query($link, $sql) or die(mysqli_error());

//START TABLE AND TABLE HEADER
echo "<div id='resultsTableReload'><table>\n<tr>";
$array = mysqli_fetch_assoc($result);
foreach ($array as $key => $value) {
    if ($config['nicefields']) {
        $field = ucwords(str_replace("_", " ", $key));
        //$field = ucwords($field);
    }

    $field = columnSortArrows($key, $field, $orderby, $sort);
    echo "<th>" . $field . "</th>\n";
}
echo "</tr>\n";

//reset result pointer
mysqli_data_seek($result, 0);

//start first row style
$tr_class = "class='odd'";

//counter
$i = 0;

//LOOP TABLE ROWS
while ($row = mysqli_fetch_assoc($result)) {

    echo "<tr " . $tr_class . " >\n";

    foreach ($row as $field => $value) {
        $date = date_parse($row["timestamp"]);
        if (strpos($field, 'id') !== false) {
            $json = objectToArray(json_decode($row["recommendations"]));
            $serviceURIs = getServiceURIs($json);
            $serviceURI = $serviceURIs[0];
            $servicemapurl = $config["servicemapurl"] . "?serviceUri=" . $serviceURI . "&format=html";
            $url = $config["sparql_url"] . "?query=" . urlencode($row["sparql"]);
            echo "<td><a class=\"pointer\" title=\"View serviceURIs on Map\" target=\"_blank\" href=\"" . $servicemapurl . "\"><img id='icon' src='images/map_pin.png' alt='View serviceURIs on Map' height='14' width='14'/></a>"
            . "<a class=\"pointer\" title=\"View Recommendations\" target=\"_blank\" href=\"recommendations.php?user=" . $row["user"] . "&profile=" . $row["profile"] . "&day=" . $date["day"] . "&month=" . $date["month"] . "&year=" . $date["year"] . "\"><img id='icon' src='images/sla.png' alt='View Recommendations' height='14' width='14'/></a>"
            . "<a class=\"pointer\" title=\"SPARQL Query\" target=\"_blank\" href=\"" . $url . "\"><img id='icon' src='images/rdf.png' alt='SPARQL Query' height='14' width='14'/></a>"
            . $value . "</td>\n";
        }
        //else if (strpos($field, '_TIME') !== false)
        //echo "<td>" . ($value != 0 ? date('Y-m-d H:i:s', $value / 1000) : "never") . "</td>\n";
        else if (strpos($field, 'user') !== false) {
            echo "<td><a title=\"View user\" href=\"recommendations_log_table.php?user=" . $row['user'] . "\">" . $value . "</a></td>\n";
        } else if (strpos($field, 'recommendations') !== false || strpos($field, 'sparql') !== false) {
            //$value = stripcslashes($value);
            //if job data field is too big, then use a resizable text area
            //if (strlen($value) > 80) {
            echo "<td><textarea class=\"result\">" . $value . "</textarea></td>\n";
            //} else {
            //echo "<td>" . $value . "</td>\n";
            //}
        } else if (strpos($field, 'TRIGGER_NAME') !== false) {
            echo "<td><a title=\"Edit Trigger\" href=\"newTrigger.php?triggerName=" . $row['TRIGGER_NAME'] . "&triggerGroup=" . $row['TRIGGER_GROUP'] . "\">" . $value . "</a></td>\n";
        } else if (strpos($field, 'TRIGGER_GROUP') !== false) {
            echo "<td><a title=\"Edit Trigger\" href=\"newTrigger.php?triggerName=" . $row['TRIGGER_NAME'] . "&triggerGroup=" . $row['TRIGGER_GROUP'] . "\">" . $value . "</a></td>\n";
        } else if (strpos($field, 'IP_ADDRESS') !== false) {
            $ipArray = explode(";", $row['IP_ADDRESS']);
            echo "<td>";
            foreach ($ipArray as $ip) {
                echo "<a target=\"_blank\" href=\"http://" . $ip . "\">" . $ip . "</a>\n";
            }
            echo "</td>\n";
        }
        //if result field is too big, then use a resizable text area
        else if (strpos($field, 'RESULT') !== false && strlen($value) > 80) {
            echo "<td><textarea class=\"result\">" . $value . "</textarea></td>\n";
        } else {
            echo "<td>" . $value . "</td>\n";
        }
    }
    echo "</tr>\n";

    //switch row style
    if ($tr_class == "class='odd'") {
        $tr_class = "class='even'";
    } else {
        $tr_class = "class='odd'";
    }
}

//close connection
mysqli_close($link);
//END TABLE
echo "</table></div>\n"; //close <div id='resultsTable'>

if (!($prev_link == null && $next_link == null && $pagination_links == null)) {
    echo '<div class="pagination">' . "\n";
    echo $prev_link_more;
    echo $prev_link;
    echo $pagination_links;
    echo $next_link;
    echo $next_link_more;
    echo '<div style="clear:both;"></div>' . "\n";
    echo "</div>\n";
    echo "<br>";
    echo 'Last updated on: ' . mdate('D d-m-Y H:i:s.u') . ' (refresh time ' . $config['refreshTime'] . ' ms)';

    //print scheduler metadata
    /* $schedulerMetadata = getSchedulerMetadata();
      foreach ($schedulerMetadata as $key => $value)
      echo "<br><b title=\"" . $value[1] . "\">" . $key . ": </b>" . $value[0] . "&emsp;";
      echo "<br><b title=\"The number of currently executing jobs\">Currently executing jobs: </b>" . getCurrentlyExecutingJobs() . "&emsp;";
      echo "<br>"; */

    //print system status
    /* $systemStatus = getSystemStatus();
      foreach ($systemStatus as $key => $value)
      echo "<br><b title=\"" . $value[1] . "\">" . $key . ": </b>" . $value[0] . "&emsp;";
      echo "<br>"; */

    /* echo "<br><a class=\"pointer\" title=\"View the job list\" href=\"jobs.php\">Jobs</a>&emsp;";
      echo "<a class=\"pointer\" title=\"View the trigger list\" href=\"triggers.php\">Triggers</a>&emsp;";
      echo "<a class=\"pointer\" title=\"Create a new job\" href=\"newJob.php\">New Job</a>&emsp;";
      echo "<a class=\"pointer\" title=\"Create a new job without trigger\" href=\"newJob.php?dormantJob\">New Job (dormant)</a>&emsp;";
      echo "<a class=\"pointer\" title=\"Create a new trigger\" href=\"newTrigger.php\">New Trigger</a>&emsp;";
      echo "<a title=\"Starts the Scheduler's threads that fire Triggers. When a scheduler is first created it is in 'stand-by' mode, and will not fire triggers. The scheduler can also be put into stand-by mode by clicking 'Standby Scheduler'. The misfire/recovery process will be started, if it is the initial call to this action on this scheduler instance.\" href=\"#\" onclick=\"startScheduler();return false;\">Start Scheduler</a>&emsp;";
      echo "<a title=\"Temporarily halts the Scheduler's firing of Triggers. When 'Start Scheduler' is called (to bring the scheduler out of stand-by mode), trigger misfire instructions will NOT be applied during the start - any misfires will be detected immediately afterward. The scheduler can be re-started at any time\" href=\"#\" onclick=\"standbyScheduler();return false;\">Standby Scheduler</a>&emsp;";
      echo "<a title=\"Halts the Scheduler's firing of Triggers, and cleans up all resources associated with the Scheduler, waiting jobs to complete (the scheduler cannot be re-started and requires Tomcat restart)\" href=\"#\" onclick=\"shutdownScheduler();return false;\">Shutdown Scheduler</a>&emsp;";
      echo "<a title=\"Halts the Scheduler's firing of Triggers, and cleans up all resources associated with the Scheduler (the scheduler cannot be re-started and requires Tomcat restart)\" href=\"#\" onclick=\"forceShutdownScheduler();return false;\">Force Shutdown Scheduler</a>&emsp;";
      echo "<a title=\"Pause all triggers, after using this method 'Resume Triggers' must be called to clear the scheduler's state of 'remembering' that all new triggers will be paused as they are added\" href=\"#\" onclick=\"pauseAll();return false;\">Pause Triggers</a>&emsp;";
      echo "<a title=\"Resume (un-pause) all triggers on every group\" href=\"#\" onclick=\"resumeAll();return false;\">Resume Triggers</a>&emsp;";
      echo "<a class=\"pointer\" title=\"View the nodes status log\" href=\"nodes.php\">Nodes Log</a>&emsp;";
      echo "<a class=\"pointer\" title=\"View the log\" href=\"log.php\">Log</a>&emsp;";
      echo "<br><br><br><a title=\"Clears (deletes) all scheduling data - all Jobs, Triggers, Calendars\" href=\"#\" onclick=\"clearScheduler();return false;\">Clear Scheduler</a>&emsp;";
      echo "<br><br>"; */
    //echo "<div onclick=\"toggleText()\"><div class=\"text\" ><a title=\"Pause All\" href=\"#\" onclick=\"pauseAll();return false;\">Pause All</a></div><div class=\"text\" style=\"display:none\"><a title=\"Resume All\" href=\"#\" onclick=\"resumeAll();return false;\">Resume All</a></div></div>";
}

/* FUNCTIONS */

function columnSortArrows($field, $text, $currentfield = null, $currentsort = null) {
    //defaults all field links to SORT ASC
    //if field link is current ORDERBY then make arrow and opposite current SORT
    global $page;
    $sortquery = "sort=ASC";
    $orderquery = "orderby=" . $field;

    if ($currentsort == "ASC") {
        $sortquery = "sort=DESC";
        $sortarrow = '<img src="images/arrow_up.png" />';
    }

    if ($currentsort == "DESC") {
        $sortquery = "sort=ASC";
        $sortarrow = '<img src="images/arrow_down.png" />';
    }

    if ($currentfield == $field) {
        $orderquery = "orderby=" . $field;
    } else {
        $sortarrow = null;
    }

    //return '<a href="?' . $orderquery . '&' . $sortquery . '">' . $text . '</a> ' . $sortarrow;
    //javascript function loadTable is defined in reload-status.php
    return "<a class=\"pointer\" onClick=\"loadTable('?" . $orderquery . "&" . $sortquery . "&page=" . $page . "')\">" . $text . "</a> " . $sortarrow;
}

function isSchedulerStarted() {
    global $config;
    $postData["id"] = "isStarted";
    $jsonData["json"] = json_encode($postData);
    $result = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
    if ($result[1] == 'true') {
        return 'running';
    } else {
        return 'stopped';
    }
}

function isSchedulerStandby() {
    global $config;
    $postData["id"] = "isInStandbyMode";
    $jsonData["json"] = json_encode($postData);
    $result = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
    if ($result[1] == 'true') {
        return 'yes';
    } else {
        return 'no';
    }
}

function isSchedulerShutdown() {
    global $config;
    $postData["id"] = "isShutdown";
    $jsonData["json"] = json_encode($postData);
    $result = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
    if ($result[1] == 'true') {
        return 'yes';
    } else {
        return 'no';
    }
}

// get scheduler metadata
function getSchedulerMetadata() {
    global $config;
    $postData["id"] = "getSchedulerMetadata";
    $jsonData["json"] = json_encode($postData);
    $arr = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
    return $arr;
}

// get system status
function getSystemStatus() {
    global $config;
    $postData["id"] = "getSystemStatus";
    $jsonData["json"] = json_encode($postData);
    $arr = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
    return $arr;
}

// get the number of running jobs
function getCurrentlyExecutingJobs() {
    global $config;
    $postData["id"] = "getCurrentlyExecutingJobs";
    $jsonData["json"] = json_encode($postData);
    $arr = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
    if (isset($arr)) {
        return count(objectToArray(json_decode($arr[1])));
    } else {
        return null;
    }
}

//send data in POST to url
function postData($data, $url) {
    //$url = 'URL';
    //$data = array('field1' => 'value', 'field2' => 'value');
    $options = array(
        'http' => array(
            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
            'method' => 'POST',
            'content' => http_build_query($data),
        )
    );
    $context = stream_context_create($options);
    return file_get_contents($url, false, $context);
}

//convert stdClass Objects to multidimensional array
function objectToArray($d) {
    if (is_object($d)) {
        // Gets the properties of the given object
        // with get_object_vars function
        $d = get_object_vars($d);
    }

    if (is_array($d)) {
        /*
         * Return array converted to object
         * Using __FUNCTION__ (Magic constant)
         * for recursive call
         */
        return array_map(__FUNCTION__, $d);
    } else {
        // Return array
        return $d;
    }
}

// get service URIs from JSON
function getServiceURIs($json) {
    $result = array();
    foreach ($json as $k1 => $v1) {
        foreach ($v1 as $k2 => $v2) {
            foreach ($v2["Service"]["features"] as $k3 => $v3) {
                foreach ($v3 as $k4 => $v4) {
                    if ($k4 == "properties")
                        foreach ($v3[$k4] as $k5 => $v5) {
                            if ($k5 == "serviceUri")
                                $result[] = $v5;
                        }
                }
            }
        }
    }
    return $result;
}

?>
