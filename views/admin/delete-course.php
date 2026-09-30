<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';

$courseModel = new Course($pdo);

/*
|--------------------------------------------------------------------------
| Get Course ID
|--------------------------------------------------------------------------
*/

$courseId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $courseId = isset($_POST['course_id'])
        ? (int) $_POST['course_id']
        : 0;

} elseif (isset($_GET['id'])) {

    $courseId = (int) $_GET['id'];
}

if ($courseId <= 0) {
    header('Location: courses.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Load Course
|--------------------------------------------------------------------------
*/

$course = $courseModel->getById($courseId);

if (!$course) {
    header('Location: courses.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| Handle Confirmed Delete
|--------------------------------------------------------------------------
*/

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['confirm']) &&
    $_POST['confirm'] === 'yes'
) {

    if (
        empty($_SESSION['csrf_token']) ||
        empty($_POST['csrf_token']) ||
        !hash_equals(
            $_SESSION['csrf_token'],
            $_POST['csrf_token']
        )
    ) {
        $error = 'Invalid security token.';

    } else {

        $deleteStmt = $pdo->prepare("
            DELETE FROM courses
            WHERE id = ?
        ");

        $deleteStmt->execute([
            $courseId
        ]);

        header('Location: courses.php');
        exit;
    }
}

/*
|--------------------------------------------------------------------------
| Get Enrollment Count
|--------------------------------------------------------------------------
*/

$enrollmentStmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM enrollments
    WHERE course_id = ?
");

$enrollmentStmt->execute([
    $courseId
]);

$enrollmentCount = (int) $enrollmentStmt->fetchColumn();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Delete Course - Admin</title>

    <link
        rel="stylesheet"
        href="../../assets/css/admin.css"
    >

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
                <?= htmlspecialchars(
                    $_SESSION['name'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>
            </span>

            <a
                href="../../logout.php"
                class="logout-button"
            >
                Logout
            </a>

        </div>

    </div>

</header>

<main class="admin-container">

    <div class="page-header">

        <div>

            <h2>Delete Course</h2>

            <p>
                Confirm that you want to delete this course.
            </p>

        </div>

    </div>

    <?php if (!empty($error)): ?>

        <div class="form-error">

            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </div>

    <?php endif; ?>


    <div class="course-form-card">

        <h3>
            <?= htmlspecialchars(
                $course['title'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h3>

        <p style="margin-top: 10px;">

            Category:

            <?= htmlspecialchars(
                $course['category_name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>

        <p style="margin-top: 8px;">

            Start Date:

            <?= htmlspecialchars(
                date(
                    'M d, Y h:i A',
                    strtotime($course['start_date'])
                ),
                ENT_QUOTES,
                'UTF-8'
            ) ?>

        </p>


        <?php if ($enrollmentCount > 0): ?>

            <div
                class="form-error"
                style="margin-top: 20px;"
            >

                This course currently has

                <?= $enrollmentCount ?>

                enrolled student(s).

                Deleting this course will also remove
                its enrollment records.

            </div>

        <?php else: ?>

            <p style="margin-top: 20px;">

                This course has no enrolled students.

            </p>

        <?php endif; ?>


        <form
            method="POST"
            style="margin-top: 25px;"
        >

            <input
                type="hidden"
                name="course_id"
                value="<?= (int) $courseId ?>"
            >

            <input
                type="hidden"
                name="csrf_token"
                value="<?= htmlspecialchars(
                    $_SESSION['csrf_token'],
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>"
            >

            <input
                type="hidden"
                name="confirm"
                value="yes"
            >

            <div class="form-actions">

                <a
                    href="courses.php"
                    class="secondary-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="delete-button"
                    style="border: none; cursor: pointer;"
                >
                    Confirm Delete
                </button>

            </div>

        </form>

    </div>

</main>

</body>

</html>