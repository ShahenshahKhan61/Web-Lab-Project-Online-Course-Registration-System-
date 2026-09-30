 <?php

session_start();

require_once "db.php";


/* =========================================
   COORDINATOR LOGIN CHECK
========================================= */

if (!isset($_SESSION["coordinator_name"])) {

    echo "Please login as a coordinator.";

    exit();

}


/* =========================================
   GET COURSE DATA
========================================= */

$course_code =
    trim($_POST["course_code"] ?? "");

$course_name =
    trim($_POST["course_name"] ?? "");

$department =
    trim($_POST["department"] ?? "");

$credit =
    (int)($_POST["credit"] ?? 0);

$teacher_name =
    trim($_POST["teacher_name"] ?? "");

$total_sections =
    (int)($_POST["total_sections"] ?? 1);

$capacity =
    (int)($_POST["capacity"] ?? 0);

$schedule_days =
    trim($_POST["schedule_days"] ?? "");

$start_time =
    trim($_POST["start_time"] ?? "");

$end_time =
    trim($_POST["end_time"] ?? "");

$room =
    trim($_POST["room"] ?? "");


/* =========================================
   VALIDATION
========================================= */

if (
    $course_code === "" ||
    $course_name === "" ||
    $department === "" ||
    $credit <= 0 ||
    $teacher_name === "" ||
    $total_sections <= 0 ||
    $capacity < 0 ||
    $schedule_days === "" ||
    $start_time === "" ||
    $end_time === "" ||
    $room === ""
) {

    showError(
        "Please fill in all course fields correctly."
    );

}


/* =========================================
   CHECK COURSE CODE
========================================= */

$sql = "SELECT course_code
        FROM courses
        WHERE course_code = ?";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Query Error: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $course_code
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows > 0) {

    $stmt->close();

    showError(
        "Course code already exists."
    );

}

$stmt->close();


/* =========================================
   ADD COURSE
========================================= */

$sql = "INSERT INTO courses
        (
            course_code,
            course_name,
            department,
            credit,
            teacher_name,
            capacity,
            start_time,
            end_time,
            room,
            total_sections,
            schedule_days
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?
        )";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Query Error: " .
        $conn->error
    );

}


/* =========================================
   PARAMETER TYPES
========================================= */

$stmt->bind_param(
    "sssisssssis",
    $course_code,
    $course_name,
    $department,
    $credit,
    $teacher_name,
    $capacity,
    $start_time,
    $end_time,
    $room,
    $total_sections,
    $schedule_days
);


/* =========================================
   SAVE COURSE
========================================= */

if ($stmt->execute()) {

    $stmt->close();

    echo "<script>

        alert('Course added successfully.');

        window.location.href =
            '../coordinator/dashboard.php';

    </script>";

    exit();

}


/* =========================================
   ERROR
========================================= */

$error = $stmt->error;

$stmt->close();

showError(
    "Course could not be added: " .
    $error
);


/* =========================================
   ERROR FUNCTION
========================================= */

function showError($message)
{

    echo "<script>

        alert(" .
        json_encode($message) .
        ");

        window.history.back();

    </script>";

    exit();

}

?>