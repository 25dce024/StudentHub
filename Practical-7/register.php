<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fullname = trim($_POST["fullname"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $phone = trim($_POST["phone"] ?? "");
    $department = trim($_POST["department"] ?? "");
    $year = trim($_POST["year"] ?? "");
    $gender = trim($_POST["gender"] ?? "");
    $password = $_POST["password"] ?? "";

    $errors = [];

    if ($fullname == "") {
        $errors[] = "Name is required.";
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email.";
    }

    if (!preg_match("/^[0-9]{10}$/", $phone)) {
        $errors[] = "Enter a valid 10-digit mobile number.";
    }

    if ($department == "") {
        $errors[] = "Select a department.";
    }

    if ($year == "") {
        $errors[] = "Select your year.";
    }

    if ($gender == "") {
        $errors[] = "Select gender.";
    }

    if (strlen($password) < 8) {
        $errors[] = "Password must contain at least 8 characters.";
    }

    if (empty($errors)) {

        $file = "registrations.csv";

        $data = [
            $fullname,
            $email,
            $phone,
            $department,
            $year,
            $gender,
            password_hash($password, PASSWORD_DEFAULT)
        ];

        $handle = fopen($file, "a");

        if ($handle) {
            fputcsv($handle, $data);
            fclose($handle);

            echo "<h2>Registration Successful!</h2>";
            echo "<p>Your details have been saved successfully.</p>";
        } else {
            echo "<p>Unable to save registration data.</p>";
        }

    } else {

        echo "<h2>Registration Failed</h2>";

        foreach ($errors as $error) {
            echo "<p>$error</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>StudentHub Registration</title>
</head>
<body>

<h1>StudentHub</h1>
<h2>Server-Side Registration</h2>

<form method="POST">

    <label>Full Name</label><br>
    <input type="text" name="fullname"><br><br>

    <label>Email</label><br>
    <input type="email" name="email"><br><br>

    <label>Mobile</label><br>
    <input type="text" name="phone"><br><br>

    <label>Department</label><br>
    <select name="department">
        <option value="">Select</option>
        <option>Computer Engineering</option>
        <option>Information Technology</option>
        <option>Mechanical Engineering</option>
        <option>Civil Engineering</option>
        <option>Electronics Engineering</option>
    </select><br><br>

    <label>Year</label><br>
    <select name="year">
        <option value="">Select</option>
        <option>First Year</option>
        <option>Second Year</option>
        <option>Third Year</option>
        <option>Fourth Year</option>
    </select><br><br>

    <label>Gender</label><br>
    <input type="radio" name="gender" value="Male"> Male
    <input type="radio" name="gender" value="Female"> Female
    <input type="radio" name="gender" value="Other"> Other
    <br><br>

    <label>Password</label><br>
    <input type="password" name="password"><br><br>

    <button type="submit">Register</button>

</form>

</body>
</html>
