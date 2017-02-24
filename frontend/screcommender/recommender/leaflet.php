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
//https://github.com/emcconville/google-map-polyline-encoding-tool
//https://developers.google.com/maps/documentation/utilities/polylinealgorithm
// attention: coordinates are scaled by 10
//https://github.com/Project-OSRM/osrm-backend/issues/713
// leaflet label plugin https://github.com/Leaflet/Leaflet.label
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

// get path coordinates
function getCoordinates() {
    global $config;
    $config['table'] = "recommendations_log";

    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    // GET DATA
    $coordinates = array();
    $sql = "SELECT latitude, longitude, timestamp FROM recommender." . $config['table'] . " WHERE user = '" . $_REQUEST["user"] . "' AND DAY(timestamp) = " . $_REQUEST["day"] . " AND MONTH(timestamp) = " . $_REQUEST["month"] . " AND YEAR(timestamp) = " . $_REQUEST["year"] . " ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $coordinates[] = array($row["latitude"], $row["longitude"], $row["timestamp"]);
    }
    return $coordinates;
}

function printCoordinates($coordinates) {
    $javascript = "";
    foreach ($coordinates as $coordinate) {
        $javascript = $javascript . ",L.latLng(" . $coordinate[0] . "," . $coordinate[1] . ")\n";
    }
    return substr($javascript, 1);
}

function printMarkers($coordinates) {
    $markers = "";
    foreach ($coordinates as $coordinate) {
        $markers .= "L.marker([" . $coordinate[0] . "," . $coordinate[1] . "]).addTo(map);\n";
        $markers = $markers .="L.marker([" . $coordinate[0] . "," . $coordinate[1] . "]).bindLabel('" . $coordinate[2] . "', { noHide: true }).addTo(map);\n";
    }
    return $markers;
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

$coordinates = getCoordinates();
$coordinates_javascript = printCoordinates($coordinates);
$markers_javascript = printMarkers($coordinates);
//$route_javascript = printShortestRoute($coordinates); // the shortest route path with given coordinates
?>
<html>
    <head>
        <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.css" />
        <script src="http://cdn.leafletjs.com/leaflet-0.7.5/leaflet.js"></script>
        <!-- leaflet label plugin includes -->
        <script src="javascript/maps/leaflet-label-plugin/Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/BaseMarkerMethods.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/Marker.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/CircleMarker.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/Path.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/Map.Label.js"></script>
        <script src="javascript/maps/leaflet-label-plugin/FeatureGroup.Label.js"></script>
        <link rel="stylesheet" href="javascript/maps/leaflet-label-plugin/leaflet.label.css" />
        <style>
            .map {
                position: absolute;
                width: 100%;
                height: 100%;
            }
        </style>
    </head>
    <body>
        <div id="map" class="map"></div>
        <script type="text/javascript">
            var map = L.map('map');
            /*L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
             attribution: '© OpenStreetMap contributors'
             }).addTo(map);*/
            var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                    '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                    'Imagery © <a href="http://mapbox.com">Mapbox</a>';

            // for satellite map use mapbox.streets-satellite in the url
            L.tileLayer('https://api.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicGJlbGxpbmkiLCJhIjoiNTQxZDNmNDY0NGZjYTk3YjlkNTAzNWQwNzc0NzQwYTcifQ.CNfaDbrJLPq14I30N1EqHg', {
                attribution: mbAttr,
                maxZoom: 23,
            }).addTo(map)
            var coordinateList = [<?php echo $coordinates_javascript; ?>];
            var routeList = [<?php echo $route_javascript; ?>];

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
            coordinatesLine.addTo(map);
            //routeLine.addTo(map);
<?php echo $markers_javascript; ?>
            map.fitBounds(coordinatesLine.getBounds());
            //map.setView([57.505, -0.01], 13);
        </script>
    </body>
</html>