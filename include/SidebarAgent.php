<!DOCTYPE html>
<!-- Created by CodingLab |www.youtube.com/CodingLabYT-->
<html lang="en" dir="ltr">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="sidebar.css">
    <link href='https://unpkg.com/boxicons@2.0.7/css/boxicons.min.css' rel='stylesheet'>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="..\assets\Logo\LogoBG.png">
</head>

<body class="bg-custom">
    <!-- Added 'open' class to make sidebar open by default -->
    <div class="sidebar open pl-5 m-0">
        <div class="logo-details">
            <i class='bx bx-menu-alt-right' id="btn"></i>
        </div>
        <ul class="nav-list p-0">
            <li>
                <a href="..\Dashboard\Dashboard.php">
                    <i class='bx bx-user'></i>
                    <span class="links_name">Agent Profile</span>
                </a>
                <span class="tooltip">Agent Profile</span>
            </li>
            <li>
                <a href="..\Investment Page\Investment.php">
                    <i class='bx bx-user-check'></i>
                    <span class="links_name">Farmer under Agent</span>
                </a>
                <span class="tooltip">Farmer under Agent</span>
            </li>
            <li>
                <a href="#">
                    <i class='bx bx-wallet'></i>
                    <span class="links_name">Payment info</span>
                </a>
                <span class="tooltip">Payment info</span>
            </li>
            <li>
                <a href="..\Receipt Page\RPage.php">
                    <i class='bx bx-user-plus'></i>
                    <span class="links_name">Add Farmer</span>
                </a>
                <span class="tooltip">Add Farmer</span>
            </li>

            <!-- Profile Info -->
            <li class="profile">
                <div class="profile-details">
                    <img src="https://www.svgrepo.com/show/23012/profile-user.svg" alt="profileImg">
                    <div class="name_job">
                        <div class="name">Aranya</div>
                    </div>
                </div>
                <i class='bx bx-log-out' id="log_out"></i>
            </li>
        </ul>
    </div>
    </section>
    <script>
    let sidebar = document.querySelector(".sidebar");
    let closeBtn = document.querySelector("#btn");
    // Removed searchBtn variable since search bar is removed

    closeBtn.addEventListener("click", () => {
        sidebar.classList.toggle("open");
        menuBtnChange(); //calling the function(optional)
    });

    // Removed search button event listener since search bar is removed

    // following are the code to change sidebar button(optional)
    function menuBtnChange() {
        if (sidebar.classList.contains("open")) {
            closeBtn.classList.replace("bx-menu", "bx-menu-alt-right"); //replacing the iocns class
        } else {
            closeBtn.classList.replace("bx-menu-alt-right", "bx-menu"); //replacing the iocns class
        }
    }
    </script>
</body>

</html>