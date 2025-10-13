<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../include/connect-db.php';

function respond($ok, $data = [], $code = 200)
{
    http_response_code($code);
    echo json_encode(array_merge(['ok' => $ok], $data));
    exit;
}

function get_json()
{
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function status_to_flag($statusText)
{
    return (strtolower(trim($statusText)) === 'in progress') ? 1 : 0;
}

// Helper: fetch warehouse capacities (optionally locking the row)
function get_warehouse_caps(mysqli $conn, int $warehouseId, bool $forUpdate = false)
{
    $warehouseId = (int)$warehouseId;
    $sql = "SELECT capacity_total AS total, capacity_used AS used FROM warehouses WHERE warehouse_id = $warehouseId" . ($forUpdate ? ' FOR UPDATE' : '');
    $res = mysqli_query($conn, $sql);
    if (!$res) return null;
    $row = mysqli_fetch_assoc($res);
    if (!$row) return null;
    return ['total' => (float)$row['total'], 'used' => (float)$row['used']];
}

// Helper: check if adding volume would overflow capacity
function can_add_volume(mysqli $conn, int $warehouseId, float $addVol, bool $lock = false)
{
    if ($addVol <= 0) return ['ok' => true];
    $caps = get_warehouse_caps($conn, $warehouseId, $lock);
    if (!$caps) return ['ok' => false, 'error' => 'Warehouse not found'];
    if (($caps['used'] + $addVol) > $caps['total']) {
        return ['ok' => false, 'error' => 'Warehouse full!'];
    }
    return ['ok' => true];
}

// Helper: adjust capacity_used by delta (can be negative). Clamps to >= 0.
function adjust_capacity(mysqli $conn, int $warehouseId, float $delta)
{
    if ($delta == 0) return true;
    $warehouseId = (int)$warehouseId;
    $delta = (float)$delta;
    $sql = "UPDATE warehouses SET capacity_used = GREATEST(0, capacity_used + ($delta)) WHERE warehouse_id = $warehouseId";
    return (bool)mysqli_query($conn, $sql);
}

function load_row(mysqli $conn, $id)
{
    $id = (int)$id;
    $sql = "SELECT
                wp.id,
                wp.product_id,
                p.name AS product_name,
                p.special_instructions AS product_instructions,
                p.unit AS product_unit,
                wp.quantity,
                wp.unit_volume,
                CASE WHEN wp.request_status = 1 THEN 'In progress' ELSE 'Completed' END AS status,
                w.warehouse_id,
                w.name AS warehouse_name,
                wp.agent_id,
                wp.offer_percentage,
                wp.offer_start,
                wp.offer_end,
                wp.inbound_stock_date,
                wp.expiry_date,
                wp.last_updated
            FROM warehouse_products wp
            JOIN products p ON p.product_id = wp.product_id
            JOIN warehouses w ON w.warehouse_id = wp.warehouse_id
            WHERE wp.id = $id";
    $res = mysqli_query($conn, $sql);
    if (!$res) return null;
    $row = mysqli_fetch_assoc($res);
    if (!$row) return null;
    $productCode = 'PRD-' . str_pad((string)$row['product_id'], 3, '0', STR_PAD_LEFT);
    $offerPct = $row['offer_percentage'];
    $offerText = ($offerPct !== null && $offerPct > 0)
        ? ($row['offer_start'] && $row['offer_end']
            ? ($offerPct . '% (' . $row['offer_start'] . ' to ' . $row['offer_end'] . ')')
            : ($offerPct . '%'))
        : 'No Offer';
    return [
        'id' => (int)$row['id'],
        'product_id' => (int)$row['product_id'],
        'product_code' => $productCode,
        'product_name' => $row['product_name'],
        'special_instructions' => $row['product_instructions'],
        'product_unit' => $row['product_unit'],
        'quantity' => (int)$row['quantity'],
        'unit_volume' => $row['unit_volume'],
        'status' => $row['status'],
        'warehouse_id' => (int)$row['warehouse_id'],
        'warehouse_name' => $row['warehouse_name'],
        'agent_id' => $row['agent_id'],
        'offer_text' => $offerText,
        'inbound_stock_date' => $row['inbound_stock_date'],
        'expiry_date' => $row['expiry_date'],
        'last_updated' => $row['last_updated']
    ];
}

function metrics(mysqli $conn)
{
    $m = ['totalCapacity' => 0, 'usedCapacity' => 0, 'freeCapacity' => 0, 'itemCount' => 0];
    $res = mysqli_query($conn, "SELECT SUM(capacity_total) AS tc, SUM(capacity_used) AS uc FROM warehouses");
    if ($res && ($r = mysqli_fetch_assoc($res))) {
        $m['totalCapacity'] = (int)($r['tc'] ?? 0);
        $m['usedCapacity'] = (int)($r['uc'] ?? 0);
        $m['freeCapacity'] = max(0, $m['totalCapacity'] - $m['usedCapacity']);
    }
    $res2 = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM warehouse_products");
    if ($res2 && ($r2 = mysqli_fetch_assoc($res2))) {
        $m['itemCount'] = (int)($r2['cnt'] ?? 0);
    }
    return $m;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';
if ($action === '') {
    respond(false, ['error' => 'Missing action'], 400);
}

try {
    if ($action === 'metrics') {
        respond(true, ['metrics' => metrics($conn)]);
    }

    if ($action === 'add') {
        $data = get_json();
        $productId = (int)($data['productId'] ?? 0);
        $dateAdded = $data['dateAdded'] ?? null; // inbound_stock_date
        $quantity = (int)($data['quantity'] ?? 0);
        $unitVolume = isset($data['unitVolume']) ? (float)$data['unitVolume'] : null;
        $warehouseId = (int)($data['warehouseId'] ?? 0);
        $agentId = isset($data['agentId']) && $data['agentId'] !== '' ? (int)$data['agentId'] : null;
        $expiryDate = $data['expiryDate'] ?? null;
        $statusText = $data['status'] ?? 'In progress';
        $statusFlag = status_to_flag($statusText);
        $special = isset($data['specialInstructions']) ? trim($data['specialInstructions']) : '';

        if (!$productId || !$dateAdded || !$quantity || !$warehouseId || $unitVolume === null) {
            respond(false, ['error' => 'Missing required fields'], 400);
        }

        // Validate product exists
        $chk = mysqli_query($conn, "SELECT product_id FROM products WHERE product_id = $productId");
        if (!$chk || !mysqli_fetch_assoc($chk)) respond(false, ['error' => 'Invalid product'], 400);

        // Optionally update product special_instructions
        if ($special !== '') {
            $specEsc = mysqli_real_escape_string($conn, $special);
            mysqli_query($conn, "UPDATE products SET special_instructions='$specEsc' WHERE product_id=$productId");
        }

        $dateAddedEsc = mysqli_real_escape_string($conn, $dateAdded);
        $expiryEsc = $expiryDate ? "'" . mysqli_real_escape_string($conn, $expiryDate) . "'" : 'NULL';
        $agentEsc = $agentId !== null ? (int)$agentId : 'NULL';
        // Volume applies only when Completed (request_status = 0)
        $newVol = ($statusFlag === 0) ? ((float)$quantity * (float)$unitVolume) : 0.0;

        // Use a transaction to ensure capacity and row are consistent
        mysqli_begin_transaction($conn);
        try {
            if ($newVol > 0) {
                $capCheck = can_add_volume($conn, $warehouseId, $newVol, true);
                if (!$capCheck['ok']) {
                    mysqli_rollback($conn);
                    respond(false, ['error' => $capCheck['error']], 400);
                }
            }

            $sqlAdd = "INSERT INTO warehouse_products(warehouse_id, product_id, quantity, unit_volume, offer_percentage, offer_start, offer_end, request_status, inbound_stock_date, expiry_date, agent_id)
                       VALUES($warehouseId, $productId, $quantity, $unitVolume, NULL, NULL, NULL, $statusFlag, '$dateAddedEsc', $expiryEsc, $agentEsc)";
            if (!mysqli_query($conn, $sqlAdd)) {
                throw new Exception('Failed to insert warehouse product');
            }
            $id = (int)mysqli_insert_id($conn);

            if ($newVol > 0) {
                if (!adjust_capacity($conn, $warehouseId, $newVol)) {
                    throw new Exception('Failed to update capacity');
                }
            }

            mysqli_commit($conn);
            $row = load_row($conn, $id);
            respond(true, ['row' => $row, 'metrics' => metrics($conn)]);
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            respond(false, ['error' => $ex->getMessage()], 500);
        }
    }

    if ($action === 'edit') {
        $data = get_json();
        $id = (int)($data['id'] ?? 0);
        if (!$id) respond(false, ['error' => 'Missing id'], 400);
        $productId = (int)($data['productId'] ?? 0);
        $dateAdded = $data['dateAdded'] ?? null;
        $quantity = (int)($data['quantity'] ?? 0);
        $unitVolume = isset($data['unitVolume']) ? (float)$data['unitVolume'] : null;
        $warehouseId = (int)($data['warehouseId'] ?? 0);
        $agentId = isset($data['agentId']) && $data['agentId'] !== '' ? (int)$data['agentId'] : null;
        $expiryDate = $data['expiryDate'] ?? null;
        $statusText = $data['status'] ?? 'In progress';
        $statusFlag = status_to_flag($statusText);
        $special = isset($data['specialInstructions']) ? trim($data['specialInstructions']) : '';

        // Load current row for calculations
        $cur = mysqli_query($conn, "SELECT product_id, warehouse_id, quantity, unit_volume, request_status FROM warehouse_products WHERE id = $id");
        if (!$cur || !($cr = mysqli_fetch_assoc($cur))) respond(false, ['error' => 'Not found'], 404);
        if (!$productId) $productId = (int)$cr['product_id'];
        if ($productId) {
            $chk = mysqli_query($conn, "SELECT product_id FROM products WHERE product_id = $productId");
            if (!$chk || !mysqli_fetch_assoc($chk)) respond(false, ['error' => 'Invalid product'], 400);
        }

        // Optionally update product special_instructions
        if ($special !== '' && $productId) {
            $specEsc = mysqli_real_escape_string($conn, $special);
            mysqli_query($conn, "UPDATE products SET special_instructions='$specEsc' WHERE product_id=$productId");
        }

        $dateAddedEsc = $dateAdded ? "'" . mysqli_real_escape_string($conn, $dateAdded) . "'" : 'NULL';
        $expiryEsc = $expiryDate ? "'" . mysqli_real_escape_string($conn, $expiryDate) . "'" : 'NULL';
        $agentEsc = $agentId !== null ? (int)$agentId : 'NULL';
        // Compute final values (if not provided, keep current)
        $finalWarehouseId = $warehouseId ?: (int)$cr['warehouse_id'];
        $finalQuantity = $quantity ?: (int)$cr['quantity'];
        $finalUnitVolume = ($unitVolume !== null) ? $unitVolume : (float)$cr['unit_volume'];
        $oldCompletedVol = ((int)$cr['request_status'] === 0) ? ((float)$cr['quantity'] * (float)$cr['unit_volume']) : 0.0;
        $newCompletedVol = ($statusFlag === 0) ? ((float)$finalQuantity * (float)$finalUnitVolume) : 0.0;

        // Transaction for capacity checks and updates
        mysqli_begin_transaction($conn);
        try {
            if ($finalWarehouseId === (int)$cr['warehouse_id']) {
                $delta = $newCompletedVol - $oldCompletedVol;
                if ($delta > 0) {
                    $capCheck = can_add_volume($conn, $finalWarehouseId, $delta, true);
                    if (!$capCheck['ok']) {
                        mysqli_rollback($conn);
                        respond(false, ['error' => $capCheck['error']], 400);
                    }
                } else {
                    // lock row anyway to serialize
                    get_warehouse_caps($conn, $finalWarehouseId, true);
                }
            } else {
                // Moving warehouses: ensure target can accommodate newCompletedVol
                if ($newCompletedVol > 0) {
                    $capCheck = can_add_volume($conn, $finalWarehouseId, $newCompletedVol, true);
                    if (!$capCheck['ok']) {
                        mysqli_rollback($conn);
                        respond(false, ['error' => $capCheck['error']], 400);
                    }
                } else {
                    // lock target row too
                    get_warehouse_caps($conn, $finalWarehouseId, true);
                }
                // lock source for consistent subtraction if needed
                if ($oldCompletedVol > 0) get_warehouse_caps($conn, (int)$cr['warehouse_id'], true);
            }

            // Perform the update
            $setsWP = [];
            $setsWP[] = "warehouse_id=$finalWarehouseId";
            $setsWP[] = "quantity=$finalQuantity";
            $setsWP[] = "unit_volume=$finalUnitVolume";
            if ($productId) $setsWP[] = "product_id=$productId";
            $setsWP[] = "request_status=$statusFlag";
            $setsWP[] = "inbound_stock_date=$dateAddedEsc";
            $setsWP[] = "expiry_date=$expiryEsc";
            $setsWP[] = "agent_id=$agentEsc";
            $sqlU = "UPDATE warehouse_products SET " . implode(',', $setsWP) . " WHERE id = $id";
            if (!mysqli_query($conn, $sqlU)) {
                throw new Exception('Failed to update item');
            }

            // Adjust capacities
            if ($finalWarehouseId === (int)$cr['warehouse_id']) {
                $delta = $newCompletedVol - $oldCompletedVol;
                if ($delta != 0 && !adjust_capacity($conn, $finalWarehouseId, $delta)) {
                    throw new Exception('Failed to adjust capacity');
                }
            } else {
                if ($oldCompletedVol > 0 && !adjust_capacity($conn, (int)$cr['warehouse_id'], -$oldCompletedVol)) {
                    throw new Exception('Failed to adjust source capacity');
                }
                if ($newCompletedVol > 0 && !adjust_capacity($conn, $finalWarehouseId, $newCompletedVol)) {
                    throw new Exception('Failed to adjust target capacity');
                }
            }

            mysqli_commit($conn);
            $row = load_row($conn, $id);
            respond(true, ['row' => $row, 'metrics' => metrics($conn)]);
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            respond(false, ['error' => $ex->getMessage()], 500);
        }
    }

    if ($action === 'delete') {
        $data = get_json();
        $id = (int)($data['id'] ?? 0);
        if (!$id) respond(false, ['error' => 'Missing id'], 400);
        // Load row to compute capacity rollback if it was Completed
        $cur = mysqli_query($conn, "SELECT warehouse_id, quantity, unit_volume, request_status FROM warehouse_products WHERE id = $id");
        if (!$cur || !($cr = mysqli_fetch_assoc($cur))) respond(false, ['error' => 'Not found'], 404);
        $oldCompletedVol = ((int)$cr['request_status'] === 0) ? ((float)$cr['quantity'] * (float)$cr['unit_volume']) : 0.0;
        $warehouseId = (int)$cr['warehouse_id'];

        mysqli_begin_transaction($conn);
        try {
            if ($oldCompletedVol > 0) {
                // lock warehouse row then adjust
                get_warehouse_caps($conn, $warehouseId, true);
            }
            if (!mysqli_query($conn, "DELETE FROM warehouse_products WHERE id = $id")) {
                throw new Exception('Failed to delete');
            }
            if ($oldCompletedVol > 0 && !adjust_capacity($conn, $warehouseId, -$oldCompletedVol)) {
                throw new Exception('Failed to adjust capacity');
            }
            mysqli_commit($conn);
            respond(true, ['metrics' => metrics($conn)]);
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            respond(false, ['error' => $ex->getMessage()], 500);
        }
    }

    if ($action === 'offer') {
        $data = get_json();
        $id = (int)($data['id'] ?? 0);
        $discount = isset($data['discount']) ? (float)$data['discount'] : null;
        $startDate = $data['startDate'] ?? null;
        $endDate = $data['endDate'] ?? null;
        if (!$id || $discount === null || !$startDate || !$endDate) {
            respond(false, ['error' => 'Missing fields'], 400);
        }
        $disc = (float)$discount;
        $startEsc = mysqli_real_escape_string($conn, $startDate);
        $endEsc = mysqli_real_escape_string($conn, $endDate);
        $sql = "UPDATE warehouse_products SET offer_percentage=$disc, offer_start='$startEsc', offer_end='$endEsc' WHERE id=$id";
        if (!mysqli_query($conn, $sql)) respond(false, ['error' => 'Failed to save offer'], 500);
        $row = load_row($conn, $id);
        respond(true, ['row' => $row]);
    }

    if ($action === 'list') {
        $data = get_json();
        $search = trim($data['search'] ?? '');
        $statuses = is_array($data['statuses'] ?? null) ? $data['statuses'] : [];
        $capacities = is_array($data['capacities'] ?? null) ? $data['capacities'] : [];
        $warehouses = is_array($data['warehouses'] ?? null) ? $data['warehouses'] : [];
        $units = is_array($data['units'] ?? null) ? $data['units'] : [];
        $page = max(1, (int)($data['page'] ?? 1));
        $pageSize = max(1, min(100, (int)($data['pageSize'] ?? 10)));

        // Preload warehouse capacities
        $warehouseCaps = [];
        $resCaps = mysqli_query($conn, "SELECT warehouse_id, capacity_total, capacity_used FROM warehouses");
        if ($resCaps) {
            while ($c = mysqli_fetch_assoc($resCaps)) {
                $warehouseCaps[(int)$c['warehouse_id']] = [
                    'total' => (float)$c['capacity_total'],
                    'used' => (float)$c['capacity_used']
                ];
            }
        }

        $where = [];
        // Statuses
        $statusFlags = [];
        foreach ($statuses as $s) {
            $s = strtolower(trim($s));
            if ($s === 'in progress') $statusFlags[] = 1;
            if ($s === 'completed') $statusFlags[] = 0;
        }
        if (!empty($statusFlags)) {
            $where[] = 'wp.request_status IN (' . implode(',', array_unique(array_map('intval', $statusFlags))) . ')';
        }

        // Capacities -> quantity bands
        $capConds = [];
        foreach ($capacities as $c) {
            if ($c === 'low') $capConds[] = 'wp.quantity < 10';
            if ($c === 'medium') $capConds[] = '(wp.quantity BETWEEN 10 AND 50)';
            if ($c === 'high') $capConds[] = 'wp.quantity > 50';
        }
        if (!empty($capConds)) {
            $where[] = '(' . implode(' OR ', $capConds) . ')';
        }

        // Warehouses by name
        if (!empty($warehouses)) {
            $names = array_map(function ($n) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $n) . "'";
            }, $warehouses);
            $where[] = 'w.name IN (' . implode(',', $names) . ')';
        }

        // Units from products.unit
        if (!empty($units)) {
            $us = array_map(function ($u) use ($conn) {
                return "'" . mysqli_real_escape_string($conn, $u) . "'";
            }, $units);
            $where[] = 'p.unit IN (' . implode(',', $us) . ')';
        }

        // Search by name or PRD-code
        if ($search !== '') {
            $searchEsc = mysqli_real_escape_string($conn, $search);
            $cond = "p.name LIKE '%$searchEsc%'";
            if (preg_match('/^PRD-([0-9]+)/i', $search, $m)) {
                $pid = (int)$m[1];
                $cond = "($cond OR wp.product_id = $pid)";
            }
            $where[] = $cond;
        }

        $whereSql = !empty($where) ? ('WHERE ' . implode(' AND ', $where)) : '';

        // Totals for filtered set
        $sqlTotals = "SELECT COUNT(*) AS total, COALESCE(SUM(wp.quantity),0) AS total_qty
                      FROM warehouse_products wp
                      JOIN products p ON p.product_id = wp.product_id
                      JOIN warehouses w ON w.warehouse_id = wp.warehouse_id
                      $whereSql";
        $resTotals = mysqli_query($conn, $sqlTotals);
        $tot = ['total' => 0, 'total_qty' => 0];
        if ($resTotals && ($rt = mysqli_fetch_assoc($resTotals))) {
            $tot['total'] = (int)$rt['total'];
            $tot['total_qty'] = (int)$rt['total_qty'];
        }

        $offset = ($page - 1) * $pageSize;
        $sqlRows = "SELECT
                        wp.id,
                        wp.product_id,
                        p.name AS product_name,
                        p.special_instructions AS product_instructions,
                        p.unit AS product_unit,
                        wp.quantity,
                        wp.unit_volume,
                        CASE WHEN wp.request_status = 1 THEN 'In progress' ELSE 'Completed' END AS status,
                        w.warehouse_id,
                        w.name AS warehouse_name,
                        wp.agent_id,
                        wp.offer_percentage,
                        wp.offer_start,
                        wp.offer_end,
                        wp.inbound_stock_date,
                        wp.expiry_date,
                        wp.last_updated
                    FROM warehouse_products wp
                    JOIN products p ON p.product_id = wp.product_id
                    JOIN warehouses w ON w.warehouse_id = wp.warehouse_id
                    $whereSql
                    ORDER BY wp.last_updated DESC
                    LIMIT $offset, $pageSize";
        $res = mysqli_query($conn, $sqlRows);
        $rows = [];
        if ($res) {
            while ($row = mysqli_fetch_assoc($res)) {
                $one = load_row($conn, (int)$row['id']);
                if ($one) {
                    $whId = $one['warehouse_id'];
                    $one['free_space'] = isset($warehouseCaps[$whId]) ? max(0, $warehouseCaps[$whId]['total'] - $warehouseCaps[$whId]['used']) : 0;
                    $rows[] = $one;
                }
            }
        }

        respond(true, [
            'rows' => $rows,
            'pagination' => [
                'page' => $page,
                'pageSize' => $pageSize,
                'total' => $tot['total'],
                'totalPages' => (int)max(1, ceil($tot['total'] / $pageSize))
            ],
            'totals' => [
                'totalItems' => $tot['total'],
                'totalQuantity' => $tot['total_qty']
            ]
        ]);
    }

    // Optional maintenance action: Recalculate capacity_used based on completed items
    if ($action === 'recalc-capacity') {
        mysqli_begin_transaction($conn);
        try {
            $sql = "UPDATE warehouses w
                    LEFT JOIN (
                        SELECT warehouse_id, COALESCE(SUM(quantity * unit_volume), 0) AS used
                        FROM warehouse_products
                        WHERE request_status = 0
                        GROUP BY warehouse_id
                    ) x ON x.warehouse_id = w.warehouse_id
                    SET w.capacity_used = LEAST(w.capacity_total, COALESCE(x.used, 0))";
            if (!mysqli_query($conn, $sql)) {
                throw new Exception('Failed to recalculate capacity');
            }
            mysqli_commit($conn);
            respond(true, ['metrics' => metrics($conn)]);
        } catch (Throwable $ex) {
            mysqli_rollback($conn);
            respond(false, ['error' => $ex->getMessage()], 500);
        }
    }

    respond(false, ['error' => 'Unknown action'], 400);
} catch (Throwable $e) {
    respond(false, ['error' => 'Server error', 'detail' => $e->getMessage()], 500);
}
