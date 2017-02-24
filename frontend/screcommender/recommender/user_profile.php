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
ini_set('max_execution_time', 300); //300 seconds = 5 minutes
//http://www.movable-type.co.uk/scripts/latlong.html
//http://research.microsoft.com/en-us/projects/clearflow/
//http://jsfiddle.net/Rodrigoson6/2yfebsgn/
// get user data

function getUserData($link) {
    $data = array();
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "'";
    } else {
        $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE " . getBannedUsers("");
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = $row["timestamp"];
    }
    return $timestamp;
}

// get user time slots
function getUserTimeSlotsJSON($link, $profile) {
    $result = array();
    $sql = "SELECT h,
        (
        SELECT COUNT(*)
        FROM  recommender.recommendations_log
        WHERE HOUR(TIMESTAMP) = h
        AND " . getBannedUsers("") . " AND profile = '" . $profile . "'
        ) AS n
FROM    (
        SELECT 0 AS h
        UNION ALL
        SELECT 1 AS h
        UNION ALL
        SELECT 2 AS h
        UNION ALL
        SELECT 3 AS h
        UNION ALL
        SELECT 4 AS h
        UNION ALL
        SELECT 5 AS h
        UNION ALL
        SELECT 6 AS h
        UNION ALL
        SELECT 7 AS h
        UNION ALL
        SELECT 8 AS h
        UNION ALL
        SELECT 9 AS h
        UNION ALL
        SELECT 10 AS h
        UNION ALL
        SELECT 11 AS h
        UNION ALL
        SELECT 12 AS h
        UNION ALL
        SELECT 13 AS h
        UNION ALL
        SELECT 14 AS h
        UNION ALL
        SELECT 15 AS h
        UNION ALL
        SELECT 16 AS h
        UNION ALL
        SELECT 17 AS h
        UNION ALL
        SELECT 18 AS h
        UNION ALL
        SELECT 19 AS h
        UNION ALL
        SELECT 20 AS h
        UNION ALL
        SELECT 21 AS h
        UNION ALL
        SELECT 22 AS h
        UNION ALL
        SELECT 23 AS h
    ) ids";
    $json = array();
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = intval($row["n"]);
    }
    return json_encode(array(array("name" => $profile, "data" => $json)));
}

// calculate the number of recommendations in the last n days
function getRecommendations($link, $days) {
    $total = array();
    if ($days == 0) {
        $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE " . getBannedUsers("") . "AND profile = '" . $_REQUEST["profile"] . "'";
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        } else {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE " . getBannedUsers("") . "AND profile = '" . $_REQUEST["profile"] . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        }
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $total = array(intval($row["nrecommendations"]), intval($row["nrecommendations_weather"]));
    }
    return $total;
}

// get the number of recommendations (JSON)
function getRecommendationsJSON($link, $profile) {
    $json = array();
    $json_weather = array();
    $dataGrouping = array("units" => array(array("day", array(1))));
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log WHERE " . getBannedUsers("") . "AND profile = '" . $profile . "' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    return json_encode(array(array("type" => "column", "name" => $profile, "dataGrouping" => $dataGrouping, "data" => $json)));
}

// get the top 30 menu count (JSON)
function getQueriesJSON($link, $profile) {
    $json = array();
    $keys = array();
    $sql = "SELECT categories FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND mode = 'api-services-by-gps' AND " . str_replace("user", "uid", getBannedUsers("")) . " AND b.profile = '" . $profile . "' ORDER BY timestamp ASC";
    $categories = array();
    $total = 0;
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $tmp = split(";", $row["categories"]);
        foreach ($tmp as $value) {
            $categories[$value] = isset($categories[$value]) ? $categories[$value] + 1 : 1;
        }
    }
    arsort($categories);
    $i = 0;
    foreach ($categories as $key => $value) {
        if ($i == 30) {
            break;
        }
        $json[] = $value;
        $keys[] = $key;
        $total += $value;
        $i++;
    }
    return array(json_encode($json), $keys, $total);
}

// get the menu count (JSON)
function getViewsJSON($link, $profile) {
    global $config;
    $json = array();
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND " . str_replace("user", "uid", getBannedUsers("")) . "AND profile = '" . $profile . "' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    return json_encode(array(array("type" => "column", "name" => $profile, "data" => $json)));
}

function getGroups($link) {
    $groups = array();
    $sql = "SELECT `group` FROM recommender.groups ORDER BY `" . $_REQUEST["profile"] . "` ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row["group"];
    }
    return $groups;
}

// calculate the number of views after recommendations in the last n days, 0 = views, 1 = users
function getViews_after_recommendations($link, $days) {
    $num = 0;
    if ($days == 0) {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(user)) AS user FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "'";
        } else {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE " . getBannedUsers("a.") . " AND profile = '" . $_REQUEST["profile"] . "'";
        }
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "' AND viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY)";
        } else {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY) AND " . getBannedUsers("a.") . " AND profile = '" . $_REQUEST["profile"] . "'";
        }
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $num = array($row['num'], $row['user']);
    }
    return $num;
}

function getUserProfile($link) {
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT profile FROM recommender.users WHERE user = '" . $_REQUEST["user"] . "'";
    } else {
        return "";
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $profile = $row['profile'];
    }
    return $profile;
}

function getStartEndDateActiveDays($link) {
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT count(distinct(date(timestamp))) AS active_days, min(timestamp) AS min_timestamp, max(timestamp) AS max_timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "'";
    } else {
        $sql = "SELECT count(distinct(date(timestamp))) AS active_days, min(timestamp) AS min_timestamp, max(timestamp) AS max_timestamp FROM recommender.recommendations_log WHERE " . getBannedUsers("") . " AND profile = '" . $_REQUEST["profile"] . "'";
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamp = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = array($row["min_timestamp"], $row["max_timestamp"], $row["active_days"]);
    }
    return $timestamp;
}

// get speed distance data for a profile (JSON)
function getSpeedDistanceJSON($link, $profile) {
    $distance = array();
    if (isset($_REQUEST["user"])) {
        $users_query = "SELECT user FROM recommender.recommendations_log WHERE profile = '" . $profile . "' AND user = '" . $_REQUEST["user"] . "'";
    } else {
        $users_query = "SELECT user FROM recommender.recommendations_log WHERE profile = '" . $profile . "' AND " . getBannedUsers("");
    }
    $users_query_result = mysqli_query($link, $users_query) or die(mysqli_error());
    while ($row_users = mysqli_fetch_assoc($users_query_result)) {
        $i = 0;
        $latitude = 0;
        $longitude = 0;
        $timestamp = "";
        $sql = "SELECT latitude, longitude, timestamp FROM recommender.recommendations_log WHERE user = '" . $row_users["user"] . "' AND `mode` = 'gps'";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            if ($row["timestamp"] != $timestamp) {
                if ($i != 0) {
                    $dist = abs(distFrom($row["latitude"], $row["longitude"], $latitude, $longitude)); // km
                    $time = abs((strtotime($row["timestamp"]) - strtotime($timestamp)) / 3600.0); // h
                    $speed = $dist / $time; // km/h
                    if ($speed > 0) {
                        $index = toSpeedRange($speed);
                        $distance[$index] = isset($distance[$index]) ? $distance[$index] + $dist : $dist;
                    }
                }
                $latitude = $row["latitude"];
                $longitude = $row["longitude"];
                $timestamp = $row["timestamp"];
                $i++;
            }
        }
    }
    ksort($distance);
    $dst = array();
    $keys = array();
    foreach ($distance as $key => $value) {
        $dst[] = array(round(floatval($value), 2));
        $keys[] = getSpeedRange($key);
    }
    return array($keys, json_encode(array(array("name" => $profile, "data" => $dst))));
}

function getSpeedRange($index) {
    switch ($index) {
        case 1:
            return "0 - 5 km/h";
            break;
        case 2:
            return "5 - 20 km/h";
        case 3:
            return "20 - 50 km/h";
            break;
        case 4:
            return "20 - 130 km/h";
            break;
        case 5:
            return "130 - 300 km/h";
            break;
        case 6:
            return ">= 300 km/h";
            break;
        default:
            break;
    }
}

function toSpeedRange($speed) {
    if ($speed < 5) {
        return 1;
    } else if ($speed < 20) {
        return 2;
    } else if ($speed < 50) {
        return 3;
    } else if ($speed < 130) {
        return 4;
    } else if ($speed < 300) {
        return 5;
    } else {
        return 6;
    }
}

function getActiveUsers($link, $profile, $days) {
    if ($days == 0) {
        $sql = "SELECT COUNT(DISTINCT(user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log WHERE " . getBannedUsers("") . " AND profile = '" . $profile . "' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        return json_encode(array(array("name" => $profile, "data" => $data)));
    } else if ($days > 0) {
        $sql = "SELECT COUNT(DISTINCT(user)) AS num FROM recommender.recommendations_log WHERE " . getBannedUsers("") . "AND profile = '" . $profile . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $num = $row["num"];
        }
        return $num;
    } else {
        $sql = "SELECT COUNT(DISTINCT(user)) AS num FROM recommender.recommendations_log WHERE " . getBannedUsers("") . " AND profile = '" . $profile . "'";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $num = $row["num"];
        }
        return $num;
    }
}

// calculate distance in m between coordinates in decimal degrees (latitude, longitude)
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

function getDistance($profile) {
    global $distance_all;
    global $distance_citizen;
    global $distance_commuter;
    global $distance_student;
    global $distance_tourist;

    switch ($profile) {
        case 'all':
            return $distance_all;
            break;
        case 'citizen':
            return $distance_citizen;
            break;
        case 'commuter':
            return $distance_commuter;
            break;
        case 'student':
            return $distance_student;
            break;
        case 'tourist':
            return $distance_tourist;
            break;
        default:
            break;
    }
}

function getDistanceKeys($profile) {
    global $distance_all_keys;
    global $distance_citizen_keys;
    global $distance_commuter_keys;
    global $distance_student_keys;
    global $distance_tourist_keys;

    switch ($profile) {
        case 'all':
            return $distance_all_keys;
            break;
        case 'citizen':
            return $distance_citizen_keys;
            break;
        case 'commuter':
            return $distance_commuter_keys;
            break;
        case 'student':
            return $distance_student_keys;
            break;
        case 'tourist':
            return $distance_tourist_keys;
            break;
        default:
            break;
    }
}

function getRequestedGroups($link) {
    $groups = array();
    $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT requestedGroup, COUNT(*) AS num FROM recommender.recommendations_log WHERE requestedGroup != '' AND profile = '" . $_REQUEST["profile"] . "' AND " . getBannedUsers("") . " GROUP BY requestedGroup ORDER BY requestedGroup ASC) AS n ON c.group = n.requestedGroup ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = array(intval($row["num"]));
    }
    return json_encode(array(array("type" => "column", "name" => "requested groups", "data" => $groups)));
}

function getViewedAfter($link) {
    //$sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT b.group, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.categories_groups b ON a.macroclass = b.key LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $_REQUEST["profile"] . "' GROUP BY b.group ORDER BY b.group ASC) AS n ON c.group = n.group ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT b.group, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.categories_groups b ON a.macroclass = b.key LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $_REQUEST["profile"] . "' AND b.group != 'Twitter1' AND b.group != 'Twitter2' AND b.group != 'Twitter3' AND " . getBannedUsers("a.") . " GROUP BY b.group UNION SELECT `group`, COUNT(*) AS num FROM recommender.tweets_log i LEFT JOIN recommender.users l ON i.user = l.user WHERE l.profile = '" . $_REQUEST["profile"] . "' AND " . getBannedUsers("i.") . " GROUP BY `group`) AS n ON c.group = n.group ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(intval($row["num"]));
    }
    return json_encode(array(array("name" => "viewed after rec", "data" => $data)));
}

function getDisliked($link) {
    //$sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT c.group, COUNT(*) AS num FROM recommender.dislike a LEFT JOIN recommender.service_category_menus b ON a.dislikedSubclass = b.SubClass LEFT JOIN recommender.categories_groups c ON b.MacroClass = c.key LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $_REQUEST["profile"] . "' GROUP BY c.group ASC ORDER BY c.group ASC) AS n ON c.group = n.group ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT c.group, COUNT(*) AS num FROM recommender.dislike a LEFT JOIN recommender.groups c ON a.dislikedGroup = c.group LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $_REQUEST["profile"] . "' AND c.group IS NOT NULL AND " . getBannedUsers("a.") . ") AS n ON c.group = n.group ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(intval($row["num"]));
    }
    return json_encode(array(array("name" => "disliked", "data" => $data)));
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
        //$house_number = $road != "" && isset($json["address"]["house_number"]) ? ", " . $json["address"]["house_number"] : "";
        //$postcode = isset($json["address"]["postcode"]) ? "<br>" . $json["address"]["postcode"] : "";
        //$city = isset($json["address"]["city"]) ? " " . $json["address"]["city"] : (isset($json["address"]["town"]) ? " " . $json["address"]["town"] : "");
        //$country = isset($json["address"]["country"]) ? " (" . $json["address"]["country"] . ")" : "";
        $nominatim = $road;
    }
    return $nominatim;
}

function getZonesFlowsOld($link) {
    $sql = "SELECT value FROM recommender.settings WHERE name='zones_square_size'";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $value = doubleval($row["value"]);
        $earthRadius = 6371000; // m
        $zones_square_size = (1 / ($value * 180 / (M_PI * $earthRadius)));
    }
    $sql = "SELECT location, COUNT(*) AS num FROM(SELECT CONCAT(round(latitude * " . $zones_square_size . "), '_', round(longitude * " . $zones_square_size . ")) AS location FROM recommender.recommendations_log WHERE profile = '" . $_REQUEST["profile"] . "' AND " . getBannedUsers("") . ") AS a GROUP BY location ORDER BY num DESC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        if (intval($row["num"]) > 20) {
            $data[] = intval($row["num"]);
            $lat_lon = split("_", $row["location"]);
            $keys[] = round($lat_lon[0] / $zones_square_size, 4) . "_" . round($lat_lon[1] / $zones_square_size, 4);
        }
    }
    $json_data = json_encode(array(array("type" => "column", "name" => "zones", "data" => $data)));
    return array(json_encode($keys), $json_data, $value);
}

function getZonesFlows($link) {
    $sql = "SELECT 
(x-138/2)/6371000*180/PI() AS lon_bl,
(2*atan(exp((y-138/2)/6371000))-PI()/2)*180/PI() AS lat_bl,
x/6371000*180/PI() AS lon,
(2*atan(exp(y/6371000))-PI()/2)*180/PI() AS lat, 
(x+138/2)/6371000*180/PI() AS lon_tr,
(2*atan(exp((y+138/2)/6371000))-PI()/2)*180/PI() AS lat_tr,
COUNT(*) AS num
FROM
(SELECT round(longitude/180*PI()* 6371000 / 138)*138 AS x, 
round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log
 WHERE profile = '" . $_REQUEST["profile"] . "' AND " . getBannedUsers("") . " AND mode = 'gps') AS a GROUP BY x, y ORDER BY num DESC LIMIT 50";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        //if (intval($row["num"]) > 20) {
        $data[] = intval($row["num"]);
        $keys[] = round($row["lat"], 4) . "_" . round($row["lon"], 4);
        $bounding[round($row["lat"], 4) . "_" . round($row["lon"], 4)] = array(array($row["lat_bl"], $row["lon_bl"]), array($row["lat_tr"], $row["lon_tr"]));
        //}
    }
    $json_data = json_encode(array(array("type" => "column", "name" => "zones", "data" => $data)));
    return array(json_encode($keys), $json_data, $bounding);
}

function getZonesFlowsUsers($link) {
    $sql = "SELECT 
(x-138/2)/6371000*180/PI() AS lon_bl,
(2*atan(exp((y-138/2)/6371000))-PI()/2)*180/PI() AS lat_bl,
x/6371000*180/PI() AS lon,
(2*atan(exp(y/6371000))-PI()/2)*180/PI() AS lat, 
(x+138/2)/6371000*180/PI() AS lon_tr,
(2*atan(exp((y+138/2)/6371000))-PI()/2)*180/PI() AS lat_tr,
COUNT(DISTINCT(user)) AS num
FROM
(SELECT user,round(longitude/180*PI()* 6371000 / 138)*138 AS x, 
round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log
 WHERE profile = '" . $_REQUEST["profile"] . "' AND " . getBannedUsers("") . " AND mode = 'gps') AS a GROUP BY x, y ORDER BY num DESC LIMIT 50";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        //if (intval($row["num"]) > 20) {
        $data[] = intval($row["num"]);
        $keys[] = round($row["lat"], 4) . "_" . round($row["lon"], 4);
        $bounding[round($row["lat"], 4) . "_" . round($row["lon"], 4)] = array(array($row["lat_bl"], $row["lon_bl"]), array($row["lat_tr"], $row["lon_tr"]));
        //}
    }
    $json_data = json_encode(array(array("type" => "column", "name" => "zones", "data" => $data)));
    return array(json_encode($keys), $json_data, $bounding);
}

function getServiceMapLocationUrl($latitude, $longitude) {
    
}

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

include_once "header.php"; //include header
include_once "settings.php"; // settings
include_once "functions.php"; //function getBannedUsers
include_once "GeoLocation.php";
global $config;
//CONNECT
$link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
/* check connection */
if (mysqli_connect_errno()) {
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
}
// get user profile
$profile = getUserProfile($link);

// get recommendations json
//$recommendations_json = getRecommendationsJSON($link, $_REQUEST["profile"]);
$recommendations_json = file_get_contents("./json/recommendations_" . $_REQUEST["profile"] . ".json");

// get time slots json
//$timeslots_json = getUserTimeSlotsJSON($link, $_REQUEST["profile"]);
$timeslots_json = file_get_contents("./json/timeslots_" . $_REQUEST["profile"] . ".json");

// get number of recommendations in the last 24 h
//$nrecommendations_1_day = getRecommendations($link, 1);
$nrecommendations_1_day = json_decode(file_get_contents("./json/nrecommendations24h_" . $_REQUEST["profile"] . ".json"));
$nrecommendations_1_day = $nrecommendations_1_day[0];
//$nrecommendations_1_day_weather = $nrecommendations_1_day[1];
// get number of recommendations in the last 7 days
//$nrecommendations_7_days = getRecommendations($link, 7);
$nrecommendations_7_days = json_decode(file_get_contents("./json/nrecommendations7days_" . $_REQUEST["profile"] . ".json"));
$nrecommendations_7_days = $nrecommendations_7_days[0];
//$nrecommendations_7_days_weather = $nrecommendations_7_days[1];
// get number of recommendations in the last 30 days
//$nrecommendations_30_days = getRecommendations($link, 30);
$nrecommendations_30_days = json_decode(file_get_contents("./json/nrecommendations30days_" . $_REQUEST["profile"] . ".json"));
$nrecommendations_30_days = $nrecommendations_30_days[0];
//$nrecommendations_30_days_weather = $nrecommendations_30_days[1];
// get total number of recommendations
//$nrecommendations = getRecommendations($link, 0);
$nrecommendations = json_decode(file_get_contents("./json/nrecommendationsTotal_" . $_REQUEST["profile"] . ".json"));
$nrecommendations = $nrecommendations[0];

// get number of view after recommendations in the last 24 h
//$views_after_recommendations_1_day = getViews_after_recommendations($link, 1);
$views_after_recommendations_1_day = json_decode(file_get_contents("./json/views_after_recommendations_1_day_" . $_REQUEST["profile"] . ".json"));
// get number of view after recommendations in the last 7 days
//$views_after_recommendations_7_days = getViews_after_recommendations($link, 7);
$views_after_recommendations_7_days = json_decode(file_get_contents("./json/views_after_recommendations_7_days_" . $_REQUEST["profile"] . ".json"));
// get number of view after recommendations in the last 30 days
//$views_after_recommendations_30_days = getViews_after_recommendations($link, 30);
$views_after_recommendations_30_days = json_decode(file_get_contents("./json/views_after_recommendations_30_days_" . $_REQUEST["profile"] . ".json"));
// get totatl number of view after recommendations
//$views_after_recommendations = getViews_after_recommendations($link, 0);
$views_after_recommendations = json_decode(file_get_contents("./json/views_after_recommendations_" . $_REQUEST["profile"] . ".json"));

// get queries json
//$menu_json_keys_total = getQueriesJSON($link, $_REQUEST["profile"]);
$menu_json_keys_total = json_decode(file_get_contents("./json/menu_keys_total_" . $_REQUEST["profile"] . ".json"));
$menu_json = $menu_json_keys_total[0];
$keys = $menu_json_keys_total[1];
$total = $menu_json_keys_total[2];
$keys_s = "";
foreach ($keys as $value) {
    $keys_s .= ",'" . $value . "'";
}
$keys_s = substr($keys_s, 1);

// get views json
//$views_json = getViewsJSON($link, $_REQUEST["profile"]);
$views_json = file_get_contents("./json/views_" . $_REQUEST["profile"] . ".json");

// get min and max timestamp
//$min_max_timestamp_active_days = getStartEndDateActiveDays($link);
$min_max_timestamp_active_days = json_decode(file_get_contents("./json/min_max_timestamp_active_days_" . $_REQUEST["profile"] . ".json"));
$min_timestamp = $min_max_timestamp_active_days[0];
$max_timestamp = $min_max_timestamp_active_days[1];
$active_days = $min_max_timestamp_active_days[2];

// get speed distance
//$distance = getSpeedDistanceJSON($link, $_REQUEST["profile"]);
$distance = json_decode(file_get_contents("./json/distance_" . $_REQUEST["profile"] . ".json"));
$distance_keys = json_encode($distance[0]);
$distance = $distance[1];

// get normalized speed distance
//$distance = getSpeedDistanceJSON($link, $_REQUEST["profile"]);
$normalized_distance = json_decode(file_get_contents("./json/normalized_distance_" . $_REQUEST["profile"] . ".json"));
$normalized_distance_keys = json_encode($normalized_distance[0]);
$normalized_distance = $normalized_distance[1];

// get active users json (total)
//$active_users_total_n_json = getActiveUsers($link, $_REQUEST["profile"], 0);
$active_users_total_n_json = file_get_contents("./json/active_users_total_n_" . $_REQUEST["profile"] . ".json");
// get active users (total)
//$active_users_total = getActiveUsers($link, $_REQUEST["profile"], -1);
$active_users_total = file_get_contents("./json/active_users_total_" . $_REQUEST["profile"] . ".json");
// get active users (last 24 h)
//$active_users_last_day = getActiveUsers($link, $_REQUEST["profile"], 1);
$active_users_last_day = file_get_contents("./json/active_users_last_day_" . $_REQUEST["profile"] . ".json");
// get active users (last 7 days)
//$active_users_last_7_days = getActiveUsers($link, $_REQUEST["profile"], 7);
$active_users_last_7_days = file_get_contents("./json/active_users_last_7_days_" . $_REQUEST["profile"] . ".json");
// get active users (last 30 days)
//$active_users_last_30_days = getActiveUsers($link, $_REQUEST["profile"], 30);
$active_users_last_30_days = file_get_contents("./json/active_users_last_30_days_" . $_REQUEST["profile"] . ".json");

// get groups
//$groups_keys = json_encode(getGroups($link));
$groups_keys = file_get_contents("./json/groups_keys_" . $_REQUEST["profile"] . ".json");

// get requested groups
//$requested_groups = getRequestedGroups($link);
$requested_groups = file_get_contents("./json/requested_groups_" . $_REQUEST["profile"] . ".json");

// get views after recommendations
//$views_after_rec = getViewedAfter($link);
$views_after_rec = file_get_contents("./json/views_after_rec_" . $_REQUEST["profile"] . ".json");

// get views after recommendations
$views_after_recommendations_graph = json_decode(file_get_contents("./json/views_after_recommendations_graph_" . $_REQUEST["profile"] . ".json"));

// get disliked
//$disliked = getDisliked($link);
$disliked = file_get_contents("./json/disliked_" . $_REQUEST["profile"] . ".json");

// get zones flows
//$zones_flows_keys = getZonesFlows($link);
$zones_flows_keys = json_decode(file_get_contents("./json/zones_flows_keys_" . $_REQUEST["profile"] . ".json"));
$zones_flows = $zones_flows_keys[1];
$zones_keys = $zones_flows_keys[0];
$zones_bounding_box = $zones_flows_keys[2];

// get zones flows for distinct users
//$zones_flows_users_keys = getZonesFlowsUsers($link);
$zones_flows_users_keys = json_decode(file_get_contents("./json/zones_flows_users_keys_" . $_REQUEST["profile"] . ".json"));
$zones_flows_users = $zones_flows_users_keys[1];
$zones_users_keys = $zones_flows_users_keys[0];
$zones_bounding_box_users = $zones_flows_users_keys[2];

//close connection
mysqli_close($link);
?>
<html>
    <head>
        <title>User Profile - <?php echo $_REQUEST["profile"]; ?></title>
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
        <!-- highstock includes -->
        <script src="javascript/highstock/highstock.js"></script>
        <script src="javascript/highstock/modules/exporting.js"></script>
        <script type="text/javascript" src="javascript/highstock/modules/export-csv.js"></script>

    </head>
    <body>
        <div id='container1'> <!-- div container -->     
            <div id='profile'>
                <h3><b><?php echo ucfirst($_REQUEST["profile"]); ?></b></h3>
                <p>
                    Recs (24 h): 
                    <?php
                    echo $nrecommendations_1_day;
                    echo ' (' . $active_users_last_day . ' users)';
                    ?>
                    <br>
                    Recs (7 days): 
                    <?php
                    echo $nrecommendations_7_days;
                    echo ' (' . $active_users_last_7_days . ' users)';
                    ?>
                    <br>
                    Recs (30 days): 
                    <?php
                    echo $nrecommendations_30_days;
                    echo ' (' . $active_users_last_30_days . ' users)';
                    ?>
                    <br>
                    Recs (total): 
                    <?php
                    echo $nrecommendations;
                    echo ' (' . $active_users_total . ' users)';
                    ?>
                    <br>
                    Views after Recs (24 h): 
                    <?php
                    echo $views_after_recommendations_1_day[0] . "/" . $nrecommendations_1_day . ($nrecommendations_1_day > 0 ? " (" . round(100 * $views_after_recommendations_1_day[0] / $nrecommendations_1_day, 2) . "%)" : " (0%)");
                    echo ' (' . $views_after_recommendations_1_day[1] . ' users)';
                    ?><br>
                    Views after Recs (7 days): 
                    <?php
                    echo $views_after_recommendations_7_days[0] . "/" . $nrecommendations_7_days . ($nrecommendations_7_days > 0 ? " (" . round(100 * $views_after_recommendations_7_days[0] / $nrecommendations_7_days, 2) . "%)" : "");
                    echo ' (' . $views_after_recommendations_7_days[1] . ' users)';
                    ?>
                    <br>
                    Views after Recs (30 days): 
                    <?php
                    echo $views_after_recommendations_30_days[0] . "/" . $nrecommendations_30_days . ($nrecommendations_30_days > 0 ? " (" . round(100 * $views_after_recommendations_30_days[0] / $nrecommendations_30_days, 2) . "%)" : "");
                    echo ' (' . $views_after_recommendations_30_days[1] . ' users)';
                    ?>
                    <br>
                    Views after Recs (total): 
                    <?php
                    echo $views_after_recommendations[0] . "/" . $nrecommendations . ($nrecommendations > 0 ? " (" . round(100 * $views_after_recommendations[0] / $nrecommendations, 2) . "%)" : "");
                    echo ' (' . $views_after_recommendations[1] . ' users)';
                    ?>
                    <br>
                    Start Activity Date: <?php echo $min_timestamp; ?><br>
                    Last Activity Date: <?php echo $max_timestamp; ?><br>
                    Days: <?php echo intval(round((strtotime($max_timestamp) - strtotime($min_timestamp)) / (3600 * 24), 2)); ?><br>
                    Active Days: <?php echo $active_days; ?><br>
                </p>
            </div>
            <div id="recommendations" style="height: 800px"></div>
            <div id="views" style="height: 800px"></div>
            <div id="timeSlots" style="height: 800px"></div>
            <div id="queries" style="height: 400px"></div>
            <div id='activeUsers' style='height: 400px'></div>
            <div id='speed' style='height: 400px'></div>
            <div id='normalized_speed' style='height: 400px'></div>
            <div id="requested_groups" style="height: 400px"></div>
            <div id="views_after_recommendations" style="height: 400px"></div>
            <div id="views_after_recommendations_timeline" style="height: 400px"></div>
            <div id="disliked_groups" style="height: 400px"></div>
            <div id="zones_flows" style="height: 800px"></div>
            <div id="zones_flows_users" style="height: 800px"></div>
        </div>

        <script type="text/javascript">
            // set UTC timezone to false for all charts
            Highcharts.setOptions({
                global: {
                    useUTC: false
                }
            });
            $(function () {
                // create the charts
                $('#recommendations').highcharts('StockChart', {
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    legend: {
                        enabled: true
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: true,
                                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                style: {
                                    textShadow: '0 0 3px black'
                                }
                            },
                            pointWidth: 20,
                            pointPadding: 0.5, // Defaults to 0.1
                            groupPadding: 0.5 // Defaults to 0.2
                        },
                    },
                    navigator: {
                        enabled: true
                    },
                    xAxis: {
                        ordinal: false,
                        type: 'datetime',
                        tickInterval: 24 * 3600 * 1000,
                        labels: {
                            rotation: -65,
                            formatter: function () {
                                //return Highcharts.dateFormat('%a %d %b %H:%M:%S', this.value);
                                return Highcharts.dateFormat('%a %d %b', this.value);
                            }
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Recommendations'
                    },
                    series: JSON.parse('<?php echo $recommendations_json; ?>')
                });
                $('#views').highcharts('StockChart', {
                    chart: {
                        type: 'column',
                    },
                    legend: {
                        enabled: true
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: true,
                                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                style: {
                                    textShadow: '0 0 3px black'
                                }
                            },
                            pointWidth: 20,
                            pointPadding: 0.5, // Defaults to 0.1
                            groupPadding: 0.5 // Defaults to 0.2
                        },
                    },
                    xAxis: {
                        ordinal: false,
                        type: 'datetime',
                        tickInterval: 24 * 3600 * 1000,
                        labels: {
                            rotation: -60,
                            formatter: function () {
                                return Highcharts.dateFormat('%a %d %b', this.value);
                            }
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Views'
                    },
                    series: JSON.parse('<?php echo $views_json; ?>')
                });
                $('#timeSlots').highcharts({
                    chart: {
                        type: 'bar', //column
                    },
                    xAxis: {
                        categories: ['00:00:00 - 00:59:59', '01:00:00 - 01:59:59', '02:00:00 - 02:59:59',
                            '03:00:00 - 03:59:59', '04:00:00 - 04:59:59', '05:00:00 - 05:59:59',
                            '06:00:00 - 06:59:59', '07:00:00 - 07:59:59', '08:00:00 - 08:59:59',
                            '09:00:00 - 09:59:59', '10:00:00 - 10:59:59', '11:00:00 - 11:59:59',
                            '12:00:00 - 12:59:59', '13:00:00 - 13:59:59', '14:00:00 - 14:59:59',
                            '15:00:00 - 15:59:59', '16:00:00 - 16:59:59', '17:00:00 - 17:59:59',
                            '18:00:00 - 18:59:59', '19:00:00 - 19:59:59', '20:00:00 - 20:59:59',
                            '21:00:00 - 21:59:59', '22:00:00 - 22:59:59', '23:00:00 - 23:59:59']
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Time Slots'
                    },
                    series: JSON.parse('<?php echo $timeslots_json; ?>')
                });
                $('#queries').highcharts({
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [<?php echo $keys_s; ?>]
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return this.y + "<br>(" + Highcharts.numberFormat(100 * this.y / <?php echo $total; ?>, 0) + "%)";
                                },
                                inside: true
                            },
                            enableMouseTracking: false
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Queries'
                    },
                    series: [{
                            name: 'all',
                            data: JSON.parse('<?php echo $menu_json; ?>')
                        }]
                });
                $('#activeUsers').highcharts('StockChart', {
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    legend: {
                        enabled: true
                    },
                    navigator: {
                        enabled: true
                    },
                    xAxis: {
                        ordinal: false,
                        type: 'datetime',
                        tickInterval: 24 * 3600 * 1000,
                        labels: {
                            rotation: -65,
                            formatter: function () {
                                //return Highcharts.dateFormat('%a %d %b %H:%M:%S', this.value);
                                return Highcharts.dateFormat('%a %d %b', this.value);
                            }
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Active Users'
                    },
                    series: JSON.parse('<?php echo $active_users_total_n_json; ?>')
                });
                $('#speed').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $distance_keys; ?>')
                    },
                    yAxis: {
                        title: {
                            text: 'Distance (km)'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Speed/Distance'
                    },
                    series: JSON.parse('<?php echo $distance; ?>')
                });
                $('#normalized_speed').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $normalized_distance_keys; ?>')
                    },
                    yAxis: {
                        title: {
                            text: 'Normalized Distance (km)'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Normalized Speed/Distance'
                    },
                    series: JSON.parse('<?php echo $normalized_distance; ?>')
                });
                $('#requested_groups').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Group'
                        },
                        categories: JSON.parse('<?php echo $groups_keys; ?>'),
                    },
                    yAxis: {
                        title: {
                            text: 'Requests'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Requested Groups'
                    },
                    series: JSON.parse('<?php echo $requested_groups; ?>')
                });
                $('#views_after_recommendations').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Group'
                        },
                        categories: JSON.parse('<?php echo $groups_keys; ?>'),
                    },
                    yAxis: {
                        title: {
                            text: 'Views'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Views after recommendations (categories)'
                    },
                    series: JSON.parse('<?php echo $views_after_rec; ?>')
                });
                $('#views_after_recommendations_timeline').highcharts('StockChart', {
                    chart: {
                        type: 'column',
                    },
                    legend: {
                        enabled: true
                    },
                    plotOptions: {
                        column: {
                            stacking: 'normal',
                            dataLabels: {
                                enabled: true,
                                color: (Highcharts.theme && Highcharts.theme.dataLabelsColor) || 'white',
                                style: {
                                    textShadow: '0 0 3px black'
                                }
                            },
                            pointWidth: 20,
                            pointPadding: 0.5, // Defaults to 0.1
                            groupPadding: 0.5 // Defaults to 0.2
                        },
                    },
                    xAxis: {
                        ordinal: false,
                        type: 'datetime',
                        tickInterval: 24 * 3600 * 1000,
                        labels: {
                            rotation: -60,
                            formatter: function () {
                                return Highcharts.dateFormat('%a %d %b', this.value);
                            }
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Views after recommendations (timeline)'
                    },
                    series: JSON.parse('<?php echo $views_after_recommendations_graph; ?>')
                });
                $('#disliked_groups').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Group'
                        },
                        categories: JSON.parse('<?php echo $groups_keys; ?>'),
                    },
                    yAxis: {
                        title: {
                            text: 'Disliked'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Disliked Groups'
                    },
                    series: JSON.parse('<?php echo $disliked; ?>')
                });
                $('#zones_flows').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Zones'
                        },
                        categories: JSON.parse('<?php echo $zones_keys; ?>'),
                        labels: {
                            formatter: function () {
                                lat_lon = this.value.split("_");
                                bounding_box = JSON.parse('<?php echo json_encode($zones_bounding_box); ?>');
                                // calculate delta latitude and longitude from (lat, lon) which is the center of the square
                                // so to get the bottom left and the top right vertexes coordinates of the square
                                // since the displacements aren't too great (less than a few kilometers) and you're not right at the poles
                                // use the quick and rough estimate that 111,111 meters (111.111 km) in the y direction is 1 degree (of latitude)
                                // and 111,111 * cos(latitude) meters in the x direction is 1 degree (of longitude).
                                //delta_lat = <?php /* echo $zones_square_size / 2 */; ?> / 111111;
                                //delta_lon = Math.cos(parseFloat(lat_lon[0])) * <?php /* echo $zones_square_size / 2; */ ?> / 111111;
                                //delta_lat = 1 /<?php /* echo $zones_square_size; */ ?>;
                                //delta_lon = 1 /<?php /* echo $zones_square_size; */ ?>;
                                /*lat_bottom_left_vertex = parseFloat(lat_lon[0]) - delta_lat;
                                 lon_bottom_left_vertex = parseFloat(lat_lon[1]) - delta_lon;
                                 lat_top_right_vertex = parseFloat(lat_lon[0]) + delta_lat;
                                 lon_top_right_vertex = parseFloat(lat_lon[1]) + delta_lon;*/
                                /*$.ajax({
                                 type: "POST",
                                 async: false,
                                 url: "geo.php",
                                 data: {latitude: lat_lon[0], longitude: lat_lon[1], radius: <?php /* echo $zones_square_size / 2000; */ ?>},
                                 success:
                                 function (data) {
                                 //obj = JSON.parse(data);
                                 obj = JSON && JSON.parse(data) || $.parseJSON(data);
                                 lat_bottom_left_vertex = obj[0][0];
                                 lon_bottom_left_vertex = obj[0][1];
                                 lat_top_right_vertex = obj[1][0];
                                 lon_top_right_vertex = obj[1][1];
                                 },
                                 });*/
                                lat_bottom_left_vertex = bounding_box[this.value][0][0];
                                lon_bottom_left_vertex = bounding_box[this.value][0][1];
                                lat_top_right_vertex = bounding_box[this.value][1][0];
                                lon_top_right_vertex = bounding_box[this.value][1][1];
                                servicemapurl = "<?php
                    global $config;
                    echo $config["servicemapurl"];
                    ?>" + "?selection=" + lat_bottom_left_vertex + ";" + lon_bottom_left_vertex + ";" + lat_top_right_vertex + ";" + lon_top_right_vertex + "&format=html";
                                return "<a title=\"View location on Map\" target=\"_blank\" href=\"" + servicemapurl + "\">" + lat_lon[0] + ";" + lat_lon[1] + "</a>";
                            },
                            rotation: -60,
                            useHTML: true
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Flows'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Zones Flows (square side 100 m)'
                    },
                    plotOptions: {
                        series: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        lat_lon = this.category.split("_");
                                        bounding_box = JSON.parse('<?php echo json_encode($zones_bounding_box); ?>');
                                        lat_bottom_left_vertex = bounding_box[this.category][0][0];
                                        lon_bottom_left_vertex = bounding_box[this.category][0][1];
                                        lat_top_right_vertex = bounding_box[this.category][1][0];
                                        lon_top_right_vertex = bounding_box[this.category][1][1];
                                        servicemapurl = "<?php
                    global $config;
                    echo $config["servicemapurl"];
                    ?>" + "?selection=" + lat_bottom_left_vertex + ";" + lon_bottom_left_vertex + ";" + lat_top_right_vertex + ";" + lon_top_right_vertex + "&format=html";
                                        var win = window.open(servicemapurl, '_blank');
                                        win.focus();
                                    }
                                }
                            }
                        }
                    },
                    series: JSON.parse('<?php echo $zones_flows; ?>')
                });
                $('#zones_flows_users').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Zones'
                        },
                        categories: JSON.parse('<?php echo $zones_users_keys; ?>'),
                        labels: {
                            formatter: function () {
                                lat_lon = this.value.split("_");
                                bounding_box = JSON.parse('<?php echo json_encode($zones_bounding_box_users); ?>');
                                lat_bottom_left_vertex = bounding_box[this.value][0][0];
                                lon_bottom_left_vertex = bounding_box[this.value][0][1];
                                lat_top_right_vertex = bounding_box[this.value][1][0];
                                lon_top_right_vertex = bounding_box[this.value][1][1];
                                servicemapurl = "<?php
                    global $config;
                    echo $config["servicemapurl"];
                    ?>" + "?selection=" + lat_bottom_left_vertex + ";" + lon_bottom_left_vertex + ";" + lat_top_right_vertex + ";" + lon_top_right_vertex + "&format=html";
                                return "<a title=\"View location on Map\" target=\"_blank\" href=\"" + servicemapurl + "\">" + lat_lon[0] + ";" + lat_lon[1] + "</a>";
                            },
                            rotation: -60,
                            useHTML: true
                        }
                    },
                    yAxis: {
                        title: {
                            text: 'Flows'
                        }
                    },
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Zones Flows Distinct Users (square side 100 m)'
                    },
                    plotOptions: {
                        series: {
                            cursor: 'pointer',
                            point: {
                                events: {
                                    click: function () {
                                        lat_lon = this.category.split("_");
                                        bounding_box = JSON.parse('<?php echo json_encode($zones_bounding_box_users); ?>');
                                        lat_bottom_left_vertex = bounding_box[this.category][0][0];
                                        lon_bottom_left_vertex = bounding_box[this.category][0][1];
                                        lat_top_right_vertex = bounding_box[this.category][1][0];
                                        lon_top_right_vertex = bounding_box[this.category][1][1];
                                        servicemapurl = "<?php
                    global $config;
                    echo $config["servicemapurl"];
                    ?>" + "?selection=" + lat_bottom_left_vertex + ";" + lon_bottom_left_vertex + ";" + lat_top_right_vertex + ";" + lon_top_right_vertex + "&format=html";
                                        var win = window.open(servicemapurl, '_blank');
                                        win.focus();
                                    }
                                }
                            }
                        }
                    },
                    series: JSON.parse('<?php echo $zones_flows_users; ?>')
                });
            });
        </script>
    </body>