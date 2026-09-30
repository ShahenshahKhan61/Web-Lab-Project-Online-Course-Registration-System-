<?php

session_start();

require_once "../php/db.php";


/* =========================================
   TIME FUNCTIONS
========================================= */

function convertTimeToMysql($time)
{
    $time = trim($time);

    if ($time === "") {
        return false;
    }

    $time_upper = strtoupper($time);

    $formats = [
        "g:i A",
        "h:i A",
        "H:i",
        "H:i:s"
    ];

    foreach ($formats as $format) {

        $date =
            DateTime::createFromFormat(
                $format,
                $time_upper
            );

        if ($date !== false) {

            $errors =
                DateTime::getLastErrors();

            if (
                $errors === false ||
                (
                    $errors["warning_count"] === 0 &&
                    $errors["error_count"] === 0
                )
            ) {

                return $date->format("H:i:s");
            }
        }
    }

    return false;
}


function formatTimeForDisplay($time)
{
    if (!$time) {
        return "";
    }

    $timestamp =
        strtotime($time);

    if ($timestamp === false) {
        return $time;
    }

    return date(
        "g:i A",
        $timestamp
    );
}


/* =========================================
   COORDINATOR LOGIN CHECK
========================================= */

if (
    !isset($_SESSION["role"]) ||
    $_SESSION["role"] !== "coordinator"
) {

    header("Location: ../login.html");
    exit();

}


$coordinator_name =
    $_SESSION["coordinator_name"] ??
    "Coordinator";

$coordinator_email =
    $_SESSION["coordinator_email"] ??
    "";

$coordinator_department =
    $_SESSION["coordinator_department"] ??
    "";


/* =========================================
   MESSAGE
========================================= */

$message = "";
$message_type = "";


/* =========================================
   EDIT COURSE VARIABLE
========================================= */

$edit_course = null;


/* =========================================
   LOAD COURSE FOR EDIT
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "edit_course"
) {

    $course_code =
        trim($_POST["course_code"] ?? "");


    if ($course_code !== "") {

        $edit_sql =
            $conn->prepare(
                "SELECT
                    course_code,
                    course_name,
                    department,
                    credit,
                    teacher_name,
                    capacity,
                    start_time,
                    end_time,
                    total_sections,
                    schedule_days
                 FROM courses
                 WHERE course_code = ?"
            );


        $edit_sql->bind_param(
            "s",
            $course_code
        );


        $edit_sql->execute();


        $edit_result =
            $edit_sql->get_result();


        if (
            $edit_result &&
            $edit_result->num_rows > 0
        ) {

            $edit_course =
                $edit_result->fetch_assoc();

        } else {

            $message =
                "Course not found.";

            $message_type =
                "error";
        }

    }
}


/* =========================================
   ADD COURSE

   EACH SECTION HAS ITS OWN:

   - Capacity
   - Schedule Days
   - Start Time
   - End Time

   Example:

   CSE345
   Total Sections = 2

   CSE345.1
   Capacity = 40
   Schedule = Sun/Tue
   Time = 10:30 AM - 12:00 PM

   CSE345.2
   Capacity = 35
   Schedule = Mon/Wed
   Time = 2:00 PM - 3:30 PM
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "add_course"
) {

    $course_code =
        trim($_POST["course_code"] ?? "");

    $course_name =
        trim($_POST["course_name"] ?? "");

    $department =
        trim($_POST["department"] ?? "");

    $credit =
        intval($_POST["credit"] ?? 0);

    $teacher_name =
        trim($_POST["teacher_name"] ?? "");

    $total_sections =
        intval($_POST["total_sections"] ?? 0);


    /* =====================================
       SECTION-SPECIFIC DATA
    ====================================== */

    $capacities =
        $_POST["capacity"] ?? [];

    $schedule_days =
        $_POST["schedule_days"] ?? [];

    $start_time_inputs =
        $_POST["start_time"] ?? [];

    $end_time_inputs =
        $_POST["end_time"] ?? [];


    /* =====================================
       BASIC VALIDATION
    ====================================== */

    if (
        $course_code === "" ||
        $course_name === "" ||
        $department === "" ||
        $credit <= 0 ||
        $teacher_name === "" ||
        $total_sections <= 0 ||
        $total_sections > 20
    ) {

        $message =
            "Please fill in all course information.";

        $message_type =
            "error";

    } else {

        /* =================================
           REMOVE EXISTING SECTION NUMBER

           CSE345     -> CSE345
           CSE345.1   -> CSE345
           CSE345.2   -> CSE345
        ================================= */

        $course_parts =
            explode(
                ".",
                $course_code
            );

        $base_course_code =
            trim(
                $course_parts[0]
            );


        /* =================================
           CHECK SECTION DATA
        ================================= */

        $valid_section_data = true;

        $section_data_error = "";


        for (
            $section_number = 1;
            $section_number <= $total_sections;
            $section_number++
        ) {

            $section_capacity =
                intval(
                    $capacities[$section_number] ?? 0
                );


            $section_schedule =
                trim(
                    $schedule_days[
                        $section_number
                    ] ?? ""
                );


            $section_start_input =
                trim(
                    $start_time_inputs[
                        $section_number
                    ] ?? ""
                );


            $section_end_input =
                trim(
                    $end_time_inputs[
                        $section_number
                    ] ?? ""
                );


            if (
                $section_capacity <= 0 ||
                $section_schedule === "" ||
                $section_start_input === "" ||
                $section_end_input === ""
            ) {

                $valid_section_data = false;

                $section_data_error =
                    "Please fill in all information for Section " .
                    $section_number .
                    ".";

                break;
            }


            $section_start =
                convertTimeToMysql(
                    $section_start_input
                );


            $section_end =
                convertTimeToMysql(
                    $section_end_input
                );


            if (
                $section_start === false ||
                $section_end === false
            ) {

                $valid_section_data = false;

                $section_data_error =
                    "Invalid time format in Section " .
                    $section_number .
                    ". Use format like 10:30 AM.";

                break;
            }


            if (
                strtotime($section_end) <=
                strtotime($section_start)
            ) {

                $valid_section_data = false;

                $section_data_error =
                    "End time must be after start time in Section " .
                    $section_number .
                    ".";

                break;
            }

        }


        if (!$valid_section_data) {

            $message =
                $section_data_error;

            $message_type =
                "error";

        } else {

            /* =================================
               CHECK ALL SECTION CODES
               BEFORE INSERTING
            ================================= */

            $sections_exist = false;

            $existing_section = "";


            for (
                $section_number = 1;
                $section_number <= $total_sections;
                $section_number++
            ) {

                /*
                 * One section:
                 * CSE345
                 *
                 * Multiple sections:
                 * CSE345.1
                 * CSE345.2
                 */

                if (
                    $total_sections === 1
                ) {

                    $section_code =
                        $base_course_code;

                } else {

                    $section_code =
                        $base_course_code .
                        "." .
                        $section_number;

                }


                $check =
                    $conn->prepare(
                        "SELECT course_code
                         FROM courses
                         WHERE course_code = ?"
                    );


                $check->bind_param(
                    "s",
                    $section_code
                );


                $check->execute();


                $result =
                    $check->get_result();


                if (
                    $result->num_rows > 0
                ) {

                    $sections_exist = true;

                    $existing_section =
                        $section_code;

                    $check->close();

                    break;
                }


                $check->close();

            }


            /* =================================
               SECTION ALREADY EXISTS
            ================================= */

            if ($sections_exist) {

                $message =
                    "Course section " .
                    $existing_section .
                    " already exists.";

                $message_type =
                    "error";

            } else {

                /* =================================
                   START TRANSACTION
                ================================= */

                $conn->begin_transaction();


                try {

                    for (
                        $section_number = 1;
                        $section_number <= $total_sections;
                        $section_number++
                    ) {

                        /*
                         * CREATE SECTION CODE
                         */

                        if (
                            $total_sections === 1
                        ) {

                            $section_code =
                                $base_course_code;

                        } else {

                            $section_code =
                                $base_course_code .
                                "." .
                                $section_number;

                        }


                        /*
                         * SECTION DATA
                         */

                        $section_capacity =
                            intval(
                                $capacities[
                                    $section_number
                                ]
                            );


                        $section_schedule =
                            trim(
                                $schedule_days[
                                    $section_number
                                ]
                            );


                        $section_start =
                            convertTimeToMysql(
                                trim(
                                    $start_time_inputs[
                                        $section_number
                                    ]
                                )
                            );


                        $section_end =
                            convertTimeToMysql(
                                trim(
                                    $end_time_inputs[
                                        $section_number
                                    ]
                                )
                            );


                        /*
                         * Each row represents
                         * ONE section.
                         */

                        $section_total =
                            1;


                        $insert =
                            $conn->prepare(
                                "INSERT INTO courses
                                (
                                    course_code,
                                    course_name,
                                    department,
                                    credit,
                                    teacher_name,
                                    capacity,
                                    start_time,
                                    end_time,
                                    total_sections,
                                    schedule_days
                                )
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                            );


                        if (!$insert) {

                            throw new Exception(
                                $conn->error
                            );
                        }


                        $insert->bind_param(
                            "sssisissis",
                            $section_code,
                            $course_name,
                            $department,
                            $credit,
                            $teacher_name,
                            $section_capacity,
                            $section_start,
                            $section_end,
                            $section_total,
                            $section_schedule
                        );


                        if (
                            !$insert->execute()
                        ) {

                            throw new Exception(
                                $insert->error
                            );
                        }


                        $insert->close();

                    }


                    $conn->commit();


                    $message =
                        $base_course_code .
                        " added successfully with " .
                        $total_sections .
                        " section(s).";

                    $message_type =
                        "success";


                } catch (Exception $e) {

                    $conn->rollback();


                    $message =
                        "Failed to add course: " .
                        $e->getMessage();

                    $message_type =
                        "error";
                }
            }
        }
    }
}


/* =========================================
   UPDATE COURSE
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "update_course"
) {

    $course_code =
        trim($_POST["course_code"] ?? "");

    $course_name =
        trim($_POST["course_name"] ?? "");

    $department =
        trim($_POST["department"] ?? "");

    $credit =
        intval($_POST["credit"] ?? 0);

    $teacher_name =
        trim($_POST["teacher_name"] ?? "");

    $capacity =
        intval($_POST["capacity"] ?? 0);

    $total_sections =
        intval($_POST["total_sections"] ?? 0);

    $schedule_days =
        trim($_POST["schedule_days"] ?? "");


    $start_time_input =
        trim(
            $_POST["start_time"] ?? ""
        );

    $end_time_input =
        trim(
            $_POST["end_time"] ?? ""
        );


    $start_time =
        convertTimeToMysql(
            $start_time_input
        );

    $end_time =
        convertTimeToMysql(
            $end_time_input
        );


    if (
        $course_code === "" ||
        $course_name === "" ||
        $department === "" ||
        $credit <= 0 ||
        $teacher_name === "" ||
        $capacity < 0 ||
        $total_sections <= 0 ||
        $schedule_days === "" ||
        $start_time === false ||
        $end_time === false
    ) {

        $message =
            "Please enter valid course information.";

        $message_type =
            "error";

    } elseif (
        strtotime($end_time) <=
        strtotime($start_time)
    ) {

        $message =
            "End time must be after start time.";

        $message_type =
            "error";

    } else {

        $update =
            $conn->prepare(
                "UPDATE courses
                 SET
                    course_name = ?,
                    department = ?,
                    credit = ?,
                    teacher_name = ?,
                    capacity = ?,
                    start_time = ?,
                    end_time = ?,
                    total_sections = ?,
                    schedule_days = ?
                 WHERE course_code = ?"
            );


        $update->bind_param(
            "ssisississ",
            $course_name,
            $department,
            $credit,
            $teacher_name,
            $capacity,
            $start_time,
            $end_time,
            $total_sections,
            $schedule_days,
            $course_code
        );


        if (
            $update->execute()
        ) {

            $message =
                "Course updated successfully.";

            $message_type =
                "success";

        } else {

            $message =
                "Failed to update course: " .
                $conn->error;

            $message_type =
                "error";
        }
    }
}


/* =========================================
   DELETE COURSE
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "delete_course"
) {

    $course_code =
        trim($_POST["course_code"] ?? "");


    if (
        $course_code === ""
    ) {

        $message =
            "Course code is missing.";

        $message_type =
            "error";

    } else {

        $delete_reg =
            $conn->prepare(
                "DELETE FROM registrations
                 WHERE course_code = ?"
            );


        $delete_reg->bind_param(
            "s",
            $course_code
        );


        $delete_reg->execute();


        $delete =
            $conn->prepare(
                "DELETE FROM courses
                 WHERE course_code = ?"
            );


        $delete->bind_param(
            "s",
            $course_code
        );


        if (
            $delete->execute()
        ) {

            $message =
                "Course deleted successfully.";

            $message_type =
                "success";

        } else {

            $message =
                "Failed to delete course: " .
                $conn->error;

            $message_type =
                "error";
        }
    }
}


/* =========================================
   REGISTER COURSE FOR SPECIFIC STUDENT
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "add_student_course"
) {

    $student_id =
        trim($_POST["student_id"] ?? "");

    $course_code =
        trim($_POST["course_code"] ?? "");


    if (
        $student_id === "" ||
        $course_code === ""
    ) {

        $message =
            "Please select both student and course.";

        $message_type =
            "error";

    } else {

        /* CHECK STUDENT */

        $student_check =
            $conn->prepare(
                "SELECT student_id
                 FROM students
                 WHERE student_id = ?"
            );


        $student_check->bind_param(
            "s",
            $student_id
        );


        $student_check->execute();


        $student_result =
            $student_check->get_result();


        /* CHECK COURSE */

        $course_check =
            $conn->prepare(
                "SELECT course_code
                 FROM courses
                 WHERE course_code = ?"
            );


        $course_check->bind_param(
            "s",
            $course_code
        );


        $course_check->execute();


        $course_result =
            $course_check->get_result();


        if (
            $student_result->num_rows === 0
        ) {

            $message =
                "Student not found.";

            $message_type =
                "error";

        } elseif (
            $course_result->num_rows === 0
        ) {

            $message =
                "Course not found.";

            $message_type =
                "error";

        } else {

            /* =====================================
               GET BASE COURSE CODE
            ====================================== */

            $course_parts =
                explode(
                    ".",
                    $course_code
                );


            $base_course_code =
                trim(
                    $course_parts[0]
                );


            /* =====================================
               CHECK SAME COURSE
            ====================================== */

            $duplicate =
                $conn->prepare(
                    "SELECT
                        registration_id,
                        course_code
                     FROM registrations
                     WHERE student_id = ?
                     AND (
                         course_code = ?
                         OR course_code LIKE CONCAT(?, '.%')
                     )"
                );


            $duplicate->bind_param(
                "sss",
                $student_id,
                $base_course_code,
                $base_course_code
            );


            $duplicate->execute();


            $duplicate_result =
                $duplicate->get_result();


            if (
                $duplicate_result->num_rows > 0
            ) {

                $existing_registration =
                    $duplicate_result->fetch_assoc();


                $existing_course_code =
                    $existing_registration[
                        "course_code"
                    ];


                $message =
                    "Student is already registered in " .
                    $base_course_code .
                    " (" .
                    $existing_course_code .
                    "). Another section cannot be registered.";

                $message_type =
                    "error";

            } else {

                /* =================================
                   CHECK AVAILABLE SEATS
                ================================= */

                $seat_check =
                    $conn->prepare(
                        "SELECT
                            capacity,
                            total_sections
                         FROM courses
                         WHERE course_code = ?"
                    );


                $seat_check->bind_param(
                    "s",
                    $course_code
                );


                $seat_check->execute();


                $seat_result =
                    $seat_check->get_result();


                $seat_data =
                    $seat_result->fetch_assoc();


                /*
                 * Each row is ONE section.
                 *
                 * Therefore capacity itself
                 * is the available capacity.
                 */

                $capacity =
                    intval(
                        $seat_data["capacity"]
                    );


                $registered_check =
                    $conn->prepare(
                        "SELECT COUNT(*) AS total
                         FROM registrations
                         WHERE course_code = ?"
                    );


                $registered_check->bind_param(
                    "s",
                    $course_code
                );


                $registered_check->execute();


                $registered_result =
                    $registered_check->get_result();


                $registered_data =
                    $registered_result->fetch_assoc();


                $registered =
                    intval(
                        $registered_data["total"]
                    );


                $available_seats =
                    $capacity -
                    $registered;


                if (
                    $available_seats <= 0
                ) {

                    $message =
                        "No available seat for this section.";

                    $message_type =
                        "error";

                } else {

                    $add_registration =
                        $conn->prepare(
                            "INSERT INTO registrations
                            (
                                student_id,
                                course_code
                            )
                            VALUES (?, ?)"
                        );


                    $add_registration->bind_param(
                        "ss",
                        $student_id,
                        $course_code
                    );


                    if (
                        $add_registration->execute()
                    ) {

                        $message =
                            "Course registered successfully for the student.";

                        $message_type =
                            "success";

                    } else {

                        $message =
                            "Failed to register course: " .
                            $conn->error;

                        $message_type =
                            "error";
                    }
                }
            }
        }
    }
}


/* =========================================
   DROP COURSE FROM SPECIFIC STUDENT
========================================= */

if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["action"]) &&
    $_POST["action"] === "drop_course"
) {

    $student_id =
        trim($_POST["student_id"] ?? "");

    $course_code =
        trim($_POST["course_code"] ?? "");


    if (
        $student_id === "" ||
        $course_code === ""
    ) {

        $message =
            "Please select both student and course.";

        $message_type =
            "error";

    } else {

        $check =
            $conn->prepare(
                "SELECT registration_id
                 FROM registrations
                 WHERE student_id = ?
                 AND course_code = ?"
            );


        $check->bind_param(
            "ss",
            $student_id,
            $course_code
        );


        $check->execute();


        $check_result =
            $check->get_result();


        if (
            $check_result->num_rows === 0
        ) {

            $message =
                "This student is not registered for this course.";

            $message_type =
                "error";

        } else {

            $drop =
                $conn->prepare(
                    "DELETE FROM registrations
                     WHERE student_id = ?
                     AND course_code = ?"
                );


            $drop->bind_param(
                "ss",
                $student_id,
                $course_code
            );


            if (
                $drop->execute()
            ) {

                $message =
                    "Course dropped successfully from the student's account.";

                $message_type =
                    "success";

            } else {

                $message =
                    "Failed to drop course: " .
                    $conn->error;

                $message_type =
                    "error";
            }
        }
    }
}


/* =========================================
   GET TEACHERS
========================================= */

$teachers = [];


$teacher_sql =
    "SELECT
        teacher_name,
        email,
        department
     FROM teachers
     ORDER BY teacher_name";


$teacher_result =
    $conn->query(
        $teacher_sql
    );


if ($teacher_result) {

    while (
        $teacher =
        $teacher_result->fetch_assoc()
    ) {

        $teachers[] =
            $teacher;
    }
}


/* =========================================
   GET COURSES
========================================= */

$courses = [];


$course_sql =
    "SELECT
        course_code,
        course_name,
        department,
        credit,
        teacher_name,
        capacity,
        start_time,
        end_time,
        total_sections,
        schedule_days
     FROM courses
     ORDER BY course_code";


$course_result =
    $conn->query(
        $course_sql
    );


if ($course_result) {

    while (
        $course =
        $course_result->fetch_assoc()
    ) {

        $courses[] =
            $course;
    }
}


/* =========================================
   GET STUDENTS
========================================= */

$students = [];


$student_sql =
    "SELECT
        student_id,
        name,
        email,
        department
     FROM students
     ORDER BY student_id";


$student_result =
    $conn->query(
        $student_sql
    );


if ($student_result) {

    while (
        $student =
        $student_result->fetch_assoc()
    ) {

        $students[] =
            $student;
    }
}


/* =========================================
   GET STUDENT REGISTRATIONS
========================================= */

$registrations = [];


$registration_sql =
    "SELECT
        r.student_id,
        r.course_code,
        s.name AS student_name,
        c.course_name
     FROM registrations r

     INNER JOIN students s
        ON r.student_id = s.student_id

     INNER JOIN courses c
        ON r.course_code = c.course_code

     ORDER BY
        r.student_id,
        r.course_code";


$registration_result =
    $conn->query(
        $registration_sql
    );


if ($registration_result) {

    while (
        $registration =
        $registration_result->fetch_assoc()
    ) {

        $registrations[] =
            $registration;
    }
}


/* =========================================
   DASHBOARD STATISTICS

   NEW STRUCTURE:

   Each database row = ONE SECTION

   Therefore:

   Total Courses =
   number of base course codes

   Total Sections =
   number of course rows

   Available Seats =
   sum of section capacities
   minus registrations
========================================= */

$total_courses = 0;

$total_sections =
    count($courses);

$total_capacity = 0;


/* =========================================
   FIND UNIQUE BASE COURSES
========================================= */

$base_courses = [];


foreach (
    $courses as $course
) {

    $course_code =
        $course["course_code"];


    $course_parts =
        explode(
            ".",
            $course_code
        );


    $base_code =
        trim(
            $course_parts[0]
        );


    if (
        !in_array(
            $base_code,
            $base_courses
        )
    ) {

        $base_courses[] =
            $base_code;
    }


    /* =====================================
       EACH ROW IS ONE SECTION
    ====================================== */

    $capacity =
        intval(
            $course["capacity"]
        );


    $registered_check =
        $conn->prepare(
            "SELECT COUNT(*) AS total
             FROM registrations
             WHERE course_code = ?"
        );


    $registered_check->bind_param(
        "s",
        $course_code
    );


    $registered_check->execute();


    $registered_result =
        $registered_check->get_result();


    $registered_data =
        $registered_result->fetch_assoc();


    $registered =
        intval(
            $registered_data["total"]
        );


    $available =
        $capacity -
        $registered;


    if (
        $available < 0
    ) {

        $available = 0;
    }


    $total_capacity +=
        $available;
}


$total_courses =
    count($base_courses);


/* =========================================
   REGISTERED STUDENTS
========================================= */

$registered_students = 0;

$count_sql = "SELECT COUNT(DISTINCT student_id) AS total
              FROM registrations";


$count_result =
    $conn->query(
        $count_sql
    );


if ($count_result) {

    $count_data =
        $count_result->fetch_assoc();


    $registered_students =
        intval(
            $count_data["total"]
        );
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
        Coordinator Dashboard - UniCourse
    </title>

    <link
        rel="stylesheet"
        href="../css/dashboard.css"
    >

</head>


<body>


<div class="dashboard-container">


    <!-- =====================================
         SIDEBAR
    ====================================== -->

    <aside
        class="sidebar"
        id="sidebar"
    >

        <div class="sidebar-header">

            <h2>
                UniCourse
            </h2>

            <button
                class="sidebar-toggle"
                onclick="toggleSidebar()"
            >
                ☰
            </button>

        </div>


        <div class="sidebar-user">

            <strong>

                <?php
                echo htmlspecialchars(
                    $coordinator_name
                );
                ?>

            </strong>

            <span>
                Coordinator
            </span>

        </div>


        <nav class="sidebar-menu">

            <button
                class="menu-item active"
                onclick="showSection(
                    'dashboard',
                    this
                )"
            >

                🏠

                <span>
                    Dashboard
                </span>

            </button>


            <button
                class="menu-item"
                onclick="showSection(
                    'manageCourses',
                    this
                )"
            >

                📚

                <span>
                    Manage Courses
                </span>

            </button>


            <button
                class="menu-item"
                onclick="showSection(
                    'addCourse',
                    this
                )"
            >

                ➕

                <span>
                    Add Course
                </span>

            </button>


            <button
                class="menu-item"
                onclick="showSection(
                    'dropCourse',
                    this
                )"
            >

                📝

                <span>
                    Student Course Registration
                </span>

            </button>


            <button
                class="menu-item"
                onclick="showSection(
                    'profile',
                    this
                )"
            >

                👤

                <span>
                    My Profile
                </span>

            </button>


            <a
                href="../logout.php"
                class="menu-item logout-item"
            >

                🚪

                <span>
                    Logout
                </span>

            </a>

        </nav>

    </aside>


    <!-- =====================================
         MAIN CONTENT
    ====================================== -->

    <main class="main-content">


        <!-- =================================
             DASHBOARD
        ================================== -->

        <section
            id="dashboard"
            class="page-section active"
        >

            <div class="welcome-box">

                <h1>
                    Coordinator Dashboard
                </h1>

                <p>

                    Welcome,

                    <strong>

                        <?php
                        echo htmlspecialchars(
                            $coordinator_name
                        );
                        ?>

                    </strong>

                </p>

                <p>
                    Manage courses, teachers,
                    sections and student capacity.
                </p>

            </div>


            <?php if ($message !== ""): ?>

                <div
                    class="message
                    <?php
                    echo $message_type;
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <div class="stats-grid">

                <div class="stat-card">

                    <h3>
                        Total Courses
                    </h3>

                    <div class="stat-number">

                        <?php
                        echo $total_courses;
                        ?>

                    </div>

                </div>


                <div class="stat-card">

                    <h3>
                        Total Sections
                    </h3>

                    <div class="stat-number">

                        <?php
                        echo $total_sections;
                        ?>

                    </div>

                </div>


                <div class="stat-card">

                    <h3>
                        Available Seats
                    </h3>

                    <div class="stat-number">

                        <?php
                        echo $total_capacity;
                        ?>

                    </div>

                </div>


                <div class="stat-card">

                    <h3>
                        Registered Students
                    </h3>

                    <div class="stat-number">

                        <?php
                        echo $registered_students;
                        ?>

                    </div>

                </div>

            </div>


            <div class="table-card">

                <h2>
                    Current Courses
                </h2>


                <div class="search-box">

                    <span class="search-icon">
                        🔎
                    </span>

                    <input
                        type="text"
                        id="dashboardCourseSearch"
                        placeholder="Search by course code, course name, teacher or department..."
                        onkeyup="searchDashboardCourses()"
                    >

                </div>


                <table
                    class="course-table"
                    id="dashboardCourseTable"
                >

                    <thead>

                        <tr>

                            <th>Code</th>
                            <th>Course</th>
                            <th>Teacher</th>
                            <th>Credit</th>
                            <th>Capacity</th>
                            <th>Sections</th>
                            <th>Available Seats</th>
                            <th>Schedule</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (count($courses) > 0): ?>

                        <?php foreach ($courses as $course): ?>

                            <?php

                            $course_capacity =
                                intval(
                                    $course["capacity"]
                                );


                            $course_code =
                                $course["course_code"];


                            $seat_count_sql =
                                $conn->prepare(
                                    "SELECT COUNT(*) AS total
                                     FROM registrations
                                     WHERE course_code = ?"
                                );


                            $seat_count_sql->bind_param(
                                "s",
                                $course_code
                            );


                            $seat_count_sql->execute();


                            $seat_count_result =
                                $seat_count_sql->get_result();


                            $seat_count_data =
                                $seat_count_result->fetch_assoc();


                            $course_registered =
                                intval(
                                    $seat_count_data["total"]
                                );


                            /*
                             * One row = one section.
                             * So capacity is already
                             * section capacity.
                             */

                            $course_available_seats =
                                $course_capacity -
                                $course_registered;


                            if (
                                $course_available_seats < 0
                            ) {

                                $course_available_seats =
                                    0;
                            }

                            ?>


                            <tr
                                class="dashboard-course-row"
                                data-search="<?php

                                echo htmlspecialchars(
                                    strtolower(
                                        $course["course_code"] . " " .
                                        $course["course_name"] . " " .
                                        $course["teacher_name"] . " " .
                                        $course["department"] . " " .
                                        $course["schedule_days"]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>"
                            >

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course["course_code"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course["course_name"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course["teacher_name"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course["credit"]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course["capacity"]
                                    );
                                    ?>

                                </td>


                                <td>
                                    1
                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo $course_available_seats;
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course["schedule_days"]
                                    );
                                    ?>

                                    <br>

                                    <?php

                                    echo htmlspecialchars(
                                        formatTimeForDisplay(
                                            $course["start_time"]
                                        )
                                    );

                                    ?>

                                    -

                                    <?php

                                    echo htmlspecialchars(
                                        formatTimeForDisplay(
                                            $course["end_time"]
                                        )
                                    );

                                    ?>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="8">
                                No courses available.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================
             MANAGE COURSES
        ================================== -->

        <section
            id="manageCourses"
            class="page-section"
        >

            <div class="welcome-box">

                <h1>
                    Manage Courses
                </h1>

                <p>
                    Update or delete existing courses.
                </p>

            </div>


            <?php if ($edit_course !== null): ?>

                <div class="form-card">

                    <h2>
                        Edit Course
                    </h2>

                    <br>


                    <form method="POST">

                        <input
                            type="hidden"
                            name="action"
                            value="update_course"
                        >


                        <div class="form-grid">


                            <div class="form-group">

                                <label>
                                    Course Code
                                </label>

                                <input
                                    type="text"
                                    name="course_code"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $edit_course[
                                            "course_code"
                                        ]
                                    );
                                    ?>"
                                    readonly
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Course Name
                                </label>

                                <input
                                    type="text"
                                    name="course_name"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $edit_course[
                                            "course_name"
                                        ]
                                    );
                                    ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Department
                                </label>

                                <input
                                    type="text"
                                    name="department"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $edit_course[
                                            "department"
                                        ]
                                    );
                                    ?>"
                                    required
                                >

                            </div>


                           <div class="form-group">

    <label>
        Credit
    </label>

    <select
        name="credit"
        id="credit"
        required
    >

        <option value="">
            Select Credit
        </option>

        <option value="1">
            1 Credit
        </option>

        <option value="3">
            3 Credits
        </option>

    </select>

</div>


                            <div class="form-group">

                                <label>
                                    Teacher
                                </label>

                                <select
                                    name="teacher_name"
                                    required
                                >

                                    <option value="">
                                        Select Teacher
                                    </option>


                                    <?php foreach (
                                        $teachers
                                        as $teacher
                                    ): ?>

                                        <option
                                            value="<?php
                                            echo htmlspecialchars(
                                                $teacher[
                                                    "teacher_name"
                                                ]
                                            );
                                            ?>"
                                            <?php

                                            if (
                                                $teacher[
                                                    "teacher_name"
                                                ]
                                                ===
                                                $edit_course[
                                                    "teacher_name"
                                                ]
                                            ) {

                                                echo "selected";
                                            }

                                            ?>
                                        >

                                            <?php
                                            echo htmlspecialchars(
                                                $teacher[
                                                    "teacher_name"
                                                ]
                                            );
                                            ?>

                                            -

                                            <?php
                                            echo htmlspecialchars(
                                                $teacher[
                                                    "email"
                                                ]
                                            );
                                            ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Capacity
                                </label>

                                <input
                                    type="number"
                                    name="capacity"
                                    min="1"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $edit_course[
                                            "capacity"
                                        ]
                                    );
                                    ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Total Sections
                                </label>

                                <input
                                    type="number"
                                    name="total_sections"
                                    min="1"
                                    value="<?php
                                    echo htmlspecialchars(
                                        $edit_course[
                                            "total_sections"
                                        ]
                                    );
                                    ?>"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    Schedule Days
                                </label>

                                <select
                                    name="schedule_days"
                                    required
                                >

                                    <option value="">
                                        Select Schedule
                                    </option>


                                    <option
                                        value="Sun/Tue"
                                        <?php

                                        if (
                                            $edit_course[
                                                "schedule_days"
                                            ]
                                            ===
                                            "Sun/Tue"
                                        ) {

                                            echo "selected";
                                        }

                                        ?>
                                    >
                                        Sun / Tue
                                    </option>


                                    <option
                                        value="Mon/Wed"
                                        <?php

                                        if (
                                            $edit_course[
                                                "schedule_days"
                                            ]
                                            ===
                                            "Mon/Wed"
                                        ) {

                                            echo "selected";
                                        }

                                        ?>
                                    >
                                        Mon / Wed
                                    </option>


                                    <option
                                        value="Thurs/Sat"
                                        <?php

                                        if (
                                            $edit_course[
                                                "schedule_days"
                                            ]
                                            ===
                                            "Thurs/Sat"
                                        ) {

                                            echo "selected";
                                        }

                                        ?>
                                    >
                                        Thurs / Sat
                                    </option>

                                </select>

                            </div>


                            <div class="form-group">

                                <label>
                                    Start Time
                                </label>

                                <input
                                    type="text"
                                    name="start_time"
                                    value="<?php
                                    echo htmlspecialchars(
                                        formatTimeForDisplay(
                                            $edit_course[
                                                "start_time"
                                            ]
                                        )
                                    );
                                    ?>"
                                    placeholder="Example: 10:30 AM"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                            <div class="form-group">

                                <label>
                                    End Time
                                </label>

                                <input
                                    type="text"
                                    name="end_time"
                                    value="<?php
                                    echo htmlspecialchars(
                                        formatTimeForDisplay(
                                            $edit_course[
                                                "end_time"
                                            ]
                                        )
                                    );
                                    ?>"
                                    placeholder="Example: 12:00 PM"
                                    autocomplete="off"
                                    required
                                >

                            </div>


                        </div>


                        <div class="form-actions">

                            <button
                                type="submit"
                                class="btn btn-success"
                            >
                                Update Course
                            </button>


                            <button
                                type="button"
                                class="btn btn-secondary"
                                onclick="cancelEdit()"
                            >
                                Cancel
                            </button>

                        </div>

                    </form>

                </div>

            <?php endif; ?>


            <div class="table-card">

                <h2>
                    All Courses
                </h2>


                <div class="search-box">

                    <span class="search-icon">
                        🔎
                    </span>

                    <input
                        type="text"
                        id="manageCourseSearch"
                        placeholder="Search by course code, course name, teacher or department..."
                        onkeyup="searchManageCourses()"
                    >

                </div>


                <table
                    class="course-table"
                    id="manageCourseTable"
                >

                    <thead>

                        <tr>

                            <th>Code</th>
                            <th>Course</th>
                            <th>Department</th>
                            <th>Credit</th>
                            <th>Teacher</th>
                            <th>Capacity</th>
                            <th>Sections</th>
                            <th>Total Seats</th>
                            <th>Schedule</th>
                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php if (
                        count($courses) > 0
                    ): ?>


                        <?php foreach (
                            $courses
                            as $course
                        ): ?>


                            <?php

                            $course_capacity =
                                intval(
                                    $course[
                                        "capacity"
                                    ]
                                );


                            $course_code =
                                $course[
                                    "course_code"
                                ];


                            $seat_count_sql =
                                $conn->prepare(
                                    "SELECT COUNT(*) AS total
                                     FROM registrations
                                     WHERE course_code = ?"
                                );


                            $seat_count_sql->bind_param(
                                "s",
                                $course_code
                            );


                            $seat_count_sql->execute();


                            $seat_count_result =
                                $seat_count_sql->get_result();


                            $seat_count_data =
                                $seat_count_result->fetch_assoc();


                            $course_registered =
                                intval(
                                    $seat_count_data[
                                        "total"
                                    ]
                                );


                            $course_total_seats =
                                $course_capacity;

                            ?>


                            <tr
                                class="manage-course-row"
                                data-search="<?php

                                echo htmlspecialchars(
                                    strtolower(
                                        $course[
                                            "course_code"
                                        ] . " " .
                                        $course[
                                            "course_name"
                                        ] . " " .
                                        $course[
                                            "teacher_name"
                                        ] . " " .
                                        $course[
                                            "department"
                                        ] . " " .
                                        $course[
                                            "schedule_days"
                                        ]
                                    ),
                                    ENT_QUOTES,
                                    "UTF-8"
                                );

                                ?>"
                            >

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "course_code"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "course_name"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "department"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "credit"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "teacher_name"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "capacity"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>
                                    1
                                </td>


                                <td>

                                    <strong>

                                        <?php
                                        echo $course_total_seats;
                                        ?>

                                    </strong>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $course[
                                            "schedule_days"
                                        ]
                                    );
                                    ?>

                                    <br>

                                    <?php

                                    echo htmlspecialchars(
                                        formatTimeForDisplay(
                                            $course[
                                                "start_time"
                                            ]
                                        )
                                    );

                                    ?>

                                    -

                                    <?php

                                    echo htmlspecialchars(
                                        formatTimeForDisplay(
                                            $course[
                                                "end_time"
                                            ]
                                        )
                                    );

                                    ?>

                                </td>


                                <td>

                                    <div
                                        class="action-buttons"
                                    >


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="edit_course"
                                            >


                                            <input
                                                type="hidden"
                                                name="course_code"
                                                value="<?php

                                                echo htmlspecialchars(
                                                    $course[
                                                        "course_code"
                                                    ],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );

                                                ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-primary small-btn"
                                            >
                                                Edit
                                            </button>

                                        </form>


                                        <form
                                            method="POST"
                                            style="display:inline;"
                                            onsubmit="return confirmDelete();"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="delete_course"
                                            >


                                            <input
                                                type="hidden"
                                                name="course_code"
                                                value="<?php

                                                echo htmlspecialchars(
                                                    $course[
                                                        "course_code"
                                                    ],
                                                    ENT_QUOTES,
                                                    "UTF-8"
                                                );

                                                ?>"
                                            >


                                            <button
                                                type="submit"
                                                class="btn btn-danger small-btn"
                                            >
                                                Delete
                                            </button>

                                        </form>


                                    </div>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>

                        <tr>

                            <td colspan="10">
                                No courses available.
                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================
             ADD COURSE
        ================================== -->

        <section
            id="addCourse"
            class="page-section"
        >

            <div class="welcome-box">

                <h1 id="courseFormTitle">
                    Add New Course
                </h1>

                <p>
                    Create or update a university course.
                </p>

            </div>


            <div class="form-card">

                <form
                    method="POST"
                    id="courseForm"
                >

                    <input
                        type="hidden"
                        name="action"
                        id="formAction"
                        value="add_course"
                    >


                    <!-- COMMON COURSE INFORMATION -->

                    <div class="form-grid">


                        <div class="form-group">

                            <label>
                                Course Code
                            </label>

                            <input
                                type="text"
                                name="course_code"
                                id="course_code"
                                placeholder="Example: CSE345"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Course Name
                            </label>

                            <input
                                type="text"
                                name="course_name"
                                id="course_name"
                                placeholder="Example: Web Engineering"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Department
                            </label>

                            <input
                                type="text"
                                name="department"
                                id="department"
                                value="CSE"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Credit
                            </label>

                            <input
                                type="number"
                                name="credit"
                                id="credit"
                                min="1"
                                max="4"
                                placeholder="3"
                                required
                            >

                        </div>


                        <div class="form-group">

                            <label>
                                Teacher
                            </label>

                            <select
                                name="teacher_name"
                                id="teacher_name"
                                required
                            >

                                <option value="">
                                    Select Teacher
                                </option>


                                <?php foreach (
                                    $teachers
                                    as $teacher
                                ): ?>

                                    <option
                                        value="<?php
                                        echo htmlspecialchars(
                                            $teacher[
                                                "teacher_name"
                                            ]
                                        );
                                        ?>"
                                    >

                                        <?php
                                        echo htmlspecialchars(
                                            $teacher[
                                                "teacher_name"
                                            ]
                                        );
                                        ?>

                                        -

                                        <?php
                                        echo htmlspecialchars(
                                            $teacher[
                                                "email"
                                            ]
                                        );
                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <div class="form-group">

                            <label>
                                Total Sections
                            </label>

                            <input
                                type="number"
                                name="total_sections"
                                id="total_sections"
                                min="1"
                                max="20"
                                value="1"
                                required
                            >

                        </div>

                    </div>


                    <!-- =================================
                         SECTION INFORMATION
                    ================================== -->

                    <div
                        id="sectionContainer"
                    ></div>


                    <div class="form-actions">

                        <button
                            type="submit"
                            class="btn btn-success"
                            id="submitButton"
                        >
                            Add Course
                        </button>


                        <button
                            type="button"
                            class="btn btn-secondary"
                            id="cancelEditButton"
                            onclick="resetCourseForm()"
                            style="display:none;"
                        >
                            Cancel Edit
                        </button>

                    </div>

                </form>

            </div>

        </section>


        <!-- =================================
             STUDENT COURSE REGISTRATION
        ================================== -->

        <section
            id="dropCourse"
            class="page-section"
        >

            <div class="welcome-box">

                <h1>
                    Student Course Registration
                </h1>

                <p>
                    Search for a student and register or drop courses from the student's account.
                </p>

            </div>


            <?php if ($message !== ""): ?>

                <div
                    class="message
                    <?php
                    echo $message_type;
                    ?>"
                >

                    <?php
                    echo htmlspecialchars(
                        $message
                    );
                    ?>

                </div>

            <?php endif; ?>


            <div class="form-card">

                <h2>
                    Search Student
                </h2>

                <br>


                <div class="student-search-box">

                    <input
                        type="text"
                        id="studentSearch"
                        placeholder="Search by Student ID, name or email..."
                        onkeyup="searchStudents()"
                        autocomplete="off"
                    >


                    <div
                        id="studentResults"
                        class="student-results"
                    >
                    </div>


                    <div
                        id="selectedStudentBox"
                        class="selected-student"
                    >
                    </div>

                </div>


                <input
                    type="hidden"
                    id="selectedStudentId"
                    value=""
                >

            </div>


            <div id="studentCourseActions">

                <div class="student-action-grid">


                    <div
                        class="form-card student-action-card add-card"
                    >

                        <h2>
                            ➕ Register Course for Student
                        </h2>

                        <p class="action-description">

                            Register an existing course
                            for the selected student.

                        </p>

                        <br>


                        <form
                            method="POST"
                            onsubmit="return confirmAddCourse();"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="add_student_course"
                            >


                            <input
                                type="hidden"
                                name="student_id"
                                id="add_student_id"
                                value=""
                            >


                            <div class="form-group">

                                <label for="add_course">

                                    Select Course to Register

                                </label>


                                <select
                                    name="course_code"
                                    id="add_course"
                                    required
                                >

                                    <option value="">

                                        Select Student First

                                    </option>

                                </select>

                            </div>


                            <div class="form-actions">

                                <button
                                    type="submit"
                                    class="btn btn-success"
                                >

                                    ➕ Register Course

                                </button>

                            </div>

                        </form>

                    </div>


                    <div
                        class="form-card student-action-card drop-card"
                    >

                        <h2>
                            ➖ Drop Course for Student
                        </h2>

                        <p class="action-description">

                            Remove a registered course
                            from the selected student's account.

                        </p>

                        <br>


                        <form
                            method="POST"
                            onsubmit="return confirmDropCourse();"
                        >

                            <input
                                type="hidden"
                                name="action"
                                value="drop_course"
                            >


                            <input
                                type="hidden"
                                name="student_id"
                                id="drop_student_id"
                                value=""
                            >


                            <div class="form-group">

                                <label for="drop_course">

                                    Select Course to Drop

                                </label>


                                <select
                                    name="course_code"
                                    id="drop_course"
                                    required
                                >

                                    <option value="">

                                        Select Student First

                                    </option>

                                </select>

                            </div>


                            <div class="form-actions">

                                <button
                                    type="submit"
                                    class="btn btn-danger"
                                >

                                    ➖ Drop Course

                                </button>

                            </div>

                        </form>

                    </div>


                </div>

            </div>


            <div class="table-card">

                <h2>
                    Current Student Registrations
                </h2>

                <br>


                <table class="course-table">

                    <thead>

                        <tr>

                            <th>
                                Student ID
                            </th>

                            <th>
                                Student Name
                            </th>

                            <th>
                                Course Code
                            </th>

                            <th>
                                Course Name
                            </th>

                        </tr>

                    </thead>


                    <tbody>


                    <?php if (
                        count($registrations) > 0
                    ): ?>


                        <?php foreach (
                            $registrations
                            as $registration
                        ): ?>


                            <tr>

                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $registration[
                                            "student_id"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $registration[
                                            "student_name"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $registration[
                                            "course_code"
                                        ]
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo htmlspecialchars(
                                        $registration[
                                            "course_name"
                                        ]
                                    );
                                    ?>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                    <?php else: ?>


                        <tr>

                            <td colspan="4">

                                No course registrations found.

                            </td>

                        </tr>


                    <?php endif; ?>


                    </tbody>

                </table>

            </div>

        </section>


        <!-- =================================
             PROFILE
        ================================== -->

        <section
            id="profile"
            class="page-section"
        >

            <div class="welcome-box">

                <h1>
                    My Profile
                </h1>

                <p>
                    Coordinator account information.
                </p>

            </div>


            <div class="profile-card">


                <div class="profile-row">

                    <span class="profile-label">
                        Name
                    </span>

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $coordinator_name
                        );
                        ?>

                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Email
                    </span>

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $coordinator_email
                        );
                        ?>

                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Department
                    </span>

                    <span>

                        <?php
                        echo htmlspecialchars(
                            $coordinator_department
                        );
                        ?>

                    </span>

                </div>


                <div class="profile-row">

                    <span class="profile-label">
                        Role
                    </span>

                    <span>
                        Coordinator
                    </span>

                </div>


            </div>

        </section>


    </main>

</div>


<script>


/* =========================================
   SIDEBAR
========================================= */

function toggleSidebar()
{

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
    clickedButton
)
{

    const sections =
        document.querySelectorAll(
            ".page-section"
        );


    sections.forEach(
        function(section)
        {

            section.classList.remove(
                "active"
            );

        }
    );


    const selectedSection =
        document.getElementById(
            sectionId
        );


    if (selectedSection) {

        selectedSection.classList.add(
            "active"
        );

    }


    const menuItems =
        document.querySelectorAll(
            ".menu-item"
        );


    menuItems.forEach(
        function(item)
        {

            item.classList.remove(
                "active"
            );

        }
    );


    if (clickedButton) {

        clickedButton.classList.add(
            "active"
        );

    }

}


/* =========================================
   DASHBOARD COURSE SEARCH
========================================= */

function searchDashboardCourses()
{

    const searchInput =
        document.getElementById(
            "dashboardCourseSearch"
        );


    const searchText =
        searchInput.value
        .toLowerCase()
        .trim();


    const rows =
        document.querySelectorAll(
            ".dashboard-course-row"
        );


    rows.forEach(
        function(row)
        {

            const searchableText =
                row
                .getAttribute(
                    "data-search"
                )
                .toLowerCase();


            if (
                searchableText.includes(
                    searchText
                )
            ) {

                row.style.display =
                    "";

            } else {

                row.style.display =
                    "none";

            }

        }
    );

}


/* =========================================
   MANAGE COURSE SEARCH
========================================= */

function searchManageCourses()
{

    const searchInput =
        document.getElementById(
            "manageCourseSearch"
        );


    const searchText =
        searchInput.value
        .toLowerCase()
        .trim();


    const rows =
        document.querySelectorAll(
            ".manage-course-row"
        );


    rows.forEach(
        function(row)
        {

            const searchableText =
                row
                .getAttribute(
                    "data-search"
                )
                .toLowerCase();


            if (
                searchableText.includes(
                    searchText
                )
            ) {

                row.style.display =
                    "";

            } else {

                row.style.display =
                    "none";

            }

        }
    );

}


/* =========================================
   CANCEL EDIT
========================================= */

function cancelEdit()
{

    window.location.href =
        "dashboard.php";

}


/* =========================================
   RESET ADD COURSE FORM
========================================= */

function resetCourseForm()
{

    document.getElementById(
        "courseForm"
    ).reset();


    document.getElementById(
        "formAction"
    ).value =
        "add_course";


    document.getElementById(
        "courseFormTitle"
    ).innerText =
        "Add New Course";


    document.getElementById(
        "submitButton"
    ).innerText =
        "Add Course";


    document.getElementById(
        "cancelEditButton"
    ).style.display =
        "none";


    document.getElementById(
        "course_code"
    ).readOnly =
        false;


    document.getElementById(
        "department"
    ).value =
        "CSE";


    generateSectionFields();

}


/* =========================================
   DELETE CONFIRMATION
========================================= */

function confirmDelete()
{

    return confirm(
        "Are you sure you want to delete this course?\n\n" +
        "All registrations for this course will also be removed."
    );

}


/* =========================================
   STUDENT DATA
========================================= */

const students =
    <?php

    echo json_encode(
        $students
    );

    ?>;


const registrations =
    <?php

    echo json_encode(
        $registrations
    );

    ?>;


const courses =
    <?php

    echo json_encode(
        $courses
    );

    ?>;


/* =========================================
   GET BASE COURSE CODE

   CSE345.1 -> CSE345
   CSE345.2 -> CSE345
========================================= */

function getBaseCourseCode(
    courseCode
)
{

    return String(courseCode)
        .split(".")[0]
        .trim()
        .toUpperCase();

}


/* =========================================
   SEARCH STUDENTS
========================================= */

function searchStudents()
{

    const searchInput =
        document.getElementById(
            "studentSearch"
        );


    const resultBox =
        document.getElementById(
            "studentResults"
        );


    const searchText =
        searchInput.value
        .toLowerCase()
        .trim();


    resultBox.innerHTML =
        "";


    if (
        searchText === ""
    ) {

        resultBox.style.display =
            "none";

        return;

    }


    let found =
        false;


    students.forEach(
        function(student)
        {

            const studentId =
                String(
                    student.student_id
                ).toLowerCase();


            const studentName =
                String(
                    student.name
                ).toLowerCase();


            const studentEmail =
                String(
                    student.email
                ).toLowerCase();


            if (
                studentId.includes(
                    searchText
                ) ||
                studentName.includes(
                    searchText
                ) ||
                studentEmail.includes(
                    searchText
                )
            ) {

                found =
                    true;


                const item =
                    document.createElement(
                        "div"
                    );


                item.className =
                    "student-result-item";


                item.innerHTML =
                    "<strong>" +
                    escapeHtml(
                        student.student_id
                    ) +
                    " - " +
                    escapeHtml(
                        student.name
                    ) +
                    "</strong>" +
                    "<br>" +
                    "<small>" +
                    escapeHtml(
                        student.email
                    ) +
                    "</small>";


                item.onclick =
                    function()
                    {

                        selectStudent(
                            student
                        );

                    };


                resultBox.appendChild(
                    item
                );

            }

        }
    );


    if (!found) {

        resultBox.innerHTML =
            "<div class='student-result-item'>" +
            "No student found." +
            "</div>";

    }


    resultBox.style.display =
        "block";

}


/* =========================================
   SELECT STUDENT
========================================= */

function selectStudent(
    student
)
{

    const searchInput =
        document.getElementById(
            "studentSearch"
        );


    const resultBox =
        document.getElementById(
            "studentResults"
        );


    const selectedBox =
        document.getElementById(
            "selectedStudentBox"
        );


    const selectedStudentId =
        document.getElementById(
            "selectedStudentId"
        );


    const addStudentId =
        document.getElementById(
            "add_student_id"
        );


    const dropStudentId =
        document.getElementById(
            "drop_student_id"
        );


    searchInput.value =
        student.student_id +
        " - " +
        student.name;


    resultBox.style.display =
        "none";


    selectedStudentId.value =
        student.student_id;


    addStudentId.value =
        student.student_id;


    dropStudentId.value =
        student.student_id;


    selectedBox.innerHTML =
        "<strong>Selected Student:</strong> " +
        escapeHtml(
            student.student_id
        ) +
        " - " +
        escapeHtml(
            student.name
        ) +
        "<br>" +
        "<small>" +
        escapeHtml(
            student.email
        ) +
        " | " +
        escapeHtml(
            student.department
        ) +
        "</small>";


    selectedBox.style.display =
        "block";


    loadStudentCourses(
        student.student_id
    );

}


/* =========================================
   LOAD STUDENT COURSES
========================================= */

function loadStudentCourses(
    studentId
)
{

    const addCourseSelect =
        document.getElementById(
            "add_course"
        );


    const dropCourseSelect =
        document.getElementById(
            "drop_course"
        );


    addCourseSelect.innerHTML =
        "";


    dropCourseSelect.innerHTML =
        "";


    const registeredCourseCodes =
        [];


    registrations.forEach(
        function(registration)
        {

            if (
                String(
                    registration.student_id
                ) ===
                String(studentId)
            ) {

                registeredCourseCodes.push(
                    String(
                        registration.course_code
                    )
                );

            }

        }
    );


    const registeredBaseCourseCodes =
        [];


    registeredCourseCodes.forEach(
        function(courseCode)
        {

            const baseCode =
                getBaseCourseCode(
                    courseCode
                );


            if (
                !registeredBaseCourseCodes.includes(
                    baseCode
                )
            ) {

                registeredBaseCourseCodes.push(
                    baseCode
                );

            }

        }
    );


    /* =====================================
       DROP COURSE
    ====================================== */

    if (
        registeredCourseCodes.length === 0
    ) {

        const option =
            document.createElement(
                "option"
            );


        option.value =
            "";


        option.textContent =
            "No registered courses";


        dropCourseSelect.appendChild(
            option
        );

    } else {

        const defaultOption =
            document.createElement(
                "option"
            );


        defaultOption.value =
            "";


        defaultOption.textContent =
            "Select Course to Drop";


        dropCourseSelect.appendChild(
            defaultOption
        );


        registrations.forEach(
            function(registration)
            {

                if (
                    String(
                        registration.student_id
                    ) ===
                    String(studentId)
                ) {

                    const option =
                        document.createElement(
                            "option"
                        );


                    option.value =
                        registration.course_code;


                    option.textContent =
                        registration.course_code +
                        " - " +
                        registration.course_name;


                    dropCourseSelect.appendChild(
                        option
                    );

                }

            }
        );

    }


    /* =====================================
       REGISTER COURSE
    ====================================== */

    let availableCourses =
        0;


    const defaultAddOption =
        document.createElement(
            "option"
        );


    defaultAddOption.value =
        "";


    defaultAddOption.textContent =
        "Select Course to Register";


    addCourseSelect.appendChild(
        defaultAddOption
    );


    courses.forEach(
        function(course)
        {

            const courseCode =
                String(
                    course.course_code
                );


            const baseCourseCode =
                getBaseCourseCode(
                    courseCode
                );


            if (
                !registeredBaseCourseCodes.includes(
                    baseCourseCode
                )
            ) {

                const option =
                    document.createElement(
                        "option"
                    );


                option.value =
                    courseCode;


                option.textContent =
                    courseCode +
                    " - " +
                    course.course_name;


                addCourseSelect.appendChild(
                    option
                );


                availableCourses++;

            }

        }
    );


    if (
        availableCourses === 0
    ) {

        addCourseSelect.innerHTML =
            "";


        const option =
            document.createElement(
                "option"
            );


        option.value =
            "";


        option.textContent =
            "All courses already registered";


        addCourseSelect.appendChild(
            option
        );

    }

}


/* =========================================
   REGISTER COURSE CONFIRMATION
========================================= */

function confirmAddCourse()
{

    return confirm(
        "Are you sure you want to register this course for the selected student?"
    );

}


/* =========================================
   DROP COURSE CONFIRMATION
========================================= */

function confirmDropCourse()
{

    return confirm(
        "Are you sure you want to drop this course from the student's account?"
    );

}


/* =========================================
   ESCAPE HTML
========================================= */

function escapeHtml(
    value
)
{

    const div =
        document.createElement(
            "div"
        );


    div.textContent =
        value;


    return div.innerHTML;

}


/* =========================================
   GENERATE SECTION FIELDS
========================================= */

function generateSectionFields()
{
    const totalInput =
        document.getElementById(
            "total_sections"
        );

    const creditInput =
        document.getElementById(
            "credit"
        );

    const container =
        document.getElementById(
            "sectionContainer"
        );

    if (
        !totalInput ||
        !creditInput ||
        !container
    ) {
        return;
    }


    let total =
        parseInt(
            totalInput.value
        );


    const credit =
        parseInt(
            creditInput.value
        );


    if (
        isNaN(total) ||
        total < 1
    ) {
        total = 1;
    }


    if (total > 20) {

        total = 20;

        totalInput.value = 20;

    }


    /* =====================================
       1 CREDIT = 1 DAY
       3 CREDIT = 2 DAYS
    ====================================== */

    let scheduleOptions = "";


    if (credit === 1) {

        scheduleOptions = `

            <option value="">
                Select Day
            </option>

            <option value="Sun">
                Sunday
            </option>

            <option value="Mon">
                Monday
            </option>

            <option value="Tue">
                Tuesday
            </option>

            <option value="Wed">
                Wednesday
            </option>

            <option value="Thurs">
                Thursday
            </option>

            <option value="Sat">
                Saturday
            </option>

        `;

    }
    else if (credit === 3) {

        scheduleOptions = `

            <option value="">
                Select Schedule
            </option>

            <option value="Sun/Tue">
                Sun / Tue
            </option>

            <option value="Mon/Wed">
                Mon / Wed
            </option>

            <option value="Thurs/Sat">
                Thurs / Sat
            </option>

        `;

    }
    else {

        scheduleOptions = `

            <option value="">
                Select Credit First
            </option>

        `;

    }


    /* =====================================
       CLEAR OLD SECTION FIELDS
    ====================================== */

    container.innerHTML = "";


    /* =====================================
       CREATE SECTION FIELDS
    ====================================== */

    for (
        let i = 1;
        i <= total;
        i++
    ) {

        const sectionBox =
            document.createElement(
                "div"
            );


        sectionBox.className =
            "section-box";


        sectionBox.innerHTML = `

            <h3>
                Section ${i}
            </h3>


            <div class="form-grid">


                <div class="form-group">

                    <label>
                        Section Capacity
                    </label>

                    <input
                        type="number"
                        name="capacity[${i}]"
                        min="1"
                        placeholder="Example: 40"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        Schedule
                    </label>

                    <select
                        name="schedule_days[${i}]"
                        required
                    >

                        ${scheduleOptions}

                    </select>

                </div>


                <div class="form-group">

                    <label>
                        Start Time
                    </label>

                    <input
                        type="text"
                        name="start_time[${i}]"
                        placeholder="Example: 10:30 AM"
                        autocomplete="off"
                        required
                    >

                </div>


                <div class="form-group">

                    <label>
                        End Time
                    </label>

                    <input
                        type="text"
                        name="end_time[${i}]"
                        placeholder="Example: 12:00 PM"
                        autocomplete="off"
                        required
                    >

                </div>


            </div>

        `;


        container.appendChild(
            sectionBox
        );

    }

}

/* =========================================
   SECTION INPUT EVENTS
========================================= */

document.addEventListener(
    "DOMContentLoaded",
    function()
    {

        const totalInput =
            document.getElementById(
                "total_sections"
            );


        const creditInput =
            document.getElementById(
                "credit"
            );


        if (totalInput) {

            totalInput.addEventListener(
                "input",
                generateSectionFields
            );

            totalInput.addEventListener(
                "change",
                generateSectionFields
            );

        }


        if (creditInput) {

            creditInput.addEventListener(
                "input",
                generateSectionFields
            );

            creditInput.addEventListener(
                "change",
                generateSectionFields
            );

        }


        generateSectionFields();

    }
);

</script>


</body>

</html>