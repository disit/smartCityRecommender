<html xmlns="http://www.w3.org/1999/xhtml"> 
    <!-- Recommender
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
   along with this program.  If not, see <http://www.gnu.org/licenses/>. --> 
    <head> 
        <title><?php
            if (isset($_REQUEST["title"])) {
                echo $_REQUEST["title"];
            } else {
                echo "Recommender";
            }
            ?>
        </title>
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <link rel="stylesheet" type="text/css" href="../index.css" />
    </head>
    <body>
        <?php
        //header("location: sce"); 
        include_once "header.php";
        ?>

        <ul class="rig columns-3">
            <li>
                <a href="user_general.php?title=<?php echo urlencode("Recommender - Combined Stats for City Users&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/users.png" /><br>Combined Stats for City Users</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=all&title=<?php echo urlencode("Recommender - Stats for User Profile All&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile All</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=citizen&title=<?php echo urlencode("Recommender - Stats for User Profile Citizen&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile Citizen</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=commuter&title=<?php echo urlencode("Recommender - Stats for User Profile Commuter&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile Commuter</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=student&title=<?php echo urlencode("Recommender - Stats for User Profile Student&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile Student</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=tourist&title=<?php echo urlencode("Recommender - Stats for User Profile Tourist&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile Tourist</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=disabled&title=<?php echo urlencode("Recommender - Stats for User Profile Disabled&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile Disabled</h3></a>
            </li>
            <li>
                <a href="user_profile.php?profile=operator&title=<?php echo urlencode("Recommender - Stats for User Profile Operator&nbsp;"); ?>" target="_blank">
                    <h3><img src="images/user.png" /><br>Stats for User Profile Operator</h3></a>
            </li>
        </ul>
    </body>
</html>
