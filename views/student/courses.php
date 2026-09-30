<?php

session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    header('Location: ../../index.php');
    exit;
}

require_once __DIR__ . '/../../config/database.php';

/*
|--------------------------------------------------------------------------
| Pagination
|--------------------------------------------------------------------------
*/

$perPage = 6;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$offset = ($page - 1) * $perPage;

/*
|--------------------------------------------------------------------------
| Filters
|--------------------------------------------------------------------------
*/

$search = trim($_GET['search'] ?? '');

$searchValue = '%' . $search . '%';

/*
|--------------------------------------------------------------------------
| Total Courses
|--------------------------------------------------------------------------
*/

$countSql = "
    SELECT COUNT(*)
    FROM courses c
    INNER JOIN categories cat
        ON cat.id = c.category_id
    WHERE c.status = 'published'
      AND c.start_date > NOW()
      AND (
            c.title LIKE ?
            OR cat.name LIKE ?
      )
";

$countStmt = $pdo->prepare($countSql);

$countStmt->execute([
    $searchValue,
    $searchValue
]);

$totalCourses = (int) $countStmt->fetchColumn();

$totalPages = max(
    1,
    (int) ceil($totalCourses / $perPage)
);

if ($page > $totalPages) {
    $page = $totalPages;
    $offset = ($page - 1) * $perPage;
}

/*
|--------------------------------------------------------------------------
| Get Courses
|--------------------------------------------------------------------------
*/

$sql = "
    SELECT
        c.id,
        c.title,
        c.description,
        c.instructor,
        c.start_date,
        c.capacity,
        cat.name AS category_name,
        COUNT(e.id) AS seats_taken
    FROM courses c
    INNER JOIN categories cat
        ON cat.id = c.category_id
    LEFT JOIN enrollments e
        ON e.course_id = c.id
    WHERE c.status = 'published'
      AND c.start_date > NOW()
      AND (
            c.title LIKE ?
            OR cat.name LIKE ?
      )
    GROUP BY
        c.id,
        c.title,
        c.description,
        c.instructor,
        c.start_date,
        c.capacity,
        cat.name
    ORDER BY c.start_date ASC
    LIMIT ? OFFSET ?
";

$stmt = $pdo->prepare($sql);

$stmt->bindValue(1, $searchValue, PDO::PARAM_STR);
$stmt->bindValue(2, $searchValue, PDO::PARAM_STR);
$stmt->bindValue(3, $perPage, PDO::PARAM_INT);
$stmt->bindValue(4, $offset, PDO::PARAM_INT);

$stmt->execute();

$courses = $stmt->fetchAll();

/*
|--------------------------------------------------------------------------
| Current Student Enrollments
|--------------------------------------------------------------------------
*/

$enrollmentStmt = $pdo->prepare("
    SELECT course_id
    FROM enrollments
    WHERE user_id = ?
");

$enrollmentStmt->execute([
    $_SESSION['user_id']
]);

$enrolledCourseIds = array_map(
    'intval',
    $enrollmentStmt->fetchAll(PDO::FETCH_COLUMN)
);

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Available Courses - Course Enrollment Portal</title>

    <link
        rel="stylesheet"
        href="../../assets/css/student.css"
    >

</head>

<body>

<header class="student-header">

    <div class="student-header-inner">

        <div>

            <h1>Course Enrollment Portal</h1>

            <p>Available Courses</p>

        </div>

        <div class="student-user">

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

<main class="student-container">

    <section class="student-welcome">

        <h2>
            Welcome,
            <?= htmlspecialchars(
                $_SESSION['name'],
                ENT_QUOTES,
                'UTF-8'
            ) ?>
        </h2>

        <p>
            Browse upcoming courses and enroll in the ones you are interested in.
        </p>

    </section>

    <section class="course-filter">

        <form method="GET">

            <label for="search">
                Search courses
            </label>

            <div class="search-row">

                <input
                    type="text"
                    id="search"
                    name="search"
                    value="<?= htmlspecialchars(
                        $search,
                        ENT_QUOTES,
                        'UTF-8'
                    ) ?>"
                    placeholder="Search by title or category..."
                >

                <button type="submit">
                    Search
                </button>

            </div>

        </form>

    </section>

    <section class="course-grid">

        <?php foreach ($courses as $course): ?>

            <?php
            $seatsTaken = (int) $course['seats_taken'];
            $capacity = (int) $course['capacity'];
            $seatsLeft = $capacity - $seatsTaken;

            $isEnrolled = in_array(
                (int) $course['id'],
                $enrolledCourseIds,
                true
            );
            ?>

            <article class="course-card">

                <div class="course-card-content">

                    <span class="course-category">
                        <?= htmlspecialchars(
                            $course['category_name'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </span>

                    <h3>
                        <?= htmlspecialchars(
                            $course['title'],
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </h3>

                    <p class="course-description">
                        <?= htmlspecialchars(
                            $course['description'] ?? '',
                            ENT_QUOTES,
                            'UTF-8'
                        ) ?>
                    </p>

                    <div class="course-details">

                        <p>
                            <strong>Instructor:</strong>
                            <?= htmlspecialchars(
                                $course['instructor'],
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p>
                            <strong>Starts:</strong>
                            <?= htmlspecialchars(
                                date(
                                    'M d, Y h:i A',
                                    strtotime($course['start_date'])
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </p>

                        <p>
                            <strong>Seats left:</strong>
                            <?= $seatsLeft ?>
                        </p>

                    </div>

                    <div class="course-action">

                        <?php if ($isEnrolled): ?>

                            <button
                                type="button"
                                class="cancel-button"
                                data-course-id="<?= (int) $course['id'] ?>"
                            >
                                Cancel Enrollment
                            </button>

                        <?php elseif ($seatsLeft <= 0): ?>

                            <button
                                type="button"
                                class="full-button"
                                disabled
                            >
                                Full
                            </button>

                        <?php else: ?>

                            <button
                                type="button"
                                class="enroll-button"
                                data-course-id="<?= (int) $course['id'] ?>"
                            >
                                Enroll
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

            </article>

        <?php endforeach; ?>

    </section>

    <div
        class="no-courses"
        style="<?= empty($courses) ? 'display: block;' : 'display: none;' ?>"
    >
        No courses found.
    </div>

    <div class="pagination">

        <?php if ($totalPages > 1): ?>

            <?php if ($page > 1): ?>

                <a
                    href="#"
                    data-page="<?= $page - 1 ?>"
                >
                    Previous
                </a>

            <?php endif; ?>

            <span>
                Page <?= $page ?> of <?= $totalPages ?>
            </span>

            <?php if ($page < $totalPages): ?>

                <a
                    href="#"
                    data-page="<?= $page + 1 ?>"
                >
                    Next
                </a>

            <?php endif; ?>

        <?php endif; ?>

    </div>

</main>

<script>
    const csrfToken = <?= json_encode($_SESSION['csrf_token']) ?>;
</script>

<script src="../../assets/js/student.js"></script>

</body>

</html>