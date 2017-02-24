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

// calculate route from coordinates
// using Leaflet Routing Machine online service
// http://www.liedman.net/leaflet-routing-machine/
// https://github.com/perliedman/leaflet-routing-machine
// get path coordinates
function getCoordinates() {
    include_once "settings.php";
    //global $config;
    $config['table'] = "recommendations_log";

    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    // GET DATA
    $coordinates = "";
    $sql = "SELECT recommendations, timestamp, latitude, longitude FROM recommender." . $config['table'] . " WHERE user = '" . $_REQUEST["user"] . "' AND DATE(timestamp) = '" . $_REQUEST["year"] . "-" . $_REQUEST["month"] . "-" . $_REQUEST["day"] . "' ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $coordinates = $coordinates . ",L.latLng(" . $row["latitude"] . "," . $row["longitude"] . ")";
    }
    return substr($coordinates, 1);
}
?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>User Route</title>
        <link rel="stylesheet" href="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.css" />
        <link rel="stylesheet" href="javascript/maps/leaflet-routing/leaflet-routing-machine.css" />
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
        <script src="http://cdn.leafletjs.com/leaflet-0.7.3/leaflet.js"></script>
        <script src="javascript/maps/leaflet-routing/leaflet-routing-machine.js"></script>
        <script>
            var map = L.map('map');

            var mbAttr = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, ' +
                    '<a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, ' +
                    'Imagery © <a href="http://mapbox.com">Mapbox</a>';

            // for satellite map use mapbox.streets-satellite in the url
            L.tileLayer('https://api.mapbox.com/v4/mapbox.streets/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicGJlbGxpbmkiLCJhIjoiNTQxZDNmNDY0NGZjYTk3YjlkNTAzNWQwNzc0NzQwYTcifQ.CNfaDbrJLPq14I30N1EqHg', {
                attribution: mbAttr
            }).addTo(map)

            /*L.tileLayer('http://{s}.tile.osm.org/{z}/{x}/{y}.png', {
             attribution: '© OpenStreetMap contributors'
             }).addTo(map);*/

            L.Routing.control({
                waypoints: [
<?php echo getCoordinates(); ?>
                ],
                routeWhileDragging: true,
                //lineOptions: [{color: 'black', opacity: 0.15, weight: 9}, {color: 'white', opacity: 0.8, weight: 6}, {color: 'red', opacity: 1, weight: 2}]
            }).addTo(map);
        </script>
    </body>
</html>
