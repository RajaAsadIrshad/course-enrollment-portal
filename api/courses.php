<?php

session_start();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'student') {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized.'
    ]);

    exit;
}

require_once __DIR__ . '/../config/database.php';

$perPage = 6;

$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;

if ($page < 1) {
    $page = 1;
}

$search = trim($_GET['search'] ?? '');
$searchValue = '%' . $search . '%';

/*
|--------------------------------------------------------------------------
| Count Courses
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
}

$offset = ($page - 1) * $perPage;

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

/*
|--------------------------------------------------------------------------
| Prepare Response
|--------------------------------------------------------------------------
*/

foreach ($courses as &$course) {

    $seatsTaken = (int) $course['seats_taken'];
    $capacity = (int) $course['capacity'];

    $course['seats_taken'] = $seatsTaken;
    $course['capacity'] = $capacity;
    $course['seats_left'] = $capacity - $seatsTaken;

    $course['is_enrolled'] = in_array(
        (int) $course['id'],
        $enrolledCourseIds,
        true
    );
}

unset($course);

echo json_encode([
    'success' => true,
    'courses' => $courses,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total_courses' => $totalCourses,
        'total_pages' => $totalPages
    ]
]);

exit;