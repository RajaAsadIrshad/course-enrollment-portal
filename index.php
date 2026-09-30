<?php

session_start();

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/controllers/AuthController.php';

if (isset($_SESSION['user_id'])) {

    if ($_SESSION['role'] === 'admin') {
        header('Location: views/admin/dashboard.php');
        exit;
    }

    if ($_SESSION['role'] === 'student') {
        header('Location: views/student/courses.php');
        exit;
    }
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'Invalid email or password';
    } else {

        $authController = new AuthController($pdo);

        $user = $authController->login($email, $password);

        if ($user) {

            session_regenerate_id(true);

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['name'] = $user['name'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];

            if ($user['role'] === 'admin') {
                header('Location: views/admin/dashboard.php');
                exit;
            }

            header('Location: views/student/courses.php');
            exit;
        }

        $error = 'Invalid email or password';
    }
}

require_once __DIR__ . '/views/login.php';