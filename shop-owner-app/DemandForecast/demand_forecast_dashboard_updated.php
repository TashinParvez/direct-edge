<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "direct-edge";

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

session_start();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');

    if ($_POST['action'] === 'get_demand_forecast') {
        $shop_id = $_POST['shop_id'] ?? 6;
        $product_id = $_POST['product_id'] ?? 0;

        if ($product_id > 0) {
            $sql = "SELECT df.*, p.name as product_name, p.category, p.img_url 
                    FROM demand_forecasts df 
                    JOIN products p ON df.product_id = p.product_id 
                    WHERE df.shop_id = ? AND df.product_id = ? 
                    AND df.forecast_date >= CURDATE() 
                    ORDER BY df.forecast_date LIMIT 30";

            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $shop_id, $product_id);
            $stmt->execute();
            $result = $stmt->get_result();

            $forecasts = [];
            while ($row = $result->fetch_assoc()) {
                $forecasts[] = $row;
            }

            echo json_encode($forecasts);
        } else {
            echo json_encode([]);
        }
        exit;
    }

    if ($_POST['action'] === 'get_forecast_products') {
        $shop_id = $_POST['shop_id'] ?? 6;

        $sql = "SELECT DISTINCT sp.product_id, p.name, p.category, p.img_url, sp.quantity, sp.selling_price,
                       COUNT(df.id) as forecast_count,
                       AVG(df.predicted_demand) as avg_predicted_demand
                FROM shop_products sp 
                JOIN products p ON sp.product_id = p.product_id 
                LEFT JOIN demand_forecasts df ON sp.product_id = df.product_id AND sp.shop_id = df.shop_id
                WHERE sp.shop_id = ? 
                GROUP BY sp.product_id, p.name, p.category, p.img_url, sp.quantity, sp.selling_price
                ORDER BY forecast_count DESC, p.name";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();

        $products = [];
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        echo json_encode($products);
        exit;
    }

    if ($_POST['action'] === 'run_ml_prediction') {
        $shop_id = $_POST['shop_id'] ?? 6;
        $product_id = $_POST['product_id'] ?? 0;

        // Generate sample predictions for demo
        $predictions = [];
        $base_demand = rand(5, 25);

        for ($i = 1; $i <= 30; $i++) {
            $date = date('Y-m-d', strtotime("+$i days"));
            $variance = (rand(-30, 30) / 100) * $base_demand;
            $demand = max(1, round($base_demand + $variance));

            // Weekend boost
            if (date('N', strtotime($date)) >= 6) {
                $demand = round($demand * 1.2);
            }

            $predictions[] = [
                'date' => $date,
                'predicted_demand' => $demand,
                'confidence' => round(0.7 + (rand(0, 25) / 100), 4)
            ];
        }

        // Save predictions to database
        $conn->autocommit(false);

        try {
            // Clear existing predictions
            $delete_sql = "DELETE FROM demand_forecasts WHERE shop_id = ? AND product_id = ? AND forecast_date >= CURDATE()";
            $delete_stmt = $conn->prepare($delete_sql);
            $delete_stmt->bind_param("ii", $shop_id, $product_id);
            $delete_stmt->execute();

            // Insert new predictions
            $insert_sql = "INSERT INTO demand_forecasts (forecast_date, shop_id, product_id, predicted_demand, confidence_score, model_used, forecast_period_days) VALUES (?, ?, ?, ?, ?, 'DEMO_MODEL', 30)";
            $insert_stmt = $conn->prepare($insert_sql);

            foreach ($predictions as $pred) {
                $insert_stmt->bind_param("siiid", $pred['date'], $shop_id, $product_id, $pred['predicted_demand'], $pred['confidence']);
                $insert_stmt->execute();
            }

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Predictions generated successfully', 'predictions' => $predictions]);
        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode(['success' => false, 'message' => 'Failed to save predictions: ' . $e->getMessage()]);
        }

        exit;
    }

    if ($_POST['action'] === 'get_forecast_summary') {
        $shop_id = $_POST['shop_id'] ?? 6;

        $sql = "SELECT 
                    COUNT(DISTINCT df.product_id) as products_with_forecast,
                    COUNT(*) as total_predictions,
                    AVG(df.predicted_demand) as avg_demand,
                    SUM(df.predicted_demand) as total_demand,
                    AVG(df.confidence_score) as avg_confidence
                FROM demand_forecasts df 
                WHERE df.shop_id = ? AND df.forecast_date >= CURDATE()";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $shop_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $summary = $result->fetch_assoc();

        echo json_encode($summary);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demand Forecasting - Shop Management</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f8f9fc;
            color: #333;
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .header h1 {
            font-size: 2.5rem;
            font-weight: 300;
            margin-bottom: 10px;
        }

        .header p {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .controls-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e3e6f0;
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            align-items: end;
        }

        .form-group {
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 16px;
            background: white;
            transition: all 0.3s ease;
        }

        .form-group select:focus,
        .form-group input:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-secondary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(245, 87, 108, 0.3);
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e3e6f0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 40px rgba(0, 0, 0, 0.12);
        }

        .product-card.active {
            border: 2px solid #667eea;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.05) 0%, rgba(118, 75, 162, 0.05) 100%);
        }

        .product-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
        }

        .product-image {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            background: #f8f9fc;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 1.5rem;
        }

        .product-info h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }

        .product-info p {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .product-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 15px;
        }

        .stat {
            text-align: center;
            padding: 10px;
            background: #f8f9fc;
            border-radius: 8px;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            color: #667eea;
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
        }

        .forecast-section {
            display: none;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border: 1px solid #e3e6f0;
        }

        .forecast-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
        }

        .forecast-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #374151;
        }

        .forecast-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .forecast-stat {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
        }

        .forecast-stat-value {
            font-size: 2rem;
            font-weight: 300;
            margin-bottom: 5px;
        }

        .forecast-stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .forecast-table {
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
        }

        .forecast-table table {
            width: 100%;
            border-collapse: collapse;
        }

        .forecast-table th {
            background: #f8f9fc;
            padding: 15px 20px;
            text-align: left;
            font-weight: 600;
            color: #374151;
            font-size: 0.9rem;
            border-bottom: 2px solid #e3e6f0;
        }

        .forecast-table td {
            padding: 15px 20px;
            border-bottom: 1px solid #f1f3f4;
            font-size: 0.9rem;
        }

        .forecast-table tbody tr:hover {
            background: #f8f9fc;
        }

        .confidence-high {
            color: #10b981;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
        }

        .confidence-medium {
            color: #f59e0b;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
        }

        .confidence-low {
            color: #ef4444;
            font-weight: 600;
            background: rgba(239, 68, 68, 0.1);
            padding: 4px 8px;
            border-radius: 6px;
        }

        .loading {
            text-align: center;
            padding: 40px;
            font-size: 1.1rem;
            color: #6b7280;
        }

        .loading::after {
            content: '';
            width: 40px;
            height: 40px;
            border: 4px solid #f3f3f3;
            border-top: 4px solid #667eea;
            border-radius: 50%;
            display: inline-block;
            animation: spin 1s linear infinite;
            margin-left: 15px;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #047857;
        }

        .alert-error {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            color: #dc2626;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state h3 {
            font-size: 1.5rem;
            margin-bottom: 10px;
            color: #374151;
        }

        @media (max-width: 768px) {
            .controls-grid {
                grid-template-columns: 1fr;
            }

            .products-grid {
                grid-template-columns: 1fr;
            }

            .forecast-stats {
                grid-template-columns: repeat(2, 1fr);
            }

            .header h1 {
                font-size: 2rem;
            }

            .container {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>📊 Demand Forecasting Dashboard</h1>
            <p>AI-powered demand prediction for your shop inventory</p>
        </div>

        <div class="controls-card">
            <div class="controls-grid">
                <div class="form-group">
                    <label for="shop-select">Select Shop</label>
                    <select id="shop-select">
                        <option value="2">Shwapno</option>
                        <option value="6" selected>Agora</option>
                        <option value="9">Amana Big Bazar</option>
                        <option value="12">Meena Bazar</option>
                        <option value="13">Unimart</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="forecast-days">Forecast Period (Days)</label>
                    <input type="number" id="forecast-days" value="30" min="7" max="90">
                </div>

                <div class="form-group">
                    <label>&nbsp;</label>
                    <button class="btn btn-primary" onclick="loadProducts()">
                        🔄 Refresh Products
                    </button>
                </div>
            </div>
        </div>

        <div id="alert-container"></div>

        <!-- Products Grid -->
        <div id="products-container">
            <div class="products-grid" id="products-grid">
                <div class="loading">Loading products...</div>
            </div>
        </div>

        <!-- Forecast Results Section -->
        <div class="forecast-section" id="forecast-section">
            <div class="forecast-header">
                <h2 class="forecast-title" id="forecast-title">📈 Demand Forecast</h2>
                <div>
                    <button class="btn btn-secondary" onclick="generateNewForecast()"
                        style="width: auto; margin-right: 10px;">
                        🚀 Generate New Forecast
                    </button>
                    <button class="btn btn-primary" onclick="exportForecast()" style="width: auto;">
                        📊 Export Data
                    </button>
                </div>
            </div>

            <div class="forecast-stats" id="forecast-stats">
                <!-- Stats will be populated by JavaScript -->
            </div>

            <div class="forecast-table">
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Predicted Demand</th>
                            <th>Confidence</th>
                            <th>Day</th>
                            <th>Notes</th>
                        </tr>
                    </thead>
                    <tbody id="forecast-body">
                        <!-- Forecast data will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        let selectedProductId = null;
        let selectedProductData = null;

        // Load products when page loads
        document.addEventListener('DOMContentLoaded', function() {
            loadProducts();

            // Reload products when shop changes
            document.getElementById('shop-select').addEventListener('change', loadProducts);
        });

        function loadProducts() {
            const shopId = document.getElementById('shop-select').value;
            const productsGrid = document.getElementById('products-grid');

            productsGrid.innerHTML = '<div class="loading">Loading products...</div>';

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=get_forecast_products&shop_id=${shopId}`
                })
                .then(response => response.json())
                .then(products => {
                    if (products.length === 0) {
                        productsGrid.innerHTML = `
                        <div class="empty-state">
                            <h3>No Products Found</h3>
                            <p>No products available for this shop.</p>
                        </div>
                    `;
                        return;
                    }

                    productsGrid.innerHTML = '';
                    products.forEach(product => {
                        const productCard = createProductCard(product);
                        productsGrid.appendChild(productCard);
                    });
                })
                .catch(error => {
                    console.error('Error loading products:', error);
                    showAlert('Failed to load products', 'error');
                    productsGrid.innerHTML = `
                    <div class="empty-state">
                        <h3>Error Loading Products</h3>
                        <p>Please try refreshing the page.</p>
                    </div>
                `;
                });
        }

        function createProductCard(product) {
            const card = document.createElement('div');
            card.className = 'product-card';
            card.onclick = () => selectProduct(product);

            const hasForecasts = product.forecast_count > 0;
            const forecastStatus = hasForecasts ? '✅ Has Forecast' : '❌ No Forecast';
            const avgDemand = hasForecasts ? Math.round(product.avg_predicted_demand || 0) : 0;

            card.innerHTML = `
                <div class="product-header">
                    <div class="product-image">
                        ${getProductEmoji(product.category)}
                    </div>
                    <div class="product-info">
                        <h3>${product.name}</h3>
                        <p>${product.category}</p>
                    </div>
                </div>
                <div class="product-stats">
                    <div class="stat">
                        <div class="stat-value">${product.quantity}</div>
                        <div class="stat-label">Stock</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value">${avgDemand}</div>
                        <div class="stat-label">Avg Demand</div>
                    </div>
                </div>
                <div style="margin-top: 15px; font-size: 0.9rem; color: ${hasForecasts ? '#10b981' : '#ef4444'}; font-weight: 600;">
                    ${forecastStatus}
                </div>
            `;

            return card;
        }

        function getProductEmoji(category) {
            const emojiMap = {
                'Fruits': '🍎',
                'Vegetables': '🥕',
                'Snacks': '🍿',
                'Beverages': '🥤',
                'Dairy': '🥛',
                'Meat': '🥩',
                'Bakery': '🍞',
                'Seafood': '🐟'
            };
            return emojiMap[category] || '📦';
        }

        function selectProduct(product) {
            // Remove active class from all cards
            document.querySelectorAll('.product-card').forEach(card => {
                card.classList.remove('active');
            });

            // Add active class to selected card
            event.currentTarget.classList.add('active');

            selectedProductId = product.product_id;
            selectedProductData = product;

            loadExistingForecast();
        }

        function loadExistingForecast() {
            if (!selectedProductId) {
                showAlert('Please select a product', 'error');
                return;
            }

            const shopId = document.getElementById('shop-select').value;
            const forecastSection = document.getElementById('forecast-section');
            const forecastBody = document.getElementById('forecast-body');

            forecastSection.style.display = 'block';
            forecastBody.innerHTML = '<tr><td colspan="5" class="loading">Loading forecast data...</td></tr>';

            document.getElementById('forecast-title').textContent = `📈 ${selectedProductData.name} - Demand Forecast`;

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=get_demand_forecast&shop_id=${shopId}&product_id=${selectedProductId}`
                })
                .then(response => response.json())
                .then(forecasts => {
                    if (forecasts.length > 0) {
                        showAlert(`Loaded forecast data for ${selectedProductData.name}`, 'success');
                        displayForecast(forecasts.map(f => ({
                            date: f.forecast_date,
                            predicted_demand: f.predicted_demand,
                            confidence: f.confidence_score
                        })));
                    } else {
                        forecastBody.innerHTML = `
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px;">
                                <h3>No forecast data available</h3>
                                <p>Click "Generate New Forecast" to create predictions for this product</p>
                            </td>
                        </tr>
                    `;
                        updateForecastStats({
                            total_demand: 0,
                            avg_daily_demand: 0,
                            peak_demand: 0,
                            avg_confidence: 0
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading forecast:', error);
                    showAlert('Failed to load forecast data', 'error');
                });
        }

        function generateNewForecast() {
            if (!selectedProductId) {
                showAlert('Please select a product first', 'error');
                return;
            }

            const shopId = document.getElementById('shop-select').value;
            const forecastBody = document.getElementById('forecast-body');

            showAlert('Generating new forecast using ML models...', 'success');
            forecastBody.innerHTML = '<tr><td colspan="5" class="loading">Generating predictions...</td></tr>';

            fetch('', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: `action=run_ml_prediction&shop_id=${shopId}&product_id=${selectedProductId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showAlert(`New forecast generated for ${selectedProductData.name}!`, 'success');
                        displayForecast(data.predictions);
                    } else {
                        showAlert(data.message || 'Failed to generate forecast', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error generating forecast:', error);
                    showAlert('Failed to generate forecast', 'error');
                });
        }

        function displayForecast(predictions) {
            const tbody = document.getElementById('forecast-body');
            tbody.innerHTML = '';

            let totalDemand = 0;
            let avgConfidence = 0;
            let peakDemand = 0;

            predictions.forEach((pred, index) => {
                const date = new Date(pred.date);
                const dayName = date.toLocaleDateString('en-US', {
                    weekday: 'long'
                });
                const demand = parseInt(pred.predicted_demand);
                const confidence = parseFloat(pred.confidence);

                totalDemand += demand;
                avgConfidence += confidence;
                peakDemand = Math.max(peakDemand, demand);

                const confidenceClass = confidence > 0.8 ? 'confidence-high' :
                    confidence > 0.6 ? 'confidence-medium' : 'confidence-low';

                const confidenceText = confidence > 0.8 ? 'High' :
                    confidence > 0.6 ? 'Medium' : 'Low';

                const notes = dayName.includes('Saturday') || dayName.includes('Sunday') ?
                    '🎪 Weekend' : index < 7 ? '📅 Week 1' :
                    index < 14 ? '📅 Week 2' : index < 21 ? '📅 Week 3' : '📅 Week 4';

                const row = `
                    <tr>
                        <td><strong>${pred.date}</strong></td>
                        <td><strong>${demand}</strong> units</td>
                        <td><span class="${confidenceClass}">${confidenceText}</span></td>
                        <td>${dayName}</td>
                        <td>${notes}</td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            // Update stats
            avgConfidence = avgConfidence / predictions.length;
            const avgDailyDemand = Math.round(totalDemand / predictions.length);

            updateForecastStats({
                total_demand: totalDemand,
                avg_daily_demand: avgDailyDemand,
                peak_demand: peakDemand,
                avg_confidence: Math.round(avgConfidence * 100)
            });
        }

        function updateForecastStats(stats) {
            const statsContainer = document.getElementById('forecast-stats');
            statsContainer.innerHTML = `
                <div class="forecast-stat">
                    <div class="forecast-stat-value">${stats.total_demand}</div>
                    <div class="forecast-stat-label">Total Predicted Demand</div>
                </div>
                <div class="forecast-stat">
                    <div class="forecast-stat-value">${stats.avg_daily_demand}</div>
                    <div class="forecast-stat-label">Average Daily Demand</div>
                </div>
                <div class="forecast-stat">
                    <div class="forecast-stat-value">${stats.peak_demand}</div>
                    <div class="forecast-stat-label">Peak Day Demand</div>
                </div>
                <div class="forecast-stat">
                    <div class="forecast-stat-value">${stats.avg_confidence}%</div>
                    <div class="forecast-stat-label">Average Confidence</div>
                </div>
            `;
        }

        function exportForecast() {
            if (!selectedProductId) {
                showAlert('Please select a product and generate forecast first', 'error');
                return;
            }

            // Simple CSV export
            const table = document.querySelector('#forecast-section table');
            let csv = [];
            const rows = table.querySelectorAll('tr');

            for (let i = 0; i < rows.length; i++) {
                const row = [],
                    cols = rows[i].querySelectorAll('td, th');
                for (let j = 0; j < cols.length; j++) {
                    row.push(cols[j].innerText);
                }
                csv.push(row.join(','));
            }

            const csvFile = new Blob([csv.join('\n')], {
                type: 'text/csv'
            });
            const downloadLink = document.createElement('a');
            downloadLink.download = `forecast-${selectedProductData.name}-${new Date().toISOString().split('T')[0]}.csv`;
            downloadLink.href = window.URL.createObjectURL(csvFile);
            downloadLink.style.display = 'none';
            document.body.appendChild(downloadLink);
            downloadLink.click();
            document.body.removeChild(downloadLink);

            showAlert('Forecast data exported successfully!', 'success');
        }

        function showAlert(message, type) {
            const alertContainer = document.getElementById('alert-container');
            const alertClass = type === 'error' ? 'alert-error' : 'alert-success';

            alertContainer.innerHTML = `
                <div class="alert ${alertClass}">
                    ${message}
                </div>
            `;

            // Auto-hide after 5 seconds
            setTimeout(() => {
                alertContainer.innerHTML = '';
            }, 5000);
        }
    </script>
</body>

</html>