<?php

class Course
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getAll(): array
    {
        $sql = "
            SELECT
                c.id,
                c.title,
                c.instructor,
                c.start_date,
                c.capacity,
                c.status,
                cat.name AS category_name,
                COUNT(e.id) AS seats_taken
            FROM courses c
            INNER JOIN categories cat
                ON cat.id = c.category_id
            LEFT JOIN enrollments e
                ON e.course_id = c.id
            GROUP BY
                c.id,
                c.title,
                c.instructor,
                c.start_date,
                c.capacity,
                c.status,
                cat.name
            ORDER BY c.start_date ASC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getById(int $id): ?array
    {
        $sql = "
            SELECT
                c.*,
                cat.name AS category_name,
                COUNT(e.id) AS seats_taken
            FROM courses c
            INNER JOIN categories cat
                ON cat.id = c.category_id
            LEFT JOIN enrollments e
                ON e.course_id = c.id
            WHERE c.id = ?
            GROUP BY c.id
            LIMIT 1
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);

        $course = $stmt->fetch();

        return $course ?: null;
    }
}