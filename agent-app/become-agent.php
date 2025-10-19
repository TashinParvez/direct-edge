<?php
// include '../Include/SidebarAgent.php'; 
include '../include/navbar.php';

?>
<link rel="stylesheet" href="../Include/sidebar.css">

<?php

// $agent_id = isset($user_id) ? $user_id : 45;

$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$error = false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Verify CSRF
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $error = true;
        $message = "Security validation failed!";
    }

    if (!$error) {
        // User account fields
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        // Agent-specific fields
        $nid_number = trim($_POST['nid_number']);
        $region = trim($_POST['region']);
        $district = trim($_POST['district']);
        $upazila = trim($_POST['upazila']);
        $coverage_area_km = (int)$_POST['coverage_area_km'];
        $experience_years = (int)$_POST['experience_years'];
        $crops_expertise = trim($_POST['crops_expertise']);
        $vehicle_types = trim($_POST['vehicle_types']);
        $warehouse_capacity = trim($_POST['warehouse_capacity']);
        $reference_name = trim($_POST['reference_name']);
        $reference_phone = trim($_POST['reference_phone']);
        $statement = trim($_POST['statement']);

        // Password validation
        $uppercase = preg_match('@[A-Z]@', $password);
        $lowercase = preg_match('@[a-z]@', $password);
        $number    = preg_match('@[0-9]@', $password);
        $specialChars = preg_match('@[^\w]@', $password);

        // Validation
        if (empty($full_name) || empty($email) || empty($phone) || empty($password)) {
            $error = true;
            $message = "Please fill all required fields!";
        } elseif ($password !== $confirm_password) {
            $error = true;
            $message = "Passwords do not match!";
        } elseif (strlen($password) < 8 || !$uppercase || !$lowercase || !$number || !$specialChars) {
            $error = true;
            $message = "Password must be at least 8 characters and include at least one uppercase letter, one lowercase letter, one digit, and one special character.";
        } elseif (strlen($phone) < 11 || strlen($phone) > 14) {
            $error = true;
            $message = "Phone number must be between 11 and 14 characters.";
        } elseif (empty($nid_number) || empty($region) || empty($crops_expertise)) {
            $error = true;
            $message = "Please complete all agent-specific fields!";
        }

        // Check if email or phone already exists
        if (!$error) {
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ? LIMIT 1");
            $stmt->bind_param("ss", $email, $phone);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = true;
                $message = "Email or phone already registered!";
            }
            $stmt->close();
        }

        // Handle file uploads
        $id_doc_url = null;
        $photo_url = null;
        $trade_license_url = null;

        if (!$error) {
            $upload_dir = __DIR__ . "/uploads/agent_docs";
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            // Upload NID/ID document
            if (isset($_FILES['id_doc']) && $_FILES['id_doc']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['id_doc']['tmp_name']);
                finfo_close($finfo);

                if (in_array($mime, $allowed) && $_FILES['id_doc']['size'] <= 3 * 1024 * 1024) {
                    $ext = pathinfo($_FILES['id_doc']['name'], PATHINFO_EXTENSION);
                    $filename = "id_" . time() . "_" . uniqid() . "." . $ext;
                    $dest = $upload_dir . "/" . $filename;
                    if (move_uploaded_file($_FILES['id_doc']['tmp_name'], $dest)) {
                        $id_doc_url = "uploads/agent_docs/" . $filename;
                    }
                }
            }

            // Upload profile photo
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['profile_photo']['tmp_name']);
                finfo_close($finfo);

                if (in_array($mime, $allowed) && $_FILES['profile_photo']['size'] <= 2 * 1024 * 1024) {
                    $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                    $filename = "photo_" . time() . "_" . uniqid() . "." . $ext;
                    $dest = $upload_dir . "/" . $filename;
                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                        $photo_url = "uploads/agent_docs/" . $filename;
                    }
                }
            }

            // Upload trade license
            if (isset($_FILES['trade_license']) && $_FILES['trade_license']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = finfo_file($finfo, $_FILES['trade_license']['tmp_name']);
                finfo_close($finfo);

                if (in_array($mime, $allowed) && $_FILES['trade_license']['size'] <= 3 * 1024 * 1024) {
                    $ext = pathinfo($_FILES['trade_license']['name'], PATHINFO_EXTENSION);
                    $filename = "license_" . time() . "_" . uniqid() . "." . $ext;
                    $dest = $upload_dir . "/" . $filename;
                    if (move_uploaded_file($_FILES['trade_license']['tmp_name'], $dest)) {
                        $trade_license_url = "uploads/agent_docs/" . $filename;
                    }
                }
            }
        }

        // Insert into database
        if (!$error) {
            mysqli_begin_transaction($conn);

            try {
                // 1. Insert into users table with role='Agent'
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, image_url, created_at, updated_at) VALUES (?, ?, ?, ?, 'Agent', ?, NOW(), NOW())");
                $stmt->bind_param("sssss", $full_name, $email, $phone, $hashed_password, $photo_url);
                $stmt->execute();
                $user_id = $conn->insert_id;
                $stmt->close();

                // 2. Insert into agent_info table
                $status = 'Pending'; // Start as pending for admin approval
                $stmt = $conn->prepare("INSERT INTO agent_info (agent_id, nid_number, region, district, upazila, coverage_area_km, experience_years, crops_expertise, vehicle_types, warehouse_capacity, reference_name, reference_phone, statement, id_doc_url, photo_url, trade_license_url, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param("issssiissssssssss", $user_id, $nid_number, $region, $district, $upazila, $coverage_area_km, $experience_years, $crops_expertise, $vehicle_types, $warehouse_capacity, $reference_name, $reference_phone, $statement, $id_doc_url, $photo_url, $trade_license_url, $status);
                $stmt->execute();
                $stmt->close();

                mysqli_commit($conn);

                // --- NOTIFICATION ---
                include_once __DIR__ . '/../include/notification_helpers.php';
                $admin_ids = get_user_ids_by_role($conn, 'Admin');
                if (!empty($admin_ids)) {
                    $notification_message = "New agent application from " . htmlspecialchars($full_name) . ".";
                    $notification_link = "/warehouse-app/admin-dashboard/admin-agent-management.php";
                    create_notification($conn, $admin_ids, 'new_agent_application', $notification_message, $notification_link);
                }
                // --- END NOTIFICATION ---

                // Success - redirect to login
                header("Location: ../Login-Signup/login.php");
                exit();
            } catch (Exception $e) {
                mysqli_rollback($conn);
                $error = true;
                $message = "Registration failed: " . $e->getMessage();
            }
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become an Agent - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">

    <style>
        /* Password Requirements Popup */
        .password-popup {
            display: none;
            position: absolute;
            background-color: #ffffff;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #10b981;
            border-radius: 6px;
            padding: 10px 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 100;
            width: 300px;
            font-size: 0.8rem;
            line-height: 1.4;
            margin-top: 5px;
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
</head>

<body class="bg-gradient-to-br from-green-50 via-white to-emerald-50 min-h-screen pb-8">

    <div class="max-w-4xl mx-auto px-4 pb-4">
        <!-- Header -->
        <div class="text-center mb-2 pt-2">
            <h2 class="text-2xl font-bold text-gray-900">Become an Agricultural Agent</h2>
            <p class="text-gray-600 mt-2">Join our network of verified agricultural professionals</p>
        </div>

        <!-- Error Message -->
        <?php if ($message): ?>
            <div id="messagePopup" class="fixed top-5 left-1/2 transform -translate-x-1/2 z-50 mb-6 rounded-lg p-4 shadow-lg <?php echo $error ? 'bg-red-50 border border-red-200' : 'bg-green-50 border border-green-200'; ?>">
                <button onclick="this.parentElement.style.display='none'" class="absolute top-2 right-2 text-gray-500 hover:text-gray-700">&times;</button>
                <p class="<?php echo $error ? 'text-red-800' : 'text-green-800'; ?> text-sm font-medium">
                    <?php echo htmlspecialchars($message); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Form -->
        <form method="POST" enctype="multipart/form-data" class="bg-white rounded-2xl shadow-xl p-8 space-y-0">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

            <!-- Account Information -->
            <section>
                <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">Account Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Full Name *</label>
                        <input type="text" name="full_name" placeholder="Enter your full name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" placeholder="Your email address" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div style="position: relative;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number *</label>
                        <input type="text" id="phonenumber" name="phone" placeholder="+8801XXXXXXXXX"
                            value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                            required minlength="11" maxlength="14" pattern=".{11,14}"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                        <div id="phonePopup" class="password-popup">
                            <p><strong>Phone Number Requirements:</strong> Must be between 11 and 14 characters.</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NID / National ID *</label>
                        <input type="text" name="nid_number" placeholder="Enter your National ID number" value="<?php echo isset($_POST['nid_number']) ? htmlspecialchars($_POST['nid_number']) : ''; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div style="position: relative;">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Password *</label>
                        <input type="password" id="password" name="password" placeholder="Enter your password" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                        <div id="passwordPopup" class="password-popup">
                            <p><strong>Password Requirements:</strong> Must be at least 8 characters and include at least one uppercase letter, one lowercase letter, one digit, and one special character.</p>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Confirm Password *</label>
                        <input type="password" name="confirm_password" placeholder="Confirm your password" required minlength="8" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                </div>
            </section>

            <!-- Location & Coverage -->
            <section>
                <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">Location & Coverage Area</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Region/Division *</label>
                        <input type="text" name="region" placeholder="e.g., Dhaka" value="<?php echo isset($_POST['region']) ? htmlspecialchars($_POST['region']) : ''; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">District</label>
                        <input type="text" name="district" placeholder="e.g., Gazipur" value="<?php echo isset($_POST['district']) ? htmlspecialchars($_POST['district']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Upazila/Sub-district</label>
                        <input type="text" name="upazila" placeholder="e.g., Kapasia" value="<?php echo isset($_POST['upazila']) ? htmlspecialchars($_POST['upazila']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Coverage Area (km)</label>
                        <input type="number" name="coverage_area_km" min="5" max="200" value="<?php echo isset($_POST['coverage_area_km']) ? htmlspecialchars($_POST['coverage_area_km']) : '20'; ?>" placeholder="Area you can service in km" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Experience (years)</label>
                        <input type="number" name="experience_years" min="0" max="50" value="<?php echo isset($_POST['experience_years']) ? htmlspecialchars($_POST['experience_years']) : '0'; ?>" placeholder="Your years of experience" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                </div>
            </section>

            <!-- Professional Details -->
            <section>
                <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">Professional Details</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Crops Expertise *</label>
                        <input type="text" name="crops_expertise" placeholder="e.g., Rice, Wheat, Vegetables" value="<?php echo isset($_POST['crops_expertise']) ? htmlspecialchars($_POST['crops_expertise']) : ''; ?>" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Vehicle Types</label>
                        <input type="text" name="vehicle_types" placeholder="e.g., Van, Pickup, Motorcycle" value="<?php echo isset($_POST['vehicle_types']) ? htmlspecialchars($_POST['vehicle_types']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Warehouse/Storage Capacity</label>
                    <input type="text" name="warehouse_capacity" placeholder="e.g., 500 sq ft, 10 tons" value="<?php echo isset($_POST['warehouse_capacity']) ? htmlspecialchars($_POST['warehouse_capacity']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                </div>
                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statement / Why become an agent?</label>
                    <textarea name="statement" rows="3" placeholder="Explain your motivation and how you plan to serve farmers and shops..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all"><?php echo isset($_POST['statement']) ? htmlspecialchars($_POST['statement']) : ''; ?></textarea>
                </div>
            </section>

            <!-- References -->
            <section>
                <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">Reference Contact</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference Name</label>
                        <input type="text" name="reference_name" placeholder="Name of shop owner, distributor, etc." value="<?php echo isset($_POST['reference_name']) ? htmlspecialchars($_POST['reference_name']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Reference Phone</label>
                        <input type="text" name="reference_phone" placeholder="+8801XXXXXXXXX" value="<?php echo isset($_POST['reference_phone']) ? htmlspecialchars($_POST['reference_phone']) : ''; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none transition-all">
                    </div>
                </div>
            </section>

            <!-- Documents Upload -->
            <section>
                <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b-2 border-green-600">Documents (Max 3MB each)</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">NID/ID Document</label>
                        <input type="file" name="id_doc" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Profile Photo</label>
                        <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trade License (Optional)</label>
                        <input type="file" name="trade_license" accept=".jpg,.jpeg,.png,.webp,.pdf" class="w-full text-sm text-gray-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-green-50 file:text-green-700 hover:file:bg-green-100 outline-none">
                    </div>
                </div>
            </section>

            <!-- Submit -->
            <div class="flex items-center justify-between pt-6 border-t">
                <a href="../Login-Signup/signup.php" class="text-sm text-gray-600 hover:text-gray-900">Back to regular signup</a>
                <button type="submit" class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 font-semibold shadow-lg hover:shadow-xl transition-all">
                    Submit Agent Application
                </button>
            </div>
        </form>

        <p class="text-center text-sm text-gray-600 mt-6">
            Already have an account? <a href="login.php" class="text-green-600 hover:underline font-medium">Log in</a>
        </p>
    </div>

    <?php include '../include/footer.php'; ?>

    <script>
        // Password popup functionality
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('password');
            const passwordPopup = document.getElementById('passwordPopup');
            const phoneInput = document.getElementById('phonenumber');
            const phonePopup = document.getElementById('phonePopup');
            const messagePopup = document.getElementById('messagePopup');

            // Show popup when password field is focused
            passwordInput.addEventListener('focus', function() {
                passwordPopup.style.display = 'block';
            });

            // Hide popup when focus moves away from password field
            passwordInput.addEventListener('blur', function() {
                setTimeout(function() {
                    passwordPopup.style.display = 'none';
                }, 300);
            });

            // Show popup when phone field is focused
            if (phoneInput && phonePopup) {
                phoneInput.addEventListener('focus', function() {
                    phonePopup.style.display = 'block';
                });

                // Hide popup when focus moves away from phone field
                phoneInput.addEventListener('blur', function() {
                    setTimeout(function() {
                        phonePopup.style.display = 'none';
                    }, 300);
                });
            }

            // Auto-hide message popup after 6 seconds
            if (messagePopup) {
                setTimeout(function() {
                    messagePopup.style.opacity = '0';
                    messagePopup.style.transition = 'opacity 0.5s ease';
                    setTimeout(function() {
                        messagePopup.style.display = 'none';
                    }, 500);
                }, 6000);
            }
        });
    </script>
</body>

</html>