 <?php

session_start();

require_once "db.php";


/* =========================================
   CHECK STUDENT LOGIN
========================================= */

if (!isset($_SESSION["student_id"])) {

    echo "Please login as a student.";
    exit();

}

$student_id = $_SESSION["student_id"];


/* =========================================
   GET COURSE CODE
========================================= */

$course_code = trim($_POST["course_code"] ?? "");

if ($course_code === "") {

    echo "Course code was not received.";
    exit();

}


/* =========================================
   FIND COURSE
========================================= */

$sql = "SELECT
            course_code,
            course_name,
            credit,
            capacity
        FROM courses
        WHERE TRIM(course_code) = TRIM(?)";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo "Database Query Error: " . $conn->error;
    exit();

}

$stmt->bind_param("s", $course_code);

$stmt->execute();

$stmt->store_result();


/* =========================================
   COURSE NOT FOUND
========================================= */

if ($stmt->num_rows === 0) {

    echo "Course not found: " . $course_code;

    $stmt->close();

    exit();

}


/* =========================================
   GET COURSE DATA
========================================= */

$stmt->bind_result(
    $course_code_db,
    $course_name,
    $credit,
    $capacity
);

$stmt->fetch();

$stmt->close();


/* =========================================
   CHECK ALREADY REGISTERED
   SAME BASE COURSE = SAME COURSE

   Example:
   CSE345.1 -> CSE345
   CSE345.2 -> CSE345

   Student can register only ONE section.
========================================= */

$sql = "SELECT
            registration_id,
            course_code
        FROM registrations
        WHERE student_id = ?
        AND UPPER(TRIM(SUBSTRING_INDEX(course_code, '.', 1)))
            =
            UPPER(TRIM(SUBSTRING_INDEX(?, '.', 1)))";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo "Database Query Error: " . $conn->error;
    exit();

}

$stmt->bind_param(
    "ss",
    $student_id,
    $course_code_db
);

$stmt->execute();

$stmt->store_result();


/* =========================================
   SAME COURSE ALREADY REGISTERED
========================================= */

if ($stmt->num_rows > 0) {

    $stmt->bind_result(
        $existing_registration_id,
        $existing_course_code
    );

    $stmt->fetch();

    echo "You are already registered for this course in section "
         . $existing_course_code . ".";

    $stmt->close();

    exit();

}

$stmt->close();


/* =========================================
   CHECK SEAT
========================================= */

$capacity = (int)$capacity;

if ($capacity <= 0) {

    echo "Seat not available for " . $course_name . ".";

    exit();

}


/* =========================================
   INSERT REGISTRATION
========================================= */

$sql = "INSERT INTO registrations
        (
            student_id,
            course_code,
            registration_date
        )
        VALUES
        (
            ?,
            ?,
            NOW()
        )";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo "Database Query Error: " . $conn->error;
    exit();

}

$stmt->bind_param(
    "ss",
    $student_id,
    $course_code_db
);


if (!$stmt->execute()) {

    echo "Registration failed: " . $stmt->error;

    $stmt->close();

    exit();

}

$stmt->close();


/* =========================================
   DECREASE AVAILABLE SEAT
========================================= */

$sql = "UPDATE courses
        SET capacity = capacity - 1
        WHERE TRIM(course_code) = TRIM(?)
        AND capacity > 0";

$stmt = $conn->prepare($sql);

if (!$stmt) {

    echo "Registration saved, but seat update failed.";

    exit();

}

$stmt->bind_param(
    "s",
    $course_code_db
);


if (!$stmt->execute()) {

    echo "Registration saved, but seat update failed.";

    $stmt->close();

    exit();

}

$stmt->close();


/* =========================================
   SUCCESS
========================================= */

echo "success";

?>