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

//http://research.microsoft.com/en-us/projects/clearflow/
//http://jsfiddle.net/Rodrigoson6/2yfebsgn/
// get user data
function getUserData($link) {
    $data = array();
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "'";
    } else {
        $sql = "SELECT timestamp FROM recommender.recommendations_log WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020'";
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
        AND user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020' AND profile = '" . $profile . "'
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
        $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020'";
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        } else {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
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
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020' AND profile = '" . $profile . "' ORDER BY timestamp ASC";
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
    $sql = "SELECT categories FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND mode = 'api-services-by-gps' AND uid != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND uid != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND uid != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND uid != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND uid != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND uid != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020' AND b.profile = '" . $profile . "' ORDER BY timestamp ASC";
    $categories = array();
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
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND uid != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND uid != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND uid != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND uid != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND uid != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND uid != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020' AND profile = '" . $profile . "' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    return json_encode(array(array("type" => "column", "name" => $profile, "data" => $json)));
}

// calculate the number of views after recommendations in the last n days
function getViews_after_recommendations($link, $days) {
    $num = 0;
    if ($days == 0) {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "'";
        } else {
            $sql = "SELECT COUNT(*) AS num FROM recommender.recommendations_stats WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020'";
        }
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "' AND viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY)";
        } else {
            $sql = "SELECT COUNT(*) AS num FROM recommender.recommendations_stats WHERE viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY) AND user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020'";
        }
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $num = $row['num'];
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
        $sql = "SELECT count(distinct(date(timestamp))) AS active_days, min(timestamp) AS min_timestamp, max(timestamp) AS max_timestamp FROM recommender.recommendations_log WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020'";
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
        $users_query = "SELECT user FROM recommender.recommendations_log WHERE profile = '" . $profile . "' AND user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020'";
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

function getActiveUsers($link, $profile) {
    $sql = "SELECT COUNT(DISTINCT(user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log WHERE user != 'd2fd74a68f195145d283cf293cde94d1e5114b81b10b6551a996a2b17f532805' AND user != '911c62dc12d8ad8aebda0dd7c1675be0d32f0edb90e29b6e9e71c77b45626b96' AND user != '42767b11352d69d6408ffcc8216a4438017cbbcc44ba5d07abd6ff38d859d519' AND user != '07bbdafed8706ec39030bd22cc75bdc191b5e0d87b5d511d7cc44e1df70645ee' AND user != 'b8fdcc28133b4391037a8609dfe1bb656c40cfb408c76918632bd9d698322918' AND user != 'fcc35ae7e786b9dbf9839a096b0c98bb9565ccbbb3306425cda76417cab27020' AND profile = '" . $profile . "' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $data = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $data[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    return json_encode(array(array("name" => $profile, "data" => $data)));
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

include_once "header.php"; //include header
include_once "settings.php";
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
$recommendations_all_json = getRecommendationsJSON($link, "all");
$recommendations_citizen_json = getRecommendationsJSON($link, "citizen");
$recommendations_commuter_json = getRecommendationsJSON($link, "commuter");
$recommendations_student_json = getRecommendationsJSON($link, "student");
$recommendations_tourist_json = getRecommendationsJSON($link, "tourist");

// get time slots json
$timeslots_all_json = getUserTimeSlotsJSON($link, "all");
$timeslots_citizen_json = getUserTimeSlotsJSON($link, "citizen");
$timeslots_commuter_json = getUserTimeSlotsJSON($link, "commuter");
$timeslots_student_json = getUserTimeSlotsJSON($link, "student");
$timeslots_tourist_json = getUserTimeSlotsJSON($link, "tourist");

// get number of recommendations in the last 24 h
$nrecommendations_1_day = getRecommendations($link, 1);
$nrecommendations_1_day = $nrecommendations_1_day[0];
//$nrecommendations_1_day_weather = $nrecommendations_1_day[1];
// get number of recommendations in the last 7 days
$nrecommendations_7_days = getRecommendations($link, 7);
$nrecommendations_7_days = $nrecommendations_7_days[0];
//$nrecommendations_7_days_weather = $nrecommendations_7_days[1];
// get number of recommendations in the last 30 days
$nrecommendations_30_days = getRecommendations($link, 30);
$nrecommendations_30_days = $nrecommendations_30_days[0];
//$nrecommendations_30_days_weather = $nrecommendations_30_days[1];
// get total number of recommendations
$nrecommendations = getRecommendations($link, 0);
$nrecommendations = $nrecommendations[0];

// get number of view after recommendations in the last 24 h
$views_after_recommendations_1_day = getViews_after_recommendations($link, 1);
// get number of view after recommendations in the last 7 days
$views_after_recommendations_7_days = getViews_after_recommendations($link, 7);
// get number of view after recommendations in the last 30 days
$views_after_recommendations_30_days = getViews_after_recommendations($link, 30);
// get totatl number of view after recommendations
$views_after_recommendations = getViews_after_recommendations($link, 0);

// get queries json
$menu_all_json_keys_total = getQueriesJSON($link, "all");
$menu_all_json = $menu_all_json_keys_total[0];
$keys_all = $menu_all_json_keys_total[1];
$total_all = $menu_all_json_keys_total[2];
$keys_all_s = "";
foreach ($keys_all as $value) {
    $keys_all_s .= ",'" . $value . "'";
}
$keys_all_s = substr($keys_all_s, 1);

$menu_citizen_json_keys_total = getQueriesJSON($link, "citizen");
$menu_citizen_json = $menu_citizen_json_keys_total[0];
$keys_citizen = $menu_citizen_json_keys_total[1];
$total_citizen = $menu_citizen_json_keys_total[2];
$keys_citizen_s = "";
foreach ($keys_citizen as $value) {
    $keys_citizen_s .= ",'" . $value . "'";
}
$keys_citizen_s = substr($keys_citizen_s, 1);

$menu_commuter_json_keys_total = getQueriesJSON($link, "commuter");
$menu_commuter_json = $menu_commuter_json_keys_total[0];
$keys_commuter = $menu_commuter_json_keys_total[1];
$total_commuter = $menu_commuter_json_keys_total[2];
$keys_commuter_s = "";
foreach ($keys_commuter as $value) {
    $keys_commuter_s .= ",'" . $value . "'";
}
$keys_commuter_s = substr($keys_commuter_s, 1);

$menu_student_json_keys_total = getQueriesJSON($link, "student");
$menu_student_json = $menu_student_json_keys_total[0];
$keys_student = $menu_student_json_keys_total[1];
$total_student = $menu_student_json_keys_total[2];
$keys_student_s = "";
foreach ($keys_student as $value) {
    $keys_student_s .= ",'" . $value . "'";
}
$keys_student_s = substr($keys_student_s, 1);

$menu_tourist_json_keys_total = getQueriesJSON($link, "tourist");
$menu_tourist_json = $menu_tourist_json_keys_total[0];
$keys_tourist = $menu_tourist_json_keys_total[1];
$total_tourist = $menu_tourist_json_keys_total[2];
$keys_tourist_s = "";
foreach ($keys_tourist as $value) {
    $keys_tourist_s .= ",'" . $value . "'";
}
$keys_tourist_s = substr($keys_tourist_s, 1);

// get views json
$views_all_json = getViewsJSON($link, "all");
$views_citizen_json = getViewsJSON($link, "citizen");
$views_commuter_json = getViewsJSON($link, "commuter");
$views_student_json = getViewsJSON($link, "student");
$views_tourist_json = getViewsJSON($link, "tourist");

// get min and max timestamp
$min_max_timestamp_active_days = getStartEndDateActiveDays($link);
$min_timestamp = $min_max_timestamp_active_days[0];
$max_timestamp = $min_max_timestamp_active_days[1];
$active_days = $min_max_timestamp_active_days[2];

// get speed distance
if (!isset($_REQUEST["user"])) {
    $distance_all = getSpeedDistanceJSON($link, "all");
    $distance_all_keys = json_encode($distance_all[0]);
    $distance_all = $distance_all[1];

    $distance_citizen = getSpeedDistanceJSON($link, "citizen");
    $distance_citizen_keys = json_encode($distance_citizen[0]);
    $distance_citizen = $distance_citizen[1];

    $distance_commuter = getSpeedDistanceJSON($link, "commuter");
    $distance_commuter_keys = json_encode($distance_commuter[0]);
    $distance_commuter = $distance_commuter[1];

    $distance_student = getSpeedDistanceJSON($link, "student");
    $distance_student_keys = json_encode($distance_student[0]);
    $distance_student = $distance_student[1];

    $distance_tourist = getSpeedDistanceJSON($link, "tourist");
    $distance_tourist_keys = json_encode($distance_tourist[0]);
    $distance_tourist = $distance_tourist[1];
} else {
    if ($profile == "all") {
        $distance_all = getSpeedDistanceJSON($link, "all");
        $distance_all_keys = json_encode($distance_all[0]);
        $distance_all = $distance_all[1];
    } else if ($profile == "citizen") {
        $distance_citizen = getSpeedDistanceJSON($link, "citizen");
        $distance_citizen_keys = json_encode($distance_citizen[0]);
        $distance_citizen = $distance_citizen[1];
    } else if ($profile == "commuter") {
        $distance_commuter = getSpeedDistanceJSON($link, "commuter");
        $distance_commuter_keys = json_encode($distance_commuter[0]);
        $distance_commuter = $distance_commuter[1];
    } else if ($profile == "student") {
        $distance_student = getSpeedDistanceJSON($link, "student");
        $distance_student_keys = json_encode($distance_student[0]);
        $distance_student = $distance_student[1];
    } else if ($profile == "tourist") {
        $distance_tourist = getSpeedDistanceJSON($link, "tourist");
        $distance_tourist_keys = json_encode($distance_tourist[0]);
        $distance_tourist = $distance_tourist[1];
    }
}

// get active users
$active_users_all_json = getActiveUsers($link, "all");
$active_users_citizen_json = getActiveUsers($link, "citizen");
$active_users_commuter_json = getActiveUsers($link, "commuter");
$active_users_student_json = getActiveUsers($link, "student");
$active_users_tourist_json = getActiveUsers($link, "tourist");

//close connection
mysqli_close($link);
?>
<html>
    <head>
        <title>User Profile</title>
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
                <p>
                    Recs (24 h): <?php echo $nrecommendations_1_day; ?><br>
                    Recs (7 days): <?php echo $nrecommendations_7_days; ?><br>
                    Recs (30 days): <?php echo $nrecommendations_30_days; ?><br>
                    Recs (total): <?php echo $nrecommendations; ?><br>
                    Views after Recs (24 h): <?php echo $views_after_recommendations_1_day . "/" . $nrecommendations_1_day . ($nrecommendations_1_day > 0 ? " (" . round(100 * $views_after_recommendations_1_day / $nrecommendations_1_day, 2) . "%)" : " (0%)"); ?><br>
                    Views after Recs (7 days): <?php echo $views_after_recommendations_7_days . "/" . $nrecommendations_7_days . ($nrecommendations_7_days > 0 ? " (" . round(100 * $views_after_recommendations_7_days / $nrecommendations_7_days, 2) . "%)" : ""); ?><br>
                    Views after Recs (30 days): <?php echo $views_after_recommendations_30_days . "/" . $nrecommendations_30_days . ($nrecommendations_30_days > 0 ? " (" . round(100 * $views_after_recommendations_30_days / $nrecommendations_30_days, 2) . "%)" : ""); ?><br>
                    Views after Recs (total): <?php echo $views_after_recommendations . "/" . $nrecommendations . ($nrecommendations > 0 ? " (" . round(100 * $views_after_recommendations / $nrecommendations, 2) . "%)" : ""); ?><br>
                    Start Activity Date: <?php echo $min_timestamp; ?><br>
                    Last Activity Date: <?php echo $max_timestamp; ?><br>
                    Days: <?php echo intval(round((strtotime($max_timestamp) - strtotime($min_timestamp)) / (3600 * 24), 2)); ?><br>
                    Active Days: <?php echo $active_days; ?><br>
                </p>
            </div>
            <div id="recommendations_all" style="height: 800px"></div>
            <div id="recommendations_citizen" style="height: 800px"></div>
            <div id="recommendations_commuter" style="height: 800px"></div>
            <div id="recommendations_student" style="height: 800px"></div>
            <div id="recommendations_tourist" style="height: 800px"></div>

            <div id="views_all" style="height: 800px"></div>
            <div id="views_citizen" style="height: 800px"></div>
            <div id="views_commuter" style="height: 800px"></div>
            <div id="views_student" style="height: 800px"></div>
            <div id="views_tourist" style="height: 800px"></div>

            <div id="timeSlots_all" style="height: 800px"></div>
            <div id="timeSlots_citizen" style="height: 800px"></div>
            <div id="timeSlots_commuter" style="height: 800px"></div>
            <div id="timeSlots_student" style="height: 800px"></div>
            <div id="timeSlots_tourist" style="height: 800px"></div>

            <div id="queries_all" style="height: 400px"></div>
            <div id="queries_citizen" style="height: 400px"></div>
            <div id="queries_commuter" style="height: 400px"></div>
            <div id="queries_student" style="height: 400px"></div>
            <div id="queries_tourist" style="height: 400px"></div>

            <div id='activeUsers_all' style='height: 400px'></div>
            <div id='activeUsers_citizen' style='height: 400px'></div>
            <div id='activeUsers_commuter' style='height: 400px'></div>
            <div id='activeUsers_student' style='height: 400px'></div>
            <div id='activeUsers_tourist' style='height: 400px'></div>

            <div id='speed_all' style='height: 400px'></div>
            <div id='speed_citizen' style='height: 400px'></div>
            <div id='speed_commuter' style='height: 400px'></div>
            <div id='speed_student' style='height: 400px'></div>
            <div id='speed_tourist' style='height: 400px'></div>
        </div>

        <script type="text/javascript">
            $(function () {
                // create the charts
                $('#recommendations_all').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $recommendations_all_json; ?>')
                });
                $('#recommendations_citizen').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $recommendations_citizen_json; ?>')
                });
                $('#recommendations_commuter').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $recommendations_commuter_json; ?>')
                });
                $('#recommendations_student').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $recommendations_student_json; ?>')
                });
                $('#recommendations_tourist').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $recommendations_tourist_json; ?>')
                });
                $('#views_all').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $views_all_json; ?>')
                });
                $('#views_citizen').highcharts('StockChart', {
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
                                //return Highcharts.dateFormat('%a %d %b %H:%M:%S', this.value);
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
                    series: JSON.parse('<?php echo $views_citizen_json; ?>')
                });
                $('#views_commuter').highcharts('StockChart', {
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
                                //return Highcharts.dateFormat('%a %d %b %H:%M:%S', this.value);
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
                    series: JSON.parse('<?php echo $views_commuter_json; ?>')
                });
                $('#views_student').highcharts('StockChart', {
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
                                //return Highcharts.dateFormat('%a %d %b %H:%M:%S', this.value);
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
                    series: JSON.parse('<?php echo $views_student_json; ?>')
                });
                $('#views_tourist').highcharts('StockChart', {
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
                                //return Highcharts.dateFormat('%a %d %b %H:%M:%S', this.value);
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
                    series: JSON.parse('<?php echo $views_tourist_json; ?>')
                });
                $('#timeSlots_all').highcharts({
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
                    series: JSON.parse('<?php echo $timeslots_all_json; ?>')
                });
                $('#timeSlots_citizen').highcharts({
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
                    series: JSON.parse('<?php echo $timeslots_citizen_json; ?>')
                });
                $('#timeSlots_commuter').highcharts({
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
                    series: JSON.parse('<?php echo $timeslots_commuter_json; ?>')
                });
                $('#timeSlots_student').highcharts({
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
                    series: JSON.parse('<?php echo $timeslots_student_json; ?>')
                });
                $('#timeSlots_tourist').highcharts({
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
                    series: JSON.parse('<?php echo $timeslots_tourist_json; ?>')
                });
                $('#queries_all').highcharts({
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [<?php echo $keys_all_s; ?>]
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return this.y + "<br>(" + Highcharts.numberFormat(100 * this.y / <?php echo $total_all; ?>, 0) + "%)";
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
                            data: JSON.parse('<?php echo $menu_all_json; ?>')
                        }]
                });
                $('#queries_citizen').highcharts({
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [<?php echo $keys_citizen_s; ?>]
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return this.y + "<br>(" + Highcharts.numberFormat(100 * this.y / <?php echo $total_citizen; ?>, 0) + "%)";
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
                            name: 'citizen',
                            data: JSON.parse('<?php echo $menu_citizen_json; ?>')
                        }]
                });
                $('#queries_commuter').highcharts({
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [<?php echo $keys_commuter_s; ?>]
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return this.y + "<br>(" + Highcharts.numberFormat(100 * this.y / <?php echo $total_commuter; ?>, 0) + "%)";
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
                            name: 'commuter',
                            data: JSON.parse('<?php echo $menu_commuter_json; ?>')
                        }]
                });
                $('#queries_student').highcharts({
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [<?php echo $keys_student_s; ?>]
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return this.y + "<br>(" + Highcharts.numberFormat(100 * this.y / <?php echo $total_student; ?>, 0) + "%)";
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
                            name: 'student',
                            data: JSON.parse('<?php echo $menu_student_json; ?>')
                        }]
                });
                $('#queries_tourist').highcharts({
                    chart: {
                        type: 'column',
                    },
                    xAxis: {
                        categories: [<?php echo $keys_tourist_s; ?>]
                    },
                    plotOptions: {
                        column: {
                            dataLabels: {
                                enabled: true,
                                formatter: function () {
                                    return this.y + "<br>(" + Highcharts.numberFormat(100 * this.y / <?php echo $total_tourist; ?>, 0) + "%)";
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
                            name: 'tourist',
                            data: JSON.parse('<?php echo $menu_tourist_json; ?>')
                        }]
                });
                $('#activeUsers_all').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $active_users_all_json; ?>')
                });
                $('#activeUsers_citizen').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $active_users_citizen_json; ?>')
                });
                $('#activeUsers_commuter').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $active_users_commuter_json; ?>')
                });
                $('#activeUsers_student').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $active_users_student_json; ?>')
                });
                $('#activeUsers_tourist').highcharts('StockChart', {
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
                    series: JSON.parse('<?php echo $active_users_tourist_json; ?>')
                });
                $('#speed_all').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $distance_all_keys; ?>')
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
                    series: JSON.parse('<?php echo $distance_all; ?>')
                });
                $('#speed_citizen').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $distance_citizen_keys; ?>')
                    },
                    yAxis: {title: {text: 'Distance (km)'}},
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Speed/Distance'
                    },
                    series: JSON.parse('<?php echo $distance_citizen; ?>')
                });
                $('#speed_commuter').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $distance_commuter_keys; ?>')
                    },
                    yAxis: {title: {text: 'Distance (km)'}},
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Speed/Distance'
                    },
                    series: JSON.parse('<?php echo $distance_commuter; ?>')
                });
                $('#speed_student').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $distance_student_keys; ?>')
                    },
                    yAxis: {title: {text: 'Distance (km)'}},
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Speed/Distance'
                    },
                    series: JSON.parse('<?php echo $distance_student; ?>')
                });
                $('#speed_tourist').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    xAxis: {
                        title: {
                            text: 'Speed (km/h)'
                        },
                        categories: JSON.parse('<?php echo $distance_tourist_keys; ?>')
                    },
                    yAxis: {title: {text: 'Distance (km)'}},
                    rangeSelector: {
                        selected: 0
                    },
                    title: {
                        text: 'Speed/Distance'
                    },
                    series: JSON.parse('<?php echo $distance_tourist; ?>')
                });
            });
        </script>
    </body>