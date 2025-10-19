import unittest
from selenium import webdriver
from selenium.webdriver.chrome.service import Service as ChromeService
from webdriver_manager.chrome import ChromeDriverManager
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import WebDriverWait
from selenium.webdriver.support import expected_conditions as EC

class AgentLoginTest(unittest.TestCase):

    def setUp(self):
        # Set up the Chrome driver using webdriver-manager to handle driver installation
        self.driver = webdriver.Chrome(service=ChromeService(ChromeDriverManager().install()))
        # Adjust this URL to your local environment's base URL
        self.base_url = "http://localhost/direct-edge/"
        self.driver.get(self.base_url + "Login-Signup/login.php")

    def test_agent_login(self):
        driver = self.driver

        # Find the phone number and password fields and the login button
        # The names 'phone_number' and 'password' are assumed based on common practices.
        # You might need to inspect the login.php page to get the correct names or IDs.
        phone_number_field = WebDriverWait(driver, 20).until(
            EC.presence_of_element_located((By.NAME, "phone")) # Assuming the input name is 'phone'
        )
        password_field = driver.find_element(By.NAME, "password") # Assuming the input name is 'password'
        login_button = driver.find_element(By.XPATH, "//button[@type='submit']") # A more generic way to find a submit button

        # Enter the credentials
        phone_number_field.send_keys("987654321")
        password_field.send_keys("123456")

        # Click the login button
        login_button.click()

        # Wait for the page to load and check for a successful login indicator.
        # This could be a URL change, or an element on the new page.
        # For example, let's assume a successful login redirects to 'agent-farmer-dashboard.php'
        WebDriverWait(driver, 20).until(
            EC.url_contains("agent-farmer-dashboard.php")
        )
        
        # You can also check for a specific element on the dashboard
        # welcome_message = WebDriverWait(driver, 10).until(
        #     EC.presence_of_element_located((By.ID, "welcome-message")) # Assuming there is an element with this ID
        # )
        # self.assertTrue("Welcome" in welcome_message.text)

        # Assert that the current URL is the dashboard URL
        self.assertIn("agent-farmer-dashboard.php", driver.current_url)

    def test_add_farmer(self):
        # First, log in
        self.test_agent_login()
        driver = self.driver

        # Navigate to the add farmer page
        driver.get(self.base_url + "agent-app/add-farmers-info.php")

        # Wait for the form to be present
        WebDriverWait(driver, 20).until(
            EC.presence_of_element_located((By.NAME, "full_name"))
        )

        # Fill out the form
        driver.find_element(By.NAME, "full_name").send_keys("Test Farmer")
        driver.find_element(By.NAME, "dob").send_keys("1990-01-01")
        driver.find_element(By.NAME, "nid_number").send_keys("1234567890123")
        driver.find_element(By.NAME, "contact_number").send_keys("01234567890")
        driver.find_element(By.NAME, "present_address").send_keys("123 Test Street, Test City")
        # driver.find_element(By.NAME, "profile_picture").send_keys("path/to/your/test/image.jpg") # Optional: handle file uploads if needed
        driver.find_element(By.NAME, "farmer_type").send_keys("Small")
        driver.find_element(By.NAME, "crops_cultivated").send_keys("Rice, Wheat")
        driver.find_element(By.NAME, "land_size").send_keys("5")
        driver.find_element(By.NAME, "land_ownership").send_keys("Own Land")
        driver.find_element(By.NAME, "fertilizer_usage").send_keys("Standard amount")
        driver.find_element(By.NAME, "bank_account").send_keys("123456789")
        driver.find_element(By.NAME, "mobile_banking_account").send_keys("01234567890")
        driver.find_element(By.NAME, "training_received").send_keys("None")
        driver.find_element(By.NAME, "avg_selling_price").send_keys("Rice - 40 BDT/kg")
        driver.find_element(By.NAME, "additional_notes").send_keys("Test farmer entry.")

        # Submit the form
        driver.find_element(By.XPATH, "//button[@type='submit']").click()

        # Wait for the success message
        success_message = WebDriverWait(driver, 20).until(
            EC.presence_of_element_located((By.XPATH, "//*[contains(text(), 'Farmer information saved successfully!')]"))
        )

        self.assertTrue(success_message.is_displayed())

    def tearDown(self):
        self.driver.quit()

if __name__ == "__main__":
    unittest.main()
