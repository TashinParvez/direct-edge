<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Payment & Financial Handling - Stock Integrated</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .financial-container {
            transition: all 0.3s ease;
        }

        .financial-container:hover {
            background-color: #f9fafb;
        }

        .table-row {
            transition: all 0.2s ease;
        }

        .table-row:hover {
            background-color: #f3f4f6;
            transform: translateY(-1px);
        }

        .filter-container {
            transition: all 0.3s ease;
        }

        .filter-container:hover {
            background-color: #f9fafb;
        }

        .search-field {
            transition: all 0.3s ease;
        }

        .search-field:hover {
            background-color: #f3f4f6;
        }

        .search-field:focus {
            background-color: #ffffff;
            border-color: #10b981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }

        .status-badge {
            transition: all 0.2s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
        }

        .action-btn {
            transition: all 0.2s ease;
        }

        .action-btn:hover {
            transform: translateY(-1px);
        }

        .table-container {
            animation: fadeIn 0.6s ease-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .table-row:hover {
                background-color: transparent !important;
                transform: none !important;
            }
        }
    </style>

    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-100">
    <?php include '../Include/navbar.php'; ?>

    <section class="home-section p-0 pb-4 mx-2 md:mx-8 lg:mx-16">
        <div class="flex justify-between items-center p-4 no-print">
            <h1 class="text-2xl font-bold">Payment & Financial Records</h1>
            <div class="flex space-x-2">
                <button onclick="window.print()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600 no-print">
                    <i class='bx bx-printer'></i> Print
                </button>
                <button class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class='bx bx-plus'></i> Add Payment
                </button>
            </div>
        </div>

        <div class="container mx-auto px-4">

            <!-- Main Financial Records Card -->
            <div class="bg-white shadow-lg rounded-lg p-6 financial-container">

                <!-- Filters / Search Section -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6 filter-container">
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 items-end">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-search mr-1'></i>Search Farmer
                            </label>
                            <input type="text" placeholder="Search by farmer name..."
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-filter mr-1'></i>Payment Status
                            </label>
                            <select class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                                <option>All Status</option>
                                <option>Paid</option>
                                <option>Due</option>
                                <option>Overdue</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                <i class='bx bx-calendar mr-1'></i>Date Range
                            </label>
                            <input type="date"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                        </div>

                        <div>
                            <button class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition-colors w-full">
                                <i class='bx bx-filter-alt mr-2'></i>Apply Filter
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Financial Records Table -->
                <div class="overflow-x-auto table-container">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-user mr-1'></i>Farmer
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-money mr-1'></i>Total Due
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-calendar-check mr-1'></i>Last Payment
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-check-circle mr-1'></i>Status
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    <i class='bx bx-note mr-1'></i>Notes
                                </th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider no-print">
                                    <i class='bx bx-cog mr-1'></i>Action
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">

                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class='bx bx-user text-green-600'></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Rahim Uddin</div>
                                            <div class="text-sm text-gray-500">ID: #F001</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-red-600">৳ 5,000</div>
                                    <div class="text-sm text-gray-500">Due Amount</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">2025-09-10</div>
                                    <div class="text-sm text-gray-500">15 days ago</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800 status-badge">
                                        <i class='bx bx-time-five mr-1'></i>Due
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">Partial payment received</div>
                                    <div class="text-sm text-gray-500">Contact: 01712345678</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium no-print">
                                    <div class="flex justify-center space-x-3">
                                        <button class="text-blue-600 hover:text-blue-900 action-btn" title="Edit Payment">
                                            <i class='bx bx-edit text-lg'></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-900 action-btn" title="Add Payment">
                                            <i class='bx bx-money text-lg'></i>
                                        </button>
                                        <button class="text-gray-600 hover:text-gray-900 action-btn" title="View Details">
                                            <i class='bx bx-show text-lg'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class='bx bx-user text-green-600'></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Abdul Karim</div>
                                            <div class="text-sm text-gray-500">ID: #F002</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-green-600">৳ 0</div>
                                    <div class="text-sm text-gray-500">Cleared</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">2025-09-15</div>
                                    <div class="text-sm text-gray-500">10 days ago</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800 status-badge">
                                        <i class='bx bx-check mr-1'></i>Paid
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">Payment cleared</div>
                                    <div class="text-sm text-gray-500">Contact: 01987654321</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium no-print">
                                    <div class="flex justify-center space-x-3">
                                        <button class="text-blue-600 hover:text-blue-900 action-btn" title="Edit Payment">
                                            <i class='bx bx-edit text-lg'></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-900 action-btn" title="Add Payment">
                                            <i class='bx bx-money text-lg'></i>
                                        </button>
                                        <button class="text-gray-600 hover:text-gray-900 action-btn" title="View Details">
                                            <i class='bx bx-show text-lg'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <tr class="table-row">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class='bx bx-user text-green-600'></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">Jashim Mia</div>
                                            <div class="text-sm text-gray-500">ID: #F003</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-semibold text-red-600">৳ 3,500</div>
                                    <div class="text-sm text-gray-500">Overdue</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">2025-09-05</div>
                                    <div class="text-sm text-gray-500">20 days ago</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800 status-badge">
                                        <i class='bx bx-error-circle mr-1'></i>Overdue
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm text-gray-900">Payment overdue by 10 days</div>
                                    <div class="text-sm text-gray-500">Contact: 01555666777</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium no-print">
                                    <div class="flex justify-center space-x-3">
                                        <button class="text-blue-600 hover:text-blue-900 action-btn" title="Edit Payment">
                                            <i class='bx bx-edit text-lg'></i>
                                        </button>
                                        <button class="text-green-600 hover:text-green-900 action-btn" title="Add Payment">
                                            <i class='bx bx-money text-lg'></i>
                                        </button>
                                        <button class="text-gray-600 hover:text-gray-900 action-btn" title="View Details">
                                            <i class='bx bx-show text-lg'></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </section>

    <?php include '../include/footer.php'; ?>
</body>

</html>