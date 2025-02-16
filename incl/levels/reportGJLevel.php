<?php
require_once __DIR__."/../lib/mainLib.php";
require_once __DIR__."/../lib/exploitPatch.php";
require_once __DIR__."/../lib/ip.php";
require_once __DIR__."/../lib/enums.php";

$IP = IP::getIP();

$levelID = Escape::number($_POST['levelID']);

$reportLevel = Library::reportLevel($levelID, $IP);
if(!$reportLevel) exit(CommonError::InvalidRequest);

exit(CommonError::Success);
?>