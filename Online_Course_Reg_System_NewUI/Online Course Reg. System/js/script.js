```javascript id="m5n7q2"
// ==========================================
// UniCourse - Main JavaScript
// ==========================================


// ==========================================
// STUDENT REGISTRATION
// ==========================================

const studentRegisterForm =
    document.querySelector(
        'form[action="php/register.php"]'
    );

if (studentRegisterForm) {

    studentRegisterForm.addEventListener(
        "submit",
        function (event) {

            const password =
                document.getElementById(
                    "password"
                ).value;

            const confirmPassword =
                document.getElementById(
                    "confirmPassword"
                ).value;


            /* -------------------------
               CHECK PASSWORD LENGTH
            ------------------------- */

            if (password.length < 6) {

                event.preventDefault();

                alert(
                    "Password must be at least 6 characters."
                );

                return;

            }


            /* -------------------------
               CHECK PASSWORD MATCH
            ------------------------- */

            if (
                password !==
                confirmPassword
            ) {

                event.preventDefault();

                alert(
                    "Passwords do not match."
                );

                return;

            }

        }
    );

}


// ==========================================
// TEACHER REGISTRATION
// ==========================================

const teacherRegisterForm =
    document.querySelector(
        'form[action="php/teacher_register.php"]'
    );

if (teacherRegisterForm) {

    teacherRegisterForm.addEventListener(
        "submit",
        function (event) {

            const password =
                document.getElementById(
                    "password"
                ).value;

            const confirmPassword =
                document.getElementById(
                    "confirm_password"
                ).value;


            /* -------------------------
               CHECK PASSWORD LENGTH
            ------------------------- */

            if (password.length < 6) {

                event.preventDefault();

                alert(
                    "Password must be at least 6 characters."
                );

                return;

            }


            /* -------------------------
               CHECK PASSWORD MATCH
            ------------------------- */

            if (
                password !==
                confirmPassword
            ) {

                event.preventDefault();

                alert(
                    "Passwords do not match."
                );

                return;

            }

        }
    );

}
```
