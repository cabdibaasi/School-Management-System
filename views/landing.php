<?php
/**
 * Old landing.php — now redirects to the main index page
 */
require_once dirname(__DIR__) . '/config/config.php';
if (Auth::check()) {
    redirect('views/dashboard.php');
} else {
    header('Location: ' . BASE_URL);
    exit;
}
