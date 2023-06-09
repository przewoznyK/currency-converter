<?php
require('classesAndConnectToDatabase.php');
// Changes the value used to determine how many rows of results to display.
$newLimit = $_POST['limitDisplayResult'];
if($newLimit == 'wszystko') $sessionManager->set('limitDisplayResult', '999999');
else $sessionManager->set('limitDisplayResult', $newLimit);
header("Location: index.php");
?>