from flask import Flask, render_template, request, redirect, url_for, session
from flask_sqlalchemy import SQLAlchemy
import config
from openai import OpenAI
import json
import re
import os
import random

client = OpenAI(api_key=config.OPENAI_API_KEY)

app = Flask(__name__)
app.secret_key = config.SECRET_KEY
app.config['SQLALCHEMY_DATABASE_URI'] = config.DB_PATH

db = SQLAlchemy(app)

# Datenbankmodell für Sources
class Source(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    word = db.Column(db.String(100), nullable=False)
    explanation = db.Column(db.String(500), nullable=True)

class ProblemWord(db.Model):
    id = db.Column(db.Integer, primary_key=True)
    word = db.Column(db.String(200))
    explanation = db.Column(db.String(500))

@app.route('/')
def index():
    if not session.get('logged_in'):
        return redirect(url_for('login'))
    return render_template('index.html')

@app.route('/login', methods=['GET', 'POST'])
def login():
    password = None

    if request.method == 'POST':
        password = request.form.get('password')
    elif request.method == 'GET':
        password = request.args.get('password')

    if password == config.LOGIN_PASSWORD:
        session['logged_in'] = True
        return redirect(url_for('index'))

    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    return redirect(url_for('login'))

@app.route('/sources', methods=['GET', 'POST'])
def sources():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    if request.method == 'POST':
        word = request.form['word']
        explanation = request.form['explanation']
        new_source = Source(word=word, explanation=explanation)
        db.session.add(new_source)
        db.session.commit()

    sources = Source.query.all()
    return render_template('sources.html', sources=sources)

@app.route('/sources/delete/<int:id>')
def delete_source(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    Source.query.filter_by(id=id).delete()
    db.session.commit()
    return redirect(url_for('sources'))


@app.route('/problems', methods=['GET', 'POST'])
def problems():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    if request.method == 'POST':
        word = request.form['word']
        explanation = request.form['explanation']
        new_source = ProblemWord(word=word, explanation=explanation)
        db.session.add(new_source)
        db.session.commit()

    problems= ProblemWord.query.all()
    return render_template('problems.html', problems=problems)

@app.route('/problems/delete/<int:id>')
def delete_problems(id):
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    ProblemWord.query.filter_by(id=id).delete()
    db.session.commit()
    return redirect(url_for('problems'))

@app.route('/save_error', methods=['GET', 'POST'])
def save_error():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    if request.method == 'POST':
        word = request.form['word']
        explanation = request.form['explanation']
        new_source = ProblemWord(word=word, explanation=explanation)
        db.session.add(new_source)
        db.session.commit()

    if word and explanation:
        new_problem = ProblemWord(term=word, definition=explanation, type='memory')
        db.session.add(new_problem)
        db.session.commit()

    return '', 204  # No Content

@app.route('/memory', methods=['GET', 'POST'])
def memory():
    if not session.get('logged_in'):
        return redirect(url_for('login'))

    memory_data = None
    memory_items = []
    error = None

    sources = [s.explanation for s in Source.query.all()]
    problems = ProblemWord.query.all()

    if request.method == 'POST':
        anzahl = int(request.form['paare'])

        prompt = f"Erstelle {anzahl} einzigartige Paare aus Fachbegriff und Erklärung im JSON-Format:\n\n"
        prompt += "Beispiel:\n[{\"term\": \"Osmose\", \"definition\": \"Diffusion von Wasser durch eine semipermeable Membran.\"}, ...]\n\n"
        prompt += "Bitte füge keine Sonderzeichen in die json ein, da javascript diese sonst nicht zu einer json umwandeln kann.\n"
        prompt += "Basierend auf diesen Quellen:\n\n" + "\n".join(sources)
        prompt += "\n\nZusätzliche Problemwörter:\n"
        for p in problems:
            prompt += f"{p.word}: {p.explanation}\n"

        try:
            result = client.chat.completions.create(model="gpt-4o",
            messages=[{"role": "user", "content": prompt}],
            max_tokens=1000)
            antwort = result.choices[0].message.content.strip()

            # Entferne mögliche ```json Blöcke
            antwort = re.sub(r'^```json|```$', '', antwort, flags=re.MULTILINE).strip()
            memory_data = json.loads(antwort)

            # Buttons vorbereiten
            pair_id = 0
            for pair in memory_data:
                term = pair['term']
                definition = pair['definition']
                memory_items.append({'value': term, 'pair_id': pair_id, 'type': 'term'})
                memory_items.append({'value': definition, 'pair_id': pair_id, 'type': 'definition'})
                pair_id += 1
            random.shuffle(memory_items)

        except Exception as e:
            error = f"Fehler beim Generieren oder Verarbeiten der Daten: {str(e)}"

    return render_template('memory.html', memory_data=memory_data, memory_items=memory_items, error=error)




