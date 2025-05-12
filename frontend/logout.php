<?php
session_start();
$_SESSION = [];
session_destroy();
http_response_code(200);
exit();
?>
