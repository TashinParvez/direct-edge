<?php
// Connect database
include '../include/connect-db.php'; // Database connection

// Get farmer ID from URL parameter
$farmer_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($farmer_id <= 0) {
    die("Invalid farmer ID");
}

// Handle form submission for update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $full_name = $_POST['full_name'];
    $dob = $_POST['dob'];
    $nid_number = $_POST['nid_number'];
    $contact_number = $_POST['contact_number'];
    $present_address = $_POST['present_address'];
    $farmer_type = $_POST['farmer_type'];
    $crops_cultivated = $_POST['crops_cultivated'];
    $land_size = $_POST['land_size'];
    $land_ownership = $_POST['land_ownership'];
    $fertilizer_usage = $_POST['fertilizer_usage'];
    $bank_account = $_POST['bank_account'];
    $mobile_banking_account = $_POST['mobile_banking_account'];
    $training_received = $_POST['training_received'];
    $avg_selling_price = $_POST['avg_selling_price'];
    $additional_notes = $_POST['additional_notes'];

    // Handle profile picture update
    $profile_picture = $_POST['existing_profile_picture']; // Keep existing by default
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture);
    }

    // Update in DB
    $stmt = $conn->prepare("UPDATE farmers SET 
        full_name = ?, dob = ?, nid_number = ?, contact_number = ?, present_address = ?, 
        profile_picture = ?, farmer_type = ?, crops_cultivated = ?, land_size = ?, 
        land_ownership = ?, fertilizer_usage = ?, bank_account = ?, mobile_banking_account = ?, 
        training_received = ?, avg_selling_price = ?, additional_notes = ?, updated_at = NOW()
        WHERE id = ?");

    $stmt->bind_param("ssssssssdsisssssi", 
        $full_name, $dob, $nid_number, $contact_number, $present_address, 
        $profile_picture, $farmer_type, $crops_cultivated, $land_size, 
        $land_ownership, $fertilizer_usage, $bank_account, $mobile_banking_account, 
        $training_received, $avg_selling_price, $additional_notes, $farmer_id);

    if ($stmt->execute()) {
        $message = "Farmer information updated successfully!";
        $message_type = "success";
    } else {
        $message = "Error updating farmer information: " . $stmt->error;
        $message_type = "error";
    }
    $stmt->close();
}

// Fetch current farmer information
$stmt = $conn->prepare("SELECT * FROM farmers WHERE id = ?");
$stmt->bind_param("i", $farmer_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die("Farmer not found");
}

$farmer = $result->fetch_assoc();
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Farmer - <?php echo htmlspecialchars($farmer['full_name']); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center py-8">
    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl p-8">

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-green-700 flex items-center">
                    <i class="fas fa-user-edit mr-3"></i>
                    Edit Farmer Information
                </h2>
                <p class="text-gray-600 mt-1">Update information for <?php echo htmlspecialchars($farmer['full_name']); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="farmer-profile.php?id=<?php echo $farmer_id; ?>" 
                   class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 transition">
                    <i class="fas fa-eye mr-1"></i> View Profile
                </a>
                <a href="farmers-list.php" 
                   class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition">
                    <i class="fas fa-list mr-1"></i> All Farmers
                </a>
            </div>
        </div>

        <!-- Success/Error Message -->
        <?php if (isset($message)): ?>
            <div class="mb-6 p-4 rounded-lg <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?>">
                <div class="flex items-center">
                    <i class="fas <?php echo $message_type == 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    <?php echo $message; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Current Profile Picture -->
        <?php if ($farmer['profile_picture'] && file_exists($farmer['profile_picture'])): ?>
            <div class="mb-6 text-center">
                <p class="text-sm text-gray-600 mb-2">Current Profile Picture:</p>
                <img src="<?php echo htmlspecialchars($farmer['profile_picture']); ?>" 
                     alt="Current Profile" 
                     class="w-24 h-24 rounded-full mx-auto object-cover border-2 border-green-200">
            </div>
        <?php endif; ?>

        <!-- Edit Form -->
        <form action="edit-farmer.php?id=<?php echo $farmer_id; ?>" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Hidden field to keep existing profile picture -->
            <input type="hidden" name="existing_profile_picture" value="<?php echo htmlspecialchars($farmer['profile_picture']); ?>">

            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name *</label>
                <input type="text" name="full_name" value="<?php echo htmlspecialchars($farmer['full_name']); ?>" 
                       placeholder="Enter full name" required
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                <input type="date" name="dob" value="<?php echo $farmer['dob']; ?>" 
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">NID Number</label>
                <input type="text" name="nid_number" value="<?php echo htmlspecialchars($farmer['nid_number']); ?>" 
                       placeholder="Enter NID number" 
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Contact Number *</label>
                <input type="tel" name="contact_number" value="<?php echo htmlspecialchars($farmer['contact_number']); ?>" 
                       placeholder="01XXXXXXXXX" required
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Present Address</label>
                <textarea name="present_address" rows="2" placeholder="Village, Union, Upazila, District"
                          class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"><?php echo htmlspecialchars($farmer['present_address']); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Update Profile Picture</label>
                <input type="file" name="profile_picture" accept="image/*" 
                       class="mt-1 w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:outline-none">
                <p class="text-xs text-gray-500 mt-1">Leave empty to keep current picture</p>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Type of Farmer</label>
                <select name="farmer_type" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <option value="Small" <?php echo $farmer['farmer_type'] == 'Small' ? 'selected' : ''; ?>>Small</option>
                    <option value="Medium" <?php echo $farmer['farmer_type'] == 'Medium' ? 'selected' : ''; ?>>Medium</option>
                    <option value="Large" <?php echo $farmer['farmer_type'] == 'Large' ? 'selected' : ''; ?>>Large</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Types of Crops Cultivated</label>
                <input type="text" name="crops_cultivated" value="<?php echo htmlspecialchars($farmer['crops_cultivated']); ?>" 
                       placeholder="e.g., Rice, Wheat, Vegetables"
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Farming Land Size</label>
                <input type="number" name="land_size" value="<?php echo $farmer['land_size']; ?>" 
                       placeholder="Land size in acres/bigha" step="0.01"
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Land Ownership</label>
                <select name="land_ownership" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <option value="Own Land" <?php echo $farmer['land_ownership'] == 'Own Land' ? 'selected' : ''; ?>>Own Land</option>
                    <option value="Leased Land" <?php echo $farmer['land_ownership'] == 'Leased Land' ? 'selected' : ''; ?>>Leased Land</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Fertilizer & Pesticide Usage</label>
                <textarea name="fertilizer_usage" rows="2" placeholder="Details about usage"
                          class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"><?php echo htmlspecialchars($farmer['fertilizer_usage']); ?></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                <input type="text" name="bank_account" value="<?php echo htmlspecialchars($farmer['bank_account']); ?>" 
                       placeholder="Bank account number"
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Mobile Banking Account Number</label>
                <input type="text" name="mobile_banking_account" value="<?php echo htmlspecialchars($farmer['mobile_banking_account']); ?>" 
                       placeholder="Optional mobile banking number"
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Any Training Received</label>
                <input type="text" name="training_received" value="<?php echo htmlspecialchars($farmer['training_received']); ?>" 
                       placeholder="e.g., Agricultural Training"
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Average Selling Price for a Specific Crop</label>
                <input type="text" name="avg_selling_price" value="<?php echo htmlspecialchars($farmer['avg_selling_price']); ?>" 
                       placeholder="e.g., Rice - 35 BDT/kg"
                       class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Additional Notes</label>
                <textarea name="additional_notes" rows="3" placeholder="Extra notes"
                          class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"><?php echo htmlspecialchars($farmer['additional_notes']); ?></textarea>
            </div>

            <!-- Action Buttons -->
            <div class="md:col-span-2 flex justify-center space-x-4">
                <button type="submit" class="px-8 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-400 transition">
                    <i class="fas fa-save mr-2"></i>Update Information
                </button>
                <a href="farmer-profile.php?id=<?php echo $farmer_id; ?>" 
                   class="px-8 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 focus:ring-2 focus:ring-gray-400 transition">
                    <i class="fas fa-times mr-2"></i>Cancel
                </a>
            </div>
        </form>
    </div>
</body>
</html>