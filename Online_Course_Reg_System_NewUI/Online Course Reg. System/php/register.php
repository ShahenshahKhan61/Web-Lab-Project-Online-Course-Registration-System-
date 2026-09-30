<?php

require_once "db.php";


/* =========================================
   GET FORM DATA
========================================= */

$student_id =
    trim($_POST["studentId"] ?? "");

$name =
    trim($_POST["name"] ?? "");

$email =
    trim($_POST["email"] ?? "");

$department =
    trim($_POST["department"] ?? "");

$password =
    $_POST["password"] ?? "";

$confirm_password =
    $_POST["confirmPassword"] ?? "";


/* =========================================
   VALIDATION
========================================= */

if (
    $student_id === "" ||
    $name === "" ||
    $email === "" ||
    $department === "" ||
    $password === "" ||
    $confirm_password === ""
) {

    showError(
        "Please fill in all fields."
    );

}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    showError(
        "Please enter a valid email address."
    );

}

if ($password !== $confirm_password) {

    showError(
        "Passwords do not match."
    );

}

if (strlen($password) < 6) {

    showError(
        "Password must be at least 6 characters."
    );

}


/* =========================================
   CHECK STUDENT ID
========================================= */

$sql = "SELECT student_id
        FROM students
        WHERE student_id = ?";

$stmt =
    $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Query Error: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $student_id
);

$stmt->execute();

$result =
    $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    showError(
        "Student ID already exists."
    );

}

$stmt->close();


/* =========================================
   CHECK EMAIL
========================================= */

$sql = "SELECT email
        FROM students
        WHERE email = ?";

$stmt =
    $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Query Error: " .
        $conn->error
    );

}

$stmt->bind_param(
    "s",
    $email
);

$stmt->execute();

$result =
    $stmt->get_result();

if ($result->num_rows > 0) {

    $stmt->close();

    showError(
        "Email already exists."
    );

}

$stmt->close();


/* =========================================
   HASH PASSWORD
========================================= */

$hashed_password =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/* =========================================
   INSERT STUDENT
========================================= */

$sql = "INSERT INTO students
        (
            student_id,
            name,
            email,
            password,
            department
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?
        )";

$stmt =
    $conn->prepare($sql);

if (!$stmt) {

    die(
        "Database Query Error: " .
        $conn->error
    );

}

$stmt->bind_param(
    "sssss",
    $student_id,
    $name,
    $email,
    $hashed_password,
    $department
);

if ($stmt->execute()) {

    $stmt->close();

    echo "<script>

        alert(
            'Student account created successfully.'
        );

        window.location.href =
            '../login.html';

    </script>";

    exit();

}

echo "<script>

    alert(
        'Registration failed: " .
        addslashes($stmt->error) .
        "'
    );

    window.history.back();

</script>";

$stmt->close();


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