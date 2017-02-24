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

//$fp = fopen("/var/www/html/log.txt", "at");
//fwrite($fp, var_export($sql, true) . "\n");
//fclose($fp);
if (!isset($_REQUEST))
    exit();

global $config;
$config['table'] = "groups_settings";

//CONNECT
$link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

/* check connection */
if (mysqli_connect_errno()) {
    printf("Connection failed: %s\n", mysqli_connect_error());
    exit();
}

foreach ($_REQUEST as $key => $value) {
    $value_name = split("_", $key);
    $sql = "UPDATE recommender." . $config['table'] . " SET `" . $value_name[1] . "` = '" . $value . "' WHERE description = '" . $value_name[0] . "'";
    $result = mysqli_query($link, $sql); //or die(mysqli_error());
    if (!$result) {
        $error = mysqli_error($link);
        break;
    }
}
//close connection
mysqli_close($link);

if (isset($error)) {
    //header('HTTP/1.1 500 Internal Server Error');
    echo $error;
}
//$fp = fopen("/var/www/html/recommender/log.txt", "at");
//fwrite($fp, var_export($sql, true));
//fclose($fp);
?>
