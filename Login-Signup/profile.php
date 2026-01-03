<?php
// Start session and handle redirects BEFORE any output
session_start();
include __DIR__ . '/../include/connect-db.php'; // Database connection

// Get user_id from session
$user_id = $_SESSION['user_id'] ?? 0;

// Handle phone verification BEFORE any output
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verify_phone'])) {
    // Fetch user phone
    $stmt = $conn->prepare("SELECT phone FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_data = $result->fetch_assoc();
    $stmt->close();

    $phone = $user_data['phone'];
    $verification_code = rand(100000, 999999);


    // Send WhatsApp OTP
    $idInstance = "7105402430";
    $apiTokenInstance = "2d8e63493c2b41b990db9e521c5f62fdb2f20c7be5e14cbd8d";
    $url = "https://api.greenapi.com/waInstance{$idInstance}/sendMessage/{$apiTokenInstance}";

    $formattedPhone = preg_replace('/^\+?88/', '', $phone);
    $formattedPhone = '88' . $formattedPhone;

    $data = [
        "chatId" => $formattedPhone . "@c.us",
        "message" => "Your OTP verification code is: $verification_code"
    ];

    $options = [
        "http" => [
            "header"  => "Content-type: application/json\r\n",
            "method"  => "POST",
            "content" => json_encode($data),
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);
    @file_get_contents($url, false, $context);

    // Store verification data in session
    $_SESSION['pending_verification_user_id'] = $user_id;
    $_SESSION['pending_verification_phone'] = $phone;
    $_SESSION['verification_type'] = 'phone';
    $_SESSION['phone_verification_code'] = $verification_code;

    // Redirect to phone verification page
    header("Location: verify.php");
    exit();
}

include '../Include/SidebarShop.php';
?>
<link rel="stylesheet" href="../Include/sidebar.css">

<?php
$message = "";
$messageType = "";

// Handle two-factor authentication toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_two_factor'])) {
    $action = $_POST['two_factor_action']; // 'enable' or 'disable'

    if ($action === 'enable') {
        $two_factor_type = $_POST['two_factor_type']; // 'email' or 'phone'

        // Verify that the selected method is verified
        $stmt = $conn->prepare("SELECT is_valid_email, is_valid_phone FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $verification_status = $result->fetch_assoc();
        $stmt->close();

        $can_enable = false;
        if ($two_factor_type === 'email' && $verification_status['is_valid_email']) {
            $can_enable = true;
        } elseif ($two_factor_type === 'phone' && $verification_status['is_valid_phone']) {
            $can_enable = true;
        }

        if ($can_enable) {
            $stmt = $conn->prepare("UPDATE users SET is_two_factor_on = 1, two_factor_type = ? WHERE user_id = ?");
            $stmt->bind_param("si", $two_factor_type, $user_id);
            if ($stmt->execute()) {
                $message = "✅ Two-factor authentication enabled successfully!";
                $messageType = "success";
            } else {
                $message = "❌ Error enabling two-factor authentication.";
                $messageType = "error";
            }
            $stmt->close();
        } else {
            $message = "❌ Cannot enable two-factor authentication. Please verify your " . $two_factor_type . " first.";
            $messageType = "error";
        }
    } elseif ($action === 'disable') {
        $stmt = $conn->prepare("UPDATE users SET is_two_factor_on = 0, two_factor_type = NULL WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        if ($stmt->execute()) {
            $message = "✅ Two-factor authentication disabled successfully!";
            $messageType = "success";
        } else {
            $message = "❌ Error disabling two-factor authentication.";
            $messageType = "error";
        }
        $stmt->close();
    }
}

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
$stmt = $conn->prepare("SELECT full_name, email, phone, role, created_at, image_url, is_valid_phone, is_valid_email, is_two_factor_on, two_factor_type FROM users WHERE user_id = ?");
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
                        <?php if (!empty($user['image_url']) && file_exists($user['image_url'])): ?>
                            <img src="<?php echo htmlspecialchars($user['image_url']); ?>" alt="<?php echo htmlspecialchars($user['full_name']); ?>" class="w-full h-full object-cover">
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
                                <dd class="mt-1 text-sm text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <span><?php echo htmlspecialchars($user['email']); ?></span>
                                        <?php if ($user['is_valid_email']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Unverified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </dd>
                            </div>

                            <div>
                                <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                                <dd class="mt-1 text-sm text-gray-900">
                                    <div class="flex items-center gap-2">
                                        <span><?php echo htmlspecialchars($user['phone']); ?></span>
                                        <?php if ($user['is_valid_phone']): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Unverified
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                </dd>
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
                                    <div class="relative">
                                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required readonly
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 bg-gray-50 cursor-not-allowed sm:text-sm border">
                                        <?php if ($user['is_valid_email']): ?>
                                            <span class="absolute right-3 top-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="absolute right-3 top-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Unverified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-500">Email cannot be changed after registration.</p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                                <div>
                                    <label for="phone" class="block text-sm font-medium text-gray-700">Phone Number</label>
                                    <div class="relative">
                                        <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone']); ?>" required
                                            class="mt-1 block w-full border-gray-300 rounded-md shadow-sm px-3 py-2 focus:ring-green-500 focus:border-green-500 sm:text-sm border">
                                        <?php if ($user['is_valid_phone']): ?>
                                            <span class="absolute right-3 top-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="absolute right-3 top-3 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-yellow-100 text-yellow-800">
                                                Unverified
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if (!$user['is_valid_phone']): ?>
                                        <p class="mt-1 text-xs text-red-600">Please verify your phone number to access all features.</p>
                                        <form method="POST" action="profile.php" class="mt-2">
                                            <button type="submit" name="verify_phone"
                                                class="mt-2 inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                                                Verify Now
                                            </button>
                                        </form>
                                    <?php endif; ?>
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

                <!-- Two-Factor Authentication Settings - Collapsible -->
                <div class="bg-white overflow-hidden shadow rounded-lg mt-6" x-data="{ open: false }">
                    <div class="px-4 py-5 sm:p-6">
                        <!-- Header with Toggle -->
                        <button @click="open = !open" class="w-full flex items-center justify-between text-left group">
                            <div class="flex items-center">
                                <h3 class="text-lg font-medium text-gray-900">🔐 Two-Factor Authentication</h3>
                                <?php if ($user['is_two_factor_on']): ?>
                                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                        Enabled
                                    </span>
                                <?php else: ?>
                                    <span class="ml-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                        Disabled
                                    </span>
                                <?php endif; ?>
                            </div>
                            <svg class="h-5 w-5 text-gray-400 group-hover:text-gray-600 transition-transform duration-200"
                                :class="{ 'rotate-180': open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <!-- Collapsible Content -->
                        <div x-show="open" x-collapse class="mt-4 space-y-4">
                            <p class="text-sm text-gray-600">Add an extra layer of security to your account by requiring a verification code during login.</p>

                            <?php if ($user['is_two_factor_on']): ?>
                                <!-- Two-Factor is currently enabled -->
                                <div class="border border-green-200 rounded-md p-4 bg-green-50">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <h4 class="text-sm font-medium text-green-800 flex items-center">
                                                <span class="mr-2">✅</span> Two-Factor Authentication Enabled
                                            </h4>
                                            <p class="text-sm text-green-600 mt-1">
                                                Verification method:
                                                <strong><?php echo ucfirst($user['two_factor_type']); ?></strong>
                                                <?php if ($user['two_factor_type'] === 'email'): ?>
                                                    (<?php echo htmlspecialchars($user['email']); ?>)
                                                <?php else: ?>
                                                    (<?php echo htmlspecialchars($user['phone']); ?>)
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                    </div>
                                    <form method="POST" action="profile.php" class="mt-3">
                                        <input type="hidden" name="two_factor_action" value="disable">
                                        <button type="submit" name="toggle_two_factor"
                                            class="inline-flex items-center px-4 py-2 border border-red-300 shadow-sm text-sm font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500">
                                            Disable Two-Factor Authentication
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <!-- Two-Factor is currently disabled -->
                                <div class="border border-yellow-200 rounded-md p-4 bg-yellow-50">
                                    <div class="flex items-center justify-between mb-3">
                                        <div>
                                            <h4 class="text-sm font-medium text-yellow-800 flex items-center">
                                                <span class="mr-2">⚠️</span> Two-Factor Authentication Disabled
                                            </h4>
                                            <p class="text-sm text-yellow-600 mt-1">Your account is less secure without two-factor authentication.</p>
                                        </div>
                                    </div>

                                    <?php if ($user['is_valid_email'] || $user['is_valid_phone']): ?>
                                        <form method="POST" action="profile.php" class="space-y-3">
                                            <input type="hidden" name="two_factor_action" value="enable">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-2">Choose verification method:</label>
                                                <div class="space-y-2">
                                                    <?php if ($user['is_valid_email']): ?>
                                                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                                                            <input type="radio" name="two_factor_type" value="email" required
                                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                                            <div class="ml-3">
                                                                <span class="block text-sm font-medium text-gray-900">📧 Email Verification</span>
                                                                <span class="block text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></span>
                                                            </div>
                                                        </label>
                                                    <?php endif; ?>

                                                    <?php if ($user['is_valid_phone']): ?>
                                                        <label class="flex items-center p-3 border border-gray-300 rounded-md hover:bg-gray-50 cursor-pointer">
                                                            <input type="radio" name="two_factor_type" value="phone" required
                                                                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300">
                                                            <div class="ml-3">
                                                                <span class="block text-sm font-medium text-gray-900">📱 Phone Verification (WhatsApp)</span>
                                                                <span class="block text-sm text-gray-500"><?php echo htmlspecialchars($user['phone']); ?></span>
                                                            </div>
                                                        </label>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <button type="submit" name="toggle_two_factor"
                                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 shadow-sm">
                                                Enable Two-Factor Authentication
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="border border-gray-300 rounded-md p-3 bg-gray-50">
                                            <p class="text-sm text-gray-700">
                                                ⚠️ You must verify at least one contact method (email or phone) before enabling two-factor authentication.
                                            </p>
                                            <div class="mt-2 space-x-2">
                                                <?php if (!$user['is_valid_email']): ?>
                                                    <span class="text-xs text-red-600">❌ Email not verified</span>
                                                <?php endif; ?>
                                                <?php if (!$user['is_valid_phone']): ?>
                                                    <span class="text-xs text-red-600">❌ Phone not verified</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Danger Zone -->
                <!-- <div class="bg-white overflow-hidden shadow rounded-lg mt-6">
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
                </div> -->
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