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

//http://jsonviewer.stack.hu/
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

function getServiceURIs($json) {
    $result = array();
    foreach ($json as $k1 => $v1) {
        foreach ($v1 as $k2 => $v2) {
            foreach ($v2["Service"]["features"] as $k3 => $v3) {
                foreach ($v3 as $k4 => $v4) {
                    if ($k4 == "properties")
                        foreach ($v3[$k4] as $k5 => $v5) {
                            if ($k5 == "serviceUri")
                                $result[] = $v5;
                        }
                }
            }
        }
    }
    return $result;
}

// get the profile name for a user
function getProfile($user) {
    $id = intval(str_replace($user, ".", ""));
    switch ($id) {
        case 0:
            return "student";
        case 1:
            return "commuter";
        case 2:
            return "tourist";
        case 3:
            return "citizen";
        case 4:
            return "all";
    }
}

// get the groups for a user
function getGroups($user) {
    global $config;
    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $sql = "SELECT `group` FROM recommender.groups_" . getProfile($user) . " ORDER BY id ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $groups = array();
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row["group"];
    }
    return $groups;
}

function printEmptyTable($groups) {
    //start first row style
    $tr_class = "class='odd'";
    echo "<td><table>\n";
    for ($i = 0; $i < 3; $i++) {
        echo "<tr " . $tr_class . " >\n";
        echo "<td><br><br><br><br><br></td>\n";
        echo "</tr>\n";
        //switch row style
        if ($tr_class == "class='odd'") {
            $tr_class = "class='even'";
        } else {
            $tr_class = "class='odd'";
        }
    }
    echo "</table></td>\n";
}

// display a json
function displayJSON() {
    global $config;
    $config['table'] = "recommendations_log";

    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }

    // title data div
    /* echo "<div class='scheduler'>";
      echo "<h3><b>User: </b>" . $row["user"];
      echo "<h3><b>Date: </b>" . $row["timestamp"] . "</h3>";
      $servicemapurl = $config["servicemapurl"] . "?selection=" . $row["latitude"] . ";" . $row["longitude"] . "&categories=Service&maxdists=1&format=html";
      echo "<h3><a title=\"View on Map\" target=\"_blank\" href=\"" . $servicemapurl . "\">"
      . "<img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/></a>"
      . "<b>Latitude: </b>" . $row["latitude"] . ", <b>Longitude: </b>" . $row["longitude"];
      echo "</div>"; */

    // PRINT TABLE
    echo "<div id='recommendationsTable'>\n";
    echo "<table>\n";

    // PRINT TABLE HEADER (TIMESTAMPS)
    echo "<tr>";
    $sql = "SELECT distinct(timestamp) FROM recommender." . $config['table'] . " WHERE user = '" . $_REQUEST["user"] . "' AND DAY(timestamp) = " . $_REQUEST["day"] . " AND MONTH(timestamp) = " . $_REQUEST["month"] . " AND YEAR(timestamp) = " . $_REQUEST["year"] . " ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    $timestamps = array();
    echo "<th></th>";
    while ($row = mysqli_fetch_assoc($result)) {
        $timestamps[] = $row["timestamp"];
        echo "<th>" . $row["timestamp"] . "</th>";
    }
    echo "</tr>\n";

    // GET DATA
    $results = array();
    $sql = "SELECT recommendations, timestamp FROM recommender." . $config['table'] . " WHERE user = '" . $_REQUEST["user"] . "' AND DAY(timestamp) = " . $_REQUEST["day"] . " AND MONTH(timestamp) = " . $_REQUEST["month"] . " AND YEAR(timestamp) = " . $_REQUEST["year"] . " ORDER BY timestamp ASC";
    $result = mysqli_query($link, $sql) or die(mysqli_error());
    while ($row = mysqli_fetch_assoc($result)) {
        $results[$row["timestamp"]] = $row["recommendations"];
    }

    // PRINT TABLE DATA
    $groups = getGroups($_REQUEST["user"]);
    foreach ($groups as $group) {
        echo "<tr><td><b>" . $group . "</b></td>\n";
        foreach ($timestamps as $timestamp) {
            $json = objectToArray(json_decode($results[$timestamp]));
            if (count($json[$group]) == 0) {
                printEmptyTable($groups);
                continue;
            }
            //start first row style
            $tr_class = "class='odd'";
            echo "<td><table>\n";
            foreach ($json[$group] as $k2 => $v2) {
                foreach ($v2["Service"]["features"] as $k3 => $v3) {
                    foreach ($v3 as $k4 => $v4) {
                        if ($k4 == "properties") {
                            echo "<tr " . $tr_class . " >\n";
                            $servicemapurl = $config["servicemapurl"] . "?serviceUri=" . $v3[$k4]["serviceUri"] . "&format=html";
                            echo "<td><a title=\"View on Map\" target=\"_blank\" href=\"" . $servicemapurl . "\"><img id='icon' src='images/map_pin.png' alt='edit' height='16' width='16'/></a>";
                            if ($v3[$k4]["name"] != "")
                                echo $v3[$k4]["name"] . "<br>";
                            echo $v3[$k4]["address"] . " " . $v3[$k4]["civic"] . " " . $v3[$k4]["cap"] . " " . $v3[$k4]["city"] . "<br>";
                            /* if ($v3[$k4]["description"] != "")
                              echo $v3[$k4]["description"] . "<br>";
                              if ($v3[$k4]["description2"] != "")
                              echo $v3[$k4]["description2"] . "<br>"; */
                            //if ($v3[$k4]["phone"] != "")
                            echo "Tel: " . $v3[$k4]["phone"] . " " . ($v3[$k4]["fax"] != "" ? " Fax: " . $v3[$k4]["fax"] : "") . "<br>";
                            if ($v3[$k4]["website"] != "")
                                echo "<a href=\"http://" . $v3[$k4]["website"] . "\"> " . $v3[$k4]["website"] . "</a>" . " " . ($v3[$k4]["email"] != "" ? "<a href=\"mailto:" . $v3[$k4]["email"] . "\" target=\"_top\">" . $v3[$k4]["email"] . "</a>" : "") . "<br>";
                            echo "Longitude: " . $v3["geometry"]["coordinates"][1] . " Latitude: " . $v3["geometry"]["coordinates"][0] . " Distance: " . $v3[$k4]["distance"] . " km</td>\n";
                            echo "</tr>\n";

                            //switch row style
                            if ($tr_class == "class='odd'") {
                                $tr_class = "class='even'";
                            } else {
                                $tr_class = "class='odd'";
                            }
                        }
                    }
                }
            }
            // fill empty rows
            for ($j = count($json[$group]); $j < 3; $j++) {
                echo "<tr " . $tr_class . " ><td></td></tr>";
                //switch row style
                if ($tr_class == "class='odd'") {
                    $tr_class = "class='even'";
                } else {
                    $tr_class = "class='odd'";
                }
            }
            echo "</table></td>\n";
        }
        echo "</tr>";
    }
    echo "</table></div>\n";

    //close connection
    mysqli_close($link);
}

// print empty table
//$json = json_decode("{\"Services and Utilities\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2492,43.8058],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Service_DigitalLocation\",\"note\":\"wifi.fid303cbba0142a32de586520a\",\"website\":\"\",\"address\":\"VIALE GAETANO PIERACCINI\",\"distance\":0.8791195092477824,\"city\":\"FIRENZE\",\"serviceUri\":\"http://www.disit.org/km4city/resource/ce25dbb863ef7aca3405f6a0bd4b12aa\",\"description\":\"Viale Pieraccini 6\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Gaetano_Pieraccini\"],\"civic\":\"8\",\"multimedia\":\"\",\"cap\":\"50139\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Firenze WIFI\",\"typeLabel\":\"Digital Location\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2492,43.8058],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Service_DigitalLocation\",\"note\":\"wifi.fid303cbba0142d5849839776\",\"website\":\"\",\"address\":\"VIALE GAETANO PIERACCINI\",\"distance\":0.8791195092477824,\"city\":\"FIRENZE\",\"serviceUri\":\"http://www.disit.org/km4city/resource/f341928665c0af4eab271f7db4a55439\",\"description\":\"Viale Pieraccini 6\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Gaetano_Pieraccini\"],\"civic\":\"8\",\"multimedia\":\"\",\"cap\":\"50139\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Firenze WIFI\",\"typeLabel\":\"Digital Location\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.2492,43.8058],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"TourismService_Wifi\",\"note\":\"\",\"website\":\"\",\"address\":\"VIALE PIERACCINI\",\"distance\":0.8791195092477824,\"city\":\"FIRENZE\",\"serviceUri\":\"http://www.disit.org/km4city/resource/wifi_210\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Gaetano_Pieraccini\"],\"civic\":\"6\",\"multimedia\":\"\",\"cap\":\"\",\"province\":\"FI\",\"phone\":\"\",\"name\":\"Firenze WIFI\",\"typeLabel\":\"Free WiFi point\",\"fax\":\"\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}}],\"Weather\":{\"head\":{\"location\":\"FIRENZE\",\"vars\":[\"day\",\"description\",\"minTemp\",\"maxTemp\",\"instantDateTime\"]},\"results\":{\"bindings\":[{\"maxTemp\":{\"type\":\"literal\",\"value\":\"19\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"coperto\"},\"day\":{\"type\":\"literal\",\"value\":\"Giovedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"16\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"24\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Venerdi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"13\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"25\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Sabato\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"11\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"velato\"},\"day\":{\"type\":\"literal\",\"value\":\"Domenica\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-24T14:47:00+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Lunedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}}]}}}");
/*  $json = objectToArray($json);
  $serviceURIs = getServiceURIs($json);
  foreach ($serviceURIs as $serviceURI)
  echo $serviceURI . "<br>"; */
//$json = "{\"Hotel\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5682,43.5252],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Rest_home\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA PASCOLI\",\"distance\":0.19028502128379646,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/f14e5672aeddea633163c96f0f899e02\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Giovanni_Pascoli\"],\"civic\":\"5\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"055980340\",\"name\":\"CASA DI RIPOSO DI MONTEVARCHI\",\"typeLabel\":\"Rest home\",\"fax\":\"055980340\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5638,43.5309],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"\",\"website\":\"www.bbnessunluogoelontano.blogspot.com\",\"address\":\"VIA MASSIMILIANO SOLDANI\",\"distance\":0.8719388969618738,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/c4805952e3b1480fb2b30b546a61bf2a\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[],\"civic\":\"3\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"3339418317\",\"name\":\"NESSUN_LUOGO_E'_LONTANO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"nessunluogoel@gmail.com\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5634,43.5319],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"Riscaldamento;Asciugacapelli;Accesso Internet;Servizio Colazione in Camera;Accesso con Vetture Private;Lampada Esterna;Edificio di Valore Storico;Colazione Compresa;Rifornimento benzina immediate vicinanze\",\"website\":\"www.bbnessunluogoelontano.blogspot.com\",\"address\":\"VIA MASSIMILIANO SOLDANI\",\"distance\":0.98743588444376,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/ca9951436c524e2f1ac5697a50786bd2\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Massimiliano_Soldani_Benzi\"],\"civic\":\"3\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"3339418317\",\"name\":\"NESSUN_LUOGO_E'_LONTANO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"nessunluogoel@gmail.com\"}}],\"type\":\"FeatureCollection\"}}],\"Weather\":{\"head\":{\"location\":\"MONTEVARCHI\",\"vars\":[\"day\",\"description\",\"minTemp\",\"maxTemp\",\"instantDateTime\"]},\"results\":{\"bindings\":[{\"maxTemp\":{\"type\":\"literal\",\"value\":\"23\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Venerdi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"17\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"24\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Sabato\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"12\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"22\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Domenica\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"12\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Lunedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Martedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}}]}}}";
//$json = objectToArray($json);
?>
<html>
    <head>
    <head>
        <title>Recommendations</title>
        <link rel="stylesheet" type="text/css" href="css/reset.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <link rel="stylesheet" type="text/css" href = "css/jquery-ui.css"/>
        <script type="text/javascript" src="javascript/jquery-2.1.0.min.js"></script>
        <script type="text/javascript" src="javascript/jquery-ui.min.js"></script>
        <script type="text/javascript" src="javascript/jquery.redirect.js"></script>
        <script type="text/javascript" src="javascript/sce.js"></script>
    </head>
</head>
<body>
    <?php
    include_once "header.php"; //include header
    include_once "settings.php";
    displayJSON();
    ?>
</body>
</html>
