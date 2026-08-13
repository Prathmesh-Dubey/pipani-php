<?php
// admin/logout.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

Auth::logout();
// Return to the public website in Visitor Mode after logout
header('Location: ' . SITE_URL);
exit;