<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/csrf.php';

date_default_timezone_set('Europe/Zagreb');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
