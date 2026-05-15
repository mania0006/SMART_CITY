<?php
require_once 'config/config.php';
session_destroy();
redirect(APP_URL . '/login.php');
?>