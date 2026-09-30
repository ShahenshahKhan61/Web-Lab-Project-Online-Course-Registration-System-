<?php

require_once "db.php";


/* =========================================
   GET FORM DATA
========================================= */

$teacher_name =
    trim($_POST["teacher_name"] ?? "");

$email =
    trim($_POST["email"] ?? "");

$department =
    trim($_POST["department"] ?? "");

$password =
    $_POST["password"] ?? "";

$confirm_password =
    $_POST["confirm_password"] ?? "";


/* =========================================
   VALIDATION
========================================= */

if (
    $teacher_name === "" ||
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
   CHECK EMAIL
========================================= */

$sql = "SELECT email
        FROM teachers
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
        "Teacher email already exists."
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
   INSERT TEACHER
========================================= */

$sql = "INSERT INTO teachers
        (
            teacher_name,
            email,
            password,
            department
        )
        VALUES
        (
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
    "ssss",
    $teacher_name,
    $email,
    $hashed_password,
    $department
);


if ($stmt->execute()) {

    $stmt->close();

    echo "<script>

        alert(
            'Teacher account created successfully.'
        );

        window.location.href =
            '../login.html';

    </script>";

    exit();

}


$error =
    $stmt->error;

$stmt->close();

showError(
    "Teacher registration failed: " .
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