<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| Get Courses
|--------------------------------------------------------------------------
*/

$courseStmt = $pdo->prepare("
    SELECT id, title
    FROM courses
    ORDER BY start_date ASC
");

$courseStmt->execute();

$courses = $courseStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Selected Course
|--------------------------------------------------------------------------
*/

$selectedCourseId = isset($_GET['course_id'])
    ? (int) $_GET['course_id']
    : 0;

$selectedCourse = null;
$enrollments = [];

if ($selectedCourseId > 0) {

    $courseStmt = $pdo->prepare("
        SELECT id, title
        FROM courses
        WHERE id = ?
        LIMIT 1
    ");

    $courseStmt->execute([$selectedCourseId]);

    $selectedCourse = $courseStmt->fetch();

    if ($selectedCourse) {

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

        $enrollmentStmt->execute([$selectedCourseId]);

        $enrollments = $enrollmentStmt->fetchAll();
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Enrollment Reports - Admin</title>

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

            <h2>Enrollment Reports</h2>

            <p>
                View students enrolled in each course.
            </p>

        </div>

        <a
            href="dashboard.php"
            class="primary-button"
        >
            Back to Dashboard
        </a>

    </div>

    <div class="course-form-card">

        <form method="GET">

            <div class="form-group">

                <label for="course_id">
                    Select Course
                </label>

                <select
                    id="course_id"
                    name="course_id"
                    required
                >

                    <option value="">
                        Select a course
                    </option>

                    <?php foreach ($courses as $course): ?>

                        <option
                            value="<?= (int) $course['id'] ?>"
                            <?= $selectedCourseId === (int) $course['id']
                                ? 'selected'
                                : ''
                            ?>
                        >
                            <?= htmlspecialchars(
                                $course['title'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </option>

                    <?php endforeach; ?>

                </select>

            </div>

            <button
                type="submit"
                class="primary-button"
                style="border: none; cursor: pointer;"
            >
                View Enrollments
            </button>

        </form>

    </div>

    <?php if ($selectedCourse): ?>

        <div style="margin-top: 25px;">

            <div class="page-header">

               <div>

    <h2>
        <?= htmlspecialchars(
            $selectedCourse['title'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>
    </h2>

    <p>
        <?= count($enrollments) ?> enrolled student(s)
    </p>

    <a
        href="download-enrollments.php?course_id=<?= (int) $selectedCourseId ?>"
        class="primary-button"
    >
        Download CSV
    </a>

</div>

            </div>

            <div class="table-wrapper">

                <table class="admin-table">

                    <thead>

                        <tr>
                            <th>Student Name</th>
                            <th>Email</th>
                            <th>Enrollment Date</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php if (empty($enrollments)): ?>

                            <tr>

                                <td
                                    colspan="3"
                                    class="empty-message"
                                >
                                    No students are enrolled in this course.
                                </td>

                            </tr>

                        <?php else: ?>

                            <?php foreach ($enrollments as $enrollment): ?>

                                <tr>

                                    <td>
                                        <?= htmlspecialchars(
                                            $enrollment['student_name'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            $enrollment['student_email'],
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(
                                            date(
                                                'M d, Y h:i A',
                                                strtotime(
                                                    $enrollment['enrolled_at']
                                                )
                                            ),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        ) ?>
                                    </td>

                                </tr>

                            <?php endforeach; ?>

                        <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    <?php endif; ?>

</main>

</body>

</html>