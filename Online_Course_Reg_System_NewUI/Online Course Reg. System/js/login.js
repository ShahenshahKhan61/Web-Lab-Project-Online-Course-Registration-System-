```javascript
/* =========================
   PASSWORD SHOW / HIDE
========================= */

const togglePassword =
    document.getElementById("togglePassword");

if (togglePassword) {

    togglePassword.addEventListener(
        "click",
        function () {

            const password =
                document.getElementById("password");

            if (password.type === "password") {

                password.type = "text";

                togglePassword.textContent =
                    "🙈";

            } else {

                password.type = "password";

                togglePassword.textContent =
                    "👁";

            }

        }
    );

}


/* =========================
   LOGIN FORM
========================= */

const loginForm =
    document.getElementById("loginForm");

if (loginForm) {

    loginForm.addEventListener(
        "submit",
        function (event) {

            const role =
                document.getElementById("role").value;

            const email =
                document
                    .getElementById("email")
                    .value
                    .trim();

            const password =
                document
                    .getElementById("password")
                    .value;


            /* -------------------------
               CHECK ROLE
            ------------------------- */

            if (role === "") {

                event.preventDefault();

                alert(
                    "Please select your role."
                );

                return;
            }


            /* -------------------------
               CHECK EMAIL & PASSWORD
            ------------------------- */

            if (
                email === "" ||
                password === ""
            ) {

                event.preventDefault();

                alert(
                    "Please fill in all fields."
                );

                return;
            }


            /*
               Validation successful.

               The form submits normally to:

               login.php
            */

        }
    );

}
```
