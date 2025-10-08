<?php
// profile.php

// 1) Session and auth guard
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../Login-Signup/login.php");
    exit();
}

// 2) DB connection
$servername = "localhost";
$username   = "root";
$password   = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// 3) CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(20));
}
$csrf_token = $_SESSION['csrf_token'];

// 4) Helpers
function sanitize($v)
{
    return htmlspecialchars($v ?? "", ENT_QUOTES, 'UTF-8');
}
function initials($name)
{
    $parts = preg_split('/\s+/', trim($name));
    $first = $parts[0] ?? '';
    $last  = $parts[1] ?? '';
    return strtoupper(($first[0] ?? '') . ($last[0] ?? ''));
}

// 5) Load user (by primary key user_id)
$user_id = (int) $_SESSION['user_id'];
$sql = "SELECT user_id, full_name, email, phone, image_url, role, created_at, updated_at FROM users WHERE user_id = ? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res  = $stmt->get_result();
$user = $res->fetch_assoc();
$stmt->close();

if (!$user) {
    // If not found, force logout
    session_destroy();
    header("Location: ../Login-Signup/login.php");
    exit();
}

$message = "";
$ok = true;

// 6) Handle profile update (name/email/phone + optional avatar)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "update_profile") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $ok = false;
        $message = "Security check failed.";
    }

    if ($ok) {
        $full_name = trim($_POST['full_name'] ?? "");
        $email     = trim($_POST['email'] ?? "");
        $phone     = trim($_POST['phone'] ?? "");
        $image_url = $user['image_url']; // keep current unless replaced

        // Optional avatar upload
        if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_file($finfo, $_FILES['avatar']['tmp_name']);
                finfo_close($finfo);

                if (in_array($mime, $allowed, true) && $_FILES['avatar']['size'] <= 2 * 1024 * 1024) {
                    $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
                    $safeExt = strtolower($ext);
                    if (!in_array($safeExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                        // normalize extension from mime if needed
                        $safeExt = match ($mime) {
                            'image/jpeg' => 'jpg',
                            'image/png'  => 'png',
                            'image/webp' => 'webp',
                            'image/gif'  => 'gif',
                            default      => 'jpg'
                        };
                    }
                    $dir = __DIR__ . "/uploads/avatars";
                    if (!is_dir($dir)) {
                        @mkdir($dir, 0775, true);
                    }
                    $filename = "u{$user_id}_" . time() . "." . $safeExt;
                    $destPath = $dir . "/" . $filename;
                    if (move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
                        // store relative path
                        $image_url = "uploads/avatars/" . $filename;
                    } else {
                        $ok = false;
                        $message = "Failed to save uploaded image.";
                    }
                } else {
                    $ok = false;
                    $message = "Invalid image (type/size). Max 2MB, jpg/png/webp/gif only.";
                }
            } else {
                $ok = false;
                $message = "Image upload error code: " . (int)$_FILES['avatar']['error'];
            }
        }

        if ($ok) {
            $sql = "UPDATE users 
                    SET full_name = ?, email = ?, phone = ?, image_url = ?, updated_at = NOW()
                    WHERE user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssi", $full_name, $email, $phone, $image_url, $user_id);
            if ($stmt->execute()) {
                $message = "Profile updated successfully.";
                // refresh $user
                $stmt->close();
                $stmt = $conn->prepare("SELECT user_id, full_name, email, phone, image_url, role, created_at, updated_at FROM users WHERE user_id = ? LIMIT 1");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();
            } else {
                $ok = false;
                $message = "Update failed: " . sanitize($stmt->error);
            }
            $stmt->close();
        }
    }
}

// 7) Handle password change
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "change_password") {
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
        $ok = false;
        $message = "Security check failed.";
    }

    $current = $_POST['current_password'] ?? "";
    $new     = $_POST['new_password'] ?? "";
    $confirm = $_POST['confirm_password'] ?? "";

    if ($ok) {
        // Fetch hash
        $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ? LIMIT 1");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $hash = $stmt->get_result()->fetch_assoc()['password'] ?? null;
        $stmt->close();

        if (!$hash || !password_verify($current, $hash)) {
            $ok = false;
            $message = "Current password is incorrect.";
        } elseif (strlen($new) < 6) {
            $ok = false;
            $message = "New password must be at least 6 characters.";
        } elseif ($new !== $confirm) {
            $ok = false;
            $message = "New passwords do not match.";
        } else {
            $newHash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
            $stmt->bind_param("si", $newHash, $user_id);
            if ($stmt->execute()) {
                $message = "Password changed successfully.";
            } else {
                $ok = false;
                $message = "Password update failed: " . sanitize($stmt->error);
            }
            $stmt->close();
        }
    }
}

mysqli_close($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Profile — DirectEdge</title>
    <link rel="icon" type="image/x-icon" href="../Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">
    <!-- Top bar -->
    <nav class="bg-white border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-lg bg-green-600 text-white grid place-items-center font-bold">DE</div>
                <span class="text-lg font-semibold text-gray-900">DirectEdge</span>
            </div>
            <div class="flex items-center gap-4">
                <span class="hidden sm:block text-sm text-gray-700"><?php echo sanitize($user['full_name']); ?></span>
                <a class="text-sm text-red-600 hover:text-red-700" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash -->
        <?php if ($message !== ""): ?>
            <div class="mb-6 rounded-md p-4 <?php echo $ok ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'; ?>">
                <p class="<?php echo $ok ? 'text-green-800' : 'text-red-800'; ?> text-sm font-medium">
                    <?php echo sanitize($message); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <section class="bg-white rounded-xl shadow">
            <div class="bg-gradient-to-r from-green-600 to-emerald-500 h-28 rounded-t-xl"></div>
            <div class="-mt-12 px-6 pb-6">
                <div class="flex items-end gap-4">
                    <?php if (!empty($user['image_url'])): ?>
                        <img src="<?php echo sanitize($user['image_url']); ?>" alt="Avatar" class="w-24 h-24 rounded-xl ring-4 ring-white object-cover">
                    <?php else: ?>
                        <div class="w-24 h-24 rounded-xl ring-4 ring-white bg-green-100 text-green-700 grid place-items-center text-3xl font-bold">
                            <?php echo initials($user['full_name']); ?>
                        </div>
                    <?php endif; ?>
                    <div class="pb-2">
                        <h1 class="text-2xl font-bold text-gray-900"><?php echo sanitize($user['full_name']); ?></h1>
                        <p class="text-sm text-gray-600">
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
              <?php
                echo match ($user['role']) {
                    'Admin' => 'bg-red-100 text-red-700',
                    'Shop-Owner' => 'bg-purple-100 text-purple-700',
                    'Agent' => 'bg-green-100 text-green-700',
                    default => 'bg-blue-100 text-blue-700'
                };
                ?>">
                                <?php echo sanitize($user['role']); ?>
                            </span>
                            <span class="ml-2">• Member since <?php echo date('F Y', strtotime($user['created_at'])); ?></span>
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-6">
            <!-- Quick actions -->
            <aside class="lg:col-span-1">
                <div class="bg-white rounded-xl shadow p-5">
                    <h3 class="text-sm font-semibold text-gray-900 mb-4">Quick actions</h3>
                    <div class="space-y-2">
                        <?php if ($user['role'] === 'Shop-Owner'): ?>
                            <a href="shop-management.php" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Manage Shop</a>
                        <?php elseif ($user['role'] === 'Agent'): ?>
                            <a href="crop-management.php" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Manage Crops</a>
                        <?php elseif ($user['role'] === 'Admin'): ?>
                            <a href="admin.php" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Admin Panel</a>
                        <?php endif; ?>
                        <a href="support.php" class="w-full inline-flex items-center justify-center px-3 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Contact Support</a>
                    </div>
                    <div class="mt-6 text-xs text-gray-500">
                        Last updated: <?php echo sanitize(date('M j, Y g:i A', strtotime($user['updated_at'] ?? $user['created_at']))); ?>
                    </div>
                </div>
            </aside>

            <!-- Forms -->
            <section class="lg:col-span-2 space-y-6">
                <!-- Profile form -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Edit profile</h3>
                    <form method="POST" enctype="multipart/form-data" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="update_profile">

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Full name</label>
                                <input name="full_name" type="text" value="<?php echo sanitize($user['full_name']); ?>" required class="w-full rounded-md border-gray-300 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Email</label>
                                <input name="email" type="email" value="<?php echo sanitize($user['email']); ?>" required class="w-full rounded-md border-gray-300 focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Phone</label>
                                <input name="phone" type="text" value="<?php echo sanitize($user['phone']); ?>" required class="w-full rounded-md border-gray-300 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Account type</label>
                                <input value="<?php echo sanitize($user['role']); ?>" disabled class="w-full rounded-md border-gray-200 bg-gray-50 text-gray-600">
                                <p class="text-xs text-gray-500 mt-1">Role changes are managed by administrators.</p>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-700 mb-1">Avatar (optional, max 2MB)</label>
                            <input name="avatar" type="file" accept="image/*" class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100">
                            <?php if (!empty($user['image_url'])): ?>
                                <p class="text-xs text-gray-500 mt-1">Current: <?php echo sanitize($user['image_url']); ?></p>
                            <?php endif; ?>
                        </div>

                        <div class="flex items-center justify-end gap-3">
                            <button type="reset" class="px-4 py-2 rounded-md border text-sm text-gray-700 hover:bg-gray-50">Cancel</button>
                            <button type="submit" class="px-5 py-2 rounded-md text-sm text-white bg-green-600 hover:bg-green-700">Save changes</button>
                        </div>
                    </form>
                </div>

                <!-- Password form -->
                <div class="bg-white rounded-xl shadow p-6">
                    <h3 class="text-base font-semibold text-gray-900 mb-4">Change password</h3>
                    <form method="POST" class="space-y-5">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="action" value="change_password">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Current password</label>
                                <input name="current_password" type="password" required class="w-full rounded-md border-gray-300 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">New password</label>
                                <input name="new_password" type="password" required class="w-full rounded-md border-gray-300 focus:ring-green-500 focus:border-green-500">
                            </div>
                            <div>
                                <label class="block text-sm text-gray-700 mb-1">Confirm new password</label>
                                <input name="confirm_password" type="password" required class="w-full rounded-md border-gray-300 focus:ring-green-500 focus:border-green-500">
                            </div>
                        </div>
                        <div class="flex items-center justify-end">
                            <button type="submit" class="px-5 py-2 rounded-md text-sm text-white bg-green-600 hover:bg-green-700">Update password</button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </main>
</body>

</html>