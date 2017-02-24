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
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title><?php
            if (isset($_REQUEST["title"])) {
                echo $_REQUEST["title"];
            } else {
                echo "Recommender";
            }
            ?>
        </title>
        <link rel="stylesheet" type="text/css" href="css/reset.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <link rel="stylesheet" type="text/css" href = "css/jquery-ui.css"/>
        <script type="text/javascript" src="javascript/jquery-2.1.0.min.js"></script>
        <script type="text/javascript" src="javascript/jquery-ui.min.js"></script>
        <script type="text/javascript" src="javascript/jquery.redirect.js"></script>
    </head>
    <body>
        <?php
        include_once "header.php"; //include header
        include_once "settings.php";
        //DATABASE SETTINGS
        /* $config['host'] = "localhost";
          $config['user'] = "root";
          $config['pass'] = "centos";
          $config['database'] = "quartz";
          $config['nicefields'] = true; //true or false | "Field Name" or "field_name"
          $config['perpage'] = 10;
          $config['showpagenumbers'] = true; //true or false
          $config['showprevnext'] = true; //true or false */
        $config['table'] = "recommendations_log";

        include './Pagination.php';
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
        //$totalrows = mysqli_fetch_array(mysqli_query($link, "SELECT COUNT(*) AS total FROM recommender." . $config['table'] . " a INNER JOIN (SELECT max(timestamp) AS max_timestamp FROM recommender.recommendations_log GROUP BY user) b ON a.timestamp=b.max_timestamp"));
        $totalrows = mysqli_fetch_array(mysqli_query($link, "SELECT COUNT(*) AS total 
                FROM recommender.recommendations_log a 
                INNER JOIN (SELECT SUM(nrecommendations_total) AS recs_total, max(timestamp) as max_timestamp, user FROM recommender.recommendations_log GROUP BY user) b ON a.timestamp = b.max_timestamp AND a.user = b.user 
                LEFT JOIN (SELECT SUM(nrecommendations_total) AS recs_today, user FROM recommender.recommendations_log WHERE timestamp > CURDATE() GROUP BY user) c ON a.user = c.user
                LEFT JOIN (SELECT COUNT(*) AS recs_accepted, user FROM recommender.recommendations_stats GROUP BY user) f ON a.user = f.user
                LEFT JOIN (SELECT COUNT(DISTINCT(date(timestamp))) AS active_days, user FROM recommender.recommendations_log GROUP by user) d ON a.user = d.user
                LEFT JOIN (SELECT COUNT(DISTINCT round(latitude * 450), round(longitude * 900)) AS active_locations, user FROM recommender.recommendations_log r WHERE `mode` IS NULL OR `mode` = 'gps' GROUP BY user) e ON a.user = e.user"));

        //IF STATUS TABLE IS EMPTY DISPLAY ONLY THE MENU
        if ($totalrows['total'] == 0) {
            echo "<div id='resultsTable'><table>\n<tr>";
            echo "Recommendations List is empty.<br>";
            echo "</table></div>\n"; //close <div id='resultsTable'>
            echo "</body>";
            echo "</html>";
            exit;
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
        //$sql = "SELECT id, user, profile, recommendations, distance, latitude, longitude, sparql, dislikedgroups, timestamp FROM recommender." . $config['table'] . " a INNER JOIN (SELECT max(timestamp) AS max_timestamp FROM recommender.recommendations_log GROUP BY user) b ON a.timestamp=b.max_timestamp ORDER BY $orderby $sort LIMIT $startrow,$limit";
        /* $sql = "SELECT id, a.user AS user, 
          IF(a.user = 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805', 'Galaxy Tab 10.1 2014',
          IF(a.user = '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96', 'Samsung Galaxy S6 Edge Plus',
          IF(a.user = '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519', 'Sony Tablet Xperia Z',
          IF(a.user = '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee', 'Nexus 4 Piero',
          IF(a.user = 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918', 'Galaxy S5 old Nesi',
          IF(a.user = 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020', 'Samsung Galaxy S6 N',
          IF(a.user = '36767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519', 'test',
          IF(a.user = '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d8000000', 'test',
          IF(a.user = '816ed0f50f318132d21500e9f8a6a4c51fbcd3313a787d6ed833a6e3774814be', 'iPhone 5s',
          IF(a.user = '61fe2e7407dcad9adf93d72d013befc173e19c93e136dc49c2a35016c359f719', 'Microsoft Lumia 950',
          '')))))))))) AS test_user,
          profile, b.recs_total AS recs_total, IF(c.recs_today IS NULL,0,recs_today) AS recs_today, IF(f.recs_accepted IS NULL, 0, f.recs_accepted) AS recs_accepted, IF(d.active_days IS NULL, 0, d.active_days) AS active_days, e.active_locations,
          recommendations, distance, IF(a.mode IS NULL, '', a.mode) AS `mode`,
          latitude, longitude, sparql, dislikedSubclasses, dislikedGroups, requestedGroup, timestamp
          FROM recommender.recommendations_log a
          INNER JOIN (SELECT SUM(nrecommendations_total) AS recs_total, max(timestamp) as max_timestamp, user FROM recommender.recommendations_log GROUP BY user) b ON a.timestamp = b.max_timestamp AND a.user = b.user
          LEFT JOIN (SELECT SUM(nrecommendations_total) AS recs_today, user FROM recommender.recommendations_log WHERE timestamp > CURDATE() GROUP BY user) c ON a.user = c.user
          LEFT JOIN (SELECT COUNT(*) AS recs_accepted, user FROM recommender.recommendations_stats GROUP BY user) f ON a.user = f.user
          LEFT JOIN (SELECT COUNT(DISTINCT(date(timestamp))) AS active_days, user FROM recommender.recommendations_log GROUP BY user) d ON a.user = d.user
          LEFT JOIN (SELECT COUNT(DISTINCT round(latitude * 450), round(longitude * 900)) AS active_locations, user FROM recommender.recommendations_log r WHERE `mode` IS NULL OR `mode` = 'gps' GROUP BY user) e ON a.user = e.user
          ORDER BY $orderby $sort LIMIT $startrow,$limit"; */
        $sql = "SELECT a.id, a.user AS user, 
                u.label AS test_user,
                a.profile, b.recs_total AS recs_total, IF(c.recs_today IS NULL,0,recs_today) AS recs_today, IF(f.recs_accepted IS NULL, 0, f.recs_accepted) AS recs_accepted, IF(d.active_days IS NULL, 0, d.active_days) AS active_days, e.active_locations,
                recommendations, distance, IF(a.mode IS NULL, '', a.mode) AS `mode`,
                latitude, longitude, sparql, dislikedSubclasses, dislikedGroups, requestedGroup, timestamp 
                FROM recommender.recommendations_log a 
                LEFT JOIN recommender.users u ON a.user = u.user 
                INNER JOIN (SELECT SUM(nrecommendations_total) AS recs_total, max(timestamp) as max_timestamp, user FROM recommender.recommendations_log GROUP BY user) b ON a.timestamp = b.max_timestamp AND a.user = b.user 
                LEFT JOIN (SELECT SUM(nrecommendations_total) AS recs_today, user FROM recommender.recommendations_log WHERE timestamp > CURDATE() GROUP BY user) c ON a.user = c.user
                LEFT JOIN (SELECT COUNT(*) AS recs_accepted, user FROM recommender.recommendations_stats GROUP BY user) f ON a.user = f.user
                LEFT JOIN (SELECT COUNT(DISTINCT(date(timestamp))) AS active_days, user FROM recommender.recommendations_log GROUP BY user) d ON a.user = d.user 
                LEFT JOIN (SELECT COUNT(DISTINCT round(latitude * 450), round(longitude * 900)) AS active_locations, user FROM recommender.recommendations_log r WHERE `mode` IS NULL OR `mode` = 'gps' GROUP BY user) e ON a.user = e.user
                ORDER BY " . ($orderby != "test_user" ? "a." . $orderby : $orderby) . " $sort LIMIT $startrow,$limit";
        $result = mysqli_query($link, $sql) or die(mysqli_error());

        //START TABLE AND TABLE HEADER
        echo "<div id='resultsTable'><table>\n<tr>";
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
                else if ($field == 'user') {
                    echo "<td><a title=\"View user\" href=\"recommendations_log_table.php?user=" . $row['user'] . "\">" . $value . "</a></td>\n";
                } else if (strpos($field, 'recommendations') !== false || strpos($field, 'sparql') !== false || strpos($field, 'dislikedSubclasses') !== false || strpos($field, 'dislikedGroups') !== false) {
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
                } else if (strpos($field, 'PROGRESS') !== false) {
                    if ($value >= 0) {
                        echo "<td><progress value=\"" . $value . "\" max=\"100\"></progress>" . $value . "%</td>\n";
                    } else {
                        echo "<td>" . $value . "</td>\n";
                    }
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

        mysqli_close($link); //close connection
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

            //print scheduler metadata
            /* $current_jobs = getCurrentlyExecutingJobs();
              echo "<div class='scheduler'>";
              if ($current_jobs != null) {
              echo "<b title=\"The number of currently executing jobs\">Currently executing jobs: </b>" . getCurrentlyExecutingJobs() . "<br>";
              }
              $schedulerMetadata = getSchedulerMetadata();
              foreach ($schedulerMetadata as $key => $value) {
              echo "<b title=\"" . $value[1] . "\">" . $key . ": </b>" . $value[0] . "<br>";
              }
              echo "</div>"; */
            //echo "<br>";
            //print system status
            /* $systemStatus = getSystemStatus();
              echo "<div class='scheduler'>";
              foreach ($systemStatus as $key => $value) {
              echo "<b title=\"" . $value[1] . "\">" . $key . ": </b>" . $value[0] . "<br>";
              }
              echo "</div>"; */
            echo "<br>";
            //echo "<a class=\"pointer\" title=\"Back\" href=\"#\" onclick=\"history.back();\">Back</a>&emsp;\n";
            echo "<a class=\"pointer button\" title=\"Back\" href=\"#\" onclick=\"if(document.referrer) {window.open(document.referrer,'_self');} else {history.go(-1);}return false;\">Back</a>&emsp;\n";
            echo "<a class=\"pointer button\" title=\"Home\" href=\"index.php\">Home</a>&emsp;\n";
            echo "<br><br><a class=\"pointer\" title=\"Push Mode\" href=\"reload-status.php\"><img id='icon' src='images/push.jpg' alt='edit' height='28' width='28'/></a>";
            echo "<br><br>";
        }

        /* FUNCTIONS */

        function columnSortArrows($field, $text, $currentfield = null, $currentsort = null) {
            //defaults all field links to SORT ASC
            //if field link is current ORDERBY then make arrow and opposite current SORT

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
            if (isset($_GET["user"]))
                $userquery = "&user=" . $_GET["user"];
            else
                $userquery = "";
            return '<a href="?' . $orderquery . '&' . $sortquery . $userquery . '">' . $text . '</a> ' . $sortarrow;
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
            ksort($arr); //sort alphabetically the array
            return $arr;
        }

        // get system status
        function getSystemStatus() {
            global $config;
            $postData["id"] = "getSystemStatus";
            $jsonData["json"] = json_encode($postData);
            $arr = json_decode(postData($jsonData, "http://" . $config["tomcat"] . ":8080/SmartCloudEngine/index.jsp"), true);
            ksort($arr); //sort alphabetically the array
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

        //read the JOB_DATA BLOB from the Quartz database as a .properties file
        //this method works if the org.quartz.jobStore.useProperties property in quartz.properties is set to true
        function parse_properties($txtProperties) {
            $result = array();

            $lines = split("\n", $txtProperties);
            $key = "";

            $isWaitingOtherLine = false;
            foreach ($lines as $i => $line) {

                if (empty($line) || (!$isWaitingOtherLine && strpos($line, "#") === 0)) {
                    continue;
                }

                if (!$isWaitingOtherLine) {
                    $key = substr($line, 0, strpos($line, '='));
                    //strip cslashes \\ from keys beginning with \\#, (e.g., reserved jobDataMap parameters: #isNonConcurrent, #url, #notificationEmail, #nextJobs, #processParameters, #jobConstraints)
                    $key = stripcslashes($key);

                    $value = substr($line, strpos($line, '=') + 1, strlen($line));
                    //strip cslashes \\ from keys beginning with \\#
                    $value = stripcslashes($value);
                } else {
                    $value .= $line;
                }

                /* Check if ends with single '\' */
                if (strrpos($value, "\\") === strlen($value) - strlen("\\")) {
                    $value = substr($value, 0, strlen($value) - 1) . "\n";
                    $isWaitingOtherLine = true;
                } else {
                    $isWaitingOtherLine = false;
                }

                $result[$key] = $value;
                unset($lines[$i]);
            }

            return $result;
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

        //get distinct user location, the distance formula from http://mysqlserverteam.com/mysql-5-7-and-gis-an-example/
        function getDistinctLocations($user, $link) {
            global $config;
            //CONNECT
            //$link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

            /* check connection */
            if (mysqli_connect_errno()) {
                printf("Connection failed: %s\n", mysqli_connect_error());
                exit();
            }

            $locations = array();
            $sql = "SELECT latitude, longitude FROM recommender.recommendations_log WHERE user = '" . $user . "'";
            $result = mysqli_query($link, $sql) or die(mysqli_error());
            while ($row = mysqli_fetch_assoc($result)) {
                $locations[$row["latitude"] . "|" . $row["longitude"]] = "";
            }
            foreach ($locations as $key => $value) {
                $latitude_longitude = split("\|", $key);
                $sql = "SELECT latitude, longitude FROM recommender.recommendations_log WHERE user = '" . $user . "' AND 6371 * acos(cos(radians(latitude)) * cos(radians(" . $latitude_longitude[0] . ")) * cos(radians(" . $latitude_longitude[1] . ") - radians(longitude)) + sin(radians(latitude)) * sin(radians(" . $latitude_longitude[0] . "))) < " . $config['distinct_location_radius'];
                $result = mysqli_query($link, $sql) or die(mysqli_error());
                while ($row = mysqli_fetch_assoc($result)) {
                    if ($row["latitude"] != $latitude_longitude[0] && $row["longitude"] != $latitude_longitude[1]) {
                        unset($locations[$key]);
                    }
                }
            }
            //mysqli_close($link); //close connection
            return count($locations);
        }
        ?>
    </body>
</html>
