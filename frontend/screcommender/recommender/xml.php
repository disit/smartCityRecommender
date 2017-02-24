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
// apt-get install php-pear
// apt-get install php5-xsl
// pear install XML_Serializer-beta
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

function json_to_xml($json) {
    $serializer = new XML_Serializer();
    $obj = json_decode($json);

    if ($serializer->serialize($obj)) {
        return $serializer->getSerializedData();
    } else {
        return null;
    }
}

function getJSON($id) {
    global $config;
    $config['table'] = "recommendations_log";

    //CONNECT
    $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    //GET DATA
    $sql = "SELECT recommendations FROM recommender." . $config['table'] . " WHERE id = " . $id;
    $result = mysqli_query($link, $sql) or die(mysqli_error());

    while ($row = mysqli_fetch_assoc($result)) {
        $json = $row["recommendations"];
    }

    //close connection
    mysqli_close($link);

    return $json;
}

function displayJSON($id) {
    //$json = "{\"Hotel\":[{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5682,43.5252],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Rest_home\",\"note\":\"\",\"website\":\"\",\"address\":\"VIA PASCOLI\",\"distance\":0.19028502128379646,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/f14e5672aeddea633163c96f0f899e02\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Giovanni_Pascoli\"],\"civic\":\"5\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"055980340\",\"name\":\"CASA DI RIPOSO DI MONTEVARCHI\",\"typeLabel\":\"Rest home\",\"fax\":\"055980340\",\"email\":\"\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5638,43.5309],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"\",\"website\":\"www.bbnessunluogoelontano.blogspot.com\",\"address\":\"VIA MASSIMILIANO SOLDANI\",\"distance\":0.8719388969618738,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/c4805952e3b1480fb2b30b546a61bf2a\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[],\"civic\":\"3\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"3339418317\",\"name\":\"NESSUN_LUOGO_E'_LONTANO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"nessunluogoel@gmail.com\"}}],\"type\":\"FeatureCollection\"}},{\"Service\":{\"features\":[{\"geometry\":{\"coordinates\":[11.5634,43.5319],\"type\":\"Point\"},\"id\":1,\"type\":\"Feature\",\"properties\":{\"serviceType\":\"Accommodation_Boarding_house\",\"note\":\"Riscaldamento;Asciugacapelli;Accesso Internet;Servizio Colazione in Camera;Accesso con Vetture Private;Lampada Esterna;Edificio di Valore Storico;Colazione Compresa;Rifornimento benzina immediate vicinanze\",\"website\":\"www.bbnessunluogoelontano.blogspot.com\",\"address\":\"VIA MASSIMILIANO SOLDANI\",\"distance\":0.98743588444376,\"city\":\"MONTEVARCHI\",\"serviceUri\":\"http://www.disit.org/km4city/resource/ca9951436c524e2f1ac5697a50786bd2\",\"description\":\"\",\"description2\":\"\",\"linkDBpedia\":[\"http://it.dbpedia.org/resource/Massimiliano_Soldani_Benzi\"],\"civic\":\"3\",\"multimedia\":\"\",\"cap\":\"52025\",\"province\":\"AR\",\"phone\":\"3339418317\",\"name\":\"NESSUN_LUOGO_E'_LONTANO\",\"typeLabel\":\"Boarding house\",\"fax\":\"\",\"email\":\"nessunluogoel@gmail.com\"}}],\"type\":\"FeatureCollection\"}}],\"Weather\":{\"head\":{\"location\":\"MONTEVARCHI\",\"vars\":[\"day\",\"description\",\"minTemp\",\"maxTemp\",\"instantDateTime\"]},\"results\":{\"bindings\":[{\"maxTemp\":{\"type\":\"literal\",\"value\":\"23\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Venerdi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"17\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"24\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Sabato\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"12\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"22\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Domenica\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"12\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Lunedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}},{\"maxTemp\":{\"type\":\"literal\",\"value\":\"\"},\"instantDateTime\":{\"type\":\"literal\",\"value\":\"2015-09-25T09:12:02+02:00\"},\"description\":{\"type\":\"literal\",\"value\":\"poco nuvoloso\"},\"day\":{\"type\":\"literal\",\"value\":\"Martedi\"},\"minTemp\":{\"type\":\"literal\",\"value\":\"\"}}]}}}";
    $xml = json_to_xml(getJSON($id));
    $xslt = new SimpleXMLElement('<xsl:stylesheet version="1.0"
        xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    
       <xsl:template match="*">
           <ul>
                <li><xsl:value-of select="local-name()"/>
                 <xsl:apply-templates/>
                </li>
           </ul>
        </xsl:template>
    </xsl:stylesheet>');

    $xml = new SimpleXMLElement($xml);
    $xsl_processor = new XSLTProcessor();
    $xsl_processor->importStylesheet($xslt);

    include_once "header.php"; //include header
    include_once "settings.php";
    echo "<div id='resultsTable'>";
    echo $xsl_processor->transformToXml($xml);
    echo "</div>";
}
?>

<html>
    <head>
    <head>
        <title>Recommendations' JSON</title>
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
    include_once "XML/Serializer.php";
    displayJSON($_REQUEST["id"]);
    ?>
</body>
</html>