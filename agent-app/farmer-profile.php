<?php
// Connect database
include '../include/connect-db.php'; // Database connection

// Get farmer ID from URL parameter
$farmer_id = isset($_GET['id']) ? (int)$_GET['id'] : 1;

if ($farmer_id <= 0) {
    die("Invalid farmer ID");
}

// Fetch farmer information
$stmt = $conn->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Farmer not found");
}

$farmer = $result->fetch_assoc();
$stmt->close();

// Check if created_at column exists
$columns_query = "SHOW COLUMNS FROM farmers LIKE 'created_at'";
$has_created_at = $conn->query($columns_query)->num_rows > 0;

$conn->close();

// Helper function to display field
function displayField($label, $value, $default = "Not provided") {
    return $value ? $value : "<span class='text-gray-400'>$default</span>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($farmer['full_name']); ?> - Farmer Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen py-8">
    <div class="px-8 md:px-16 lg:px-24 xl:px-32 2xl:px-40">
    <div class="max-w-6xl mx-auto px-12 py-4">

        <!-- Header Section -->
        <div class="bg-white rounded-2xl shadow-xl mb-6 p-6">
            <div class="flex items-center justify-between mb-4">
                <a href="farmers-list.php" class="flex items-center text-green-600 hover:text-green-800 transition">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Farmers List
                </a>
                <div class="flex space-x-3">
                    <a href="edit-farmer.php?id=<?php echo $farmer['id']; ?>" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                        <i class="fas fa-edit mr-1"></i> Edit Profile
                    </a>
                    <button onclick="window.print()" class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition">
                        <i class="fas fa-print mr-1"></i> Print
                    </button>
                </div>
            </div>

            <!-- Profile Header -->
            <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                <div class="flex-shrink-0">
                    <?php if ($farmer['profile_picture'] && file_exists($farmer['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($farmer['profile_picture']); ?>" 
                             alt="Profile Picture" 
                             class="w-32 h-32 rounded-full object-cover border-4 border-green-200">
                    <?php else: ?>
                        <div class="w-32 h-32 bg-green-200 rounded-full flex items-center justify-center">
                            <i class="fas fa-user text-4xl text-green-600"></i>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="flex-grow text-center md:text-left">
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <?php echo htmlspecialchars($farmer['full_name']); ?>
                    </h1>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-600">
                        <?php if ($farmer['nid_number']): ?>
                            <span class="flex items-center">
                                <i class="fas fa-id-card mr-2"></i>
                                NID: <?php echo htmlspecialchars($farmer['nid_number']); ?>
                            </span>
                        <?php endif; ?>
                        <span class="flex items-center">
                            <i class="fas fa-phone mr-2"></i>
                            <?php echo htmlspecialchars($farmer['contact_number']); ?>
                        </span>
                        <?php if ($farmer['dob']): ?>
                            <span class="flex items-center">
                                <i class="fas fa-birthday-cake mr-2"></i>
                                <?php echo date('d M Y', strtotime($farmer['dob'])); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="mt-3">
                        <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium">
                            <?php echo htmlspecialchars($farmer['farmer_type']); ?> Farmer
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            <!-- Personal Information -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-circle text-green-600 mr-2"></i>
                    Personal Information
                </h2>

                <div class="space-y-4">
                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Full Name</label>
                        <p class="text-gray-800 font-medium"><?php echo displayField('', $farmer['full_name']); ?></p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Date of Birth</label>
                        <p class="text-gray-800"><?php echo $farmer['dob'] ? date('d M Y', strtotime($farmer['dob'])) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">NID Number</label>
                        <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['nid_number']); ?></p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Contact Number</label>
                        <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['contact_number']); ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Present Address</label>
                        <p class="text-gray-800"><?php echo displayField('', $farmer['present_address']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Farming Information -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-seedling text-green-600 mr-2"></i>
                    Farming Information
                </h2>

                <div class="space-y-4">
                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Farmer Type</label>
                        <p class="text-gray-800 font-medium"><?php echo displayField('', $farmer['farmer_type']); ?></p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Crops Cultivated</label>
                        <p class="text-gray-800"><?php echo displayField('', $farmer['crops_cultivated']); ?></p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Land Size</label>
                        <p class="text-gray-800 font-medium">
                            <?php echo $farmer['land_size'] ? $farmer['land_size'] . ' acres/bigha' : '<span class="text-gray-400">Not provided</span>'; ?>
                        </p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Land Ownership</label>
                        <p class="text-gray-800"><?php echo displayField('', $farmer['land_ownership']); ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Fertilizer & Pesticide Usage</label>
                        <p class="text-gray-800"><?php echo displayField('', $farmer['fertilizer_usage']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Financial Information -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-credit-card text-green-600 mr-2"></i>
                    Financial Information
                </h2>

                <div class="space-y-4">
                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Bank Account Number</label>
                        <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['bank_account']); ?></p>
                    </div>

                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Mobile Banking Account</label>
                        <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['mobile_banking_account']); ?></p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-500">Average Selling Price</label>
                        <p class="text-gray-800 font-medium"><?php echo displayField('', $farmer['avg_selling_price']); ?></p>
                    </div>
                </div>
            </div>

            <!-- Training & Additional Info -->
            <div class="bg-white rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-graduation-cap text-green-600 mr-2"></i>
                    Training & Additional Info
                </h2>

                <div class="space-y-4">
                    <div class="border-b pb-3">
                        <label class="block text-sm font-medium text-gray-500">Training Received</label>
                        <p class="text-gray-800"><?php echo displayField('', $farmer['training_received']); ?></p>
                    </div>

                    <div class="<?php echo $has_created_at ? 'border-b pb-3' : ''; ?>">
                        <label class="block text-sm font-medium text-gray-500">Additional Notes</label>
                        <p class="text-gray-800"><?php echo displayField('', $farmer['additional_notes']); ?></p>
                    </div>

                    <!-- Registration Date (only if created_at column exists) -->
                    <?php if ($has_created_at && isset($farmer['created_at'])): ?>
                        <div class="pt-4 border-t">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-calendar-alt mr-2"></i>
                                <span>Profile created: 
                                    <?php echo date('d M Y, g:i A', strtotime($farmer['created_at'])); ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Action Buttons (Mobile) -->
        <div class="mt-6 flex justify-center space-x-4 md:hidden">
            <a href="edit-farmer.php?id=<?php echo $farmer['id']; ?>" 
               class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-2"></i> Edit Profile
            </a>
            <button onclick="window.print()" 
                    class="bg-gray-600 text-white px-3 py-1 rounded text-sm hover:bg-gray-700 transition">
                <i class="fas fa-print mr-2"></i> Print
            </button>
        </div>
    </div>

    <!-- Print Styles -->
    <style>
        @media print {
            .no-print, button, a[href*="edit"], a[href*="list"] {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .shadow-xl {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
        }
    </style>
    </div>
</body>
</html>