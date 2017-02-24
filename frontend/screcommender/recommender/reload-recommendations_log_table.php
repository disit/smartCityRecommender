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
include_once "header.php";
include_once "settings.php";
global $config;
?>
<html>
    <head> 
        <title>Personal Recommender</title> 
        <link rel="stylesheet" type="text/css" href="css/reset.css" />
        <link rel="stylesheet" type="text/css" href="css/style.css" />
        <link rel="stylesheet" type="text/css" href="css/typography.css" />
        <script type="text/javascript" src="javascript/sce.js"></script>
        <script type="text/javascript" src="javascript/jquery-2.1.0.min.js"></script>
        <script type="text/javascript">
            var global_sort_filters = ''; //used to store the global sort filters

            $(document).ready(function () {
                //refreshTable();
                loadTable();
            });

            function rfc3986EncodeURIComponent(str) {
                return encodeURIComponent(str).replace(/[!'()*]/g, escape);
            }

            function getSortFilter(sortFilters, variable) {
                //var query = window.location.search.substring(1);
                var query = sortFilters.substring(1); //delete the '?' character from the string
                var vars = query.split('&');
                for (var i = 0; i < vars.length; i++) {
                    var pair = vars[i].split('=');
                    if (decodeURIComponent(pair[0]) == variable) {
                        return decodeURIComponent(pair[1]);
                    }
                }
            }

            function refreshTable() {
                //get text filter parameters
                var filters = '';
                var ampersand = '';
                $('.FILTER').each(function (index) {
                    filters += $(this).val() != '' ? (ampersand + $(this).attr('name') + '=' + encodeURIComponent($(this).val())) : '';
                    filters != '' ? ampersand = '&' : '';
                });
                //load table
                $('#tableHolder').load('status.php' + (location.search != '' ? (location.search + '&' + filters) : '?' + filters), function () {
                    setTimeout(refreshTable, <?php echo $config['refreshTime']; ?>);
                });
            }

            //called by clicking on a sort field in status.php and by $(document).ready of this php
            function loadTable(sortFilters) {
                //get text filter parameters
                var filters = '';
                $('.FILTER').each(function (index) {
                    filters += $(this).val() != '' ? ('&' + $(this).attr('name') + '=' + encodeURIComponent($(this).val())) : '';
                });
                //load table
                //if sortFilters is defined (called by status.php) set global sort filters with current values
                if (sortFilters) {
                    var orderBy = getSortFilter(sortFilters, 'orderby');
                    var sort = getSortFilter(sortFilters, 'sort');
                    var page = getSortFilter(sortFilters, 'page');
                    //global_sort_filters = "orderby=" + getSortFilter(sortFilters, 'orderby') + "&sort=" + getSortFilter(sortFilters, 'sort') + "&page=" + getSortFilter(sortFilters, 'page');
                    if (typeof orderBy != 'undefined' && typeof sort != 'undefined') {
                        global_sort_filters = "orderby=" + orderBy + "&sort=" + sort;
                        if (typeof page != 'undefined')
                            global_sort_filters += "&page=" + page;
                    } else if (typeof page != 'undefined')
                        global_sort_filters = "page=" + page;
                    $('#tableHolder').load('status.php' + sortFilters + filters);
                }
                else {
                    var parameters = '?';
                    parameters += global_sort_filters;
                    parameters += (global_sort_filters != '' ? filters : (filters != '' ? filters.substr(1) : ''));
                    $('#tableHolder').load('recommendations_log_table-static.php' + parameters, function () {
                        setTimeout(loadTable, <?php echo $config['refreshTime']; ?>);
                    });
                }
            }
        </script>
    </head>
    <body>
        <br>
        <div id='filtersTable'>
            <table>
                <tr class='even'>
                    <td><input type="text" name="FILTER_ID" class="FILTER" id="FILTER_ID"></td>
                    <td><input type="text" name="FILTER_USER" class="FILTER" id="FILTER_USER"></td>
                    <td><input type="text" name="FILTER_PROFILE" class="FILTER" id="FILTER_PROFILE"></td>
                    <td><input type="text" name="FILTER_RECOMMENDATIONS" class="FILTER" id="FILTER_RECOMMENDATIONS"></td>
                    <td><input type="text" name="FILTER_DISTANCE" class="FILTER" id="FILTER_DISTANCE"></td>
                    <td><input type="text" name="FILTER_LATITUDE" class="FILTER" id="FILTER_LATITUDE"></td>
                    <td><input type="text" name="FILTER_LONGITUDE" class="FILTER" id="FILTER_LONGITUDE"></td>
                    <td><input type="text" name="FILTER_SPARQL" class="FILTER" id="FILTER_SPARQL"></td>
                    <td><input type="text" name="FILTER_DISLIKEDSUBCLASSES" class="FILTER" id="FILTER_DISLIKEDSUBCLASSES"></td>
                    <td><input type="text" name="FILTER_DISLIKEDGROUPS" class="FILTER" id="FILTER_DISLIKEDGROUPS"></td>
                    <td><input type="text" name="FILTER_REQUESTEDGROUP" class="FILTER" id="FILTER_REQUESTEDGROUP"></td>
                    <td><input type="text" name="FILTER_TIMESTAMP" class="FILTER" id="FILTER_TIMESTAMP"></td>
                </tr>
            </table>
        </div>
        <!--<script type="text/javascript" language="javascript">
            $('.FILTER').keyup(function() {
                var FILTER_SCHED_NAME = $('#FILTER_SCHED_NAME').val();
                //$('#md5').text(md5);

                var FILTER_ID = $('#FILTER_ID').val();
                //$('#md5').text(md5);

                var FILTER_FIRE_INSTANCE_ID = $('#FILTER_FIRE_INSTANCE_ID').val();
                //$('#md5').text(md5);

                var FILTER_DATE = $('#FILTER_DATE').val();
                //$('#md5').text(md5);

                var FILTER_JOB_NAME = $('#FILTER_JOB_NAME').val();
                //$('#md5').text(md5);

                var FILTER_JOB_GROUP = $('#FILTER_JOB_GROUP').val();
                //$('#md5').text(md5);

                var FILTER_STATUS = $('#FILTER_STATUS').val();
                //$('#md5').text(md5);

                var FILTER_TRIGGER_NAME = $('#FILTER_TRIGGER_NAME').val();
                //$('#md5').text(md5);

                var FILTER_TRIGGER_GROUP = $('#FILTER_TRIGGER_GROUP').val();
                //$('#md5').text(md5);

                var FILTER_PREV_FIRE_TIME = $('#FILTER_PREV_FIRE_TIME').val();
                //$('#md5').text(md5);

                var FILTER_NEXT_FIRE_TIME = $('#FILTER_NEXT_FIRE_TIME').val();
                //$('#md5').text(md5);

                var FILTER_REFIRE_COUNT = $('#FILTER_REFIRE_COUNT').val();
                //$('#md5').text(md5);

                var FILTER_RESULT = $('#FILTER_RESULT').val();
                //$('#md5').text(md5);

                var FILTER_SCHEDULER_INSTANCE_ID = $('#FILTER_SCHEDULER_INSTANCE_ID').val();
                //$('#md5').text(md5);

                var FILTER_IP_ADDRESS = $('#FILTER_IP_ADDRESS').val();
                //$('#md5').text(md5);
            });
        </script>-->
        <div id="tableHolder"></div>
        <br><br>
        <!--<a class="pointer" title="Back" href="#" onclick="history.back();">Back</a>&emsp;-->
        <a class="pointer button" title="Back" href="#" onclick="if (document.referrer) {
                    window.open(document.referrer, '_self');
                } else {
                    history.go(-1);
                }
                return false;">Back</a>&emsp;
        <a class="pointer button" title="Home" href="index.php">Home</a>&emsp;
        <br><br>
    </body>
</html>
