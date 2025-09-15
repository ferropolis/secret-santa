<?php // toggle_reg.php
require __DIR__.'/db.php'; session_start(); if (!isset($_SESSION['admin'])) { http_response_code(403); exit; }
$to = ($_POST['to'] ?? '0') === '1' ? '1' : '0';
setting('registration_open', $to);
header('Location: admin.php');
