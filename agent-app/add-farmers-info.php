<?php
// connect database
include '../include/connect-db.php'; // Database connection
// $servername = 'localhost';
// $username = 'root';
// $password = '';
// $databasename = 'direct-edge';


// // connection obj
// $conn = mysqli_connect($servername, $username, $password, $databasename);

// // check connection
// if (!$conn) {
//     die("Sorry failed to connect: " . mysqli_connect_error());
// }

// Handle form submission
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

    // Handle profile picture
    $profile_picture = "";
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
        $target_dir = "uploads/";
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);
        $profile_picture = $target_dir . basename($_FILES["profile_picture"]["name"]);
        move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $profile_picture);
    }

    // Insert into DB
    $stmt = $conn->prepare("INSERT INTO farmers 
        (full_name, dob, nid_number, contact_number, present_address, profile_picture, farmer_type, crops_cultivated, land_size, land_ownership, fertilizer_usage, bank_account, mobile_banking_account, training_received, avg_selling_price, additional_notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param(
        "ssssssssdsisssss",
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
        $additional_notes
    );

    if ($stmt->execute()) {
        $message = "Farmer information saved successfully!";
    } else {
        $message = "Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Farmer Information Form</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">

    <div class="w-full max-w-4xl bg-white rounded-2xl shadow-xl p-8">

        <h2 class="text-2xl font-bold text-green-700 mb-6 text-center">Farmer Information Form</h2>

        <?php if (isset($message)) echo "<p class='text-center mb-4 text-green-600 font-semibold'>$message</p>"; ?>

        <form action="add-farmers-info.php" method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <div>
                <label class="block text-sm font-medium text-gray-700">Full Name</label>
                <input type="text" name="full_name" placeholder="Enter full name" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Date of Birth</label>
                <input type="date" name="dob" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">NID Number</label>
                <input type="text" name="nid_number" placeholder="Enter NID number" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Contact Number</label>
                <input type="tel" name="contact_number" placeholder="01XXXXXXXXX" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Present Address</label>
                <textarea name="present_address" rows="2" placeholder="Village, Union, Upazila, District" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Profile Picture</label>
                <input type="file" name="profile_picture" accept="image/*" class="mt-1 w-full p-2 rounded-lg border border-gray-300 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Type of Farmer</label>
                <select name="farmer_type" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <option>Small</option>
                    <option>Medium</option>
                    <option>Large</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Types of Crops Cultivated</label>
                <input type="text" name="crops_cultivated" placeholder="e.g., Rice, Wheat, Vegetables" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Farming Land Size</label>
                <input type="number" name="land_size" placeholder="Land size in acres/bigha" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Land Ownership</label>
                <select name="land_ownership" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
                    <option>Own Land</option>
                    <option>Leased Land</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Fertilizer & Pesticide Usage</label>
                <textarea name="fertilizer_usage" rows="2" placeholder="Details about usage" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Bank Account Number</label>
                <input type="text" name="bank_account" placeholder="Bank account number" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Mobile Banking Account Number</label>
                <input type="text" name="mobile_banking_account" placeholder="Optional mobile banking number" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Any Training Received</label>
                <input type="text" name="training_received" placeholder="e.g., Agricultural Training" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Average Selling Price for a Specific Crop</label>
                <input type="text" name="avg_selling_price" placeholder="e.g., Rice - 35 BDT/kg" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none">
            </div>

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700">Additional Notes</label>
                <textarea name="additional_notes" rows="3" placeholder="Extra notes" class="mt-1 w-full rounded-lg border border-gray-300 p-3 focus:ring-2 focus:ring-green-500 focus:outline-none"></textarea>
            </div>

            <div class="md:col-span-2 flex justify-center">
                <button type="submit" class="px-6 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 focus:ring-2 focus:ring-green-400 transition">
                    Submit Information
                </button>
            </div>

        </form>
    </div>

</body>

</html>