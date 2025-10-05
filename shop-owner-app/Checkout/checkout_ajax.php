<?php
//error_reporting(E_ALL);
//ini_set('display_errors', 1);

require_once(__DIR__ . "/lib/SslCommerzNotification.php");
// include("db_connection.php");
// include("OrderTransaction.php");

use SslCommerz\SslCommerzNotification;

$requestData = (array) json_decode($_POST['cart_json']);

// Insert into orders and billing_info tables
include '../../include/connect-db.php';
session_start();
$user_id = $_SESSION['user_id'] ?? 2; // Default to 2 if not set

$grand_total = $requestData['amount'];
$full_name = $requestData['shopName'];
$billing_address = $requestData['address'];
$shipping_address = $requestData['shippingAddress'];
$notes = $requestData['notes'];
$tax_id = $requestData['taxId'];
$state = $requestData['state'];
$country = $requestData['country'];
$zip = $requestData['zip'];

$billing_address_full = $billing_address . ', ' . $state . ', ' . $country . ' ' . $zip;
$shipping_address_full = $shipping_address . ', ' . $state . ', ' . $country . ' ' . $zip;

// Insert into orders first
$order_insert = mysqli_prepare($conn, "INSERT INTO orders (shopowner_id, total_amount, status, payment_status) VALUES (?, ?, 'Pending', 'Pending')");
mysqli_stmt_bind_param($order_insert, "id", $user_id, $grand_total);
if (!mysqli_stmt_execute($order_insert)) {
    die('Error inserting into orders: ' . mysqli_error($conn));
}
$order_id = mysqli_insert_id($conn);

// Now insert into billing_info
$stmt = mysqli_prepare($conn, "INSERT INTO billing_info (user_id, order_id, shop_owener_name, billing_address, shipping_address, special_nstructions, tax_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
mysqli_stmt_bind_param($stmt, "iisssss", $user_id, $order_id, $full_name, $billing_address_full, $shipping_address_full, $notes, $tax_id);
if (!mysqli_stmt_execute($stmt)) {
    die("Error inserting billing info: " . mysqli_error($conn));
}

// SSLCommerz payment processing
$post_data['total_amount'] = $grand_total;
$post_data['currency'] = "BDT";
$post_data['tran_id'] = "SSLCZ_TEST_" . uniqid();

// Update the order with the transaction ID
$update_tran_id_sql = "UPDATE orders SET tran_id = ? WHERE order_id = ?";
$update_stmt = mysqli_prepare($conn, $update_tran_id_sql);
mysqli_stmt_bind_param($update_stmt, "si", $post_data['tran_id'], $order_id);
mysqli_stmt_execute($update_stmt);

//# CUSTOMER INFORMATION
$post_data['cus_name'] = isset($requestData['cus_name']) ? $requestData['cus_name'] : "John Doe";
$post_data['cus_email'] = isset($requestData['cus_email']) ? $requestData['cus_email'] : "john.doe@email.com";
$post_data['cus_add1'] = isset($requestData['cus_add1']) ? $requestData['cus_add1'] : "Dhaka";
$post_data['cus_add2'] = "Dhaka";
$post_data['cus_city'] = "Dhaka";
$post_data['cus_state'] = "Dhaka";
$post_data['cus_postcode'] = "1000";
$post_data['cus_country'] = "Bangladesh";
$post_data['cus_phone'] = isset($requestData['cus_phone']) ? $requestData['cus_phone'] : "01711111111";
$post_data['cus_fax'] = "01711111111";

# SHIPMENT INFORMATION
$post_data["shipping_method"] = "YES";
$post_data['ship_name'] = "Store Test";
$post_data['ship_add1'] = "Dhaka";
$post_data['ship_add2'] = "Dhaka";
$post_data['ship_city'] = "Dhaka";
$post_data['ship_state'] = "Dhaka";
$post_data['ship_postcode'] = "1000";
$post_data['ship_phone'] = "";
$post_data['ship_country'] = "Bangladesh";

$post_data['emi_option'] = "1";
$post_data["product_category"] = "Electronic";
$post_data["product_profile"] = "general";
$post_data["product_name"] = "Computer";
$post_data["num_of_item"] = "1";

# OPTIONAL PARAMETERS
// $post_data['value_a'] = "Regent Air";
// $post_data['value_b'] = "ref002";
// $post_data['value_c'] = "ref003";
// $post_data['value_d'] = "ref004";

# MANAGED TRANS
//$post_data['multi_card_name'] = "brac_visa,dbbl_visa,city_visa,ebl_visa,brac_master,dbbl_master,city_master,ebl_master,city_amex,qcash,dbbl_nexus,bankasia,abbank,ibbl,mtbl,city";
//$post_data['allowed_bin'] = "371598,371599,376947,376948,376949";
//$post_data['multi_card_name'] = "bankasia,mtbl,city";


# CART PARAMETERS
// $post_data['cart'] = json_encode(array(
//     array("sku" => "REF0001", "product" => "DHK TO BRS AC A1", "quantity" => "1", "amount" => "200.00"),
//     array("sku" => "REF0002", "product" => "DHK TO BRS AC A2", "quantity" => "1", "amount" => "200.00"),
//     array("sku" => "REF0003", "product" => "DHK TO BRS AC A3", "quantity" => "1", "amount" => "200.00"),
//     array("sku" => "REF0004", "product" => "DHK TO BRS AC A4", "quantity" => "2", "amount" => "200.00")
// ));

//$post_data['emi_max_inst_option'] = "9";
//$post_data['emi_selected_inst'] = "24";


//$post_data['product_amount'] = "0";
//$post_data['discount_amount'] = "5";

//$post_data['product_amount'] = "100";
//$post_data['vat'] = "5";
//$post_data['discount_amount'] = "5";
//$post_data['convenience_fee'] = "3";

//$post_data['discount_amount'] = "5";

//$post_data['multi_card_name'] = "brac_visa,brac_master";
//$post_data['allowed_bin'] = "408860,458763,489035,432147,432145,548895,545610,545538,432149,484096,484097,464573,539932,436475";

# RECURRING DATA
// $schedule = array(
//     "refer" => "5B90BA91AA3F2", # Subscriber id which generated in Merchant Admin panel
//     "acct_no" => "01730671731",
//     "type" => "daily", # Recurring Schedule - monthly,weekly,daily
//     //"dayofmonth"	=>	"24", 	# 1st day of every month
//     //"month"		=>	"8",	# 1st day of January for Yearly Recurring
//     //"week"	=>	"sat",	# In case, weekly recurring

// );


// $post_data["product_shipping_contry"] = "Bangladesh";
// $post_data["vip_customer"] = "YES";
// $post_data["hours_till_departure"] = "12 hrs";
// $post_data["flight_type"] = "Oneway";
// $post_data["journey_from_to"] = "DAC-CGP";
// $post_data["third_party_booking"] = "No";

// $post_data["hotel_name"] = "Sheraton";
// $post_data["length_of_stay"] = "2 days";
// $post_data["check_in_time"] = "24 hrs";
// $post_data["hotel_city"] = "Dhaka";


// $post_data["product_type"] = "Prepaid";
// $post_data["phone_number"] = "01711111111";
// $post_data["country_topUp"] = "Bangladesh";

// $post_data["shipToFirstName"] = "John";
// $post_data["shipToLastName"] = "Doe";
// $post_data["shipToStreet"] = "93 B, New Eskaton Road";
// $post_data["shipToCity"] = "Dhaka";
// $post_data["shipToState"] = "Dhaka";
// $post_data["shipToPostalCode"] = "1000";
// $post_data["shipToCountry"] = "Bangladesh";
// $post_data["shipToEmail"] = "john.doe@email.com";
// $post_data["ship_to_phone_number"] = "01711111111";

# SPECIAL PARAM
// $post_data['tokenize_id'] = "1";

# 1 : Physical Goods
# 2 : Non-Physical Goods Vertical(software)
# 3 : Airline Vertical Profile
# 4 : Travel Vertical Profile
# 5 : Telecom Vertical Profile

// $post_data["product_profile_id"] = "5";

// $post_data["topup_number"] = "01711111111"; # topUpNumber

# First, save the input data into local database table `orders`
// $query = new OrderTransaction();
// $sql = $query->saveTransactionQuery($post_data);

// if ($conn_integration->query($sql) === TRUE) {

//     # Call the Payment Gateway Library
$sslcz = new SslCommerzNotification();
$sslcz->makePayment($post_data, 'checkout', 'plain');
// } else {
//     echo "Error: " . $sql . "<br>" . $conn_integration->error;
// }