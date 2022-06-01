'''
@author      Stephen White  <stephenawhite57@gmail.com>
@author      Selaudin Agolli <a.selaudin@gmail.com>
@copyright   Stephen White
@link        https://github.com/readsoftware
@version     1.0
@license     <http://www.gnu.org/licenses/>
@package     READ.Admin

Flask Admin Main Application
'''
from flask import Flask
from flask import send_file
from flask_admin import Admin, AdminIndexView
import ORM.readFlaskModel as rfm
import ORM.readFlaskView as rfv
from extensions import csrf
from ORM.readFlaskModel import db
# from livereload import Server
import os

dbAdminUsername = os.getenv('POSTGRES_USER')
dbAdminPassword = os.getenv('POSTGRES_PASSWORD')

# load_dotenv()


def default_app():
    app = Flask(__name__)

    @app.route("/")
    def index():
        return "<h2> READ Central Admin coming soon.</h2> <br/> " \
               "<span>Try going direct to admin your database using localhost:8001/<i>yourdbname</i></span> "

    @app.route("/<name>")
    def unknown(name):
        return f"Failed connecting to dbname: {name}"

    @app.route("/coco")
    def cocoSpecs():
        try:
            return f"<span>{os.environ.get('POSTGRES_DB')} COCO JSON URL Specs: <b>serverName</b>/coco/<b>dbName</b>/filter/<b>filterSpec</b></span>"
        except Exception as e:
            return str(e)

    @app.route("/coco/<dbName>")
    def coco(dbName=None):
        try:
            from module.coco import getCOCO
            # return f"{dbName}"
            getCOCO(db=dbName)
            return send_file('coco.json', attachment_filename='coco-' + dbName + '.json', as_attachment=True)
        except Exception as e:
            return str("error: ") + str(e)

    @app.route("/coco/<dbName>/filter/<filterspec>")
    def cocoFilter(dbName, filterspec):
        try:
            import module.readQueryCursor as RQC
            import psycopg2
            import json
            from module.coco import getCOCO, getFilteredCOCO, getFilteredCOCO_OR, getFilteredCOCO_AND

            # Multifiltering
            if '*' and '+' in filterspec:
                return "Filtering with both conditions not done!"
            # AND condition filtering
            elif '*' in filterspec:
                # if there is and logical condition AND or OR, then we will update the coco to get the last result, not
                # before, because then the user will have to wait for the updated file. If the filtering was invalid,
                # then they waited for nothing.
                getCOCO(db=dbName)
                filtersAND = filterspec.split('*')
                getFilteredCOCO_AND(file="coco.json", listt=filtersAND)
                return send_file('cocoFilteredAND.json',
                                 attachment_filename='readCocoFiltered-' + dbName + "-" + filterspec + '.json',
                                 as_attachment=True)
            # OR condition filtering
            elif '+' in filterspec:
                getCOCO(db=dbName)
                filtersOR = filterspec.split('+')
                getFilteredCOCO_OR(file="coco.json", listt=filtersOR)
                return send_file('cocoFilteredOR.json',
                                 attachment_filename='readCocoFiltered-' + dbName + "-" + filterspec + '.json',
                                 as_attachment=True)
            # NO condition filtering
            else:
                # if there aren't any conditions, then check for a single filtering
                getCOCO(db=dbName)
                filters = [filterspec]
                getFilteredCOCO(file="coco.json", listt=filters)
                return send_file('cocoFiltered.json',
                                 attachment_filename='readCocoFiltered-' + dbName + "-" + filterspec + '.json',
                                 as_attachment=True)

        except Exception as e:
            return str(e)

    print('returning default app')
    return app


def checkDBName(dbname):
    # todo check is valid db and user has access
    return dbname


def get_dbname_for_path(path):
    checkedDBName = checkDBName(path)
    print("got dbname", checkedDBName, "for path", path)
    return checkedDBName


def create_dbAdmin(dbname=None):
    '''
    Create app using app factory pattern

    :param dbname: name of database to admin
    :return :Flask app
    '''
    print("create dbAdmin for", dbname)
    # create Flask app obj
    app = Flask(__name__, instance_relative_config=True)

    app.config.from_object('config.settings')  # load config/settings.py
    app.config.from_pyfile('settings.py', silent=True)  # load instance/settings.py if exist
    if dbname is None or dbname == '':
        return None  # not using Admin for db, signal default startup
    app.config['SQLALCHEMY_DATABASE_URI'] = \
        f'postgresql+psycopg2://{dbAdminUsername}:{dbAdminPassword}@db/{str(dbname)}'
    print(app.config['SQLALCHEMY_DATABASE_URI'])
    print("key", app.config['SECRET_KEY'])
    try:
        db.init_app(app)
        app.dbname = dbname
        admin = Admin(app,
                      template_mode='bootstrap4',
                      index_view=AdminIndexView(name=dbname,
                                                url='/' + dbname, ))
        with app.app_context():
            rfv.addAllViews(admin, rfm, db)
            db.create_all()
    except Exception as err:
        print(err)
        return None

    # @app.route('/favicon.ico')  # handle browser request for icon
    # def favicon():
    #     return app.send_static_file('vreadlogo.ico')  # requires static folder to contain file

    return app


def extensions(app):
    """
    Register 0 or more extensions (mutates the app passed in).

    :param app: Flask application instance
    :return: None
    """
    csrf.init_app(app)
    db.init_app(app)
#    login_manager.init_app(app)

    return None


if __name__ == "__main__":
    # app.run()
    server = Server(wsgi_app)
    server.serve()
