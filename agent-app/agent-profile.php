<?php
// agent-profile.php - Dedicated profile page for agents only
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$user_id = (int)$_SESSION['user_id'];

// Fetch user data
$stmt = $conn->prepare("SELECT user_id, full_name, email, phone, password, role, image_url, created_at, updated_at FROM users WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$user) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Check if user is actually an agent
if ($user['role'] !== 'Agent') {
    header("Location: profile.php"); // Redirect to regular profile
    exit();
}

// Fetch agent-specific info
$stmt = $conn->prepare("SELECT * FROM agent_info WHERE user_id = ? LIMIT 1");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$agent_info = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$agent_info) {
    $error = "Agent information not found!";
}

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$message = "";
$success = false;

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {

        // Basic info update
        $full_name = trim($_POST['full_name']);
        $email = trim($_POST['email']);
        $phone = trim($_POST['phone']);

        // Agent info update
        $region = trim($_POST['region']);
        $district = trim($_POST['district']);
        $upazila = trim($_POST['upazila']);
        $coverage_area_km = (int)$_POST['coverage_area_km'];
        $experience_years = (int)$_POST['experience_years'];
        $crops_expertise = trim($_POST['crops_expertise']);
        $vehicle_types = trim($_POST['vehicle_types']);
        $warehouse_capacity = trim($_POST['warehouse_capacity']);

        $image_url = $user['image_url'];

        // Handle photo upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            $allowed = ['image/jpeg', 'image/png', 'image/webp'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $_FILES['profile_photo']['tmp_name']);
            finfo_close($finfo);

            if (in_array($mime, $allowed) && $_FILES['profile_photo']['size'] <= 2 * 1024 * 1024) {
                $upload_dir = __DIR__ . "/uploads/agent_docs";
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

                $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
                $filename = "photo_u{$user_id}_" . time() . "." . $ext;
                $dest = $upload_dir . "/" . $filename;

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                    $image_url = "uploads/agent_docs/" . $filename;
                }
            }
        }

        mysqli_begin_transaction($conn);

        try {
            // Update users table
            $stmt = $conn->prepare("UPDATE users SET full_name=?, email=?, phone=?, image_url=?, updated_at=NOW() WHERE user_id=?");
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $image_url, $user_id);
            $stmt->execute();
            $stmt->close();

            // Update agent_info table
            $stmt = $conn->prepare("UPDATE agent_info SET region=?, district=?, upazila=?, coverage_area_km=?, experience_years=?, crops_expertise=?, vehicle_types=?, warehouse_capacity=?, updated_at=NOW() WHERE user_id=?");
            $stmt->bind_param("ssssisssi", $region, $district, $upazila, $coverage_area_km, $experience_years, $crops_expertise, $vehicle_types, $warehouse_capacity, $user_id);
            $stmt->execute();
            $stmt->close();

            mysqli_commit($conn);

            $success = true;
            $message = "Profile updated successfully!";

            // Refresh data
            $stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
            $stmt->close();

            $stmt = $conn->prepare("SELECT * FROM agent_info WHERE user_id = ? LIMIT 1");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $agent_info = $stmt->get_result()->fetch_assoc();
            $stmt->close();
        } catch (Exception $e) {
            mysqli_rollback($conn);
            $message = "Update failed: " . $e->getMessage();
        }
    } else {
        $message = "Security validation failed!";
    }
}

// Handle password change
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if (password_verify($current_password, $user['password'])) {
            if ($new_password === $confirm_password && strlen($new_password) >= 6) {
                $hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password=?, updated_at=NOW() WHERE user_id=?");
                $stmt->bind_param("si", $hashed, $user_id);

                if ($stmt->execute()) {
                    $success = true;
                    $message = "Password changed successfully!";
                } else {
                    $message = "Password update failed!";
                }
                $stmt->close();
            } else {
                $message = "New passwords don't match or too short (min 6 chars)!";
            }
        } else {
            $message = "Current password is incorrect!";
        }
    }
}

mysqli_close($conn);

function sanitize($v)
{
    return htmlspecialchars($v ?? "", ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agent Profile - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-50">
    <?php include '../include/navbar.php'; ?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Status Message -->
        <?php if ($message): ?>
            <div class="mb-6 rounded-lg p-4 <?php echo $success ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <p class="<?php echo $success ? 'text-green-800' : 'text-red-800'; ?> text-sm font-medium">
                    <?php echo sanitize($message); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="bg-white rounded-2xl shadow-xl mb-6 overflow-hidden">
            <div class="bg-gradient-to-r from-green-600 via-emerald-500 to-green-700 h-32"></div>
            <div class="px-6 pb-6 -mt-16">
                <div class="flex flex-col sm:flex-row items-center sm:items-end gap-4">
                    <?php if (!empty($user['image_url'])): ?>
                        <img src="<?php echo sanitize($user['image_url']); ?>" alt="Agent Photo" class="w-32 h-32 rounded-2xl ring-4 ring-white object-cover shadow-lg">
                    <?php else: ?>
                        <div class="w-32 h-32 rounded-2xl ring-4 ring-white bg-green-100 text-green-700 grid place-items-center text-4xl font-bold shadow-lg">
                            <?php echo strtoupper(substr($user['full_name'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-center sm:text-left pb-2">
                        <h1 class="text-2xl font-bold text-white"><?php echo sanitize($user['full_name']); ?></h1>
                        <p class="text-sm text-gray-600 flex items-center gap-2 justify-center sm:justify-start mt-1">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                🌾 Agricultural Agent
                            </span>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                <?php
                                $status = $agent_info['status'] ?? 'Pending';
                                echo match ($status) {
                                    'Approved' => 'bg-green-100 text-green-700',
                                    'Rejected' => 'bg-red-100 text-red-700',
                                    default => 'bg-yellow-100 text-yellow-700'
                                };
                                ?>">
                                <?php echo sanitize($status); ?>
                            </span>
                        </p>
                        <p class="text-xs text-gray-500 mt-2">
                            📍 <?php echo sanitize($agent_info['region'] ?? 'N/A'); ?> •
                            Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Quick Stats -->
            <aside class="lg:col-span-1 space-y-6">
                <!-- Agent Stats Card -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Agent Statistics</h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <span class="text-sm text-gray-700">Coverage Area</span>
                            <span class="text-sm font-bold text-green-700"><?php echo sanitize($agent_info['coverage_area_km'] ?? 0); ?> km</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <span class="text-sm text-gray-700">Experience</span>
                            <span class="text-sm font-bold text-blue-700"><?php echo sanitize($agent_info['experience_years'] ?? 0); ?> years</span>
                        </div>
                        <div class="flex items-center justify-between p-3 bg-purple-50 rounded-lg">
                            <span class="text-sm text-gray-700">Status</span>
                            <span class="text-sm font-bold text-purple-700"><?php echo sanitize($agent_info['status'] ?? 'Pending'); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Agent Info Card -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Professional Details</h3>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Crops Expertise</dt>
                            <dd class="text-sm text-gray-900 mt-1"><?php echo sanitize($agent_info['crops_expertise'] ?? 'N/A'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Vehicles</dt>
                            <dd class="text-sm text-gray-900 mt-1"><?php echo sanitize($agent_info['vehicle_types'] ?? 'N/A'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">Storage Capacity</dt>
                            <dd class="text-sm text-gray-900 mt-1"><?php echo sanitize($agent_info['warehouse_capacity'] ?? 'N/A'); ?></dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 uppercase">NID Number</dt>
                            <dd class="text-sm text-gray-900 mt-1"><?php echo sanitize($agent_info['nid_number'] ?? 'N/A'); ?></dd>
                        </div>
                    </dl>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-base font-bold text-gray-900 mb-4">Quick Actions</h3>
                    <div class="space-y-2">
                        <a href="manage-deliveries.php" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition">
                            🚚 Manage Deliveries
                        </a>
                        <a href="crop-listings.php" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition">
                            🌾 View Crop Listings
                        </a>
                        <a href="orders.php" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition">
                            📦 Orders
                        </a>
                        <a href="agent-farmer-dashboard.php" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg border">
                            🌾 Manage Farmers
                        </a>
                        <a href="support.php" class="w-full flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-200 text-sm text-gray-700 hover:bg-gray-50 transition">
                            📞 Contact Support
                        </a>

                    </div>
                </div>
            </aside>

            <!-- Main Content -->
            <section class="lg:col-span-2 space-y-6">
                <!-- Edit Profile Form -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Edit Profile Information</h3>
                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <!-- Basic Info -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Basic Information</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Full Name</label>
                                    <input type="text" name="full_name" value="<?php echo sanitize($user['full_name']); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Email</label>
                                    <input type="email" name="email" value="<?php echo sanitize($user['email']); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Phone</label>
                                    <input type="text" name="phone" value="<?php echo sanitize($user['phone']); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Profile Photo</label>
                                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png,.webp" class="w-full text-xs text-gray-600 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100 outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Location -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Location & Coverage</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Region</label>
                                    <input type="text" name="region" value="<?php echo sanitize($agent_info['region'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">District</label>
                                    <input type="text" name="district" value="<?php echo sanitize($agent_info['district'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Upazila</label>
                                    <input type="text" name="upazila" value="<?php echo sanitize($agent_info['upazila'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Coverage Area (km)</label>
                                    <input type="number" name="coverage_area_km" value="<?php echo sanitize($agent_info['coverage_area_km'] ?? 20); ?>" min="5" max="200" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Experience (years)</label>
                                    <input type="number" name="experience_years" value="<?php echo sanitize($agent_info['experience_years'] ?? 0); ?>" min="0" max="50" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                            </div>
                        </div>

                        <!-- Professional Details -->
                        <div>
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Professional Details</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Crops Expertise</label>
                                    <input type="text" name="crops_expertise" value="<?php echo sanitize($agent_info['crops_expertise'] ?? ''); ?>" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-gray-600 mb-1">Vehicle Types</label>
                                    <input type="text" name="vehicle_types" value="<?php echo sanitize($agent_info['vehicle_types'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                                </div>
                            </div>
                            <div class="mt-4">
                                <label class="block text-xs font-medium text-gray-600 mb-1">Warehouse Capacity</label>
                                <input type="text" name="warehouse_capacity" value="<?php echo sanitize($agent_info['warehouse_capacity'] ?? ''); ?>" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-4 border-t">
                            <button type="reset" class="px-5 py-2 rounded-lg border border-gray-300 text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700 font-medium">Save Changes</button>
                        </div>
                    </form>
                </div>

                <!-- Change Password -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-6">Change Password</h3>
                    <form method="POST" class="space-y-4">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <input type="hidden" name="action" value="change_password">

                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Current Password</label>
                                <input type="password" name="current_password" required class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">New Password</label>
                                <input type="password" name="new_password" required minlength="6" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Confirm Password</label>
                                <input type="password" name="confirm_password" required minlength="6" class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 outline-none">
                            </div>
                        </div>

                        <div class="flex justify-end pt-2">
                            <button type="submit" class="px-5 py-2 rounded-lg bg-green-600 text-white text-sm hover:bg-green-700 font-medium">Update Password</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>

    <?php include '../include/footer.php'; ?>
</body>

</html>