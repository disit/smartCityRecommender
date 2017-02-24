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
include_once "settings.php";
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
if ($_REQUEST["profile"] != "null" && $_REQUEST["profile"] != "") {
    $sql = "SELECT trajectory FROM recommender.trajectories WHERE profile = '" . $_REQUEST["profile"] . "' AND cluster_id = " . $_REQUEST["id"];
} else {
    $sql = "SELECT trajectory FROM recommender.trajectories WHERE cluster_id = " . $_REQUEST["id"];
}

$result = mysqli_query($link, $sql) or die(mysqli_error());
while ($row = mysqli_fetch_assoc($result)) {
    $trajectory = split(";", $row["trajectory"]);
    $trajectory_javascript = "";
    foreach ($trajectory as $coordinates) {
        $coordinates_array = split(" ", $coordinates);
        $trajectory_javascript .= "new L.LatLng(" . $coordinates_array[0] . "," . $coordinates_array[1] . "),";
    }
    $javascript .= "new L.Polyline([" . substr($trajectory_javascript, 0, strlen($trajectory_javascript0) - 1) . "], {color: 'black', weight: 1, opacity: 1, smoothFactor: 1}),";
}
$javascript = "L.layerGroup([" . substr($javascript, 0, strlen($javascript) - 1) . "])";
mysqli_close($link);
echo $javascript;
?>