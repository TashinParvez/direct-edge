from selenium import webdriver
from selenium.webdriver.edge.service import Service
from selenium.webdriver.common.by import By
import time
import random
import string

def generate_random_username():
    """Generate a random username with Bangladeshi first name and last name format."""
    first_names = ["Anik", "Mahbub", "Rahman", "Karim", "Rahim", "Fahim", "Sakib", "Tamim", 
                  "Mashrafe", "Mushfiqur", "Tashin", "Nafis", "Rashed", "Mehedi", "Mustafiz", "Rubel",
                  "Sabina", "Mithila", "Nusrat", "Tania", "Nazia", "Shirin", "Sadia", "Israt", 
                  "Farzana", "Mim", "Taslima", "Nasrin", "Afroza", "Nilufa", "Shahana", "Munni"]
    last_names = ["Islam", "Ahmed", "Khan", "Hossain", "Rahman", "Ali", "Haque", "Chowdhury", 
                 "Miah", "Sarkar", "Uddin", "Alam", "Mahmud", "Siddique", "Talukder", "Sheikh",
                 "Begum", "Khatun", "Akter", "Sultana", "Khanam", "Jahan", "Parvin"]
    
    first_name = random.choice(first_names)
    last_name = random.choice(last_names)
    return f"{first_name} {last_name}"

def generate_random_email(username):
    """Generate a random email based on username."""
    domains = ["gmail.com", "yahoo.com", "hotmail.com", "outlook.com", "example.com", "test.org"]
    # Remove spaces and make lowercase
    clean_name = username.lower().replace(" ", ".")
    # Add random number to ensure uniqueness
    random_num = random.randint(10, 9999)
    return f"{clean_name}{random_num}@{random.choice(domains)}"

def generate_random_phone_number():
    """Generate a random Bangladesh-like phone number (11-14 digits)."""
    prefixes = ["+880", "880", "0"]
    operator_codes = ["13", "14", "15", "16", "17", "18", "19"]
    
    prefix = random.choice(prefixes)
    operator = random.choice(operator_codes)
    
    # Generate 8 random digits for the rest of the number
    rest_digits = ''.join(random.choices(string.digits, k=8))
    
    phone = f"{prefix}{operator}{rest_digits}"
    return phone

def generate_strong_password():
    """Generate a strong password that meets the requirements."""
    # At least one uppercase, one lowercase, one digit, one special char, and at least 8 chars
    lowercase = random.choices(string.ascii_lowercase, k=random.randint(3, 5))
    uppercase = random.choices(string.ascii_uppercase, k=random.randint(2, 4))
    digits = random.choices(string.digits, k=random.randint(2, 3))
    special_chars = random.choices('!@#$%^&*()-_=+', k=random.randint(1, 3))
    
    # Combine all characters and shuffle
    all_chars = lowercase + uppercase + digits + special_chars
    random.shuffle(all_chars)
    
    return ''.join(all_chars)

# Generate random user data
username = generate_random_username()
email = generate_random_email(username)
phone_number = generate_random_phone_number()
password = generate_strong_password()

print(f"Testing with:")
print(f"Username: {username}")
print(f"Email: {email}")
print(f"Phone: {phone_number}")
print(f"Password: {password}")

# To Keep Browser Open Indefinitely
options = webdriver.EdgeOptions()
options.add_experimental_option("detach", True)

# Edge Driver
service_obj = Service()
driver = webdriver.Edge(options=options, service=service_obj)

# Browser Tasks
driver.maximize_window()
driver.get("http://localhost/direct-edge/Login-Signup/signup.php")

driver.find_element(By.NAME, "username").send_keys(username)
time.sleep(1)
driver.find_element(By.NAME, "mail").send_keys(email)
time.sleep(1)
driver.find_element(By.NAME, "phonenumber").send_keys(phone_number)
time.sleep(1)
driver.find_element(By.NAME, "password").send_keys(password)
time.sleep(1)
driver.find_element(By.NAME, "confirm_password").send_keys(password)
time.sleep(1)

# Submit the form
print("\nSubmitting form with random data...")
driver.find_element(By.CSS_SELECTOR, 'input[type="submit"], button[type="submit"]').click()
print("Form submitted successfully!")
time.sleep(1)

