<?php

namespace CheshireCatWp\inc;

// Exit if accessed directly.
if (!defined('ABSPATH')) {
    exit;
}

// Include the admin files
require_once __DIR__ . '/admin/admin-menu.php';
require_once __DIR__ . '/admin/overview.php';
require_once __DIR__ . '/admin/style.php';
require_once __DIR__ . '/admin/configuration.php';