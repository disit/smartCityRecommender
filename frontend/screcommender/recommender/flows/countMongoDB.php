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
// count MongoDB items, send email to recipients if previous value was equal to actual one (i.e., Streaming Server is not receiving the streaming data from Comune di Firenze)
include_once "../settings.php"; // settings
//include_once "../PHPMailer/PHPMailerAutoload.php";
ini_set('max_execution_time', 9999999); //300 seconds = 5 minutes
ini_set("memory_limit", "-1");

// get MongoDB counts
function getMongoDBCounts() {
    global $config;
    if (!isset($_REQUEST["minutes"])) {
        return;
    }
    $time = time();
    $client = new MongoClient($config["mongodb_url"]);
    $collection = $client->data->collection;
    $timestamp = ['insert_timestamp' => ['$gte' => new Mongodate($time - intval($_REQUEST["minutes"]) * 60)]];
    $events = 0;
    $users = 0;
    $aps = 0;

    MongoCursor::$timeout = -1; // avoid MongoCursor timeout
    // number of events in the last $_REQUEST["minutes"] minutes
    $events = $collection->find($timestamp)->count();
    // number of distinct users in the last $_REQUEST["minutes"] minutes
    $cursor = $collection->aggregate(['$match' => $timestamp], ['$group' => ['_id' => null, 'a2' => ['$addToSet' => '$a2']]]);
    $users = count($cursor["result"][0]["a2"]);
    // number of distinct APs in the last $_REQUEST["minutes"] minutes
    $cursor = $collection->aggregate(['$match' => $timestamp], ['$group' => ['_id' => null, 'a3' => ['$addToSet' => '$a3']]]);

    $aps = count($cursor["result"][0]["a3"]);

    $client->close();
    insertData($events, $users, $aps, intval($_REQUEST["minutes"]));
}

/* function sendEmail() {
  $mail = new PHPMailer;

  //$mail->SMTPDebug = 3;                                 // Enable verbose debug output

  $mail->isSMTP();                                        // Set mailer to use SMTP
  $mail->Host = 'musicnetwork.dinfo.unifi.it';            // Specify main and backup SMTP servers
  $mail->SMTPAuth = false;                                // Enable SMTP authentication
  //$mail->Username = 'user@example.com';                 // SMTP username
  //$mail->Password = 'secret';                           // SMTP password
  //$mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
  $mail->Port = 25;                                       // TCP port to connect to

  $mail->setFrom('info@disit.org', 'DISIT');
  $mail->addAddress('daniele.cenni@unifi.it', 'Daniele Cenni');     // Add a recipient
  //$mail->addAddress('ellen@example.com');               // Name is optional
  //$mail->addReplyTo('info@example.com', 'Information');
  //$mail->addCC('cc@example.com');
  //$mail->addBCC('bcc@example.com');
  //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
  //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
  $mail->isHTML(true);                                    // Set email format to HTML

  $mail->Subject = 'Streaming Server MongoDB alert';
  $mail->Body = 'This is an automatically generated message to notify you that the MongoDB database of the Streaming Server did not receive objects since<br>'
  . '<br>Login to the server 192.168.0.210 and then (as root user):<br><br>'
  . '- find the PID of the process<br>'
  . '<b>ps aux | grep StreamReceiver</b><br><br>'
  . '- kill the PID<br><br>'
  . '<b>kill -9 [pid]</b>, where [pid] is the PID number<br><br>'
  . '- restart it with:<br><br>'
  . '<b>nohup java -classpath :/root/* streamreceiver.StreamReceiver /root/receiver.properties &</b>';
  $mail->AltBody = 'This is an automatically generated message to notify you that the MongoDB database of the Streaming Server is not receiving objects.'
  . 'Login to the server 192.168.0.210 and restart it with from command line with: '
  . 'nohup java -classpath :/root/* streamreceiver.StreamReceiver /root/receiver.properties &';

  $mail->send();

  // if (!$mail->send()) {
  //  echo 'Message could not be sent.';
  //  echo 'Mailer Error: ' . $mail->ErrorInfo;
  //  } else {
  //  echo 'Message has been sent';
  //  }
  } */

function insertData($events, $users, $aps, $range_minutes) {
    global $config;
    $link = mysqli_connect($config['wifi_host'], $config['wifi_user'], $config['wifi_pass'], $config['wifi_database']);
    /* check connection */
    if (mysqli_connect_errno()) {
        printf("Connection failed: %s\n", mysqli_connect_error());
        exit();
    }
    $sql = "INSERT INTO wifi.mongodb_streaming (events, users, aps, range_minutes) VALUES (" . $events . ", " . $users . ", " . $aps . "," . $range_minutes . ")";
    //echo $sql;
    mysqli_query($link, $sql) or die(mysqli_error());
    //close connection
    mysqli_close($link);
}

getMongoDBCounts();
?>