<?php
require_once "../incl/lib/mainLib.php";
require_once "../incl/lib/exploitPatch.php";
require_once "../incl/lib/enums.php";
$lib = new Library();

$userName = Escape::username($_POST['userName']);
$password = $_POST['password'];
$email = Escape::text($_POST['email']);

if(empty($userName) || empty($password) || empty($email)) exit(RegisterError::GenericError);

$createAccount = $lib->createAccount($userName, $password, $password, $email, $email);
exit($createAccount['success'] ? RegisterError::Success : $createAccount['error']);
?>