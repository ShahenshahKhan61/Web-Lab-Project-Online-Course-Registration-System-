<?php

session_start();

require_once "../php/db.php";


/* =========================================
   CHECK TEACHER LOGIN
========================================= */

if (!isset($_SESSION["teacher_name"])) {

    header("Location: ../login.html");

    exit;

}


$teacher_name =
    $_SESSION["teacher_name"];

$teacher_email =
    $_SESSION["teacher_email"] ?? "";

$teacher_department =
    $_SESSION["teacher_department"] ?? "";


/* =========================================
   GET TEACHER'S COURSES
========================================= */

$courses = [];

$sql = "SELECT *
        FROM courses
        WHERE TRIM(teacher_name) = TRIM(?)
        ORDER BY course_code";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Course query failed: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $teacher_name
);

$stmt->execute();

$result =
    $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $courses[] = $row;

}

$stmt->close();


/* =========================================
   GET REGISTERED STUDENTS
========================================= */

$registered_students = [];

$sql = "SELECT
            r.registration_id,
            r.course_code,
            r.registration_date,

            s.student_id,
            s.name,
            s.email,
            s.department,

            c.course_name,
            c.teacher_name

        FROM registrations r

        INNER JOIN students s
            ON TRIM(r.student_id) =
               TRIM(s.student_id)

        INNER JOIN courses c
            ON TRIM(r.course_code) =
               TRIM(c.course_code)

        WHERE TRIM(c.teacher_name) =
              TRIM(?)

        ORDER BY
            c.course_code,
            s.name";


$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Registered student query failed: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $teacher_name
);

$stmt->execute();

$result =
    $stmt->get_result();

while ($row = $result->fetch_assoc()) {

    $registered_students[] = $row;

}

$stmt->close();


/* =========================================
   COUNT UNIQUE REGISTERED STUDENTS
========================================= */

$student_ids = [];

foreach ($registered_students as $student) {

    $student_id = $student["student_id"] ?? "";

    if (!in_array($student_id, $student_ids)) {

        $student_ids[] = $student_id;

    }

}

$total_students =
    count($student_ids);


/* =========================================
   COUNT COURSES
========================================= */

$total_courses =
    count($courses);


/* =========================================
   COUNT TOTAL AVAILABLE SEATS
========================================= */

$total_available_seats = 0;

foreach ($courses as $course) {

    $capacity =
        (int)($course["capacity"] ?? 0);

    $total_available_seats +=
        $capacity;

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
        Teacher Dashboard - UniCourse
    </title>


    <link
        rel="stylesheet"
        href="../css/dashboard.css"
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


    <!-- SIDEBAR HEADER -->

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



    <!-- TEACHER INFORMATION -->

    <div class="student-info">

        <div class="student-avatar">

            <?php

            echo strtoupper(
                substr(
                    $teacher_name,
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
                    $teacher_name
                );

                ?>

            </h3>


            <p>
                Teacher
            </p>

        </div>

    </div>



    <!-- NAVIGATION -->

    <nav class="sidebar-nav">


        <!-- DASHBOARD -->

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



        <!-- MY COURSES -->

        <a
            href="#"
            class="nav-link"
            onclick="showSection('my-courses', this); return false;"
        >

            <span>
                📚
            </span>

            <span class="nav-text">
                My Courses
            </span>

        </a>



        <!-- COURSE SCHEDULE -->

        <a
            href="#"
            class="nav-link"
            onclick="showSection('course-schedule', this); return false;"
        >

            <span>
                🗓️
            </span>

            <span class="nav-text">
                Course Schedule
            </span>

        </a>



        <!-- REGISTERED STUDENTS -->

        <a
            href="#"
            class="nav-link"
            onclick="showSection('registered-students', this); return false;"
        >

            <span>
                👨‍🎓
            </span>

            <span class="nav-text">
                Registered Students
            </span>

        </a>



        <!-- PROFILE -->

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



        <!-- LOGOUT -->

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
         DASHBOARD SECTION
    ====================================== -->

    <section
        id="dashboard"
        class="content-section"
    >


        <div class="top-header">

            <div>

                <h1>
                    Teacher Dashboard
                </h1>


                <p>

                    Welcome back,
                    <?php

                    echo htmlspecialchars(
                        $teacher_name
                    );

                    ?>!

                </p>

            </div>

        </div>



        <!-- =================================
             STATISTICS
        ================================== -->

        <div class="stats-container">


            <!-- TOTAL COURSES -->

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
                        My Courses
                    </p>

                </div>

            </div>



            <!-- REGISTERED STUDENTS -->

            <div class="stat-card">

                <div class="stat-icon">
                    👨‍🎓
                </div>


                <div>

                    <h3>

                        <?php

                        echo $total_students;

                        ?>

                    </h3>


                    <p>
                        Registered Students
                    </p>

                </div>

            </div>



            <!-- AVAILABLE SEATS -->

            <div class="stat-card">

                <div class="stat-icon">
                    💺
                </div>


                <div>

                    <h3>

                        <?php

                        echo $total_available_seats;

                        ?>

                    </h3>


                    <p>
                        Available Seats
                    </p>

                </div>

            </div>


        </div>



        <!-- WELCOME -->

        <div class="welcome-card">

            <h2>
                Welcome to UniCourse
            </h2>


            <p>

                From here you can view your assigned
                courses, check course schedules,
                and see the students registered
                in your courses.

            </p>

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
                    Courses assigned to you.
                </p>

            </div>

        </div>



        <?php if (empty($courses)): ?>


            <div class="no-courses">

                <h2>
                    No Courses Assigned
                </h2>


                <p>

                    There are currently no courses
                    assigned to you.

                </p>

            </div>


        <?php else: ?>


            <div class="course-grid">


                <?php foreach ($courses as $course): ?>


                    <?php

                    $course_code =
                        $course["course_code"] ?? "";

                    $course_name =
                        $course["course_name"] ?? "";

                    $credit =
                        $course["credit"] ?? "";

                    $department =
                        $course["department"] ?? "";

                    $schedule_days =
                        $course["schedule_days"] ?? "";

                    $start_time =
                        $course["start_time"] ?? "";

                    $end_time =
                        $course["end_time"] ?? "";

                    $capacity =
                        $course["capacity"] ?? 0;

                    ?>


                    <div
                        class="course-card"
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

                                echo htmlspecialchars(
                                    $capacity
                                );

                                ?>

                            </p>


                        </div>


                    </div>


                <?php endforeach; ?>


            </div>


        <?php endif; ?>


    </section>



    <!-- =====================================
         COURSE SCHEDULE
    ====================================== -->

    <section
        id="course-schedule"
        class="content-section"
        style="display: none;"
    >


        <div class="top-header">

            <div>

                <h1>
                    Course Schedule
                </h1>


                <p>
                    Your current teaching schedule.
                </p>

            </div>

        </div>



        <?php if (empty($courses)): ?>


            <div class="no-courses">

                <h2>
                    No Schedule Available
                </h2>


                <p>
                    No courses are assigned to you.
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
                                Schedule
                            </th>

                            <th>
                                Start Time
                            </th>

                            <th>
                                End Time
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                        <?php foreach (
                            $courses
                            as $course
                        ): ?>


                            <tr>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            "course_code"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            "course_name"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            "schedule_days"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            "start_time"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $course[
                                            "end_time"
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
         REGISTERED STUDENTS
    ====================================== -->

    <section
        id="registered-students"
        class="content-section"
        style="display: none;"
    >


        <div class="top-header">

            <div>

                <h1>
                    Registered Students
                </h1>


                <p>
                    Students registered in your courses.
                </p>

            </div>

        </div>



        <?php if (empty($registered_students)): ?>


            <div class="no-courses">

                <h2>
                    No Registered Students
                </h2>


                <p>

                    No students have registered
                    for your courses yet.

                </p>

            </div>


        <?php else: ?>


            <div class="table-container">


                <table class="courses-table">


                    <thead>

                        <tr>

                            <th>
                                Student ID
                            </th>

                            <th>
                                Student Name
                            </th>

                            <th>
                                Email
                            </th>

                            <th>
                                Department
                            </th>

                            <th>
                                Course Code
                            </th>

                            <th>
                                Course Name
                            </th>

                            <th>
                                Registration Date
                            </th>

                        </tr>

                    </thead>



                    <tbody>


                        <?php foreach (
                            $registered_students
                            as $student
                        ): ?>


                            <tr>


                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
                                            "student_id"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
                                            "name"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
                                            "email"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
                                            "department"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
                                            "course_code"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
                                            "course_name"
                                        ]
                                        ??
                                        "N/A"
                                    );

                                    ?>

                                </td>



                                <td>

                                    <?php

                                    echo htmlspecialchars(
                                        $student[
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
                    View your teacher information.
                </p>

            </div>

        </div>



        <div class="profile-card">


            <div class="profile-avatar">

                <?php

                echo strtoupper(
                    substr(
                        $teacher_name,
                        0,
                        1
                    )
                );

                ?>

            </div>



            <div class="profile-details">


                <div class="profile-row">

                    <strong>
                        Teacher Name
                    </strong>


                    <span>

                        <?php

                        echo htmlspecialchars(
                            $teacher_name
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
                            $teacher_email
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
                            $teacher_department
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

<script>


/* =========================================
   SIDEBAR TOGGLE
========================================= */

function toggleSidebar() {

    const sidebar =
        document.getElementById(
            "sidebar"
        );


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


</script>


</body>

</html>
