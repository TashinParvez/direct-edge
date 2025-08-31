# DirectEdge

## Overview
DirectEdge is a smart 3-sided digital platform designed to simplify agricultural supply chains by connecting farmers, distributors, and shop owners. It aims to eliminate inefficiencies caused by middlemen, improve logistics, and provide real-time inventory and demand forecasting using advanced technologies like computer vision and machine learning.

## Table of Contents
- [Background](#background)
- [Features](#features)
- [Technology Stack](#technology-stack)
- [Installation](#installation)
- [Usage](#usage)
- [Work Distribution](#work-distribution)
- [Methodology](#methodology)
- [Contributing](#contributing)
- [Contact](#contact)
- [References](#references)

## Background
The agricultural supply chain in many regions faces significant challenges:
- **Unorganized Supply Chain**: Dependency on middlemen results in low prices for farmers.
- **Logistics & Storage Inefficiencies**: Delays cause spoilage of perishable goods.
- **Manual Inventory Systems**: Small shops suffer from stock-outs and overstocking.

DirectEdge addresses these issues by providing a platform that:
- Connects farmers directly with buyers.
- Optimizes logistics with real-time crop listing and delivery scheduling.
- Enhances inventory management with computer vision and demand forecasting.

## Features
### Farmer's App
- Real-time crop listing and stock management.
- Collection scheduling and planning.
- Payment and financial handling.
- Communication and notifications.

### Shop Owner's App
- Free shop management tool.
- Product management and analysis.
- Real-time inventory tracking.
- Computer vision-based receipt generation.
- Demand forecasting using deep learning.

### Warehouse App
- Delivery scheduling.
- Agent and shop handling.
- Warehouse management.

### Additional Features
- Reporting and analytics.
- Email confirmations.
- Personalized coupons and ratings for customer loyalty.

## Technology Stack
- **Frontend**: React.js (web dashboards), Flutter (mobile apps).
- **Backend**: FastAPI (Python) for APIs, Node.js for business logic.
- **Database**: PostgreSQL for structured data.
- **Computer Vision**: PyTorch/TensorFlow for product and crop disease recognition.
- **Machine Learning**: scikit-learn, Prophet, or XGBoost for demand forecasting.
- **Cloud & Deployment**: AWS/GCP for scalable infrastructure.

## Installation
1. **Clone the Repository**:
   ```bash
   git clone https://github.com/DirectEdge/DirectEdge.git
   cd DirectEdge
   ```

2. **Frontend Setup**:
   ```bash
   cd frontend
   npm install
   npm start
   ```

3. **Backend Setup**:
   ```bash
   cd backend
   pip install -r requirements.txt
   uvicorn main:app --reload
   ```

4. **Database Setup**:
   - Install PostgreSQL and configure the database.
   - Update the database connection settings in the backend configuration file.

5. **Computer Vision & Machine Learning**:
   - Install Python dependencies: PyTorch, TensorFlow, scikit-learn, OpenCV.
   - Pre-trained models for product recognition and forecasting are available in the `models/` directory.

6. **Cloud Deployment**:
   - Configure AWS/GCP credentials.
   - Deploy using provided scripts in the `deploy/` directory.

## Usage
- **Farmers**: Register and list crops, schedule collections, and track payments.
- **Shop Owners**: Use the app to manage inventory, generate receipts via computer vision, and access demand forecasts.
- **Warehouse Managers**: Schedule deliveries and manage agents through the warehouse app.
- **Admins**: Access dashboards for analytics and system oversight.

## Work Distribution
The project is divided into a 9-week development cycle:
- **Weeks 1-2**: Backend and frontend setup, farmer and agent module development.
- **Weeks 3-4**: Distributor and storehouse module development.
- **Weeks 5-6**: Shop owner, admin, and storehouse manager module development.
- **Weeks 7-8**: AI/ML integration (product recognition, forecasting) and module connectivity.
- **Week 9**: Testing and final refinements.

## Methodology
DirectEdge follows an **Agile (Scrum)** methodology to accommodate:
- Multi-module development (farmers, distributors, shop owners, admins).
- Iterative AI/ML model experimentation and improvement.
- Parallel web and mobile development.
- Evolving stakeholder feedback.

## Contributing
We welcome contributions! To contribute:
1. Fork the repository.
2. Create a new branch (`git checkout -b feature-branch`).
3. Make your changes and commit (`git commit -m "Add feature"`).
4. Push to the branch (`git push origin feature-branch`).
5. Create a pull request.

Please ensure your code follows the project's coding standards and includes tests.

## Contact
- **Email**: directedge@gmail.com
- **Social Media**: [@DirectEdge](https://x.com/DirectEdge)
- **Phone**: +8801300919276
- **Address**: United City, Madani Ave, Dhaka 1212

## References
- [Industry-wise GDP Contribution in Bangladesh](https://businessinspection.com.bd/industry-wise-gdp-contribution-in-bd/)
- [Consumer-end Questionnaire](https://forms.gle/idmEzbDJmu25jUJ48)
- [Shopowner-end Questionnaire](https://forms.gle/cfqQxZiGr4iW2pbMA)