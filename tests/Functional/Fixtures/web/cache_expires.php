<?php
header('Content-Type: text/html');
header('Expires: ' . gmdate('D, d M Y H:i:s', time()+10) . ' GMT');

echo microtime();
