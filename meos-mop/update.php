<?php
  /*
  Copyright 2014-2018 Melin Software HB
  
  Licensed under the Apache License, Version 2.0 (the "License");
  you may not use this file except in compliance with the License.
  You may obtain a copy of the License at
  
      http://www.apache.org/licenses/LICENSE-2.0
  
  Unless required by applicable law or agreed to in writing, software
  distributed under the License is distributed on an "AS IS" BASIS,
  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
  See the License for the specific language governing permissions and
  limitations under the License.
  */

include_once("functions.php");
$link = ConnectToDB();

// Extract headers
$password = '';
$cmpId = '';
foreach ($_SERVER as $header => $value) {
  if (strcasecmp($header, "http_competition") == 0)
    $cmpId = (int)$value;
  if (strcasecmp($header, "http_pwd") == 0)
    $password = $value;
}

if (!($cmpId > 0)) {
  returnStatus('BADCMP');
}

if ($password != MEOS_PASSWORD) {
  returnStatus('BADPWD');
}

$data = file_get_contents("php://input"); 

if ($data[0] == 'P') { //Zip starts with 'PK'
  returnStatus('NOZIP'); // Zip not supported
}
  
$update = new SimpleXMLElement($data);

if ($update->getName() == "MOPComplete")
  clearCompetition($link, $cmpId);
else if ($update->getName() != "MOPDiff")
  die("Unknown data");
  
foreach ($update->children() as $d) {
  if ($d->getName() == "cmp")
    processCompetitor($link, $cmpId, $d);
  else  if ($d->getName() == "tm")
    processTeam($link, $cmpId, $d);
  else if ($d->getName() == "cls")
    processClass($link, $cmpId, $d);
  else if ($d->getName() == "org")
    processOrganization($link, $cmpId, $d);
  else if ($d->getName() == "ctrl")
    processControl($link, $cmpId, $d);
  else if ($d->getName() == "competition")
    processCompetition($link, $cmpId, $d);   
}

returnStatus('OK');

?>
