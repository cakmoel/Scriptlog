<?php

/**
 * validate-db.php
 *
 * AJAX endpoint for database connectivity testing.
 * Part of the Scriptlog installation system hardening.
 */

session_start();
define('SCRIPTLOG', true);

require dirname(__FILE__) . '/include/setup.php';

header('Content-Type: application/json');

// 1. CSRF Check
if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    echo json_encode(['success' => false, 'message' => 'CSRF validation failed.']);
    exit;
}

// 2. Input Sanitization
$dbhost = isset($_POST['db_host']) ? escapeHTML(trim($_POST['db_host'])) : "";
$dbname = isset($_POST['db_name']) ? escapeHTML(trim($_POST['db_name'])) : "";
$dbport = isset($_POST['db_port']) ? escapeHTML(trim($_POST['db_port'])) : "3306";
$dbuser = isset($_POST['db_user']) ? escapeHTML(trim($_POST['db_user'])) : "";
$dbpass = isset($_POST['db_pass']) ? $_POST['db_pass'] : "";

// 3. Hard Error Validation
if (empty($dbhost) || empty($dbname) || empty($dbuser)) {
    echo json_encode(['success' => false, 'message' => 'All database fields are required.']);
    exit;
}

if (!ctype_digit(strval($dbport))) {
    echo json_encode(['success' => false, 'message' => 'Port must be a valid number.']);
    exit;
}

// 4. Test Connection
try {
    $link = make_connection($dbhost, $dbuser, $dbpass, $dbname, $dbport);

    if ($link) {
        // Unlock Step 4
        $_SESSION['install_step_reached'] = 3;
        echo json_encode(['success' => true, 'message' => 'Connection successful! Database is online.']);
        $link->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Unknown database connection error.']);
    }
} catch (mysqli_sql_exception $e) {
    if ($e->getCode() == 1049) {
        $msg = "Database '{$dbname}' not found. Please create it first.";
    } elseif ($e->getCode() == 1045) {
        $msg = "Access denied for user '{$dbuser}'. Check your password.";
    } elseif ($e->getCode() == 2002) {
        $msg = "Server '{$dbhost}' not found or unreachable.";
    } else {
        $msg = "Database Error: " . $e->getMessage();
    }
    echo json_encode(['success' => false, 'message' => $msg]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Connection failed: ' . $e->getMessage()]);
}
