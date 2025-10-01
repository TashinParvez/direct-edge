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
  