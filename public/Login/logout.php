<?php
// /Login/logout.php
session_start();
$_SESSION = [];
session_destroy();
header('Location: login.php?out=1');
exit;
