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

//http://jsonviewer.stack.hu/
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

// get the profile name for a user
// used for test ids with IP format
// do not use
function getProfile($user) {
    $id = sprintf("%.0f", str_replace(".", "", $user)) % 5;
    switch ($id) {
        case 0:
            return "student";
        case 1:
            return "commuter";
        case 2:
            return "tourist";
        case 3:
            return "citizen";
        case 4:
            return "all";
    }
}

// get the groups for a user
function getGroups($profile) {
    global $config;
    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $sql = "SELECT `group`, `" . $profile . "` FROM recommender.groups ORDER BY `" . $profile . "` ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $groups = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[$row[$profile]] = $row["group"];
    }
    //close connection
    mysqli_close($link);
    return $groups;
}

// check if a serviceUri was viewed by the user after having been recommended
function isViewed($user, $serviceUri, $timestamp) {
    global $config;
    $viewed = "";
    //CONNECT
    $link = mysqli_connect($config['access_log_host'], $config['access_log_user'], $config['access_log_pass'], $config['access_log_database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $sql = "SELECT timestamp FROM ServiceMap.AccessLog WHERE uid = '" . $user . "' AND serviceUri = '" . $serviceUri . "' AND categories IS NOT NULL AND mode = 'api-service-info' AND timestamp > '" . $timestamp . "' LIMIT 1";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $viewed = $row["timestamp"];
    }
    //close connection
    mysqli_close($link);
    return $viewed;
}

function printEmptyTable($group, $label, $tableId) {
    //start first row style
    $tr_class = "class='odd'";
    echo "<td>";
    echo $label;
    echo "<table class='empty result' id='a" . $tableId . "'>\n";
    for ($i = 0; $i < 3; $i++) {
        echo "<tr " . $tr_class . " >\n";
        echo "<td>";
        echo "<br><br><br><br>";
        echo "</td>\n";
        echo "</tr>\n";
        //switch row style
        if ($tr_class == "class='odd'") {
            $tr_class = "class='even'";
        } else {
            $tr_class = "class='odd'";
        }
    }
    echo "</table>\n";
    echo "</td>\n";
}

// calculate distance in km between coordinates in decimal degrees (latitude, longitude)
function distFrom($lat1, $lng1, $lat2, $lng2) {
    if (($lat2 == 0 && $lng2 == 0) || ($lat1 == $lat2 && $lng1 == $lng2))
        return 0;
    $earthRadius = 6371000; //meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $dist = $earthRadius * $c;

    return $dist / 1000;
}

// calculate distance in km between coordinates in decimal degrees (latitude, longitude)
// http://www.geodatasource.com/developers/php
function distance($lat1, $lon1, $lat2, $lon2, $unit = "K") {
    if (($lat2 == 0 && $lon2 == 0) || ($lat1 == $lat2 && $lon1 == $lon2))
        return 0;
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);

    if ($unit == "K") {
        return ($miles * 1.609344);
    } else if ($unit == "N") {
        return ($miles * 0.8684);
    } else {
        return $miles;
    }
}

// get the inferred route distance from coordinates
function getRouteDistance($coordinates) {
    global $config;
    $osrm = "";
    foreach ($coordinates as $value) {
        $osrm .= "&loc=" . $value[0] . "," . $value[1];
    }
    $osrm = substr($osrm, 1);
    $json = json_decode(file_get_contents($config["osrm_server_url"] . "/viaroute?" . $osrm));
    $json = objectToArray($json);
    if (!isset($json["route_summary"]["total_distance"])) {
        return null;
    }
    $distance = $json["route_summary"]["total_distance"];
    if ($distance > 1000) {
        return ($distance / 1000) . " km";
    } else {
        return $distance . " m";
    }
}

// get the reverse geocoding (street name, civic number, city, nation from coordinates using Nominatim http://wiki.openstreetmap.org/wiki/Nominatim)
function getLocationInfo($latitude, $longitude) {
    global $config;
    $nominatim = "";
    if (!isset($config["nominatim_server_url"])) {
        return $nominatim;
    }
    $json = json_decode(file_get_contents($config["nominatim_server_url"] . "/reverse.php?format=json&lat=" . $latitude . "&lon=" . $longitude . "&zoom=18&addressdetails=1"));
    $json = objectToArray($json);
    if (isset($json["address"])) {
        if (isset($json["address"]["road"])) {
            $road = $json["address"]["road"];
        } else if (isset($json["address"]["footway"])) {
            $road = $json["address"]["footway"];
        } else if (isset($json["address"]["pedestrian"])) {
            $road = $json["address"]["pedestrian"];
        } else if (isset($json["address"]["suburb"])) {
            $road = $json["address"]["suburb"];
        } else {
            $road = "";
        }
        $house_number = $road != "" && isset($json["address"]["house_number"]) ? ", " . $json["address"]["house_number"] : "";
        $postcode = isset($json["address"]["postcode"]) ? "<br>" . $json["address"]["postcode"] : "";
        $city = isset($json["address"]["city"]) ? " " . $json["address"]["city"] : (isset($json["address"]["town"]) ? " " . $json["address"]["town"] : "");
        $country = isset($json["address"]["country"]) ? " (" . $json["address"]["country"] . ")" : "";
        $nominatim = "<br><br>" . $road . $house_number . $postcode . $city . $country . "<br><br>";
    } else {
        $nominatim = "<br><br><br><br><br>";
    }
    return $nominatim;
}

// get the previous day in which the user got recommendations with respect to this one
function getPreviousDay() {
    global $config;
    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $date = $_REQUEST["year"] . "-" . $_REQUEST["month"] . "-" . $_REQUEST["day"] . " 00:00:00";
    $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND timestamp < '" . $date . "' ORDER BY timestamp DESC LIMIT 1";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamp = "";
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = $row["timestamp"];
    }
    //close connection
    mysqli_close($link);
    return $timestamp;
}

// get the next day in which the user got recommendations with respect to this one
function getNextDay() {
    global $config;
    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $date = $_REQUEST["year"] . "-" . $_REQUEST["month"] . "-" . $_REQUEST["day"] . " 00:00:00";
    $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND timestamp > '" . $date . "' + INTERVAL 1 DAY ORDER BY timestamp ASC LIMIT 1";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamp = "";
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = $row["timestamp"];
    }
    //close connection
    mysqli_close($link);
    return $timestamp;
}

// display a json
function displayJSON() {
    global $config;

    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }

    // GET DATA
    $results = array();
    $coordinates = array();
    $distances = array();
    $timing = array();
    $mode = array();
    $sql = "SELECT recommendations, latitude, longitude, distance, dislikedGroups, requestedGroup, `mode`, timestamp, total_time FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND DATE(timestamp) = '" . $_REQUEST["year"] . "-" . $_REQUEST["month"] . "-" . $_REQUEST["day"] . "' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $results[$row["timestamp"]] = $row["recommendations"];
        $coordinates[$row["timestamp"]]["latitude"] = $row["latitude"]; // latitude of user (decimal) at timestamp
        $coordinates[$row["timestamp"]]["longitude"] = $row["longitude"]; // longitude of user (decimal) at timestamp
        $distances[$row["timestamp"]] = $row["distance"]; // radius of recommendations (km) at timestamp
        $dislikedGroups[$row["timestamp"]] = $row["dislikedGroups"]; // disliked groups
        $requestedGroup[$row["timestamp"]] = $row["requestedGroup"]; // requested group
        // add this point to the osrm array only if it is not a manual setted location by the user
        if ($row["mode"] != 'manual') {
            $osrm[] = array($row["latitude"], $row["longitude"]);
        }
        $timing[$row["timestamp"]] = $row["total_time"];
        $mode[$row["timestamp"]] = $row["mode"];
    }

    // title data div
    /* echo "<div class='scheduler'>";
      echo "<h3><b>User: </b>" . $row["user"];
      echo "<h3><b>Date: </b>" . $row["timestamp"] . "</h3>";
      $servicemapurl = $config["servicemapurl"] . "?selection=" . $row["latitude"] . ";" . $row["longitude"] . "&categories=Service&maxDists=1&format=html";
      echo "<h3><a title=\"View on Map\" target=\"_blank\" href=\"" . $servicemapurl . "\">"
      . "<img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/></a>"
      . "<b>Latitude: </b>" . $row["latitude"] . ", <b>Longitude: </b>" . $row["longitude"];
      echo "</div>"; */
    echo "<div id='user'>";

    // build previous day and next day urls
    $previousDay = split(" ", getPreviousDay());
    $previousDay = $previousDay[0];
    $previousDay = split("-", $previousDay);
    $nextDay = split(" ", getNextDay());
    $nextDay = $nextDay[0];
    $nextDay = split("-", $nextDay);
    // if previous and next day are an array of three elements (yy-MM-dd)
    $previousDayURL = count($previousDay) == 3 ? "recommendations.php?user=" . $_REQUEST["user"] . "&profile=" . $_REQUEST["profile"] . "&day=" . $previousDay[2] . "&month=" . $previousDay[1] . "&year=" . $previousDay[0] : "";
    $nextDayURL = count($nextDay) == 3 ? "recommendations.php?user=" . $_REQUEST["user"] . "&profile=" . $_REQUEST["profile"] . "&day=" . $nextDay[2] . "&month=" . $nextDay[1] . "&year=" . $nextDay[0] : "";
    $previousDayURL = $previousDayURL != "" ? "<a title=\"View previous day recommendations\" href=\"" . $previousDayURL . "\">&#9664;</a>" : "";
    $nextDayURL = $nextDayURL != "" ? "&nbsp;&nbsp;&nbsp;<a title=\"View next day recommendations\" href=\"" . $nextDayURL . "\">&#9654;</a>" : "";

    //$mapurl = "map.php?user=" . $_REQUEST["user"] . "&day=" . $_REQUEST["day"] . "&month=" . $_REQUEST["month"] . "&year=" . $_REQUEST["year"];
    $route = "route.php?user=" . $_REQUEST["user"] . "&day=" . $_REQUEST["day"] . "&month=" . $_REQUEST["month"] . "&year=" . $_REQUEST["year"];
    //echo "<p><a title=\"View user's locations on Map\" target=\"_blank\" href=\"" . $mapurl . "\">"
    //. "<img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/></a>"
    echo"<p><a title=\"View user's inferred route on Map\" target=\"_blank\" href=\"" . $route . "\">"
    . "<img id='icon' src='images/route.png' alt='edit' height='16' width='16'/></a>"
    . $previousDayURL
    . $nextDayURL
    . "<br>User ID: " . $_REQUEST["user"] . "<br>";
    $route_distance = getRouteDistance($osrm);
    if ($route_distance != null) {
        echo "Route Distance (inferred): " . getRouteDistance($osrm) . "<br>";
    }
    echo "User Profile: " . $_REQUEST["profile"] . "<br>";
    echo "Date: " . $_REQUEST["year"] . "-" . sprintf("%02d", $_REQUEST["month"]) . "-" . sprintf("%02d", $_REQUEST["day"]) . "</p>";
    echo "</div>";

    // PRINT TABLE
    echo "<div id='recommendationsTable'>\n";
    echo "<table>\n";

    // PRINT TABLE HEADER (TIMESTAMPS AND COORDINATES)
    echo "<tr>";
    $sql = "SELECT distinct(timestamp) FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND DATE(timestamp) = '" . $_REQUEST["year"] . "-" . $_REQUEST["month"] . "-" . $_REQUEST["day"] . "' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamps = array();
    //echo "<th class=\"label\"></th>"; // ROW LABEL
    $lat_tmp = 0;
    $lng_tmp = 0;
    $marker_index = 0;
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamps[] = $row["timestamp"];
        $servicemapurl = $config["servicemapurl"] . "?selection=" . $coordinates[$row["timestamp"]]["latitude"] . ";" . $coordinates[$row["timestamp"]]["longitude"] . "&categories=Service&maxDists=1&format=html";
        $date = date_parse($row["timestamp"]);
        // radius
        $distance = distFrom($coordinates[$row["timestamp"]]["latitude"], $coordinates[$row["timestamp"]]["longitude"], $lat_tmp, $lng_tmp);
        // if radius is < 1 km, print it in m
        if ($distance < 1) {
            $distance = round(1000 * $distance, 2) . " m";
        }
        // else print the radius in km
        else {
            $distance = round($distance, 2) . " km";
        }
        $location = getLocationInfo($coordinates[$row["timestamp"]]["latitude"], $coordinates[$row["timestamp"]]["longitude"]);
        $time = sprintf("%02d", $date["hour"]) . ":" . sprintf("%02d", $date["minute"]) . ":" . sprintf("%02d", $date["second"]);
        $generation_time = $timing[$row["timestamp"]] != "" ? " (generation time: " . sprintf("%.2f", ($timing[$row["timestamp"]] / 1000)) . " s)" : ""; // time to provide recommendation
        $gps = $mode[$row["timestamp"]] != null ? " (" . $mode[$row["timestamp"]] . ")" : "";
        // if this marker is a gps marker, then use markers, if it is manual manual_markers
        $marker = $mode[$row["timestamp"]] == "gps" || $mode[$row["timestamp"]] == "" ? "markers" : ($mode[$row["timestamp"]] == "manual" ? "manual_markers" : "");
        echo "<th>" .
        "<a title=\"View user's location on Map\" target=\"_blank\" href=\"" . $servicemapurl . "\">"
        . "<img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/></a>"
        //. "<span id='" . $time . "' class='timestamp' onclick = 'javascript: " . $marker . "[" . $marker_index . "].openPopup()'>Time: " . $time . "</span>" . $generation_time . "<br>"
        . "<span id='" . $time . "' class='timestamp' onclick = 'javascript: centerOnMarker(" . $marker . "[" . $marker_index . "])'>Time: " . $time . "</span>" . $generation_time . "<br>"
        . "Latitude: " . $coordinates[$row["timestamp"]]["latitude"] . ", Longitude: " . $coordinates[$row["timestamp"]]["longitude"] . "<br>" . $gps
        . $location
        . " Distance from previous location: " . $distance
        . (isset($distances[$row["timestamp"]]) ? "<br>Radius: " . $distances[$row["timestamp"]] . " km" : "")
        . "</th>";
        $lat_tmp = $coordinates[$row["timestamp"]]["latitude"];
        $lng_tmp = $coordinates[$row["timestamp"]]["longitude"];
        $marker_index++;
    }
    echo "</tr>\n";

    // dislike label
    $dislikeText = "<a id='dislike'><b> Disliked</b></a>"; //<img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/>"; //"<p id='dislike'><b>Disliked</b>";
    // requested label
    $requestedText = "<a id='requested'><b> Requested</b></a>";
    // PRINT TABLE DATA
    $groups = getGroups($_REQUEST["profile"]);
    $max_index = max(array_keys($groups));
    for ($i = 1; $i <= $max_index; $i++) {
        $group = $groups[$i];
        if (!isset($group))
            continue;
        echo "<tr>";
        //echo "<td><p id='label'><b>" . $group . "</b></p></td>\n"; // ROW LABEL
        $k = 1;
        foreach ($timestamps as $timestamp) {
            $json = objectToArray(json_decode($results[$timestamp]));
            if (count($json[$group]) == 0 || ($group == "Weather" && $json[$group]["ERROR"] != null)) {
                $label = "<p id='group'><b>" . $group . "</b>" . (strpos($dislikedGroups[$timestamp], $group) !== false ? $dislikeText : "") . "</p>\n";
                $label = strpos($requestedGroup[$timestamp], $group) !== false ? "<p id='group'><b>" . $group . "</b>" . $requestedText . "</p>\n" : $label;
                printEmptyTable($group, $label, $i . "_" . $k); // $i = table row id, $j = table column id
                $k++;
                continue;
            }
            //start first row style
            $tr_class = "class='odd'";
            // if this group was disliked by the user, then label it "Disliked"
            $title_group = "<td><p id='group'><b>" . $group . "</b>" . (strpos($dislikedGroups[$timestamp], $group) !== false ? $dislikeText : "") . "</p>\n";
            $title_group = strpos($requestedGroup[$timestamp], $group) !== false ? "<td><p id='group'><b>" . $group . "</b>" . $requestedText . "</p>\n" : $title_group;
            echo $title_group;
            echo "<table id = 'a" . $i . "_" . $k . "' class = 'result'>\n";
            foreach ($json[$group] as $k2 => $v2) {
                if ($group != "Weather") {
                    if ($group == "Bus") {
                        $service = "BusStop";
                    } else if ($group == "Events") {
                        $service = "Event";
                    } else if ($group == "Transfer Services") {
                        if ($v2["Service"] != null) {
                            $service = "Service";
                        } else {
                            $service = "Sensor";
                        }
                    } else {
                        $service = "Service";
                    }
                    foreach ($v2[$service]["features"] as $k3 => $v3) {
                        foreach ($v3 as $k4 => $v4) {
                            if ($k4 == "properties") {
                                echo "<tr " . $tr_class . " >\n";
                                // check if the serviceUri was viewed after recommendation
                                $viewed_timestamp = isViewed($_REQUEST["user"], $v3[$k4]["serviceUri"], $timestamp);
                                $viewed = $viewed_timestamp != "" ? "<a title=\"Viewed after recommendation on " . $viewed_timestamp . "\">&#9679;</a>" : ""; //<img id='icon' src='images/eye.png' alt='Viewed after recommendation' height='16' width='16'/>" : "";
                                $servicemapurl = $config["servicemapurl"] . "?serviceUri=" . $v3[$k4]["serviceUri"] . "&format=html";
                                echo "<td>" . $viewed . "<a title=\"View on Map\" target=\"_blank\" href=\"" . $servicemapurl . "\"><img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/></a>";
                                if ($v3[$k4]["name"] != "")
                                    echo (strlen($v3[$k4]["name"]) > 40 ? (substr($v3[$k4]["name"], 0, 40) . "...") : $v3[$k4]["name"]) . "<br>";
                                //echo $v3[$k4]["name"] . "<br>";
                                echo $v3[$k4]["address"] . " " . $v3[$k4]["civic"] . " " . $v3[$k4]["cap"] . " " . $v3[$k4]["city"] . "<br>";
                                /* if ($v3[$k4]["description"] != "")
                                  echo $v3[$k4]["description"] . "<br>";
                                  if ($v3[$k4]["description2"] != "")
                                  echo $v3[$k4]["description2"] . "<br>"; */
                                //if ($v3[$k4]["phone"] != "")
                                // if this is an event
                                if ($service == "Event") {
                                    $startDate = split("T", $v3[$k4]["startDate"]);
                                    $startDate = $startDate[0];
                                    $endDate = split("T", $v3[$k4]["endDate"]);
                                    $endDate = $endDate[0];
                                    if ($startDate == $endDate) {
                                        $endDate = "";
                                    } else {
                                        $endDate = "/" . $endDate;
                                    }
                                    echo "Date: " . $startDate . $endDate . " " . "<br>";
                                } else {
                                    echo "Tel: " . $v3[$k4]["phone"] . " " . ($v3[$k4]["fax"] != "" ? " Fax: " . $v3[$k4]["fax"] : "") . "<br>";
                                }
                                //if ($v3[$k4]["website"] != "")
                                //echo "<a href=\"http://" . $v3[$k4]["website"] . "\"> " . $v3[$k4]["website"] . "</a>" . " " . ($v3[$k4]["email"] != "" ? "<a href=\"mailto:" . $v3[$k4]["email"] . "\" target=\"_top\">" . $v3[$k4]["email"] . "</a>" : "") . "<br>";
                                echo "<span class='coordinates'>" . $v3["geometry"]["coordinates"][1] . "," . $v3["geometry"]["coordinates"][0] . "</span>"; // . " Distance: " . $v3[$k4]["distance"] . " km<br>";
                                //echo " Distance: " . $v3[$k4]["distance"] . " km</td>\n";
                                echo " Distance: " . round(distFrom($coordinates[$timestamp]["latitude"], $coordinates[$timestamp]["longitude"], $v3["geometry"]["coordinates"][1], $v3["geometry"]["coordinates"][0]), 2) . " km</td>\n";
                                echo "</tr>\n";

                                //switch row style
                                if ($tr_class == "class='odd'") {
                                    $tr_class = "class='even'";
                                } else {
                                    $tr_class = "class='odd'";
                                }
                            }
                        }
                    }
                }
                // print the weather
                else {
                    echo "<tr " . $tr_class . " >\n";
                    echo "<td>" . $json[$group]["head"]["location"] . "<br>" .
                    "Max. Temp.: " . $json[$group]["results"]["bindings"][0]["maxTemp"]["value"] . " Min. Temp.: " . $json[$group]["results"]["bindings"][0]["minTemp"]["value"] . "<br>" .
                    $json[$group]["results"]["bindings"][0]["description"]["value"] . "<br><br>" .
                    "</td>\n";
                    echo "</tr>\n";

                    //switch row style
                    if ($tr_class == "class='odd'") {
                        $tr_class = "class='even'";
                    } else {
                        $tr_class = "class='odd'";
                    }
                }
            }
            // fill empty rows
            for ($j = count($json[$group]); $j < 3; $j++) {
                if ($group == "Weather") {
                    echo "<tr " . $tr_class . " >\n";
                    echo "<td>" . $json[$group]["head"]["location"] . "<br>" .
                    "Max. Temp.: " . $json[$group]["results"]["bindings"][0]["maxTemp"]["value"] . " Min. Temp.: " . $json[$group]["results"]["bindings"][0]["minTemp"]["value"] . "<br>" .
                    $json[$group]["results"]["bindings"][0]["description"]["value"] . "<br><br>" .
                    "</td>\n";
                    echo "</tr>\n";
                } else {
                    echo "<tr " . $tr_class . " ><td><br><br><br><br></td></tr>";
                }
                //switch row style
                if ($tr_class == "class='odd'") {
                    $tr_class = "class='even'";
                } else {
                    $tr_class = "class='odd'";
                }
            }
            echo "</table></td>\n";
            $k++;
        }
        echo "</tr>";
    }
    echo "</table></div>\n";
    //close connection
    mysqli_close($link);

    return array($i, $k);
}

// print empty table
//$json = json_decode("{\"Services and Utilities\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2492,43.8058],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Service_DigitalLocation\",\"note\":\"wifi.fid303cbba0142a32de586520a\",\"website\":\"\",\"address\":\"VIALE GAETANO PIERACCINI\",\"distance\":0.8791195092477824,\"city\":\"FIRENZE\",\"serviceUri\":\"http://www.disit.org/km4city/resource/ce25dbb863ef7aca3405f6a0bd4b12aa\",\"description\":\"Viale Pieraccini 6\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Gaetano_Pieraccini\"],\"civic\":\"8\",\"multimedia\":\"\",\"cap\":\"50139\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Firenze WIFI\",\"typeLabel\":\"Digital Location\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2492,43.8058],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Service_DigitalLocation\",\"note\":\"wifi.fid303cbba0142d5849839776\",\"website\":\"\",\"address\":\"VIALE GAETANO PIERACCINI\",\"distance\":0.8791195092477824,\"city\":\"FIRENZE\",\"serviceUri\":\"http://www.disit.org/km4city/resource/f341928665c0af4eab271f7db4a55439\",\"description\":\"Viale Pieraccini 6\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Gaetano_Pieraccini\"],\"civic\":\"8\",\"multimedia\":\"\",\"cap\":\"50139\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Firenze WIFI\",\"typeLabel\":\"Digital Location\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2492,43.8058],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TourismService_Wifi\",\"note\":\"\",\"website\":\"\",\"address\":\"VIALE PIERACCINI\",\"distance\":0.8791195092477824,\"city\":\"FIRENZE\",\"serviceUri\":\"http://www.disit.org/km4city/resource/wifi_210\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Gaetano_Pieraccini\"],\"civic\":\"6\",\"multimedia\":\"\",\"cap\":\"\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Firenze WIFI\",\"typeLabel\":\"Free WiFi point\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}}],\"Weather\":{\"head\":{\"location\":\"FIRENZE\",\"vars\":[\"day\",\"description\",\"minTemp\",\"maxTemp\",\"instantDateTime\"]},\"results\":{\"bindings\":[{\"maxTemp\":{\"type\":\"literal\",\"value\":\"19\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"coperto\"},\"day\":{\"type\":\"literal\",\"value\":\"Giovedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"16\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"24\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Venerdi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"13\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"25\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Sabato\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"11\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"velato\"},\"day\":{\"type\":\"literal\",\"value\":\"Domenica\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Lunedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}}]}}}");
/*  $json = objectToArray($json);
  $serviceURIs = getServiceURIs($json);
  foreach ($serviceURIs as $serviceURI)
  echo $serviceURI . "<br>"; */
//$json = "{\"Hotel\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5682,43.5252],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Rest_home\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA PASCOLI\",\"distance\":0.19028502128379646,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/f14e5672aeddea633163c96f0f899e02\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Giovanni_Pascoli\"],\"civic\":\"5\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"055980340\",\"name\":\"CASA DI RIPOSO DI MONTEVARCHI\",\"typeLabel\":\"Rest home\",\"fax\":\"055980340\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5638,43.5309],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"\",\"website\":\"www.bbnessunluogoelontano.blogspot.com\",\"address\":\"VIA MASSIMILIANO SOLDANI\",\"distance\":0.8719388969618738,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/c4805952e3b1480fb2b30b546a61bf2a\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[],\"civic\":\"3\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"3339418317\",\"name\":\"NESSUN_LUOGO_E'_LONTANO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"nessunluogoel@gmail.com\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5634,43.5319],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"Riscaldamento;Asciugacapelli;Accesso Internet;Servizio Colazione in Camera;Accesso con Vetture Private;Lampada Esterna;Edificio di Valore Storico;Colazione Compresa;Rifornimento benzina immediate vicinanze\",\"website\":\"www.bbnessunluogoelontano.blogspot.com\",\"address\":\"VIA MASSIMILIANO SOLDANI\",\"distance\":0.98743588444376,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/ca9951436c524e2f1ac5697a50786bd2\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Massimiliano_Soldani_Benzi\"],\"civic\":\"3\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"3339418317\",\"name\":\"NESSUN_LUOGO_E'_LONTANO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"nessunluogoel@gmail.com\"}}],\"type\":\"FeatureCollection\"}}],\"Weather\":{\"head\":{\"location\":\"MONTEVARCHI\",\"vars\":[\"day\",\"description\",\"minTemp\",\"maxTemp\",\"instantDateTime\"]},\"results\":{\"bindings\":[{\"maxTemp\":{\"type\":\"literal\",\"value\":\"23\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Venerdi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"17\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"24\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Sabato\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"12\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"22\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Domenica\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"12\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Lunedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Martedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}}]}}}";
//$json = objectToArray($json);
?>
<html>
    <head>
        <title>Recommendations</title>
        <link rel="stylesheet" type="text/css" href="css/reset.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <link rel="stylesheet" type="text/css" href = "css/jquery-ui.css"/>
        <script type="text/javascript" src="javascript/jquery-2.1.0.min.js"></script>
        <script type="text/javascript" src="javascript/jquery-ui.min.js"></script>
        <script type="text/javascript" src="javascript/jquery.redirect.js"></script>
        <!-- map headers -->
        <!--<link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.css" />-->
        <link rel="stylesheet" href="css/leaflet.css" />
        <script src="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.js"></script>

        <!--leaflet label plugin includes https://github.com/Leaflet/Leaflet.label-->
        <script src = "javascript/maps/leaflet-label-plugin/Label.js" ></script>
        <script src="javascript/maps/leaflet-label-plugin/BaseMarkerMethods.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/Marker.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/CircleMarker.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/Path.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/Map.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/FeatureGroup.Label.js"></script>
        <link rel="stylesheet" href="javascript/maps/leaflet-label-plugin/leaflet.label.css" />
        <!-- jquery scroll to plugin includes http://demos.flesler.com/jquery/scrollTo/ -->
        <script type="text/javascript" src="javascript/maps/jquery.scrollTo-2.1.2/jquery.scrollTo.min.js"></script>
                <!-- <style>
                    .map {
                        position: absolute;
                        width: 100%;
                        height: 100%;
                    }
                </style> -->
    </head>
    <body>
        <?php include_once "header.php"; //include header
        ?>
        <div id='container1'> <!-- div container -->
            <div id='recommendations1'> <!-- div recommendations -->
                <?php
                include_once "settings.php";
                include_once "Polyline.php";

                // print the shortest route path connecting the user's locations
                function printShortestRoute($coordinates) {
                    global $config;
                    // encoded polyline as returned by OSRM
                    //https://github.com/Project-OSRM/osrm-backend
                    //http://localhost:5000/viaroute?loc=43.7727,11.2532&loc=43.71328,11.22361
                    //$encoded = objectToArray(json_decode(file_get_contents($config['osrm_server_url'] . "/viaroute?" . substr($lat_lng, 1))));

                    $lat_lng = array();
                    foreach ($coordinates as $coordinate) {
                        $lat_lng[] = $coordinate[0] . "," . $coordinate[1];
                    }
                    // workaround to post an array of key => value with the same key (loc)
                    $vars = array('loc' => $lat_lng);
                    $query = http_build_query($vars, null, '&');
                    $data = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $query); //loc=x&loc=y&loc=z...
                    $options = array(
                        'http' => array(
                            'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                            'method' => 'POST',
                            'content' => $data,
                        )
                    );
                    $context = stream_context_create($options);
                    $result = file_get_contents($config['osrm_server_url'] . "/viaroute", false, $context);
                    $json = objectToArray(json_decode($result));
                    $points = Polyline::Decode($json["route_geometry"]);
                    // list of tuples
                    $points = Polyline::Pair($points);
                    $javascript = "";
                    foreach ($points as $point) {
                        // coordinates of geometry as returned by the OSRM server must be scaled by 10
                        $javascript = $javascript . ",L.latLng(" . ($point[0] / 10) . "," . ($point[1] / 10) . ")\n";
                    }
                    return substr($javascript, 1);
                    //var_dump($points);
                }

                // get path coordinates (mode = gps or manual)
                function getCoordinates() {
                    global $config;

                    //CONNECT
                    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

                    /* check connection */
                    if (mysqli_connect_errno()) {
                        printf("Connection failed: %s\n", mysqli_connect_error());
                        exit();
                    }
                    // GET DATA
                    $coordinates = array();
                    $sql = "SELECT latitude, longitude, `mode`, timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND DATE(timestamp) = '" . $_REQUEST["year"] . "-" . $_REQUEST["month"] . "-" . $_REQUEST["day"] . "' ORDER BY timestamp ASC";
                    $result = mysqli_query($link, $sql) or die(mysqli_error());
                    while ($row = mysqli_fetch_assoc($result)) {
                        $coordinates[] = $row["latitude"] . "|" . $row["longitude"] . "|" . $row["mode"] . "|" . $row["timestamp"];
                    }
                    return $coordinates;
                }

                function printCoordinates($coordinates) {
                    $javascript = "";
                    $javascript_manual_markers = "";
                    $i = 0;
                    foreach ($coordinates as $coordinate) {
                        $lat_lng_mode_timestamp = split("\|", $coordinate);
                        $latitude = $lat_lng_mode_timestamp[0];
                        $longitude = $lat_lng_mode_timestamp[1];
                        $mode = $lat_lng_mode_timestamp[2];
                        // print only if these are gps coordinates
                        if ($mode != "manual") {
                            $javascript .= ",L.latLng(" . $latitude . "," . $longitude . ")\n";
                            $i++;
                        } else {
                            $javascript_manual_markers .= ",L.latLng(" . $latitude . "," . $longitude . ")\n";
                        }
                    }
                    if ($i > 0) {
                        return array($i, substr($javascript, 1));
                    }
                    // if there are only manual coordinates return this javascript
                    else {
                        return array($i, substr($javascript_manual_markers, 1));
                    }
                }

                // print markers (gps or manual)
                function printMarkers($coordinates) {
                    $markers = "var markers = [];\n";
                    $markers .= "//Extend the Default marker class
                    var ManualMarkerIcon = L.Icon.Default.extend({
                    options: {
            	    iconUrl: 'images/location_red.png' 
                    }
                    });
                    var manualMarkerIcon = new ManualMarkerIcon();";
                    $markers .= "var manual_markers = [];\n";
                    $i = 0;
                    foreach ($coordinates as $coordinate) {
                        $lat_lng_mode_timestamp = split("\|", $coordinate);
                        $latitude = $lat_lng_mode_timestamp[0];
                        $longitude = $lat_lng_mode_timestamp[1];
                        $mode = $lat_lng_mode_timestamp[2];
                        $timestamp = split(" ", $lat_lng_mode_timestamp[3]);
                        $timestamp = $timestamp[1];
                        if ($mode != "manual") {
                            $markers .= "markers[" . $i . "] = L.marker([" . $latitude . "," . $longitude . "]).bindLabel('" . $timestamp . "', {noHide: true})" .
                                    ".bindPopup('" . $timestamp . "')" .
                                    ".addTo(map).on('click', " .
                                    "function(event){" .
                                    "$('#recommendations1').scrollTo(document.getElementById('" . $timestamp . "'), 800, {axis:'x', offset: {top:0, left:-50}});" .
                                    "event.target.closePopup();\n" .
                                    "});\n";
                        } else {
                            $markers .= "manual_markers[" . $i . "] = L.marker([" . $latitude . "," . $longitude . "], {icon: manualMarkerIcon}).bindLabel('" . $timestamp . "', {noHide: true})" .
                                    ".bindPopup('" . $timestamp . "')" .
                                    ".addTo(map).on('click', " .
                                    "function(event){" .
                                    "$('#recommendations1').scrollTo(document.getElementById('" . $timestamp . "'), 800, {axis:'x', offset: {top:0, left:-50}});" .
                                    "event.target.closePopup();\n" .
                                    "});\n";
                        }
                        $i++;
                    }
                    return $markers;
                }

                // get coordinates (gps and manual)
                $coordinates = getCoordinates();
                // print gps coordinates array (number of gps coordinates, coordinates javascript)
                $coordinates_javascript = printCoordinates($coordinates);
                // number of gps coordinates
                $coordinates_javascript_i = $coordinates_javascript[0];
                $coordinates_javascript = $coordinates_javascript[1];
                // print markers (gps and manual with different colors)
                $markers_javascript = printMarkers($coordinates);
                //$route_javascript = printShortestRoute($coordinates); // the shortest route path with given coordinates
                // display recommendations table
                $i_j = displayJSON();
                ?>
            </div> <!-- div recommendations -->
            <!-- display map javascript -->
            <div id="map" class="map"></div>
            <script type="text/javascript">
                var map = L.map('map');
                /*L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                 attribution: '© OpenStreetMap contributors',
                 maxZoom: 23
                 }).addTo(map);*/
                var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                        '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                        'Imagery © <a href="http://mapbox.com">Mapbox</a>';

                // for satellite map use mapbox.streets-satellite in the url
                L.tileLayer('https://api.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicGJlbGxpbmkiLCJhIjoiNTQxZDNmNDY0NGZjYTk3YjlkNTAzNWQwNzc0NzQwYTcifQ.CNfaDbrJLPq14I30N1EqHg', {
                    attribution: mbAttr,
                    maxZoom: 22
                }).addTo(map);
                var coordinateList = [<?php echo $coordinates_javascript; ?>];
                //var routeList = [<?php /* echo $route_javascript; */ ?>];

                // line of user's locations
                var coordinatesLine = new L.Polyline(coordinateList, {
                    color: 'blue',
                    weight: 8,
                    opacity: 0.5,
                    smoothFactor: 1
                });

                function centerOnMarker(m) {
                    m.openPopup();
                    var lat_lng = m.getLatLng();
                    //map.panTo(L.latLng(lat_lng.lat, lat_lng.lng));
                    map.panTo(lat_lng);
                    //map.setView(lat_lng, 22);
                }

                // line of shortest route path between user's locations
                /*var routeLine = new L.Polyline(routeList, {
                 color: 'red',
                 weight: 8,
                 opacity: 0.5,
                 smoothFactor: 1
                 });*/
                // add the line to the map only if there are almost two gps coordinates
<?php
if ($coordinates_javascript_i > 1) {
    echo "coordinatesLine.addTo(map);";
}
?>
                //routeLine.addTo(map);
<?php
echo $markers_javascript;
echo $manual_markers_javascript;
?>
                map.fitBounds(coordinatesLine.getBounds());
                //map.setView([57.505, -0.01], 13);
            </script>
        </div> <!-- div container -->
        <script>
            $(document).ready(function () {
                // replace each empty recommendations table with contents from previous table
                for (var i = 1; i <= <?php echo $i_j[0]; ?>; i++) {
                    for (var j = 2; j <= <?php echo $i_j[1]; ?>; j++) {
                        var table = $("table[id='a" + i + "_" + j + "']");
                        if (table.hasClass('empty')) {
                            table.html($("table[id='a" + i + "_" + (j - 1) + "']").html());
                        }
                    }
                }
                // custom marker icon
                var markerIcon = L.icon({
                    iconUrl: 'images/location.png',
                    iconSize: [54, 54],
                    iconAnchor: [10, 10],
                    labelAnchor: [6, 0]
                });
                var locations = [];
                // when a result table is clicked show the correspondent recommendations on the map
                $(".result").click(function () {
                    // remove previous markers
                    for (var i = 0; i < locations.length; i++) {
                        map.removeLayer(locations[i]);
                    }
                    locations = [];
                    //var coordinateList = [];

                    $(this).children().find("span.coordinates").each(function () {
                        var lat_lng = $(this).text().split(",");
                        var location = L.marker(lat_lng, {icon: markerIcon});
                        location.bindLabel($(this).parent().html(), {noHide: true});
                        map.addLayer(location);
                        locations.push(location);
                        //coordinateList.push(lat_lng);
                    });
                    // zoom map to fit clicked recommendations locations and user location  
                    /*
                     if (coordinateList.length > 0) {
                     // get user location
                     var user_location = $(this).parent().parent().parent().find("th a").attr("href");
                     user_location = user_location.split("=");
                     user_location = user_location[1];
                     user_location = user_location.split("&");
                     var user_lat_lng = user_location[0].split(";");
                     coordinateList.push(user_lat_lng);
                     var bounds = new L.LatLngBounds(coordinateList);
                     map.fitBounds(bounds);
                     }*/
                });
            });
        </script>
    </body>
</html>
