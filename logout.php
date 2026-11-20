<?php
/**
 * Warehouse Management System - Logout
 */

require_once __DIR__ . '/includes/init.php';

logoutUser();

setFlash('info', 'You have been logged out successfully.');
redirect('/login.php');
