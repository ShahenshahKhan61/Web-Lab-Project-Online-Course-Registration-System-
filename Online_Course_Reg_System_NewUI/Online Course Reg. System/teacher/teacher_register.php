<?php

require_once "db.php";


/* =========================
   CHECK REQUEST
========================= */

if ($_SERVER["REQUEST_METHOD"] !== "POST") {

    header("Location: ../teacher_register.html");
    exit();

}


/* =========================
   GET FORM DATA
========================= */

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


/* =========================
   VALIDATION
========================= */

if (
    $teacher_name === "" ||
    $email === "" ||
    $department === "" ||
    $password === "" ||
    $confirm_password === ""
) {

    showError("Please fill in all fields.");

}


if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

    showError("Please enter a valid email address.");

}


if ($password !== $confirm_password) {

    showError("Passwords do not match.");

}


/* =========================
   CHECK DUPLICATE EMAIL
========================= */

$sql = "SELECT email
        FROM teachers
        WHERE email = ?";

$stmt = $conn->prepare($sql);

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

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    showError("Email already exists.");

}

$stmt->close();


/* =========================
   HASH PASSWORD
========================= */

$hashed_password =
    password_hash(
        $password,
        PASSWORD_DEFAULT
    );


/* =========================
   INSERT TEACHER
========================= */

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

$stmt = $conn->prepare($sql);

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

    header("Location: ../login.html");
    exit();

} else {

    die(
        "Teacher Registration Error: " .
        $stmt->error
    );

}


function showError(string $message): void
{

    echo "<script>

        alert(" .
        json_encode($message) .
        ");

        window.location.href =
            '../teacher_register.html';

    </script>";

    exit();

}

?>