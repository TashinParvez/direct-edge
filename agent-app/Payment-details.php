<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment & Financial Handling</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">

    <div class="min-h-screen p-8 flex justify-center">

        <!-- Unified Card -->
        <div class="bg-white rounded-2xl p-6 shadow-sm border border-gray-200 w-full max-w-6xl space-y-6">

            <!-- Page Header -->
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-2xl font-semibold text-gray-800">Payment & Financial Records</h2>
                <button class="bg-green-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-green-700 transition">
                    Add Payment
                </button>
            </div>

            <!-- Filters / Search -->
            <div class="flex flex-col lg:flex-row gap-4 items-center mb-4">
                <input type="text" placeholder="Search by Farmer Name"
                    class="flex-1 p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:outline-none text-sm">
                <select class="p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:outline-none text-sm">
                    <option>All Status</option>
                    <option>Paid</option>
                    <option>Due</option>
                    <option>Overdue</option>
                </select>
                <input type="date"
                    class="p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:outline-none text-sm">
                <button class="bg-blue-600 text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-blue-700 transition">
                    Filter
                </button>
            </div>

            <!-- Financial Records Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Farmer</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Total Due</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Last Payment</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Payment Status</th>
                            <th class="px-4 py-2 text-left font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                            <th class="px-4 py-2 text-center font-medium text-gray-500 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">

                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2 text-gray-700">Rahim Uddin</td>
                            <td class="px-4 py-2 text-gray-700">৳ 5,000</td>
                            <td class="px-4 py-2 text-gray-700">2025-09-10</td>
                            <td class="px-4 py-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                    Due
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">Partial payment received</td>
                            <td class="px-4 py-2 text-center">
                                <button class="text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                            </td>
                        </tr>

                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2 text-gray-700">Abdul Karim</td>
                            <td class="px-4 py-2 text-gray-700">৳ 0</td>
                            <td class="px-4 py-2 text-gray-700">2025-09-15</td>
                            <td class="px-4 py-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                    Paid
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">Payment cleared</td>
                            <td class="px-4 py-2 text-center">
                                <button class="text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                            </td>
                        </tr>

                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-4 py-2 text-gray-700">Jashim Mia</td>
                            <td class="px-4 py-2 text-gray-700">৳ 3,500</td>
                            <td class="px-4 py-2 text-gray-700">2025-09-05</td>
                            <td class="px-4 py-2">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                    Overdue
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-700">Payment overdue by 10 days</td>
                            <td class="px-4 py-2 text-center">
                                <button class="text-blue-600 hover:text-blue-800 font-medium">Edit</button>
                            </td>
                        </tr>

                    </tbody>
                </table>
            </div>

        </div>

    </div>

</body>

</html>