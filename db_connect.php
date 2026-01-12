<?php
$serverName = "localhost";
$connectionOptions = [
    "Database" => "JSDatabase",
    "TrustServerCertificate" => true
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}
