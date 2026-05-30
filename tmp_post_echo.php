<?php
header('Content-Type: text/plain');
$body = file_get_contents('php://input');
$headers = getallheaders();
echo "BODY:\n";
echo $body === false ? 'FALSE' : $body;
echo "\n\nHEADERS:\n";
foreach ($headers as $name => $value) {
    echo "$name: $value\n";
}
