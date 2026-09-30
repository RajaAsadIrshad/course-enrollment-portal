<?php

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Course.php';

$courseModel = new Course($pdo);

$courseId = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $courseId > 0;

$course = null;

if ($isEdit) {
    $course = $courseModel->getById($courseId);

    if (!$course) {
        header('Location: courses.php');
        exit;
    }
}

$error = '';

$title = $course['title'] ?? '';
$description = $course['description'] ?? '';
$categoryId = $course['category_id'] ?? '';
$instructor = $course['instructor'] ?? '';
$startDate = '';

if (!empty($course['start_date'])) {
    $startDate = date('Y-m-d\TH:i', strtotime($course['start_date']));
}

$capacity = $course['capacity'] ?? '';
$status = $course['status'] ?? 'draft';

/*
|--------------------------------------------------------------------------
| Load Categories
|--------------------------------------------------------------------------
*/

$categoryStmt = $pdo->prepare("
    SELECT id, name
    FROM categories
    ORDER BY name ASC
");

$categoryStmt->execute();

$categories = $categoryStmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Handle Form Submission
|--------------------------------------------------------------------------
*/

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    )
) {
    $errors[] = 'Invalid security token.';
}

    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0);
    $instructor = trim($_POST['instructor'] ?? '');
    $startDate = trim($_POST['start_date'] ?? '');
    $capacity = trim($_POST['capacity'] ?? '');
    $status = $_POST['status'] ?? 'draft';

    /*
    |--------------------------------------------------------------------------
    | Validation
    |--------------------------------------------------------------------------
    */

    if ($title === '') {
        $error = 'Course title is required.';
    } elseif (strlen($title) > 150) {
        $error = 'Course title cannot exceed 150 characters.';
    } elseif ($categoryId <= 0) {
        $error = 'Please select a category.';
    } elseif ($instructor === '') {
        $error = 'Instructor is required.';
    } elseif ($startDate === '') {
        $error = 'Start date is required.';
    } elseif ($capacity === '' || !ctype_digit($capacity)) {
        $error = 'Capacity must be a valid number.';
    } elseif ((int) $capacity < 1 || (int) $capacity > 500) {
        $error = 'Capacity must be between 1 and 500.';
    } elseif (!in_array($status, ['draft', 'published'], true)) {
        $error = 'Invalid course status.';
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Category
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $categoryCheck = $pdo->prepare("
            SELECT id
            FROM categories
            WHERE id = ?
            LIMIT 1
        ");

        $categoryCheck->execute([$categoryId]);

        if (!$categoryCheck->fetch()) {
            $error = 'Selected category is invalid.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Validate Date
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $dateObject = DateTime::createFromFormat('Y-m-d\TH:i', $startDate);

        if (!$dateObject || $dateObject->format('Y-m-d\TH:i') !== $startDate) {
            $error = 'Please enter a valid start date and time.';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Check Capacity Against Existing Enrollments
    |--------------------------------------------------------------------------
    */

    if ($error === '' && $isEdit) {

        $enrollmentStmt = $pdo->prepare("
            SELECT COUNT(*)
            FROM enrollments
            WHERE course_id = ?
        ");

        $enrollmentStmt->execute([$courseId]);

        $enrollmentCount = (int) $enrollmentStmt->fetchColumn();

        if ((int) $capacity < $enrollmentCount) {
            $error = "Capacity cannot be lower than the current enrollment count ({$enrollmentCount}).";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Save Course
    |--------------------------------------------------------------------------
    */

    if ($error === '') {

        $formattedStartDate = $dateObject->format('Y-m-d H:i:s');

        if ($isEdit) {

            $stmt = $pdo->prepare("
                UPDATE courses
                SET
                    title = ?,
                    description = ?,
                    category_id = ?,
                    instructor = ?,
                    start_date = ?,
                    capacity = ?,
                    status = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $title,
                $description !== '' ? $description : null,
                $categoryId,
                $instructor,
                $formattedStartDate,
                (int) $capacity,
                $status,
                $courseId
            ]);

        } else {

            $stmt = $pdo->prepare("
                INSERT INTO courses
                    (
                        title,
                        description,
                        category_id,
                        instructor,
                        start_date,
                        capacity,
                        status
                    )
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $title,
                $description !== '' ? $description : null,
                $categoryId,
                $instructor,
                $formattedStartDate,
                (int) $capacity,
                $status
            ]);
        }

        header('Location: courses.php');
        exit;
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

    <title>
        <?= $isEdit ? 'Edit Course' : 'Add Course' ?> - Admin
    </title>

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

            <h2>
                <?= $isEdit ? 'Edit Course' : 'Add Course' ?>
            </h2>

            <p>
                <?= $isEdit
                    ? 'Update the course information below.'
                    : 'Create a new course for the portal.'
                ?>
            </p>

        </div>

        <a
            href="courses.php"
            class="primary-button"
        >
            Back to Courses
        </a>

    </div>

    <?php if ($error !== ''): ?>

        <div class="form-error">
            <?= htmlspecialchars(
                $error,
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </div>

    <?php endif; ?>

    <div class="course-form-card">

        <form method="POST">

    <input
        type="hidden"
        name="csrf_token"
        value="<?= htmlspecialchars(
            $_SESSION['csrf_token'],
            ENT_QUOTES,
            'UTF-8'
        ) ?>"
    >

            <div class="form-group">

                <label for="title">
                    Course Title
                </label>

                <input
                    type="text"
                    id="title"
                    name="title"
                    maxlength="150"
                    value="<?= htmlspecialchars(
                        $title,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    required
                >

            </div>

            <div class="form-group">

                <label for="description">
                    Short Description
                </label>

                <textarea
                    id="description"
                    name="description"
                    rows="5"
                ><?= htmlspecialchars(
                    $description,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?></textarea>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label for="category_id">
                        Category
                    </label>

                    <select
                        id="category_id"
                        name="category_id"
                        required
                    >

                        <option value="">
                            Select Category
                        </option>

                        <?php foreach ($categories as $category): ?>

                            <option
                                value="<?= (int) $category['id'] ?>"
                                <?= (int) $categoryId === (int) $category['id']
                                    ? 'selected'
                                    : ''
                                ?>
                            >
                                <?= htmlspecialchars(
                                    $category['name'],
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>

                <div class="form-group">

                    <label for="instructor">
                        Instructor
                    </label>

                    <input
                        type="text"
                        id="instructor"
                        name="instructor"
                        maxlength="100"
                        value="<?= htmlspecialchars(
                            $instructor,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-row">

                <div class="form-group">

                    <label for="start_date">
                        Start Date & Time
                    </label>

                    <input
                        type="datetime-local"
                        id="start_date"
                        name="start_date"
                        value="<?= htmlspecialchars(
                            $startDate,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="capacity">
                        Capacity
                    </label>

                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        min="1"
                        max="500"
                        value="<?= htmlspecialchars(
                            $capacity,
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>"
                        required
                    >

                </div>

            </div>

            <div class="form-group">

                <label for="status">
                    Status
                </label>

                <select
                    id="status"
                    name="status"
                    required
                >

                    <option
                        value="draft"
                        <?= $status === 'draft'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Draft
                    </option>

                    <option
                        value="published"
                        <?= $status === 'published'
                            ? 'selected'
                            : ''
                        ?>
                    >
                        Published
                    </option>

                </select>

            </div>

            <div class="form-actions">

                <a
                    href="courses.php"
                    class="secondary-button"
                >
                    Cancel
                </a>

                <button
                    type="submit"
                    class="primary-button"
                >
                    <?= $isEdit ? 'Update Course' : 'Create Course' ?>
                </button>

            </div>

        </form>

    </div>

</main>

</body>

</html>