<?php

session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| CSRF Token
|--------------------------------------------------------------------------
*/

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';

$courseModel = new Course($pdo);
$courses = $courseModel->getAll();

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Courses - Admin</title>

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

            <h2>Courses</h2>

            <p>
                Manage courses and their enrollment capacity.
            </p>

        </div>

        <a
            href="course-form.php"
            class="primary-button"
        >
            Add Course
        </a>

    </div>


    <div class="table-wrapper">

        <table class="admin-table">

            <thead>

                <tr>

                    <th>Title</th>
                    <th>Category</th>
                    <th>Start Date</th>
                    <th>Seats Taken</th>
                    <th>Capacity</th>
                    <th>Status</th>
                    <th>Actions</th>

                </tr>

            </thead>

            <tbody>

                <?php if (empty($courses)): ?>

                    <tr>

                        <td
                            colspan="7"
                            class="empty-message"
                        >
                            No courses found.
                        </td>

                    </tr>

                <?php else: ?>

                    <?php foreach ($courses as $course): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars(
                                    $course['title'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    $course['category_name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars(
                                    date(
                                        'M d, Y h:i A',
                                        strtotime($course['start_date'])
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </td>

                            <td>
                                <?= (int) $course['seats_taken'] ?>
                            </td>

                            <td>
                                <?= (int) $course['capacity'] ?>
                            </td>

                            <td>

                                <?php if ($course['status'] === 'published'): ?>

                                    <span class="status-badge status-published">
                                        Published
                                    </span>

                                <?php else: ?>

                                    <span class="status-badge status-draft">
                                        Draft
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="action-buttons">

                                    <a
                                        href="course-form.php?id=<?= (int) $course['id'] ?>"
                                        class="edit-button"
                                    >
                                        Edit
                                    </a>

                                    <form
                                        method="POST"
                                        action="delete-course.php"
                                    >

                                        <input
                                            type="hidden"
                                            name="course_id"
                                            value="<?= (int) $course['id'] ?>"
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

                                        <button
                                            type="submit"
                                            class="delete-button"
                                        >
                                            Delete
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

</main>

</body>

</html>