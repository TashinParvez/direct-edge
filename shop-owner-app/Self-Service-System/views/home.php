<?php
function renderHome()
{
?>
    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Shopno Entry</title>
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            /* Custom color overrides using your theme */
            :root {
                --black-text: #111827;
                --green-text: #16a34a;
                --bg-color: #f0fdf7;
                --button-color: #16a34a;
                --tick-color: #64d68e;
            }

            body {
                background-color: var(--bg-color);
            }

            .btn-custom {
                background-color: var(--button-color);
                color: white;
            }

            .btn-custom:hover {
                background-color: #128a3b;
            }

            .tick {
                color: var(--tick-color);
            }

            h1,
            label {
                color: var(--black-text);
            }

            input {
                border-color: #cbd5e1;
            }

            input:focus {
                outline: none;
                border-color: var(--green-text);
                ring: 2px var(--green-text);
            }
        </style>
    </head>

    <body class="flex items-center justify-center min-h-screen">
        <div class="bg-white p-10 rounded-xl shadow-lg w-full max-w-md">
            <h1 class="text-3xl font-extrabold mb-6 text-center">Welcome to Shopno</h1>
            <form action="/products" method="POST" class="space-y-5">
                <div>
                    <label for="user_name" class="block mb-2 font-medium">Enter Your Name:</label>
                    <input type="text" id="user_name" name="user_name" required
                        class="w-full p-3 rounded-lg border focus:ring-2 focus:ring-green-500 transition">
                </div>
                <button type="submit"
                    class="btn-custom w-full p-3 rounded-lg font-semibold text-lg transition duration-200">Start
                    Shopping</button>
            </form>

            <div class="mt-6 text-center text-green-600 flex items-center justify-center space-x-2">
                <svg class="tick w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z"
                        clip-rule="evenodd"></path>
                </svg>
                <span class="font-medium">Fast & Secure Checkout</span>
            </div>
        </div>
    </body>

    </html>
<?php
}
?>