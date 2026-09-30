<?php

session_start();


/* =========================================
   CHECK COORDINATOR LOGIN
========================================= */

if (!isset($_SESSION["coordinator_name"])) {

    header("Location: login.html");

    exit();

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

    <title>Add Course - UniCourse</title>


    <link rel="stylesheet" href="css/dashboard.css">

</head>


<body>


<!-- =========================================
     HEADER
========================================= -->

<header>

    <h1>
        UniCourse
    </h1>


    <button
        type="button"
        class="back-btn"
        onclick="window.location.href='coordinator/dashboard.php';"
    >

        Back to Dashboard

    </button>

</header>



<!-- =========================================
     MAIN
========================================= -->

<main>

    <div class="course-box">


        <h2>
            Add New Course
        </h2>


        <p class="course-description">

            Enter the course information below to
            add a new course to UniCourse.

        </p>



        <!-- =================================
             COURSE FORM
        ================================== -->

        <form
            action="php/add_course.php"
            method="POST"
        >


            <div class="form-grid">


                <!-- =========================
                     COURSE CODE
                ========================== -->

                <div class="form-group">

                    <label for="course_code">

                        Course Code

                    </label>


                    <input
                        type="text"
                        id="course_code"
                        name="course_code"
                        placeholder="Example: CSE301"
                        required
                    >

                </div>



                <!-- =========================
                     COURSE NAME
                ========================== -->

                <div class="form-group">

                    <label for="course_name">

                        Course Name

                    </label>


                    <input
                        type="text"
                        id="course_name"
                        name="course_name"
                        placeholder="Example: Database Systems"
                        required
                    >

                </div>



                <!-- =========================
                     DEPARTMENT
                ========================== -->

                <div class="form-group">

                    <label for="department">

                        Department

                    </label>


                    <select
                        id="department"
                        name="department"
                        required
                    >

                        <option value="">

                            Select Department

                        </option>


                        <option value="CSE">

                            CSE

                        </option>


                        <option value="EEE">

                            EEE

                        </option>


                        <option value="BBA">

                            BBA

                        </option>


                        <option value="Civil">

                            Civil Engineering

                        </option>


                        <option value="Architecture">

                            Architecture

                        </option>

                    </select>

                </div>



                <!-- =========================
                     CREDIT
                ========================== -->

                <div class="form-group">

                    <label for="credit">

                        Credit

                    </label>


                    <select
                        id="credit"
                        name="credit"
                        required
                    >

                        <option value="">

                            Select Credit

                        </option>


                        <option value="1">

                            1 Credit

                        </option>


                        <option value="2">

                            2 Credits

                        </option>


                        <option value="3">

                            3 Credits

                        </option>


                        <option value="4">

                            4 Credits

                        </option>


                        <option value="5">

                            5 Credits

                        </option>

                    </select>

                </div>



                <!-- =========================
                     TEACHER NAME
                ========================== -->

                <div class="form-group">

                    <label for="teacher_name">

                        Teacher Name

                    </label>


                    <input
                        type="text"
                        id="teacher_name"
                        name="teacher_name"
                        placeholder="Example: MH"
                        required
                    >

                    <span class="hint">

                        Enter the teacher name exactly
                        as stored in the teachers table.

                    </span>

                </div>



                <!-- =========================
                     TOTAL SECTIONS
                ========================== -->

                <div class="form-group">

                    <label for="total_sections">

                        Total Sections

                    </label>


                    <input
                        type="number"
                        id="total_sections"
                        name="total_sections"
                        min="1"
                        value="1"
                        required
                    >

                </div>



                <!-- =========================
                     CAPACITY
                ========================== -->

                <div class="form-group">

                    <label for="capacity">

                        Student Capacity

                    </label>


                    <input
                        type="number"
                        id="capacity"
                        name="capacity"
                        min="0"
                        placeholder="Example: 40"
                        required
                    >

                    <span class="hint">

                        Maximum number of students.

                    </span>

                </div>



                <!-- =========================
                     SCHEDULE DAYS
                ========================== -->

                <div class="form-group">

                    <label for="schedule_days">

                        Schedule Days

                    </label>


                    <select
                        id="schedule_days"
                        name="schedule_days"
                        required
                    >

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

                    </select>

                </div>



                <!-- =========================
                     START TIME
                ========================== -->

                <div class="form-group">

                    <label for="start_time">

                        Start Time

                    </label>


                    <input
                        type="time"
                        id="start_time"
                        name="start_time"
                        required
                    >

                </div>



                <!-- =========================
                     END TIME
                ========================== -->

                <div class="form-group">

                    <label for="end_time">

                        End Time

                    </label>


                    <input
                        type="time"
                        id="end_time"
                        name="end_time"
                        required
                    >

                </div>



                <!-- =========================
                     ROOM
                ========================== -->

                <div class="form-group">

                    <label for="room">

                        Room

                    </label>


                    <input
                        type="text"
                        id="room"
                        name="room"
                        placeholder="Example: Room 501"
                        required
                    >

                </div>


            </div>



            <!-- =================================
                 BUTTONS
            ================================== -->

            <div class="button-area">


                <button
                    type="button"
                    class="cancel-btn"
                    onclick="window.location.href='coordinator/dashboard.php';"
                >

                    Cancel

                </button>


                <button
                    type="submit"
                    class="submit-btn"
                >

                    Add Course

                </button>


            </div>


        </form>

    </div>

</main>



<!-- =========================================
     FOOTER
========================================= -->

<footer>

    © 2026 UniCourse

</footer>


</body>

</html>