from flask import Flask, render_template, request, redirect, url_for, session
from flask_sqlalchemy import SQLAlchemy
import config

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
    if request.method == 'POST':
        if request.form['password'] == config.LOGIN_PASSWORD:
            session['logged_in'] = True
            return redirect(url_for('index'))
    return render_template('login.html')

@app.route('/logout')
def logout():
    session.clear()
    return render_template('logout.html')

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


