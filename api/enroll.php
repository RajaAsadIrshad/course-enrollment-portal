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
    | Start Transaction
    |--------------------------------------------------------------------------
    */

    $pdo->beginTransaction();

    /*
    |--------------------------------------------------------------------------
    | Lock Course Row
    |--------------------------------------------------------------------------
    |
    | FOR UPDATE prevents two students from enrolling into the
    | final available seat at the same time.
    |
    */

    $courseStmt = $pdo->prepare("
        SELECT
            id,
            start_date,
            capacity,
            status
        FROM courses
        WHERE id = ?
        FOR UPDATE
    ");

    $courseStmt->execute([$courseId]);

    $course = $courseStmt->fetch();

    if (!$course) {

        $pdo->rollBack();

        http_response_code(404);

        echo json_encode([
            'success' => false,
            'message' => 'Course not found.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Course Status
    |--------------------------------------------------------------------------
    */

    if ($course['status'] !== 'published') {

        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'This course is not available for enrollment.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Course Start Date
    |--------------------------------------------------------------------------
    */

    if (strtotime($course['start_date']) <= time()) {

        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'This course has already started.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Check Existing Enrollment
    |--------------------------------------------------------------------------
    */

    $existingStmt = $pdo->prepare("
        SELECT id
        FROM enrollments
        WHERE user_id = ?
          AND course_id = ?
        LIMIT 1
    ");

    $existingStmt->execute([
        $userId,
        $courseId
    ]);

    if ($existingStmt->fetch()) {

        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'You are already enrolled in this course.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Count Current Enrollments
    |--------------------------------------------------------------------------
    */

    $countStmt = $pdo->prepare("
        SELECT COUNT(*)
        FROM enrollments
        WHERE course_id = ?
    ");

    $countStmt->execute([$courseId]);

    $enrollmentCount = (int) $countStmt->fetchColumn();

    /*
    |--------------------------------------------------------------------------
    | Check Capacity
    |--------------------------------------------------------------------------
    */

    if ($enrollmentCount >= (int) $course['capacity']) {

        $pdo->rollBack();

        echo json_encode([
            'success' => false,
            'message' => 'This course is full.'
        ]);

        exit;
    }

    /*
    |--------------------------------------------------------------------------
    | Create Enrollment
    |--------------------------------------------------------------------------
    */

    $insertStmt = $pdo->prepare("
        INSERT INTO enrollments (
            user_id,
            course_id
        )
        VALUES (?, ?)
    ");

    $insertStmt->execute([
        $userId,
        $courseId
    ]);

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Enrollment successful.'
    ]);

} catch (Throwable $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);

    echo json_encode([
        'success' => false,
        'message' => 'Unable to process enrollment.'
    ]);

}

exit;