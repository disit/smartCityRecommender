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
include_once "Polyline.php";

// encoded polyline as returned by OSRM
//https://github.com/Project-OSRM/osrm-backend
//http://localhost:5000/viaroute?loc=43.7727,11.2532&loc=43.71328,11.22361
$encoded = "q~occB{}brX}KaHyY{QaW}OoN{IoHuEcIcFeM}HoKyGuBsAZ}Ip@aUXaZIqT_@sUi@kNgCk`@wAePsCwTwDuTsG}YwIsZgFuMmB_F_HmQ}NoVgFyGoCsDmK}KgLaJuHeEgEyCiHcFgE}BeGo@mIKoQW{JwBwHeFmFmDePcNuJqIwBmByg@ec@{EiEmU}RoGkDwPoGeD|K{U`z@_Vpy@}\\pbAsJ}B}NqCyEy@yI}A_WeFeSyEqToJu^_Nmv@ge@{MyI}TcJkTeGcg@oMgCq@";
$points = Polyline::Decode($encoded);
// list of tuples
$points = Polyline::Pair($points);
var_dump($points);
?>
