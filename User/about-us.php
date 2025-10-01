<!-- aboutus.php -->
<?php include('../include/user-navbar.php'); ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>


<body class="bg-gray-50 text-gray-800">
    <div class="max-w-5xl mx-auto px-6 py-12">
        <!-- Hero -->
        <h1 class="text-4xl font-bold text-gray-900 mb-4">About DirectEdge</h1>
        <p class="text-lg text-gray-700 mb-6">
            DirectEdge is an innovative agricultural supply-chain platform built to connect
            <span class="text-green-600 font-semibold">farmers</span>,
            <span class="text-blue-600 font-semibold">distributors & storehouses</span>, and
            <span class="text-purple-600 font-semibold">shop owners</span> — reducing friction, improving transparency,
            and unlocking fairer prices across the food value chain.
        </p>

        <!-- Short summary -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
            <h2 class="text-2xl font-semibold mb-2">What we do</h2>
            <p class="text-gray-700">
                We provide a simple, reliable digital marketplace and logistics toolkit where farmers can list produce,
                agents can manage groups of farmers, distributors can optimize collection and delivery, and shop owners can
                run intelligent, free shop management. Our platform blends practical business tools with AI-powered features
                (image-based product recognition, demand forecasting, and route optimization) so every participant can
                trade faster, smarter, and with more confidence.
            </p>
        </div>

        <!-- Mission & Vision -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-2">🌱 Our Mission</h3>
                <p class="text-gray-700">
                    To remove unnecessary middlemen, give farmers direct access to markets, and build data-driven logistics
                    that reduce waste, lower costs, and stabilize prices for producers and retailers alike.
                </p>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-2">🚀 Our Vision</h3>
                <p class="text-gray-700">
                    A transparent agricultural ecosystem where technology empowers local producers, strengthens local supply,
                    and helps small shops remain competitive through modern inventory & demand tools.
                </p>
            </div>
        </div>

        <!-- How it works -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
            <h3 class="text-2xl font-semibold mb-3">How DirectEdge works — simple</h3>
            <ol class="list-decimal ml-5 space-y-2 text-gray-700">
                <li><strong>Farmers list produce</strong> (with quantity, expected harvest, and photos).</li>
                <li><strong>Agents verify</strong> quality & quantity and submit listings on behalf of smallholders.</li>
                <li><strong>Distributors</strong> pick up listed produce, store it, and deliver to shops based on optimized routes.</li>
                <li><strong>Shop owners</strong> browse available stock, place orders, and the system updates inventory automatically.</li>
            </ol>
        </div>

        <!-- Stakeholder sections -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">

            <!-- Farmers -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-2">For Farmers</h3>
                <p class="text-gray-700 mb-3">
                    DirectEdge helps small and medium farmers sell directly to verified buyers. We focus on fair pricing,
                    transparent transactions, and on-the-ground support.
                </p>
                <ul class="list-disc ml-5 text-gray-700 space-y-1">
                    <li><strong>Direct market access</strong> — list crops to reach distributors and shops without extra middlemen.</li>
                    <li><strong>Price visibility</strong> — see demand & recent prices so you can choose when and where to sell.</li>
                    <li><strong>Support & training</strong> — best-practice guides on packaging, storage and photo capture for AI recognition.</li>
                </ul>
                <div class="mt-4">
                    <a href="/register-farmer" class="inline-block px-4 py-2 rounded btn-green">Join as a Farmer</a>
                </div>
            </div>

            <!-- Agents -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-2">For Agents</h3>
                <p class="text-gray-700 mb-3">
                    Agents manage clusters of farmers and act as quality & volume verifiers. DirectEdge gives agents tools to
                    list, price and schedule pickups while tracking farmer performance.
                </p>
                <ul class="list-disc ml-5 text-gray-700 space-y-1">
                    <li><strong>Group management</strong> — add farmers, verify listings, and monitor yields from one dashboard.</li>
                    <li><strong>Pickup scheduling</strong> — coordinate collections with distributors and get delivery assignments.</li>
                    <li><strong>Data insights</strong> — see quality trends and get suggestions for crop health and post-harvest handling.</li>
                </ul>
                <div class="mt-4">
                    <a href="/register-agent" class="inline-block px-4 py-2 rounded btn-green">Become an Agent</a>
                </div>
            </div>
        </div>

        <!-- Distributors & Shop Owners -->
        <div class="grid md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-2">For Distributors & Warehouse Managers</h3>
                <p class="text-gray-700">
                    Optimize routes and warehouse capacity, minimize empty runs, and reduce transport costs with our routing
                    and inventory tools.
                </p>
                <ul class="ml-5 list-disc text-gray-700 space-y-1">
                    <li><strong>VRP-based routing</strong> — optimized pickups & deliveries.</li>
                    <li><strong>Real-time stock</strong> — track capacity and get low-space alerts.</li>
                    <li><strong>Priority scheduling</strong> — deliver where it’s most urgent based on demand & perishability.</li>
                </ul>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-xl font-semibold mb-2">For Shop Owners</h3>
                <p class="text-gray-700">
                    A free shop-management tool that replaces clunky manual records. Use camera-based product recognition,
                    automatic stock updates, and sales forecasting to keep shelves stocked and sales steady.
                </p>
                <ul class="ml-5 list-disc text-gray-700 space-y-1">
                    <li><strong>Camera-based POS</strong> — scan products using images instead of barcodes.</li>
                    <li><strong>Demand forecasting</strong> — restock wisely with AI-driven predictions.</li>
                    <li><strong>Purchase from platform</strong> — order directly from nearby warehouses.</li>
                </ul>
            </div>
        </div>

        <!-- Technology -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
            <h3 class="text-xl font-semibold mb-3">Technology powering DirectEdge</h3>
            <div class="grid md:grid-cols-2 gap-4 text-gray-700">
                <div>
                    <strong>Computer Vision</strong>
                    <p class="mt-1 text-sm">Image-based product recognition for fast POS operations and basic crop-quality checks.</p>
                </div>
                <div>
                    <strong>Machine Learning</strong>
                    <p class="mt-1 text-sm">Demand forecasting per shop and product, safety-stock suggestions, and anomaly detection.</p>
                </div>
                <div>
                    <strong>Optimization</strong>
                    <p class="mt-1 text-sm">Vehicle Routing Problem (VRP) solvers for efficient logistics and cost reductions.</p>
                </div>
                <div>
                    <strong>Design for low-connectivity</strong>
                    <p class="mt-1 text-sm">Lightweight mobile-first interfaces and offline-friendly flows for rural areas.</p>
                </div>
            </div>
        </div>

        <!-- Privacy, Safety and Payments -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
            <h3 class="text-xl font-semibold mb-3">Privacy, Safety & Payments</h3>
            <p class="text-gray-700 mb-2">
                We store only the minimum information required to run the platform. Personal data and farmer identities are protected
                — we never share contact details without permission. Financial settlements between parties are recorded on the platform,
                and payments/invoices are available through the admin dashboard.
            </p>
            <p class="text-gray-700 text-sm">
                <strong>Safety tips:</strong> Always verify pickup assignments and check the identity of drivers before handing over produce.
            </p>
        </div>

        <!-- How to start -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
            <h3 class="text-2xl font-semibold mb-3">How to get started (fast)</h3>
            <div class="grid md:grid-cols-3 gap-4 text-gray-700">
                <div class="p-3 border rounded">
                    <strong>1. Create account</strong>
                    <p class="text-sm mt-1">Register as a Farmer, Agent, Distributor or Shop Owner.</p>
                </div>
                <div class="p-3 border rounded">
                    <strong>2. Complete profile</strong>
                    <p class="text-sm mt-1">Add farm/warehouse/shop details and upload clear product photos.</p>
                </div>
                <div class="p-3 border rounded">
                    <strong>3. Start listing & trading</strong>
                    <p class="text-sm mt-1">Agents verify listings, distributors schedule pickups, and shops place orders.</p>
                </div>
            </div>
            <div class="mt-4">
                <a href="/register-farmer" class="inline-block px-4 py-2 rounded btn-green">Get Started — Farmers</a>
                <a href="/register-agent" class="inline-block ml-3 px-4 py-2 rounded border text-gray-700">Agent Signup</a>
            </div>
        </div>

        <!-- FAQs -->
        <div class="bg-white p-6 rounded-lg shadow-sm mb-8">
            <h3 class="text-xl font-semibold mb-3">Frequently asked questions</h3>
            <details class="mb-2">
                <summary class="font-medium cursor-pointer">How do I list a harvest?</summary>
                <p class="text-gray-700 mt-2 text-sm">Create a listing from your dashboard, add quantity, expected harvest date and a clear photo — then your agent or our verification team will confirm it for marketplace availability.</p>
            </details>
            <details class="mb-2">
                <summary class="font-medium cursor-pointer">How do I get paid?</summary>
                <p class="text-gray-700 mt-2 text-sm">Payment terms depend on agreements with buyers. The platform records transactions and can show invoices and settlement history to you and administrators.</p>
            </details>
            <details>
                <summary class="font-medium cursor-pointer">Is the platform offline-friendly?</summary>
                <p class="text-gray-700 mt-2 text-sm">Yes — key screens are designed for intermittent connectivity and will sync when a connection is available.</p>
            </details>
        </div>

        <!-- Contact & Footer CTA -->
        <div class="bg-white p-6 rounded-lg shadow-sm text-center">
            <h3 class="text-xl font-semibold mb-2">Have questions or need help?</h3>
            <p class="text-gray-700 mb-3">Email us at <a href="mailto:support@directedge.example" class="text-blue-600">support@directedge.example</a> or call <strong class="text-gray-900">+880 1X XXX XXXX</strong></p>
            <a href="/contact" class="inline-block px-4 py-2 rounded btn-green">Contact Support</a>
        </div>

        <p class="text-xs text-gray-400 mt-6">© DirectEdge — building fairer agricultural markets. Version 1.0</p>
    </div>
</body>