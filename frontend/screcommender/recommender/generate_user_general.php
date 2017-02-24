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
function getUserTimeSlotsJSON($link) {
    $result = array();
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT h,
        (
        SELECT COUNT(*)
        FROM  recommender.recommendations_log
        WHERE user = '" . $_REQUEST["user"] . "' AND HOUR(TIMESTAMP) = h
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
    } else {
        
    }
    $result = mysqli_query($link, getTimeSlotsQuery("all")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_all[] = intval($row["n"]);
    }
    $result = mysqli_query($link, getTimeSlotsQuery("citizen")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_citizen[] = intval($row["n"]);
    }
    $result = mysqli_query($link, getTimeSlotsQuery("commuter")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_commuter[] = intval($row["n"]);
    }
    $result = mysqli_query($link, getTimeSlotsQuery("student")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_student[] = intval($row["n"]);
    }
    $result = mysqli_query($link, getTimeSlotsQuery("tourist")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_tourist[] = intval($row["n"]);
    }
    $result = mysqli_query($link, getTimeSlotsQuery("disabled")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_disabled[] = intval($row["n"]);
    }
    $result = mysqli_query($link, getTimeSlotsQuery("operator")) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_operator[] = intval($row["n"]);
    }
    return json_encode(array(array("name" => "all", "data" => $json_all),
        array("name" => "citizen", "data" => $json_citizen),
        array("name" => "commuter", "data" => $json_commuter),
        array("name" => "student", "data" => $json_student),
        array("name" => "tourist", "data" => $json_tourist),
        array("name" => "disabled", "data" => $json_disabled),
        array("name" => "operator", "data" => $json_operator)));
}

// get sql for timeslots
function getTimeSlotsQuery($profile) {
    return "SELECT h,
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
}

// calculate the number of recommendations in the last n days
function getRecommendations($link, $days) {
    $total = array();
    if ($days == 0) {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "'";
        } else {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL";
        }
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        } else {
            $sql = "SELECT SUM(nrecommendations) AS nrecommendations, SUM(nrecommendations_weather) AS nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE  b.label IS NULL AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        }
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $total = array(intval($row["nrecommendations"]), intval($row["nrecommendations_weather"]));
    }
    return $total;
}

// get the number of recommendations (JSON)
function getRecommendationsJSONOld($link) {
    $json = array();
    $json_weather = array();
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log WHERE user = '" . $_REQUEST["user"] . "' ORDER BY timestamp ASC";
    } else {
        $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL ORDER BY timestamp ASC";
    }
    //$dataGrouping = array("units" => array(array("day", array(1)), array("month", array(1, 2, 3, 4, 6))));
    $dataGrouping = array("units" => array(array("day", array(1))));
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
        $json_weather[] = array(intval($row["timestamp"]), intval($row['nrecommendations_weather']));
    }
    return json_encode(array(array("type" => "column", "name" => "recommendations", "dataGrouping" => $dataGrouping, "data" => $json), array("type" => "column", "name" => "weather", "dataGrouping" => $dataGrouping, "data" => $json_weather)));
}

// get the number of recommendations (JSON)
function getRecommendationsJSON($link) {
    $json = array();
    $json_weather = array();
    $dataGrouping = array("units" => array(array("day", array(1))));
    // all
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'all' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_all[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    // citizen
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'citizen' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_citizen[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    // commuter
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'commuter' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_commuter[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    // student
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'student' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_student[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    // tourist
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'tourist' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_tourist[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    // disabled
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'disabled' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_disabled[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    // operator
    $sql = "SELECT UNIX_TIMESTAMP(timestamp) * 1000 AS timestamp, nrecommendations_total, nrecommendations_weather FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'operator' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json_operator[] = array(intval($row["timestamp"]), intval($row['nrecommendations_total']));
    }
    $result = array();
    if (isset($json_all)) {
        $result[] = array("type" => "column", "name" => "all", "dataGrouping" => $dataGrouping, "data" => $json_all);
    }
    if (isset($json_citizen)) {
        $result[] = array("type" => "column", "name" => "citizen", "dataGrouping" => $dataGrouping, "data" => $json_citizen);
    }
    if (isset($json_commuter)) {
        $result[] = array("type" => "column", "name" => "commuter", "dataGrouping" => $dataGrouping, "data" => $json_commuter);
    }
    if (isset($json_student)) {
        $result[] = array("type" => "column", "name" => "student", "dataGrouping" => $dataGrouping, "data" => $json_student);
    }
    if (isset($json_tourist)) {
        $result[] = array("type" => "column", "name" => "tourist", "dataGrouping" => $dataGrouping, "data" => $json_tourist);
    }
    if (isset($json_disabled)) {
        $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => $json_disabled);
    } else if (!isset($json_disabled)) {
        $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => []);
    }
    if (isset($json_operator)) {
        $result[] = array("type" => "column", "name" => "operator", "dataGrouping" => $dataGrouping, "data" => $json_operator);
    }
    return json_encode($result);
}

// get the top 30 menu count (JSON)
function getQueriesJSONOld($link) {
    $json = array();
    $keys = array();
    $sql = "SELECT categories FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND mode = 'api-services-by-gps' AND b.label IS NULL ORDER BY timestamp ASC";
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
    $total = 0;
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

// get the top 30 menu count (JSON)
function getQueriesJSON() {
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $result = array();
    $categories = array();
    $categories_values = array();
    $categories_filtered = array();
    $data = array();
    $total = 0;
    foreach ($profiles as $profile) {
        $json = json_decode(file_get_contents("./json/menu_keys_total_" . $profile . ".json"), true);
        $values = json_decode($json[0]);
        $keys = $json[1];
        $total += $json[2];
        for ($i = 0; $i < count($keys); $i++) {
            if (!in_array($keys[$i], $categories)) {
                $categories[] = $keys[$i];
            }
            $data[$profile][$keys[$i]] = $values[$i];
            $categories_values[$keys[$i]] = isset($categories_values[$keys[$i]]) ? $categories_values[$keys[$i]] + $values[$i] : $values[$i];
        }
    }
    // sort the top 30 categories
    arsort($categories_values);
    $t = 0;
    $counter = 1;
    $json = array();
    foreach ($profiles as $profile) {
        foreach ($categories_values as $category => $value) {
            if (!in_array($category, $categories_filtered)) {
                $categories_filtered[] = $category;
            }
            if (!isset($data[$profile][$category])) {
                $json[$profile][] = 0;
            } else {
                $json[$profile][] = $data[$profile][$category];
            }
            $t += $value;
            if ($counter == 30) {
                $counter = 1;
                break;
            }
            $counter++;
        }
    }
    $result = array();
    if (isset($json["all"])) {
        $result[] = array("type" => "column", "name" => "all", "data" => $json["all"]);
    }
    if (isset($json["citizen"])) {
        $result[] = array("type" => "column", "name" => "citizen", "data" => $json["citizen"]);
    }
    if (isset($json["commuter"])) {
        $result[] = array("type" => "column", "name" => "commuter", "data" => $json["commuter"]);
    }
    if (isset($json["student"])) {
        $result[] = array("type" => "column", "name" => "student", "data" => $json["student"]);
    }
    if (isset($json["tourist"])) {
        $result[] = array("type" => "column", "name" => "tourist", "data" => $json["tourist"]);
    }
    if (isset($json["disabled"])) {
        $result[] = array("type" => "column", "name" => "disabled", "data" => $json["disabled"]);
    }
    if (isset($json["operator"])) {
        $result[] = array("type" => "column", "name" => "operator", "data" => $json["operator"]);
    }
    return array(json_encode($result), $categories_filtered, $t);
}

// get the menu count (JSON)
function getViewsJSONOld() {
    global $config;
    //CONNECT
    $link = mysqli_connect($config['access_log_host'], $config['access_log_user'], $config['access_log_pass'], $config['access_log_database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $json = array();
    if (isset($_REQUEST["user"])) {
        $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM ServiceMap.AccessLog WHERE uid = '" . $_REQUEST["user"] . "' AND categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' GROUP BY DATE(timestamp) ORDER BY id ASC";
    } else {
        $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    }
    $categories = array();
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $json[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    //close connection
    mysqli_close($link);
    return json_encode($json);
}

// get the menu count (JSON)
function getViewsJSON($link) {
    $dataGrouping = array("units" => array(array("day", array(1))));
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'all' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_all[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'citizen' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_citizen[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'commuter' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_commuter[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'student' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_student[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'tourist' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_tourist[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'disabled' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_disabled[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.AccessLog a LEFT JOIN recommender.users b ON a.uid = b.user WHERE categories IS NOT NULL AND serviceURI LIKE 'http%' AND mode = 'api-service-info' AND b.label IS NULL AND profile = 'operator' GROUP BY DATE(timestamp) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_operator[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $result = array();
    if (isset($json_all)) {
        $result[] = array("type" => "column", "name" => "all", "dataGrouping" => $dataGrouping, "data" => $json_all);
    }
    if (isset($json_citizen)) {
        $result[] = array("type" => "column", "name" => "citizen", "dataGrouping" => $dataGrouping, "data" => $json_citizen);
    }
    if (isset($json_commuter)) {
        $result[] = array("type" => "column", "name" => "commuter", "dataGrouping" => $dataGrouping, "data" => $json_commuter);
    }
    if (isset($json_student)) {
        $result[] = array("type" => "column", "name" => "student", "dataGrouping" => $dataGrouping, "data" => $json_student);
    }
    if (isset($json_tourist)) {
        $result[] = array("type" => "column", "name" => "tourist", "dataGrouping" => $dataGrouping, "data" => $json_tourist);
    }
    if (isset($json_disabled)) {
        $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => $json_disabled);
    } else if (!isset($json_disabled)) {
        $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => []);
    }
    if (isset($json_operator)) {
        $result[] = array("type" => "column", "name" => "operator", "dataGrouping" => $dataGrouping, "data" => $json_operator);
    }
    return json_encode($result);
}

function getGroups($link) {
    $groups = array();
    $sql = "SELECT `group` FROM recommender.groups ORDER BY `group` ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row["group"];
    }
    return $groups;
}

// calculate the number of views after recommendations in the last n days
function getViews_after_recommendations($link, $days) {
    $num = 0;
    if ($days == 0) {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(user)) AS user FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "'";
        } else {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL";
        }
    } else {
        if (isset($_REQUEST["user"])) {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(user)) AS user FROM recommender.recommendations_stats WHERE user = '" . $_REQUEST["user"] . "' AND viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY)";
        } else {
            $sql = "SELECT COUNT(*) AS num, COUNT(DISTINCT(a.user)) AS user FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE viewedAt > DATE(NOW() - INTERVAL " . $days . " DAY) AND b.label IS NULL";
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
        $sql = "SELECT count(distinct(date(timestamp))) AS active_days, min(timestamp) AS min_timestamp, max(timestamp) AS max_timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL";
    }
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamp = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamp = array($row["min_timestamp"], $row["max_timestamp"], $row["active_days"]);
    }
    return $timestamp;
}

// get speed distance for profiles (JSON) generated by generate_user_profile.php
function getSpeedDistanceJSON($link) {
    $distance = array();
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $keys = array();
    $result = array();
    foreach ($profiles as $profile) {
        $keys_dst = json_decode(file_get_contents("./json/distance_" . $profile . ".json"), true);
        $keys = $keys_dst[0];
        $json = json_decode($keys_dst[1], true);
        $result[] = $json[0];
    }
    return array($keys, json_encode($result));
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

function getActiveUsers($link, $days) {
    if ($days == 0) {
        $dataGrouping = array("units" => array(array("day", array(1))));
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'all' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_all[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'citizen' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_citizen[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'commuter' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_commuter[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'student' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_student[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'tourist' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_tourist[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'disabled' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_disabled[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num, UNIX_TIMESTAMP(date(timestamp)) * 1000 AS timestamp FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND a.profile = 'operator' GROUP BY date(timestamp) ORDER BY date(timestamp) ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data_operator[] = array(intval($row["timestamp"]), intval($row["num"]));
        }
        $result = array();
        if (isset($data_all)) {
            $result[] = array("type" => "column", "name" => "all", "dataGrouping" => $dataGrouping, "data" => $data_all);
        }
        if (isset($data_citizen)) {
            $result[] = array("type" => "column", "name" => "citizen", "dataGrouping" => $dataGrouping, "data" => $data_citizen);
        }
        if (isset($data_commuter)) {
            $result[] = array("type" => "column", "name" => "commuter", "dataGrouping" => $dataGrouping, "data" => $data_commuter);
        }
        if (isset($data_student)) {
            $result[] = array("type" => "column", "name" => "student", "dataGrouping" => $dataGrouping, "data" => $data_student);
        }
        if (isset($data_tourist)) {
            $result[] = array("type" => "column", "name" => "tourist", "dataGrouping" => $dataGrouping, "data" => $data_tourist);
        }
        if (isset($data_disabled)) {
            $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => $data_disabled);
        } else if (!isset($data_disabled)) {
            $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => []);
        }
        if (isset($data_operator)) {
            $result[] = array("type" => "column", "name" => "operator", "dataGrouping" => $dataGrouping, "data" => $data_operator);
        }
        return json_encode($result);
    } else if ($days > 0) {
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND timestamp > NOW() - INTERVAL " . $days . " DAY";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        $data = array();
        while ($row = mysqli_fetch_assoc($result)) {
            $num = $row["num"];
        }
        return $num;
    } else {
        $sql = "SELECT COUNT(DISTINCT(a.user)) AS num FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL";
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
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $r = array();
    foreach ($profiles as $profile) {
        $groups = array();
        $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT requestedGroup, COUNT(*) AS num FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE requestedGroup != '' AND a.profile = '" . $profile . "' AND b.label IS NULL GROUP BY requestedGroup ORDER BY requestedGroup ASC) AS n ON c.group = n.requestedGroup ORDER BY c.group ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $groups[] = array(intval($row["num"]));
        }
        $r[] = array("type" => "column", "name" => $profile, "data" => $groups);
    }
    return json_encode($r);
}

// get the views after recommendations (JSON)
function getViewedAfter($link) {
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $r = array();
    foreach ($profiles as $profile) {
        $data = array();
        $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT b.group, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.categories_groups b ON a.macroclass = b.key LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $profile . "' AND b.group != 'Twitter1' AND b.group != 'Twitter2' AND b.group != 'Twitter3' AND d.label IS NULL GROUP BY b.group UNION SELECT `group`, COUNT(*) AS num FROM recommender.tweets_log i LEFT JOIN recommender.users l ON i.user = l.user WHERE l.profile = '" . $profile . "' AND l.label IS NULL GROUP BY `group`) AS n ON c.group = n.group ORDER BY c.group ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = array(intval($row["num"]));
        }
        $r[] = array("name" => $profile, "data" => $data);
    }
    return json_encode($r);
}

// get the views after recommendations timeline (JSON)
function getViewsAfterRecommendationsJSON($link) {
    $dataGrouping = array("units" => array(array("day", array(1))));
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'all' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_all[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'citizen' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_citizen[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'commuter' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_commuter[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'student' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_student[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'tourist' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_tourist[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'disabled' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_disabled[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $sql = "SELECT UNIX_TIMESTAMP(date(viewedAt)) * 1000 AS timestamp, COUNT(*) AS num FROM recommender.recommendations_stats a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL AND b.profile = 'operator' GROUP BY DATE(viewedAt) ORDER BY a.id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error($link));
    while ($row = mysqli_fetch_assoc($result)) {
        $json_operator[] = array(intval($row["timestamp"]), intval($row["num"]));
    }
    $result = array();
    if (isset($json_all)) {
        $result[] = array("type" => "column", "name" => "all", "dataGrouping" => $dataGrouping, "data" => $json_all);
    }
    if (isset($json_citizen)) {
        $result[] = array("type" => "column", "name" => "citizen", "dataGrouping" => $dataGrouping, "data" => $json_citizen);
    }
    if (isset($json_commuter)) {
        $result[] = array("type" => "column", "name" => "commuter", "dataGrouping" => $dataGrouping, "data" => $json_commuter);
    }
    if (isset($json_student)) {
        $result[] = array("type" => "column", "name" => "student", "dataGrouping" => $dataGrouping, "data" => $json_student);
    }
    if (isset($json_tourist)) {
        $result[] = array("type" => "column", "name" => "tourist", "dataGrouping" => $dataGrouping, "data" => $json_tourist);
    }
    if (isset($json_disabled)) {
        $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => $json_disabled);
    } else if (!isset($json_disabled)) {
        $result[] = array("type" => "column", "name" => "disabled", "dataGrouping" => $dataGrouping, "data" => []);
    }
    if (isset($json_operator)) {
        $result[] = array("type" => "column", "name" => "operator", "dataGrouping" => $dataGrouping, "data" => $json_operator);
    }
    return json_encode($result);
}

function getDisliked($link) {
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $r = array();
    foreach ($profiles as $profile) {
        $data = array();
        $sql = "SELECT c.group, IF(n.num is null, 0, n.num) AS num FROM recommender.groups c LEFT JOIN (SELECT c.group, COUNT(*) AS num FROM recommender.dislike a LEFT JOIN recommender.groups c ON a.dislikedGroup = c.group LEFT JOIN recommender.users d ON a.user = d.user WHERE d.profile = '" . $profile . "' AND c.group IS NOT NULL AND d.label IS NULL) AS n ON c.group = n.group ORDER BY c.group ASC";
        $result = mysqli_query($link, $sql) or die(mysqli_error());
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = array(intval($row["num"]));
        }
        $r[] = array("name" => $profile, "data" => $data);
    }
    return json_encode($r);
}

function getZonesFlowsOldOld($link) {
    $sql = "SELECT value FROM recommender.settings WHERE name='zones_square_size'";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $value = doubleval($row["value"]);
        $earthRadius = 6371000; // m
        $zones_square_size = (1 / ($value * 180 / (M_PI * $earthRadius)));
    }
    $sql = "SELECT location, COUNT(*) AS num FROM(SELECT CONCAT(round(latitude * " . $zones_square_size . "), '_', round(longitude * " . $zones_square_size . ")) AS location FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL) AS a GROUP BY location ORDER BY num DESC";
    echo $sql;
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

function getZonesFlowsOld($link) {
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
 WHERE b.label IS NULL AND mode = 'gps') AS a GROUP BY x, y ORDER BY num DESC LIMIT 50";
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

function getZonesFlows() {
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $result = array();
    $zones = array();
    $zones_values = array();
    $zones_filtered = array();
    $bounding_filtered = array();
    $data = array();
    foreach ($profiles as $profile) {
        $json = json_decode(file_get_contents("./json/zones_flows_keys_" . $profile . ".json"), true);
        $keys = json_decode($json[0]);
        $values = json_decode($json[1], true);
        $values = $values[0];
        $values = $values["data"];
        $bounding = $json[2];
        for ($i = 0; $i < count($keys); $i++) {
            if (!in_array($keys[$i], $zones)) {
                $zones[] = $keys[$i];
                $bounding_filtered[$keys[$i]] = $bounding[$keys[$i]];
            }
            $data[$profile][$keys[$i]] = $values[$i];
            $zones_values[$keys[$i]] = isset($zones_values[$keys[$i]]) ? $zones_values[$keys[$i]] + $values[$i] : $values[$i];
        }
    }
    // sort the top 50 zones
    arsort($zones_values);
    $counter = 1;
    $json = array();
    foreach ($profiles as $profile) {
        foreach ($zones_values as $zone => $value) {
            if (!in_array($zone, $zones_filtered)) {
                $zones_filtered[] = $zone;
            }
            if (!isset($data[$profile][$zone])) {
                $json[$profile][] = 0;
            } else {
                $json[$profile][] = $data[$profile][$zone];
            }
            if ($counter == 50) {
                $counter = 1;
                break;
            }
            $counter++;
        }
    }
    $result = array();
    if (isset($json["all"])) {
        $result[] = array("type" => "column", "name" => "all", "data" => $json["all"]);
    }
    if (isset($json["citizen"])) {
        $result[] = array("type" => "column", "name" => "citizen", "data" => $json["citizen"]);
    }
    if (isset($json["commuter"])) {
        $result[] = array("type" => "column", "name" => "commuter", "data" => $json["commuter"]);
    }
    if (isset($json["student"])) {
        $result[] = array("type" => "column", "name" => "student", "data" => $json["student"]);
    }
    if (isset($json["tourist"])) {
        $result[] = array("type" => "column", "name" => "tourist", "data" => $json["tourist"]);
    }
    if (isset($json["disabled"])) {
        $result[] = array("type" => "column", "name" => "disabled", "data" => $json["disabled"]);
    }
    if (isset($json["operator"])) {
        $result[] = array("type" => "column", "name" => "operator", "data" => $json["operator"]);
    }
    return array(json_encode($zones_filtered), json_encode($result), $bounding_filtered);
}

function getZonesFlowsUsersOld($link) {
    $sql = "SELECT 
(x-138/2)/6371000*180/PI() AS lon_bl,
(2*atan(exp((y-138/2)/6371000))-PI()/2)*180/PI() AS lat_bl,
x/6371000*180/PI() AS lon,
(2*atan(exp(y/6371000))-PI()/2)*180/PI() AS lat, 
(x+138/2)/6371000*180/PI() AS lon_tr,
(2*atan(exp((y+138/2)/6371000))-PI()/2)*180/PI() AS lat_tr,
COUNT(DISTINCT(a.user)) AS num
FROM
(SELECT a.user,round(longitude/180*PI()* 6371000 / 138)*138 AS x, 
round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user
 WHERE b.label IS NULL AND mode = 'gps') AS a GROUP BY x, y ORDER BY num DESC LIMIT 50";
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

function getZonesFlowsUsers() {
    $profiles = array("all", "citizen", "commuter", "student", "tourist", "disabled", "operator");
    $result = array();
    $zones = array();
    $zones_values = array();
    $zones_filtered = array();
    $bounding_filtered = array();
    $data = array();
    foreach ($profiles as $profile) {
        $json = json_decode(file_get_contents("./json/zones_flows_users_keys_" . $profile . ".json"), true);
        $keys = json_decode($json[0]);
        $values = json_decode($json[1], true);
        $values = $values[0];
        $values = $values["data"];
        $bounding = $json[2];
        for ($i = 0; $i < count($keys); $i++) {
            if (!in_array($keys[$i], $zones)) {
                $zones[] = $keys[$i];
                $bounding_filtered[$keys[$i]] = $bounding[$keys[$i]];
            }
            $data[$profile][$keys[$i]] = $values[$i];
            $zones_values[$keys[$i]] = isset($zones_values[$keys[$i]]) ? $zones_values[$keys[$i]] + $values[$i] : $values[$i];
        }
    }
    // sort the top 50 zones
    arsort($zones_values);
    $counter = 1;
    $json = array();
    foreach ($profiles as $profile) {
        foreach ($zones_values as $zone => $value) {
            if (!in_array($zone, $zones_filtered)) {
                $zones_filtered[] = $zone;
            }
            if (!isset($data[$profile][$zone])) {
                $json[$profile][] = 0;
            } else {
                $json[$profile][] = $data[$profile][$zone];
            }
            if ($counter == 50) {
                $counter = 1;
                break;
            }
            $counter++;
        }
    }
    $result = array();
    if (isset($json["all"])) {
        $result[] = array("type" => "column", "name" => "all", "data" => $json["all"]);
    }
    if (isset($json["citizen"])) {
        $result[] = array("type" => "column", "name" => "citizen", "data" => $json["citizen"]);
    }
    if (isset($json["commuter"])) {
        $result[] = array("type" => "column", "name" => "commuter", "data" => $json["commuter"]);
    }
    if (isset($json["student"])) {
        $result[] = array("type" => "column", "name" => "student", "data" => $json["student"]);
    }
    if (isset($json["tourist"])) {
        $result[] = array("type" => "column", "name" => "tourist", "data" => $json["tourist"]);
    }
    if (isset($json["disabled"])) {
        $result[] = array("type" => "column", "name" => "disabled", "data" => $json["disabled"]);
    }
    if (isset($json["operator"])) {
        $result[] = array("type" => "column", "name" => "operator", "data" => $json["operator"]);
    }
    return array(json_encode($zones_filtered), json_encode($result), $bounding_filtered);
}

// get the top services exploited (viewed) by users
function getTopServices($link) {
    
}

include_once "settings.php"; // settings
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

// get recommendations json and save to file
$recommendations_json = getRecommendationsJSON($link);
file_put_contents("./json/recommendations.json", $recommendations_json);

// get time slots json
$timeslots_json = getUserTimeSlotsJSON($link);
file_put_contents("./json/timeslots.json", $timeslots_json);

// get number of recommendations in the last 24 h and save to file
$nrecommendations_1_day = getRecommendations($link, 1);
file_put_contents("./json/nrecommendations24h.json", json_encode($nrecommendations_1_day));

// get number of recommendations in the last 7 days and save to file
$nrecommendations_7_days = getRecommendations($link, 7);
file_put_contents("./json/nrecommendations7days.json", json_encode($nrecommendations_7_days));

// get number of recommendations in the last 30 days and save to file
$nrecommendations_30_days = getRecommendations($link, 30);
file_put_contents("./json/nrecommendations30days.json", json_encode($nrecommendations_30_days));

// get total number of recommendations and save to file
$nrecommendations = getRecommendations($link, 0);
file_put_contents("./json/nrecommendationsTotal.json", json_encode($nrecommendations));

// get number of views after recommendations in the last 24 h and save to file
$views_after_recommendations_1_day = getViews_after_recommendations($link, 1);
file_put_contents("./json/views_after_recommendations_1_day.json", json_encode($views_after_recommendations_1_day));
// get number of view after recommendations in the last 7 days and save to file
$views_after_recommendations_7_days = getViews_after_recommendations($link, 7);
file_put_contents("./json/views_after_recommendations_7_days.json", json_encode($views_after_recommendations_7_days));
// get number of view after recommendations in the last 30 days and save to file
$views_after_recommendations_30_days = getViews_after_recommendations($link, 30);
file_put_contents("./json/views_after_recommendations_30_days.json", json_encode($views_after_recommendations_30_days));
// get totatl number of view after recommendations and save to file
$views_after_recommendations = getViews_after_recommendations($link, 0);
file_put_contents("./json/views_after_recommendations.json", json_encode($views_after_recommendations));

// get viewed after recommendation and save to file
$views_after_rec = getViewedAfter($link);
file_put_contents("./json/views_after_rec.json", $views_after_rec);

// get the number of views after recommendations timeline json and save to file
$views_after_recommendations_graph = getViewsAfterRecommendationsJSON($link);
file_put_contents("./json/views_after_recommendations_graph.json", json_encode($views_after_recommendations_graph));

// get queries json and save to file
$menu_json_keys_total = getQueriesJSON();
file_put_contents("./json/menu_keys_total.json", json_encode($menu_json_keys_total));

// get views json and save to file
$views_json = getViewsJSON($link);
file_put_contents("./json/views.json", $views_json);

// get min and max timestamp and save to file
$min_max_timestamp_active_days = getStartEndDateActiveDays($link);
file_put_contents("./json/min_max_timestamp_active_days.json", json_encode($min_max_timestamp_active_days));

// get speed distance, this metrics are generated by generate_user_profile.php for each profile
$distance = getSpeedDistanceJSON($link);
file_put_contents("./json/distance.json", json_encode($distance));

// get active users json (total) and save to file
$active_users_total_n_json = getActiveUsers($link, 0);
file_put_contents("./json/active_users_total_n.json", $active_users_total_n_json);
// get active users (total) and save to file
$active_users_total = getActiveUsers($link, -1);
file_put_contents("./json/active_users_total.json", $active_users_total);
// get active users (last 24 h) and save to file
$active_users_last_day = getActiveUsers($link, 1);
file_put_contents("./json/active_users_last_day.json", $active_users_last_day);
// get active users (last 7 days) and save to file
$active_users_last_7_days = getActiveUsers($link, 7);
file_put_contents("./json/active_users_last_7_days.json", $active_users_last_7_days);
// get active users (last 30 days) and save to file
$active_users_last_30_days = getActiveUsers($link, 30);
file_put_contents("./json/active_users_last_30_days.json", $active_users_last_30_days);

// get groups and save to file
$groups_keys = json_encode(getGroups($link));
file_put_contents("./json/groups_keys.json", $groups_keys);

// get requested groups and save to file
$requested_groups = getRequestedGroups($link);
file_put_contents("./json/requested_groups.json", $requested_groups);

// get disliked and save to file
$disliked = getDisliked($link);
file_put_contents("./json/disliked.json", $disliked);

// get zones flows and save to file
$zones_flows_keys = getZonesFlows();
file_put_contents("./json/zones_flows_keys.json", json_encode($zones_flows_keys));

// get zones flows and save to file
$zones_flows_users_keys = getZonesFlowsUsers();
file_put_contents("./json/zones_flows_users_keys.json", json_encode($zones_flows_users_keys));

//close connection
mysqli_close($link);
?>