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
// set unlimited memory usage for server
ini_set('memory_limit', '-1');

// heatmap of users per profile
//http://www.patrick-wied.at/static/heatmapjs/
//http://jsonviewer.stack.hu/
// calculate distance in km between coordinates in decimal degrees (latitude, longitude)
function distFrom($lat1, $lng1, $lat2, $lng2) {
    if (($lat2 == 0 && $lng2 == 0) || ($lat1 == $lat2 && $lng1 == $lng2))
        return 0;
    $earthRadius = 6371000; // meters
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
?>
<html>
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
        <link rel="stylesheet" type="text/css" href="css/typography1.css" />
        <link rel="stylesheet" type="text/css" href = "css/jquery-ui.css"/>
        <script type="text/javascript" src="javascript/jquery-2.1.0.min.js"></script>
        <script type="text/javascript" src="javascript/jquery-ui.min.js"></script>
        <script type="text/javascript" src="javascript/jquery.redirect.js"></script>

        <!-- map headers -->
        <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.css" />
        <!--<link rel="stylesheet" href="css/leaflet.css" />-->
        <script src="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.js"></script>
        <!--<script type="text/javascript" src="javascript/maps/leaflet.js"></script>-->

        <!--leaflet label plugin includes https://github.com/Leaflet/Leaflet.label-->
        <script src = "javascript/maps/leaflet-label-plugin/Label.js" ></script>
        <script src="javascript/solr.js" type="text/javascript"></script>
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
        <!-- heat map and leaflet plugin http://www.patrick-wied.at/static/heatmapjs/example-heatmap-leaflet.html -->
        <script src = "javascript/maps/heatmap.js" ></script>
        <script src = "javascript/maps/leaflet-heatmap.js" ></script>

        <!-- marker cluster plugin https://github.com/Leaflet/Leaflet.markercluster-->
        <link rel="stylesheet" href="javascript/maps/markercluster/MarkerCluster.css" />
        <link rel="stylesheet" href="javascript/maps/markercluster/MarkerCluster.Default.css" />
        <script src="javascript/maps/markercluster/leaflet.markercluster-src.js"></script>

        <!-- polyline decorator https://github.com/bbecquet/Leaflet.PolylineDecorator-->
        <script src="javascript/maps/leaflet_polylineDecorator/leaflet.polylineDecorator.js"></script>
    </head>
    <body>
        <?php
        include_once "header.php"; //include header
        ?>
        <div id='container1'> <!-- div container -->
            <?php
            include_once "settings.php";
            include_once "Polyline.php";
            include_once "SolrPhpClient/Apache/Solr/Service.php";

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
                $link = mysqli_connect($config['wifi_host'], $config['wifi_user'], $config['wifi_pass'], $config['wifi_database']);

                /* check connection */
                if (mysqli_connect_errno()) {
                    printf("Connection failed: %s\n", mysqli_connect_error());
                    exit();
                }
                // GET DATA
                $coordinates = array();
                $sql = "SELECT latitude, longitude, address FROM wifi.aps_new";
                // if id is set, then search only the coordinates for this AP in the table wifi.all_aps
                if (isset($_REQUEST["id"])) {
                    $sql = "SELECT latitude, longitude FROM wifi.aps WHERE id = " . $_REQUEST["id"];
                    $result = mysqli_query($link, $sql) or die(mysqli_error());
                    while ($row = mysqli_fetch_assoc($result)) {
                        $latitude = $row["latitude"];
                        $longitude = $row["longitude"];
                    }
                    $sql = "SELECT latitude, longitude, address,
                             ( 6371000 * acos( cos( radians(" . $latitude . ") ) * cos( radians(latitude) ) 
                             * cos( radians(longitude) - radians(" . $longitude . ")) + sin(radians(" . $latitude . ")) 
                             * sin( radians(latitude)))) AS distance 
                             FROM wifi.all_aps HAVING distance <= 100 ORDER BY distance ASC LIMIT 1";
                }
                $result = mysqli_query($link, $sql) or die(mysqli_error());
                while ($row = mysqli_fetch_assoc($result)) {
                    if (!in_array($row["latitude"] . "|" . $row["longitude"] . "|" . $row["address"], $coordinates)) {
                        $coordinates[] = $row["latitude"] . "|" . $row["longitude"] . "|" . $row["address"];
                    }
                }
                //close connection
                mysqli_close($link);
                return $coordinates;
            }

            function printCoordinates($coordinates) {
                $javascript = "";
                foreach ($coordinates as $coordinate) {
                    $lat_lng_mode_timestamp = split("\|", $coordinate);
                    $latitude = $lat_lng_mode_timestamp[0];
                    $longitude = $lat_lng_mode_timestamp[1];
                    $javascript .= ",L.latLng(" . $latitude . "," . $longitude . ")\n";
                }
                return substr($javascript, 1);
            }

            // print markers (gps or manual)
            function printMarkers($coordinates, $mac_aps) {
                $markers = "var markers = [];\n";
                $markers .= "//Extend the Default marker class
                    var ManualMarkerIcon = L.Icon.Default.extend({
                    options: {
            	    iconUrl: 'images/location_red.png' 
                    }
                    });
                    var manualMarkerIcon = new ManualMarkerIcon();";
                $markers .= "var markersClusterGroup = L.markerClusterGroup();\n";
                $markersNotCluster = "";
                $i = 0;
                foreach ($coordinates as $coordinate) {
                    $lat_lng_mode_address = split("\|", $coordinate);
                    $latitude = $lat_lng_mode_address[0];
                    $longitude = $lat_lng_mode_address[1];
                    $address = $lat_lng_mode_address[2];
                    if (isset($mac_aps[$latitude . " " . $longitude])) {
                        if (count($mac_aps[$latitude . " " . $longitude]) > 1) {
                            foreach ($mac_aps[$latitude . " " . $longitude] as $mac_ap) {
                                $markers .= "markers[" . $i . "] = L.marker([" . $latitude . "," . $longitude . "])" .
                                        ".bindPopup('Lat: " . $latitude . "<br>Lon: " . $longitude . "<br>" . addslashes($address) . "<br>Mac: " . $mac_ap . "<br>N: <span id=\"a" . str_replace(".", "", $latitude) . str_replace(".", "", $longitude) . "\"></span>')" .
                                        ".on('click', " .
                                        "function(event){" .
                                        "solr(" . $latitude . "," . $longitude . ", '" . $mac_ap . "');" .
                                        "});\n";
                                $markers .= "markersClusterGroup.addLayer(markers[" . $i . "]);\n";
                                $i++;
                            }
                        } else {
                            $markers .= "markers[" . $i . "] = L.marker([" . $latitude . "," . $longitude . "])" .
                                    ".bindPopup('Lat: " . $latitude . "<br>Lon: " . $longitude . "<br>" . addslashes($address) . "<br>Mac: " . $mac_aps[$latitude . " " . $longitude][0] . "<br>N: <span id=\"a" . str_replace(".", "", $latitude) . str_replace(".", "", $longitude) . "\"></span>')" .
                                    ".on('click', " .
                                    "function(event){" .
                                    "solr(" . $latitude . "," . $longitude . ", '" . $mac_aps[$latitude . " " . $longitude][0] . "');" .
                                    "});\n";
                            $markersNotCluster .= ",markers[" . $i . "]";
                            $i++;
                        }
                    } else {
                        $markers .= "markers[" . $i . "] = L.marker([" . $latitude . "," . $longitude . "], {icon: manualMarkerIcon})" .
                                ".bindPopup('Lat: " . $latitude . "<br>Lon: " . $longitude . "<br>" . addslashes($address) . "')" .
                                ".on('click', " .
                                "function(event){" .
                                "solr(" . $latitude . "," . $longitude . ");" .
                                "});\n";
                        $i++;
                    }
                    //$layergroup .= "markers[" . $i . "],";
                }
                //$layergroup = "var markersLayerGroup = L.layerGroup([" . substr($layergroup, 0, strlen($layergroup) - 1) . "]);";
                return $markers . "var markersLayerGroup = L.layerGroup([markersClusterGroup" . $markersNotCluster . "]);";
            }

            // get the heatmap data, https://wiki.apache.org/solr/SpatialSearch
            function getHeatMapData() {
                global $config;
                $solr = new Apache_Solr_Service($config["solr_host"], $config["solr_port"], $config["solr_collection"]);
                $heatmapdata = "";
                // limit result to 100 m radius
                //$params = array('sfield' => 'latitude_longitude', 'pt' => $latitude . ',' . $longitude, 'sort' => 'geodist() asc', 'd' => '0.1', 'fq' => '{!geofilt}');
                $results = $solr->search("network_name:FirenzeWiFi AND accuracy:*", 0, 1000000, null);
                $i = 0;
                // get anonymous APs
                //$anonymous_aps = getAnonymousAPs();
                foreach ($results->response->docs as $doc) {
                    foreach ($doc as $field => $value) {
                        if ($field == "latitude") {
                            $latitude = $value;
                        } else if ($field == "longitude") {
                            $longitude = $value;
                        } else if ($field == "rssi") {
                            $rssi = $value;
                        } else if ($field == "distance_by_rssi") {
                            $distance_by_rssi = $value;
                        } else if ($field == "MAC_address") {
                            $mac_address = $value;
                        }
                    }
                    /* if (isset($distance_by_rssi)) {
                      echo "var pMarkers = [];
                      \n";
                      if ($rssi <= -90) {
                      echo 'pMarkers[' . $i . '] = L.marker([' . $latitude . ',' . $longitude . '], {icon: redIcon}).bindPopup("Distance by RSSI: ' . round($distance_by_rssi, 2) . ' m<br> RSSI: ' . $rssi . ' dB").addTo(map);';
                      } else if ($rssi > -90 and $rssi <= -80) {
                      echo 'pMarkers[' . $i . '] = L.marker([' . $latitude . ',' . $longitude . '], {icon: orangeIcon}).bindPopup("Distance by RSSI: ' . round($distance_by_rssi, 2) . ' m<br> RSSI: ' . $rssi . ' dB").addTo(map);';
                      } else if ($rssi > -80 and $rssi <= -70) {
                      echo 'pMarkers[' . $i . '] = L.marker([' . $latitude . ',' . $longitude . '], {icon: yellowIcon}).bindPopup("Distance by RSSI: ' . round($distance_by_rssi, 2) . ' m<br> RSSI: ' . $rssi . ' dB").addTo(map);';
                      } else if ($rssi > -70 and $rssi <= -60) {
                      echo 'pMarkers[' . $i . '] = L.marker([' . $latitude . ',' . $longitude . '], {icon: brownIcon}).bindPopup("Distance by RSSI: ' . round($distance_by_rssi, 2) . ' m<br> RSSI: ' . $rssi . ' dB").addTo(map);';
                      } else if ($rssi > -60) {
                      echo 'pMarkers[' . $i . '] = L.marker([' . $latitude . ',' . $longitude . '], {icon: whiteIcon}).bindPopup("Distance by RSSI: ' . round($distance_by_rssi, 2) . ' m<br> RSSI: ' . $rssi . ' dB").addTo(map);';
                      }
                      $i++;
                      } */
                    $heatmapdata .= ($i != 0 ? ", " : "") . " {
                    \"lat\": " . $latitude . ", \"lng\":" . $longitude . ", \"rssi\": " . abs($rssi) . "}";
                    $i++;
                }
                return "[" . $heatmapdata . "]";
            }

            // get APs with a mac
            function getMacAPs() {
                global $config;

                //CONNECT
                $link = mysqli_connect($config['wifi_host'], $config['wifi_user'], $config['wifi_pass'], $config['wifi_database']);

                /* check connection */
                if (mysqli_connect_errno()) {
                    printf("Connection failed: %s\n", mysqli_connect_error());
                    exit();
                }
                // get the nearest AP from wifi.all_aps (RDF) to that in wifi.aps (Comune di Firenze)
                $coordinates = array();
                $ap = isset($_REQUEST["id"]) ? " WHERE id = " . $_REQUEST["id"] : "";
                $sql1 = "SELECT mac_radio, latitude, longitude FROM wifi.aps_new" . $ap;
                $result = mysqli_query($link, $sql1) or die(mysqli_error());
                while ($row1 = mysqli_fetch_assoc($result)) {
                    /* $sql2 = "SELECT latitude, longitude,
                      ( 6371000 * acos( cos( radians(" . $row1["latitude"] . ") ) * cos( radians(latitude) )
                     * cos( radians(longitude) - radians(" . $row1["longitude"] . ")) + sin(radians(" . $row1["latitude"] . ")) 
                     * sin( radians(latitude)))) AS distance 
                      FROM wifi.all_aps HAVING distance <= 100 ORDER BY distance ASC LIMIT 1";
                      $aps = mysqli_query($link, $sql2) or die(mysqli_error());
                      while ($row2 = mysqli_fetch_assoc($aps)) {
                      $coordinates[$row2["latitude"] . " " . $row2["longitude"]][] = $row1["mac"];
                      } */
                    $coordinates[$row1["latitude"] . " " . $row1["longitude"]][] = $row1["mac_radio"];
                }
                //close connection
                mysqli_close($link);
                return $coordinates;
            }

            // get APs without a mac
            function getNoMacAPs($mac_aps) {
                global $config;

                //CONNECT
                $link = mysqli_connect($config['wifi_host'], $config['wifi_user'], $config['wifi_pass'], $config['wifi_database']);

                /* check connection */
                if (mysqli_connect_errno()) {
                    printf("Connection failed: %s\n", mysqli_connect_error());
                    exit();
                }
                // GET DATA
                $coordinates = array();
                $sql = "SELECT latitude, longitude FROM wifi.all_aps";
                $result = mysqli_query($link, $sql) or die(mysqli_error());
                while ($row = mysqli_fetch_assoc($result)) {
                    if (!in_array($row["latitude"] . " " . $row["latitude"], $mac_aps)) {
                        $coordinates[] = $row1["latitude"] . " " . $row1["latitude"];
                    }
                }
                //close connection
                mysqli_close($link);
                return $coordinates;
            }

            // get people flows
            function getPeopleFlows() {
                global $config;
                $markers = "var markers = L.markerClusterGroup();\n";
                //CONNECT
                $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
                /* check connection */
                if (mysqli_connect_errno()) {
                    printf("Connection failed: %s\n", mysqli_connect_error());
                    exit();
                }
                // get recommendations data
                if (isset($_REQUEST["profile"])) {
                    $sql = "SELECT latitude, longitude FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE a.profile = '" . $_REQUEST["profile"] . "' AND b.label IS NULL";
                } else {
                    $sql = "SELECT latitude, longitude FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user WHERE b.label IS NULL";
                }
                $result = mysqli_query($link, $sql) or die(mysqli_error());
                $profile = isset($_REQUEST["profile"]) ? $_REQUEST["profile"] : "";
                while ($row = mysqli_fetch_assoc($result)) {
                    $data[] = array("lat" => $row["latitude"], "lng" => $row["longitude"], "count" => 1);
                    $markers .= "markers.addLayer(L.marker([" . $row["latitude"] . "," . $row["longitude"] . "]).bindPopup('Lat: " . $row["latitude"] . "<br>Lon: " . $row["longitude"] . "<br>Profile: " . $profile . "<br>'))\n";
                }
                return array(json_encode($data), $markers);
            }

            //get people clustered markers
            function getPeopleClusteredMarkers() {
                global $config;
                $markers = "var peopleClusteredMarkers = L.markerClusterGroup();\n";
                //CONNECT
                $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
                /* check connection */
                if (mysqli_connect_errno()) {
                    printf("Connection failed: %s\n", mysqli_connect_error());
                    exit();
                }
                if (isset($_REQUEST["profile"])) {
                    $sql = "SELECT 
                        (2*atan(exp(y/6371000))-PI()/2)*180/PI() AS latitude,
                         x/6371000*180/PI() AS longitude,
                        COUNT(DISTINCT(user)) AS num
                        FROM
                        (SELECT a.user, round(longitude/180*PI()* 6371000 / 138)*138 AS x, 
                        round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user
                        WHERE a.profile = '" . $_REQUEST["profile"] . "' AND b.label IS NULL AND mode = 'gps') AS c GROUP BY x, y";
                } else {
                    $sql = "SELECT 
                        (2*atan(exp(y/6371000))-PI()/2)*180/PI() AS latitude,
                         x/6371000*180/PI() AS longitude,
                        COUNT(DISTINCT(user)) AS num
                        FROM
                        (SELECT a.user, round(longitude/180*PI()* 6371000 / 138)*138 AS x, 
                        round(6371000*ln(tan(PI()/4+latitude/180*PI()/2)) /138)*138 AS y FROM recommender.recommendations_log a LEFT JOIN recommender.users b ON a.user = b.user
                        WHERE b.label IS NULL AND mode = 'gps') AS c GROUP BY x, y";
                }
                $result = mysqli_query($link, $sql) or die(mysqli_error());
                while ($row = mysqli_fetch_assoc($result)) {
                    $profile = isset($_REQUEST["profile"]) ? $_REQUEST["profile"] : "";
                    for ($i = 0; $i < intval($row["num"]); $i++) {
                        $markers .= "peopleClusteredMarkers.addLayer(L.marker([" . $row["latitude"] . "," . $row["longitude"] . "]).bindPopup('Lat: " . $row["latitude"] . "<br>Lon: " . $row["longitude"] . "<br>Profile: " . $profile . "<br>'))\n";
                    }
                }
                return $markers;
            }

            // get the heatmap data
            //$heatMapData = getHeatMapData();
            // get aps with a mac
            $mac_aps = getMacAPs();
            // get aps withot a mac
            //$no_mac_aps = getNoMacAPs($mac_aps);
            // get coordinates (gps and manual)
            $coordinates = getCoordinates();
            if (count($coordinates) == 0) {
                echo "No results found.";
                exit();
            }
            // print gps coordinates array (number of gps coordinates, coordinates javascript)
            //$coordinates_javascript = printCoordinates($coordinates);
            // print markers
            //$markers_javascript = printMarkers($coordinates, $mac_aps);

            $peopleFlows_markers = getPeopleFlows();
            //print zones flows
            $people_flows = $peopleFlows_markers[0];
            //print clusterd markers
            $clustered_markers = $peopleFlows_markers[1];

            // get the heatmap data
            //$heatMapData = getHeatMapData();
            ?>
            <!-- display map javascript -->
            <div id="map" class="map"></div>
            <script type="text/javascript">
                //var map = L.map('map');
                /*L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
                 attribution: '© OpenStreetMap contributors',
                 maxZoom: 23
                 }).addTo(map);*/
                var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                        '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                        'Imagery © <a href="http://mapbox.com">Mapbox</a>';
                // for satellite map use mapbox.streets-satellite in the url
                var baseLayer = L.tileLayer('https://api.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicGJlbGxpbmkiLCJhIjoiNTQxZDNmNDY0NGZjYTk3YjlkNTAzNWQwNzc0NzQwYTcifQ.CNfaDbrJLPq14I30N1EqHg', {
                    attribution: mbAttr,
                    maxZoom: 22,
                });
                //heatmap recommender data
                var testData = {
                    max: 8,
                    //data: [{lat: 43.79172134399414, lng: 11.24617958068848, rssi: -60}, {lat: 43.77293014526367, lng: 11.22749996185303, rssi: -65}, {lat: 43.78359985351562, lng: 11.16689968109131, rssi: -70}]
                    data: <?php echo $people_flows; ?>
                };
                //heatmap wifi data
                /*var testDataWiFi = {
                 max: 8,
                 data: <?php /* echo $heatMapData; */ ?>
                 };*/
                //heatmap configuration
                var cfg = {
                    // radius should be small ONLY if scaleRadius is true (or small radius is intended)
                    // if scaleRadius is false it will be the constant radius used in pixels
                    "radius": 0.0008,
                    "maxOpacity": .8,
                    // scales the radius based on map zoom
                    "scaleRadius": true,
                    // if set to false the heatmap uses the global maximum for colorization
                    // if activated: uses the data maximum within the current map boundaries 
                    //   (there will always be a red spot with useLocalExtremas true)
                    "useLocalExtrema": false,
                    // which field name in your data represents the latitude - default "lat"
                    latField: 'lat',
                    // which field name in your data represents the longitude - default "lng"
                    lngField: 'lng',
                    // which field name in your data represents the data value - default "value"
                    valueField: 'count',
                    gradient: {
                        // enter n keys between 0 and 1 here
                        // for gradient color customization
                        '.0': 'blue',
                        '.1': 'cyan',
                        '.2': 'green',
                        '.3': 'yellowgreen',
                        '.4': 'yellow',
                        '.5': 'gold',
                        '.6': 'orange',
                        '.7': 'darkorange',
                        '.8': 'tomato',
                        '.9': 'orangered',
                        '1.0': 'red'
                    }
                };
                //setup heatmap recommender
                var heatmapLayer = new HeatmapOverlay(cfg);
                var map = new L.Map('map', {
                    center: new L.LatLng(43.76990127563477, 11.25531959533691),
                    zoom: 14,
                    layers: [baseLayer, heatmapLayer]
                });
                heatmapLayer.setData(testData);

                //setup heatmap wifi
                //var heatmapLayerWiFi = new HeatmapOverlay(cfg);
                //heatmapLayerWiFi.setData(testDataWiFi);

                // set cluster trajectories to be set by the function getTrajectory (generated by the Java application TrajectoriesClustering) in clusteredTrajectories_[profile].js
                var clusterTrajectories = "";
                map.on('click', function () {
                    map.removeLayer(clusterTrajectories);
                });
                // view clustered trajectory
<?php
if (isset($_REQUEST["cluster"])) {
    $data = file_get_contents("http://localhost/screcommender/recommender/getCluster.php?profile=" . $_REQUEST["profile"] . "&cluster=" . $_REQUEST["cluster"]);
    echo "var clusterTrajectory = " . $data . ";";
    echo "map.addLayer(clusterTrajectory);";
    echo "map.fitBounds(clusterTrajectory.getBounds());";
}
?>

            </script>

            <!--include clustered trajectories-->
            <script type="text/javascript" src="javascript/clusteredTrajectories_<?php echo isset($_REQUEST["profile"]) ? $_REQUEST["profile"] : "";
?>.js"></script>

            <!--include trajectories-->
            <!--<script type="text/javascript" src="javascript/trajectories_<?php echo isset($_REQUEST["profile"]) ? $_REQUEST["profile"] : ""; ?>.js"></script>-->

            <script type="text/javascript">

                var coordinateList = [<?php /* echo $coordinates_javascript; */ ?>];
                //var routeList = [<?php /* echo $route_javascript; */ ?>];

                // line of user's locations
                var coordinatesLine = new L.Polyline(coordinateList, {
                    color: 'blue',
                    weight: 8,
                    opacity: 0.5,
                    smoothFactor: 1
                });
                // line of shortest route path between user's locations
                /*var routeLine = new L.Polyline(routeList, {
                 color: 'red',
                 weight: 8,
                 opacity: 0.5,
                 smoothFactor: 1
                 });*/
                pMarkers = [];
                function solr(latitude, longitude, mac) {
                    $.getJSON('solr.php', {latitude: latitude, longitude: longitude, mac: mac}, function (data) {
                        // remove previous markers
                        for (var i = 0; i < pMarkers.length; i++) {
                            map.removeLayer(pMarkers[i]);
                        }
                        pMarkers = [];
                        // data will hold the php array as a javascript object
                        $.each(data, function (key, val) {
                            var lat = data[key].latitude;
                            var lng = data[key].longitude;
                            var distance_by_rssi = data[key].distance_by_rssi;
                            var rssi = data[key].rssi;
                            var distance = data[key].distance;
                            var MAC_address = data[key].MAC_address;
                            var accuracy = typeof (data[key].accuracy) != "undefined" ? "<br>Accuracy: " + data[key].accuracy + " m" : "";
                            var pMarker;
                            if (rssi <= -90) {
                                pMarker = L.marker([lat, lng], {icon: whiteIcon}).bindPopup("RSSI: " + rssi + " dB<br>Distance: " + distance + " m<br>Distance by RSSI: " + distance_by_rssi + "  m<br>MAC address: " + MAC_address + accuracy);
                            } else if (rssi > -90 && rssi <= -80) {
                                pMarker = L.marker([lat, lng], {icon: brownIcon}).bindPopup("RSSI: " + rssi + " dB<br>Distance: " + distance + " m<br>Distance by RSSI: " + distance_by_rssi + "  m<br>MAC address: " + MAC_address + accuracy);
                            } else if (rssi > -80 && rssi <= -70) {
                                pMarker = L.marker([lat, lng], {icon: yellowIcon}).bindPopup("RSSI: " + rssi + " dB<br>Distance: " + distance + " m<br>Distance by RSSI: " + distance_by_rssi + "  m<br>MAC address: " + MAC_address + accuracy);
                            } else if (rssi > -70 && rssi <= -60) {
                                pMarker = L.marker([lat, lng], {icon: orangeIcon}).bindPopup("RSSI: " + rssi + " dB<br>Distance: " + distance + " m<br>Distance by RSSI: " + distance_by_rssi + "  m<br>MAC address: " + MAC_address + accuracy);
                            } else if (rssi > -60) {
                                pMarker = L.marker([lat, lng], {icon: redIcon}).bindPopup("RSSI: " + rssi + " dB<br>Distance: " + distance + " m<br>Distance by RSSI: " + distance_by_rssi + "  m<br>MAC address: " + MAC_address + accuracy);
                            }
                            map.addLayer(pMarker);
                            pMarkers.push(pMarker);
                        });
                        // replace span in popup with number of markers
                        $("#a" + (latitude + "").replace(".", "") + (longitude + "").replace(".", "")).html(pMarkers.length);
                    });
                }
<?php
//echo "coordinatesLine.addTo(map);";
//echo $markers_javascript;
echo $clustered_markers;
echo getPeopleClusteredMarkers();
/* if (isset($_REQUEST["id"])) {
  $lat_lng_mode_timestamp = split("\|", $coordinates[0]);
  printRSSI($lat_lng_mode_timestamp[0], $lat_lng_mode_timestamp[1]);
  } */
?>

                // add a legend, http://leafletjs.com/examples/choropleth.html
                var legend = L.control({position: 'topright'});
                /*legend.onAdd = function (map) {
                 var div = L.DomUtil.create('div', 'info legend');
                 categories = ['< -89 dB', '-89/-80 dB', '-79/-70 dB', '-69/-60 dB', '> -60 dB'];
                 color_urls = ['url(images/white_icon.png)', 'url(images/brown_icon.png)', 'url(images/yellow_icon.png)', 'url(images/orange_icon.png)', 'url(images/red_icon.png)'];
                 for (var i = 0; i < categories.length; i++) {
                 div.innerHTML +=
                 '<i style="background: ' + color_urls[i] + '"></i> ' +
                 (categories[i] ? categories[i] + '<br>' : '+');
                 }
                 return div;
                 };*/
                function setColor(color, value, decimals) {
                    cfg["gradient"][value] = color;
                    //document.getElementById("range" + color).innerHTML = value;
                    $("#range" + color).text(parseFloat(value).toFixed(parseInt(decimals)));
                    $("#slider" + color).attr("value", parseFloat(value).toFixed(parseInt(decimals)));
                    map.removeLayer(heatmapLayer);
                    heatmapLayer = new HeatmapOverlay(cfg);
                    heatmapLayer.setData(testData);
                    map.addLayer(heatmapLayer);

                    /*map.removeLayer(heatmapLayerWiFi);
                     heatmapLayerWiFi = new HeatmapOverlay(cfg);
                     heatmapLayerWiFi.setData(testDataWiFi);
                     map.addLayer(heatmapLayerWiFi);*/
                }
                function setOption(option, value, decimals) {
                    cfg[option] = value;
                    //document.getElementById("range" + color).innerHTML = value;
                    //set values for sliders, not for checkboxes
                    if (decimals) {
                        $("#range" + option).text(parseFloat(value).toFixed(parseInt(decimals)));
                        $("#slider" + option).attr("value", parseFloat(value).toFixed(parseInt(decimals)));
                    }
                    /*map.removeLayer(heatmapLayer);
                     heatmapLayer = new HeatmapOverlay(cfg);
                     heatmapLayer.setData(testData);
                     map.addLayer(heatmapLayer);*/
                    heatmapLayer.configure(cfg); // metodo aggiunto in leaflet-heatmap.js per aggiornare la heatmap con la nuova configurazione
                    //heatmapLayerWiFi.configure(cfg);
                }
                function toggleClusteredMarkers(toggle) {
                    if (toggle) {
                        map.addLayer(markers);
                    } else {
                        map.removeLayer(markers);
                    }
                }
                function togglePeopleClusteredMarkers(toggle) {
                    if (toggle) {
                        map.addLayer(peopleClusteredMarkers);
                    } else {
                        map.removeLayer(peopleClusteredMarkers);
                    }
                }
                function toggleClusteredTrajectories(toggle) {
                    if (toggle) {
                        map.addLayer(clusteredTrajectories);
                    } else {
                        map.removeLayer(clusteredTrajectories);
                    }
                }
                function toggleTrajectories(toggle) {
                    if (toggle) {
                        map.addLayer(trajectories);
                    } else {
                        map.removeLayer(trajectories);
                    }
                }
                function toggleTrajectoriesDecorator(toggle) {
                    if (toggle) {
                        map.addLayer(trajectories_decorator);
                    } else {
                        map.removeLayer(trajectories_decorator);
                    }
                }
                function toggleAPsMarkers(toggle) {
                    if (toggle) {
                        map.addLayer(markersLayerGroup);
                    } else {
                        map.removeLayer(markersLayerGroup);
                    }
                }
                function toggleHeatmap(toggle) {
                    if (toggle) {
                        map.addLayer(heatmapLayer);
                    } else {
                        map.removeLayer(heatmapLayer);
                    }
                }
                function toggleWiFiHeatmap(toggle) {
                    if (toggle) {
                        map.addLayer(heatmapLayerWiFi);
                    } else {
                        map.removeLayer(heatmapLayerWiFi);
                    }
                }
                function filterClusteredTrajectories(option, limit) {
                    $("#range" + option).text(parseInt(limit));
                    $("#slider" + option).attr("value", parseInt(limit));
                    $("#checkBoxClusteredTrajectories").prop("checked", true);
                    map.removeLayer(clusteredTrajectories);
                    var tmp = [];
                    for (i = 0; i < polyline_list.length; i++) {
                        var layers = polyline_list[i].getLayers();
                        var size = parseInt(layers[0].options.className);
                        if (size >= limit) {
                            tmp.push(polyline_list[i]);
                        }
                    }
                    clusteredTrajectories = L.layerGroup(tmp);
                    // update the number of filtered clustered trajectories in the legend  
                    $("#numClusteredTrajectories").text(clusteredTrajectories.getLayers().length);
                    map.addLayer(clusteredTrajectories);
                }
                function upSlider(color, step, decimals, max) {
                    var value = $("#slider" + color).attr("value");
                    //setColor(color, value, 0.01);           
                    if (parseFloat(parseFloat(value) + parseFloat(step)) <= max) {
                        $("#range" + color).text(parseFloat(parseFloat(value) + parseFloat(step)).toFixed(parseInt(decimals)));
                        //$("#slider" + color).attr("value", parseFloat(parseFloat(value) + parseFloat(0.01)).toFixed(2));
                        document.getElementById("slider" + color).value = parseFloat(parseFloat(value) + parseFloat(step)).toFixed(parseInt(decimals));
                        $("#slider" + color).trigger('change');
                    }
                }
                function downSlider(color, step, decimals, min) {
                    var value = $("#slider" + color).attr("value");
                    //setColor(color, value, parseFloat(-0.01));
                    if (parseFloat(parseFloat(value) - parseFloat(step)) >= min) {
                        $("#range" + color).text(parseFloat(parseFloat(value) - parseFloat(step)).toFixed(parseInt(decimals)));
                        //$("#slider" + color).attr("value", parseFloat(parseFloat(value) + parseFloat(0.01)).toFixed(2));
                        document.getElementById("slider" + color).value = parseFloat(parseFloat(value) - parseFloat(step)).toFixed(parseInt(decimals));
                        $("#slider" + color).trigger('change');
                    }
                }
                legend.onAdd = function (map) {
                    var div = L.DomUtil.create('div', 'info legend');
                    categories = ['blue', 'cyan', 'green', 'yellowgreen', 'yellow', 'gold', 'orange', 'darkorange', 'tomato', 'orangered', 'red'];
                    var colors = new Array();
                    colors['blue'] = '#0000FF';
                    colors['cyan'] = '#00FFFF';
                    colors['green'] = '#008000';
                    colors['yellowgreen'] = '#9ACD32';
                    colors['yellow'] = '#FFFF00';
                    colors['gold'] = '#FFD700';
                    colors['orange'] = '#FFA500';
                    colors['darkorange'] = '#FF8C00';
                    colors['orangered'] = '#FF4500';
                    colors['tomato'] = '#FF6347';
                    colors['red'] = '#FF0000';
                    var colors_value = new Array();
                    colors_value['blue'] = <?php echo $config["legend_color_blue"]; ?>;
                    colors_value['cyan'] = <?php echo $config["legend_color_cyan"]; ?>;
                    colors_value['green'] = <?php echo $config["legend_color_green"]; ?>;
                    colors_value['yellowgreen'] = <?php echo $config["legend_color_yellowgreen"]; ?>;
                    colors_value['yellow'] = <?php echo $config["legend_color_yellow"]; ?>;
                    colors_value['gold'] = <?php echo $config["legend_color_gold"]; ?>;
                    colors_value['orange'] = <?php echo $config["legend_color_orange"]; ?>;
                    colors_value['darkorange'] = <?php echo $config["legend_color_darkorange"]; ?>;
                    colors_value['tomato'] = <?php echo $config["legend_color_tomato"]; ?>;
                    colors_value['orangered'] = <?php echo $config["legend_color_orangered"]; ?>;
                    colors_value['red'] = <?php echo $config["legend_color_red"]; ?>;
                    //color_urls = ['url(images/white_icon.png)', 'url(images/brown_icon.png)', 'url(images/yellow_icon.png)', 'url(images/orange_icon.png)', 'url(images/red_icon.png)', 'url(images/white_icon.png)', 'url(images/brown_icon.png)', 'url(images/yellow_icon.png)', 'url(images/orange_icon.png)', 'url(images/red_icon.png)'];
                    div.innerHTML += '<div class="text">' + '<?php echo ucfirst(isset($_REQUEST["profile"]) ? $_REQUEST["profile"] : "Global"); ?>' + '</div>';
                    /*for (var i = 0; i < categories.length; i++) {
                     div.innerHTML +=
                     //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                     '<div class="input-color"><div class="color-box" style="background-color: ' + colors[categories[i]] + ';"></div></div>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
                     '<a id="unselect" style="cursor:pointer" onclick="downSlider(\'' + categories[i] + '\',0.01,2,0);">&#10094;</a>&nbsp;&nbsp;&nbsp;' +
                     '<input id="slider' + categories[i] + '" name="sl' + categories[i] + '" type="range" min="0" max="1" value="' + colors_value[categories[i]] + '" step="0.01" style="width: 190px;" onchange="setColor(\'' + categories[i] + '\',this.value,2);">' +
                     '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="unselect" style="cursor:pointer" onclick="upSlider(\'' + categories[i] + '\',0.01,2,1);">&#10095;</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
                     '<span id="range' + categories[i] + '">' + colors_value[categories[i]] + '</span>';
                     }*/
                    // radius
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Radius: &nbsp;&nbsp;&nbsp;&nbsp;' +
                            '<a id="unselect" style="cursor:pointer" onclick="downSlider(\'radius\',0.00001,6,0);">&#10094;</a>&nbsp;&nbsp;&nbsp;' +
                            '<input id="sliderradius" type="range" min="0" max="0.0010" value="0.0008" step="0.00001" onchange="setOption(\'radius\',this.value,6);">' +
                            '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="unselect" style="cursor:pointer" onclick="upSlider(\'radius\',0.00001,6,0.0010);">&#10095;</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
                            '<span id="rangeradius">0.0008</span>';
                    // max opacity                   
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Max Opacity: &nbsp;&nbsp;&nbsp;&nbsp;' +
                            '<a id="unselect" style="cursor:pointer" onclick="downSlider(\'maxOpacity\',0.1,2,0);">&#10094;</a>&nbsp;&nbsp;&nbsp;' +
                            '<input id="slidermaxOpacity" type="range" min="0" max="1" value="0.8" step="0.01" onchange="setOption(\'maxOpacity\',this.value,2);">' +
                            '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="unselect" style="cursor:pointer" onclick="upSlider(\'maxOpacity\',0.01,2,0.8);">&#10095;</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
                            '<span id="rangemaxOpacity">0.80</span>';
                    // view heatmap
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Heatmap: ' +
                            '<input id="checkBoxHeatmap" type="checkbox" name="Heatmap" value="false" checked onclick="toggleHeatmap(this.checked);">';
                    // scale radius
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Scale Radius: ' +
                            '<input id="checkBoxscaleRadius" type="checkbox" name="scaleRadius" value="true" checked onclick="setOption(\'scaleRadius\',this.checked);">';
                    // use local extrema
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Use Local Extrema: ' +
                            '<input id="checkBoxuseLocalExtrema" type="checkbox" name="useLocalExtrema" value="false" onclick="setOption(\'useLocalExtrema\',this.checked);">';
                    // view clustered markers
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Clustered markers: ' +
                            '<input id="checkBoxclusteredMarkers" type="checkbox" name="clusteredMarkers" value="false" onclick="toggleClusteredMarkers(this.checked);">';
                    // view people clustered markers
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Distinct people clustered markers: ' +
                            '<input id="checkBoxPeopleClusteredMarkers" type="checkbox" name="peopleClusteredMarkers" value="false" onclick="togglePeopleClusteredMarkers(this.checked);">';
                    // view trajectories
                    //div.innerHTML +=
                    //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                    //'<br>Trajectories (' + numTrajectories + '): ' +
                    //'<input id="checkBoxTrajectories" type="checkbox" name="trajectories" value="false" onclick="toggleTrajectories(this.checked);">' +
                    //'Trajectories arrows ' +
                    //'<input id="checkBoxTrajectoriesDecorator" type="checkbox" name="trajectoriesdecorator" value="false" onclick="toggleTrajectoriesDecorator(this.checked);">' +
                    //'<br>(min. length: ' + minTrajectoryLength + ')';
                    // view clustered trajectories
                    div.innerHTML +=
                            //'<i style="background: ' + color_urls[i] + '"></i> ' +                 
                            '<br>Clustered trajectories (<span id="numClusteredTrajectories">' + numClusteredTrajectories + '</span>): ' +
                            '<input id="checkBoxClusteredTrajectories" type="checkbox" name="clusteredTrajectories" value="false" onclick="toggleClusteredTrajectories(this.checked);">' +
                            '<br>(' + String.fromCharCode(0x03B5) + ': ' + eps + ', minLns: ' + minLns + ')' +
                            '<br> ' +
                            '<a id="unselect" style="cursor:pointer" onclick="downSlider(\'clusteredTrajectories\',1,1,0);">&#10094;</a>&nbsp;&nbsp;&nbsp;' +
                            '<input id="sliderclusteredTrajectories" type="range" min="0" max="300" value="2" step="1" style="width: 250px;" onchange="filterClusteredTrajectories(\'clusteredTrajectories\',this.value);">' +
                            '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a id="unselect" style="cursor:pointer" onclick="upSlider(\'clusteredTrajectories\',1,1,300);">&#10095;</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;' +
                            '<span id="rangeclusteredTrajectories">2</span>';
                    // disable interaction of this div with map
                    if (!L.Browser.touch) {
                        L.DomEvent.disableClickPropagation(div);
                        L.DomEvent.on(div, 'mousewheel', L.DomEvent.stopPropagation);
                    } else {
                        L.DomEvent.on(div, 'click', L.DomEvent.stopPropagation);
                    }
                    return div;
                };
                // add legend to map
                legend.addTo(map);
                //map.fitBounds(coordinatesLine.getBounds());
                //map.setView([57.505, -0.01], 13);
            </script>
        </div> <!-- div container -->
    </body>
</html>
