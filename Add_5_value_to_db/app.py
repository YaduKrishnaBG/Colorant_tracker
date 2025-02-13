from flask import Flask, render_template, request, jsonify
import mysql.connector

app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': '10.0.60.30',
    'user': 'colorant',
    'password': '**********',
    'database': 'colorant_tracker'
}

def create_colorant_data_table():
    """
    Creates the colorant_data table if it doesn't exist.
    """
    conn = None
    cursor = None

    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor()

        create_table_query = """
            CREATE TABLE IF NOT EXISTS colorant_data (
                `index` INT AUTO_INCREMENT PRIMARY KEY,
                machine_id VARCHAR(50),
                colorant_name VARCHAR(255),
                colorant_description VARCHAR(255),
                required_quantity DECIMAL(10, 2),
                mo_number VARCHAR(50),
                actual_weight DECIMAL(10, 2)
            )
        """
        cursor.execute(create_table_query)
        conn.commit()
    except Exception as e:
        print("Error creating colorant_data table:", e)
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

@app.route('/', methods=['GET', 'POST'])
def index():
    if request.method == 'POST':
        # Handle final form submission
        machine_id           = request.form.get('machine_id')
        colorant_name        = request.form.get('colorant_name')
        colorant_description = request.form.get('colorant_description')
        required_quantity    = request.form.get('required_quantity')
        mo_number            = request.form.get('mo_number')

        # Insert into the colorant_data table
        conn = None
        cursor = None
        try:
            conn = mysql.connector.connect(**DB_CONFIG)
            cursor = conn.cursor()

            insert_query = """
                INSERT INTO colorant_data
                (machine_id, colorant_name, colorant_description, required_quantity, mo_number)
                VALUES (%s, %s, %s, %s, %s)
            """
            cursor.execute(insert_query, (
                machine_id,
                colorant_name,
                colorant_description,
                required_quantity,
                mo_number
            ))
            conn.commit()

            return (f"<h3>Data Inserted Successfully!</h3>"
                    f"<p>Machine ID: {machine_id}</p>"
                    f"<p>Colorant Name: {colorant_name}</p>"
                    f"<p>Colorant Description: {colorant_description}</p>"
                    f"<p>Required Quantity: {required_quantity}</p>"
                    f"<p>MO Number: {mo_number}</p>")
        except Exception as e:
            return f"Error inserting data: {e}"
        finally:
            if cursor:
                cursor.close()
            if conn:
                conn.close()

    # If GET, just render the form
    return render_template('index.html')


@app.route('/get_colorant_info', methods=['POST'])
def get_colorant_info():
    """
    AJAX endpoint to fetch details from the database
    given an MO Number.
    Returns JSON with:
       - machine_id (work_center_code)
       - colorant_name (colorant)
       - colorant_description
       - required_quantity
    """
    data = request.json
    mo_number = data.get('mo_number')

    conn = None
    cursor = None
    result = {
        'success': False,
        'machine_id': '',
        'colorant_name': '',
        'colorant_description': '',
        'required_quantity': '',
        'error': ''
    }

    try:
        conn = mysql.connector.connect(**DB_CONFIG)
        cursor = conn.cursor(dictionary=True)

        # Match column names to your actual table structure
        query = """
            SELECT 
                work_center_code,
                colorant,
                colorant_description,
                required_quantity
            FROM colorant_requirements
            WHERE mo_number = %s
        """
        cursor.execute(query, (mo_number,))
        row = cursor.fetchone()

        if row:
            result['success'] = True
            result['machine_id']            = row['work_center_code']
            result['colorant_name']         = row['colorant']
            result['colorant_description']  = row['colorant_description']
            result['required_quantity']     = str(row['required_quantity'])
        else:
            result['error'] = f"No record found for MO Number: {mo_number}"

    except Exception as e:
        result['error'] = str(e)
    finally:
        if cursor:
            cursor.close()
        if conn:
            conn.close()

    return jsonify(result)


if __name__ == '__main__':
    # Create the table before starting the app (only if it doesn't exist)
    create_colorant_data_table()
    app.run(debug=True)
