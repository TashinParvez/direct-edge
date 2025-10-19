from selenium import webdriver
from selenium.webdriver.edge.service import Service
from selenium.webdriver.common.by import By
from selenium.webdriver.support.ui import Select
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

def generate_random_nid():
    """Generate a random NID number."""
    return ''.join(random.choices(string.digits, k=random.randint(10, 17)))

def generate_random_region():
    """Generate a random region in Bangladesh."""
    regions = ["Dhaka", "Chittagong", "Rajshahi", "Khulna", "Barisal", "Sylhet", "Rangpur", "Mymensingh"]
    return random.choice(regions)

def generate_random_district(region):
    """Generate a random district based on region."""
    districts = {
        "Dhaka": ["Dhaka", "Gazipur", "Narsingdi", "Manikganj", "Munshiganj"],
        "Chittagong": ["Chittagong", "Cox's Bazar", "Rangamati", "Bandarban", "Khagrachari"],
        "Rajshahi": ["Rajshahi", "Bogra", "Pabna", "Sirajganj", "Natore"],
        "Khulna": ["Khulna", "Jessore", "Satkhira", "Bagerhat", "Chuadanga"],
        "Barisal": ["Barisal", "Patuakhali", "Bhola", "Pirojpur", "Jhalokathi"],
        "Sylhet": ["Sylhet", "Moulvibazar", "Habiganj", "Sunamganj"],
        "Rangpur": ["Rangpur", "Dinajpur", "Kurigram", "Gaibandha", "Nilphamari"],
        "Mymensingh": ["Mymensingh", "Jamalpur", "Sherpur", "Netrokona"]
    }
    return random.choice(districts.get(region, ["Unknown"]))

def generate_random_upazila(district):
    """Generate a random upazila."""
    # This is a simplified approach - in a real scenario, you'd have a more complete mapping
    upazilas = {
        "Dhaka": ["Savar", "Dhamrai", "Keraniganj", "Dohar", "Nawabganj"],
        "Gazipur": ["Gazipur Sadar", "Kaliakair", "Kaliganj", "Kapasia", "Sreepur"],
        "Chittagong": ["Anwara", "Banshkhali", "Boalkhali", "Chandanaish", "Fatikchhari"],
    }
    
    # If we have specific upazilas for this district, return one
    if district in upazilas:
        return random.choice(upazilas[district])
    
    # Otherwise, generate a generic name
    return f"{district} {random.choice(['North', 'South', 'East', 'West', 'Central'])}"

def generate_random_crops():
    """Generate a random list of crops expertise."""
    crops = ["Rice", "Wheat", "Corn", "Potato", "Tomato", "Eggplant", "Cabbage", 
             "Cauliflower", "Beans", "Lentils", "Pumpkin", "Watermelon", "Mango", 
             "Banana", "Jackfruit", "Litchi", "Papaya", "Jute", "Sugarcane", "Cotton"]
    
    # Select 2-5 random crops
    num_crops = random.randint(2, 5)
    selected_crops = random.sample(crops, num_crops)
    return ", ".join(selected_crops)

def generate_random_vehicle_types():
    """Generate a random list of vehicle types."""
    vehicles = ["Van", "Pickup", "Motorcycle", "Bicycle", "Mini-truck", "Rickshaw van", 
                "Auto-rickshaw", "Tractor", "Boat", "None"]
    
    # Select 1-3 random vehicles
    num_vehicles = random.randint(1, 3)
    selected_vehicles = random.sample(vehicles, num_vehicles)
    return ", ".join(selected_vehicles)

# Generate random user data
username = generate_random_username()
email = generate_random_email(username)
phone_number = generate_random_phone_number()
password = generate_strong_password()
nid_number = generate_random_nid()
region = generate_random_region()
district = generate_random_district(region)
upazila = generate_random_upazila(district)
coverage_area_km = random.randint(5, 100)
experience_years = random.randint(0, 15)
crops_expertise = generate_random_crops()
vehicle_types = generate_random_vehicle_types()
warehouse_capacity = f"{random.randint(50, 2000)} sq ft, {random.randint(1, 30)} tons"
reference_name = generate_random_username()
reference_phone = generate_random_phone_number()
statement = "I want to help local farmers connect with markets and improve agricultural practices in my region."

print(f"Testing agent signup with:")
print(f"Full Name: {username}")
print(f"Email: {email}")
print(f"Phone: {phone_number}")
print(f"Password: {password}")
print(f"NID: {nid_number}")
print(f"Region: {region}")
print(f"District: {district}")
print(f"Upazila: {upazila}")

# To Keep Browser Open Indefinitely
options = webdriver.EdgeOptions()
options.add_experimental_option("detach", True)

# Edge Driver
service_obj = Service()
driver = webdriver.Edge(options=options, service=service_obj)

# Browser Tasks
driver.maximize_window()
driver.get("http://localhost/direct-edge/agent-app/become-agent.php")

# Fill in the form
driver.find_element(By.NAME, "full_name").send_keys(username)
time.sleep(0.5)
driver.find_element(By.NAME, "email").send_keys(email)
time.sleep(0.5)
driver.find_element(By.NAME, "phone").send_keys(phone_number)
time.sleep(0.5)
driver.find_element(By.NAME, "nid_number").send_keys(nid_number)
time.sleep(0.5)
driver.find_element(By.NAME, "password").send_keys(password)
time.sleep(0.5)
driver.find_element(By.NAME, "confirm_password").send_keys(password)
time.sleep(0.5)
driver.find_element(By.NAME, "region").send_keys(region)
time.sleep(0.5)
driver.find_element(By.NAME, "district").send_keys(district)
time.sleep(0.5)
driver.find_element(By.NAME, "upazila").send_keys(upazila)
time.sleep(0.5)
driver.find_element(By.NAME, "coverage_area_km").clear()
driver.find_element(By.NAME, "coverage_area_km").send_keys(str(coverage_area_km))
time.sleep(0.5)
driver.find_element(By.NAME, "experience_years").clear()
driver.find_element(By.NAME, "experience_years").send_keys(str(experience_years))
time.sleep(0.5)
driver.find_element(By.NAME, "crops_expertise").send_keys(crops_expertise)
time.sleep(0.5)
driver.find_element(By.NAME, "vehicle_types").send_keys(vehicle_types)
time.sleep(0.5)
driver.find_element(By.NAME, "warehouse_capacity").send_keys(warehouse_capacity)
time.sleep(0.5)
driver.find_element(By.NAME, "reference_name").send_keys(reference_name)
time.sleep(0.5)
driver.find_element(By.NAME, "reference_phone").send_keys(reference_phone)
time.sleep(0.5)
driver.find_element(By.NAME, "statement").send_keys(statement)
time.sleep(0.5)

# Submit the form
print("\nSubmitting form with random data...")
driver.find_element(By.CSS_SELECTOR, 'button[type="submit"]').click()
print("Form submitted successfully!")



