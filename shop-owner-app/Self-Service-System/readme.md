# Shopno-Self-Service-Grocery-Ordering-System

Shopno is a web-based grocery ordering system designed for in-store use, allowing customers to order groceries via their mobile devices and receive them from shopkeepers using a unique code. Customers connect to the store’s WiFi, scan a QR code to access the app, enter their name, select products, and receive a unique code (e.g., A12). Shopkeepers view orders, prepare items, and call out the code for pickup.
The system uses PHP, MySQL, HTML, CSS (with Tailwind CSS via CDN), and a basic MVC architecture.

This is a demo implementation suitable for a small store, running on a local server accessible via WiFi. It does not include payment processing or advanced security features.

---

## Features

- **Customer Flow**:
  - Scan QR code to access the app.
  - Enter name and select products with quantities.
  - Receive a unique code (e.g., A12) and wait for it to be called.
  - Show code to shopkeeper to collect items.
- **Shopkeeper Flow**:
  - View pending orders with customer names, items, and codes.
  - Mark orders as delivered after handing over items.
- **Tech Stack**:
  - Backend: PHP with MySQL for data storage.
  - Frontend: HTML, Tailwind CSS (via CDN).
  - Architecture: Basic Model-View-Controller (MVC).
  - Database: MySQL with tables for products and orders.

---

## Requirements

- **Software**:
  - PHP 7.4+ with `pdo_mysql` extension.
  - MySQL 5.7+ (or MariaDB equivalent).
  - Web browser (e.g., Chrome, Safari) for testing.
- **Hardware**:
  - Computer or device to run the PHP server and MySQL (e.g., laptop, Raspberry Pi).
  - WiFi network for customer access.
- **Optional**:
  - MySQL client (e.g., phpMyAdmin, MySQL Workbench) for database management.
  - QR code generator (online or app) to create access QR code.

---

## File Structure

```
shopno-self-service-pickup-system/
├── index.php               # Main entry point and router
├── config.php              # MySQL database connection
├── models/
│   ├── Product.php         # Model for products
│   └── Order.php           # Model for orders
├── controllers/
│   ├── HomeController.php  # Handles entry page
│   ├── ProductController.php # Handles product selection
│   └── OrderController.php # Handles order submission and admin
├── views/
│   ├── home.php            # Entry page (name input)
│   ├── products.php        # Product selection page
│   ├── order_confirmation.php # Order confirmation with code
│   └── admin_orders.php    # Shopkeeper order management
├── public/
│   └── styles.css          # Custom CSS (empty for now)
└── setup_database.sql      # MySQL schema and sample data
```

---

## Setup Instructions

### 1. Install Software

- **PHP**:
  - Download from [php.net](https://www.php.net/downloads.php) (version 8.x recommended).
  - Ensure `pdo_mysql` extension is enabled (`php -m | grep pdo_mysql`).
  - Add PHP to system PATH (Windows) or install via package manager (Linux/Mac).
- **MySQL**:
  - Install MySQL Community Server from [mysql.com](https://dev.mysql.com/downloads/mysql/) or use MariaDB.
  - Set up a root user and password (e.g., `root`/`your_password`).

### 2. Set Up the Database

### 3. Configure the Project

### 4. Start the PHP Server

1. **Start the PHP Server**

   - Open a terminal/command prompt in the `shopno-system/` folder.
   - Run the following command:

   ```bash
   php -S 0.0.0.0:8000
   ```

   This will start a PHP development server accessible on **port 8000**.

2. **Find Your Local IP Address**

   - **Windows:**
     Open Command Prompt and run:

     ```bash
     ipconfig
     ```

     Look for **IPv4 Address** (e.g., `192.168.1.100`).

   - **Mac/Linux:**
     ```bash
     ifconfig
     ```

   Look for your network interface’s IP (e.g., `192.168.1.100`).

3. **Test Access**

   - On the **server computer**, open a browser and visit:

     - [http://localhost:8000](http://localhost:8000)
     - or [http://your-local-ip:8000](http://10.10.201.19:8000)
     <!-- - or [http://your-local-ip:8000](http://192.168.1.100:8000) -->

You should see the **“Welcome to Shopno”** page with a name input form.

4. **Access from Another Device**

   On a laptop/phone connected to the same network, open a browser and enter:

   ```
   http://your-laptop-ip:8000
   ```

   Replace `your-laptop-ip` with the IP address you found in **Step 2** (e.g., `http://192.168.1.100:8000`).

5. Stop the Server
   ```
   Ctrl + C
   ```

### 5. Create and Place QR Code

- Generate a QR code for `http://your-laptop-ip:8000` (e.g., `http://192.168.1.100:8000`) using a free online tool (e.g., [qr-code-generator.com](https://www.qr-code-generator.com/)).
- Download and print the QR code.
- Place it in the store (e.g., entrance or counter) for customers to scan.

### 6. Ensure WiFi Access

- Configure your store’s WiFi.
- Ensure the laptop and customer phones connect to this WiFi.

---

## Usage

### Customer Usage

1. **Connect to WiFi**: Join the Tashin WiFi network.
2. **Scan QR Code**: Use a phone’s camera or QR scanner app to scan the store’s QR code, opening `http://your-laptop-ip:8000`.
3. **Enter Name**: On the homepage, enter your name (e.g., “John”) and click “Start Shopping.”
4. **Select Products**: Choose products (e.g., 2 Apples, 1 Milk) by entering quantities and click “Submit Order.”
5. **View Confirmation**: See your unique code (e.g., A12) and ordered items. Wait for the shopkeeper to call your code.
6. **Collect Items**: When your code is called, show it to the shopkeeper and collect your groceries.

### Shopkeeper Usage

1. **Access Admin Page**: On a store computer or device, visit `http://your-laptop-ip:8000/admin` (or `http://localhost:8000/admin` if on the server).
2. **View Orders**: See a list of pending orders with customer names, items, and codes.
3. **Prepare Orders**: Collect items for each order and announce the code (e.g., “Order A12 is ready”) via microphone or manually.
4. **Mark Delivered**: After handing over items, click “Mark Delivered” to update the order status.

---

## Troubleshooting

- **Phone Can’t Access Server**:
  - Ensure the phone is on the Tashin WiFi.
  - Use the laptop’s IP (e.g., `http://192.168.1.100:8000`), not `localhost`.
  - Check firewall settings to allow port 8000.
  - Disable WiFi client isolation in router settings.
- **Database Errors**:
  - Verify MySQL credentials in `config.php`.
  - Ensure the `shopno` database and tables exist (`SELECT * FROM shopno.products;`).
- **Server Not Running**:
  - Confirm `php -S 0.0.0.0:8000` is active in the terminal.
- **QR Code Issues**:
  - Ensure the QR code links to the correct IP and port.
  - Test scanning with multiple devices.

---

## Notes

- **Demo Limitations**:
  - No payment processing.
  - No admin authentication (add for production).
  - Basic error handling.
- **Production Recommendations**:
  - Use Apache/Nginx with HTTPS for security.
  - Add input validation and sanitization.
  - Implement admin login for `/admin`.
  - Set a static IP for the server to avoid IP changes.
- **Extending the System**:
  - Add product categories, images, or search.
  - Integrate payment processing (e.g., Stripe API).
  - Use a session-based cart for better user experience.

## License

This is a demo project for educational purposes. No license is specified.
