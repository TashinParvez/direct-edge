<?php
include __DIR__ . '/../include/connect-db.php'; // Database connection

session_start();
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
}

$name = '';

$stmt = $conn->prepare('SELECT full_name FROM users WHERE user_id = ? LIMIT 1');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();

$stmt->close();

// Take only first name from full_name
if (!empty($name)) {
    $name = explode(' ', trim($name))[0];
}

// echo $user_id;

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400..700;1,400..700&display=swap" rel="stylesheet">

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        body,
        * {
            font-family: "Lora", serif;
        }

        /* Mega menu width and position */
        .mega-menu {
            width: 900px;
            left: -700px;
        }
    </style>
</head>

<body class="bg-white">
    <header class="sticky top-0 z-50 bg-white border-b shadow-sm">
        <!-- Single Line Navbar -->
        <nav class="w-full">
            <div class="flex items-center justify-between px-6 py-2">
                <!-- Logo -->
                <a class="flex items-center flex-shrink-0" href="/Home/Homepage.php">
                    <img src="../assets/Logo/logo.png" alt="Logo" class="h-14 w-14 object-contain">
                </a>

                <!-- Main Navigation -->
                <div class="flex-1 flex justify-center">
                    <ul class="flex space-x-6 items-center">
                        <li>
                            <a class="text-green-700 font-semibold hover:text-green-600" href="../Home/landing.php">Home</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="">Dashboard</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="">Projection</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="#">Receipt</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="">Track Storage</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="">Order</a>
                        </li>
                        <li>
                            <a class="text-gray-700 hover:text-green-700 font-semibold" href="">Saved</a>
                        </li>

                        <!-- Mega Menu Dropdown -->
                        <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                            <a href="/Recipe Search/RecipeSearch.php"
                                class="flex items-center cursor-pointer font-semibold text-gray-700 hover:text-green-700"
                                @click.prevent="open = !open">
                                Mega Menu
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </a>

                            <!-- Mega Menu -->
                            <div x-show="open" x-transition class="mega-menu absolute top-full bg-gray-50 shadow-lg rounded p-6 z-50"
                                @mouseenter="open = true" @mouseleave="open = false" style="display: none;">
                                <div class="flex space-x-8">
                                    <div class="w-48">
                                        <h6 class="font-semibold mb-3 text-gray-800 pl-4">Popular Categories</h6>
                                        <ul>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="/All Categories/oneparticularCategoryShow.php">Appetizer</a></li>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Main Course</a></li>
                                        </ul>
                                    </div>

                                    <div class="w-48">
                                        <h6 class="font-semibold mb-3 text-gray-800 pl-4">Recipe by Meal</h6>
                                        <ul>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Breakfast</a></li>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Brunch</a></li>
                                        </ul>
                                    </div>

                                    <div class="w-48">
                                        <h6 class="font-semibold mb-3 text-gray-800 pl-4">Recipe by Diet</h6>
                                        <ul>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-70 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Vegetarian</a></li>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Gluten Free</a></li>
                                        </ul>
                                    </div>

                                    <div class="w-48">
                                        <h6 class="font-semibold mb-3 text-gray-800 pl-4">What to Cook</h6>
                                        <ul>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Quick and Easy</a></li>
                                            <li><a
                                                    class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
                                                    href="#">Family Recipe</a></li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Login / Signup -->
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <a href="../Login-Signup/profile.php" class="text-black hover:text-green-600 text-sm font-semibold"><?php echo htmlspecialchars($name); ?></a>
                        <span>|</span>
                        <a href="../Login-Signup/logout.php" class="text-black hover:text-green-600 text-sm font-semibold">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="flex items-center space-x-2 flex-shrink-0">
                        <a href="../Login-Signup/login.php" class="text-black hover:text-green-600 text-sm font-semibold">Login</a>
                        <span>|</span>
                        <a href="../Login-Signup/signup.php" class="text-black hover:text-green-600 text-sm font-semibold">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>
    </header>
</body>

</html>