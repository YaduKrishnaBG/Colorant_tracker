from flask import Flask, render_template, request, jsonify
import mysql.connector

app = Flask(__name__)

# Database configuration
DB_CONFIG = {
    'host': '10.0.60.30',
    'user': 'colorant',
    'password': 'C0l0r@nt*66',
    'database': 'colorant_tracker'
}

@app.route('/', methods=['GET', 'POST'])
def index():
    if request.method == 'POST':
        # Handle final form submission
        machine_id           = request.form.get('machine_id')
        colorant_name        = request.form.get('colorant_name')
        colorant_description = request.form.get('colorant_description')
        required_quantity    = request.form.get('required_quantity')
        mo_number            = request.form.get('mo_number')

        # TODO: Process or store these values as needed
        # For now, just return them for demonstration:
        return (f"Data received:<br>"
                f"Machine ID: {machine_id}<br>"
                f"Colorant Name: {colorant_name}<br>"
                f"Colorant Description: {colorant_description}<br>"
                f"Required Quantity: {required_quantity}<br>"
                f"MO Number: {mo_number}")

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
            result['machine_id']             = row['work_center_code']
            result['colorant_name']          = row['colorant']
            result['colorant_description']   = row['colorant_description']
            result['required_quantity']      = str(row['required_quantity'])
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
    app.run(debug=True)
