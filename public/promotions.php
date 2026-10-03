<?php

require_once __DIR__ . '/../config/app.php';

header('Location: ' . BASE_URL . '/products.php?promotion=1', true, 301);
exit;
