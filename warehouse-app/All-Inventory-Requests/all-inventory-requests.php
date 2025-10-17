<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarWarehouse.php'; ?>

<?php
include '../../include/connect-db.php'; // database connection
$admin_id = isset($user_id) ? $user_id : 65;

// Handle status update (Accept/Reject/Toggle Working-Pending)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $requestId = (int)$_POST['request_id'];

    $allowed = ['Accept', 'Reject', 'SetWorking', 'SetPending'];
    if (in_array($action, $allowed, true) && $requestId > 0) {
        if ($action === 'Accept')      $newStatus = 'Done';
        elseif ($action === 'Reject')  $newStatus = 'Rejected';
        elseif ($action === 'SetWorking') $newStatus = 'Working';
        else /* SetPending */             $newStatus = 'Pending';

        $stmt = $conn->prepare("UPDATE stock_requests SET status = ?, updated_at = NOW() WHERE request_id = ?");
        $stmt->bind_param('si', $newStatus, $requestId);
        $ok = $stmt->execute();

        header('Content-Type: application/json');
        echo json_encode(['success' => $ok, 'status' => $newStatus]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

/*
 Compute requiredSpace from products.unit_space * sr.quantity.
 If products uses imgurl instead of img_url, switch below.
*/
$sql = "SELECT 
    sr.request_id AS id,
    p.name,
    p.category,
    ROUND(p.unit_space * sr.quantity, 3) AS requiredSpace,
    p.img_url AS image,
    sr.quantity,
    sr.requested_at AS requested_at,
    sr.updated_at AS updated_at,
    sr.status,
    sr.note AS notes,
    u.full_name AS requester_name
FROM stock_requests sr
JOIN products p ON sr.product_id = p.product_id
JOIN users u ON sr.requester_id = u.user_id
WHERE sr.status <> 'Done'
ORDER BY sr.requested_at ASC";  // oldest first (First Come, First Serve)

$result = $conn->query($sql);
$requests = [];
while ($row = $result->fetch_assoc()) {
    $row['requiredSpace'] = $row['requiredSpace'] === null ? null : (float)$row['requiredSpace'];
    $requests[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Inventory Requests - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../../assets/Logo/LogoBG.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .request-card {
            transition: all 0.3s ease;
        }

        .request-card:hover {
            background-color: #f8fafc;
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
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

        .modal {
            animation: fadeIn 0.3s ease-out;
            backdrop-filter: blur(4px);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-content {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-30px) scale(0.95);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .requests-grid {
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-enter {
            animation: pageEnter 0.5s ease-out;
        }

        @keyframes pageEnter {
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
        }
    </style>
</head>

<body class="bg-gray-100 page-enter">

    <section class="home-section p-0">
        <div class="flex justify-between items-center p-6 bg-white shadow-sm border-b">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Inventory Requests</h1>
                <p class="text-gray-600 mt-1">Review and manage stock requests from agents</p>
            </div>
            <div class="flex space-x-3 no-print">
                <button onclick="window.print()"
                    class="bg-gray-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-gray-600 transition-colors">
                    <i class='bx bx-printer mr-2'></i>Print Report
                </button>
                <button onclick="location.reload()"
                    class="bg-blue-500 text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                    <i class='bx bx-refresh mr-2'></i>Refresh
                </button>
            </div>
        </div>

        <div class="container mx-auto px-6 py-6">
            <div class="flex flex-col lg:flex-row gap-6">

                <!-- Left Sidebar: Filters -->
                <div class="w-full lg:w-80 space-y-6">

                    <!-- Search Section -->
                    <div class="bg-white shadow-lg rounded-xl p-6 filter-container border border-gray-100">
                        <div class="flex items-center mb-4">
                            <i class='bx bx-search text-xl text-blue-600 mr-2'></i>
                            <h3 class="text-lg font-semibold text-gray-900">Search</h3>
                        </div>
                        <div class="relative">
                            <input type="search" id="search-input" placeholder="Search products..."
                                class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            <i
                                class='bx bx-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400'></i>
                        </div>
                    </div>

                    <!-- Volume Filters -->
                    <div class="bg-white shadow-lg rounded-xl p-6 filter-container border border-gray-100">
                        <div class="flex items-center mb-4">
                            <i class='bx bx-cube text-xl text-green-600 mr-2'></i>
                            <h3 class="text-lg font-semibold text-gray-900">Volume Filters</h3>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Volume (m³)</label>
                                <input type="number" id="min-volume" step="0.001" placeholder="0.000"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Volume (m³)</label>
                                <input type="number" id="max-volume" step="0.001" placeholder="No limit"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            </div>
                        </div>
                    </div>

                    <!-- Date Filters -->
                    <div class="bg-white shadow-lg rounded-xl p-6 filter-container border border-gray-100">
                        <div class="flex items-center mb-4">
                            <i class='bx bx-calendar text-xl text-purple-600 mr-2'></i>
                            <h3 class="text-lg font-semibold text-gray-900">Date Filters</h3>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <input type="date" id="start-date"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 search-field">
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="radio" name="start-date-option" value="before" class="mr-2">
                                    <span class="text-sm text-gray-700">Before this date</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="start-date-option" value="after" class="mr-2">
                                    <span class="text-sm text-gray-700">After this date</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Categories -->
                    <div class="bg-white shadow-lg rounded-xl p-6 filter-container border border-gray-100">
                        <div class="flex items-center mb-4">
                            <i class='bx bx-category text-xl text-orange-600 mr-2'></i>
                            <h3 class="text-lg font-semibold text-gray-900">Categories</h3>
                        </div>
                        <div class="space-y-3 max-h-64 overflow-y-auto">
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Fresh Produce"> Fresh Produce</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Dairy & Eggs"> Dairy & Eggs</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Meat & Poultry"> Meat & Poultry</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Seafood"> Seafood</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Bakery"> Bakery</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Frozen Foods"> Frozen Foods</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Canned Goods"> Canned Goods</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Snacks & Sweets"> Snacks & Sweets</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Beverages"> Beverages</label>
                            <label class="flex items-center"><input type="checkbox" class="category-checkbox mr-3"
                                    value="Household Essentials"> Household Essentials</label>
                        </div>
                    </div>
                </div>

                <!-- Right Side: Main Content -->
                <div class="flex-1">

                    <!-- Controls Header -->
                    <div class="bg-white shadow-md rounded-lg p-6 mb-6 border border-gray-100">
                        <div
                            class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                            <div>
                                <h2 id="requests-header" class="text-xl font-semibold text-gray-900">All Requests</h2>
                                <p class="text-sm text-gray-600">Manage pending inventory requests</p>
                            </div>
                            <div class="flex flex-col sm:flex-row space-y-2 sm:space-y-0 sm:space-x-4">
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">Show:</span>
                                    <select id="per-page"
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                        <option value="10" selected>10</option>
                                        <option value="20">20</option>
                                        <option value="50">50</option>
                                        <option value="100">100</option>
                                        <option value="all">Display All</option>
                                    </select>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="text-sm font-medium text-gray-700">Order by:</span>
                                    <select id="sort-by"
                                        class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 text-sm">
                                        <option value="request-order" selected>Request Order (FCFS)</option>
                                        <option value="volume-high-low">Volume (High to Low)</option>
                                        <option value="volume-low-high">Volume (Low to High)</option>
                                        <option value="a-z">A-Z</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Requests Grid -->
                    <div class="products-container grid grid-cols-1 md:grid-cols-2 gap-6 mb-6 requests-grid">
                        <!-- Cards will be rendered here by JS -->
                    </div>

                    <!-- Pagination -->
                    <div class="bg-white shadow-md rounded-lg p-4 border border-gray-100">
                        <div class="flex justify-center items-center space-x-2">
                            <button id="first-page"
                                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                                <i class='bx bx-chevrons-left'></i>
                            </button>
                            <button id="prev-page"
                                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                                <i class='bx bx-chevron-left'></i>
                            </button>
                            <span id="page-info" class="px-4 py-2 font-medium">1 / 1</span>
                            <button id="next-page"
                                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                                <i class='bx bx-chevron-right'></i>
                            </button>
                            <button id="last-page"
                                class="bg-gray-200 hover:bg-gray-300 px-4 py-2 rounded-lg transition-colors">
                                <i class='bx bx-chevrons-right'></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Modal -->
    <div id="requestModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 modal">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl w-full max-w-4xl modal-content border border-gray-200">

                <!-- Modal Header -->
                <div
                    class="flex justify-between items-center p-6 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-indigo-50">
                    <div class="flex items-center space-x-3">
                        <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center">
                            <i class='bx bx-package text-2xl text-blue-600'></i>
                        </div>
                        <div>
                            <h2 id="modalHeading" class="text-2xl font-bold text-gray-900">Inventory Request</h2>
                            <p id="modalSub" class="text-gray-600">Request Details</p>
                        </div>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span id="modalStatusBadge" class="px-3 py-1 rounded-full text-xs font-semibold"></span>
                        <button class="close-modal text-gray-400 hover:text-gray-600 text-3xl transition-colors">
                            <i class='bx bx-x'></i>
                        </button>
                    </div>
                </div>

                <!-- Modal Content -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

                        <!-- Product Image -->
                        <div class="space-y-4">
                            <div class="w-full h-64 bg-gray-100 rounded-lg overflow-hidden">
                                <img id="modalImage" src="" alt="Product Thumbnail" class="w-full h-full object-cover">
                            </div>
                        </div>

                        <!-- Product Details -->
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Requested By</label>
                                    <div class="flex items-center space-x-2">
                                        <i class='bx bx-user text-gray-400'></i>
                                        <span id="modalRequester" class="text-gray-900 font-medium">-</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Requested At</label>
                                    <div class="flex items-center space-x-2">
                                        <i class='bx bx-calendar text-gray-400'></i>
                                        <span id="modalRequestedAt" class="text-gray-900 font-medium">-</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Product Name</label>
                                    <div class="flex items-center space-x-2">
                                        <i class='bx bx-package text-gray-400'></i>
                                        <span id="modalName" class="text-gray-900 font-medium">-</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Category</label>
                                    <div class="flex items-center space-x-2">
                                        <i class='bx bx-category text-gray-400'></i>
                                        <span id="modalCategory" class="text-gray-900 font-medium">-</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Quantity</label>
                                    <div class="flex items-center space-x-2">
                                        <i class='bx bx-calculator text-gray-400'></i>
                                        <span id="modalQuantity" class="text-gray-900 font-medium">-</span>
                                    </div>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Updated At</label>
                                    <div class="flex items-center space-x-2">
                                        <i class='bx bx-time text-gray-400'></i>
                                        <span id="modalUpdatedAt" class="text-gray-900 font-medium">-</span>
                                    </div>
                                </div>
                            </div>

                            <!-- In Progress Toggle -->
                            <div>
                                <label class="flex items-center space-x-3 cursor-pointer">
                                    <input type="checkbox" id="inProgressToggle" class="w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                                    <span class="text-sm font-medium text-gray-700">Mark as In Progress</span>
                                </label>
                            </div>

                            <!-- Notes Section -->
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-2">Notes</label>
                                <div class="bg-gray-50 rounded-lg p-4">
                                    <div class="flex items-start space-x-2">
                                        <i class='bx bx-note text-gray-400 mt-1'></i>
                                        <span id="modalNotes" class="text-gray-900">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="flex justify-end space-x-3 p-6 border-t border-gray-200 bg-gray-50">
                    <button
                        class="reject bg-red-500 text-white px-6 py-3 rounded-lg hover:bg-red-600 transition-colors font-medium">
                        <i class='bx bx-x mr-2'></i>Reject
                    </button>
                    <button
                        class="accept bg-green-500 text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-colors font-medium">
                        <i class='bx bx-check mr-2'></i>Accept
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Data from PHP
        const items = <?php echo json_encode($requests); ?>;

        // Ensure default sort is "request-order"
        document.addEventListener('DOMContentLoaded', () => {
            const sortSel = document.getElementById('sort-by');
            sortSel.value = 'request-order';
            renderCards();
        });

        // Event listeners for filters
        const filterElements = [
            document.getElementById('min-volume'),
            document.getElementById('max-volume'),
            document.getElementById('start-date'),
            ...document.querySelectorAll('input[name="start-date-option"]'),
            ...document.querySelectorAll('.category-checkbox'),
            document.getElementById('per-page'),
            document.getElementById('sort-by'),
            document.getElementById('search-input')
        ];

        filterElements.forEach(el => {
            const eventType = el.type === 'radio' || el.type === 'checkbox' ? 'change' : 'input';
            el.addEventListener(eventType, renderCards);
        });

        // Pagination
        document.getElementById('first-page').addEventListener('click', () => {
            currentPage = 0;
            renderCards();
        });
        document.getElementById('prev-page').addEventListener('click', () => {
            if (currentPage > 0) currentPage--;
            renderCards();
        });
        document.getElementById('next-page').addEventListener('click', () => {
            if (currentPage < totalPages - 1) currentPage++;
            renderCards();
        });
        document.getElementById('last-page').addEventListener('click', () => {
            currentPage = totalPages - 1;
            renderCards();
        });

        // Modal
        const modal = document.getElementById('requestModal');
        const closeModalBtn = document.querySelector('.close-modal');
        closeModalBtn.addEventListener('click', () => {
            modal.classList.add('hidden');
        });
        window.addEventListener('click', (event) => {
            if (event.target === modal) {
                modal.classList.add('hidden');
            }
        });

        let selectedRequestId = null;
        let currentPage = 0;
        let totalPages = 1;

        function renderCards() {
            const minVolume = parseFloat(document.getElementById('min-volume').value) || 0;
            const maxVolume = parseFloat(document.getElementById('max-volume').value) || Infinity;
            const startDate = document.getElementById('start-date').value;
            const startOption = document.querySelector('input[name="start-date-option"]:checked')?.value;
            const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb
                .value);
            const searchQuery = document.getElementById('search-input').value.toLowerCase();
            const sortValue = document.getElementById('sort-by').value;
            const perPage = document.getElementById('per-page').value === 'all' ? items.length : parseInt(document
                .getElementById('per-page').value, 10);

            let filtered = items.filter(item => {
                if (typeof item.requiredSpace === 'number') {
                    if (item.requiredSpace < minVolume || item.requiredSpace > maxVolume) return false;
                }
                if (selectedCategories.length > 0 && !selectedCategories.includes(item.category)) return false;
                if (searchQuery && !String(item.name).toLowerCase().includes(searchQuery)) return false;

                if (startDate && startOption) {
                    const itemStart = new Date(item.requested_at);
                    const selected = new Date(startDate);
                    if (startOption === 'before' && itemStart >= selected) return false;
                    if (startOption === 'after' && itemStart <= selected) return false;
                }
                return true;
            });

            // Sort
            if (sortValue === 'volume-high-low') {
                filtered.sort((a, b) => (b.requiredSpace ?? 0) - (a.requiredSpace ?? 0));
            } else if (sortValue === 'volume-low-high') {
                filtered.sort((a, b) => (a.requiredSpace ?? 0) - (b.requiredSpace ?? 0));
            } else if (sortValue === 'a-z') {
                filtered.sort((a, b) => a.name.localeCompare(b.name));
            } else if (sortValue === 'request-order') {
                // First placed first: ascending by requested_at
                filtered.sort((a, b) => new Date(a.requested_at) - new Date(b.requested_at));
            }

            document.getElementById('requests-header').innerText = filtered.length === items.length ? 'All Requests' :
                'Filtered Requests';

            // Pagination
            totalPages = Math.ceil(filtered.length / perPage);
            if (currentPage > totalPages - 1) currentPage = 0;
            const start = currentPage * perPage;
            const pageItems = filtered.slice(start, start + perPage);

            const container = document.querySelector('.products-container');
            container.innerHTML = '';

            if (pageItems.length === 0) {
                container.innerHTML = `
                    <div class="col-span-full flex flex-col items-center justify-center py-16 text-center">
                        <i class='bx bx-package text-6xl text-gray-300 mb-4'></i>
                        <h3 class="text-xl font-semibold text-gray-600 mb-2">No requests found</h3>
                        <p class="text-gray-500">Try adjusting your search criteria or filters</p>
                    </div>
                `;
            } else {
                pageItems.forEach(item => {
                    const statusKey = String(item.status || '').toLowerCase();
                    const reqSpaceText = (typeof item.requiredSpace === 'number') ?
                        item.requiredSpace.toFixed(3) + ' m³' :
                        'N/A';

                    const statusClasses = {
                        'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                        'rejected': 'bg-red-100 text-red-800 border border-red-200',
                        'working': 'bg-blue-100 text-blue-800 border border-blue-200',
                        'done': 'bg-green-100 text-green-800 border border-green-200'
                    };

                    const card = document.createElement('div');
                    card.classList.add('bg-white', 'rounded-xl', 'shadow-md', 'hover:shadow-xl', 'overflow-hidden',
                        'request-card', 'border', 'border-gray-100');
                    card.innerHTML = `
                        <div class="p-6">
                            <div class="flex justify-between items-start mb-4">
                                <h3 class="text-lg font-bold text-gray-900">${item.name}</h3>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold ${statusClasses[statusKey] || 'bg-gray-100 text-gray-800 border border-gray-200'}">
                                    ${item.status}
                                </span>
                            </div>
                            
                            <div class="grid grid-cols-2 gap-4 text-sm text-gray-600 mb-4">
                                <div class="flex items-center">
                                    <i class='bx bx-category mr-2 text-gray-400'></i>
                                    <span>${item.category || 'N/A'}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class='bx bx-cube mr-2 text-gray-400'></i>
                                    <span>${reqSpaceText}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class='bx bx-calculator mr-2 text-gray-400'></i>
                                    <span>Qty: ${item.quantity}</span>
                                </div>
                                <div class="flex items-center">
                                    <i class='bx bx-calendar mr-2 text-gray-400'></i>
                                    <span>${new Date(item.requested_at).toLocaleDateString()}</span>
                                </div>
                            </div>
                            
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-gray-500">
                                    <i class='bx bx-user mr-1'></i>
                                    By: ${item.requester_name}
                                </div>
                                <button class="view-details-btn bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors text-sm font-medium" data-id="${item.id}">
                                    <i class='bx bx-show mr-1'></i>View Details
                                </button>
                            </div>
                        </div>
                    `;
                    container.appendChild(card);
                });
            }

            // View details
            document.querySelectorAll('.view-details-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    const itemId = parseInt(btn.getAttribute('data-id'));
                    const item = items.find(i => i.id == itemId);
                    if (item) {
                        selectedRequestId = item.id;

                        // Populate modal
                        const reqSpaceText = (typeof item.requiredSpace === 'number') ?
                            item.requiredSpace.toFixed(3) + ' m³' :
                            'N/A';

                        document.getElementById('modalHeading').textContent = `Inventory Request for ${reqSpaceText}`;
                        document.getElementById('modalSub').textContent = `Request #${item.id}`;
                        document.getElementById('modalImage').src = `../../${item.image}`;

                        const badge = document.getElementById('modalStatusBadge');
                        const sClass = String(item.status || '').toLowerCase();
                        const statusClasses = {
                            'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                            'rejected': 'bg-red-100 text-red-800 border border-red-200',
                            'working': 'bg-blue-100 text-blue-800 border border-blue-200',
                            'done': 'bg-green-100 text-green-800 border border-green-200'
                        };
                        badge.className = `px-3 py-1 rounded-full text-xs font-semibold ${statusClasses[sClass] || 'bg-gray-100 text-gray-800'}`;
                        badge.textContent = item.status ?? '-';

                        document.getElementById('modalRequester').textContent = item.requester_name ?? '-';
                        document.getElementById('modalRequestedAt').textContent = item.requested_at ?? '-';
                        document.getElementById('modalName').textContent = item.name ?? '-';
                        document.getElementById('modalCategory').textContent = item.category ?? '-';
                        document.getElementById('modalQuantity').textContent = item.quantity ?? '-';
                        document.getElementById('modalNotes').textContent = item.notes ?? '-';
                        document.getElementById('modalUpdatedAt').textContent = item.updated_at ?? '-';

                        // Set the "In Progress" checkbox based on current status
                        const inProgress = document.getElementById('inProgressToggle');
                        inProgress.checked = (String(item.status).toLowerCase() === 'working');

                        modal.classList.remove('hidden');

                        // Bind once: toggle Working/Pending when checkbox changes
                        if (!inProgress._bound) {
                            inProgress._bound = true;
                            inProgress.addEventListener('change', async () => {
                                if (!selectedRequestId) return;
                                
                                const make = inProgress.checked ? 'SetWorking' : 'SetPending';
                                const newStatusText = inProgress.checked ? 'Working' : 'Pending';
                                const cls = inProgress.checked ? 'working' : 'pending';
                                
                                // Update badge immediately for instant feedback
                                const statusClasses = {
                                    'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                                    'rejected': 'bg-red-100 text-red-800 border border-red-200',
                                    'working': 'bg-blue-100 text-blue-800 border border-blue-200',
                                    'done': 'bg-green-100 text-green-800 border border-green-200'
                                };
                                badge.className = `px-3 py-1 rounded-full text-xs font-semibold ${statusClasses[cls]}`;
                                badge.textContent = newStatusText;
                                
                                // Update local state
                                const idx = items.findIndex(i => i.id == selectedRequestId);
                                if (idx > -1) items[idx].status = newStatusText;
                                
                                // Send update to server (no need to wait or check response for UI update)
                                updateRequestStatus(make, selectedRequestId, false);
                            });
                        }
                    }
                });
            });

            document.getElementById('page-info').innerText = `${totalPages > 0 ? currentPage + 1 : 0} / ${totalPages || 1}`;
            document.getElementById('first-page').disabled = currentPage === 0;
            document.getElementById('prev-page').disabled = currentPage === 0;
            document.getElementById('next-page').disabled = currentPage >= totalPages - 1;
            document.getElementById('last-page').disabled = currentPage >= totalPages - 1;
        }

        // Handle accept/reject buttons (outside renderCards)
        document.querySelector('.accept').addEventListener('click', async () => {
            if (!selectedRequestId) return;
            
            // Update badge immediately for visual feedback
            const badge = document.getElementById('modalStatusBadge');
            const statusClasses = {
                'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                'rejected': 'bg-red-100 text-red-800 border border-red-200',
                'working': 'bg-blue-100 text-blue-800 border border-blue-200',
                'done': 'bg-green-100 text-green-800 border border-green-200'
            };
            badge.className = `px-3 py-1 rounded-full text-xs font-semibold ${statusClasses['done']}`;
            badge.textContent = 'Done';
            
            await updateRequestStatus('Accept', selectedRequestId, true);
        });

        document.querySelector('.reject').addEventListener('click', async () => {
            if (!selectedRequestId) return;
            
            // Update badge immediately for visual feedback
            const badge = document.getElementById('modalStatusBadge');
            const statusClasses = {
                'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                'rejected': 'bg-red-100 text-red-800 border border-red-200',
                'working': 'bg-blue-100 text-blue-800 border border-blue-200',
                'done': 'bg-green-100 text-green-800 border border-green-200'
            };
            badge.className = `px-3 py-1 rounded-full text-xs font-semibold ${statusClasses['rejected']}`;
            badge.textContent = 'Rejected';
            
            await updateRequestStatus('Reject', selectedRequestId, true);
        });

        // action: 'Accept' | 'Reject' | 'SetWorking' | 'SetPending'
        // closeModal: whether to close modal after update
        async function updateRequestStatus(action, requestId, closeModal = false) {
            try {
                const formData = new FormData();
                formData.append('action', action);
                formData.append('request_id', requestId);

                const res = await fetch(location.href, {
                    method: 'POST',
                    body: formData
                });
                const data = await res.json();
                if (data && data.success) {
                    const idx = items.findIndex(i => i.id == requestId);
                    const newStatus = data.status ?? (action === 'Accept' ? 'Done' : action === 'Reject' ? 'Rejected' : (action === 'SetWorking' ? 'Working' : 'Pending'));

                    if (idx > -1) {
                        // If status is Done, remove from items array (since page only shows non-Done requests)
                        if (newStatus === 'Done') {
                            items.splice(idx, 1);
                        } else {
                            items[idx].status = newStatus;
                        }
                    }

                    // Update modal badge if modal is open
                    if (!closeModal && idx > -1 && newStatus !== 'Done') {
                        const badge = document.getElementById('modalStatusBadge');
                        const sClass = String(newStatus || '').toLowerCase();
                        const statusClasses = {
                            'pending': 'bg-yellow-100 text-yellow-800 border border-yellow-200',
                            'rejected': 'bg-red-100 text-red-800 border border-red-200',
                            'working': 'bg-blue-100 text-blue-800 border border-blue-200',
                            'done': 'bg-green-100 text-green-800 border border-green-200'
                        };
                        badge.className = `px-3 py-1 rounded-full text-xs font-semibold ${statusClasses[sClass]}`;
                        badge.textContent = newStatus;
                    }

                    if (closeModal) {
                        document.getElementById('requestModal').classList.add('hidden');
                    }
                    renderCards();
                    return true;
                }
            } catch (e) {
                console.error('Error:', e);
            }
            return false;
        }
    </script>
</body>

</html>