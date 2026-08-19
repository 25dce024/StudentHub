document.addEventListener("DOMContentLoaded", function () {

    const form = document.querySelector("form");

    if (!form) return;

    if (document.title.includes("Register")) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();

            const password = document.getElementById("password").value;
            const confirmPassword = document.getElementById("confirm").value;

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                return;
            }

            alert("Registration successful!");
            form.reset();
        });
    }

    else if (document.title.includes("Login")) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();

            alert("Login successful! Welcome to StudentHub.");
        });
    }

    else if (document.title.includes("Feedback")) {
        form.addEventListener("submit", function (event) {
            event.preventDefault();

            alert("Thank you for your feedback!");
            form.reset();
        });
    }

});
