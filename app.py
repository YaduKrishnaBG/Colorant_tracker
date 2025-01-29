from flask import Flask, request, jsonify, render_template, redirect, url_for, flash
from flask_cors import CORS
from flask_socketio import SocketIO
import pymysql
import threading
import serial
import requests
import time
from datetime import datetime

# --- ADDED IMPORTS ---
import os
import subprocess

# Initialize the Flask application
app = Flask(__name__)
app.config['SECRET_KEY'] = 'secret!'  # Secret key for session management
CORS(app)  # Enable Cross-Origin Resource Sharing
socketio = SocketIO(app)  # Initialize SocketIO for real-time communication

# Configuration for MySQL database connection
db_config = {
    'host': '10.0.60.30',
    'user': 'colorant',
    'password': 'C0l0r@nt*66',
    'db': 'colorant_tracker',
    'charset': 'utf8mb4',
    'cursorclass': pymysql.cursors.DictCursor
}

@app.route('/weights', methods=['POST'])
def save_weight():
    """
    Endpoint to save a new weight reading.
    Expects a JSON payload with a 'weight' key.
    """
    data = request.json
    weight = data.get('weight', None)

    if weight is not None:
        try:
            # Convert the weight to a float
            weight_value = float(weight)
        except ValueError:
            # Return an error response if conversion fails
            return jsonify({'message': 'Invalid weight value'}), 400

        # Insert the weight into the MySQL database
        conn = pymysql.connect(**db_config)
        try:
            with conn.cursor() as cursor:
                sql = "INSERT INTO weights (value) VALUES (%s)"
                cursor.execute(sql, (weight_value,))
            conn.commit()
        except Exception as e:
            return jsonify({'message': f'Error saving weight: {e}'}), 500
        finally:
            conn.close()
        
        return jsonify({'message': 'Weight stored successfully'})
    
    # Return an error response if weight is not provided
    return jsonify({'message': 'Invalid weight'}), 400

@app.route('/weights/latest', methods=['GET'])
def get_latest_weight():
    """
    Endpoint to retrieve the most recent weight reading from the MySQL database.
    """
    conn = pymysql.connect(**db_config)
    try:
        with conn.cursor() as cursor:
            cursor.execute('SELECT value FROM weights ORDER BY timestamp DESC LIMIT 1')
            weight = cursor.fetchone()
    except Exception as e:
        return jsonify({'message': f'Error fetching weight: {e}'}), 500
    finally:
        conn.close()
    
    # Return the latest weight or 'No data' if no records exist
    return jsonify({'weight': weight['value'] if weight else 'No data'})

def read_serial_data():
    """
    Continuously reads data from the serial port connected to the scale.
    Cleans and processes the data, then posts it to the /weights endpoint.
    """
    try:
        # Establish serial connection (update 'COM3' and baudrate as needed)
        ser = serial.Serial('COM3', baudrate=9600, timeout=1)
    except serial.SerialException as e:
        # Exit the function if the serial connection fails
        print(f"Serial connection error: {e}")
        return

    while True:
        try:
            # Send command to request weight from the scale
            ser.write(b'S\r\n')  # Replace 'S' with the correct command for your scale
            response = ser.readline().decode('utf-8').strip()

            # Clean the response by removing unnecessary prefixes and units
            cleaned_response = response.replace("S S", "").strip()
            if cleaned_response.startswith("S "):
                cleaned_response = cleaned_response[2:].strip()

            cleaned_response = cleaned_response.replace("lb", "").strip()

            # If the response is valid (not empty, not 'I'), parse it
            if cleaned_response and cleaned_response != 'I':
                float_val = round(float(cleaned_response), 2)
                # Post the weight to the /weights endpoint
                requests.post('http://127.0.0.1:5000/weights', json={'weight': float_val})
            else:
                # If invalid or empty, just skip (or handle differently if you want)
                pass

        except ValueError:
            # Skip the reading if conversion to float fails
            pass
        except Exception as e:
            # Log any other exceptions
            print(f"Error reading serial data: {e}")
            pass
                
        # Delay to reduce CPU usage
        time.sleep(0.5)

@app.route('/', methods=['GET', 'POST'])
def index():
    """
    Main route handling both GET and POST requests.
    - GET: Displays the form with the latest weight.
    - POST: Processes the form submission and stores data in MySQL,
            then runs the VBScript in the 'scripts' folder.
    """
    latest_weight = None  # Initialize variable

    # On GET request, fetch the latest weight from MySQL
    if request.method == 'GET':
        try:
            conn = pymysql.connect(**db_config)
            with conn.cursor() as cursor:
                cursor.execute('SELECT value FROM weights ORDER BY timestamp DESC LIMIT 1')
                row = cursor.fetchone()
                latest_weight = row['value'] if row else None
        except Exception as e:
            flash(f"Error fetching latest weight: {e}")
        finally:
            conn.close()

    # On POST request, handle form submission
    if request.method == 'POST':
        machine_id = request.form.get('machine')
        colorant_name = request.form.get('colorant_name')
        weight = request.form.get('weight')  # Weight is received as a string

        # Ensure all form fields are provided
        if machine_id and colorant_name and weight:
            try:
                # Convert weight to float
                weight_val = float(weight)
            except ValueError:
                # Flash an error message if conversion fails
                flash("Invalid weight value.")
                return redirect(url_for('index'))

            # Connect to the MySQL database
            conn = pymysql.connect(**db_config)
            try:
                with conn.cursor() as cursor:
                    # SQL query to insert the colorant usage data
                    sql = """
                    INSERT INTO colorant_usage (machine_id, colorant_name, weight, entry_date)
                    VALUES (%s, %s, %s, %s)
                    """
                    cursor.execute(sql, (machine_id, colorant_name, weight_val, datetime.now()))
                conn.commit()
                # Flash a success message upon successful insertion
                flash("Data submitted successfully!")
                
                # --- RUN THE VBSCRIPT HERE ---
                try:
                    subprocess.run(
                        ["cscript", os.path.join("scripts", "print_label.vbs")],
                        check=True
                    )
                except subprocess.CalledProcessError as e:
                    flash(f"Error running VBScript: {e}")
                except Exception as e:
                    flash(f"Unexpected error running VBScript: {e}")

            except Exception as e:
                # Flash an error message if insertion fails
                flash(f"Error inserting data: {e}")
            finally:
                # Ensure the database connection is closed
                conn.close()

            # Redirect back to the index page after form submission
            return redirect(url_for('index'))

    # Render the index.html template with the latest weight
    return render_template('index.html', latest_weight=latest_weight)

@socketio.on('barcode_detected')
def handle_barcode(data):
    """
    SocketIO event handler for barcode detection.
    Emits the barcode data to update the colorant name field in real-time.
    """
    socketio.emit('update_colorant_name', data)

if __name__ == '__main__':
    # Start the serial data reading thread
    threading.Thread(target=read_serial_data, daemon=True).start()
    # Print a message indicating that the application is starting
    print("Starting Colorant Usage Tracker...")
    print("Access the application at http://127.0.0.1:5000/")
    # Run the Flask-SocketIO application
    socketio.run(app, debug=False, host='127.0.0.1', port=5000)
