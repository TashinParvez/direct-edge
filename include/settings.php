<?php
// Move session_start() to the very beginning
session_start();
$_SESSION['phonenumber'] = "01798294346";

include "../Include/directedge_database.php"; // Fixed the path, use forward slashes for cross-platform compatibility

// Handle change name
if (isset($_POST['submit_name'])) {
    $new_name = trim(htmlspecialchars($_POST['changeName']));
    if (empty($new_name)) {
        $_SESSION['error_name'] = "Name cannot be empty";
        header("Location: Settings.php");
        exit;
    }
    $sql = "UPDATE users SET Name=? WHERE Phone=?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $new_name, $_SESSION['phonenumber']);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Name updated successfully!";
            header("Location: Settings.php");
            exit;
        } else {
            $_SESSION['error_name'] = "Error updating record: " . mysqli_stmt_error($stmt);
            header("Location: Settings.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_name'] = "Error preparing statement: " . mysqli_error($conn);
        header("Location: Settings.php");
        exit;
    }
}

// Handle change phone
if (isset($_POST['submit_phone'])) {
    $new_phone = htmlspecialchars($_POST['changePhone']);
    if (empty($new_phone)) {
        $_SESSION['error_phone'] = "Phone number cannot be empty";
        header("Location: Settings.php");
        exit;
    }
    if (strlen($new_phone) != 11) {
        $_SESSION['error_phone'] = "Phone number must be exactly 11 digits";
        header("Location: Settings.php");
        exit;
    }
    $first_three_digits = substr($new_phone, 0, 3);
    $valid_prefixes = ["017", "013", "019", "014", "018", "016", "015"];
    if (!in_array($first_three_digits, $valid_prefixes)) {
        $_SESSION['error_phone'] = "Invalid phone number prefix. Must start with a valid Bangladeshi operator code.";
        header("Location: Settings.php");
        exit;
    }
    $sql = "UPDATE users SET Phone=? WHERE Phone=?";
    $sql1 = "UPDATE cart SET phone=? WHERE phone=?";
    $sql2 = "UPDATE discount SET user_phn_number=? WHERE user_phn_number=?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "ss", $new_phone, $_SESSION['phonenumber']);
        if (mysqli_stmt_execute($stmt)) {
            $stmt1 = mysqli_prepare($conn, $sql1);
            mysqli_stmt_bind_param($stmt1, "ss", $new_phone, $_SESSION['phonenumber']);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_close($stmt1);

            $stmt2 = mysqli_prepare($conn, $sql2);
            mysqli_stmt_bind_param($stmt2, "ss", $new_phone, $_SESSION['phonenumber']);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_close($stmt2);

            $_SESSION['phonenumber'] = $new_phone;
            $_SESSION['success'] = "Phone number updated successfully!";
            header("Location: Settings.php");
            exit;
        } else {
            $_SESSION['error_phone'] = "Error updating record: " . mysqli_stmt_error($stmt);
            header("Location: Settings.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_phone'] = "Error preparing statement: " . mysqli_error($conn);
        header("Location: Settings.php");
        exit;
    }
}

// Handle change password
if (isset($_POST['submit_password'])) {
    $new_password = htmlspecialchars($_POST['changePassword']);
    $confirm_password = htmlspecialchars($_POST['confirmPassword']);
    if (empty($new_password) || empty($confirm_password)) {
        $_SESSION['error_password'] = "Password fields cannot be empty";
        header("Location: Settings.php");
        exit;
    }
    if (strlen($new_password) < 8) {
        $_SESSION['error_password'] = "Password must be at least 8 characters long";
        header("Location: Settings.php");
        exit;
    }
    if ($new_password != $confirm_password) {
        $_SESSION['error_password'] = "Passwords do not match";
        header("Location: Settings.php");
        exit;
    }
    $sql = "UPDATE users SET Password=? WHERE Phone=?";
    if ($stmt = mysqli_prepare($conn, $sql)) {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        mysqli_stmt_bind_param($stmt, "ss", $hashed_password, $_SESSION['phonenumber']);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success'] = "Password updated successfully!";
            header("Location: Settings.php");
            exit;
        } else {
            $_SESSION['error_password'] = "Error updating record: " . mysqli_stmt_error($stmt);
            header("Location: Settings.php");
            exit;
        }
        mysqli_stmt_close($stmt);
    } else {
        $_SESSION['error_password'] = "Error preparing statement: " . mysqli_error($conn);
        header("Location: Settings.php");
        exit;
    }
}

// Fetch user details securely
$stmt = $conn->prepare("SELECT Name, Phone FROM users WHERE Phone = ?");
$stmt->bind_param("s", $_SESSION['phonenumber']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    echo "<p>User not found.</p>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <style>
    .bg-custom {
        background-color: #f8f9fa;
    }

    .profile-container {
        max-width: 600px;
        margin: auto;
        padding: 20px;
    }

    .profile-info {
        margin-bottom: 20px;
    }

    .change-name-form,
    .change-phone-form,
    .change-password-form {
        display: none;
        margin-top: 10px;
    }

    .message.success {
        color: green;
        padding: 10px;
        border: 1px solid green;
        border-radius: 5px;
    }

    .btn-toggle {
        margin-top: 10px;
    }
    </style>
    <script>
    window.onload = function() {
        var successMessage = document.getElementById('successMessage');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.display = 'none';
            }, 4000);
        }
    }

    function toggleNameForm() {
        var form = document.getElementById('nameForm');
        form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
    }

    function togglePhoneForm() {
        var form = document.getElementById('phoneForm');
        form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
    }

    function togglePasswordForm() {
        var form = document.getElementById('passwordForm');
        form.style.display = form.style.display === 'none' || form.style.display === '' ? 'block' : 'none';
    }
    </script>
</head>

<body class="bg-custom">
    <?php include '../Include/Sidebar.php'; ?>
    <section class="home-section p-0">
        <div class="text">User Profile</div>
        <div class="container m-3">
            <div class="profile-container">
                <?php
                if (isset($_SESSION['error_name'])) {
                    echo "<p style='color: red;'>" . $_SESSION['error_name'] . "</p>";
                    unset($_SESSION['error_name']);
                }
                if (isset($_SESSION['error_phone'])) {
                    echo "<p style='color: red;'>" . $_SESSION['error_phone'] . "</p>";
                    unset($_SESSION['error_phone']);
                }
                if (isset($_SESSION['error_password'])) {
                    echo "<p style='color: red;'>" . $_SESSION['error_password'] . "</p>";
                    unset($_SESSION['error_password']);
                }
                if (isset($_SESSION['success'])) {
                    echo "<div id='successMessage' class='message success'>" . $_SESSION['success'] . "</div>";
                    unset($_SESSION['success']);
                }
                ?>
                <div class="profile-info">
                    <h3>Name: <?php echo htmlspecialchars($user['Name']); ?></h3>
                    <button onclick="toggleNameForm()" class="btn btn-light btn-toggle">Change Name</button>
                    <div id="nameForm" class="change-name-form">
                        <form action="Settings.php" method="post">
                            <input class="form-control" type="text" name="changeName" placeholder="Enter new name"
                                required>
                            <input type="submit" name="submit_name" value="Submit" class="btn btn-light mt-2">
                        </form>
                    </div>
                </div>
                <div class="profile-info">
                    <h3>Phone Number: <?php echo htmlspecialchars($user['Phone']); ?></h3>
                    <button onclick="togglePhoneForm()" class="btn btn-light btn-toggle">Change Phone</button>
                    <div id="phoneForm" class="change-phone-form">
                        <form action="Settings.php" method="post">
                            <input class="form-control" type="text" name="changePhone"
                                placeholder="Enter new phone number" required>
                            <input type="submit" name="submit_phone" value="Submit" class="btn btn-light mt-2">
                        </form>
                    </div>
                </div>
                <div class="profile-info">
                    <h3>Change Password</h3>
                    <button onclick="togglePasswordForm()" class="btn btn-light btn-toggle">Change Password</button>
                    <div id="passwordForm" class="change-password-form">
                        <form action="Settings.php" method="post">
                            <input type="password" name="changePassword" class="form-control" placeholder="New Password"
                                required>
                            <input type="password" name="confirmPassword" class="form-control mt-2"
                                placeholder="Confirm Password" required>
                            <div class="form-text">Your password must be 8-20 characters long, contain letters and
                                numbers, and must not contain spaces, special characters, or emoji.</div>
                            <input type="submit" name="submit_password" value="Submit" class="btn btn-light mt-2">
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </section>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"
        integrity="sha384-0pUGZvbkm6XF6gxjEnlmuGrJXVbNuzT9qBBavbLwCsOGabYfZo0T0to5eqruptLy" crossorigin="anonymous">
    </script>
    <?php
    $stmt->close();
    $conn->close();
    session_abort();
    ?>
</body>

</html>