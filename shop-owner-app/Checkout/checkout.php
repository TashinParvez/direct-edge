<?php
// Hardcoded cart data (simulating session data)
$cart = [
  [
    'id' => 1,
    'name' => 'Rice',
    'description' => 'Miniket',
    'price' => 5000,
    'quantity' => 1,
    'image' => '../Images/products/rice.jpg'
  ],
  [
    'id' => 2,
    'name' => 'Potato',
    'description' => 'Fresh potatoes',
    'price' => 2000,
    'quantity' => 1,
    'image' => '../Images/products/potato.jpg'
  ],
  [
    'id' => 3,
    'name' => 'Sugar',
    'description' => 'Refined sugar',
    'price' => 3000,
    'quantity' => 1,
    'image' => '../Images/products/sugar.jpg'
  ]
];

// Handle initial totals (will be updated via JS)
$total = 0;
foreach ($cart as $item) {
  $total += $item['price'] * $item['quantity'];
}
$discount = 500;
$tax = 0;
$shipping = 200;
$grand_total = $total - $discount + $tax + $shipping;
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
        <a href="buy-products.php" class="btn btn-outline-secondary mb-3">Back to Products</a>
      </div>

      <?php if (empty($cart)): ?>
        <div class="alert alert-info text-center">
          Your cart is empty. <a href="buy-products.php" class="alert-link">Browse available products</a> to add items.
        </div>
      <?php else: ?>

        <div class="row g-5">
          <div class="col-md-5 col-lg-4 order-md-last">
            <div class="cart-header">
              Your Cart (<span class="text-primary"><?php echo count($cart); ?></span> items)
            </div>
            <div class="cart-summary">
              <ul class="list-group" id="cart-items">
                <?php foreach ($cart as $index => $item): ?>
                  <li class="cart-item" data-item-id="<?php echo $item['id']; ?>">
                    <img src="<?php echo $item['image']; ?>" alt="<?php echo $item['name']; ?>" class="cart-item-img">
                    <div class="cart-item-details">
                      <h6 class="text-dark"><?php echo $item['name']; ?></h6>
                      <small class="text-muted"><?php echo $item['description']; ?></small>
                      <?php if ($item['quantity'] < 5): ?>
                        <small class="text-warning">Only <?php echo $item['quantity']; ?> left - Add quickly!</small>
                      <?php endif; ?>
                    </div>
                    <div class="cart-item-actions">
                      <form class="d-flex align-items-center flex-grow-1" data-index="<?php echo $index; ?>">
                        <input type="hidden" name="item-id" value="<?php echo $item['id']; ?>">
                        <input type="number" class="form-control quantity-input me-2" value="<?php echo $item['quantity']; ?>" min="0" data-price="<?php echo $item['price']; ?>">
                        <button type="button" class="btn btn-outline-primary update-btn me-2" data-index="<?php echo $index; ?>">Update</button>
                        <button type="button" class="btn btn-outline-danger delete-btn" data-index="<?php echo $index; ?>">Delete</button>
                      </form>
                      <span class="price-tag ms-3 text-muted">Tk<?php echo number_format($item['price'] * $item['quantity'], 2); ?></span>
                    </div>
                  </li>
                <?php endforeach; ?>
                <li class="list-group-item d-flex justify-content-between bg-light">
                  <div class="text-success">
                    <h6 class="my-0">Promo code</h6>
                    <small>EXAMPLECODE</small>
                  </div>
                  <span class="text-success">−Tk<?php echo number_format($discount, 2); ?></span>
                </li>
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

            <form class="card p-2 mt-3">
              <div class="input-group">
                <input type="text" class="form-control" placeholder="Promo code">
                <button type="submit" class="btn btn-secondary">Redeem</button>
              </div>
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
                    <input type="text" class="form-control" id="shopName" placeholder="Enter shop name" value="" required>
                    <div class="invalid-feedback">Shop name is required.</div>
                  </div>

                  <div class="col-12">
                    <label for="ownerName" class="form-label text-dark">Shop Owner Name</label>
                    <input type="text" class="form-control" id="ownerName" placeholder="Enter owner name" value="" required>
                    <div class="invalid-feedback">Owner name is required.</div>
                  </div>
                  <div class="col-12">
                    <label for="phone" class="form-label text-dark">Phone Number</label>
                    <div class="input-group has-validation">
                      <span class="input-group-text bg-light">📞</span>
                      <input type="tel" class="form-control" id="phone" placeholder="Phone Number" required>
                      <div class="invalid-feedback">Your phone number is required.</div>
                    </div>
                  </div>
                  <div class="col-12">
                    <label for="email" class="form-label text-dark">Email</label>
                    <input type="email" class="form-control" id="email" placeholder="you@example.com" required>
                    <div class="invalid-feedback">Please enter a valid email address for shipping updates.</div>
                  </div>
                  <div class="col-12">
                    <label for="address" class="form-label text-dark">Billing Address</label>
                    <input type="text" class="form-control" id="address" placeholder="1234 Main St" required>
                    <div class="invalid-feedback">Please enter your billing address.</div>
                  </div>
                  <div class="col-12">
                    <label for="shippingAddress" class="form-label text-dark">Shipping Address</label>
                    <input type="text" class="form-control" id="shippingAddress" placeholder="1234 Main St" required>
                    <div class="invalid-feedback">Please enter your shipping address.</div>
                  </div>
                  <div class="col-12">
                    <label for="address2" class="form-label text-dark">Address 2 <span class="text-muted">(Optional)</span></label>
                    <input type="text" class="form-control" id="address2" placeholder="Apartment or suite">
                  </div>
                  <div class="col-md-5">
                    <label for="country" class="form-label text-dark">Country</label>
                    <select class="form-select" id="country" required>
                      <option value="">Choose...</option>
                      <option>Bangladesh</option>
                    </select>
                    <div class="invalid-feedback">Please select a valid country.</div>
                  </div>
                  <div class="col-md-4">
                    <label for="state" class="form-label text-dark">State</label>
                    <select class="form-select" id="state" required>
                      <option value="">Choose...</option>
                      <option>Dhaka</option>
                    </select>
                    <div class="invalid-feedback">Please provide a valid state.</div>
                  </div>
                  <div class="col-md-3">
                    <label for="zip" class="form-label text-dark">Zip</label>
                    <input type="text" class="form-control" id="zip" placeholder="" required>
                    <div class="invalid-feedback">Zip code required.</div>
                  </div>
                  <div class="col-12">
                    <label for="notes" class="form-label text-dark">Special Instructions/Notes</label>
                    <textarea class="form-control" id="notes" rows="3" placeholder="e.g., Deliver after 5 PM"></textarea>
                  </div>
                  <div class="col-12">
                    <label for="taxId" class="form-label text-dark">Tax ID/VAT Number <span class="text-muted">(Optional)</span></label>
                    <input type="text" class="form-control" id="taxId" placeholder="Your Tax ID">
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
          const index = updateBtn.dataset.index;
          const item = cartItems.querySelector(`.cart-item[data-item-id="${cartItems.children[index].dataset.itemId}"]`);
          const qtyInput = item.querySelector('.quantity-input');
          const qty = parseInt(qtyInput.value);
          if (qty >= 0) {
            if (qty === 0) {
              item.remove();
            }
            updateTotals();
          } else {
            qtyInput.value = 1; // Reset to 1 if negative
            updateTotals();
          }
        }

        if (deleteBtn) {
          const index = deleteBtn.dataset.index;
          cartItems.children[index].remove();
          updateTotals();
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

      // Payment button postdata
      $('#sslczPayBtn').on('click', function(e) {
        var obj = {
          cus_name: $('#firstName').val() + ' ' + $('#lastName').val(),
          cus_phone: $('#phone').val(),
          cus_email: $('#email').val(),
          cus_addr1: $('#address').val(),
          amount: parseFloat($('#grand-total').text().replace('Tk', ''))
        };
        $(this).prop('postdata', JSON.stringify(obj));
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