<?php

session_start();

require_once "php/db.php";


/* =========================================
   GET LOGIN DATA
========================================= */

$role =
    trim($_POST["role"] ?? "");

$email =
    trim($_POST["email"] ?? "");

$password =
    $_POST["password"] ?? "";


/* =========================================
   VALIDATION
========================================= */

if (
    $role === "" ||
    $email === "" ||
    $password === ""
) {

    showError("Please fill in all fields.");

}


/* =========================================
   STUDENT LOGIN
========================================= */

if ($role === "student") {

    $sql = "SELECT
                student_id,
                name,
                email,
                password,
                department
            FROM students
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

    $result =
        $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        showError(
            "Student account not found."
        );

    }

    $student =
        $result->fetch_assoc();

    $stmt->close();


    /* =====================================
       CHECK STUDENT PASSWORD
    ====================================== */

    if (
        !password_verify(
            $password,
            $student["password"]
        )
    ) {

        showError(
            "Incorrect password."
        );

    }


    /* =====================================
       STUDENT SESSION
    ====================================== */

    $_SESSION["role"] =
        "student";

    $_SESSION["student_id"] =
        $student["student_id"];

    $_SESSION["student_name"] =
        $student["name"];

    $_SESSION["student_email"] =
        $student["email"];

    $_SESSION["student_department"] =
        $student["department"];


    /* =====================================
       STUDENT DASHBOARD
    ====================================== */

    header(
        "Location: student/dashboard.php"
    );

    exit();

}


/* =========================================
   TEACHER LOGIN
========================================= */

if ($role === "teacher") {

    $sql = "SELECT
                teacher_name,
                email,
                password,
                department
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

    $result =
        $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        showError(
            "Teacher account not found."
        );

    }

    $teacher =
        $result->fetch_assoc();

    $stmt->close();


    /* =====================================
       CHECK TEACHER PASSWORD
    ====================================== */

    if (
        !password_verify(
            $password,
            $teacher["password"]
        )
    ) {

        showError(
            "Incorrect password."
        );

    }


    /* =====================================
       TEACHER SESSION
    ====================================== */

    $_SESSION["role"] =
        "teacher";

    $_SESSION["teacher_name"] =
        $teacher["teacher_name"];

    $_SESSION["teacher_email"] =
        $teacher["email"];

    $_SESSION["teacher_department"] =
        $teacher["department"];


    /* =====================================
       TEACHER DASHBOARD
    ====================================== */

    header(
        "Location: teacher/dashboard.php"
    );

    exit();

}


/* =========================================
   COORDINATOR LOGIN
========================================= */

if ($role === "coordinator") {

    $sql = "SELECT
                coordinator_name,
                email,
                password,
                department
            FROM coordinators
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

    $result =
        $stmt->get_result();

    if ($result->num_rows === 0) {

        $stmt->close();

        showError(
            "Coordinator account not found."
        );

    }

    $coordinator =
        $result->fetch_assoc();

    $stmt->close();


    /* =====================================
       CHECK COORDINATOR PASSWORD
    ====================================== */

    if (
        !password_verify(
            $password,
            $coordinator["password"]
        )
    ) {

        showError(
            "Incorrect password."
        );

    }


    /* =====================================
       COORDINATOR SESSION
    ====================================== */

    $_SESSION["role"] =
        "coordinator";

    $_SESSION["coordinator_name"] =
        $coordinator["coordinator_name"];

    $_SESSION["coordinator_email"] =
        $coordinator["email"];

    $_SESSION["coordinator_department"] =
        $coordinator["department"];


    /* =====================================
       COORDINATOR DASHBOARD
========================================= */

    header(
        "Location: coordinator/dashboard.php"
    );

    exit();

}


/* =========================================
   INVALID ROLE
========================================= */

showError(
    "Invalid login role."
);


/* =========================================
   ERROR FUNCTION
========================================= */

function showError(string $message): void
{

    echo "<script>

        alert(" .
        json_encode($message) .
        ");

        window.location.href =
            'login.html';

    </script>";

    exit();

}

?>
```

### Now your student login will work like this

Suppose during registration you choose:

```text
Student ID: 2026001
Email: student@gmail.com
Password: abc123
```

Your database stores something like:

```text
$2y$10$SikrfpAwYXhAJoff9Onma.PfhmU0lL0JbbpUgbrisWfJAP2jxLpry
```

At login, enter:

```text
Email: student@gmail.com
Password: abc123
```

**Not** the `$2y$10$...` hash.

PHP does:

```php
password_verify("abc123", "$2y$10$...");
```

and logs the student in.

### One more important thing

Your current `register.php` is already correct, so **don't change it**.

But your **teacher account creation code** and **coordinator account creation code** also need to use:

```php
$hashed_password = password_hash(
    $password,
    PASSWORD_DEFAULT
);


Otherwise teacher/coordinator accounts created with plain-text passwords will still fail with this new `login.php`.

Also, any **old accounts whose database password is literally `123456`** are not compatible with `password_verify()`. Those accounts need their passwords converted to hashes/reset once. New accounts created through your corrected registration code will work normally.
