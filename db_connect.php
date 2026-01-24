<?php
$serverName = "localhost"; // use named instance if SQL Server Express
$connectionOptions = [
    "Database" => "JSDatabase",
    "TrustServerCertificate" => true, // safe for local dev
    "Encrypt" => false,               // avoid SSL errors in dev
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    $errors = sqlsrv_errors();
    echo "<pre>";
    echo "Connection failed!\n\n";
    echo "Server: localhost\\SQLEXPRESS\n";
    echo "Database: JSDatabase\n\n";
    print_r($errors);
    echo "</pre>";
    die();
}

function db_connected() {
    global $conn;
    return (bool)$conn;
}

function db_last_error() {
    $err = sqlsrv_errors();
    return $err ? print_r($err, true) : '';
}
