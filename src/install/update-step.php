<?php

/**
 * update-step.php
 *
 * Simple endpoint to update the installation step reached in the session.
 */

session_start();
define('SCRIPTLOG', true);

header('Content-Type: application/json');

if (!isset($_GET['step']) || !ctype_digit($_GET['step'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid step.']);
    exit;
}

$step = (int)$_GET['step'];

// Only allow updating to valid step numbers and prevent decreasing it if already set
if ($step >= 1 && $step <= 4) {
    if (!isset($_SESSION['install_step_reached']) || $step > $_SESSION['install_step_reached']) {
        $_SESSION['install_step_reached'] = $step;
    }
    echo json_encode(['success' => true, 'step_reached' => $_SESSION['install_step_reached']]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid step range.']);
}
