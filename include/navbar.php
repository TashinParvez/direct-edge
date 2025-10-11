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

        /* Hide scroll on search suggestions */
        #suggestions::-webkit-scrollbar {
            width: 6px;
        }

        #suggestions {
            scrollbar-width: thin;
        }
    </style>
</head>

<body class="bg-white">
    <header class="sticky top-0 z-50">
        <!-- Top Navbar -->
        <nav class="w-full bg-white">
            <div class="w-full flex items-center justify-between py-2 relative">
                <!-- Logo -->
                <a class="flex items-center" href="/Home/Homepage.php">
                    <img src="../assets/Logo/logo.png" alt="Logo" class="h-24 w-24 object-contain absolute left-0 top-0 z-50">
                </a>

                <!-- Search Bar -->
                <form class="relative w-[500px] mx-auto" role="search" action="/Search/globalsearchOutput.php" method="GET">
                    <input
                        id="search-input"
                        type="search"
                        name="query"
                        placeholder="Search your Recipe"
                        aria-label="Search"
                        required
                        autocomplete="off"
                        class="w-full px-4 py-2 pr-10 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400">

                    <!-- Suggestions -->
                    <div id="suggestions" class="absolute top-full left-0 w-full max-h-52 z-50 overflow-y-auto bg-white shadow rounded"></div>

                    <!-- Search Button inside input -->
                    <button
                        type="submit"
                        class="absolute right-2 top-1/2 -translate-y-1/2 text-gray-500 hover:text-green-600">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
                        </svg>
                    </button>
                </form>

                <!-- Login / Signup -->
                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])): ?>
                    <div class="ml-auto flex items-center space-x-2 absolute right-4">
                        <span> <a href="../Login-Signup/profile.php" class="text-black hover:text-green-600 text-sm font-semibold"><?php echo htmlspecialchars($name); ?></a></span>
                        <span>|</span>
                        <a href="../Login-Signup/logout.php" class="text-black hover:text-green-600 text-sm font-semibold">Logout</a>
                    </div>
                <?php else: ?>
                    <div class="ml-auto flex items-center space-x-2 absolute right-4">
                        <a href="../Login-Signup/login.php" class="text-black hover:text-green-600 text-sm font-semibold">Login</a>
                        <span>|</span>
                        <a href="../Login-Signup/signup.php" class="text-black hover:text-green-600 text-sm font-semibold">Sign Up</a>
                    </div>
                <?php endif; ?>
            </div>
        </nav>

        <!-- Main Navbar -->
        <div class="w-full bg-white border-b">
            <div class="w-full flex flex-wrap justify-center py-2">
                <ul class="flex space-x-6 items-center">
                    <li>
                        <a class="relative inline-block text-green-700 font-semibold" href="../Home/landing.php">Home</a>
                    </li>

                    <li>
                        <a class="relative inline-block text-gray-700 hover:text-green-700 font-semibold"
                            href="">Dashboard</a>
                    </li>
                    <li>
                        <a class="relative inline-block text-gray-700 hover:text-green-700 font-semibold"
                            href="">Projection</a>
                    </li>
                    <li>
                        <a class="relative inline-block text-gray-700 hover:text-green-700 font-semibold" href="#">Receipt</a>
                    </li>
                    <li>
                        <a class="relative inline-block text-gray-700 hover:text-green-700 font-semibold"
                            href="">Track Storage</a>
                    </li>
                    <li>
                        <a class="relative inline-block text-gray-700 hover:text-green-700 font-semibold"
                            href="">Order</a>
                    </li>
                    <li>
                        <a class="relative inline-block text-gray-700 hover:text-green-700 font-semibold"
                            href="">Saved</a>
                    </li>


                    <!-- Mega Menu Dropdown -->
                    <li class="relative" x-data="{ open: false }" @mouseenter="open = true" @mouseleave="open = false">
                        <a href="/Recipe Search/RecipeSearch.php"
                            class="relative inline-block flex items-center cursor-pointer font-semibold text-gray-700 hover:text-green-700"
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
                                                class="block px-8 py-1 rounded hover:bg-green-50 hover:text-green-700 transition-all duration-200 text-gray-700 text-[0.95rem]"
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
        </div>
    </header>
</body>

</html>