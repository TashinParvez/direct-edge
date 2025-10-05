// Example starter JavaScript for disabling form submissions if there are invalid fields
(() => {
    'use strict'

    // Fetch the form element
    const form = document.getElementById('paymentForm');
  
    // Add event listener to the payment button
    document.getElementById('sslczPayBtn').addEventListener('click', (event) => {
        if (!form.checkValidity()) {
            event.preventDefault();  // Prevent form submission if invalid
            event.stopPropagation(); // Stop the event from propagating
        }
  
        form.classList.add('was-validated'); // Add Bootstrap validation styles
    }, false);

})();
  // checkout.js

document.addEventListener('DOMContentLoaded', function() {
  const cartItems = document.getElementById('cart-items');
  const subtotalEl = document.getElementById('subtotal');
  const taxEl = document.getElementById('tax');
  const shippingEl = document.getElementById('shipping');
  const grandTotalEl = document.getElementById('grand-total');
  const discount = parseFloat(document.querySelector('.text-success span').textContent.replace('-Tk', ''));
  const tax = parseFloat(taxEl.textContent.replace('Tk', ''));
  const shipping = parseFloat(shippingEl.textContent.replace('Tk', ''));

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
      const item = updateBtn.closest('.cart-item');
      const qtyInput = item.querySelector('.quantity-input');
      const qty = parseInt(qtyInput.value);
      if (qty >= 0) {
        if (qty === 0) {
          item.remove();
        }
        updateTotals();
      } else {
        qtyInput.value = 1;
        updateTotals();
      }
    }

    if (deleteBtn) {
      deleteBtn.closest('.cart-item').remove();
      updateTotals();
    }
  });

  // Real-time quantity update on input change
  cartItems.addEventListener('input', function(e) {
    if (e.target.classList.contains('quantity-input')) {
      const item = e.target.closest('.cart-item');
      const qty = parseInt(e.target.value);
      if (qty >= 0) {
        if (qty === 0) {
          item.remove();
        }
        updateTotals();
      } else {
        e.target.value = 1;
        updateTotals();
      }
    }
  });

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
      cus_name: $('#ownerName').val(),
      shop_name: $('#shopName').val(),      cus_phone: $('#phone').val(),
      cus_email: $('#email').val(),
      cus_addr1: $('#address').val(),
      amount: parseFloat($('#grand-total').text().replace('Tk', ''))
    };
    $(this).prop('postdata', JSON.stringify(obj));
  });
});