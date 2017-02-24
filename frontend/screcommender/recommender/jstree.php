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
    </head>
</head>
<body>
    <?php
    include_once "header.php"; //include header
    include_once "settings.php";
    ?>
    <!-- include the minified jstree source -->
    <script type="text/javascript" src="javascript/jstree/dist/jstree.min.js"></script>
    <script>
        $(function () {
            // create an instance when the DOM is ready
            $('#using_json').jstree({'core': {
                    'data': [
                        'Simple root node',
                        {
                            'text': 'Root node 2',
                            'state': {
                                'opened': true,
                                'selected': true
                            },
                            'children': [
                                {'text': 'Child 1'},
                                'Child 2'
                            ]
                        }
                    ]
                }});
        });
    </script>
    <div id="resultsTable">
        <div id="using_json"></div>
    </div>
</body>
</html>
