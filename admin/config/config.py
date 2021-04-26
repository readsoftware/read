# file used to set app configuration
# NOTE the database is connected to FLASK using
#  SQLALCHEMY_DATABASE_URI
#  which needs to be configured before db.init_app
#  this can be done uding this file as follows:
# (env)
#     set/export  APP_CONFIGURATION_SETUP=\
#                   "pathfromapptothisconfigfile.configclassname"
#     set/export  READ_DBNAME="postgresREADdatabasename"
#
# (app.py)
#
# from flask import Flask
# import os
#
# app = Flask(__name__)
# app_config_obj = os.environ['APP_CONFIGURATION_SETUP']
# dbname = os.getenv('READ_DBNAME')
# app.config.from_object(app_config_obj)
# app.config['SQLALCHEMY_DATABASE_URI'] = app.config['DATABASE_BASEURI']\
#  + str(dbname)
#

class Config(object):
    DEBUG = False
    TESTING = False
    DATABASE_BASEURI = 'postgresql+psycopg2://postgres:gandhari@db/'
    SQLALCHEMY_TRACK_MODIFICATIONS = False


class ProdConfig(Config):
    ENV = "production"
    SECRET_KEY = "98d0tie372SD90AS)(dd78(*&ASWWHD08A"
    SQLALCHEMY_ECHO = False


class DevConfig(Config):
    DEBUG = True
    ENV = "development"
    SECRET_KEY = "9anvbf*O*^(das0ˆSDer73u5ba67900SA(D*66"
    SQLALCHEMY_ECHO = True


class TestConfig(Config):
    ENV = "testing"
    TESTING = True
    SQLALCHEMY_ECHO = True
