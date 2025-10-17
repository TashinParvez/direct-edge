
import pandas as pd
import numpy as np
from sklearn.ensemble import RandomForestRegressor
from sklearn.linear_model import LinearRegression
from sklearn.metrics import mean_absolute_error, mean_squared_error, r2_score
from sklearn.preprocessing import StandardScaler
import mysql.connector
from datetime import datetime, timedelta
import json
import warnings
warnings.filterwarnings('ignore')

class DemandPredictor:
    """
    Advanced Demand Prediction System for Shop Products
    Uses historical sales data to predict future demand using ML models
    """

    def __init__(self, db_config):
        """
        Initialize the demand predictor with database configuration

        Args:
            db_config (dict): Database connection configuration
        """
        self.db_config = db_config
        self.models = {
            'random_forest': RandomForestRegressor(n_estimators=100, random_state=42),
            'linear_regression': LinearRegression()
        }
        self.scalers = {}
        self.connection = None

    def connect_db(self):
        """Establish database connection"""
        try:
            self.connection = mysql.connector.connect(**self.db_config)
            return True
        except Exception as e:
            print(f"Database connection failed: {e}")
            return False

    def close_db(self):
        """Close database connection"""
        if self.connection and self.connection.is_connected():
            self.connection.close()

    def extract_features(self, data):
        """
        Extract features from historical sales data

        Args:
            data (DataFrame): Historical sales data

        Returns:
            DataFrame: Feature matrix
        """
        features_df = data.copy()

        # Date-based features
        features_df['day_of_week'] = pd.to_datetime(features_df['date']).dt.dayofweek
        features_df['day_of_month'] = pd.to_datetime(features_df['date']).dt.day
        features_df['month'] = pd.to_datetime(features_df['date']).dt.month
        features_df['is_weekend'] = features_df['day_of_week'].isin([5, 6]).astype(int)

        # Rolling averages (demand patterns)
        features_df = features_df.sort_values(['shop_id', 'product_id', 'date'])
        features_df['demand_7d_avg'] = features_df.groupby(['shop_id', 'product_id'])['quantity_sold'].rolling(7, min_periods=1).mean().values
        features_df['demand_14d_avg'] = features_df.groupby(['shop_id', 'product_id'])['quantity_sold'].rolling(14, min_periods=1).mean().values
        features_df['demand_30d_avg'] = features_df.groupby(['shop_id', 'product_id'])['quantity_sold'].rolling(30, min_periods=1).mean().values

        # Lag features (previous day's demand)
        features_df['demand_lag_1'] = features_df.groupby(['shop_id', 'product_id'])['quantity_sold'].shift(1)
        features_df['demand_lag_7'] = features_df.groupby(['shop_id', 'product_id'])['quantity_sold'].shift(7)

        # Price features
        features_df['price_change'] = features_df.groupby(['shop_id', 'product_id'])['avg_selling_price'].pct_change()

        # Fill NaN values
        features_df = features_df.fillna(0)

        return features_df

    def prepare_training_data(self, shop_id, product_id, days_back=90):
        """
        Prepare training data for a specific shop-product combination

        Args:
            shop_id (int): Shop identifier
            product_id (int): Product identifier  
            days_back (int): Number of days to look back for training data

        Returns:
            tuple: (X_train, y_train) feature matrix and target vector
        """
        if not self.connection:
            if not self.connect_db():
                return None, None

        # Query historical sales data
        query = """
        SELECT date, shop_id, product_id, quantity_sold, total_revenue, avg_selling_price
        FROM daily_sales_history 
        WHERE shop_id = %s AND product_id = %s 
        AND date >= DATE_SUB(CURDATE(), INTERVAL %s DAY)
        ORDER BY date
        """

        try:
            df = pd.read_sql(query, self.connection, params=(shop_id, product_id, days_back))

            if df.empty:
                print(f"No data found for shop {shop_id}, product {product_id}")
                return None, None

            # Extract features
            features_df = self.extract_features(df)

            # Select feature columns
            feature_columns = [
                'day_of_week', 'day_of_month', 'month', 'is_weekend',
                'demand_7d_avg', 'demand_14d_avg', 'demand_30d_avg',
                'demand_lag_1', 'demand_lag_7', 'avg_selling_price', 'price_change'
            ]

            X = features_df[feature_columns]
            y = features_df['quantity_sold']

            # Remove rows with insufficient data (first few rows might have NaN in lag features)
            mask = ~X.isnull().any(axis=1)
            X = X[mask]
            y = y[mask]

            return X, y

        except Exception as e:
            print(f"Error preparing training data: {e}")
            return None, None

    def train_models(self, shop_id, product_id):
        """
        Train ML models for demand prediction

        Args:
            shop_id (int): Shop identifier
            product_id (int): Product identifier

        Returns:
            dict: Trained models and their performance metrics
        """
        X, y = self.prepare_training_data(shop_id, product_id)

        if X is None or len(X) < 10:  # Need at least 10 data points
            print(f"Insufficient data for training shop {shop_id}, product {product_id}")
            return None

        # Split data (80% train, 20% test)
        split_idx = int(0.8 * len(X))
        X_train, X_test = X[:split_idx], X[split_idx:]
        y_train, y_test = y[:split_idx], y[split_idx:]

        results = {}

        for model_name, model in self.models.items():
            try:
                # Scale features for linear regression
                if model_name == 'linear_regression':
                    scaler = StandardScaler()
                    X_train_scaled = scaler.fit_transform(X_train)
                    X_test_scaled = scaler.transform(X_test)
                    self.scalers[f"{shop_id}_{product_id}"] = scaler

                    model.fit(X_train_scaled, y_train)
                    predictions = model.predict(X_test_scaled)
                else:
                    model.fit(X_train, y_train)
                    predictions = model.predict(X_test)

                # Calculate metrics
                mae = mean_absolute_error(y_test, predictions)
                rmse = np.sqrt(mean_squared_error(y_test, predictions))
                r2 = r2_score(y_test, predictions)
                mape = np.mean(np.abs((y_test - predictions) / (y_test + 1e-8))) * 100

                results[model_name] = {
                    'model': model,
                    'mae': mae,
                    'rmse': rmse,
                    'r2': r2,
                    'mape': mape,
                    'predictions': predictions.tolist(),
                    'actual': y_test.tolist()
                }

                print(f"Model {model_name} - MAE: {mae:.2f}, RMSE: {rmse:.2f}, R2: {r2:.3f}, MAPE: {mape:.2f}%")

            except Exception as e:
                print(f"Error training {model_name}: {e}")
                continue

        return results

    def predict_future_demand(self, shop_id, product_id, days_ahead=30, model_name='random_forest'):
        """
        Predict future demand for a product in a shop

        Args:
            shop_id (int): Shop identifier
            product_id (int): Product identifier
            days_ahead (int): Number of days to predict ahead
            model_name (str): Model to use for prediction

        Returns:
            dict: Future demand predictions with dates
        """
        # First train the model
        results = self.train_models(shop_id, product_id)

        if not results or model_name not in results:
            print(f"Model {model_name} not available for prediction")
            return None

        model = results[model_name]['model']

        # Get recent data for feature generation
        X, y = self.prepare_training_data(shop_id, product_id, days_back=90)

        if X is None:
            return None

        # Generate future predictions
        predictions = []
        current_date = datetime.now().date()

        # Use last known values as starting point
        last_features = X.iloc[-1].copy()

        for day in range(days_ahead):
            future_date = current_date + timedelta(days=day+1)

            # Update date-based features
            last_features['day_of_week'] = future_date.weekday()
            last_features['day_of_month'] = future_date.day
            last_features['month'] = future_date.month
            last_features['is_weekend'] = int(future_date.weekday() >= 5)

            # Prepare features for prediction
            if model_name == 'linear_regression' and f"{shop_id}_{product_id}" in self.scalers:
                scaler = self.scalers[f"{shop_id}_{product_id}"]
                features_scaled = scaler.transform(last_features.values.reshape(1, -1))
                pred = model.predict(features_scaled)[0]
            else:
                pred = model.predict(last_features.values.reshape(1, -1))[0]

            # Ensure prediction is non-negative and reasonable
            pred = max(0, int(round(pred)))

            predictions.append({
                'date': future_date.strftime('%Y-%m-%d'),
                'predicted_demand': pred,
                'confidence': results[model_name]['r2']
            })

            # Update rolling features with the prediction (for next iteration)
            # This is a simplified approach - in practice, you might want more sophisticated updating
            last_features['demand_lag_1'] = pred

        return {
            'shop_id': shop_id,
            'product_id': product_id,
            'model_used': model_name,
            'predictions': predictions,
            'model_performance': {
                'mae': results[model_name]['mae'],
                'rmse': results[model_name]['rmse'],
                'r2': results[model_name]['r2'],
                'mape': results[model_name]['mape']
            }
        }

    def save_predictions_to_db(self, predictions_data):
        """
        Save predictions to the database

        Args:
            predictions_data (dict): Prediction results from predict_future_demand
        """
        if not self.connection:
            if not self.connect_db():
                return False

        try:
            cursor = self.connection.cursor()

            # Clear existing predictions for this shop-product combination
            delete_query = """
            DELETE FROM demand_forecasts 
            WHERE shop_id = %s AND product_id = %s AND forecast_date >= CURDATE()
            """
            cursor.execute(delete_query, (predictions_data['shop_id'], predictions_data['product_id']))

            # Insert new predictions
            insert_query = """
            INSERT INTO demand_forecasts 
            (forecast_date, shop_id, product_id, predicted_demand, confidence_score, model_used, forecast_period_days)
            VALUES (%s, %s, %s, %s, %s, %s, %s)
            """

            for pred in predictions_data['predictions']:
                cursor.execute(insert_query, (
                    pred['date'],
                    predictions_data['shop_id'],
                    predictions_data['product_id'],
                    pred['predicted_demand'],
                    pred['confidence'],
                    predictions_data['model_used'],
                    len(predictions_data['predictions'])
                ))

            self.connection.commit()
            print(f"Saved {len(predictions_data['predictions'])} predictions to database")
            return True

        except Exception as e:
            print(f"Error saving predictions: {e}")
            self.connection.rollback()
            return False
        finally:
            cursor.close()

# Example usage and configuration
if __name__ == "__main__":
    # Database configuration
    db_config = {
        'host': 'localhost',
        'user': 'root',
        'password': '', 
        'database': 'direct-edge',
        'charset': 'utf8mb4'
    }

    # Initialize predictor
    predictor = DemandPredictor(db_config)

    # Example: Predict demand for shop 6, product 1 (Lays)
    try:
        predictions = predictor.predict_future_demand(
            shop_id=6, 
            product_id=1, 
            days_ahead=30,
            model_name='random_forest'
        )

        if predictions:
            print("\nFuture Demand Predictions:")
            print("="*50)
            for pred in predictions['predictions'][:7]:  # Show first 7 days
                print(f"Date: {pred['date']}, Predicted Demand: {pred['predicted_demand']}")

            # Save to database
            predictor.save_predictions_to_db(predictions)

            print(f"\nModel Performance:")
            perf = predictions['model_performance']
            print(f"R² Score: {perf['r2']:.3f}")
            print(f"Mean Absolute Error: {perf['mae']:.2f}")
            print(f"Mean Absolute Percentage Error: {perf['mape']:.2f}%")

    except Exception as e:
        print(f"Prediction failed: {e}")

    finally:
        predictor.close_db()
