<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Generate Receipt</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link rel="stylesheet" href="../Include/sidebar.css">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>

<body class="bg-gray-100">
    <?php
    include '../Include/Sidebar.php';
    ?>
    <section class="home-section p-0">
        <div class="text-2xl font-bold p-4">
            Generate Receipt
        </div>

        <div class="container mx-auto my-3">
            <!-- Product Card 1: Lay's Chips -->
            <div class="bg-white shadow-md rounded-lg mb-3 flex product-card" data-product="lays">
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
            <div class="bg-white shadow-md rounded-lg mb-3 flex product-card">
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
                <div class="w-1/6 flex flex-col items-center justify-content-center">
                    <div class="flex items-center space-x-2">
                        <button type="button" class="decrease-btn bg-gray-200 px-3 py-1 rounded">-</button>
                        <div class="counter text-lg">2</div>
                        <button type="button" class="increase-btn bg-gray-200 px-3 py-1 rounded">+</button>
                    </div>
                </div>
            </div>
            <!-- Product Card 3 -->
            <div class="bg-white shadow-md rounded-lg mb-3 flex product-card">
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
                <div class="w-1/6 flex flex-col items-center justify-content-center">
                    <div class="flex items-center space-x-2">
                        <button type="button" class="decrease-btn bg-gray-200 px-3 py-1 rounded">-</button>
                        <div class="counter text-lg">2</div>
                        <button type="button" class="increase-btn bg-gray-200 px-3 py-1 rounded">+</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="container mx-auto my-3 flex space-x-4">
            <button type="button" class="bg-gray-200 px-4 py-2 rounded hover:bg-gray-300">Manually add product</button>
            <button type="button" class="scan-btn bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Scan to
                add product</button>
        </div>
    </section>

    <!-- Webcam Overlay Modal -->
    <div id="webcam-modal" class="fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 hidden">
        <div class="bg-white p-4 rounded-lg max-w-md w-full mx-4">
            <h3 class="text-lg font-bold mb-2">Scanning for Lay's Chips...</h3>
            <video id="webcam-video" autoplay class="w-full max-h-64 object-cover rounded mb-2"
                style="background: black;"></video>
            <div id="detection-status" class="text-sm text-gray-600 mb-2">Hold a Lay's chip in view...</div>
            <button id="stop-scan-btn" class="bg-red-500 text-white px-4 py-2 rounded w-full">Stop Scan</button>
        </div>
    </div>

    <script>
    // Manual counter logic for all cards
    document.querySelectorAll('.product-card').forEach(card => {
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

    // Webcam Detection for Lay's Chips using Roboflow Hosted API
    const scanBtn = document.querySelector('.scan-btn');
    const modal = document.getElementById('webcam-modal');
    const video = document.getElementById('webcam-video');
    const status = document.getElementById('detection-status');
    const stopBtn = document.getElementById('stop-scan-btn');
    const API_KEY = 'u64iBlZU98pNf3NhHccg'; // REPLACE WITH YOUR API KEY!

    let stream = null;
    let detectionInterval = null;
    let detected = false;

    scanBtn.addEventListener('click', async () => {
        console.log('Starting scan...');
        if (!API_KEY || API_KEY === 'YOUR_ROBOFLOW_API_KEY') {
            alert('Please set your Roboflow API Key in the code!');
            return;
        }

        try {
            // Show modal
            modal.classList.remove('hidden');

            // Get webcam access
            stream = await navigator.mediaDevices.getUserMedia({
                video: {
                    facingMode: 'environment'
                }
            });
            video.srcObject = stream;
            await new Promise(resolve => {
                video.onloadedmetadata = () => {
                    video.play();
                    resolve();
                };
            });

            status.textContent = 'Hold a Lay\'s chip in view...';

            // Start detection loop
            detectionInterval = setInterval(async () => {
                if (video.readyState === video.HAVE_ENOUGH_DATA && !detected) {
                    // Capture frame as base64
                    const canvas = document.createElement('canvas');
                    canvas.width = video.videoWidth;
                    canvas.height = video.videoHeight;
                    canvas.getContext('2d').drawImage(video, 0, 0);
                    const imageData = canvas.toDataURL('image/jpeg', 0.8);

                    // Send to Roboflow API
                    try {
                        const response = await fetch(
                            'https://detect.roboflow.com/lays-txw7q/1?api_key=' + API_KEY, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded'
                                },
                                body: imageData
                            });
                        const predictions = await response.json();
                        console.log('Predictions:', predictions);

                        if (predictions.predictions && predictions.predictions.length > 0 &&
                            predictions.predictions[0].confidence > 0.5) {
                            // Detection found! Add to Lay's cart
                            const laysCard = document.querySelector(
                                '.product-card[data-product="lays"]');
                            if (laysCard) {
                                const increaseBtn = laysCard.querySelector('.increase-btn');
                                increaseBtn.click();
                                status.textContent =
                                    `Detected ${predictions.predictions[0].class}! Added to cart.`;
                                console.log('Added to cart:', predictions.predictions[0].class);
                                detected = true;
                                stopScan();
                            }
                        } else {
                            status.textContent =
                                'No Lay\'s chips detected. Try adjusting the angle or lighting.';
                        }
                    } catch (apiError) {
                        console.error('API error:', apiError);
                        status.textContent = 'Error contacting Roboflow API. Check console.';
                    }
                }
            }, 500); // Check every 500ms

        } catch (error) {
            console.error('Scan error:', error);
            alert(`Error starting scan: ${error.message}. Check console for details.`);
            stopScan();
        }
    });

    stopBtn.addEventListener('click', stopScan);

    function stopScan() {
        console.log('Stopping scan...');
        if (detectionInterval) clearInterval(detectionInterval);
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        modal.classList.add('hidden');
        detected = false;
        status.textContent = 'Hold a Lay\'s chip in view...';
    }

    // Cleanup on page unload
    window.addEventListener('beforeunload', stopScan);
    </script>
</body>

</html>