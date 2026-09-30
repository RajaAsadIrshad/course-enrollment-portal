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
if (
    empty($_SESSION['csrf_token']) ||
    empty($_POST['csrf_token']) ||
    !hash_equals(
        $_SESSION['csrf_token'],
        $_POST['csrf_token']
    )
) {
    http_response_code(403);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid security token.'
    ]);

    exit;
}

require_once __DIR__ . '/../config/database.php';

$courseId = isset($_POST['course_id'])
    ? (int) $_POST['course_id']
    : 0;

if ($courseId <= 0) {

    http_response_code(400);

    echo json_encode([
        'success' => false,
        'message' => 'Invalid course.'
    ]);

    exit;
}

$userId = (int) $_SESSION['user_id'];

try {

    /*
    |--------------------------------------------------------------------------
    | Check Course
    |--------------------------------------------------------------------------
    */

    $courseStmt = $pdo->prepare("
        SELECT
            id,
            start_date
        FROM courses
        WHERE id = ?
        LIMIT 1
    ");

    $courseStmt->execute([$courseId]);

    $course = $courseStmt->fetch();

    if (!$course) {

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Course not found.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Cancellation Before Course Start Only
    |--------------------------------------------------------------------------
    */

    if (strtotime($course['start_date']) <= time()) {

        echo json_encode([
            'success' => false,
            'message' => 'Enrollment cannot be cancelled after the course has started.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Enrollment
    |--------------------------------------------------------------------------
    */

    $deleteStmt = $pdo->prepare("
        DELETE FROM enrollments
        WHERE user_id = ?
          AND course_id = ?
    ");

    $deleteStmt->execute([
        $userId,
        $courseId
    ]);

    if ($deleteStmt->rowCount() === 0) {

        echo json_encode([
            'success' => false,
            'message' => 'You are not enrolled in this course.'
        ]);

        exit;
    }

    echo json_encode([
        'success' => true,
        'message' => 'Enrollment cancelled successfully.'
    ]);

} catch (Throwable $e) {

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to cancel enrollment.'
    ]);

}

exit;