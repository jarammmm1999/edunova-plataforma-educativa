# archivo: buscar_contenido.py
from flask import Flask, request, jsonify
import wikipedia

app = Flask(__name__)
wikipedia.set_lang("es")  # Idioma en español

@app.route('/buscar-contenido', methods=['POST'])
def buscar_contenido():
    data = request.get_json()
    titulo = data.get("titulo", "")

    if not titulo:
        return jsonify({"error": "Título vacío"}), 400

    try:
        resumen = wikipedia.summary(titulo, sentences=5)
        return jsonify({"contenido": resumen})
    except Exception as e:
        return jsonify({"error": str(e)}), 500

if __name__ == '__main__':
    app.run(debug=True)
