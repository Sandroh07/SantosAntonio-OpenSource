# Training Attendance Manager

Manage learners, sessions and attendance with a local SQLite database. CLI commands: `python app.py learner "Ana"`, `python app.py session "Excel" 2026-11-07`, `python app.py mark 1 1 present`, `python app.py csv`.

Python 3.10+, SQLite, standard library only.

## Tests
Run `python -m unittest discover -v`.

## Security
Parameterized SQL, input validation, transactions and SQLite foreign keys. Single-user local prototype; no authentication, authorization, encryption at rest or network API. Restrict filesystem access, do not commit databases, and protect personal information. CSV may contain spreadsheet formula injection; sanitize before opening in Excel. Not certified or production deployed.

## License
MIT. See LICENSE and SUPPORT.md.
