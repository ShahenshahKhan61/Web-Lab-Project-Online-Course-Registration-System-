<?php

session_start();

require_once "../php/db.php";


/* =========================================
   CHECK STUDENT LOGIN
========================================= */

if (!isset($_SESSION["student_id"])) {

    header("Location: ../login.html");
    exit;

}

$student_id = $_SESSION["student_id"];


/* =========================================
   GET STUDENT INFORMATION
========================================= */

$student_name = "";
$student_email = "";
$student_department = "";

$sql = "SELECT name, email, department
        FROM students
        WHERE student_id = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    die("Student query failed: " . $conn->error);
}

$stmt->bind_param("s", $student_id);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    $student = $result->fetch_assoc();

    $student_name =
        $student["name"] ?? "";

    $student_email =
        $student["email"] ?? "";

    $student_department =
        $student["department"] ?? "";

}

$stmt->close();


/* =========================================
   GET ALL COURSES
========================================= */

$courses = [];

$sql = "SELECT *
        FROM courses
        ORDER BY course_code";

$result = $conn->query($sql);

if (!$result) {
    die("Course query failed: " . $conn->error);
}

while ($row = $result->fetch_assoc()) {

    $courses[] = $row;

}


/* =========================================
   GET REGISTERED COURSES
========================================= */

$registered_courses = [];

$sql = "SELECT
            r.registration_id,
            r.student_id,
            r.course_code,
            r.registration_date,

            c.course_code AS db_course_code,
            c.course_name,
            c.credit,
            c.teacher_name,
            c.department,
            c.schedule_days,
            c.start_time,
            c.end_time,
            c.total_sections,
            c.capacity

        FROM registrations r

        LEFT JOIN courses c
            ON TRIM(r.course_code) =
               TRIM(c.course_code)

        WHERE TRIM(r.student_id) =
              TRIM(?)

        ORDER BY r.registration_date DESC";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Registered course query failed: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $student_id
);

$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $registered_courses[] = $row;

}

$stmt->close();




/* =========================================
   COUNTS
========================================= */

$total_courses = 0;

$course_codes = [];

foreach ($courses as $course) {

    $code = $course["course_code"];

    if (strpos($code, ".") !== false) {
        $code = explode(".", $code)[0];
    }

    if (!in_array($code, $course_codes)) {
        $course_codes[] = $code;
    }
}

$total_courses = count($course_codes);

$registered_count = count($registered_courses);

$available_count = $total_courses - $registered_count;

if ($available_count < 0) {
    $available_count = 0;
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
        Student Dashboard - UniCourse
    </title>

    <link
        rel="stylesheet" href="../css/dashboard.css"
    >

</head>


<body>


<!-- =========================================
     SIDEBAR
========================================= -->

<div
    class="sidebar"
    id="sidebar"
>

    <div class="sidebar-header">

        <h2>
            UniCourse
        </h2>

        <button
            type="button"
            class="sidebar-toggle"
            onclick="toggleSidebar()"
        >
            ☰
        </button>

    </div>


    <div class="student-info">

        <div class="student-avatar">

            <?php

            echo strtoupper(
                substr(
                    $student_name,
                    0,
                    1
                )
            );

            ?>

        </div>


        <div class="student-details">

            <h3>

                <?php

                echo htmlspecialchars(
                    $student_name
                );

                ?>

            </h3>

            <p>
                Student
            </p>

        </div>

    </div>


    <nav class="sidebar-nav">


        <a
            href="#"
            class="nav-link active"
            onclick="showSection('dashboard', this); return false;"
        >

            <span>
                🏠
            </span>

            <span class="nav-text">
                Dashboard
            </span>

        </a>


        <a
            href="#"
            class="nav-link"
            onclick="showSection('available-courses', this); return false;"
        >

            <span>
                📚
            </span>

            <span class="nav-text">
                Available Courses
            </span>

        </a>


        <a
            href="#"
            class="nav-link"
            onclick="showSection('my-courses', this); return false;"
        >

            <span>
                📝
            </span>

            <span class="nav-text">
                My Courses
            </span>

        </a>


        <a
            href="#"
            class="nav-link"
            onclick="showSection('my-profile', this); return false;"
        >

            <span>
                👤
            </span>

            <span class="nav-text">
                My Profile
            </span>

        </a>


        <a
            href="../logout.php"
            class="nav-link logout-link"
        >

            <span>
                🚪
            </span>

            <span class="nav-text">
                Logout
            </span>

        </a>


    </nav>

</div>



<!-- =========================================
     MAIN CONTENT
========================================= -->

<div
    class="main-content"
    id="mainContent"
>


    <!-- =====================================
         DASHBOARD
    ====================================== -->

    <section
        id="dashboard"
        class="content-section"
    >

        <div class="top-header">

            <div>

                <h1>
                    Student Dashboard
                </h1>

                <p>

                    Welcome back,
                    <?php

                    echo htmlspecialchars(
                        $student_name
                    );

                    ?>!

                </p>

            </div>

        </div>


        <div class="stats-container">


            <div class="stat-card">

                <div class="stat-icon">
                    📚
                </div>

                <div>

                    <h3>
                        <?php
                        echo $total_courses;
                        ?>
                    </h3>

                    <p>
                        Total Courses
                    </p>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    📝
                </div>

                <div>

                    <h3>
                        <?php
                        echo $registered_count;
                        ?>
                    </h3>

                    <p>
                        My Courses
                    </p>

                </div>

            </div>


            <div class="stat-card">

                <div class="stat-icon">
                    ✅
                </div>

                <div>

                    <h3>
                        <?php
                        echo $available_count;
                        ?>
                    </h3>

                    <p>
                        Available Courses
                    </p>

                </div>

            </div>


        </div>


        <div class="welcome-card">

            <h2>
                Welcome to UniCourse
            </h2>

            <p>
                You can browse available courses,
                register for courses, and view your
                registered courses from this dashboard.
            </p>

        </div>

    </section>



    <!-- =====================================
         AVAILABLE COURSES
    ====================================== -->

    <section
        id="available-courses"
        class="content-section"
        style="display: none;"
    >

        <div class="top-header">

            <div>

                <h1>
                    Available Courses
                </h1>

                <p>
                    Select a course and register
                    if seats are available.
                </p>

            </div>

        </div>


        <div class="search-container">

            <input
                type="text"
                id="courseSearch"
                placeholder="Search course..."
                onkeyup="searchCourses()"
            >

        </div>


        <div class="course-grid">


            <?php if (empty($courses)): ?>


                <div class="no-courses">

                    <h2>
                        No Courses Available
                    </h2>

                    <p>
                        There are currently no courses
                        in the system.
                    </p>

                </div>


            <?php else: ?>


                <?php foreach ($courses as $course): ?>


                    <?php

                    $capacity =
                        (int)($course["capacity"] ?? 0);

                    $course_code =
                        $course["course_code"] ?? "";

                    $course_name =
                        $course["course_name"] ?? "";

                    $credit =
                        $course["credit"] ?? "";

                    $teacher_name =
                        $course["teacher_name"] ?? "";

                    $department =
                        $course["department"] ?? "";

                    $schedule_days =
                        $course["schedule_days"] ?? "";

                    $start_time =
                        $course["start_time"] ?? "";

                    $end_time =
                        $course["end_time"] ?? "";

                    ?>


                    <div
                        class="course-card"
                        data-search="<?php

                        echo htmlspecialchars(
                            $course_code . " " .
                            $course_name . " " .
                            $teacher_name . " " .
                            $department,
                            ENT_QUOTES,
                            "UTF-8"
                        );

                        ?>"
                    >


                        <div class="course-header">

                            <span class="course-code">

                                <?php

                                echo htmlspecialchars(
                                    $course_code
                                );

                                ?>

                            </span>


                            <span class="credit">

                                <?php

                                echo htmlspecialchars(
                                    $credit
                                );

                                ?>

                                Credit

                            </span>

                        </div>


                        <h2>

                            <?php

                            echo htmlspecialchars(
                                $course_name
                            );

                            ?>

                        </h2>


                        <div class="course-info">


                            <p>

                                <strong>
                                    Instructor:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $teacher_name
                                );

                                ?>

                            </p>


                            <p>

                                <strong>
                                    Department:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $department
                                );

                                ?>

                            </p>


                            <p>

                                <strong>
                                    Schedule:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $schedule_days
                                );

                                ?>

                            </p>


                            <p>

                                <strong>
                                    Time:
                                </strong>

                                <?php

                                echo htmlspecialchars(
                                    $start_time
                                );

                                ?>

                                -

                                <?php

                                echo htmlspecialchars(
                                    $end_time
                                );

                                ?>

                            </p>


                            <p>

                                <strong>
                                    Available Seats:
                                </strong>

                                <?php

                                echo $capacity;

                                ?>

                            </p>


                        </div>


                        <?php if ($capacity > 0): ?>


                            <button
                                type="button"
                                class="register-btn"
                                onclick="registerCourse(this.dataset.course)"
                                data-course="<?php

                                echo htmlspecialchars(
                                    $course_code,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>"
                            >

                                Register

                            </button>


                        <?php else: ?>


                            <button
                                type="button"
                                class="register-btn disabled"
                                onclick="showSeatNotAvailable(this.dataset.course)"
                                data-course="<?php

                                echo htmlspecialchars(
                                    $course_name,
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>"
                            >

                                Seat Full

                            </button>


                        <?php endif; ?>


                    </div>


                <?php endforeach; ?>


            <?php endif; ?>


        </div>

    </section>



    <!-- =====================================
         MY COURSES
    ====================================== -->

    <section
        id="my-courses"
        class="content-section"
        style="display: none;"
    >

        <div class="top-header">

            <div>

                <h1>
                    My Courses
                </h1>

                <p>
                    Courses you have registered for.
                </p>

            </div>

        </div>


        <?php if (empty($registered_courses)): ?>


            <div class="no-courses">

                <h2>
                    No Registered Courses
                </h2>

                <p>
                    You have not registered for
                    any course yet.
                </p>

            </div>


        <?php else: ?>


            <div class="table-container">

                <table class="courses-table">

                    <thead>

                        <tr>

                            <th>
                                Course Code
                            </th>

                            <th>
                                Course Name
                            </th>

                            <th>
                                Credit
                            </th>

                            <th>
                                Instructor
                            </th>

                            <th>
                                Schedule
                            </th>

                            <th>
                                Time
                            </th>

                            <th>
                                Registration Date
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                        <?php foreach (
                            $registered_courses
                            as $course
                        ): ?>


                            <tr>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course["course_code"]
                                        ??
                                        $course["db_course_code"]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course["course_name"]
                                        ??
                                        "Course information unavailable"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course["credit"]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course["teacher_name"]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course["schedule_days"]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    $start =
                                        $course["start_time"]
                                        ?? "";

                                    $end =
                                        $course["end_time"]
                                        ?? "";


                                    if (
                                        $start !== "" &&
                                        $end !== ""
                                    ) {

                                        echo htmlspecialchars(
                                            $start .
                                            " - " .
                                            $end
                                        );

                                    } else {

                                        echo "N/A";

                                    }

                                    ?>

                                </td>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            "registration_date"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>


                            </tr>


                        <?php endforeach; ?>


                    </tbody>

                </table>

            </div>


        <?php endif; ?>


    </section>



    <!-- =====================================
         MY PROFILE
    ====================================== -->

    <section
        id="my-profile"
        class="content-section"
        style="display: none;"
    >

        <div class="top-header">

            <div>

                <h1>
                    My Profile
                </h1>

                <p>
                    View your student information.
                </p>

            </div>

        </div>


        <div class="profile-card">


            <div class="profile-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $student_name,
                        0,
                        1
                    )
                );

                ?>

            </div>


            <div class="profile-details">


                <div class="profile-row">

                    <strong>
                        Student ID
                    </strong>

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $student_id
                        );

                        ?>

                    </span>

                </div>


                <div class="profile-row">

                    <strong>
                        Name
                    </strong>

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $student_name
                        );

                        ?>

                    </span>

                </div>


                <div class="profile-row">

                    <strong>
                        Email
                    </strong>

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $student_email
                        );

                        ?>

                    </span>

                </div>


                <div class="profile-row">

                    <strong>
                        Department
                    </strong>

                    <span>

                        <?php

                        echo htmlspecialchars(
                            $student_department
                        );

                        ?>

                    </span>

                </div>


            </div>

        </div>

    </section>


</div>



<!-- =========================================
     JAVASCRIPT
========================================= -->

<script src="../js/student_dashboard.js"></script>


<script>


/* =========================================
   SIDEBAR TOGGLE
========================================= */

function toggleSidebar() {

    const sidebar =
        document.getElementById("sidebar");

    sidebar.classList.toggle(
        "collapsed"
    );

}


/* =========================================
   SHOW SECTION
========================================= */

function showSection(
    sectionId,
    clickedLink
) {

    const sections =
        document.querySelectorAll(
            ".content-section"
        );


    sections.forEach(
        function(section) {

            section.style.display =
                "none";

        }
    );


    const selectedSection =
        document.getElementById(
            sectionId
        );


    if (selectedSection) {

        selectedSection.style.display =
            "block";

    }


    const navLinks =
        document.querySelectorAll(
            ".nav-link"
        );


    navLinks.forEach(
        function(link) {

            link.classList.remove(
                "active"
            );

        }
    );


    if (clickedLink) {

        clickedLink.classList.add(
            "active"
        );

    }

}


/* =========================================
   SEAT NOT AVAILABLE
========================================= */

function showSeatNotAvailable(
    courseName
) {

    alert(
        "Seat not available for " +
        courseName +
        "."
    );

}


</script>


</body>

</html>