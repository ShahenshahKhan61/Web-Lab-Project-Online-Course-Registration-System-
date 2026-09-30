 /* =========================================
   GET BASE COURSE CODE
   Example:
   CSE345.1 -> CSE345
   CSE345.2 -> CSE345
========================================= */

function getBaseCourseCode(courseCode) {

    return String(courseCode)
        .split(".")[0]
        .trim()
        .toUpperCase();

}


/* =========================================
   SEARCH COURSES
========================================= */

function searchCourses() {

    const searchInput =
        document.getElementById("courseSearch");

    if (!searchInput) {
        return;
    }

    const searchText =
        searchInput.value.toLowerCase().trim();

    const courses =
        document.querySelectorAll(".course-card");

    courses.forEach(function(course) {

        const searchData =
            course.getAttribute("data-search");

        if (!searchData) {
            return;
        }

        if (
            searchData
                .toLowerCase()
                .includes(searchText)
        ) {

            course.style.display = "";

        } else {

            course.style.display = "none";

        }

    });

}


/* =========================================
   REGISTER COURSE
========================================= */

function registerCourse(courseCode) {

    if (!courseCode) {

        alert("Course code is missing.");

        return;
    }


    /* =====================================
       CHECK SAME COURSE / DIFFERENT SECTION
    ===================================== */

    const selectedBaseCourse =
        getBaseCourseCode(courseCode);


    /*
       Check registered courses.

       CSE345.1 and CSE345.2
       are treated as the same course.
    */

    if (
        typeof registeredCourses !== "undefined" &&
        Array.isArray(registeredCourses)
    ) {

        let alreadyRegistered = false;
        let existingSection = "";


        registeredCourses.forEach(function(course) {

            const registeredCode =
                course.course_code ||
                course.courseCode ||
                "";


            const registeredBaseCourse =
                getBaseCourseCode(registeredCode);


            if (
                registeredBaseCourse ===
                selectedBaseCourse
            ) {

                alreadyRegistered = true;
                existingSection = registeredCode;

            }

        });


        if (alreadyRegistered) {

            alert(
                "You are already registered for this course in section " +
                existingSection +
                ". You cannot register another section of the same course."
            );

            return;
        }

    }


    /* =====================================
       CONFIRM REGISTRATION
    ===================================== */

    const confirmRegistration =
        confirm(
            "Are you sure you want to register for " +
            courseCode +
            "?"
        );


    if (!confirmRegistration) {

        return;

    }


    /* =====================================
       SEND TO PHP
    ===================================== */

    const formData =
        new FormData();

    formData.append(
        "course_code",
        courseCode
    );


    fetch(
        "../php/register_course.php",
        {
            method: "POST",
            body: formData
        }
    )

    .then(function(response) {

        return response.text();

    })

    .then(function(message) {

        message =
            message.trim();


        /* =====================================
           SUCCESS
        ===================================== */

        if (message === "success") {

            alert(
                "Course registered successfully."
            );

            location.reload();

            return;
        }


        /* =====================================
           SEAT NOT AVAILABLE
        ===================================== */

        if (
            message
                .toLowerCase()
                .includes("seat not available")
        ) {

            alert(message);

            return;
        }


        /* =====================================
           ALREADY REGISTERED
        ===================================== */

        if (
            message
                .toLowerCase()
                .includes("already registered")
        ) {

            alert(message);

            return;
        }


        /* =====================================
           COURSE NOT FOUND
        ===================================== */

        if (
            message
                .toLowerCase()
                .includes("course not found")
        ) {

            alert(message);

            return;
        }


        /* =====================================
           OTHER PHP MESSAGE
        ===================================== */

        alert(message);

    })

    .catch(function(error) {

        console.error(
            "Registration error:",
            error
        );

        alert(
            "Something went wrong. Please try again."
        );

    });

}


/* =========================================
   LOGOUT
========================================= */

function logout() {

    window.location.href =
        "../logout.php";

}