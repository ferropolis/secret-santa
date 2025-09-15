<?php // advance_year.php
require __DIR__.'/db.php'; session_start(); if (!isset($_SESSION['admin'])) { http_response_code(403); exit; }
$year = (int)setting('current_round_year') + 1;
setting('current_round_year', (string)$year);
header('Location: admin.php');
