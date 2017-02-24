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

//http://jsfiddle.net/Rodrigoson6/2yfebsgn/
// get speed distance data for a profile (JSON)
function getSpeedDistanceJSON($profile, $link) {
    $distance = array();
    $users_query = "SELECT user FROM recommender.recommendations_log WHERE profile = '" . $profile . "'";
    $users_query_result = mysqli_query($link, $users_query) or die(mysqli_error());
    while ($row_users = mysqli_fetch_assoc($users_query_result)) {
        $i = 0;
        $latitude = 0;
        $longitude = 0;
        $timestamp = "";
        $sql = "SELECT latitude, longitude, timestamp FROM recommender.recommendations_log WHERE user = '" . $row_users["user"] . "' AND `mode` = 'gps' ORDER BY id ASC";
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
    return array($keys, json_encode(array(array("name" => "speed_distance", "data" => $dst))));
}

/* function getSpeedRange($speed) {
  if ($speed < 5) {
  return "0 - 5 km/h";
  } else if ($speed < 20) {
  return "5 - 20 km/h";
  } else if ($speed < 130) {
  return "20 - 130 km/h";
  } else if ($speed < 300) {
  return "130 - 300 km/h";
  } else {
  return ">= 300 km/h";
  }
  } */

function getSpeedRange($index) {
    switch ($index) {
        case 1:
            return "0 - 5 km/h";
            break;
        case 2:
            return "5 - 20 km/h";
        case 3:
            return "20 - 130 km/h";
            break;
        case 4:
            return "130 - 300 km/h";
            break;
        case 5:
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
    } else if ($speed < 130) {
        return 3;
    } else if ($speed < 300) {
        return 4;
    } else {
        return 5;
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

$distance_all = getSpeedDistanceJSON("all", $link);
$distance_all_keys = json_encode($distance_all[0]);
$distance_all = $distance_all[1];

$distance_citizen = getSpeedDistanceJSON("citizen", $link);
$distance_citizen_keys = json_encode($distance_citizen[0]);
$distance_citizen = $distance_citizen[1];

$distance_commuter = getSpeedDistanceJSON("commuter", $link);
$distance_commuter_keys = json_encode($distance_commuter[0]);
$distance_commuter = $distance_commuter[1];

$distance_student = getSpeedDistanceJSON("student", $link);
$distance_student_keys = json_encode($distance_student[0]);
$distance_student = $distance_student[1];

$distance_tourist = getSpeedDistanceJSON("tourist", $link);
$distance_tourist_keys = json_encode($distance_tourist[0]);
$distance_tourist = $distance_tourist[1];

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

    </head>
    <body>
        <div id='container1'> <!-- div container -->     
            <div id='profile'>
            </div>
            <div id="all" style="height: 400px"></div>
            <div id="citizen" style="height: 400px"></div>
            <div id="commuter" style="height: 400px"></div>
            <div id="student" style="height: 400px"></div>
            <div id="tourist" style="height: 400px"></div>
        </div>

        <script type="text/javascript">
            $(function () {
                // create the charts
                $('#all').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    /*plotOptions: {
                     scatter: {
                     lineWidth: 2
                     }
                     },*/
                    /*xAxis: {
                     //type: 'logarithmic',
                     //minorTickInterval: 0.1,
                     //min: 0.1,
                     //max: 300,
                     tickInterval: 10,
                     title: {
                     text: 'Speed (km/h)'
                     },
                     },*/
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
                        text: 'Speed/Distance (All)'
                    },
                    series: JSON.parse('<?php echo $distance_all; ?>')
                });
                $('#citizen').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    /*plotOptions: {
                     scatter: {
                     lineWidth: 2
                     }
                     },*/
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
                        text: 'Speed/Distance (Citizen)'
                    },
                    series: JSON.parse('<?php echo $distance_citizen; ?>')
                });
                $('#commuter').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    /*plotOptions: {
                     scatter: {
                     lineWidth: 2
                     }
                     },*/
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
                        text: 'Speed/Distance (Commuter)'
                    },
                    series: JSON.parse('<?php echo $distance_commuter; ?>')
                });
                $('#student').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    /*plotOptions: {
                     scatter: {
                     lineWidth: 2
                     }
                     },*/
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
                        text: 'Speed/Distance (Student)'
                    },
                    series: JSON.parse('<?php echo $distance_student; ?>')
                });
                $('#tourist').highcharts({
                    chart: {
                        type: 'column',
                        alignTicks: false
                    },
                    /*plotOptions: {
                     scatter: {
                     lineWidth: 2
                     }
                     },*/
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
                        text: 'Speed/Distance (Tourist)'
                    },
                    series: JSON.parse('<?php echo $distance_tourist; ?>')
                });
            });
        </script>
    </body>