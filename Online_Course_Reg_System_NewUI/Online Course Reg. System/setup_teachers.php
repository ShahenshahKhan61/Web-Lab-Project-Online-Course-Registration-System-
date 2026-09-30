<?php

require_once "php/db.php";


/* =========================================
   TEACHER ACCOUNTS
========================================= */

$teachers = [

    [
        "name" => "AR",
        "email" => "ar@seu.edu.bd",
        "password" => "123456",
        "department" => "CSE"
    ],

    [
        "name" => "MH",
        "email" => "mh@seu.edu.bd",
        "password" => "123456",
        "department" => "CSE"
    ],

    [
        "name" => "DK",
        "email" => "dk@seu.edu.bd",
        "password" => "123456",
        "department" => "CSE"
    ]

];


/* =========================================
   INSERT OR UPDATE TEACHERS
========================================= */

foreach ($teachers as $teacher) {

    $name =
        $teacher["name"];

    $email =
        $teacher["email"];

    $password =
        password_hash(
            $teacher["password"],
            PASSWORD_DEFAULT
        );

    $department =
        $teacher["department"];


    /* =====================================
       CHECK EXISTING TEACHER
    ====================================== */

    $check = $conn->prepare(
        "SELECT teacher_name
         FROM teachers
         WHERE teacher_name = ?"
    );

    $check->bind_param(
        "s",
        $name
    );

    $check->execute();

    $result =
        $check->get_result();


    /* =====================================
       UPDATE EXISTING TEACHER
    ====================================== */

    if ($result->num_rows > 0) {

        $update = $conn->prepare(
            "UPDATE teachers
             SET email = ?,
                 password = ?,
                 department = ?
             WHERE teacher_name = ?"
        );

        $update->bind_param(
            "ssss",
            $email,
            $password,
            $department,
            $name
        );

        $update->execute();

        echo
            "Updated teacher: " .
            $name .
            " (" .
            $email .
            ")<br>";

    }


    /* =====================================
       CREATE NEW TEACHER
    ====================================== */

    else {

        $insert = $conn->prepare(
            "INSERT INTO teachers
            (
                teacher_name,
                email,
                password,
                department
            )
            VALUES (?, ?, ?, ?)"
        );

        $insert->bind_param(
            "ssss",
            $name,
            $email,
            $password,
            $department
        );

        $insert->execute();

        echo
            "Created teacher: " .
            $name .
            " (" .
            $email .
            ")<br>";

    }

}


echo "<br>";
echo "<strong>All teacher accounts are ready.</strong>";

?>