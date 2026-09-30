# Technical Notes

## 1. Architecture

The application is built using plain PHP with a simple MVC-style structure.

### Main layers

* **Controllers**: Handle authentication-related application flow.
* **Models**: Contain database-related operations for users and courses.
* **Views**: Render HTML pages for administrators and students.
* **API**: Handles AJAX requests for course listing, enrollment, and cancellation.
* **Config**: Contains the database connection.
* **Database**: Contains schema and seed SQL files.
* **Assets**: Contains separate CSS and JavaScript files.

The application does not use a PHP framework because the assessment allows plain PHP and the project scope can be handled cleanly without framework overhead.

---

## 2. Database Design

The application uses four main tables:

### users

Stores administrator and student accounts.

Important fields:

* `id`
* `name`
* `email`
* `password`
* `role`
* `created_at`

Passwords are stored using PHP's `password_hash()` and verified using `password_verify()`.

### categories

Stores course categories.

Important fields:

* `id`
* `name`

### courses

Stores course information.

Important fields:

* `id`
* `title`
* `description`
* `category_id`
* `instructor`
* `start_date`
* `capacity`
* `status`
* `created_at`
* `updated_at`

Each course belongs to one category through a foreign key.

### enrollments

Stores student course enrollments.

Important fields:

* `id`
* `user_id`
* `course_id`
* `enrolled_at`

A unique constraint on `(user_id, course_id)` prevents the same student from enrolling in the same course more than once.

---

## 3. Authentication and Authorization

Authentication is session-based.

After a successful login:

* The user's ID and role are stored in the session.
* `session_regenerate_id(true)` is used to prevent session fixation.
* The user is redirected according to their role.

Invalid credentials always return:

`Invalid email or password`

Admin pages check that the logged-in user has the `admin` role.

Student pages and APIs check that the logged-in user has the `student` role.

Users attempting to access pages belonging to another role are redirected instead of being allowed to access the page.

Logout clears the session and destroys the session cookie/session data.

---

## 4. Environment Configuration

Database credentials are stored in `.env`.

Example structure:

```env
DB_HOST=localhost
DB_NAME=course_enrollment
DB_USER=root
DB_PASS=
```

`.env` is excluded from Git using `.gitignore`.

`.env.example` is included so another developer can create their own local configuration.

The database connection uses PDO with:

* Exception-based error handling
* UTF-8 (`utf8mb4`)
* Native prepared statements
* Associative fetch mode

Database errors are not displayed directly to users.

---

## 5. SQL Injection Protection

All database operations that use user-provided or request-provided values use PDO prepared statements.

Examples include:

* Login queries
* Course searches
* Course creation/update
* Course deletion
* Enrollment
* Cancellation
* Enrollment reports
* CSV export

User input is not directly concatenated into SQL statements.

---

## 6. Output Escaping

Dynamic values displayed in HTML are escaped using:

```php
htmlspecialchars()
```

This is applied to values such as:

* Course titles
* Categories
* Instructor names
* Dates
* User names
* User emails
* Validation messages

The JavaScript course renderer also escapes dynamic values before inserting them into HTML.

---

## 7. CSRF Protection

CSRF tokens are generated and stored in the user's session.

The token is validated for state-changing operations including:

* Course creation
* Course editing
* Course deletion
* Student enrollment
* Student cancellation

The AJAX enrollment and cancellation requests send the CSRF token along with the course ID.

Requests with invalid or missing CSRF tokens are rejected.

---

## 8. Course Validation

Server-side validation is performed regardless of frontend input.

Course validation includes:

* Title is required.
* Title cannot exceed 150 characters.
* Category must be valid.
* Instructor is required.
* Start date must be valid.
* Capacity must be between 1 and 500.
* Status must be either `draft` or `published`.

When editing an existing course, the capacity cannot be reduced below the number of students already enrolled.

Submitted form values are preserved when validation fails.

---

## 9. Enrollment Concurrency and Overbooking Protection

Enrollment uses a database transaction.

Before checking available seats, the course row is locked using:

```sql
SELECT id, start_date, capacity, status
FROM courses
WHERE id = ?
FOR UPDATE
```

The application then:

1. Starts a transaction.
2. Locks the selected course row.
3. Checks that the course exists.
4. Checks that the course is published.
5. Checks that the course has not started.
6. Checks whether the student is already enrolled.
7. Counts current enrollments.
8. Compares enrollment count with course capacity.
9. Inserts the enrollment if a seat is available.
10. Commits the transaction.

Because the course row is locked during the transaction, simultaneous enrollment requests for the same course are serialized. This prevents two requests from both seeing the same final available seat and causing overbooking.

The unique `(user_id, course_id)` constraint provides an additional database-level protection against duplicate enrollment.

---

## 10. Enrollment Cancellation

Students can cancel their enrollment before the course start date.

The cancellation API:

* Requires a valid student session.
* Validates the CSRF token.
* Validates the course ID.
* Checks the course start date.
* Prevents cancellation after the course has started.
* Deletes the student's enrollment.

---

## 11. Student Course Listing

Students can only see courses that are:

* Published
* Not yet started

Courses are ordered by the soonest start date first.

The page uses database-level pagination with:

```sql
LIMIT
OFFSET
```

The page size is six courses.

The API returns pagination information including:

* Current page
* Items per page
* Total courses
* Total pages

The course list does not perform one database query per course. Course information and enrollment information are retrieved using grouped/related queries to avoid an N+1 query pattern.

---

## 12. AJAX Search and Pagination

The student course page supports filtering by:

* Course title
* Category

Filtering and pagination are handled through AJAX without a full page reload.

When the search changes, the page is reset to page 1.

The interface provides:

* Loading state
* Empty result message
* Pagination controls
* Friendly error message if the request fails

Enrollment and cancellation are also handled through AJAX.

---

## 13. Admin Course Management

Administrators can:

* View all courses
* Add courses
* Edit courses
* Delete courses

The admin course table displays:

* Title
* Category
* Start date
* Seats taken
* Capacity
* Status
* Actions

Course deletion requires confirmation.

If students are enrolled in the course, the confirmation page clearly warns that deleting the course will also remove its enrollment records.

The database foreign key uses cascading deletion for related enrollment records.

---

## 14. Enrollment Reports

Administrators can select a course and view its enrolled students.

The report contains:

* Student name
* Student email
* Enrollment date

The report can also be downloaded as a CSV file using PHP's `fputcsv()`.

---

## 15. Database Indexes

Indexes are included for commonly queried fields.

Examples include:

```sql
INDEX idx_courses_status_start_date (status, start_date)
INDEX idx_courses_category (category_id)
INDEX idx_courses_title (title)
INDEX idx_enrollments_course (course_id)
INDEX idx_enrollments_user (user_id)
```

The enrollment table also has a unique index through:

```sql
UNIQUE (user_id, course_id)
```

These indexes support course filtering, sorting, enrollment lookups, and duplicate-enrollment protection.

---

## 16. Error Handling

User-facing pages do not expose raw PHP warnings, SQL queries, database credentials, or database exception details.

The APIs return JSON responses with a success/error structure so the frontend can display appropriate messages.

Unexpected enrollment/cancellation database failures trigger transaction rollback where applicable.

---

## 17. Security Considerations

The following security measures are implemented:

* Password hashing using `password_hash()`
* Password verification using `password_verify()`
* Session ID regeneration after login
* Session destruction on logout
* Role-based authorization
* Prepared SQL statements
* HTML output escaping
* CSRF protection
* Server-side validation
* Database constraints
* Transaction and row locking for enrollment
* `.env` excluded from Git
* No raw database errors displayed to users

---

## 18. Trade-offs and Assumptions

### Plain PHP

Plain PHP was selected instead of a framework to keep the implementation lightweight and aligned with the assessment requirements.

The project separates database logic, request handling, views, and APIs sufficiently for the current scope.

### Environment Loading

A lightweight `.env` loading approach using PHP's `parse_ini_file()` is used instead of adding a third-party environment package.

This keeps the project dependency-free, while `.env.example` documents the required configuration.

### Category Management

The assessment requires courses to belong to categories but does not require a separate category management interface.

Categories are therefore provided through the database seed data and selected when creating or editing courses.

### Course Deletion

Course deletion is allowed from the admin interface. Existing enrollment records are removed through the course foreign key's cascade behavior after the administrator confirms deletion.

### Pagination

Database-level pagination is used rather than loading all student-visible courses into PHP. This keeps the listing more scalable as the number of courses grows.

---

## 19. Testing and Verification

The following functionality was manually tested during development:

* Admin login
* Student login
* Invalid login credentials
* Logout
* Role-based page protection
* Admin dashboard
* Course creation
* Course editing
* Course validation
* Course deletion confirmation
* Protection against reducing capacity below enrollment
* Enrollment report
* CSV enrollment download
* Student course listing
* Course search
* AJAX pagination
* Enrollment
* Cancellation
* Full-course behavior
* CSRF-protected AJAX requests
* Published/upcoming course filtering
* Empty search results

A seeded course with existing enrollments was also used to verify enrollment counts, cancellation behavior, and the course deletion warning.

---

## 20. Future Improvements

If the application were expanded beyond the assessment scope, possible improvements could include:

* Dedicated category management
* More advanced filtering
* Server-side sorting controls
* Centralized validation/error handling
* More automated tests
* Rate limiting for authentication
* Email notifications for enrollment
* Admin audit logs
* More granular permissions
* Production-grade environment configuration using a dedicated environment library
