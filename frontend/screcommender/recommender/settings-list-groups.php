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
<html lang="en">
    <head>
        <meta charset="utf-8">
        <title><?php
            if (isset($_REQUEST["title"])) {
                echo $_REQUEST["title"];
            } else {
                echo "Recommender";
            }
            ?>
        </title>
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <link rel="stylesheet" href="//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.css">
        <link href="javascript/toast/toastr.css" rel="stylesheet"/>
        <script src="//code.jquery.com/jquery-1.10.2.js"></script>
        <script src="//code.jquery.com/ui/1.11.4/jquery-ui.js"></script>
        <script src="javascript/toast/toastr.min.js" type = "text/javascript"></script>
        <style>
            td.evenSortable { background: #d3d3d3; } /* #e5ecf9 */
            td.oddSortable { background: #f5fff9; } /* #fffafa #f5fffa */
            #sortable_all { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_all li { margin: 10px 3px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_all li span { position: absolute; margin-left: -1.3em; }

            #sortable_citizen { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_citizen li { margin: 10px 3px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_citizen li span { position: absolute; margin-left: -1.3em; }

            #sortable_commuter { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_commuter li { margin: 10px 3px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_commuter li span { position: absolute; margin-left: -1.3em; }

            #sortable_student { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_student li { margin: 10px 3px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_student li span { position: absolute; margin-left: -1.3em; }

            #sortable_tourist { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_tourist li { margin: 10px 3px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_tourist li span { position: absolute; margin-left: -1.3em; }

            #sortable_disabled { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_disabled li { margin: 10px 3px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_disabled li span { position: absolute; margin-left: -1.3em; }

            #sortable_operator { list-style-type: none; margin-left: -40; padding: 0; width: 60%; }
            #sortable_operator li { margin: 10px 1px 3px 50px; padding: 0.4em; padding-left: 1.5em; font-size: 1.2em; height: 28px; width: 200px; }
            #sortable_operator li span { position: absolute; margin-left: -1.3em; }
        </style>
        <script>
            $(function () {
                $("#sortable_all").sortable();
                $("#sortable_all").disableSelection();
            });
            $(function () {
                $("#sortable_citizen").sortable();
                $("#sortable_citizen").disableSelection();
            });
            $(function () {
                $("#sortable_commuter").sortable();
                $("#sortable_commuter").disableSelection();
            });
            $(function () {
                $("#sortable_student").sortable();
                $("#sortable_student").disableSelection();
            });
            $(function () {
                $("#sortable_tourist").sortable();
                $("#sortable_tourist").disableSelection();
            });
            $(function () {
                $("#sortable_disabled").sortable();
                $("#sortable_disabled").disableSelection();
            });
            $(function () {
                $("#sortable_operator").sortable();
                $("#sortable_operator").disableSelection();
            });
            function submit_form() {
                // get sortable groups for each profile
                sortable_all = $("#sortable_all").sortable('serialize');
                sortable_citizen = $("#sortable_citizen").sortable('serialize');
                sortable_commuter = $("#sortable_commuter").sortable('serialize');
                sortable_student = $("#sortable_student").sortable('serialize');
                sortable_tourist = $("#sortable_tourist").sortable('serialize');
                sortable_disabled = $("#sortable_disabled").sortable('serialize');
                sortable_operator = $("#sortable_operator").sortable('serialize');

                // convert sortable group to arrays
                // attribute can specify a different field rather than id for <li> to be used
                sortable_all_array = $("#sortable_all").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();
                sortable_citizen_array = $("#sortable_citizen").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();
                sortable_commuter_array = $("#sortable_commuter").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();
                sortable_student_array = $("#sortable_student").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();
                sortable_tourist_array = $("#sortable_tourist").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();
                sortable_disabled_array = $("#sortable_disabled").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();
                sortable_operator_array = $("#sortable_operator").sortable("toArray"/*, {attribute: 'data-item_number'}*/);//.toSource();

                var data = "";

                // populate data
                for (var i = 0; i < sortable_all_array.length; i++) {
                    if (i > 0) {
                        data += "&";
                    }
                    data += sortable_all_array[i] + "=" + (i + 1);
                }
                for (var i = 0; i < sortable_citizen_array.length; i++) {
                    data += "&";
                    data += sortable_citizen_array[i] + "=" + (i + 1);
                }
                for (var i = 0; i < sortable_commuter_array.length; i++) {
                    data += "&";
                    data += sortable_commuter_array[i] + "=" + (i + 1);
                }
                for (var i = 0; i < sortable_student_array.length; i++) {
                    data += "&";
                    data += sortable_student_array[i] + "=" + (i + 1);
                }
                for (var i = 0; i < sortable_tourist_array.length; i++) {
                    data += "&";
                    data += sortable_tourist_array[i] + "=" + (i + 1);
                }
                for (var i = 0; i < sortable_disabled_array.length; i++) {
                    data += "&";
                    data += sortable_disabled_array[i] + "=" + (i + 1);
                }
                for (var i = 0; i < sortable_operator_array.length; i++) {
                    data += "&";
                    data += sortable_operator_array[i] + "=" + (i + 1);
                }

                $.ajax({
                    url: "updateGroups.php",
                    type: "POST",
                    async: true,
                    cache: false,
                    data: data,
                    success: function (data) {
                        //display toast
                        // https://codeseven.github.io/toastr/
                        if (data == '') {
                            toastr["success"]("Settings updated successfully", "", {"timeOut": "3000", "iconClass": "customer-info"}); //"iconClass": "customer-info"
                        }
                        else {
                            toastr["error"](data, "", {"timeOut": "3000", "iconClass": "customer-info"});
                            //location.reload(true);
                        }
                    },
                    error: function (error) {
                    }
                });
            }
        </script>
    </head>
    <body>
        <?php
        include_once "header.php"; //include header
        include_once "settings.php";
        $config['table'] = "groups";

        //CONNECT
        $link = mysqli_connect($config['host'], $config['user'], $config['pass'], $config['database']);

        /* check connection */
        if (mysqli_connect_errno()) {
            printf("Connection failed: %s\n", mysqli_connect_error());
            exit();
        }

        //get total rows
        $totalrows = mysqli_fetch_array(mysqli_query($link, "SELECT COUNT(*) AS total FROM recommender." . $config['table']));

        //IF STATUS TABLE IS EMPTY DISPLAY ONLY THE MENU
        if ($totalrows['total'] == 0) {
            echo "<div id='resultsTable'><table>\n<tr>";
            echo "Groups Priorities List is empty.<br>";
            echo "</table></div>\n"; //close <div id='resultsTable'>
            echo "</body>";
            echo "</html>";
            exit;
        }

        //START TABLE AND TABLE HEADER
        echo "<div id='resultsTable'>";
        echo"<table>\n";
        echo"<tr>\n";
        $result = mysqli_query($link, "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'recommender' AND TABLE_NAME = 'groups' AND COLUMN_NAME != 'id' AND COLUMN_NAME != 'group'");
        if (!$result) {
            echo 'Could not run query: ' . mysqli_error();
            exit;
        }
        while ($row = mysqli_fetch_assoc($result)) {
            echo "<th>" . ucfirst($row["COLUMN_NAME"]) . "</th>";
            $columns[] = $row["COLUMN_NAME"];
        }
        echo "</tr>\n";

        //reset result pointer
        mysqli_data_seek($result, 0);

        //start first row style
        $td_class = "evenSortable";

        echo "<tr>\n";
        foreach ($columns as $column) {
            $result = mysqli_query($link, "SELECT `group` FROM recommender." . $config['table'] . " ORDER BY `" . $column . "` ASC");
            //LOOP TABLE ROWS
            echo "<td class=\"" . $td_class . "\">\n";
            echo "<ul id=\"sortable_" . $column . "\">\n";
            while ($row = mysqli_fetch_assoc($result)) {
                foreach ($row as $field => $value) {
                    echo "<li id=\"" . $column . "_" . str_replace(" ", "-", $value) . "\" class=\"ui-state-default\"><span class=\"ui-icon ui-icon-arrowthick-2-n-s\"></span>" . $value . "</li>\n";
                }
            }
            echo "</ul>";
            echo "</td>\n";
        }
        echo "</tr>\n";

        mysqli_close($link); //close connection
        //END TABLE
        echo "</table></div>\n"; //close <div id='resultsTable'>
        // form
        echo "<form>";
        echo "<div class=\"submit\"><input name=\"apply\" value=\"Apply\" onclick=\"submit_form();\" type=\"button\"></div><br>";
        echo "</form>";
        echo "<a class=\"pointer button\" title=\"Back\" href=\"#\" onclick=\"if(document.referrer) {window.open(document.referrer,'_self');} else {history.go(-1);}return false;\">Back</a>&emsp;\n";
        echo "<a class=\"pointer button\" title=\"Home\" href=\"index.php\">Home</a>&emsp;\n";
        echo "<br><br>";
        ?>
    </body>
</html>