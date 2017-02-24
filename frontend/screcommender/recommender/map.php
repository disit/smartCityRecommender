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

//http://openlayers.org/en/v3.9.0/examples/
//http://openlayers.org/en/v3.9.0/examples/simple.html
//http://openlayers.org/en/v3.9.0/examples/vector-layer.html
//http://jsfiddle.net/6RS2z/356/
//http://wiki.openstreetmap.org/wiki/OpenLayers_Simple_Example
// get centroid from an array of coordinates (timestamp = > coordinates)
//http://www.geomidpoint.com/example.html
function getCentroid($data) {
    if (!is_array($data))
        return FALSE;

    $num_coords = count($data);

    $x = 0.0;
    $y = 0.0;
    $z = 0.0;

    foreach ($data as $timestamp => $coord) {
        $lat = $coord["latitude"] * pi() / 180;
        $lon = $coord["longitude"] * pi() / 180;

        $a = cos($lat) * cos($lon);
        $b = cos($lat) * sin($lon);
        $c = sin($lat);

        $x += $a;
        $y += $b;
        $z += $c;
    }

    $x /= $num_coords;
    $y /= $num_coords;
    $z /= $num_coords;

    $lon = atan2($y, $x);
    $hyp = sqrt($x * $x + $y * $y);
    $lat = atan2($z, $hyp);

    return array($lat * 180 / pi(), $lon * 180 / pi());
}

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
    $results = array();
    $coordinates = array();
    $sql = "SELECT recommendations, timestamp, latitude, longitude FROM recommender." . $config['table'] . " WHERE user = '" . $_REQUEST["user"] . "' AND DAY(timestamp) = " . $_REQUEST["day"] . " AND MONTH(timestamp) = " . $_REQUEST["month"] . " AND YEAR(timestamp) = " . $_REQUEST["year"] . " ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $results[$row["timestamp"]] = $row["recommendations"];
        $coordinates[$row["timestamp"]]["latitude"] = $row["latitude"];
        $coordinates[$row["timestamp"]]["longitude"] = $row["longitude"];
    }
    return $coordinates;
}

$coordinates = getCoordinates();
$centroid = getCentroid($coordinates);
?>
<!DOCTYPE html>
<html>
    <head>
        <title>Path</title>
        <script src="https://code.jquery.com/jquery-1.11.2.min.js"></script>
        <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/css/bootstrap.min.css">
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.4/js/bootstrap.min.js"></script>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ol3/3.5.0/ol.css" type="text/css">
        <script src="https://cdnjs.cloudflare.com/ajax/libs/ol3/3.5.0/ol.js"></script>
        <link rel="stylesheet" type="text/css" href="css/reset.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <link rel="stylesheet" type="text/css" href = "css/jquery-ui.css"/>
        <script type="text/javascript" src="javascript/jquery-ui.min.js"></script>
        <script type="text/javascript" src="javascript/jquery.redirect.js"></script>
    </head>
    <body>
        <?php
        //include_once "header.php"; //include header
        include_once "settings.php";
        ?>
        <div class="container-fluid">

            <div class="row-fluid">
                <div class="span12">
                    <div id="map" class="map"></div>
                </div>
            </div>

        </div>
        <script>
            var vectorSource = new ol.source.Vector({
                //create empty vector
            });
            //create a bunch of icons and add to source vector
<?php
foreach ($coordinates as $timestamp => $coord) {
    echo
    "var iconFeature = new ol.Feature({\n" .
    "geometry: new\n" .
    "ol.geom.Point(ol.proj.transform([" . $coord["longitude"] . "," . $coord["latitude"] . "], 'EPSG:4326', 'EPSG:3857')),\n" .
    "name: '" . $timestamp . "'\n" .
    //"population: 4000," .
    //"rainfall: 500" .
    "});\n" .
    "vectorSource.addFeature(iconFeature);\n";
}
?>
            //create the style
            var iconStyle = new ol.style.Style({
                image: new ol.style.Icon(/** @type {olx.style.IconOptions} */ ({
                    anchor: [0.5, 46],
                    anchorXUnits: 'fraction',
                    anchorYUnits: 'pixels',
                    opacity: 0.75,
                    //src: 'http://openlayers.org/en/v3.9.0/examples/data/icon.png'
                    src: 'http://api.tiles.mapbox.com/mapbox.js/v2.1.3/images/marker-icon.png'
                }))
            });
            //add the feature vector to the layer vector, and apply a style to whole layer
            var vectorLayer = new ol.layer.Vector({
                source: vectorSource,
                style: iconStyle
            });
            var map = new ol.Map({
                //layers: [new ol.layer.Tile({source: new ol.source.OSM()}), vectorLayer], // street map
                layers: [new ol.layer.Tile({
                        source: new ol.source.XYZ({
                            url: 'https://api.mapbox.com/v4/mapbox.streets-satellite/{z}/{x}/{y}.png?access_token=pk.eyJ1IjoicGJlbGxpbmkiLCJhIjoiNTQxZDNmNDY0NGZjYTk3YjlkNTAzNWQwNzc0NzQwYTcifQ.CNfaDbrJLPq14I30N1EqHg'
                        })
                    }), vectorLayer],
                /*layers: [
                 new ol.layer.Tile({
                 source: new ol.source.OSM()
                 })
                 ],*/
                controls: ol.control.defaults({
                    attributionOptions: /** @type {olx.control.AttributionOptions} */ ({
                        collapsible: false
                    })
                }),
                target: 'map',
                view: new ol.View({
                    center: ol.proj.transform([<?php echo $centroid[1]; ?>, <?php echo $centroid[0]; ?>], 'EPSG:4326', 'EPSG:3857'),
                    zoom: 16
                })
            });

        </script>
    </body>
</html>