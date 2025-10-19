<?php

$to = "mahmed221209@bscse.uiu.ac.bd";
$subject = "HTML email";  // Fixed: Added the equal sign

$message = "
<html>
<head>
<title>HTML email</title>
</head>
<body>
<p>This email contains HTML Tags!</p>
<table border='1'>
<tr>
<th>Firstname</th>
<th>Lastname</th>
</tr>
<tr>
<td>Noman</td>
<td>Ahmed</td>
</tr>
</table>
</body>
</html>
";

$headers = "MIME-Version: 1.0" . "\r\n";
$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";

// Fixed: Added the missing closing quotation mark and removed the extra period
$headers .= 'From: <mahmed221209@bscse.uiu.ac.bd>' . "\r\n";

// Fixed: Typo in the variable $message and closing parenthesis
// mail($to, $subject, $message, $headers);

?>



<?php
######
# THIS FILE IS ONLY AN EXAMPLE. PLEASE MODIFY AS REQUIRED.
# Contributors: 
#       Md. Rakibul Islam <rakibul.islam@sslwireless.com>
#       Prabal Mallick <prabal.mallick@sslwireless.com>
######

error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">


<head>
    <meta charset="UTF-8">

    <!-- <meta name="author" content="SSLCommerz">
    <title>Successful Transaction - SSLCommerz</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" integrity="sha384-JcKb8q3iqJ61gNV9KGb8thSsNjpSL0n8PARn9HuZOnIxN0hoP+VmmDGMN5t9UJ0Z" crossorigin="anonymous"> -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge"> -->
    <title>success</title>
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Latest compiled and minified CSS -->
    <!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <link rel="stylesheet" href="success.css">


    <!-- favicon -->
    <link rel="icon" href="../Images/logo/fav-icon.png" />


    <!-- css  -->
    <link rel="stylesheet" href="../Includes/Navbar/navbarMain.css"> <!-- Navbar CSS -->


</head>

<body>


    <?php

    ?>

    <!-- This section was removed as it was causing errors -->




    <!-- 
<div class="congratulation-area text-center mt-5">
        <div class="container">
            <div class="congratulation-wrapper">
                <div class="congratulation-contents center-text">
                    <div class="congratulation-contents-icon">
                        <i class="fas fa-check"></i>
                    </div>
                    <h4 class="congratulation-contents-title"> Congratulations! </h4>
                    <p class="congratulation-contents-para"> Your account is ready to submit proposals and get work. </p>
                    <div class="btn-wrapper mt-4">
                        <a href="javascript:void(0)" class="cmn-btn btn-bg-1"> Go to Home </a>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    <!--.........................................new code............................................-->
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="message-box _success">
                    <i class="fa fa-check-circle" aria-hidden="true"></i>
                    <h2> Your payment was successful </h2>
                    <p> Thank you for your payment. we will <br>
                        be in contact with more details shortly </p>
                    <div class="btn-wrapper mt-4">
                        <!-- <a href="../Home/Homepage.php" class="cmn-btn btn-bg-1"> Go to Home </a> -->

                        <!-- <a href="javascript:void(0)" class="cmn-btn btn-bg-1"> Go to Home </a> -->

                        <a href="../../buy-products-from-warehouse.php" class="btn cmn-btn btn-bg-1"> Go to Buy products from warehouse </a>

                    </div>
                </div>
            </div>
        </div>



        <!-- <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="message-box _success _failed">
                     <i class="fa fa-times-circle" aria-hidden="true"></i>
                    <h2> Your payment failed </h2>
             <p>  Try again later </p> 
         
            </div> 
        </div> 
    </div> 
   -->

        <!--......................................... old code............................................-->
        <div class="container">
            <div class="row" style="margin-top: 10%;">
                <div class="col-md-8 offset-md-2">
                    <?php
                    echo $_POST['tran_id'];
                    echo ' - ' . $_POST['amount'];
                    echo ' - ' . $_POST['currency'];
                    require_once(__DIR__ . "/../lib/SslCommerzNotification.php");
                    include_once(__DIR__ . "/../../../include/connect-db.php");
                    if (!isset($conn) || !$conn) {
                        die('<h2 class="text-center text-danger">Database connection failed. Please check connect-db.php path and connection settings.</h2>');
                    }
                    include_once(__DIR__ . "/../OrderTransaction.php");

                    use SslCommerz\SslCommerzNotification;

                    $sslc = new SslCommerzNotification();
                    $tran_id = $_POST['tran_id'];
                    $amount =  $_POST['amount'];
                    $currency =  $_POST['currency'];

                    $ot = new OrderTransaction();
                    $sql = $ot->getRecordQuery($tran_id);
                    $result = $conn->query($sql);
                    $row = $result->fetch_array(MYSQLI_ASSOC);

                    if ($row && $row['payment_status'] == 'Pending') {
                        $validated = $sslc->orderValidate($_POST, $tran_id, $amount, $currency);

                        if ($validated) {
                            $sql = $ot->updateTransactionQuery($tran_id, 'Paid');

                            if ($conn->query($sql) === TRUE) { ?>
                                <h2 class="text-center text-success">Congratulations! Your Transaction is Successful.</h2>
                                <br>
                                <table border="1" class="table table-striped">
                                    <thead class="thead-dark">
                                        <tr class="text-center">
                                            <th colspan="2">Payment Details</th>
                                        </tr>
                                    </thead>
                                    <tr>
                                        <td class="text-right">Transaction ID</td>
                                        <td><?= $_POST['tran_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Transaction Time</td>
                                        <td><?= $_POST['tran_date'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Payment Method</td>
                                        <td><?= $_POST['card_issuer'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Bank Transaction ID</td>
                                        <td><?= $_POST['bank_tran_id'] ?></td>
                                    </tr>
                                    <tr>
                                        <td class="text-right">Amount</td>
                                        <td><?= $_POST['amount'] . ' ' . $_POST['currency'] ?></td>
                                    </tr>
                                </table>

                    <?php

                            } else { // update query returned error

                                echo '<h2 class="text-center text-danger">Error updating record: </h2>' . $conn_integration->error;
                            } // update query successful or not 

                        } else { // $validated is false

                            echo '<h2 class="text-center text-danger">Payment was not valid. Please contact with the merchant.</h2>';
                        } // check if validated or not

                    } else { // status is something else

                        echo '<h2 class="text-center text-danger">Invalid Information.</h2>';
                    } // status is 'Pending' or already 'Processing'
                    ?>

                </div>
            </div>
        </div>


        <!-- ============================== Footer ==================================== -->

        <!-- ============================== Footer End ==================================== -->
</body>

</html>