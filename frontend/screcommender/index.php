<html xmlns="http://www.w3.org/1999/xhtml"> 
    <head> 
        <title>Recommender</title>
        <link rel="stylesheet" type="text/css" href="recommender/css/typography.css" />
        <link rel="stylesheet" type="text/css" href="index.css" />
    </head>
    <body>
        <div class="main">
            <div class="menu">
                <?php
                if (isset($_SESSION["username_recommender"])) {
                    $_REQUEST["username"] = $_SESSION["username_recommender"];
                }
                if (isset($_SESSION["password_recommender"])) {
                    $_REQUEST["password"] = $_SESSION["password_recommender"];
                }
                if (!isset($_REQUEST["username"]) || !isset($_REQUEST["password"])) {
                    if (isset($_SESSION["username_recommender"])) {
                        unset($_SESSION["username_recommender"]);
                    }
                    if (isset($_SESSION["password_recommender"])) {
                        unset($_SESSION["password_recommender"]);
                    }
                }
                $admin = password_verify($_REQUEST["username"], '...hash...') &&
                        password_verify($_REQUEST["password"], '...hash...');
                include_once "recommender/header.php";
                ?>

                <ul class="rig columns-3">
                    <?php
                    if ($admin) {
                        $_SESSION["username_recommender"] = $_REQUEST["password"];
                        $_SESSION["password_recommender"] = $_REQUEST["password"];
                        echo "<li>
                <a href=\"recommender/index.php?title=" . urlencode("Recommender - City Users and Stats&nbsp;") . "\">
                <h3><img src=\"recommender/images/sla.png\" /><br>City Users and Stats</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/recommendations_log_table.php?title=" . urlencode("Recommender - Recommendations Log&nbsp;") . "\">
                    <h3><img src=\"recommender/images/sla.png\" /><br>Recommendations Log</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/settings-list-general.php?title=" . urlencode("Recommender - General Settings&nbsp;") . "\">
                    <h3><img src=\"recommender/images/gear.png\" /><br>General Settings</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/settings-list-groups-general.php?title=" . urlencode("Recommender - Social Media Group Recommendations Settings&nbsp;") . "\">
                    <h3><img src=\"recommender/images/gear.png\" /><br>Social Media Group Recommendations Settings</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/settings-list-groups.php?title=" . urlencode("Recommender - Groups Recommendations Priorities&nbsp;") . "\">
                    <h3><img src=\"recommender/images/gear.png\" /><br>Groups Recommendations Priorities</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/scores.php?title=" . urlencode("Recommender - Class Scores&nbsp;") . "\">
                    <h3><img src=\"recommender/images/metric.png\" /><br>Class Scores</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/stats.php?title=" . urlencode("Recommender - General Stats&nbsp;") . "\">
                    <h3><img src=\"recommender/images/metric.png\" /><br>General Stats</h3></a>
                </li>";
                        echo "<li>
                <a href=\"recommender/users.php?title=" . urlencode("Recommender - City Users&nbsp;") . "\">
                    <h3><img src=\"recommender/images/users.png\" /><br>City Users</h3></a>
            </li>";
                        echo "<li>
                <a href=\"recommender/user.php?title=" . urlencode("Recommender - Statistics for City Users Types&nbsp;") . "\">
                    <h3><img src=\"recommender/images/graph.png\" /><br>Statistics for City Users Types</h3></a>
            </li>";
                        echo "<li>
                <a href=\"recommender/trajectories_clusters.php?title=" . urlencode("Recommender - List of Trajectories Clusters&nbsp;") . "\">
                    <h3><img src=\"recommender/images/trajectories_clusters.png\" /><br>List of Trajectories Clusters</h3></a>
            </li>";
                        echo "<li>
                <a href=\"recommender/flows/index.php?title=" . urlencode("Recommender - Interactive People Flow Maps&nbsp;") . "\">
                    <h3><img src=\"recommender/images/flows.png\" /><br>Interactive People Flow Maps</h3></a>
            </li>";
                    }
                    ?> 
                    <li>
                        <a href="recommender/heatmap.php?profile=all&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: All)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: All)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?profile=citizen&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: Citizen)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: Citizen)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?profile=commuter&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: Commuter)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: Commuter)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?profile=student&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: Student)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: Student)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?profile=tourist&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: Tourist)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: Tourist)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?profile=disabled&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: Disabled)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: Disabled)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?profile=operator&title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters (User Profile: Operator)&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters (User Profile: Operator)</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap.php?title=<?php echo urlencode("Recommender - Heatmap and Trajectories Clusters of City Users Together&nbsp;"); ?>">
                            <h3><img src="recommender/images/heat.png" /><br>Heatmap and Trajectories Clusters of City Users Together</h3></a>
                    </li>
                    <li>
                        <a href="recommender/heatmap-realtime/heatmap-realtime.php?title=<?php echo urlencode("Recommender - Real Time City Users - positions and movements&nbsp;"); ?>">
                            <h3><img src="recommender/images/time.png" /><br>Real Time City Users: positions and movements</h3></a>
                    </li>
                </ul>
            </div>
            <?php
            if (!$admin) {
                echo "<div class=\"login\"><form action=\"index.php\" method=\"post\" target=\"_self\" accept-charset=\"UTF-8\" enctype=\"application/x-www-form-urlencoded\" autocomplete=\"off\" novalidate>";
                //echo "<fieldset>";
                //echo "<legend>Login:</legend>";
                echo "Username: <input type=\"text\" name=\"username\" value=\"\">";
                echo "&nbsp;Password: <input type=\"password\" name=\"password\" value=\"\">";
                echo "&nbsp;<input type=\"submit\" value=\"Submit\">";
                //echo "</fieldset>";
                echo "</form></div>";
            } else {
                echo "<div class=\"login\"><form action=\"index.php\" method=\"post\" target=\"_self\" accept-charset=\"UTF-8\" enctype=\"application/x-www-form-urlencoded\" autocomplete=\"off\" novalidate>";
                //echo "<fieldset>";
                //echo "<legend>Login:</legend>";
                echo "&nbsp;<input type=\"submit\" value=\"Logout\">";
                //echo "</fieldset>";
                echo "</form></div>";
            }
            ?>
        </div>
    </body>
</html>
