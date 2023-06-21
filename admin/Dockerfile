FROM python:3.7-slim-buster
# MAINTAINER Stephen White <stephenawhite57@gmail.com>

ENV INSTALL_PATH /readadmin
RUN mkdir -p $INSTALL_PATH

WORKDIR $INSTALL_PATH

COPY requirements.txt requirements.txt
RUN pip install -r requirements.txt

COPY . .
# requires setup.py for setup of tools ignore for now
# RUN pip install --editable .

CMD gunicorn -b 0.0.0.0:8001 --access-logfile - "wsgi:application" --timeout 180