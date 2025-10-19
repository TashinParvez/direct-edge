<?php
include '../include/connect-db.php';
include('../include/navbar.php');


$sql = "SELECT `user_id` as shop_id,`full_name`,`image_url`
        FROM `users` WHERE `role` = 'Shop-Owner'";

$result = mysqli_query($conn, $sql);
$allshops = mysqli_fetch_all($result, MYSQLI_ASSOC);


// echo '<pre>';
// print_r($allshops);
// echo '</pre>';


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - DirectEdge</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-50">

    <div class="max-w-7xl mx-auto px-6 py-12 pt-24">

        <!--================== Welcome Section ==================-->
        <div class="text-center mb-12">
            <h1 class="text-4xl font-bold text-gray-900">Welcome to Your <span style="color: green;">DirectEdge</span> 🎉</h1>
            <p class="text-lg text-gray-600 mt-4">
                Explore shops powered by DirectEdge. Shop authentic, fresh, and natural goods straight from the source from trusted retailers.
            </p>
        </div>


        <!--================== Shops Grid ==================-->
        <div class="grid md:grid-cols-3 sm:grid-cols-2 grid-cols-1 gap-8">

            <?php foreach ($allshops as $shop): ?>
                <a href="../shop-owner-app/Profuct-for-buyers-from-shop/Available-Products-List.php?shop_id=<?php echo $shop['shop_id']; ?>"
                    class="block">
                    <div class="bg-white rounded-xl shadow-lg p-6 flex flex-col items-center hover:shadow-xl transition cursor-pointer">
                        <img src="../<?php echo $shop['image_url']; ?>"
                            alt="<?php echo $shop['full_name']; ?>"
                            class="w-32 h-32 object-cover rounded-lg mb-4">
                        <h3 class="text-xl font-semibold text-gray-800">
                            <?php echo $shop['full_name']; ?>
                        </h3>
                    </div>
                </a>
            <?php endforeach; ?>

        </div>
    </div>

</body>

</html>