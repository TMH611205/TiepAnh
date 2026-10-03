<?php

require_once __DIR__ . '/../middleware/admin.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    Auth::verifyCsrf();
    Auth::logout();
}

redirect(ADMIN_URL . '/login.php');
