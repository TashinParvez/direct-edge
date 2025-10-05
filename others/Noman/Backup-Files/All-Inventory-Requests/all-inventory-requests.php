<?php
include '../../include/connect-db.php'; // database connection

// Handle status update (Accept/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    $action = $_POST['action'];
    $requestId = (int)$_POST['request_id'];
    if (in_array($action, ['Accept', 'Reject'], true) && $requestId > 0) {
        $newStatus = $action === 'Accept' ? 'Done' : 'Rejected';
        $stmt = $conn->prepare("UPDATE stock_requests SET status = ?, updated_at = NOW() WHERE request_id = ?");
        $stmt->bind_param('si', $newStatus, $requestId);
        $ok = $stmt->execute();
        header('Content-Type: application/json');
        echo json_encode(['success' => $ok]);
        exit;
    }
    header('Content-Type: application/json');
    echo json_encode(['success' => false]);
    exit;
}

// Load requests: single product_id per request; include required_space from stock_requests
$sql = "SELECT 
    sr.request_id AS id,
    p.name,
    p.category,
    NULL AS requiredSpace,              -- force NULL like Query 1
    p.img_url AS image,                 -- change to p.imgurl if your schema uses that
    sr.quantity,
    sr.requested_at AS requested_at,
    sr.updated_at AS updated_at,
    sr.status,
    sr.note AS notes,                   -- note (singular)
    u.full_name AS requester_name
FROM stock_requests sr
JOIN products p ON sr.product_id = p.product_id
JOIN users u ON sr.requester_id = u.user_id
WHERE sr.status <> 'Done'
ORDER BY sr.requested_at DESC;
";

$result = $conn->query($sql);
$requests = [];
while ($row = $result->fetch_assoc()) {
    $requests[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Requests</title>
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <link rel="stylesheet" href="all-inventory-requests.css">

</head>

<body>
    <?php // include '../direct-edge/include/Sidebar.php'; 
    ?>
    <section>
        <h1>Inventory Requests</h1>

        <!-- Search bar -->
        <div class="search-bar">
            <div class="search-container">
                <input type="search" id="search-input" placeholder="Search products...">
                <i class='bx bx-search search-icon'></i>
            </div>
        </div>

        <div class="main-container">
            <!-- Filter column -->
            <div class="filter-column">
                <!-- Volume -->
                <div class="filter-group">
                    <label><span>Volume more than:</span> <input type="number" id="min-volume" step="0.1"><span>m³</span></label>
                    <label><span>Volume less than:</span> <input type="number" id="max-volume" step="0.1"><span>m³</span></label>
                </div>

                <!-- Start Date -->
                <div class="filter-group">
                    <h3>Start Date</h3>
                    <input type="date" id="start-date">
                    <div class="date-options">
                        <label><input type="radio" name="start-date-option" value="before"> Before this date</label>
                        <label><input type="radio" name="start-date-option" value="after"> After this date</label>
                    </div>
                </div>

                <!-- Categories -->
                <div class="filter-group">
                    <h3>Categories</h3>
                    <label><input type="checkbox" class="category-checkbox" value="Fresh Produce"> Fresh Produce</label>
                    <label><input type="checkbox" class="category-checkbox" value="Dairy & Eggs"> Dairy & Eggs</label>
                    <label><input type="checkbox" class="category-checkbox" value="Meat & Poultry"> Meat & Poultry</label>
                    <label><input type="checkbox" class="category-checkbox" value="Seafood"> Seafood</label>
                    <label><input type="checkbox" class="category-checkbox" value="Bakery"> Bakery</label>
                    <label><input type="checkbox" class="category-checkbox" value="Frozen Foods"> Frozen Foods</label>
                    <label><input type="checkbox" class="category-checkbox" value="Canned Goods"> Canned Goods</label>
                    <label><input type="checkbox" class="category-checkbox" value="Snacks & Sweets"> Snacks & Sweets</label>
                    <label><input type="checkbox" class="category-checkbox" value="Beverages"> Beverages</label>
                    <label><input type="checkbox" class="category-checkbox" value="Household Essentials"> Household Essentials</label>
                </div>
            </div>

            <!-- Cards column -->
            <div class="cards-column">
                <!-- Header -->
                <div class="all-products-header">
                    <h2 id="requests-header">All Requests</h2>
                    <div>
                        <span class="label-text">Show:</span>
                        <select class="dropdown" id="per-page">
                            <option value="10" selected>10</option>
                            <option value="20">20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                            <option value="all">Display All</option>
                        </select>
                        <span class="label-text">Order by:</span>
                        <select class="dropdown" id="sort-by">
                            <option value="request-order" selected>Request Order (First Come, First Serve)</option>
                            <option value="volume-high-low">Volume (Highest to Lowest)</option>
                            <option value="volume-low-high">Volume (Lowest to Highest)</option>
                            <option value="a-z">A-Z</option>
                        </select>
                    </div>
                </div>

                <!-- Cards container -->
                <div class="products-container">
                    <!-- Cards will be rendered here by JS -->
                </div>

                <!-- Pagination -->
                <div class="pagination">
                    <button id="first-page">&lt;&lt;</button>
                    <button id="prev-page">&lt;</button>
                    <span id="page-info">1 / 1</span>
                    <button id="next-page">&gt;</button>
                    <button id="last-page">&gt;&gt;</button>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <div id="requestModal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>

                <!-- Header: required space and colored status -->
                <div class="modal-title-row">
                    <h2 id="modalHeading"></h2>
                    <span id="modalStatusBadge" class="status-badge"></span>
                </div>
                <div class="muted" id="modalSub"></div>

                <img id="modalImage" src="" alt="Product Thumbnail">
                <div class="modal-details">
                    <p><strong>Requested By:</strong> <span id="modalRequester"></span></p>
                    <p><strong>Requested At:</strong> <span id="modalRequestedAt"></span></p>
                    <p><strong>Product Name:</strong> <span id="modalName"></span></p>
                    <p><strong>Category:</strong> <span id="modalCategory"></span></p>
                    <p><strong>Quantity:</strong> <span id="modalQuantity"></span></p>
                    <p><strong>Notes:</strong> <span id="modalNotes"></span></p>
                    <p><strong>Updated At:</strong> <span id="modalUpdatedAt"></span></p>
                </div>
                <div class="modal-buttons">
                    <a class="switch-branch" href="#">Switch to other branch</a>
                    <button class="accept">Accept</button>
                    <button class="reject">Reject</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Data from PHP
        const items = <?php echo json_encode($requests); ?>;

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
            modal.style.display = 'none';
        });
        window.addEventListener('click', (event) => {
            if (event.target === modal) modal.style.display = 'none';
        });

        let selectedRequestId = null;
        let currentPage = 0;
        let totalPages = 1;
        renderCards();

        function renderCards() {
            const minVolume = parseFloat(document.getElementById('min-volume').value) || 0;
            const maxVolume = parseFloat(document.getElementById('max-volume').value) || Infinity;
            const startDate = document.getElementById('start-date').value;
            const startOption = document.querySelector('input[name="start-date-option"]:checked')?.value;
            const selectedCategories = Array.from(document.querySelectorAll('.category-checkbox:checked')).map(cb => cb.value);
            const searchQuery = document.getElementById('search-input').value.toLowerCase();
            const sortValue = document.getElementById('sort-by').value;
            const perPage = document.getElementById('per-page').value === 'all' ? items.length : parseInt(document.getElementById('per-page').value, 10);

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
                filtered.sort((a, b) => a.id - b.id);
            }

            // Header
            document.getElementById('requests-header').innerText = filtered.length === items.length ? 'All Requests' : 'Filtered Requests';

            // Pagination
            totalPages = Math.ceil(filtered.length / perPage);
            if (currentPage > totalPages - 1) currentPage = 0;
            const start = currentPage * perPage;
            const pageItems = filtered.slice(start, start + perPage);

            // Render cards (show earlier attributes + status)
            const container = document.querySelector('.products-container');
            container.innerHTML = '';
            pageItems.forEach(item => {
                const card = document.createElement('div');
                card.classList.add('product-row');
                const statusClass = String(item.status || '').toLowerCase();
                const reqSpace = (item.requiredSpace ?? '-') + ' m³';
                card.innerHTML = `
                    <img src="../../${item.image}" alt="${item.name}">
                    <div class="product-info">
                        <h3>${item.name}</h3>
                        <span>Category: ${item.category ?? '-'}</span>
                        <span>Required Space: ${reqSpace}</span>
                        <span>Requested At: ${item.requested_at}</span>
                        <span class="status-badge ${statusClass}">${item.status}</span>
                        <a class="view-details-link" href="#" data-id="${item.id}">View Details</a>
                    </div>
                `;
                container.appendChild(card);
            });

            // View details
            document.querySelectorAll('.view-details-link').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const itemId = parseInt(link.getAttribute('data-id'));
                    const item = items.find(i => i.id == itemId);
                    if (item) {
                        selectedRequestId = item.id;
                        const spaceText = (item.requiredSpace ?? '-') + ' m³';
                        document.getElementById('modalHeading').textContent = `Inventory Request for ${spaceText}`;
                        document.getElementById('modalSub').textContent = `Request #${item.id}`;
                        document.getElementById('modalImage').src = `../../${item.image}`;

                        // Colored status in header row
                        const badge = document.getElementById('modalStatusBadge');
                        const sClass = String(item.status || '').toLowerCase();
                        badge.className = `status-badge ${sClass}`;
                        badge.textContent = item.status ?? '-';

                        // Details order (Requester, Requested At, Product, Category, Quantity, Notes, Updated At)
                        document.getElementById('modalRequester').textContent = item.requester_name ?? '-';
                        document.getElementById('modalRequestedAt').textContent = item.requested_at ?? '-';
                        document.getElementById('modalName').textContent = item.name ?? '-';
                        document.getElementById('modalCategory').textContent = item.category ?? '-';
                        document.getElementById('modalQuantity').textContent = item.quantity ?? '-';
                        document.getElementById('modalNotes').textContent = item.notes ?? '-';
                        document.getElementById('modalUpdatedAt').textContent = item.updated_at ?? '-';

                        modal.style.display = 'block';
                    }
                });
            });

            // Update pagination info
            document.getElementById('page-info').innerText = `${totalPages > 0 ? currentPage + 1 : 0} / ${totalPages || 1}`;
            document.getElementById('first-page').disabled = currentPage === 0;
            document.getElementById('prev-page').disabled = currentPage === 0;
            document.getElementById('next-page').disabled = currentPage >= totalPages - 1;
            document.getElementById('last-page').disabled = currentPage >= totalPages - 1;

            // Bind Accept/Reject once
            const acceptBtn = document.querySelector('.accept');
            const rejectBtn = document.querySelector('.reject');
            if (acceptBtn && !acceptBtn._bound) {
                acceptBtn._bound = true;
                acceptBtn.addEventListener('click', async () => {
                    if (!selectedRequestId) return;
                    await updateRequestStatus('Accept', selectedRequestId);
                });
            }
            if (rejectBtn && !rejectBtn._bound) {
                rejectBtn._bound = true;
                rejectBtn.addEventListener('click', async () => {
                    if (!selectedRequestId) return;
                    await updateRequestStatus('Reject', selectedRequestId);
                });
            }
        }

        async function updateRequestStatus(action, requestId) {
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
                    if (idx > -1) items[idx].status = action === 'Accept' ? 'Done' : 'Rejected';
                    document.getElementById('requestModal').style.display = 'none';
                    renderCards();
                } else {
                    alert('Failed to update status.');
                }
            } catch {
                alert('Error updating status.');
            }
        }
    </script>

</body>

</html>