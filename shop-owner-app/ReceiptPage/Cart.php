<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Generate Receipt</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="..\..\include\sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="bg-gray-100">
    <?php
    include '../../include/Sidebar.php';
    ?>
    <section class="home-section p-0">
        <div class="text-2xl font-bold p-4">
            Generate Receipt
        </div>

        <div class="container mx-auto my-3 p-2">
            <!-- Product Card 1 -->
            <div class="bg-white shadow-md rounded-lg mb-3 flex">
                <div class="w-1/6 p-2">
                    <img src="https://www.lays.com/sites/lays.com/files/2020-11/lays-bbq.jpg"
                        class="w-full h-48 object-cover rounded" alt="Lay's Chips">
                </div>
                <div class="w-4/6 flex flex-col justify-center p-4">
                    <h5 class="text-xl font-semibold">Lay's Chips</h5>
                    <p class="counter-price" data-base-price="15">30</p>
                </div>
                <div class="w-1/6 flex flex-col items-center justify-center">
                    <div class="flex items-center space-x-2">
                        <button type="button" class="decrease-btn bg-gray-200 px-3 py-1 rounded">-</button>
                        <div class="counter text-lg">2</div>
                        <button type="button" class="increase-btn bg-gray-200 px-3 py-1 rounded">+</button>
                    </div>
                </div>
            </div>
            <!-- Product Card 2 -->
            <div class="bg-white shadow-md rounded-lg mb-3 flex">
                <div class="w-1/6 p-2">
                    <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcTaRzEHWAsSf4tno2sJkarET8u0A_rEFJYhDg&s"
                        class="w-full h-48 object-cover rounded" alt="Product Image">
                </div>
                <div class="w-4/6 flex flex-col justify-center p-4">
                    <h5 class="text-xl font-semibold">Card title</h5>
                    <p class="text-gray-600">This is a wider card with supporting text below as a natural lead-in to
                        additional content. This content is a little bit longer.</p>
                    <p class="text-gray-500 text-sm">Last updated 3 mins ago</p>
                </div>
                <div class="w-1/6 flex flex-col items-center justify-center">
                    <div class="flex items-center space-x-2">
                        <button type="button" class="decrease-btn bg-gray-200 px-3 py-1 rounded">-</button>
                        <div class="counter text-lg">2</div>
                        <button type="button" class="increase-btn bg-gray-200 px-3 py-1 rounded">+</button>
                    </div>
                </div>
            </div>
            <!-- Product Card 3 -->
            <div class="bg-white shadow-md rounded-lg mb-3 flex">
                <div class="w-1/6 p-2 flex items-center justify-center">
                    <img src="https://www.kroger.com/product/images/large/left/0003800016966"
                        class="w-full h-48 object-cover rounded" alt="Product Image">
                </div>
                <div class="w-4/6 flex flex-col justify-center p-4">
                    <h5 class="text-xl font-semibold">Card title</h5>
                    <p class="text-gray-600">This is a wider card with supporting text below as a natural lead-in to
                        additional content. This content is a little bit longer.</p>
                    <p class="text-gray-500 text-sm">Last updated 3 mins ago</p>
                </div>
                <div class="w-1/6 flex flex-col items-center justify-center">
                    <div class="flex items-center space-x-2">
                        <button type="button" class="decrease-btn bg-gray-200 px-3 py-1 rounded">-</button>
                        <div class="counter text-lg">2</div>
                        <button type="button" class="increase-btn bg-gray-200 px-3 py-1 rounded">+</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mx-auto flex space-x-4">
            <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Manually add product</button>
            <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Scan to add product</button>
        </div>
    </section>
    <script>
        document.querySelectorAll('.flex.mb-3').forEach(card => {
            const counterElement = card.querySelector('.counter');
            const counterPriceElement = card.querySelector('.counter-price');
            const increaseBtn = card.querySelector('.increase-btn');
            const decreaseBtn = card.querySelector('.decrease-btn');
            const basePrice = parseInt(counterPriceElement.dataset.basePrice) || 15;

            let counter = parseInt(counterElement.textContent);
            let counterPrice = parseInt(counterPriceElement.textContent);

            increaseBtn.addEventListener('click', () => {
                counter++;
                counterPrice += basePrice;
                counterElement.textContent = counter;
                counterPriceElement.textContent = counterPrice;
            });

            decreaseBtn.addEventListener('click', () => {
                if (counter > 0) {
                    counter--;
                    counterPrice -= basePrice;
                    counterElement.textContent = counter;
                    counterPriceElement.textContent = counterPrice;
                }
            });
        });
    </script>
</body>

</html>