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
ini_set('max_execution_time', 9999999); //300 seconds = 5 minutes
ini_set("memory_limit", "-1");

//http://www.movable-type.co.uk/scripts/latlong.html
//http://research.microsoft.com/en-us/projects/clearflow/
//http://jsfiddle.net/Rodrigoson6/2yfebsgn/
// get user data

function getUserData($link) {
    $data = array();
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "'";
    } else {
        $sql = "SELECT timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL";
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
        FROM  recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user
        WHERE HOUR(TIMESTAMP) = h
        AND b.label IS NULL AND a.profile = '" . $profile . "'
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
        $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = '" . $_REQUEST["profile"] . "'";
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        } else {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = '" . $_REQUEST["profile"] . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
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
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = '" . $profile . "' ORDER BY timestamp ASC";
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
    $sql = "SELECT categories FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND mode = 'api-services-by-gps' AND b.label IS NULL AND b.profile = '" . $profile . "' ORDER BY timestamp ASC";
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
    $dataGrouping = array("units" => array(array("day", array(1))));
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = '" . $profile . "' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    return json_encode(array(array("type" => "column", "name" => $profile, "dataGrouping" => $dataGrouping, "data" => $json)));
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
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND profile = '" . $_REQUEST["profile"] . "'";
        }
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "' AND viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY)";
        } else {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY) AND b.label IS NULL AND b.profile = '" . $_REQUEST["profile"] . "'";
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

// get the user profiles map
function getUserProfiles($link) {
    $profiles = array();
    $sql = "SELECT user, profile FROM recommender.users WHERE label IS NULL";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $profiles[$row["user"]] = $row["profile"];
    }
    return $profiles;
}

function getStartEndDateActiveDays($link) {
    $sql = "SELECT count(distinct(date(timestamp))) AS active_days, min(timestamp) AS min_timestamp, max(timestamp) AS max_timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = '" . $_REQUEST["profile"] . "'";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamp = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = array($row["min_timestamp"], $row["max_timestamp"], $row["active_days"]);
    }
    return $timestamp;
}

// get speed distance data for a profile (JSON)
function getSpeedDistanceJSONOld($link, $profile) {
    $users_query = "SELECT a.user FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE a.profile = '" . $profile . "' AND b.label IS NULL";
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
    $ranges = array("0 - 5 km/h", "5 - 20 km/h", "20 - 50 km/h", "50 - 130 km/h", "130 - 300 km/h", ">= 300 km/h");
    for ($i = 1; $i < 7; $i++) {
        $d = isset($distance[$i]) ? round(floatval($distance[$i]), 2) : 0;
        $dst[] = array($d);
    }
    return array($ranges, json_encode(array(array("name" => $profile, "data" => $dst))));
}

// get speed distance data for a profile (JSON)
function getSpeedDistanceJSON($link, $profile, $normalized) {
    global $config;
    $client = new MongoClient($config["mongodb_flows_url"]);
    MongoCursor::$timeout = -1; // avoid MongoCursor timeout
    $collection = $client->data->collection;
    $options = [
        '$and' => [
            ['$or' => [
                    ['provider' => 'fused'],
                    ['provider' => 'gps']
                ]
            ],
            [
                'user' => ['$ne' => '0000000011111111222222223333333344444444555555556666666677777777']
            ],
            [
                'profile' => $profile
            ]
        ]
    ];
    $profiles = getUserProfiles($link);
    $i = 0;
    $latitude = 0;
    $longitude = 0;
    $timestamp = "";
    // number of users in a speed range
    $users = array();
    // sort the results by user desc, timestamp desc
    $cursor = $collection->find($options)->sort(["user" => 1, "timestamp" => 1]); //->limit(100);
    foreach ($cursor as $v) {
        // if this user is not banned
        if (isset($profiles[$v["user"]])) {
            $actual_timestamp = date('Y-M-d H:i:s', $v["timestamp"]->sec);
            if ($actual_timestamp != $timestamp) {
                if ($i != 0) {
                    $dist = abs(distFrom(doubleval($v["latitude"]), doubleval($v["longitude"]), $latitude, $longitude)); // km
                    $time = abs((strtotime($actual_timestamp) - strtotime($timestamp)) / 3600.0); // h
                    $speed = $dist / $time; // km/h
                    if ($speed > 0) {
                        $index = toSpeedRange($speed);
                        $users[$index][$v["user"]] = "";
                        $distance[$index] = isset($distance[$index]) ? $distance[$index] + $dist : $dist;
                    }
                }
                $latitude = doubleval($v["latitude"]);
                $longitude = doubleval($v["longitude"]);
                $timestamp = $actual_timestamp;
                $i++;
            }
        }
    }
    // if normalized is true, then normalize distances to the number of users in their speed range
    if ($normalized) {
        foreach ($users as $index => $number) {
            $distance[$index] /= count($users[$index]);
        }
    }
    $client->close();
    ksort($distance);
    $dst = array();
    $keys = array();
    $ranges = array("0 - 5 km/h", "5 - 20 km/h", "20 - 50 km/h", "50 - 130 km/h", "130 - 300 km/h", ">= 300 km/h");
    for ($i = 1; $i < 7; $i++) {
        $d = isset($distance[$i]) ? round(floatval($distance[$i]), 2) : 0;
        $dst[] = array($d);
    }
    return array($ranges, json_encode(array(array("name" => $profile, "data" => $dst))));
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
            return "50 - 130 km/h";
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
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = '" . $profile . "' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        return json_encode(array(array("name" => $profile, "data" => $data)));
    } else if ($days > 0) {
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = '" . $profile . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $num = $row["num"];
        }
        return $num;
    } else {
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = '" . $profile . "'";
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
    global $distance_disabled;
    global $distance_operator;

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
        case 'disabled':
            return $distance_disabled;
            break;
        case 'operator':
            return $distance_operator;
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
    global $distance_disabled_keys;
    global $distance_operator_keys;

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
        case 'disabled':
            return $distance_disabled_keys;
            break;
        case 'operator':
            return $distance_operator_keys;
            break;
        default:
            break;
    }
}

function getRequestedGroups($link) {
    $groups = array();
    $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT requestedGroup, COUNT(*) AS num FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE requestedGroup != '' AND a.profile = '" . $_REQUEST["profile"] . "' AND b.label IS NULL GROUP BY requestedGroup ORDER BY requestedGroup ASC) AS n ON c.group = n.requestedGroup ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = array(intval($row["num"]));
    }
    return json_encode(array(array("type" => "column", "name" => "requested groups", "data" => $groups)));
}

// get the views after recommendations (JSON)
function getViewedAfter($link) {
    $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT b.group, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.categories_groups b ON a.macroclass = b.key LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $_REQUEST["profile"] . "' AND b.group != 'Twitter1' AND b.group != 'Twitter2' AND b.group != 'Twitter3' AND d.label IS NULL GROUP BY b.group UNION SELECT `group`, COUNT(*) AS num FROM recommender.tweets_log i LEFT JOIN recommender.users l ON i.user = l.user WHERE l.profile = '" . $_REQUEST["profile"] . "' AND l.label IS NULL GROUP BY `group`) AS n ON c.group = n.group ORDER BY c." . $_REQUEST["profile"] . " ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(intval($row["num"]));
    }
    return json_encode(array(array("name" => "viewed after rec", "data" => $data)));
}

// get the views after recommendations timeline (JSON)
function getViewsAfterRecommendationsJSON($link) {
    $dataGrouping = array("units" => array(array("day", array(1))));
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = '" . $_REQUEST["profile"] . "' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    $json = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    return json_encode(array(array("type" => "column", "name" => $_REQUEST["profile"], "dataGrouping" => $dataGrouping, "data" => $json)));
}

function getDisliked($link) {
    $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT c.group, COUNT(*) AS num FROM recommender.dislike a LEFT JOIN recommender.groups c ON a.dislikedGroup = c.group LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $_REQUEST["profile"] . "' AND c.group IS NOT NULL AND d.label IS NULL) AS n ON c.group = n.group ORDER BY c." . $_REQUEST["profile"] . " ASC";
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
    $sql = "SELECT location, COUNT(*) AS num FROM(SELECT CONCAT(round(latitude * " . $zones_square_size . "), '_', round(longitude * " . $zones_square_size . ")) AS location FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE a.profile = '" . $_REQUEST["profile"] . "' AND b.label IS NULL) AS a GROUP BY location ORDER BY num DESC";
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
round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user
 WHERE a.profile = '" . $_REQUEST["profile"] . "' AND b.label IS NULL AND (mode = 'gps' OR mode = 'fused')) AS a GROUP BY x, y ORDER BY num DESC LIMIT 50";
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
(SELECT a.user,round(longitude/180*PI()* 6371000 / 138)*138 AS x, 
round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user
 WHERE a.profile = '" . $_REQUEST["profile"] . "' AND b.label IS NULL AND (mode = 'gps' OR mode = 'fused')) AS a GROUP BY x, y ORDER BY num DESC LIMIT 50";
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

include_once "settings.php"; // settings
include_once "GeoLocation.php";
global $config;
//CONNECT
$link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
/* check connection */
if (mysqli_connect_errno()) {
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
}
$profiles = ["all", "citizen", "commuter", "student", "tourist", "disabled", "operator"];

foreach ($profiles as $s) {
    $_REQUEST["profile"] = $s;

    // get user profile
    //$profile = getUserProfile($link);
    // get recommendations json and save to file
    $recommendations_json = getRecommendationsJSON($link, $_REQUEST["profile"]);
    file_put_contents("./json/recommendations_" . $_REQUEST["profile"] . ".json", $recommendations_json);

    // get time slots json and save to file
    $timeslots_json = getUserTimeSlotsJSON($link, $_REQUEST["profile"]);
    file_put_contents("./json/timeslots_" . $_REQUEST["profile"] . ".json", $timeslots_json);

    // get number of recommendations in the last 24 h and save to file
    $nrecommendations_1_day = getRecommendations($link, 1);
    file_put_contents("./json/nrecommendations24h_" . $_REQUEST["profile"] . ".json", json_encode($nrecommendations_1_day));

    // get number of recommendations in the last 7 days and save to file
    $nrecommendations_7_days = getRecommendations($link, 7);
    file_put_contents("./json/nrecommendations7days_" . $_REQUEST["profile"] . ".json", json_encode($nrecommendations_7_days));

    // get number of recommendations in the last 30 days and save to file
    $nrecommendations_30_days = getRecommendations($link, 30);
    file_put_contents("./json/nrecommendations30days_" . $_REQUEST["profile"] . ".json", json_encode($nrecommendations_30_days));

    // get total number of recommendations and save to file
    $nrecommendations = getRecommendations($link, 0);
    file_put_contents("./json/nrecommendationsTotal_" . $_REQUEST["profile"] . ".json", json_encode($nrecommendations));

    // get number of view after recommendations in the last 24 h and save to file
    $views_after_recommendations_1_day = getViews_after_recommendations($link, 1);
    file_put_contents("./json/views_after_recommendations_1_day_" . $_REQUEST["profile"] . ".json", json_encode($views_after_recommendations_1_day));
    // get number of view after recommendations in the last 7 days and save to file
    $views_after_recommendations_7_days = getViews_after_recommendations($link, 7);
    file_put_contents("./json/views_after_recommendations_7_days_" . $_REQUEST["profile"] . ".json", json_encode($views_after_recommendations_7_days));
    // get number of view after recommendations in the last 30 days and save to file
    $views_after_recommendations_30_days = getViews_after_recommendations($link, 30);
    file_put_contents("./json/views_after_recommendations_30_days_" . $_REQUEST["profile"] . ".json", json_encode($views_after_recommendations_30_days));
    // get totatl number of view after recommendations and save to file
    $views_after_recommendations = getViews_after_recommendations($link, 0);
    file_put_contents("./json/views_after_recommendations_" . $_REQUEST["profile"] . ".json", json_encode($views_after_recommendations));

    // get queries json and save to file
    $menu_json_keys_total = getQueriesJSON($link, $_REQUEST["profile"]);
    file_put_contents("./json/menu_keys_total_" . $_REQUEST["profile"] . ".json", json_encode($menu_json_keys_total));

    // get views json and save to file
    $views_json = getViewsJSON($link, $_REQUEST["profile"]);
    file_put_contents("./json/views_" . $_REQUEST["profile"] . ".json", $views_json);

    // get min and max timestamp and save to file
    $min_max_timestamp_active_days = getStartEndDateActiveDays($link);
    file_put_contents("./json/min_max_timestamp_active_days_" . $_REQUEST["profile"] . ".json", json_encode($min_max_timestamp_active_days));

    // get speed distance and save to file
    $distance = getSpeedDistanceJSON($link, $_REQUEST["profile"], false);
    file_put_contents("./json/distance_" . $_REQUEST["profile"] . ".json", json_encode($distance));

    // get normalized speed distance and save to file
    $normalized_distance = getSpeedDistanceJSON($link, $_REQUEST["profile"], true);
    file_put_contents("./json/normalized_distance_" . $_REQUEST["profile"] . ".json", json_encode($normalized_distance));

    // get active users json (total) and save to file
    $active_users_total_n_json = getActiveUsers($link, $_REQUEST["profile"], 0);
    file_put_contents("./json/active_users_total_n_" . $_REQUEST["profile"] . ".json", $active_users_total_n_json);
    // get active users (total) and save to file
    $active_users_total = getActiveUsers($link, $_REQUEST["profile"], -1);
    file_put_contents("./json/active_users_total_" . $_REQUEST["profile"] . ".json", $active_users_total);
    // get active users (last 24 h) and save to file
    $active_users_last_day = getActiveUsers($link, $_REQUEST["profile"], 1);
    file_put_contents("./json/active_users_last_day_" . $_REQUEST["profile"] . ".json", $active_users_last_day);
    // get active users (last 7 days) and save to file
    $active_users_last_7_days = getActiveUsers($link, $_REQUEST["profile"], 7);
    file_put_contents("./json/active_users_last_7_days_" . $_REQUEST["profile"] . ".json", $active_users_last_7_days);
    // get active users (last 30 days) and save to file
    $active_users_last_30_days = getActiveUsers($link, $_REQUEST["profile"], 30);
    file_put_contents("./json/active_users_last_30_days_" . $_REQUEST["profile"] . ".json", $active_users_last_30_days);

    // get groups and save to file
    $groups_keys = json_encode(getGroups($link));
    file_put_contents("./json/groups_keys_" . $_REQUEST["profile"] . ".json", $groups_keys);

    // get requested groups and save to file
    $requested_groups = getRequestedGroups($link);
    file_put_contents("./json/requested_groups_" . $_REQUEST["profile"] . ".json", $requested_groups);

    // get views after recommendations and save to file
    $views_after_rec = getViewedAfter($link);
    file_put_contents("./json/views_after_rec_" . $_REQUEST["profile"] . ".json", $views_after_rec);

    // get the number of views after recommendations timeline json and save to file
    $views_after_recommendations_graph = getViewsAfterRecommendationsJSON($link);
    file_put_contents("./json/views_after_recommendations_graph_" . $_REQUEST["profile"] . ".json", json_encode($views_after_recommendations_graph));

    // get disliked and save to file
    $disliked = getDisliked($link);
    file_put_contents("./json/disliked_" . $_REQUEST["profile"] . ".json", $disliked);

    // get zones flows and save to file
    $zones_flows_keys = getZonesFlows($link);
    file_put_contents("./json/zones_flows_keys_" . $_REQUEST["profile"] . ".json", json_encode($zones_flows_keys));

    // get zones flows for distinct users and save to file
    $zones_flows_users_keys = getZonesFlowsUsers($link);
    file_put_contents("./json/zones_flows_users_keys_" . $_REQUEST["profile"] . ".json", json_encode($zones_flows_users_keys));
}
//close connection
mysqli_close($link);
?>