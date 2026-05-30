<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        jsonResponse(false, 'Please login first');
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        jsonResponse(false, 'Admin access required');
    }
}
?>