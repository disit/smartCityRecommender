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
include_once "functions.php"; //function getBannedUsers
include_once "settings.php";
set_time_limit(9999999999);

function setStatus() {
    global $config;

    //CONNECT
    $link = mysqli_connect($config['sensors_host'], $config['sensors_user'], $config['sensors_pass'], $config['sensors_database']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }

    // load previous status if present (curr_status, cc_x, cc_y), give permission to write this file
    $data = file_get_contents("/var/www/html/screcommender/recommender/status.log");
    if ($data != null) {
        $status = json_decode($data, true);
    } else {
        $status = array();
    }

    // GET DATA
    $coordinates = array();
    $sql1 = "SELECT user_eval_id, device_id, UNIX_TIMESTAMP(date) AS date, cc_x, cc_y, latitude, longitude, curr_status FROM sensors.user_eval WHERE curr_status_new IS NULL AND device_id IS NOT NULL ORDER BY device_id, user_eval_id";
    $result = mysqli_query($link, $sql1) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        // set the user_eval_id for this row for this user
        $lastStatusRow[$row["device_id"]] = $row["user_eval_id"];
        // if we are in the same location cluster for the user
        if (isset($status[$row["device_id"]]) &&
                $status[$row["device_id"]]["cc_x"] == $row["cc_x"] &&
                $status[$row["device_id"]]["cc_y"] == $row["cc_y"]) {
            $status[$row["device_id"]]["seconds"] += intval($row["date"]) - $status[$row["device_id"]]["date"];
            if ($row["curr_status"] != "") {
                // update the status for the user with the current one
                $sql2 = "UPDATE sensors.user_eval SET curr_status_new = '" . $row["curr_status"] . "' WHERE user_eval_id = " . $row["user_eval_id"];
                mysqli_query($link, $sql2) or die(mysqli_error());
                // if this user status is different from the previous one or the previous state is different from "stay", set the elapsed time for the previous status
                if ($row["curr_status"] != $status[$row["device_id"]]["curr_status"] || $status[$row["device_id"]]["curr_status"] != "stay") {
                    $sql2 = "UPDATE sensors.user_eval SET curr_status_time_new = " . $status[$row["device_id"]]["seconds"] . " WHERE user_eval_id = " . $status[$row["device_id"]]["user_eval_id"];
                    mysqli_query($link, $sql2) or die(mysqli_error());
                    // if the previous status is "stay" then calculate the centroid
                    if ($status[$row["device_id"]]["curr_status"] == "stay") {
                        $centroid = getCentroid($status[$row["device_id"]]["centroid"]);
                        $sql2 = "UPDATE sensors.user_eval SET lat_centroid = " . $centroid[0] . ", lon_centroid = " . $centroid[1] . " WHERE user_eval_id = " . $status[$row["device_id"]]["user_eval_id"];
                        mysqli_query($link, $sql2) or die(mysqli_error());
                        unset($status[$row["device_id"]]["centroid"]);
                    }
                    $status[$row["device_id"]]["curr_status"] = $row["curr_status"];
                    $status[$row["device_id"]]["user_eval_id"] = $row["user_eval_id"];
                    $status[$row["device_id"]]["seconds"] = 0;
                }
                // if the status is "stay" then save the coordinate for centroid calculation
                if ($row["curr_status"] == "stay") {
                    $status[$row["device_id"]]["centroid"][] = array(doubleval($row["latitude"]), doubleval($row["longitude"]));
                }
            } else if ($status[$row["device_id"]]["curr_status"] == "stay") {
                // update the status for the user with the previous one
                $sql2 = "UPDATE sensors.user_eval SET curr_status_new = '" . $status[$row["device_id"]]["curr_status"] . "' WHERE user_eval_id = " . $row["user_eval_id"];
                mysqli_query($link, $sql2) or die(mysqli_error());
                // save the coordinate for centroid calculation
                $status[$row["device_id"]]["centroid"][] = array(doubleval($row["latitude"]), doubleval($row["longitude"]));
            } else {
                // update the status for the user with the current one
                $sql2 = "UPDATE sensors.user_eval SET curr_status_new = '" . $row["curr_status"] . "' WHERE user_eval_id = " . $row["user_eval_id"];
                mysqli_query($link, $sql2) or die(mysqli_error());
                // if this user status is different from the previous one, increment and set the elapsed time for the previous status
                if ($row["curr_status"] != $status[$row["device_id"]]["curr_status"] || $status[$row["device_id"]]["curr_status"] != "stay") {
                    $sql2 = "UPDATE sensors.user_eval SET curr_status_time_new = " . $status[$row["device_id"]]["seconds"] . " WHERE user_eval_id = " . $status[$row["device_id"]]["user_eval_id"];
                    mysqli_query($link, $sql2) or die(mysqli_error());
                    $status[$row["device_id"]]["curr_status"] = $row["curr_status"];
                    $status[$row["device_id"]]["user_eval_id"] = $row["user_eval_id"];
                    $status[$row["device_id"]]["seconds"] = 0;
                }
            }
            $status[$row["device_id"]]["date"] = intval($row["date"]);
        }
        // if we are not in the same location cluster for the user
        else {
            // update the status for the user with the current one
            $sql2 = "UPDATE sensors.user_eval SET curr_status_new = '" . $row["curr_status"] . "' WHERE user_eval_id = " . $row["user_eval_id"];
            mysqli_query($link, $sql2) or die(mysqli_error());
            if (isset($status[$row["device_id"]]["date"]) && isset($status[$row["device_id"]]["user_eval_id"])) {
                $status[$row["device_id"]]["seconds"] += intval($row["date"]) - $status[$row["device_id"]]["date"];
                $sql2 = "UPDATE sensors.user_eval SET curr_status_time_new = " . $status[$row["device_id"]]["seconds"] . " WHERE user_eval_id = " . $status[$row["device_id"]]["user_eval_id"];
                mysqli_query($link, $sql2) or die(mysqli_error());
                // if the previous status is "stay" then calculate the centroid
                if ($status[$row["device_id"]]["curr_status"] == "stay") {
                    $centroid = getCentroid($status[$row["device_id"]]["centroid"]);
                    $sql2 = "UPDATE sensors.user_eval SET lat_centroid = " . $centroid[0] . ", lon_centroid = " . $centroid[1] . " WHERE user_eval_id = " . $status[$row["device_id"]]["user_eval_id"];
                    mysqli_query($link, $sql2) or die(mysqli_error());
                    unset($status[$row["device_id"]]["centroid"]);
                }
            }
            // if the status is "stay" then save the coordinate for centroid calculation
            if ($row["curr_status"] == "stay") {
                $status[$row["device_id"]]["centroid"][] = array(doubleval($row["latitude"]), doubleval($row["longitude"]));
            }
            // update the status
            $status[$row["device_id"]]["curr_status"] = $row["curr_status"];
            $status[$row["device_id"]]["cc_x"] = $row["cc_x"];
            $status[$row["device_id"]]["cc_y"] = $row["cc_y"];
            $status[$row["device_id"]]["date"] = intval($row["date"]);
            $status[$row["device_id"]]["seconds"] = 0;
            $status[$row["device_id"]]["user_eval_id"] = $row["user_eval_id"];
        }
    }
    // update the last status row for each user
    $row_ids = "";
    $user_ids = "";
    foreach ($lastStatusRow as $key => $value) {
        $row_ids .= $lastStatusRow[$key] . ",";
        $user_ids .= "'" . $key . "',";
    }
    // start MySQL transaction
    if ($user_ids != "") {
        $sql = "START TRANSACTION";
        mysqli_query($link, $sql) or die(mysqli_error());
        $sql = "UPDATE sensors.user_eval SET last_status_row = NULL WHERE last_status_row = 1 AND device_id IN (" . substr($user_ids, 0, -1) . ")";
        mysqli_query($link, $sql) or die(mysqli_error());
        $sql = "UPDATE sensors.user_eval SET last_status_row = 1 WHERE user_eval_id IN (" . substr($row_ids, 0, -1) . ")";
        mysqli_query($link, $sql) or die(mysqli_error());
        // end MySQL transaction
        $sql = "COMMIT";
        mysqli_query($link, $sql) or die(mysqli_error());
    }
    //close connection
    mysqli_close($link);
    // save the status to file
    file_put_contents("/var/www/html/screcommender/recommender/status.log", json_encode($status));
}

// get centroid from arrays of coordinates
//http://www.geomidpoint.com/example.html
function getCentroid($coordinates) {
    if (count($coordinates) == 1) {
        return array($coordinates[0][0], $coordinates[0][1]);
    }

    $num = count($coordinates);
    $x = 0.0;
    $y = 0.0;
    $z = 0.0;

    foreach ($coordinates as $coordinate) {
        $lat = $coordinate[0] * pi() / 180;
        $lon = $coordinate[1] * pi() / 180;

        $a = cos($lat) * cos($lon);
        $b = cos($lat) * sin($lon);
        $c = sin($lat);

        $x += $a;
        $y += $b;
        $z += $c;
    }

    $x /= $num;
    $y /= $num;
    $z /= $num;

    $lon = atan2($y, $x);
    $hyp = sqrt($x * $x + $y * $y);
    $lat = atan2($z, $hyp);

    return array($lat * 180 / pi(), $lon * 180 / pi());
}

setStatus();
?>