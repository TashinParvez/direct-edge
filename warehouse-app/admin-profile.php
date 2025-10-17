<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../Include/SidebarWarehouse.php'; ?>

<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection
// include '../include/navbar.php';

$message = "";
$messageType = "";

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_profile'])) {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $role = $_POST['role'];

    // Handle password update
    if (!empty($_POST['new_password'])) {
        if ($_POST['new_password'] === $_POST['confirm_password']) {
            $hashed_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ?, password = ? WHERE user_id = ?");
            $stmt->bind_param("sssssi", $full_name, $email, $phone, $role, $hashed_password, $user_id);
        } else {
            $message = "New passwords do not match!";
            $messageType = "error";
        }
    } else {
        $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, role = ? WHERE user_id = ?");
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $role, $user_id);
    }

    if (isset($stmt) && $stmt->execute()) {
        $message = "✅ Profile updated successfully!";
        $messageType = "success";
    } elseif (!isset($stmt)) {
        // Don't update if there was a password mismatch
    } else {
        $message = "❌ Error updating profile: " . $stmt->error;
        $messageType = "error";
    }

    if (isset($stmt)) {
        $stmt->close();
    }
}

// Fetch current user data
$stmt = $conn->prepare("SELECT full_name, email, phone, role, created_at, image_url FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - DirectEdge</title>
    <link rel="icon" type="image/x-icon" href="../Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
</head>

<body class="bg-gray-50">
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        
        <!-- Profile Header -->
        <div class="bg-white overflow-hidden shadow rounded-lg mb-6">
            <div class="bg-gradient-to-r from-green-600 to-blue-600 px-4 py-5 sm:px-6">
                <div class="flex items-center">
                    <div class="w-20 h-20 bg-white rounded-full flex items-center justify-center shadow-lg overflow-hidden">
                        <?php if (!empty($user['image_url']) && file_exists('../' . $user['image_url'])): ?>
                            <img src="<?php echo '../' . htmlspecialchars($user['image_url']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full h-full object-cover">
                        <?php else: ?>
                            <span class="text-3xl font-bold text-green-600"><?php echo strtoupper(substr($user['full_name'], 0, 1)); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="ml-6">
                        <h1 class="text-2xl font-bold text-white"><?php echo htmlspecialchars($user['full_name']); ?></h1>
                        <p class="text-green-100">
                            <?php
                            $roleDisplay = [
                                'Admin' => '🔧 Administrator',
                                'Shop-Owner' => '🏪 Shop Owner',
                                'Agent' => '🌾 Agricultural Agent',
                                'User' => '👤 Platform User'
                            ];
                            echo $roleDisplay[$user['role']] ?? $user['role'];
                            ?>
                        </p>
                        <p class="text-green-100 text-sm">Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Profile Information Card -->
            <div class="lg:col-span-1">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Profile Information</h3>

                        <dl class="space-y-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Full Name</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['email']); ?></dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($user['phone']); ?></dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Account Type</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                        <?php
                                        switch ($user['role']) {
                                            case 'Admin':
                                                echo 'bg-red-100 text-red-800';
                                                break;
                                            case 'Shop-Owner':
                                                echo 'bg-purple-100 text-purple-800';
                                                break;
                                            case 'Agent':
                                                echo 'bg-green-100 text-green-800';
                                                break;
                                            default:
                                                echo 'bg-blue-100 text-blue-800';
                                        }
                                        ?>">
                                        <?php echo htmlspecialchars($user['role']); ?>
                                    </span>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                                <dd class="mt-1">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        ✅ Active
                                    </span>
                                </dd>
                            </div>
                        </dl>

                        <!-- Quick Actions -->
                        <div class="mt-6 pt-6 border-t border-gray-200">
                            <h4 class="text-sm font-medium text-gray-900 mb-3">Quick Actions</h4>
                            <div class="space-y-2">
                                <?php if ($user['role'] === 'Shop-Owner'): ?>
                                    <a href="shop-management.php" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        🏪 Manage Shop
                                    </a>
                                <?php elseif ($user['role'] === 'Agent'): ?>
                                    <a href="crop-management.php" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                        🌾 Manage Crops
                                    </a>
                                <?php endif; ?>

                                <a href="support.php" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    📞 Contact Support
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Profile Form -->
            <div class="lg:col-span-2">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Edit Profile</h3>

                        <?php if ($message != ""): ?>
                            <div class="mb-4 p-4 rounded-md <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                                <p class="text-sm font-medium <?php echo $messageType === 'success' ? 'text-green-800' : 'text-red-800'; ?>">
                                    <?php echo htmlspecialchars($message); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <form action="" method="POST" class="space-y-6">
                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="full_name" class="block text-sm font-medium text-gray-700">Full Name</label>
                                    <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                </div>

                                <div>
                                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address</label>
                                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                </div>

                                <div>
                                    <label for="role" class="block text-sm font-medium text-gray-700">Account Type</label>
                                    <select id="role" name="role" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                        <option value="User" <?php echo ($user['role'] === 'User') ? 'selected' : ''; ?>>User</option>
                                        <option value="Shop-Owner" <?php echo ($user['role'] === 'Shop-Owner') ? 'selected' : ''; ?>>Shop Owner</option>
                                        <option value="Agent" <?php echo ($user['role'] === 'Agent') ? 'selected' : ''; ?>>Agricultural Agent</option>
                                        <option value="Admin" <?php echo ($user['role'] === 'Admin') ? 'selected' : ''; ?>>Administrator</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Password Change Section -->
                            <div class="pt-6 border-t border-gray-200">
                                <h4 class="text-lg font-medium text-gray-900 mb-4">Change Password</h4>
                                <p class="text-sm text-gray-600 mb-4">Leave blank if you don't want to change your password</p>

                                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                    <div>
                                        <label for="new_password" class="block text-sm font-medium text-gray-700">New Password</label>
                                        <input type="password" id="new_password" name="new_password" placeholder="Enter new password"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                    </div>

                                    <div>
                                        <label for="confirm_password" class="block text-sm font-medium text-gray-700">Confirm New Password</label>
                                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password"
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="flex items-center justify-between pt-6 border-t border-gray-200">
                                <button type="button" onclick="window.location.reload()"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Cancel
                                </button>

                                <button type="submit" name="update_profile"
                                    class="inline-flex items-center px-6 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm">
                                    Save Changes
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Account Settings -->
                <div class="bg-white overflow-hidden shadow rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Account Settings</h3>

                        <div class="space-y-4">
                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Email Notifications</h4>
                                    <p class="text-sm text-gray-500">Receive updates about your account activity</p>
                                </div>
                                <button class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 bg-green-600">
                                    <span class="translate-x-5 pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                                </button>
                            </div>

                            <div class="flex items-center justify-between py-3 border-b border-gray-200">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">SMS Notifications</h4>
                                    <p class="text-sm text-gray-500">Receive important updates via SMS</p>
                                </div>
                                <button class="relative inline-flex flex-shrink-0 h-6 w-11 border-2 border-transparent rounded-full cursor-pointer transition-colors ease-in-out duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 bg-gray-200">
                                    <span class="translate-x-0 pointer-events-none inline-block h-5 w-5 rounded-full bg-white shadow transform ring-0 transition ease-in-out duration-200"></span>
                                </button>
                            </div>

                            <div class="flex items-center justify-between py-3">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900">Two-Factor Authentication</h4>
                                    <p class="text-sm text-gray-500">Add an extra layer of security to your account</p>
                                </div>
                                <button class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                    Setup
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <div class="bg-white overflow-hidden shadow rounded-lg mt-6">
                    <div class="px-4 py-5 sm:p-6">
                        <h3 class="text-lg font-medium text-red-600 mb-4">⚠️ Danger Zone</h3>

                        <div class="space-y-4">
                            <div class="border border-red-200 rounded-md p-4 bg-red-50">
                                <h4 class="text-sm font-medium text-red-800">Delete Account</h4>
                                <p class="text-sm text-red-600 mt-1">Once you delete your account, there is no going back. Please be certain.</p>
                                <button class="mt-3 inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                    🗑️ Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for interactive elements -->
    <script>
        // Toggle switches
        document.querySelectorAll('button[class*="bg-green-600"], button[class*="bg-gray-200"]').forEach(button => {
            button.addEventListener('click', function() {
                const span = this.querySelector('span');
                if (this.classList.contains('bg-green-600')) {
                    this.classList.remove('bg-green-600');
                    this.classList.add('bg-gray-200');
                    span.classList.remove('translate-x-5');
                    span.classList.add('translate-x-0');
                } else {
                    this.classList.remove('bg-gray-200');
                    this.classList.add('bg-green-600');
                    span.classList.remove('translate-x-0');
                    span.classList.add('translate-x-5');
                }
            });
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('new_password').value;
            const confirmPassword = this.value;

            if (password && confirmPassword && password !== confirmPassword) {
                this.classList.add('border-red-300', 'ring-red-500');
                this.classList.remove('border-gray-300', 'ring-green-500');
            } else {
                this.classList.remove('border-red-300', 'ring-red-500');
                this.classList.add('border-gray-300');
            }
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (newPassword && confirmPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('New passwords do not match!');
            }
        });

        console.log('DirectEdge Profile Page Loaded Successfully');
    </script>
</body>

</html>