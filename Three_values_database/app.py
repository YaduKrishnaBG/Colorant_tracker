from flask import Flask, render_template, request

app = Flask(__name__)

@app.route('/', methods=['GET', 'POST'])
def index():
    if request.method == 'POST':
        machine_id = request.form.get('machine_id')
        colorant_name = request.form.get('colorant_name')
        mo_number = request.form.get('mo_number')

        # Handle the data as needed. For example:
        return f"Data received: Machine ID={machine_id}, Colorant Name={colorant_name}, MO Number={mo_number}"

    return render_template('index.html')


if __name__ == '__main__':
    app.run(debug=True)
