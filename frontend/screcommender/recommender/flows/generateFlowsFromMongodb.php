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
// calculate people flows from MongoDB and write json and csv files to be used by flows/index.php
// https://github.com/geodesign/spatialsankey
// https://github.com/ilyabo/jflowmap.js
// http://nssdc.gsfc.nasa.gov/planetary/factsheet/earthfact.html
// Earth Equatorial radius (km) 6378.137    
// Earth Polar radius (km) 6356.752         
// Earth Volumetric mean radius (km) 6371.008
include_once "../settings.php"; // settings
ini_set('max_execution_time', 9999999); //300 seconds = 5 minutes
ini_set("memory_limit", "-1");

// get people flows from MongoDB
function getPeopleFlows($clusterSize) {
    global $config;
    $client = new MongoClient($config["mongodb_flows_url"]);
    $collection = $client->data->collection;
    // cluster suffix
    $cluster_suffix = "_" . $clusterSize;
    $previous = array();
    // max row for recommendations
    $max_row["r"] = 0;
    // max row for sensors
    $max_row["s"] = 0;
    $links = array();
    $clusters_squares_array = array();
    $users_profiles = getUsersProfiles();
    /* $options = [
      "latitude" =>
      [
      '$gte' => doubleval($config["min_latitude"]),
      '$lte' => doubleval($config["max_latitude"])
      ],
      "longitude" =>
      [
      '$gte' => doubleval($config["min_longitude"]),
      '$lte' => doubleval($config["max_longitude"])
      ]
      ]; */
    $options = [
        '$and' => [
            ['$or' => [
                    ['provider' => 'fused'],
                    ['provider' => 'gps']
                ]
            ],
            [
                'timestamp' => [ '$gt' => new MongoDate(strtotime("1970-01-01 00:00:00"))]
            ],
            ['$or' => [
                    ['type' => 'r', 'row' => ['$gt' => $max_row["r"]]],
                    ['type' => 's', 'row' => ['$gt' => $max_row["s"]]]
                ]
            ]
        /* [
          'user' => ['$ne' => '0000000011111111222222223333333344444444555555556666666677777777']
          ],
          [
          'user' => ['$ne' => null]
          ] */
        ]
    ];
    // sort the results by user desc, timestamp desc
    $cursor = $collection->find($options)->sort(["user" => 1, "timestamp" => 1]); //->limit(100);
    // load the status from file (@ suppress the warning if the file is not there)
    $status = @file_get_contents("./status" . $cluster_suffix . ".json");
    if ($status !== false) {
        $max_row_r_max_row_s_previous_links = json_decode($status, true);
        $max_row["r"] = intval($max_row_r_max_row_s_previous_links[0]);
        $max_row["s"] = intval($max_row_r_max_row_s_previous_links[1]);
        $previous = $max_row_r_max_row_s_previous_links[2];
        $links = $max_row_r_max_row_s_previous_links[3];
    }

    foreach ($cursor as $v) {
        $max_row[$v["type"]] = max(intval($max_row[$v["type"]]), intval($v["row"]));

        // avoid data where user profile is null
        $profile = isset($users_profiles[$v["user"]]) ? $users_profiles[$v["user"]] : null;
        if ($profile == null) {
            continue;
        }

        $cluster = getClusterSquare($v["latitude"], $v["longitude"], $clusterSize);
        // set the cluster to the clusters squares array if in the bounds
        //if ($cluster[0] >= $config["min_latitude"] && $cluster[0] <= $config["max_latitude"] && $cluster[1] >= $config["min_longitude"] && $cluster[1] <= $config["max_longitude"]) {
        $clusters_squares_array[$cluster[0] . "," . $cluster[1]] = $cluster;
        //}
        if (isset($previous["latitude"]) && ($cluster[0] != $previous["latitude"] || $cluster[1] != $previous["longitude"]) && $v["user"] == $previous["user"]) {
            $key = $cluster[0] . "," . $cluster[1] . "|" . $previous["latitude"] . "," . $previous["longitude"];
            $links[$key] = isset($links[$key]) ? $links[$key] + 1 : 1;
        }
        $previous["user"] = $v["user"];
        $previous["latitude"] = $cluster[0];
        $previous["longitude"] = $cluster[1];
    }
    $client->close();

    // sort $links by descending value
    arsort($links);

    $clusters_array = array();
    $clusters_squares_array_filtered = array();
    $links_file = "target,source,flow\n";
    //$markers = "markers.clearLayers();\n";
    $grid = "var gridLayer" . $cluster_suffix . " = L.layerGroup([]);\n";

    //$counter = 0;
    foreach ($links as $k => $v) {
        // do not include values < 2
        if ($links[$k] < 2) {
            break;
        }
        // populate node.geojson file
        $target_source = split("\|", $k);
        $target = split(",", $target_source[0]);
        $source = split(",", $target_source[1]);
        $target_geojson = getGeoJSON($target);
        $source_geojson = getGeoJSON($source);
        if (!in_array($target_geojson, $clusters_array)) {
            $clusters_array[] = $target_geojson;
        }
        if (!in_array($source_geojson, $clusters_array)) {
            $clusters_array[] = $source_geojson;
        }
        // populate clusters array filtered
        if (isset($clusters_squares_array[$target[0] . "," . $target[1]])) {
            $clusters_squares_array_filtered[$target[0] . "," . $target[1]] = $clusters_squares_array[$target[0] . "," . $target[1]];
        }
        if (isset($clusters_squares_array[$source[0] . "," . $source[1]])) {
            $clusters_squares_array_filtered[$source[0] . "," . $source[1]] = $clusters_squares_array[$source[0] . "," . $source[1]];
        }
        // populate links.csv file
        $links_file .= sha1($target[0] . $target[1]) . "," . sha1($source[0] . $source[1]) . "," . $links[$k] . "\n";
        //$counter++;
        // populate cluster markers javascript
        //$markers .= "markers.addLayer(L.marker([" . $target[0] . "," . $target[1] . "]).bindPopup('Lat: " . $target[0] . "<br>Lon: " . $target[1] . "'));";
    }

    // populate grid javascript
    foreach ($clusters_squares_array_filtered as $v) {
        $lat_center = $v[0];
        $lon_center = $v[1];
        $lat_right = $v[2];
        $lat_left = $v[3];
        $lon_top = $v[4];
        $lon_bottom = $v[5];
        /* // line top right point - top left point
          $grid .= "gridLayer" . $cluster_suffix . ".addLayer(new L.Polyline([L.latLng(" . $v[2] . "," . $v[4] . "), L.latLng(" . $v[3] . "," . $v[4] . ")], {color: 'green', weight: 1}));";
          // line bottom right point - bottom left point
          $grid .= "gridLayer" . $cluster_suffix . ".addLayer(new L.Polyline([L.latLng(" . $v[2] . "," . $v[5] . "), L.latLng(" . $v[3] . "," . $v[5] . ")], {color: 'green', weight: 1}));";
          // line top right point - bottom right point
          $grid .= "gridLayer" . $cluster_suffix . ".addLayer(new L.Polyline([L.latLng(" . $v[2] . "," . $v[4] . "), L.latLng(" . $v[2] . "," . $v[5] . ")], {color: 'green', weight: 1}));";
          // line top left point - bottom left point
          $grid .= "gridLayer" . $cluster_suffix . ".addLayer(new L.Polyline([L.latLng(" . $v[3] . "," . $v[4] . "), L.latLng(" . $v[3] . "," . $v[5] . ")], {color: 'green', weight: 1}));"; */
        $grid .= "gridLayer" . $cluster_suffix . ".addLayer(L.rectangle([[" . $v[2] . "," . $v[4] . "],[" . $v[3] . "," . $v[5] . "]], {color:'green',fill:false,weight:1}));";
    }

    // write node.geojson file
    $geoJSON = ["type" => "FeatureCollection", "features" => $clusters_array];
    file_put_contents("./nodes" . $cluster_suffix . ".geojson", json_encode($geoJSON/* , JSON_PRETTY_PRINT */));
    // write links.csv file
    file_put_contents("./links" . $cluster_suffix . ".csv", $links_file);
    // write markers.js file
    //file_put_contents("../javascript/markers" . $cluster_suffix . ".js", $markers);
    // write grid.js file
    file_put_contents("../javascript/grid" . $cluster_suffix . ".js", $grid);
    // save the status to file
    file_put_contents("./status" . $cluster_suffix . ".json", json_encode(array($max_row["r"], $max_row["s"], $previous, $links)));
}

// get cluster coordinates (decimal latitude and longitude) from coordinates
function getCluster($latitude, $longitude, $clusterSize) {
    $lat_cluster = round(6371000 * log(tan(pi() / 4 + $latitude / 180 * pi() / 2)) / $clusterSize) * $clusterSize;
    $lon_cluster = round($longitude / 180 * pi() * 6371000 / $clusterSize) * $clusterSize;
    $lat = (2 * atan(exp($lat_cluster / 6371000)) - pi() / 2) * 180 / pi();
    $lon = $lon_cluster / 6371000 * 180 / pi();
    return array($lat, $lon);
}

// get cluster square coordinates (decimal latitude and longitude) 
// ([lat_center, lon_center], [lat_top_right, lon_top_right], [lat_top_left, lon_top_left], [lat_bottom_left, lon_bottom_left], [lat_bottom_right, lon_bottom_right] from coordinates
function getClusterSquare($latitude, $longitude, $clusterSize) {
    $lat_cluster = round(6371000 * log(tan(pi() / 4 + $latitude / 180 * pi() / 2)) / $clusterSize) * $clusterSize;
    $lon_cluster = round($longitude / 180 * pi() * 6371000 / $clusterSize) * $clusterSize;

    $lat_center = (2 * atan(exp($lat_cluster / 6371000)) - pi() / 2) * 180 / pi();
    $lon_center = $lon_cluster / 6371000 * 180 / pi();
    $lat_right = (2 * atan(exp(($lat_cluster + $clusterSize / 2) / 6371000)) - pi() / 2) * 180 / pi();
    $lat_left = (2 * atan(exp(($lat_cluster - $clusterSize / 2) / 6371000)) - pi() / 2) * 180 / pi();
    $lon_top = ($lon_cluster + $clusterSize / 2) / 6371000 * 180 / pi();
    $lon_bottom = ($lon_cluster - $clusterSize / 2) / 6371000 * 180 / pi();

    return array($lat_center, $lon_center, $lat_right, $lat_left, $lon_top, $lon_bottom);
}

// get GeoJSON object for coordinates
function getGeoJSON($cluster) {
    return [
        "type" => "Feature",
        "id" => sha1($cluster[0] . $cluster[1]),
        "properties" => ["LAT" => doubleval($cluster[0]), "LON" => doubleVal($cluster[1])],
        "geometry" => [
            "type" => "Point",
            "coordinates" => [doubleval($cluster[1]), doubleval($cluster[0])]
        ]
    ];
}

// http://php.net/manual/en/function.json-last-error.php
function getJSONError($string) {
    json_decode($string);

    switch (json_last_error()) {
        case JSON_ERROR_NONE:
            echo ' - No errors';
            break;
        case JSON_ERROR_DEPTH:
            echo ' - Maximum stack depth exceeded';
            break;
        case JSON_ERROR_STATE_MISMATCH:
            echo ' - Underflow or the modes mismatch';
            break;
        case JSON_ERROR_CTRL_CHAR:
            echo ' - Unexpected control character found';
            break;
        case JSON_ERROR_SYNTAX:
            echo ' - Syntax error, malformed JSON';
            break;
        case JSON_ERROR_UTF8:
            echo ' - Malformed UTF-8 characters, possibly incorrectly encoded';
            break;
        default:
            echo ' - Unknown error';
            break;
    }
}

// calculate the area of a polygon of decimal coordinates ($lat1, $lon1, $lat2, $lon2) in m^2 http://mathforum.org/library/drmath/view/63767.html
function calculatePolygonArea($lat1, $lat2, $lon1, $lon2) {
    //return 2 * pi() * pow(6371000, 2) * abs(sin(deg2rad($lat1)) - sin(deg2rad($lat2))) * abs($lon1 - $lon2) / 360;
    return (pi() / 180) * pow(6371000, 2) * abs(sin(deg2rad($lat1)) - sin(deg2rad($lat2))) * abs($lon1 - $lon2);
}

// calculate the area of a cluster from its center and cluster size ($lat, $lon, $clusterSize) in m^2
function calculateClusterArea($lat, $lon, $clusterSize) {
    $cluster = getClusterSquare($lat, $lon, $clusterSize);
    $lat_center = $cluster[0];
    $lon_center = $cluster[1];
    $lat_right = $cluster[2];
    $lat_left = $cluster[3];
    $lon_top = $cluster[4];
    $lon_bottom = $cluster[5];
    return calculatePolygonArea($lat_left, $lat_right, $lon_top, $lon_bottom);
}

// calculate the distance in km between coordinates in decimal degrees (latitude, longitude)
function distFrom($lat1, $lng1, $lat2, $lng2) {
    if (($lat2 == 0 && $lng2 == 0) || ($lat1 == $lat2 && $lng1 == $lng2)) {
        return 0;
    }
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $dist = $earthRadius * $c;

    return $dist / 1000;
}

// get users profiles map
function getUsersProfiles() {
    global $config;

    //CONNECT
    $link = mysqli_connect($config['host_recommender'], $config['user_recommender'], $config['pass_recommender'], $config['database_recommender']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    // GET DATA
    $users_profiles = array();
    $sql = "SELECT user, profile FROM recommender.users";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $users_profiles[$row["user"]] = $row["profile"];
    }
    //close connection
    mysqli_close($link);
    return $users_profiles;
}

// get people flows from MongoDB for various cluster sizes
$n = 13;
$clusterSize = 1104;
for ($i = 0; $i < $n; $i++) {
    getPeopleFlows($clusterSize);
    $clusterSize *= 2;
}
?>