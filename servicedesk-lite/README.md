# ServiceDesk Lite

Local helpdesk with ticket creation, status workflow, audit events and CSV exports. CLI: `python app.py new "Printer" "Paper jam" --priority high`, `python app.py status 1 in_progress`, `python app.py list`, `python app.py csv`.

Python 3.10+, SQLite, standard library only.

## Tests
Run `python -m unittest discover -v`.

## Security
Parameterized SQL, input validation, transactions and SQLite foreign keys. Single-user local prototype; no authentication, authorization, encryption at rest or network API. Restrict filesystem access, do not commit databases, and protect personal information. CSV may contain spreadsheet formula injection; sanitize before opening in Excel. Not certified or production deployed.

## License
MIT. See LICENSE and SUPPORT.md.
