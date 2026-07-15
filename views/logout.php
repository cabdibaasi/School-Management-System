<?php
/**
 * Logout script
 */
require_once dirname(__DIR__) . '/config/config.php';

Auth::logout();

// Set flash message
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
Utility::setFlash('info', 'You have been successfully logged out.');
redirect('views/login.php');
