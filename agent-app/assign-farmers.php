<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Assign Farmers</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50 font-sans">

    <!-- Header Section -->
    <header class="relative w-full h-64">
        <img src="farm.jpg"
            alt="Farm Field"
            class="w-full h-full object-cover">
        <div class="absolute inset-0 bg-black bg-opacity-40 flex items-center justify-center">
            <h1 class="text-white text-4xl font-bold">Assign Farmers</h1>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto py-10 px-6">
        <div class="grid md:grid-cols-2 gap-8">

            <!-- Assign Farmers Form -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Add New Farmer</h2>
                <form action="#" method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Farmer Name</label>
                        <input type="text" placeholder="Enter farmer name"
                            class="w-full mt-1 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Location</label>
                        <input type="text" placeholder="Village / District"
                            class="w-full mt-1 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:outline-none">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-600">Contact Number</label>
                        <input type="tel" placeholder="e.g. +8801XXXXXXXXX"
                            class="w-full mt-1 p-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-green-500 focus:outline-none">
                    </div>
                    <button type="submit"
                        class="w-full bg-green-600 text-white py-3 rounded-xl font-semibold hover:bg-green-700 transition">
                        Assign Farmer
                    </button>
                </form>
            </div>

            <!-- Assigned Farmers List -->
            <div class="bg-white rounded-2xl shadow-lg p-6">
                <h2 class="text-2xl font-semibold mb-4 text-gray-800">Assigned Farmers</h2>
                <ul class="divide-y divide-gray-200">
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-gray-700">Rahim Uddin</p>
                            <p class="text-sm text-gray-500">Bogura, +8801789XXXXXX</p>
                        </div>
                        <button class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                    </li>
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-gray-700">Abdul Karim</p>
                            <p class="text-sm text-gray-500">Rangpur, +8801567XXXXXX</p>
                        </div>
                        <button class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                    </li>
                    <li class="flex items-center justify-between py-3">
                        <div>
                            <p class="font-medium text-gray-700">Jashim Mia</p>
                            <p class="text-sm text-gray-500">Sylhet, +8801934XXXXXX</p>
                        </div>
                        <button class="text-red-500 hover:text-red-700 font-medium">Remove</button>
                    </li>
                </ul>
            </div>

        </div>
    </main>

</body>

</html>