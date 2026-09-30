document.addEventListener('DOMContentLoaded', function () {

    const searchForm = document.querySelector('.course-filter form');
    const searchInput = document.querySelector('#search');
    const courseGrid = document.querySelector('.course-grid');
    const noCoursesMessage = document.querySelector('.no-courses');
    const pagination = document.querySelector('.pagination');

    /*
    |--------------------------------------------------------------------------
    | Enrollment / Cancellation
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', async function (event) {

        const enrollButton = event.target.closest('.enroll-button');
        const cancelButton = event.target.closest('.cancel-button');

        if (!enrollButton && !cancelButton) {
            return;
        }

        const button = enrollButton || cancelButton;
        const courseId = button.dataset.courseId;

        if (!courseId) {
            return;
        }

        const isCancel = !!cancelButton;

        button.disabled = true;

        const originalText = button.textContent;

        button.textContent = isCancel
            ? 'Cancelling...'
            : 'Enrolling...';

       const formData = new FormData();

formData.append('course_id', courseId);
formData.append('csrf_token', csrfToken);

        const endpoint = isCancel
            ? '../../api/cancel.php'
            : '../../api/enroll.php';

        try {

            const response = await fetch(endpoint, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (!data.success) {

                alert(data.message);

                button.disabled = false;
                button.textContent = originalText;

                return;
            }

            loadCourses(
                getCurrentPage(),
                searchInput ? searchInput.value.trim() : ''
            );

        } catch (error) {

            alert('Something went wrong. Please try again.');

            button.disabled = false;
            button.textContent = originalText;
        }
    });

    /*
    |--------------------------------------------------------------------------
    | AJAX Search
    |--------------------------------------------------------------------------
    */

    if (searchForm) {

        searchForm.addEventListener('submit', function (event) {

            event.preventDefault();

            const search = searchInput
                ? searchInput.value.trim()
                : '';

            loadCourses(1, search);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | AJAX Pagination
    |--------------------------------------------------------------------------
    */

    document.addEventListener('click', function (event) {

        const paginationLink = event.target.closest(
            '.pagination a[data-page]'
        );

        if (!paginationLink) {
            return;
        }

        event.preventDefault();

        const page = parseInt(
            paginationLink.dataset.page,
            10
        );

        const search = searchInput
            ? searchInput.value.trim()
            : '';

        loadCourses(page, search);
    });

    /*
    |--------------------------------------------------------------------------
    | Load Courses
    |--------------------------------------------------------------------------
    */

    async function loadCourses(page, search) {

        showLoading();

        try {

            const params = new URLSearchParams();

            params.append('page', page);
            params.append('search', search);

            const response = await fetch(
                `../../api/courses.php?${params.toString()}`
            );

            const data = await response.json();

            if (!data.success) {
                showError(data.message || 'Unable to load courses.');
                return;
            }

            renderCourses(data.courses);
            renderPagination(data.pagination);

        } catch (error) {

            showError(
                'Unable to load courses. Please try again.'
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Render Courses
    |--------------------------------------------------------------------------
    */

    function renderCourses(courses) {

        if (!courseGrid) {
            return;
        }

        courseGrid.innerHTML = '';

        if (courses.length === 0) {

            courseGrid.style.display = 'none';

            if (noCoursesMessage) {
                noCoursesMessage.textContent = 'No courses found.';
                noCoursesMessage.style.display = 'block';
            }

            if (pagination) {
                pagination.style.display = 'none';
            }

            return;
        }

        courseGrid.style.display = 'grid';

        if (noCoursesMessage) {
            noCoursesMessage.style.display = 'none';
        }

        courses.forEach(function (course) {

            const card = document.createElement('article');

            card.className = 'course-card';

            const startDate = new Date(
                course.start_date.replace(' ', 'T')
            );

            const formattedDate = startDate.toLocaleString(
                'en-US',
                {
                    month: 'short',
                    day: '2-digit',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit'
                }
            );

            let actionButton = '';

            if (course.is_enrolled) {

                actionButton = `
                    <button
                        type="button"
                        class="cancel-button"
                        data-course-id="${course.id}"
                    >
                        Cancel Enrollment
                    </button>
                `;

            } else if (course.seats_left <= 0) {

                actionButton = `
                    <button
                        type="button"
                        class="full-button"
                        disabled
                    >
                        Full
                    </button>
                `;

            } else {

                actionButton = `
                    <button
                        type="button"
                        class="enroll-button"
                        data-course-id="${course.id}"
                    >
                        Enroll
                    </button>
                `;
            }

            card.innerHTML = `
                <div class="course-card-content">

                    <span class="course-category">
                        ${escapeHtml(course.category_name)}
                    </span>

                    <h3>
                        ${escapeHtml(course.title)}
                    </h3>

                    <p class="course-description">
                        ${escapeHtml(course.description || '')}
                    </p>

                    <div class="course-details">

                        <p>
                            <strong>Instructor:</strong>
                            ${escapeHtml(course.instructor)}
                        </p>

                        <p>
                            <strong>Start Date:</strong>
                            ${escapeHtml(formattedDate)}
                        </p>

                        <p>
                            <strong>Seats Left:</strong>
                            ${course.seats_left}
                        </p>

                    </div>

                    <div class="course-action">
                        ${actionButton}
                    </div>

                </div>
            `;

            courseGrid.appendChild(card);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Pagination
    |--------------------------------------------------------------------------
    */

    function renderPagination(data) {

        if (!pagination) {
            return;
        }

        pagination.innerHTML = '';

        if (data.total_pages <= 1) {
            pagination.style.display = 'none';
            return;
        }

        pagination.style.display = 'flex';

        if (data.page > 1) {

            const previous = document.createElement('a');

            previous.href = '#';
            previous.dataset.page = data.page - 1;
            previous.textContent = 'Previous';

            pagination.appendChild(previous);
        }

        const pageInfo = document.createElement('span');

        pageInfo.textContent =
            `Page ${data.page} of ${data.total_pages}`;

        pagination.appendChild(pageInfo);

        if (data.page < data.total_pages) {

            const next = document.createElement('a');

            next.href = '#';
            next.dataset.page = data.page + 1;
            next.textContent = 'Next';

            pagination.appendChild(next);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Loading State
    |--------------------------------------------------------------------------
    */

    function showLoading() {

        if (courseGrid) {
            courseGrid.innerHTML = `
                <div class="no-courses">
                    Loading courses...
                </div>
            `;

            courseGrid.style.display = 'block';
        }

        if (noCoursesMessage) {
            noCoursesMessage.style.display = 'none';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Error State
    |--------------------------------------------------------------------------
    */

    function showError(message) {

        if (courseGrid) {

            courseGrid.innerHTML = `
                <div class="no-courses">
                    ${escapeHtml(message)}
                </div>
            `;

            courseGrid.style.display = 'block';
        }

        if (pagination) {
            pagination.style.display = 'none';
        }

        if (noCoursesMessage) {
            noCoursesMessage.style.display = 'none';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Current Page
    |--------------------------------------------------------------------------
    */

    function getCurrentPage() {

        const pageInfo = document.querySelector(
            '.pagination span'
        );

        if (!pageInfo) {
            return 1;
        }

        const match = pageInfo.textContent.match(
            /Page\s+(\d+)/
        );

        return match
            ? parseInt(match[1], 10)
            : 1;
    }

    /*
    |--------------------------------------------------------------------------
    | Escape HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        const div = document.createElement('div');

        div.textContent = value;

        return div.innerHTML;
    }

});