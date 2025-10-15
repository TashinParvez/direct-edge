<?php
ob_start();
include '../include/connect-db.php';
include '../include/navbar.php';

$agent_id = isset($user_id) ? $user_id : 45;

// Handle farmer deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $delete_id = (int)$_POST['id'];

    if ($delete_id > 0) {
        $del_stmt = $conn->prepare("DELETE FROM farmers WHERE id = ?");
        $del_stmt->bind_param("i", $delete_id);

        if ($del_stmt->execute()) {
            $del_stmt->close();
            $conn->close();
            header("Location: farmers-list.php?deleted=1");
            exit;
        } else {
            echo "<script>alert('Error deleting farmer. Please try again.');</script>";
        }

        $del_stmt->close();
        $conn->close();
    }
}

// Connect database
include '../include/connect-db.php';

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
function displayField($label, $value, $default = "Not provided")
{
    return $value ? $value : "<span class='text-gray-400'>$default</span>";
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($farmer['full_name']); ?> - Farmer Profile - Stock Integrated</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        .profile-card {
            transition: all 0.3s ease;
        }

        .profile-card:hover {
            background-color: #f9fafb;
        }

        .profile-picture {
            transition: all 0.3s ease;
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .info-section {
            transition: all 0.3s ease;
        }

        .info-section:hover {
            background-color: #f3f4f6;
        }

        .field-item {
            transition: all 0.2s ease;
        }

        .field-item:hover {
            background-color: #f9fafb;
            padding-left: 0.75rem;
        }

        .badge-farmer-type {
            animation: pulseGlow 2s infinite;
        }

        @keyframes pulseGlow {

            0%,
            100% {
                box-shadow: 0 0 5px rgba(34, 197, 94, 0.3);
            }

            50% {
                box-shadow: 0 0 15px rgba(34, 197, 94, 0.6);
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }

            body {
                background: white !important;
            }

            .shadow-lg {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
        }
    </style>

    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">
</head>

<body class="bg-gray-100">

    <section class="home-section mx-2 md:mx-8 lg:mx-16 pb-4">
        <div class="flex justify-between items-center p-4 no-print">
            <h1 class="text-2xl font-bold">Farmer Profile</h1>
            <div class="flex space-x-2">
                <a href="edit-farmer.php?id=<?php echo $farmer['id']; ?>" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class='bx bx-edit'></i> Edit Profile
                </a>
                <!-- <button onclick="window.print()" class="bg-gray-500 text-white px-3 py-1 rounded text-sm hover:bg-gray-600 no-print">
                    <i class='bx bx-printer'></i> Print
                </button> -->
                <form action="" method="POST" onsubmit="return confirm('Are you sure you want to delete this farmer? This action cannot be undone.');" class="inline">
                    <input type="hidden" name="id" value="<?php echo $farmer['id']; ?>">
                    <button type="submit" class="bg-red-500 text-white px-3 py-1 rounded text-sm hover:bg-red-600">
                        <i class='bx bx-trash'></i> Delete
                    </button>
                </form>
            </div>
        </div>

        <div class="container mx-auto px-4">
            <!-- Profile Header -->
            <div class="bg-white shadow-lg rounded-lg mb-6 p-6 profile-card">
                <div class="flex flex-col md:flex-row items-center md:items-start space-y-4 md:space-y-0 md:space-x-6">
                    <div class="flex-shrink-0">
                        <?php if ($farmer['profile_picture'] && file_exists($farmer['profile_picture'])): ?>
                            <img src="<?php echo htmlspecialchars($farmer['profile_picture']); ?>"
                                alt="Profile Picture"
                                class="w-32 h-32 rounded-full object-cover border-4 border-green-200 profile-picture">
                        <?php else: ?>
                            <div class="w-32 h-32 bg-green-200 rounded-full flex items-center justify-center profile-picture">
                                <i class='bx bx-user text-4xl text-green-600'></i>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="flex-grow text-center md:text-left">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">
                            <?php echo htmlspecialchars($farmer['full_name']); ?>
                        </h2>
                        <div class="flex flex-wrap justify-center md:justify-start gap-4 text-sm text-gray-600 mb-3">
                            <?php if ($farmer['nid_number']): ?>
                                <span class="flex items-center">
                                    <i class='bx bx-id-card mr-2'></i>
                                    NID: <?php echo htmlspecialchars($farmer['nid_number']); ?>
                                </span>
                            <?php endif; ?>
                            <span class="flex items-center">
                                <i class='bx bx-phone mr-2'></i>
                                <?php echo htmlspecialchars($farmer['contact_number']); ?>
                            </span>
                            <?php if ($farmer['dob']): ?>
                                <span class="flex items-center">
                                    <i class='bx bx-cake mr-2'></i>
                                    <?php echo date('d M Y', strtotime($farmer['dob'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="mt-3">
                            <span class="inline-block bg-green-100 text-green-800 px-3 py-1 rounded-full text-sm font-medium badge-farmer-type">
                                <?php echo htmlspecialchars($farmer['farmer_type']); ?> Farmer
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

                <!-- Personal Information -->
                <div class="bg-white shadow-lg rounded-lg p-6 info-section">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class='bx bx-user-circle text-green-600 mr-2'></i>
                        Personal Information
                    </h3>
                    <div class="space-y-4">
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Full Name</label>
                            <p class="text-gray-800 font-medium"><?php echo displayField('', $farmer['full_name']); ?></p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Date of Birth</label>
                            <p class="text-gray-800"><?php echo $farmer['dob'] ? date('d M Y', strtotime($farmer['dob'])) : '<span class="text-gray-400">Not provided</span>'; ?></p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">NID Number</label>
                            <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['nid_number']); ?></p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Contact Number</label>
                            <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['contact_number']); ?></p>
                        </div>
                        <div class="field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Present Address</label>
                            <p class="text-gray-800"><?php echo displayField('', $farmer['present_address']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Farming Information -->
                <div class="bg-white shadow-lg rounded-lg p-6 info-section">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class='bx bx-leaf text-green-600 mr-2'></i>
                        Farming Information
                    </h3>
                    <div class="space-y-4">
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Farmer Type</label>
                            <p class="text-gray-800 font-medium"><?php echo displayField('', $farmer['farmer_type']); ?></p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Crops Cultivated</label>
                            <p class="text-gray-800"><?php echo displayField('', $farmer['crops_cultivated']); ?></p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Land Size</label>
                            <p class="text-gray-800 font-medium">
                                <?php echo $farmer['land_size'] ? $farmer['land_size'] . ' acres/bigha' : '<span class="text-gray-400">Not provided</span>'; ?>
                            </p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Land Ownership</label>
                            <p class="text-gray-800"><?php echo displayField('', $farmer['land_ownership']); ?></p>
                        </div>
                        <div class="field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Fertilizer & Pesticide Usage</label>
                            <p class="text-gray-800"><?php echo displayField('', $farmer['fertilizer_usage']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Financial Information -->
                <div class="bg-white shadow-lg rounded-lg p-6 info-section">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class='bx bx-credit-card text-green-600 mr-2'></i>
                        Financial Information
                    </h3>
                    <div class="space-y-4">
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Bank Account Number</label>
                            <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['bank_account']); ?></p>
                        </div>
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Mobile Banking Account</label>
                            <p class="text-gray-800 font-mono"><?php echo displayField('', $farmer['mobile_banking_account']); ?></p>
                        </div>
                        <div class="field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Average Selling Price</label>
                            <p class="text-gray-800 font-medium"><?php echo displayField('', $farmer['avg_selling_price']); ?></p>
                        </div>
                    </div>
                </div>

                <!-- Training & Additional Info -->
                <div class="bg-white shadow-lg rounded-lg p-6 info-section">
                    <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class='bx bx-book-reader text-green-600 mr-2'></i>
                        Training & Additional Info
                    </h3>
                    <div class="space-y-4">
                        <div class="border-b pb-3 field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Training Received</label>
                            <p class="text-gray-800"><?php echo displayField('', $farmer['training_received']); ?></p>
                        </div>
                        <div class="<?php echo $has_created_at ? 'border-b pb-3' : ''; ?> field-item rounded p-2">
                            <label class="block text-sm font-medium text-gray-500">Additional Notes</label>
                            <p class="text-gray-800"><?php echo displayField('', $farmer['additional_notes']); ?></p>
                        </div>
                        <?php if ($has_created_at && isset($farmer['created_at'])): ?>
                            <div class="pt-4 border-t field-item rounded p-2">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class='bx bx-calendar mr-2'></i>
                                    <span>Profile created:
                                        <?php echo date('d M Y, g:i A', strtotime($farmer['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Mobile Action Buttons -->
            <div class="mt-6 flex justify-center space-x-4 md:hidden no-print">
                <a href="edit-farmer.php?id=<?php echo $farmer['id']; ?>"
                    class="bg-blue-500 text-white px-4 py-2 rounded text-sm hover:bg-blue-600 transition-colors">
                    <i class='bx bx-edit mr-2'></i> Edit Profile
                </a>
                <button onclick="window.print()"
                    class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600 transition-colors">
                    <i class='bx bx-printer mr-2'></i> Print
                </button>
            </div>
        </div>
    </section>

    <?php include '../include/footer.php'; ?>

</body>

</html>