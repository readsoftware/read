from threading import Lock
from werkzeug.wsgi import pop_path_info, peek_path_info
from app import create_dbAdmin, default_app, get_dbname_for_path


class PathDispatcher(object):

    def __init__(self, default_app, create_app):
        self.default_app = default_app
        self.create_app = create_app
        self.lock = Lock()
        self.instances = {}

    def get_application(self, path):
        print("path in disp get_app =", path)
        with self.lock:
            app = self.instances.get(path)
            if app is None:
                app = self.create_app(path)
                if app is not None:
                    self.instances[path] = app
            return app

    def __call__(self, environ, start_response):
        app = self.get_application(peek_path_info(environ))
        if app is not None:
#            print('env',environ)
#            pop_path_info(environ)
#            print('env',environ)
            print('startresp', start_response)
        else:
            app = self.default_app()
        return app(environ, start_response)


def make_app(path):
    dbname = get_dbname_for_path(path)
    if dbname is not None:
        return create_dbAdmin(dbname)


application = PathDispatcher(default_app, make_app)

if __name__ == "__main__":
    application()
