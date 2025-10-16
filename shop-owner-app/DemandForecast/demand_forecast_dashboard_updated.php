<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "direct-edge";

// include '../../include/connect-db.php';
include '../../include/navbar.php';
// $admin_id = isset($user_id) ? $user_id : 65;

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// session_start();

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
    <title>Demand Forecasting Dashboard</title>

    <link rel="stylesheet" href="../../Include/sidebar.css">
    <style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    body {
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        background: #f5f5f5;
        color: #1f2a44;
        line-height: 1.5;
    }

    .container {
        max-width: 1280px;
        margin: 0 auto;
        padding: 24px;
    }

    .header {
        padding: 32px;
        text-align: left;
        margin-bottom: 32px;
    }

    .header h1 {
        font-size: 28px;
        font-weight: 600;
        color: #1f2a44;
    }

    .header p {
        font-size: 16px;
        color: #64748b;
        margin-top: 8px;
    }

    .controls-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        margin-bottom: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .controls-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 16px;
        align-items: end;
    }

    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 500;
        color: #1f2a44;
        margin-bottom: 8px;
    }

    .form-group select,
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        font-size: 14px;
        background: #ffffff;
        transition: border-color 0.2s;
    }

    .form-group select:focus,
    .form-group input:focus {
        outline: none;
        border-color: #3b82f6;
    }

    .btn {
        padding: 10px 16px;
        border: none;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 500;
        cursor: pointer;
        transition: background-color 0.2s, transform 0.1s;
    }

    .btn-primary {
        background: #3b82f6;
        color: #ffffff;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-secondary {
        background: #6b7280;
        color: #ffffff;
    }

    .btn-secondary:hover {
        background: #4b5563;
    }

    .products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .product-card {
        background: #ffffff;
        border-radius: 12px;
        padding: 16px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        transition: transform 0.2s, box-shadow 0.2s;
        cursor: pointer;
    }

    .product-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .product-card.active {
        border: 1px solid #3b82f6;
    }

    .product-header {
        display: flex;
        align-items: center;
        margin-bottom: 12px;
    }

    .product-image {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #f1f5f9;
        margin-right: 12px;
    }

    .product-info h3 {
        font-size: 16px;
        font-weight: 600;
        color: #1f2a44;
    }

    .product-info p {
        font-size: 14px;
        color: #64748b;
    }

    .product-stats {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-top: 12px;
    }

    .stat {
        padding: 8px;
        background: #f1f5f9;
        border-radius: 6px;
        text-align: center;
    }

    .stat-value {
        font-size: 16px;
        font-weight: 600;
        color: #3b82f6;
    }

    .stat-label {
        font-size: 12px;
        color: #64748b;
    }

    .forecast-section {
        display: none;
        background: #ffffff;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .forecast-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .forecast-title {
        font-size: 20px;
        font-weight: 600;
        color: #1f2a44;
    }

    .forecast-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }

    .forecast-stat {
        background: #f1f5f9;
        padding: 16px;
        border-radius: 8px;
        text-align: center;
    }

    .forecast-stat-value {
        font-size: 24px;
        font-weight: 500;
        color: #1f2a44;
    }

    .forecast-stat-label {
        font-size: 14px;
        color: #64748b;
    }

    .forecast-table {
        border-radius: 8px;
        overflow: hidden;
    }

    .forecast-table table {
        width: 100%;
        border-collapse: collapse;
    }

    .forecast-table th {
        background: #f1f5f9;
        padding: 12px;
        text-align: left;
        font-size: 14px;
        font-weight: 500;
        color: #1f2a44;
        border-bottom: 1px solid #e2e8f0;
    }

    .forecast-table td {
        padding: 12px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 14px;
    }

    .forecast-table tbody tr:hover {
        background: #f8fafc;
    }

    .confidence-high {
        color: #15803d;
        font-weight: 500;
        background: #dcfce7;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .confidence-medium {
        color: #b45309;
        font-weight: 500;
        background: #fef3c7;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .confidence-low {
        color: #b91c1c;
        font-weight: 500;
        background: #fee2e2;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .loading {
        text-align: center;
        padding: 32px;
        font-size: 14px;
        color: #64748b;
    }

    .loading::after {
        content: '';
        width: 24px;
        height: 24px;
        border: 3px solid #e2e8f0;
        border-top: 3px solid #3b82f6;
        border-radius: 50%;
        display: inline-block;
        animation: spin 1s linear infinite;
        margin-left: 8px;
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
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 16px;
        font-size: 14px;
        font-weight: 500;
    }

    .alert-success {
        background: #dcfce7;
        color: #15803d;
    }

    .alert-error {
        background: #fee2e2;
        color: #b91c1c;
    }

    .empty-state {
        text-align: center;
        padding: 48px;
        color: #64748b;
    }

    .empty-state h3 {
        font-size: 18px;
        color: #1f2a44;
        margin-bottom: 8px;
    }

    @media (max-width: 768px) {
        .controls-grid {
            grid-template-columns: 1fr;
        }

        .products-grid {
            grid-template-columns: 1fr;
        }

        .forecast-stats {
            grid-template-columns: 1fr;
        }

        .header h1 {
            font-size: 24px;
        }

        .container {
            padding: 16px;
        }
    }
    </style>
</head>

<body>

    <div class="container">
        <div class="header">
            <h1>Demand Forecasting Dashboard</h1>
            <p>AI-powered demand prediction for shop inventory</p>
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
                    <button class="btn btn-primary" onclick="loadProducts()">Refresh Products</button>
                </div>
            </div>
        </div>

        <div id="alert-container"></div>

        <div id="products-container">
            <div class="products-grid" id="products-grid">
                <div class="loading">Loading products...</div>
            </div>
        </div>

        <div class="forecast-section" id="forecast-section">
            <div class="forecast-header">
                <h2 class="forecast-title" id="forecast-title">Demand Forecast</h2>
                <div>
                    <button class="btn btn-secondary" onclick="generateNewForecast()"
                        style="margin-right: 8px;">Generate New Forecast</button>
                    <button class="btn btn-primary" onclick="exportForecast()">Export Data</button>
                </div>
            </div>

            <div class="forecast-stats" id="forecast-stats"></div>

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
                    <tbody id="forecast-body"></tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    let selectedProductId = null;
    let selectedProductData = null;

    document.addEventListener('DOMContentLoaded', function() {
        loadProducts();
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
        const forecastStatus = hasForecasts ? 'Has Forecast' : 'No Forecast';
        const avgDemand = hasForecasts ? Math.round(product.avg_predicted_demand || 0) : 0;

        card.innerHTML = `
            <div class="product-header">
                <div class="product-image"></div>
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
            <div style="margin-top: 12px; font-size: 14px; color: ${hasForecasts ? '#15803d' : '#b91c1c'}; font-weight: 500;">
                ${forecastStatus}
            </div>
        `;

        return card;
    }

    function selectProduct(product) {
        document.querySelectorAll('.product-card').forEach(card => {
            card.classList.remove('active');
        });

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

        document.getElementById('forecast-title').textContent = `${selectedProductData.name} - Demand Forecast`;

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
                            <td colspan="5" style="text-align: center; padding: 32px;">
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
                'Weekend' : index < 7 ? 'Week 1' :
                index < 14 ? 'Week 2' : index < 21 ? 'Week 3' : 'Week 4';

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

        setTimeout(() => {
            alertContainer.innerHTML = '';
        }, 5000);
    }
    </script>
</body>

</html>