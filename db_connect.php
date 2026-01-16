<?php
$serverName = "localhost\\SQLEXPRESS"; // use named instance if SQL Server Express
$connectionOptions = [
    "Database" => "JSDatabase",
    "TrustServerCertificate" => true, // safe for local dev
    "Encrypt" => false,               // avoid SSL errors in dev
    "CharacterSet" => "UTF-8"
];

$conn = sqlsrv_connect($serverName, $connectionOptions);

if ($conn === false) {
    // Log detailed errors for troubleshooting, show a generic message to users
    error_log("SQLSRV connect failed: " . print_r(sqlsrv_errors(), true));
    die("Database connection failed. Please check server and logs.");
}

function db_connected() {
    global $conn;
    return (bool)$conn;
}

function db_last_error() {
    $err = sqlsrv_errors();
    return $err ? print_r($err, true) : '';
}
