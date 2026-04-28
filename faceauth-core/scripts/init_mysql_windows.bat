@echo off
setlocal

if not exist .venv (
  py -3 -m venv .venv
)

call .venv\Scripts\activate
pip install -r requirements.txt
python scripts\init_mysql.py
