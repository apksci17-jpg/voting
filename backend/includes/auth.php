<?php
// includes/auth.php — Session Middleware & Authorization
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 */
function isAuthenticated() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Get current logged in user array
 */
function getCurrentUser() {
    if (!isAuthenticated()) {
        return null;
    }
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'role' => $_SESSION['role'] ?? 'voter',
        'full_name' => $_SESSION['full_name'] ?? 'User',
        'student_id' => $_SESSION['student_id'] ?? ''
    ];
}

/**
 * Require login for protected routes
 */
function requireLogin() {
    if (!isAuthenticated()) {
        header("Location: ../login.php");
        exit;
    }
}

/**
 * Require admin role for admin routes
 */
function requireAdmin() {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        header("Location: ../voter/dashboard.php");
        exit;
    }
}

/**
 * Require voter role for voter routes
 */
function requireVoter() {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'voter') {
        header("Location: ../admin/dashboard.php");
        exit;
    }
}
