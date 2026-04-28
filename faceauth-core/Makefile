.PHONY: lint run init-mysql

lint:
	python -m compileall -q .

run:
	uvicorn app.main:app --host 0.0.0.0 --port 8000

init-mysql:
	python scripts/init_mysql.py
