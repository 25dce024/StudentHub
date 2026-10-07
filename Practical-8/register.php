<?php

require "db.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $event_id = $_POST["event_id"] ?? "";

    if ($name == "" || !filter_var($email, FILTER_VALIDATE_EMAIL) || $event_id == "") {

        $message = "Please enter valid details.";

    } else {

        $sql = "INSERT INTO registrations
                (student_name, email, event_id)
                VALUES (:name, :email, :event_id)";

        $stmt = $conn->prepare($sql);

        $stmt->execute([
            ":name" => $name,
            ":email" => $email,
            ":event_id" => $event_id
        ]);

        $message = "Event registration successful!";
    }
}

$events = $conn->query("SELECT * FROM events")->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html>
<head>
    <title>StudentHub | Event Registration</title>
</head>
<body>

<h1>StudentHub</h1>
<h2>Event Registration</h2>

<?php if ($message != ""): ?>
    <p><?php echo htmlspecialchars($message); ?></p>
<?php endif; ?>

<form method="POST">

    <label>Student Name</label><br>
    <input type="text" name="name"><br><br>

    <label>Email</label><br>
    <input type="email" name="email"><br><br>

    <label>Select Event</label><br>

    <select name="event_id">
        <option value="">Select Event</option>

        <?php foreach ($events as $event): ?>

            <option value="<?php echo $event["id"]; ?>">
                <?php echo htmlspecialchars($event["title"]); ?>
            </option>

        <?php endforeach; ?>

    </select>

    <br><br>

    <button type="submit">Register for Event</button>

</form>

</body>
</html>
