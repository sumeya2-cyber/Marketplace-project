<?php
// logout.php
session_start();
session_unset();
session_destroy();

// Ensure the admin session cookie is cleared
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

// Redirect to the site home page (project root)
header('Location: /MarketPlace/index.php');
exit;
?>