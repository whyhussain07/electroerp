<?php
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function requireAdmin() {
    if(!isAdmin()) {
        header("Location: ../../dashboard.php?error=access_denied");
        exit();
    }
}