<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

$courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

if ($courseId <= 0) {
    header('Location: enrollments.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Course
|--------------------------------------------------------------------------
*/

$courseStmt = $pdo->prepare("
    SELECT id, title
    FROM courses
    WHERE id = ?
    LIMIT 1
");

$courseStmt->execute([$courseId]);

$course = $courseStmt->fetch();

if (!$course) {
    header('Location: enrollments.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Get Enrollments
|--------------------------------------------------------------------------
*/

$enrollmentStmt = $pdo->prepare("
    SELECT
        u.name AS student_name,
        u.email AS student_email,
        e.enrolled_at
    FROM enrollments e
    INNER JOIN users u
        ON u.id = e.user_id
    WHERE e.course_id = ?
    ORDER BY e.enrolled_at ASC
");

$enrollmentStmt->execute([$courseId]);

$enrollments = $enrollmentStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| CSV Headers
|--------------------------------------------------------------------------
*/

$filename = 'enrollments-' . $courseId . '.csv';

header('Content-Type: text/csv; charset=utf-8');
header(
    'Content-Disposition: attachment; filename="' . $filename . '"'
);

$output = fopen('php://output', 'w');

/*
|--------------------------------------------------------------------------
| CSV Header Row
|--------------------------------------------------------------------------
*/

fputcsv($output, [
    'Course',
    'Student Name',
    'Student Email',
    'Enrollment Date'
]);

/*
|--------------------------------------------------------------------------
| CSV Data
|--------------------------------------------------------------------------
*/

foreach ($enrollments as $enrollment) {

    fputcsv($output, [
        $course['title'],
        $enrollment['student_name'],
        $enrollment['student_email'],
        $enrollment['enrolled_at']
    ]);
}

fclose($output);

exit;