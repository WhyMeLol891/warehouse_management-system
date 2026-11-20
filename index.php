<?php
/**
 * Warehouse Management System - Entry Point
 */

require_once __DIR__ . '/includes/init.php';

if (isLoggedIn()) {
    if (isAdmin()) {
        redirect('/admin/dashboard.php');
    } else {
        redirect('/user/dashboard.php');
    }
} else {
    redirect('/login.php');
}
