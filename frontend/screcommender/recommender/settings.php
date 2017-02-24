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
$config['tomcat'] = "localhost"; // ip of tomcat service
$config['host'] = "localhost"; //ip of database
$config['user'] = "user"; //db username
$config['pass'] = "password"; //db password
$config['database'] = "recommender"; //db name
$config['wifi_host'] = "localhost"; //ip of wifi database
$config['wifi_user'] = "user"; //db username
$config['wifi_pass'] = "password"; //db password
$config['wifi_database'] = "wifi"; //db name
$config['sensors_host'] = "localhost"; //ip of sensors database
$config['sensors_user'] = "user"; //db username
$config['sensors_pass'] = "password"; //db password
$config['sensors_database'] = "sensors"; //db name
$config["solr_host"] = "localhost";
$config["solr_port"] = "8983";
$config["solr_collection_ap"] = "solr/aps";
$config["solr_collection"] = "solr/collection1";
$config['access_log_host'] = "localhost"; //ip of access_log database
$config['access_log_user'] = "user"; //access_log db username
$config['access_log_pass'] = "password"; //access_log db password
$config['access_log_database'] = "ServiceMap"; //access_log db name
$config['nicefields'] = true; //true or false | "Field Name" or "field_name"
$config['perpage'] = 20;
$config['pagelinks'] = 50; // max number of page links, if not set 50 will be used as a default value when calling $Pagination->showPageNumbers  
$config['showpagenumbers'] = true; //true or false
$config['showprevnext'] = true; //true or false
$config['refreshTime'] = 3000; //refresh time in ms for push mode views
$config['servicemapurl'] = "http://servicemap.disit.org/WebAppGrafo/api/v1/"; //"http://www.disit.org/ServiceMap/api/v1/";
$config["mongodb_url"] = "mongodb://localhost:27017"; // MongoDB url streaming
$config["mongodb_flows_url"] = "mongodb://localhost:27017"; // MongoDB url flows (recommender and sensors)
$config['sparql_url'] = "http://servicemap.disit.org/WebAppGrafo/sparql"; //"http://www.disit.org/ServiceMap/sparql";
//https://github.com/Project-OSRM/osrm-backend
$config['osrm_server_url'] = "http://localhost:5000";
//http://wiki.openstreetmap.org/wiki/Nominatim
$config['nominatim_server_url'] = "http://localhost/nominatim";
// km
$config['distinct_location_radius'] = 0.025;
// min, max latitude, longitude
$config["min_latitude"] = 43.5226;
$config["max_latitude"] = 44.1565;
$config["min_longitude"] = 10.8682;
$config["max_longitude"] = 11.7677;
// refresh timeout (ms)
$config["timeout"] = 10000;
// legend colors values
$config["legend_color_blue"] = 0;
$config["legend_color_cyan"] = .1;
$config["legend_color_green"] = .2;
$config["legend_color_yellowgreen"] = .3;
$config["legend_color_yellow"] = .4;
$config["legend_color_gold"] = .5;
$config["legend_color_orange"] = .6;
$config["legend_color_darkorange"] = .7;
$config["legend_color_tomato"] = .8;
$config["legend_color_orangered"] = .9;
$config["legend_color_red"] = 1.0;
//error_reporting(0); //disable error reporting
?>
