
<link rel="stylesheet" href="../../Include/sidebar.css">
<?php include '../../Include/SidebarShop.php'; ?>

<?php
// session_start();
include '../../include/connect-db.php';

// Current user (defaults to 2 like other shop-owner pages)
$user_id = $_SESSION['user_id'] ?? 2;

// Helper: get or create the active cart_id for this user
function get_active_cart_id(mysqli $conn, int $user_id): int
{
  $sql = "SELECT cart_id FROM shop_owner_cart_list WHERE user_id = ? AND status = 'active' ORDER BY updated_at DESC LIMIT 1";
  $st = mysqli_prepare($conn, $sql);
  mysqli_stmt_bind_param($st, 'i', $user_id);
  mysqli_stmt_execute($st);
  $rs = mysqli_stmt_get_result($st);
  $row = mysqli_fetch_assoc($rs);
  mysqli_stmt_close($st);
  if ($row && isset($row['cart_id'])) {
    return (int)$row['cart_id'];
  }
  // Create a new active cart if none exists
  $ins = mysqli_prepare($conn, "INSERT INTO shop_owner_cart_list (user_id, status) VALUES (?, 'active')");
  mysqli_stmt_bind_param($ins, 'i', $user_id);
  mysqli_stmt_execute($ins);
  $new_id = (int) mysqli_insert_id($conn);
  mysqli_stmt_close($ins);
  return $new_id;
}

$cart_id = get_active_cart_id($conn, (int)$user_id);

// Fetch user data for Shop-Owner (current logged-in user preferred)
$query = "SELECT full_name, phone, email FROM users WHERE user_id = ? LIMIT 1";
$stmtUser = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmtUser, 'i', $user_id);
mysqli_stmt_execute($stmtUser);
$resUser = mysqli_stmt_get_result($stmtUser);
$user_data = mysqli_fetch_assoc($resUser) ?? ['full_name' => '', 'phone' => '', 'email' => ''];
mysqli_stmt_close($stmtUser);

// Handle cart actions (update, delete, clear)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // helper to compute totals after a change
  $computeTotals = function (mysqli $conn, int $cart_id) {
    $sqlT = "SELECT COALESCE(SUM(ci.quantity * COALESCE(ci.price_at_time, p.price)),0) AS subtotal, COUNT(*) AS item_count
             FROM shop_owner_cart_items ci
             LEFT JOIN products p ON p.product_id = ci.product_id
             WHERE ci.cart_id = ?";
    $stT = mysqli_prepare($conn, $sqlT);
    mysqli_stmt_bind_param($stT, 'i', $cart_id);
    mysqli_stmt_execute($stT);
    $rsT = mysqli_stmt_get_result($stT);
    $rowT = mysqli_fetch_assoc($rsT) ?: ['subtotal' => 0, 'item_count' => 0];
    mysqli_stmt_close($stT);
    $subtotal = (float)$rowT['subtotal'];
    $discountX = 0; // keep aligned with view
    $taxX = 0;
    $shippingX = 200;
    $grand = max(0, $subtotal - $discountX + $taxX + $shippingX);
    return [
      'subtotal' => $subtotal,
      'tax' => $taxX,
      'shipping' => $shippingX,
      'discount' => $discountX,
      'grand_total' => $grand,
      'cart_count' => (int)$rowT['item_count']
    ];
  };
  // Clear entire cart
  if (isset($_POST['clear_cart'])) {
    $sql = "DELETE FROM shop_owner_cart_items WHERE cart_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $cart_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (isset($_POST['ajax'])) {
      header('Content-Type: application/json');
      $tot = $computeTotals($conn, $cart_id);
      echo json_encode(['success' => true, 'cleared' => true] + $tot);
      exit;
    }
  }

  // Update single item quantity
  if (isset($_POST['update_item']) && isset($_POST['cart_item_id'])) {
    $cart_item_id = (int)($_POST['cart_item_id'] ?? 0);
    $quantity = max(0, (int)($_POST['quantity'] ?? 0));

    // Get product_id for the cart item (and ensure ownership via cart_id)
    $q = "SELECT product_id FROM shop_owner_cart_items WHERE cart_item_id = ? AND cart_id = ?";
    $st = mysqli_prepare($conn, $q);
    mysqli_stmt_bind_param($st, 'ii', $cart_item_id, $cart_id);
    mysqli_stmt_execute($st);
    $r = mysqli_stmt_get_result($st);
    $row = mysqli_fetch_assoc($r);
    mysqli_stmt_close($st);

    if ($row) {
      $product_id = (int)$row['product_id'];

      if ($quantity === 0) {
        // Delete the item row from cart
        $del = mysqli_prepare($conn, "DELETE FROM shop_owner_cart_items WHERE cart_item_id=? AND cart_id=?");
        mysqli_stmt_bind_param($del, 'ii', $cart_item_id, $cart_id);
        mysqli_stmt_execute($del);
        mysqli_stmt_close($del);
        if (isset($_POST['ajax'])) {
          header('Content-Type: application/json');
          $tot = $computeTotals($conn, $cart_id);
          echo json_encode(['success' => true, 'removed' => true, 'cart_item_id' => $cart_item_id] + $tot);
          exit;
        }
      } else {
        // Optional: check available stock
        $stockSql = "SELECT COALESCE(SUM(wp.quantity),0) AS available FROM warehouse_products wp WHERE wp.product_id = ?";
        $st2 = mysqli_prepare($conn, $stockSql);
        mysqli_stmt_bind_param($st2, 'i', $product_id);
        mysqli_stmt_execute($st2);
        $rs2 = mysqli_stmt_get_result($st2);
        $available = (int) (mysqli_fetch_assoc($rs2)['available'] ?? 0);
        mysqli_stmt_close($st2);

        if ($quantity > $available) {
          $quantity = $available; // clamp to available
        }

        $up = mysqli_prepare($conn, "UPDATE shop_owner_cart_items SET quantity=?, updated_at=CURRENT_TIMESTAMP WHERE cart_item_id=? AND cart_id=?");
        mysqli_stmt_bind_param($up, 'iii', $quantity, $cart_item_id, $cart_id);
        mysqli_stmt_execute($up);
        mysqli_stmt_close($up);
        if (isset($_POST['ajax'])) {
          // compute this item's total
          $itSql = "SELECT ci.quantity, COALESCE(ci.price_at_time, p.price) AS unit_price FROM shop_owner_cart_items ci LEFT JOIN products p ON p.product_id = ci.product_id WHERE ci.cart_id=? AND ci.cart_item_id=?";
          $ist = mysqli_prepare($conn, $itSql);
          mysqli_stmt_bind_param($ist, 'ii', $cart_id, $cart_item_id);
          mysqli_stmt_execute($ist);
          $irs = mysqli_stmt_get_result($ist);
          $irow = mysqli_fetch_assoc($irs) ?: ['quantity' => 0, 'unit_price' => 0];
          mysqli_stmt_close($ist);
          $item_total = (float)$irow['quantity'] * (float)$irow['unit_price'];
          $tot = $computeTotals($conn, $cart_id);
          header('Content-Type: application/json');
          echo json_encode([
            'success' => true,
            'removed' => false,
            'cart_item_id' => $cart_item_id,
            'quantity' => (int)$irow['quantity'],
            'item_total' => $item_total
          ] + $tot);
          exit;
        }
      }
    }
  }

  // Delete single item
  if (isset($_POST['delete_item']) && isset($_POST['cart_item_id'])) {
    $cart_item_id = (int)$_POST['cart_item_id'];
    $del = mysqli_prepare($conn, "DELETE FROM shop_owner_cart_items WHERE cart_item_id=? AND cart_id=?");
    mysqli_stmt_bind_param($del, 'ii', $cart_item_id, $cart_id);
    mysqli_stmt_execute($del);
    mysqli_stmt_close($del);
    if (isset($_POST['ajax'])) {
      header('Content-Type: application/json');
      $tot = $computeTotals($conn, $cart_id);
      echo json_encode(['success' => true, 'removed' => true, 'cart_item_id' => $cart_item_id] + $tot);
      exit;
    }
  }
}

// Fetch active cart items for this user
$cart = [];
$sql = "SELECT 
          ci.cart_item_id,
          ci.product_id,
          ci.quantity,
          COALESCE(ci.price_at_time, p.price) AS unit_price,
          p.name AS product_name,
          p.img_url,
          p.unit,
          p.category
   FROM shop_owner_cart_items ci
   LEFT JOIN products p ON p.product_id = ci.product_id
        WHERE ci.cart_id = ?
        ORDER BY ci.cart_item_id DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, 'i', $cart_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);
while ($row = mysqli_fetch_assoc($res)) {
  $img = $row['img_url'] ?: '';
  $isAbsolute = (strpos($img, 'http://') === 0) || (strpos($img, 'https://') === 0) || (strpos($img, '/') === 0);
  $imgPath = $isAbsolute ? $img : ('../../' . ltrim($img, '/'));
  $productMissing = empty($row['product_name']);
  $displayName = $productMissing ? ('Unknown product #' . $row['product_id']) : ($row['product_name'] ?? 'Product');
  $displayDesc = $productMissing ? 'Product not found in catalog' : ($row['unit'] ? ('Unit: ' . $row['unit']) : ($row['category'] ?? ''));
  $displayImg = $productMissing ? '../../assets/products-image/placeholder.jpg' : ($imgPath ?: '../../assets/products-image/placeholder.jpg');
  $cart[] = [
    'cart_item_id' => (int)$row['cart_item_id'],
    'id' => (int)$row['product_id'],
    'name' => $displayName,
    'description' => $displayDesc,
    'price' => (float)$row['unit_price'],
    'quantity' => (int)$row['quantity'],
    'image' => $displayImg
  ];
}
mysqli_stmt_close($stmt);

// Totals (will also be updated via JS on the client)
$total = 0;
foreach ($cart as $item) {
  $total += ($item['price'] * $item['quantity']);
}
$discount = 0;
$tax = 0;
$shipping = 200;
$grand_total = max(0, $total - $discount + $tax + $shipping);
?>

<!doctype html>
<html lang="en" data-bs-theme="auto">

<head>
  <script src="../assets/js/color-modes.js"></script>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="">
  <meta name="author" content="Mark Otto, Jacob Thornton, and Bootstrap contributors">
  <meta name="generator" content="Hugo 0.122.0">
  <title>Cart Viewing & Checkout</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <link rel="canonical" href="https://getbootstrap.com/docs/5.3/examples/checkout/">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@docsearch/css@3">
  <link href="../assets/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="../Includes/Navbar/navbarMain.css">
  <link rel="stylesheet" href="checkout.css">
</head>

<body class="bg-light">
  <div class="container">
    <main>
      <div class="py-5 text-center">
        <img class="d-block mx-auto mb-4" src="checkout_logo.png" alt="" width="72" height="67">
        <h2 class="text-primary fw-bold">Your Cart & Checkout</h2>
        <a href="../buy-products-from-warehouse.php" class="btn btn-outline-secondary mb-3">Back to Products</a>
      </div>

      <?php if (empty($cart)): ?>
        <div class="alert alert-info text-center">
          <!-- Your cart is empty. <a href="shop-owner-app\buy-products-from-warehouse.php" class="alert-link">Browse available products</a> to add items. -->
          Your cart is empty. <a href="../buy-products-from-warehouse.php" class="alert-link">Browse available products</a> to add items.
        </div>
      <?php else: ?>

        <div class="row g-5">
          <div class="col-md-5 col-lg-4 order-md-last">
            <div class="cart-header">
              Your Cart (<span id="cart-count" class="text-primary"><?php echo count($cart); ?></span> items)
            </div>
            <div class="cart-summary">
              <ul class="list-group" id="cart-items">
                <?php foreach ($cart as $index => $item): ?>
                  <li class="cart-item" data-item-id="<?php echo (int)$item['cart_item_id']; ?>">
                    <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="cart-item-img">
                    <div class="cart-item-details">
                      <h6 class="text-dark"><?php echo htmlspecialchars($item['name']); ?></h6>
                      <?php if (!empty($item['description'])): ?>
                        <small class="text-muted"><?php echo htmlspecialchars($item['description']); ?></small>
                      <?php endif; ?>
                    </div>
                    <div class="cart-item-actions">
                      <form method="POST" class="d-flex align-items-center flex-grow-1" data-index="<?php echo $index; ?>">
                        <input type="hidden" name="cart_item_id" value="<?php echo (int)$item['cart_item_id']; ?>">
                        <input type="number" name="quantity" class="form-control quantity-input me-2" value="<?php echo (int)$item['quantity']; ?>" min="0" data-price="<?php echo (float)$item['price']; ?>">
                        <button type="submit" name="update_item" class="btn btn-outline-primary update-btn me-2" data-index="<?php echo $index; ?>">Update</button>
                        <button type="submit" name="delete_item" class="btn btn-outline-danger delete-btn" data-index="<?php echo $index; ?>">Delete</button>
                      </form>
                      <span class="price-tag ms-3 text-muted">Tk<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                  </li>
                <?php endforeach; ?>
                <li class="list-group-item d-flex justify-content-between">
                  <span class="fw-medium">Subtotal</span>
                  <strong class="text-danger" id="subtotal">Tk<?php echo number_format($total, 2); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <span class="fw-medium">Taxes</span>
                  <strong class="text-danger" id="tax">Tk<?php echo number_format($tax, 2); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <span class="fw-medium">Shipping</span>
                  <strong class="text-danger" id="shipping">Tk<?php echo number_format($shipping, 2); ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <span class="fw-medium">Grand Total (Taka)</span>
                  <strong class="text-danger" id="grand-total">Tk<?php echo number_format($grand_total, 2); ?></strong>
                </li>
              </ul>
            </div>
            <form method="POST" action="" class="cart-actions">
              <button type="submit" name="clear_cart" class="btn btn-danger w-100 mb-2">Clear Cart</button>
              <button type="button" class="btn btn-outline-secondary w-100" onclick="alert('Cart saved for later!');">Save Cart for Later</button>
            </form>

            <button class="btn btn-outline-info w-100 mt-3" onclick="alert('Receipt Preview: Total Tk<?php echo number_format($grand_total, 2); ?>');">Preview Receipt</button>
            <a href="request-product.php" class="btn btn-outline-secondary w-100 mt-3">Request Out-of-Stock Product</a>
          </div>

          <div class="col-md-7 col-lg-8">
            <div class="billing-form">
              <h4 class="mb-3 text-dark fw-bold">Billing & Shipping Information</h4>
              <form id="paymentForm" class="needs-validation" novalidate>
                <div class="row g-3">
                  <div class="col-12">
                    <label for="shopName" class="form-label text-dark">Shop Name</label>
                    <input type="text" class="form-control" id="shopName" name="shopName" placeholder="Enter shop name" value="<?php echo htmlspecialchars($user_data['full_name']); ?>" required>
                    <div class="invalid-feedback">Shop name is required.</div>
                  </div>

                  <div class="col-12">
                    <label for="ownerName" class="form-label text-dark">Shop Owner Name</label>
                    <input type="text" class="form-control" id="ownerName" name="ownerName" placeholder="Enter owner name" value="" required>
                    <div class="invalid-feedback">Owner name is required.</div>
                  </div>
                  <div class="col-12">
                    <label for="phone" class="form-label text-dark">Phone Number</label>
                    <div class="input-group has-validation">
                      <span class="input-group-text bg-light">📞</span>
                      <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone Number" value="<?php echo htmlspecialchars($user_data['phone']); ?>" required>
                      <div class="invalid-feedback">Your phone number is required.</div>
                    </div>
                  </div>
                  <div class="col-12">
                    <label for="email" class="form-label text-dark">Email</label>
                    <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                    <div class="invalid-feedback">Please enter a valid email address for shipping updates.</div>
                  </div>
                  <div class="col-12">
                    <label for="address" class="form-label text-dark">Billing Address</label>
                    <input type="text" class="form-control" id="address" name="address" placeholder="1234 Main St" required>
                    <div class="invalid-feedback">Please enter your billing address.</div>
                  </div>
                  <div class="col-12">
                    <label for="shippingAddress" class="form-label text-dark">Shipping Address</label>
                    <input type="text" class="form-control" id="shippingAddress" name="shippingAddress" placeholder="1234 Main St" required>
                    <div class="invalid-feedback">Please enter your shipping address.</div>
                  </div>
                  <div class="col-md-5">
                    <label for="country" class="form-label text-dark">Country</label>
                    <select class="form-select" id="country" name="country" required>
                      <option value="">Choose...</option>
                      <option>Bangladesh</option>
                    </select>
                    <div class="invalid-feedback">Please select a valid country.</div>
                  </div>
                  <div class="col-md-4">
                    <label for="state" class="form-label text-dark">State</label>
                    <select class="form-select" id="state" name="state" required>
                      <option value="">Choose...</option>
                      <option>Dhaka</option>
                    </select>
                    <div class="invalid-feedback">Please provide a valid state.</div>
                  </div>
                  <div class="col-md-3">
                    <label for="zip" class="form-label text-dark">Zip</label>
                    <input type="text" class="form-control" id="zip" name="zip" placeholder="" required>
                    <div class="invalid-feedback">Zip code required.</div>
                  </div>
                  <div class="col-12">
                    <label for="notes" class="form-label text-dark">Special Instructions/Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="e.g., Deliver after 5 PM"></textarea>
                  </div>
                  <div class="col-12">
                    <label for="taxId" class="form-label text-dark">Tax ID/VAT Number <span class="text-muted">(Optional)</span></label>
                    <input type="text" class="form-control" id="taxId" name="taxId" placeholder="Your Tax ID">
                  </div>
                </div>

                <hr class="my-4">

                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="same-address">
                  <label class="form-check-label text-dark" for="same-address">Shipping address is the same as my billing address</label>
                </div>
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="save-info">
                  <label class="form-check-label text-dark" for="save-info">Save this information for next time</label>
                </div>
                <div class="form-check">
                  <input type="checkbox" class="form-check-input" id="agree-terms" required>
                  <label class="form-check-label text-dark" for="agree-terms">I agree to the terms and conditions</label>
                  <div class="invalid-feedback">You must agree to the terms.</div>
                </div>

                <hr class="my-4">

                <button class="btn btn-primary btn-block" type="submit" id="sslczPayBtn" postdata="{}" order="123" endpoint="checkout_ajax.php">Pay Now</button>
              </form>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </main>
  </div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js" crossorigin="anonymous"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
  <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
  <script src="../assets/dist/js/bootstrap.bundle.min.js"></script>
  <script src="checkout.js"></script>
  <script>
    // DOM manipulation for cart updates
    document.addEventListener('DOMContentLoaded', function() {
      // Save-for-next-time profile handling (localStorage)
      const USER_ID = <?php echo (int)$user_id; ?>;
      const PROFILE_KEY = `checkout_profile_u${USER_ID}`;

      const form = document.getElementById('paymentForm');
      const saveInfoCheckbox = document.getElementById('save-info');

      function getFormData() {
        return {
          shopName: document.getElementById('shopName').value || '',
          ownerName: document.getElementById('ownerName').value || '',
          phone: document.getElementById('phone').value || '',
          email: document.getElementById('email').value || '',
          address: document.getElementById('address').value || '',
          shippingAddress: document.getElementById('shippingAddress').value || '',
          country: document.getElementById('country').value || '',
          state: document.getElementById('state').value || '',
          zip: document.getElementById('zip').value || '',
          notes: document.getElementById('notes').value || '',
          taxId: document.getElementById('taxId').value || ''
        };
      }

      function setFormData(data) {
        if (!data || typeof data !== 'object') return;
        const setVal = (id, val) => {
          const el = document.getElementById(id);
          if (el && val != null) el.value = val;
        };
        setVal('shopName', data.shopName);
        setVal('ownerName', data.ownerName);
        setVal('phone', data.phone);
        setVal('email', data.email);
        setVal('address', data.address);
        setVal('shippingAddress', data.shippingAddress);
        setVal('country', data.country);
        setVal('state', data.state);
        setVal('zip', data.zip);
        setVal('notes', data.notes);
        setVal('taxId', data.taxId);
      }

      function loadProfile() {
        try {
          const raw = localStorage.getItem(PROFILE_KEY);
          if (!raw) return;
          const profile = JSON.parse(raw);
          setFormData(profile);
          // Keep the checkbox checked to indicate data came from saved profile
          if (saveInfoCheckbox) saveInfoCheckbox.checked = true;
        } catch (e) {
          /* ignore parse errors */
        }
      }

      function saveProfile() {
        try {
          const data = getFormData();
          localStorage.setItem(PROFILE_KEY, JSON.stringify(data));
        } catch (e) {
          /* ignore storage errors */
        }
      }

      // Load saved profile on page load
      loadProfile();

      // If user toggles save-info, save or remove profile
      if (saveInfoCheckbox) {
        saveInfoCheckbox.addEventListener('change', function() {
          if (this.checked) {
            saveProfile();
          } else {
            localStorage.removeItem(PROFILE_KEY);
          }
        });
      }

      const cartItems = document.getElementById('cart-items');
      const subtotalEl = document.getElementById('subtotal');
      const taxEl = document.getElementById('tax');
      const shippingEl = document.getElementById('shipping');
      const grandTotalEl = document.getElementById('grand-total');
      const discount = <?php echo $discount; ?>;
      const tax = <?php echo $tax; ?>;
      const shipping = <?php echo $shipping; ?>;

      function updateTotals() {
        let subtotal = 0;
        cartItems.querySelectorAll('.cart-item').forEach(item => {
          const qty = parseInt(item.querySelector('.quantity-input').value) || 0;
          const price = parseFloat(item.querySelector('.quantity-input').dataset.price) || 0;
          const priceTag = item.querySelector('.price-tag');
          priceTag.textContent = `Tk${(qty * price).toFixed(2)}`;
          subtotal += qty * price;
        });
        subtotalEl.textContent = `Tk${subtotal.toFixed(2)}`;
        const grandTotal = subtotal - discount + tax + shipping;
        grandTotalEl.textContent = `Tk${grandTotal.toFixed(2)}`;
      }

      cartItems.addEventListener('click', function(e) {
        const updateBtn = e.target.closest('.update-btn');
        const deleteBtn = e.target.closest('.delete-btn');

        if (updateBtn) {
          e.preventDefault();
          const index = updateBtn.dataset.index;
          const li = cartItems.children[index];
          const itemId = li.dataset.itemId;
          const qtyInput = li.querySelector('.quantity-input');
          const qty = Math.max(0, parseInt(qtyInput.value || '0'));
          // submit AJAX
          const formData = new URLSearchParams();
          formData.append('update_item', '1');
          formData.append('ajax', '1');
          formData.append('cart_item_id', itemId);
          formData.append('quantity', String(qty));
          fetch('', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
              if (!data.success) return;
              if (data.removed) {
                li.remove();
              } else {
                // sync quantity (in case backend clamped it)
                qtyInput.value = data.quantity;
                const priceTag = li.querySelector('.price-tag');
                priceTag.textContent = `Tk${(data.item_total || 0).toFixed(2)}`;
              }
              // totals
              subtotalEl.textContent = `Tk${(data.subtotal || 0).toFixed(2)}`;
              taxEl.textContent = `Tk${(data.tax || 0).toFixed(2)}`;
              shippingEl.textContent = `Tk${(data.shipping || 0).toFixed(2)}`;
              grandTotalEl.textContent = `Tk${(data.grand_total || 0).toFixed(2)}`;
              const countEl = document.getElementById('cart-count');
              if (countEl && typeof data.cart_count !== 'undefined') {
                countEl.textContent = String(data.cart_count);
              }
            })
            .catch(() => {
              // fallback to local recalc
              updateTotals();
            });
        }

        if (deleteBtn) {
          e.preventDefault();
          const index = deleteBtn.dataset.index;
          const li = cartItems.children[index];
          const itemId = li.dataset.itemId;
          const formData = new URLSearchParams();
          formData.append('delete_item', '1');
          formData.append('ajax', '1');
          formData.append('cart_item_id', itemId);
          fetch('', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
              if (!data.success) return;
              li.remove();
              subtotalEl.textContent = `Tk${(data.subtotal || 0).toFixed(2)}`;
              taxEl.textContent = `Tk${(data.tax || 0).toFixed(2)}`;
              shippingEl.textContent = `Tk${(data.shipping || 0).toFixed(2)}`;
              grandTotalEl.textContent = `Tk${(data.grand_total || 0).toFixed(2)}`;
              const countEl = document.getElementById('cart-count');
              if (countEl && typeof data.cart_count !== 'undefined') {
                countEl.textContent = String(data.cart_count);
              }
            })
            .catch(() => updateTotals());
        }
      });

      // Initial total update
      updateTotals();

      // Same-address checkbox logic
      document.getElementById('same-address').addEventListener('change', function() {
        if (this.checked) {
          document.getElementById('shippingAddress').value = document.getElementById('address').value;
          document.getElementById('shippingAddress').disabled = true;
        } else {
          document.getElementById('shippingAddress').disabled = false;
        }
      });

      // Wire up Clear Cart button via AJAX
      const clearBtn = document.querySelector('button[name="clear_cart"]');
      if (clearBtn) {
        clearBtn.addEventListener('click', function(e) {
          e.preventDefault();
          const formData = new URLSearchParams();
          formData.append('clear_cart', '1');
          formData.append('ajax', '1');
          fetch('', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
              },
              body: formData.toString()
            })
            .then(r => r.json())
            .then(data => {
              if (!data.success) return;
              // remove all li.cart-item entries
              cartItems.querySelectorAll('li.cart-item').forEach(li => li.remove());
              subtotalEl.textContent = `Tk${(data.subtotal || 0).toFixed(2)}`;
              taxEl.textContent = `Tk${(data.tax || 0).toFixed(2)}`;
              shippingEl.textContent = `Tk${(data.shipping || 0).toFixed(2)}`;
              grandTotalEl.textContent = `Tk${(data.grand_total || 0).toFixed(2)}`;
              const countEl = document.getElementById('cart-count');
              if (countEl && typeof data.cart_count !== 'undefined') {
                countEl.textContent = String(data.cart_count);
              }
            })
            .catch(() => updateTotals());
        });
      }

      // Payment button postdata
      $('#sslczPayBtn').on('click', function(e) {
        e.preventDefault(); // Prevent default form submission
        // Form validation
        var form = document.getElementById('paymentForm');
        if (form.checkValidity() === false) {
          e.stopPropagation();
          form.classList.add('was-validated');
          return;
        }

        // Persist profile locally if requested
        if (saveInfoCheckbox && saveInfoCheckbox.checked) {
          saveProfile();
        }

        var obj = {
          shopName: $('#shopName').val(),
          ownerName: $('#ownerName').val(),
          phone: $('#phone').val(),
          email: $('#email').val(),
          address: $('#address').val(),
          shippingAddress: $('#shippingAddress').val(),
          country: $('#country').val(),
          state: $('#state').val(),
          zip: $('#zip').val(),
          notes: $('#notes').val(),
          taxId: $('#taxId').val(),
          amount: parseFloat($('#grand-total').text().replace('Tk', ''))
        };

        // Set postdata and trigger SSLCommerz
        $(this).prop('postdata', obj);

        // The SSLCommerz script will automatically pick up the click and postdata
      });
    });

    // SSLCommerz script loader
    (function(window, document) {
      var loader = function() {
        var script = document.createElement("script"),
          tag = document.getElementsByTagName("script")[0];
        script.src = "https://sandbox.sslcommerz.com/embed.min.js?" + Math.random().toString(36).substring(7);
        tag.parentNode.insertBefore(script, tag);
      };
      window.addEventListener ? window.addEventListener("load", loader, false) : window.attachEvent("onload", loader);
    })(window, document);
  </script>
</body>

</html>