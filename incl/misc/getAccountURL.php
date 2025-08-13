<?php
if(!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') $https = 'https';
else $https = 'http';
echo dirname($https."://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
?>
