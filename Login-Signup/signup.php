<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$databasename = "direct-edge";

$conn = mysqli_connect($servername, $username, $password, $databasename);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['username'];
    $email = $_POST['mail'];
    $phone = $_POST['phonenumber'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['usertype'];

    // Initialize image_url as null
    $image_url = null;

    // Allowed ENUM values
    // $allowed_roles = ['Admin', 'Shop-Owner', 'Agent', 'User'];
    $allowed_roles = ['Admin', 'Shop-Owner', 'Agent', 'User'];
    if (!in_array($role, $allowed_roles)) {
        $message = "Invalid role selected!";
    } elseif ($password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        // Handle file upload if a file was selected
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'uploads/profile_pictures/';

            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_tmp_path = $_FILES['profile_picture']['tmp_name'];
            $file_name = $_FILES['profile_picture']['name'];
            $file_size = $_FILES['profile_picture']['size'];
            $file_type = $_FILES['profile_picture']['type'];
            $file_name_cmps = explode(".", $file_name);
            $file_extension = strtolower(end($file_name_cmps));

            // Allowed file extensions
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($file_extension, $allowed_extensions)) {
                // Create unique filename
                $new_file_name = uniqid() . '_' . time() . '.' . $file_extension;
                $dest_path = $upload_dir . $new_file_name;

                // Check file size (limit to 5MB)
                if ($file_size < 5000000) {
                    if (move_uploaded_file($file_tmp_path, $dest_path)) {
                        $image_url = $dest_path;
                    } else {
                        $message = "❌ Error uploading profile picture.";
                    }
                } else {
                    $message = "❌ File size too large. Maximum size is 5MB.";
                }
            } else {
                $message = "❌ Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
            }
        }

        // If no error with file upload, proceed with user registration
        if ($message === "") {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert query with image_url
            $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, image_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $full_name, $email, $phone, $hashed_password, $role, $image_url);

            if ($stmt->execute()) {
                // $message = "Sign up successful!";
                header("Location: login.php");
                exit();
            } else {
                $message = "❌ Error: " . $stmt->error;

                // If there was an error and we uploaded a file, delete it
                if ($image_url && file_exists($image_url)) {
                    unlink($image_url);
                }
            }

            $stmt->close();
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up DirectEdge</title>
    <link rel="icon" type="image/x-icon" href="../Logo/Favicon.png">
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-[#eff2f9] flex items-center justify-center min-h-screen">

    <div class="bg-white shadow rounded-lg p-6 md:p-10 flex flex-col md:flex-row items-center gap-8 w-full max-w-5xl">

        <div class="flex-1 flex justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="350" height="250" viewBox="0 0 841.59024 589">
                <polygon points="743.208 574.193 729.128 574.192 722.43 519.885 743.21 519.886 743.208 574.193" fill="#ffb8b8" />
                <path d="M926.00334,743.34094l-45.39888-.00169V742.765A17.67147,17.67147,0,0,1,898.275,725.0948h.00112l27.72809.00112Z" transform="translate(-179.20488 -155.5)" fill="#2f2e41" />
                <polygon points="805.349 539.578 796.21 550.289 750.55 520.133 764.039 504.326 805.349 539.578" fill="#ffb8b8" />
                <path d="M997.26727,701.20576l-29.46919,34.5344-.43682-.37273a17.67143,17.67143,0,0,1-1.97223-24.91172l.00073-.00086,17.99885-21.09237Z" transform="translate(-179.20488 -155.5)" fill="#2f2e41" />
                <polygon points="643.236 307.117 638.853 347.537 816.024 345.01 738.759 219.114 675.673 216.848 643.236 307.117" fill="#2f2e41" />
                <polygon points="643.236 307.117 638.853 347.537 816.024 345.01 738.759 219.114 675.673 216.848 643.236 307.117" opacity="0.28" />
                <path d="M857.27966,469.55618l-6.25477,36.98405v33.05813L901.455,556.05955s-5.90374,128.40409,1.96683,136.58258l-.19337,19.74247,22,1,.17941-13.89387L934.735,595.635l5.49349-76.25041-8.90664-57.8109Z" transform="translate(-179.20488 -155.5)" fill="#2f2e41" />
                <path d="M896.35029,501.39223l-45.3254,38.20613s-2.6527,68.93184,4.08383,77.01567c5.786,6.94318,81.03774,66.83187,81.03774,66.83187s13.37442,12.26792,20.86947,12.46353,16.21255-15.52483,16.21255-15.52483l-17.35829-13.18827S910.901,625.22041,897.54884,608.5302c-5.38922-6.73653-5.38922-7.41018-5.38922-7.41018l1.84061-28.01427,18.52508-51.99127Z" transform="translate(-179.20488 -155.5)" fill="#2f2e41" />
                <polygon points="669.024 162.885 649.482 161.079 631.756 164.033 594.222 164.045 591.024 191.249 625.277 198.914 633.624 194.823 668.419 208.08 669.024 162.885" fill="#2f2e41" />
                <path d="M871.56928,318.29985s41.47357-3.88605,50.90471,3.52413,29.64072,154.26646,29.64072,154.26646l-80.54543,4.54583-14.4396-9.93505,4.0988-73.31662Z" transform="translate(-179.20488 -155.5)" fill="#ccc" />
                <polygon points="705.024 161.885 659.275 162.889 616.622 355.62 650.305 363.031 705.024 161.885" fill="#2f2e41" />
                <polygon points="737.546 160.256 773.583 171.535 771.536 250.722 816.024 345.396 763.725 350.905 722.386 248.675 737.546 160.256" fill="#2f2e41" />
                <path d="M976.17566,510.51266a11.51329,11.51329,0,0,0-.43624-17.64888l7.24981-25.2913-14.29491-8.15427-9.73651,35.86a11.5757,11.5757,0,0,0,17.21785,15.23444Z" transform="translate(-179.20488 -155.5)" fill="#ffb8b8" />
                <path d="M931.90513,329.23416l21.32335-1.84956s19.1431,20.02168,23.4982,35.52394,9.07066,64.00526,9.07066,64.00526l-1.96706,70.95509L958.90513,480.9427l-1.67172-61.03065Z" transform="translate(-179.20488 -155.5)" fill="#2f2e41" />
                <circle cx="900.23883" cy="279.0718" r="30.2266" transform="translate(44.35273 779.63092) rotate(-61.33681)" fill="#ffb8b8" />
                <path d="M896.86876,266.45286c-3.96584-.91637-8.08269.09909-12.09946.75737s-8.44336.87528-11.84109-1.36586c-2.50928-1.65512-4.052-4.39327-5.5007-7.02712a8.08807,8.08807,0,0,1-1.18324-3.10061c-.17057-1.94791,1.06856-3.72514,2.3844-5.17153,6.112-6.71846,15.04376-10.39563,24.051-11.56369a41.55433,41.55433,0,0,1,21.96117,2.68176c6.83748,2.99893,12.71514,8.49079,15.40335,15.45628a43.8945,43.8945,0,0,1,2.30051,11.03757,32.97038,32.97038,0,0,1,.15507,8.35909,35.83247,35.83247,0,0,1-1.61995,6.056l-4.453,13.446a8.85863,8.85863,0,0,1-1.751,3.466,3.02927,3.02927,0,0,1-3.54735.77718,11.03957,11.03957,0,0,0,.15962-4.46052,3.76376,3.76376,0,0,0-2.96974-2.99493c-1.64132-.17418-3.04344,1.09814-4.60042,1.64611a5.65951,5.65951,0,0,1-7.03462-3.81764q-.06393-.21546-.11053-.43552c-.24579-1.53722.1669-3.18288-.41576-4.62647-.68269-1.69135-2.493-2.57757-3.853-3.79294a8.86863,8.86863,0,0,1-2.4334-9.21678c.36409-1.119,1.39236-1.62773,1.18665-2.91325C900.78734,267.96287,898.30087,266.78376,896.86876,266.45286Z" transform="translate(-179.20488 -155.5)" fill="#2f2e41" />
                <path d="M560.81664,394.22516H179.20488V155.5H560.81664Z" transform="translate(-179.20488 -155.5)" fill="#fff" />
                <path d="M560.81664,394.22516H179.20488V155.5H560.81664ZM180.9474,392.48264H559.07412V157.24252H180.9474Z" transform="translate(-179.20488 -155.5)" fill="#3f3d56" />
                <path d="M514.20424,724.86865H296.38931V486.14349H514.20424Z" transform="translate(-179.20488 -155.5)" fill="#fff" />
                <path d="M514.20424,724.86865H296.38931V486.14349H514.20424Zm-216.07241-1.74252H512.46172V487.886H298.13183Z" transform="translate(-179.20488 -155.5)" fill="#3f3d56" />
                <rect x="260.07105" y="546.82166" width="45.30551" height="1.8367" fill="#3f3d56" />
                <rect x="260.07105" y="552.33175" width="45.30551" height="1.8367" fill="#3f3d56" />
                <rect x="260.07105" y="557.84189" width="45.30551" height="1.8367" fill="#3f3d56" />
                <path d="M405.2968,528.835v19.16772a57.50315,57.50315,0,0,1,40.66088,98.164l13.55361,13.55361A76.67086,76.67086,0,0,0,405.2968,528.835Z" transform="translate(-179.20488 -155.5)" fill="#e6e6e6" />
                <path d="M459.51129,659.72034l-13.55361-13.55361a57.48875,57.48875,0,0,1-75.4511,5.11572l-11.60149,15.26513A76.66891,76.66891,0,0,0,459.51129,659.72034Z" transform="translate(-179.20488 -155.5)" fill="#575a89" />
                <path d="M347.79366,605.50585a57.50312,57.50312,0,0,1,57.50314-57.50314V528.835a76.6701,76.6701,0,0,0-46.39171,137.71259l11.60152-15.26513A57.403,57.403,0,0,1,347.79366,605.50585Z" transform="translate(-179.20488 -155.5)" fill="#6c63ff" />
                <path d="M836.57034,576.7545H618.75541V338.02891H836.57034Z" transform="translate(-179.20488 -155.5)" fill="#fff" />
                <path d="M836.57034,576.7545H618.75541V338.02891H836.57034ZM620.49793,575.012H834.82782V339.77143H620.49793Z" transform="translate(-179.20488 -155.5)" fill="#3f3d56" />
                <rect x="470.9159" y="328.46512" width="39.20669" height="62.7307" fill="#575a89" />
                <rect x="527.54778" y="285.77339" width="39.20669" height="105.42243" fill="#e6e6e6" />
                <rect x="584.17967" y="236.11159" width="39.20669" height="155.08423" fill="#6c63ff" />
                <polygon points="636.455 392.939 460.461 392.939 460.461 210.845 462.203 210.845 462.203 391.196 636.455 391.196 636.455 392.939" fill="#3f3d56" />
                <circle cx="594.0236" cy="176.8846" r="12" fill="#ffb8b8" />
                <path d="M1019.79512,744.5h-187a1,1,0,0,1,0-2h187a1,1,0,0,1,0,2Z" transform="translate(-179.20488 -155.5)" fill="#ccc" />
                <path d="M507.81987,344.2986H232.20165a.86259.86259,0,0,1-.86256-.86255V211.33223a.86256.86256,0,0,1,1.72512,0V342.57349H507.81987a.86256.86256,0,1,1,0,1.72511Z" transform="translate(-179.20488 -155.5)" fill="#3f3d56" />
                <path d="M291.08926,334.81045H266.02071a2.56337,2.56337,0,0,1-2.56052-2.5603V297.693a2.56337,2.56337,0,0,1,2.56052-2.5603h25.06855a2.56337,2.56337,0,0,1,2.56051,2.5603v34.55712A2.56337,2.56337,0,0,1,291.08926,334.81045Z" transform="translate(-179.20488 -155.5)" fill="#6c63ff" />
                <path d="M336.8049,334.81045H311.73635a2.56336,2.56336,0,0,1-2.56051-2.5603V264.91577a2.56337,2.56337,0,0,1,2.56051-2.5603H336.8049a2.56337,2.56337,0,0,1,2.56051,2.5603v67.33438A2.56336,2.56336,0,0,1,336.8049,334.81045Z" transform="translate(-179.20488 -155.5)" fill="#6c63ff" />
                <path d="M382.52054,334.81045H357.452a2.56337,2.56337,0,0,1-2.56051-2.5603V297.693a2.56337,2.56337,0,0,1,2.56051-2.5603h25.06855a2.56337,2.56337,0,0,1,2.56052,2.5603v34.55712A2.56337,2.56337,0,0,1,382.52054,334.81045Z" transform="translate(-179.20488 -155.5)" fill="#6c63ff" />
                <path d="M428.23619,334.81045H403.16763a2.50734,2.50734,0,0,1-2.56051-2.44431V251.8614a2.50734,2.50734,0,0,1,2.56051-2.44432h25.06856a2.50734,2.50734,0,0,1,2.56051,2.44432v80.50474A2.50734,2.50734,0,0,1,428.23619,334.81045Z" transform="translate(-179.20488 -155.5)" fill="#6c63ff" />
                <path d="M473.95183,334.81045H448.88328a2.56336,2.56336,0,0,1-2.56051-2.5603V228.68828a2.56337,2.56337,0,0,1,2.56051-2.5603h25.06855a2.56337,2.56337,0,0,1,2.56051,2.5603V332.25015A2.56337,2.56337,0,0,1,473.95183,334.81045Z" transform="translate(-179.20488 -155.5)" fill="#6c63ff" />
                <circle cx="99.3501" cy="124.10666" r="5.17536" fill="#3f3d56" />
                <circle cx="145.06574" cy="90.46685" r="5.17536" fill="#3f3d56" />
                <circle cx="190.78139" cy="124.10666" r="5.17536" fill="#3f3d56" />
                <circle cx="236.49703" cy="74.07822" r="5.17536" fill="#3f3d56" />
                <circle cx="282.21267" cy="55.10191" r="5.17536" fill="#3f3d56" />
                <polygon points="190.89 125.266 145.066 90.943 99.867 124.797 98.833 123.416 145.066 88.787 190.672 122.947 235.993 73.352 236.175 73.278 281.89 54.858 282.535 56.459 237.001 74.805 190.89 125.266" fill="#3f3d56" />
            </svg>
        </div>

        <!-- Form Section -->
        <div class="flex-1 w-full">
            <h2 class="text-2xl font-bold text-center mb-6">Sign Up As Shop Owner</h2>

            <?php if ($message != "") { ?>
                <p class="text-center mb-4 font-medium <?php echo (strpos($message, '✅') !== false) ? 'text-green-600' : 'text-red-600'; ?>">
                    <?php echo $message; ?>
                </p>
            <?php } ?>

            <form action="" method="post" enctype="multipart/form-data" class="space-y-4">

                <div>
                    <label for="username" class="block text-sm font-medium">Full Name</label>
                    <input type="text" id="username" name="username" placeholder="Mahbub" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="mail" class="block text-sm font-medium">Email</label>
                    <input type="email" id="mail" name="mail" placeholder="example@mail.com" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="phonenumber" class="block text-sm font-medium">Phone Number</label>
                    <input type="text" id="phonenumber" name="phonenumber" placeholder="+8.80174417762e+12" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium">Password</label>
                    <input type="password" id="password" name="password" placeholder="Password" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <label for="confirm_password" class="block text-sm font-medium">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="Password" required
                        class="mt-1 w-full rounded-md border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <div>
                    <!-- <label class="block text-sm font-medium">User Type : Shop Owner</label> -->
                    <div class="flex flex-wrap gap-4 mt-2">
                        <!-- <label class="flex items-center gap-2">
                            <input type="radio" name="usertype" value="User" required class="text-blue-600 focus:ring-blue-500">
                            <span>User</span>
                        </label> -->
                        <label class="flex items-center gap-2">
                            <input type="radio" name="usertype" value="Shop-Owner" required hidden checked class="text-blue-600 focus:ring-blue-500">
                            <!-- <span>Shop Owner</span> -->
                        </label>
                        <!-- <label class="flex items-center gap-2">
                            <input type="radio" name="usertype" value="Agent" required class="text-blue-600 focus:ring-blue-500">
                            <span>Agent</span>
                        </label> -->
                        <!-- <label class="flex items-center gap-2">
                            <input type="radio" name="usertype" value="Admin" required class="text-blue-600 focus:ring-blue-500">
                            <span>Admin</span>
                        </label> -->
                    </div>
                </div>

                <!-- Profile Picture Upload - Simple field at the end -->
                <div>
                    <label for="profile_picture" class="block text-sm font-medium">Profile Picture (Optional)</label>
                    <input type="file" id="profile_picture" name="profile_picture" accept="image/*"
                        class="mt-1 w-full rounded-md  px-3 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-xs text-gray-500 mt-1">JPG, JPEG, PNG, or GIF. Max size: 5MB</p>
                </div>

                <button type="submit" class="w-full bg-green-600 text-white font-medium py-2 rounded-md hover:bg-green-700 transition">
                    Sign Up
                </button>

                <p class="text-center text-sm mt-3">
                    Already have an account?
                    <a href="login.php" class="text-green-600 hover:underline">Log in</a>
                </p>

                <p class="text-center text-sm text-gray-600 mt-4">
                    Want to join as an agricultural agent?
                    <a href="../agent-app/become-agent.php" class="text-green-600 hover:underline font-semibold">Become an Agent →</a>
                </p>


            </form>
        </div>
    </div>

</body>

</html>