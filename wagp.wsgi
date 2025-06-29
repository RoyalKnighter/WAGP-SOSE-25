import sys
import site

# Pfad zu deinem virtuellen Environment
site.addsitedir('/var/www/wagp/venv/lib/python3.12/site-packages')

# Projektverzeichnis zu sys.path hinzufÃÂ¼gen
sys.path.insert(0, '/var/www/wagp')

from app import app as application
