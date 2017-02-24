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
?>
<script type="text/javascript" src="../../../screcommender/recommender/javascript/date_time.js"></script>
<div class="header" id="gradient">
    <div class="left-image"><a  href="../../../">
            <img class="logo" src="../../../screcommender/recommender/images/logo1.png"/>
        </a>
    </div>
    <div class="text"><?php
        if (isset($_REQUEST["title"]))
            echo $_REQUEST["title"];
        else
            echo "Recommender ";
        ?><a href="http://www.disit.org/" target="_blank"><img class="info" src="../../../screcommender/recommender/images/info-icon.png"/></a><br><span class="subtext"><a href="http://www.disit.org" target="_blank">DISIT - Distributed Systems and Internet Technologies Lab</a></span>
        <span id="date_time"></span>
        <script type="text/javascript">window.onload = date_time('date_time');</script>
    </div>
</div>
