USE course_enrollment;

-- ============================================
-- USERS
-- ============================================

INSERT INTO users (name, email, password, role) VALUES
(
    'Admin User',
    'admin@example.com',
    '$2y$10$dssc/RKzjHf0pgm0Sk9mUemg1irluKF170i1kEdyemPMpHP/mK1Ry',
    'admin'
),
(
    'Student One',
    'student1@example.com',
    '$2y$10$y4vP/.DTZEqaPZM9diYe5ezBL3iPrUlTkIvE/UTAgLfTVcTi9xtCu',
    'student'
),
(
    'Student Two',
    'student2@example.com',
    '$2y$10$y4vP/.DTZEqaPZM9diYe5ezBL3iPrUlTkIvE/UTAgLfTVcTi9xtCu',
    'student'
),
(
    'Student Three',
    'student3@example.com',
    '$2y$10$y4vP/.DTZEqaPZM9diYe5ezBL3iPrUlTkIvE/UTAgLfTVcTi9xtCu',
    'student'
);

-- ============================================
-- CATEGORIES
-- ============================================

INSERT INTO categories (name) VALUES
('Web Development'),
('Programming'),
('Database'),
('Cloud & DevOps');

-- ============================================
-- COURSES
-- ============================================

INSERT INTO courses
(title, description, category_id, instructor, start_date, capacity, status)
VALUES

-- Past published courses
(
    'Introduction to PHP',
    'Learn the fundamentals of PHP programming and server-side web development.',
    2,
    'Ahmed Khan',
    '2026-09-05 10:00:00',
    20,
    'published'
),
(
    'HTML & CSS Fundamentals',
    'Build responsive web pages using modern HTML and CSS techniques.',
    1,
    'Sara Ahmed',
    '2026-09-12 11:00:00',
    25,
    'published'
),
(
    'MySQL Database Basics',
    'Learn relational database concepts, SQL queries, and database design.',
    3,
    'Usman Ali',
    '2026-09-18 14:00:00',
    15,
    'published'
),
(
    'JavaScript Essentials',
    'Understand JavaScript fundamentals and browser-based programming.',
    1,
    'Hassan Raza',
    '2026-09-22 15:00:00',
    20,
    'published'
),

-- Upcoming published courses
(
    'Advanced PHP Development',
    'Learn object-oriented PHP, PDO, sessions, security, and application architecture.',
    2,
    'Ahmed Khan',
    '2026-10-02 10:00:00',
    15,
    'published'
),
(
    'WordPress Development',
    'Learn WordPress theme customization, plugins, hooks, and custom development.',
    1,
    'Bilal Ahmed',
    '2026-10-05 11:00:00',
    12,
    'published'
),
(
    'REST API Development with PHP',
    'Build secure REST APIs using PHP and MySQL.',
    2,
    'Hassan Raza',
    '2026-10-08 14:00:00',
    10,
    'published'
),
(
    'Database Design & Optimization',
    'Learn database normalization, indexing, query optimization, and best practices.',
    3,
    'Usman Ali',
    '2026-10-10 10:00:00',
    8,
    'published'
),
(
    'Docker for Developers',
    'Learn containerization and Docker workflows for modern development.',
    4,
    'Omar Farooq',
    '2026-10-13 15:00:00',
    10,
    'published'
),
(
    'Laravel Fundamentals',
    'Build modern PHP applications using Laravel fundamentals and MVC architecture.',
    2,
    'Ahmed Khan',
    '2026-10-16 11:00:00',
    15,
    'published'
),
(
    'Git & GitHub for Developers',
    'Learn version control, branching, pull requests, and collaborative development.',
    4,
    'Sara Ahmed',
    '2026-10-18 13:00:00',
    20,
    'published'
),
(
    'Cloud Computing Basics',
    'Introduction to cloud concepts, services, deployment, and infrastructure.',
    4,
    'Omar Farooq',
    '2026-10-20 10:00:00',
    10,
    'published'
),

-- Course with 1 seat remaining after seeded enrollments
(
    'Full Stack Web Development',
    'Learn the fundamentals of building complete web applications.',
    1,
    'Bilal Ahmed',
    '2026-10-22 10:00:00',
    3,
    'published'
),

-- Other upcoming courses
(
    'Advanced MySQL',
    'Explore advanced SQL queries, indexes, transactions, and performance tuning.',
    3,
    'Usman Ali',
    '2026-10-24 14:00:00',
    10,
    'published'
),
(
    'PHP Security Best Practices',
    'Learn secure PHP development including authentication, CSRF, XSS, and SQL injection prevention.',
    2,
    'Hassan Raza',
    '2026-10-26 11:00:00',
    12,
    'published'
),
(
    'Linux for Web Developers',
    'Learn essential Linux commands and server concepts for web developers.',
    4,
    'Omar Farooq',
    '2026-10-28 15:00:00',
    15,
    'published'
),

-- Draft courses
(
    'Advanced JavaScript',
    'Explore advanced JavaScript concepts and modern application development.',
    1,
    'Sara Ahmed',
    '2026-11-02 10:00:00',
    15,
    'draft'
),
(
    'DevOps Fundamentals',
    'Introduction to CI/CD, automation, deployment, and DevOps practices.',
    4,
    'Omar Farooq',
    '2026-11-05 14:00:00',
    12,
    'draft'
),
(
    'PHP Testing & Debugging',
    'Learn practical techniques for testing and debugging PHP applications.',
    2,
    'Ahmed Khan',
    '2026-11-08 11:00:00',
    10,
    'draft'
),
(
    'Advanced REST APIs',
    'Design and implement scalable REST APIs with authentication and validation.',
    2,
    'Hassan Raza',
    '2026-11-12 10:00:00',
    10,
    'draft'
);

-- ============================================
-- EXISTING ENROLLMENTS
-- ============================================

-- Full Stack Web Development:
-- Capacity = 3
-- Two students enrolled
-- Therefore 1 seat remains

INSERT INTO enrollments (user_id, course_id) VALUES
(2, 13),
(3, 13);

-- A few additional enrollments for testing
INSERT INTO enrollments (user_id, course_id) VALUES
(2, 5),
(3, 6),
(4, 7);