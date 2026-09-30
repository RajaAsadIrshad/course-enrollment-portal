# Course Enrollment Portal

A PHP and MySQL course enrollment portal developed as a PHP Developer take-home assessment.

The application provides separate dashboards and workflows for administrators and students.

## Tech Stack

- PHP 8.1+
- MySQL / MariaDB
- PDO
- HTML5
- CSS3
- JavaScript
- AJAX
- XAMPP

## Features

### Authentication

- Admin and student login
- Email/password authentication
- Passwords stored using PHP `password_hash()`
- Session-based authentication
- Session ID regeneration after successful login
- Secure logout and session destruction
- Role-based access control

### Admin

- Admin dashboard
- View course statistics
- Create courses
- Edit courses
- Delete courses
- Course categories
- Course status: Draft / Published
- Enrollment capacity management
- Prevent reducing capacity below current enrollment
- Enrollment reports
- CSV enrollment export
- Delete confirmation for courses with enrolled students

### Student

- View published upcoming courses
- Courses sorted by soonest start date
- Six courses per page
- AJAX pagination
- AJAX title/category search
- Loading indicator
- Friendly empty-state message
- Enroll in courses
- Cancel enrollment before course start
- Seats remaining display
- Full-course handling
- Duplicate enrollment prevention

## Project Structure

```text
course-enrollment-portal/
│
├── api/
│   ├── cancel.php
│   ├── courses.php
│   └── enroll.php
│
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── login.css
│   │   └── student.css
│   │
│   └── js/
│       └── student.js
│
├── config/
│   └── database.php
│
├── controllers/
│   └── AuthController.php
│
├── database/
│   ├── schema.sql
│   └── seed.sql
│
├── models/
│   ├── Course.php
│   └── User.php
│
├── views/
│   ├── admin/
│   │   ├── course-form.php
│   │   ├── courses.php
│   │   ├── dashboard.php
│   │   ├── delete-course.php
│   │   ├── download-enrollments.php
│   │   └── enrollments.php
│   │
│   ├── student/
│   │   └── courses.php
│   │
│   └── login.php
│
├── .env
├── .env.example
├── .gitignore
├── index.php
├── logout.php
├── README.md
└── TECHNICAL_NOTES.md


Requirements
PHP 8.1 or newer
MySQL or MariaDB
Apache
XAMPP or another PHP development environment
Installation
1. Clone or copy the project

Place the project inside the XAMPP htdocs directory:

C:\xampp\htdocs\course-enrollment-portal
2. Create the database

Open phpMyAdmin and create a database named:

course_enrollment

Recommended collation:

utf8mb4_unicode_ci
3. Import the database schema

Import:

database/schema.sql

This creates:

users
categories
courses
enrollments
4. Import the seed data

Import:

database/seed.sql

The seed contains:

1 admin
3 students
4 categories
approximately 20 courses
past and upcoming courses
draft and published courses
enrollment records
a course with limited remaining capacity
5. Configure environment variables

Create a .env file in the project root:

DB_HOST=localhost
DB_NAME=course_enrollment
DB_USER=root
DB_PASS=

An example configuration is provided in:

.env.example
6. Start XAMPP

Start:

Apache
MySQL
7. Open the application

Visit:

http://localhost/course-enrollment-portal/
Seed Login Credentials
Admin
Email: admin@example.com
Password: Admin@12345
Student 1
Email: student1@example.com
Password: Student@12345
Student 2
Email: student2@example.com
Password: Student@12345
Student 3
Email: student3@example.com
Password: Student@12345
Enrollment Concurrency

Enrollment uses a database transaction and locks the selected course row using:

SELECT ... FOR UPDATE

The enrollment count and capacity check are performed while the course row is locked.

This prevents simultaneous enrollment requests from both seeing the same available seat and causing an overbooking condition.

Security

The application includes:

PDO prepared statements
Password hashing
Session-based authentication
Session ID regeneration after login
Role-based authorization
CSRF protection for state-changing forms and AJAX requests
Escaped HTML output
Server-side validation
Capacity validation
Duplicate enrollment prevention
Transaction-based enrollment
Database foreign keys
Unique student/course enrollment constraint
Generic database error messages

The .env file is excluded from Git using .gitignore.

Validation

The admin course form validates:

Required course title
Maximum title length of 150 characters
Valid category
Required instructor
Valid start date
Capacity between 1 and 500
Valid course status
Capacity cannot be lower than existing enrollment count
Assumptions
A course is considered started when its start_date is less than or equal to the current database time.
Students can cancel enrollment at any time before the course starts.
Only published and not-yet-started courses are shown to students.
Course deletion removes related enrollment records through the database foreign key cascade.
Administrators manage course categories through the database seed data; category CRUD was not required by the assessment.
The application is intended to run in a standard PHP/MySQL environment such as XAMPP.
Testing

The following workflows were tested during development:

Admin login
Student login
Invalid login
Logout
Role-based access restrictions
Course creation
Course editing
Course deletion confirmation
Enrollment reporting
CSV enrollment export
Student course listing
AJAX search
AJAX pagination
Enrollment
Enrollment cancellation
Full-course handling
Duplicate enrollment prevention
CSRF-protected requests
Database connection through .env
Notes

The project uses plain PHP with a simple MVC-style separation between models, controllers, views, and API endpoints.

No external PHP framework is required.


**Now you only need to copy this entire block once** into `README.md` and save it.

Then tell me **done**.