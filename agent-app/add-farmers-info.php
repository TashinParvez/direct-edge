<?php

include '../include/navbar.php';
$agent_id = isset($user_id) ? $user_id : 45;

// connect database
include '../include/connect-db.php'; // Database connection


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $full_name = trim($_POST['full_name']);
    $dob = $_POST['dob'];
    $nid_number = trim($_POST['nid_number']);
    $contact_number = trim($_POST['contact_number']);
    $present_address = trim($_POST['present_address']);
    $farmer_type = $_POST['farmer_type'];
    $crops_cultivated = trim($_POST['crops_cultivated']);
    $land_size = $_POST['land_size'];
    $land_ownership = $_POST['land_ownership'];
    $fertilizer_usage = trim($_POST['fertilizer_usage']);
    $bank_account = trim($_POST['bank_account']);
    $mobile_banking_account = trim($_POST['mobile_banking_account']);
    $training_received = trim($_POST['training_received']);
    $avg_selling_price = trim($_POST['avg_selling_price']);
    $additional_notes = trim($_POST['additional_notes']);


    // Basic validation
    $errors = [];
    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    if (empty($contact_number)) {
        $errors[] = "Contact number is required.";
    }


    // Handle profile picture
    $profile_picture = "";
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);


        // Generate unique filename to avoid conflicts
        $file_extension = pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION);
        $unique_filename = time() . '_' . uniqid() . '.' . $file_extension;
        $profile_picture = $target_dir . $unique_filename;


        if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture)) {
            $errors[] = "Failed to upload profile picture.";
            $profile_picture = "";
        }
    }


    // Insert into DB if no errors
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO farmers 
            (full_name, dob, nid_number, contact_number, present_address, profile_picture, farmer_type, crops_cultivated, land_size, land_ownership, fertilizer_usage, bank_account, mobile_banking_account, training_received, avg_selling_price, additional_notes, agent_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");


        $stmt->bind_param(
            "ssssssssdsissssss",
            $full_name,
            $dob,
            $nid_number,
            $contact_number,
            $present_address,
            $profile_picture,
            $farmer_type,
            $crops_cultivated,
            $land_size,
            $land_ownership,
            $fertilizer_usage,
            $bank_account,
            $mobile_banking_account,
            $training_received,
            $avg_selling_price,
            $additional_notes,
            $agent_id
        );


        if ($stmt->execute()) {
            // $farmer_id = $conn->insert_id();
            $message = "Farmer information saved successfully!";
            $message_type = "success";
            $show_actions = true;
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    } else {
        $message = implode(" ", $errors);
        $message_type = "error";
    }
}


$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Farmer Information Form - Stock Integrated</title>
    <link rel="icon" type="image/x-icon" href="../Logo/LogoBG.png">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="../assets/Logo/favicon.png">

    <style>
        .form-container {
            transition: all 0.3s ease;
        }

        .form-container:hover {
            background-color: #f9fafb;
        }

        .form-field {
            transition: all 0.3s ease;
        }

        .form-field:hover {
            background-color: #f3f4f6;
        }

        .success-actions {
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media print {
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>

<body class="bg-gray-100">

    <div class="max-w-6xl mx-auto px-6 md:px-12">

        <section class="home-section pb-4">
            <div class="flex justify-between items-center p-4">
                <h1 class="text-2xl font-bold">Farmer Information Form</h1>
                <!-- <a href="farmers-list.php" class="bg-green-500 text-white px-3 py-1 rounded text-sm hover:bg-green-600">
                    <i class='bx bx-list-ul'></i> View All Farmers
                </a> -->
            </div>

            <?php if (isset($message)): ?>
                <div class="mx-4 mb-4 <?php echo $message_type == 'success' ? 'bg-green-100 border border-green-400 text-green-700' : 'bg-red-100 border border-red-400 text-red-700'; ?> px-4 py-3 rounded">
                    <div class="flex items-center">
                        <i class='bx <?php echo $message_type == 'success' ? 'bx-check-circle' : 'bx-error-circle'; ?> mr-2'></i>
                        <?php echo $message; ?>
                    </div>

                    <?php if (isset($show_actions) && $show_actions): ?>
                        <div class="mt-4 flex flex-wrap gap-3 success-actions">
                            <a href="farmer-profile.php?id=<?php echo $farmer_id; ?>"
                                class="bg-blue-500 text-white px-4 py-2 rounded text-sm hover:bg-blue-600 transition-colors">
                                <i class='bx bx-show'></i> View Profile
                            </a>
                            <a href="farmers-list.php"
                                class="bg-green-500 text-white px-4 py-2 rounded text-sm hover:bg-green-600 transition-colors">
                                <i class='bx bx-list-ul'></i> All Farmers
                            </a>
                            <a href="add-farmers-info.php"
                                class="bg-gray-500 text-white px-4 py-2 rounded text-sm hover:bg-gray-600 transition-colors">
                                <i class='bx bx-plus'></i> Add Another
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>


            <div class="bg-white shadow-lg rounded-lg p-6 form-container">
                <form action="add-farmers-info.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Full Name *</label>
                        <input type="text" name="full_name" placeholder="Enter full name" required
                            value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Date of Birth</label>
                        <input type="date" name="dob"
                            value="<?php echo isset($_POST['dob']) ? $_POST['dob'] : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">NID Number</label>
                        <input type="text" name="nid_number" placeholder="Enter NID number"
                            value="<?php echo isset($_POST['nid_number']) ? htmlspecialchars($_POST['nid_number']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Contact Number *</label>
                        <input type="tel" name="contact_number" placeholder="01XXXXXXXXX" required
                            value="<?php echo isset($_POST['contact_number']) ? htmlspecialchars($_POST['contact_number']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Present Address</label>
                        <textarea name="present_address" rows="3" placeholder="Village, Union, Upazila, District"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field"><?php echo isset($_POST['present_address']) ? htmlspecialchars($_POST['present_address']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Profile Picture</label>
                        <input type="file" name="profile_picture" accept="image/*"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                        <p class="text-xs text-gray-500 mt-1">Maximum file size: 2MB (JPG, PNG, GIF)</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type of Farmer</label>
                        <select name="farmer_type" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                            <option value="Small" <?php echo (isset($_POST['farmer_type']) && $_POST['farmer_type'] == 'Small') ? 'selected' : ''; ?>>Small</option>
                            <option value="Medium" <?php echo (isset($_POST['farmer_type']) && $_POST['farmer_type'] == 'Medium') ? 'selected' : ''; ?>>Medium</option>
                            <option value="Large" <?php echo (isset($_POST['farmer_type']) && $_POST['farmer_type'] == 'Large') ? 'selected' : ''; ?>>Large</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Types of Crops Cultivated</label>
                        <input type="text" name="crops_cultivated" placeholder="e.g., Rice, Wheat, Vegetables"
                            value="<?php echo isset($_POST['crops_cultivated']) ? htmlspecialchars($_POST['crops_cultivated']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Farming Land Size</label>
                        <input type="number" name="land_size" placeholder="Land size in acres/bigha" step="0.01"
                            value="<?php echo isset($_POST['land_size']) ? $_POST['land_size'] : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Land Ownership</label>
                        <select name="land_ownership" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                            <option value="Own Land" <?php echo (isset($_POST['land_ownership']) && $_POST['land_ownership'] == 'Own Land') ? 'selected' : ''; ?>>Own Land</option>
                            <option value="Leased Land" <?php echo (isset($_POST['land_ownership']) && $_POST['land_ownership'] == 'Leased Land') ? 'selected' : ''; ?>>Leased Land</option>
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Fertilizer & Pesticide Usage</label>
                        <textarea name="fertilizer_usage" rows="3" placeholder="Details about usage"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field"><?php echo isset($_POST['fertilizer_usage']) ? htmlspecialchars($_POST['fertilizer_usage']) : ''; ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Bank Account Number</label>
                        <input type="text" name="bank_account" placeholder="Bank account number"
                            value="<?php echo isset($_POST['bank_account']) ? htmlspecialchars($_POST['bank_account']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mobile Banking Account Number</label>
                        <input type="text" name="mobile_banking_account" placeholder="Optional mobile banking number"
                            value="<?php echo isset($_POST['mobile_banking_account']) ? htmlspecialchars($_POST['mobile_banking_account']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Any Training Received</label>
                        <input type="text" name="training_received" placeholder="e.g., Agricultural Training"
                            value="<?php echo isset($_POST['training_received']) ? htmlspecialchars($_POST['training_received']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Average Selling Price for a Specific Crop</label>
                        <input type="text" name="avg_selling_price" placeholder="e.g., Rice - 35 BDT/kg"
                            value="<?php echo isset($_POST['avg_selling_price']) ? htmlspecialchars($_POST['avg_selling_price']) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Additional Notes</label>
                        <textarea name="additional_notes" rows="3" placeholder="Extra notes"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 form-field"><?php echo isset($_POST['additional_notes']) ? htmlspecialchars($_POST['additional_notes']) : ''; ?></textarea>
                    </div>

                    <div class="md:col-span-2 flex justify-center">
                        <button type="submit" class="bg-green-500 text-white px-8 py-3 rounded hover:bg-green-600 transition-colors font-semibold">
                            <i class='bx bx-save mr-2'></i>Submit Information
                        </button>
                    </div>

                </form>

                <!-- Form Helper Text -->
                <div class="mt-6 text-center text-sm text-gray-500">
                    <p><span class="text-red-500">*</span> indicates required fields</p>
                </div>
            </div>
    </div>
    </section>

    <?php include '../include/footer.php'; ?>
</body>

</html>