<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';

$courseModel = new Course($pdo);
$courses = $courseModel->getAll();

$totalCourses = count($courses);
$publishedCourses = 0;
$draftCourses = 0;
$totalEnrollments = 0;

foreach ($courses as $course) {

    if ($course['status'] === 'published') {
        $publishedCourses++;
    }

    if ($course['status'] === 'draft') {
        $draftCourses++;
    }

    $totalEnrollments += (int) $course['seats_taken'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Admin Dashboard - Course Enrollment Portal</title>

    <link rel="stylesheet" href="../../assets/css/admin.css">

</head>

<body>

<header class="admin-header">

    <div class="admin-header-inner">

        <div>
            <h1>Course Enrollment Portal</h1>
            <p>Admin Dashboard</p>
        </div>

        <div class="admin-user">

            <span>
                <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>
            </span>

            <a href="../../logout.php" class="logout-button">
                Logout
            </a>

        </div>

    </div>

</header>

<main class="admin-container">

    <section class="welcome-section">

        <h2>
            Welcome, <?= htmlspecialchars($_SESSION['name'], ENT_QUOTES, 'UTF-8') ?>
        </h2>

        <p>
            Manage courses and monitor student enrollments from your dashboard.
        </p>

    </section>

    <section class="dashboard-grid">

        <div class="dashboard-card">

            <h3>Total Courses</h3>

            <p class="dashboard-number">
                <?= $totalCourses ?>
            </p>

        </div>

        <div class="dashboard-card">

            <h3>Published Courses</h3>

            <p class="dashboard-number">
                <?= $publishedCourses ?>
            </p>

        </div>

        <div class="dashboard-card">

            <h3>Draft Courses</h3>

            <p class="dashboard-number">
                <?= $draftCourses ?>
            </p>

        </div>

        <div class="dashboard-card">

            <h3>Total Enrollments</h3>

            <p class="dashboard-number">
                <?= $totalEnrollments ?>
            </p>

        </div>

    </section>

    <section class="dashboard-grid dashboard-links">

        <a href="courses.php" class="dashboard-card">

            <h3>Courses</h3>

            <p>
                Create, edit, delete and manage course listings.
            </p>

        </a>

        <a href="enrollments.php" class="dashboard-card">

            <h3>Enrollment Reports</h3>

            <p>
                View enrolled students and download enrollment reports.
            </p>

        </a>

    </section>

</main>

</body>
</html>