# Bank Transaction Importer

## Overview

A simple application for importing bank transactions from CSV, JSON, and XML files.

The system validates each record, stores valid transactions, logs invalid ones, and provides a clear interface for reviewing import history and failures.

------------------------------------------------------------------------

## Tech Stack

- Backend: Laravel 12 (PHP 8.3)
- Frontend: Vue 3 (Vite)
- Database: MySQL 8
- Environment: Docker

------------------------------------------------------------------------

## Features

### Backend

- File upload support for CSV, JSON, and XML
- Record-level validation:
  - account_number: required, valid IBAN
  - amount: must be greater than 0
  - currency: must be a 3-letter code
- Stores:
  - valid records → transactions table
  - invalid records → import_logs table
  - import metadata → imports table
- REST API:
  - POST /api/imports
  - GET /api/imports
  - GET /api/imports/{id}

### Frontend

- File upload view
- Import history list
- Import details view
- Error log preview

------------------------------------------------------------------------


## Installation

1. Clone the repository:
```bash
git clone git@github.com:Norivo7/bank-transaction-importer.git
```

2. Go to ```/backend``` folder, copy ```.env.example``` to ```.env```.


3. navigate to the project directory, build and start the containers using:
  ```bash
  docker compose up --build
  ```

4. Install backend dependencies:
```bash
docker compose exec app composer install
```

5. Run database migrations:
```bash
docker compose exec app php artisan migrate
```


## Frontend

### Access the UI at:

http://localhost:5173

------------------------------------------------------------------------

## Sample Files

Located in: ```sample-files/```


Includes _success_, _partial_, and _failed_ cases for CSV, JSON, and XML formats

------------------------------------------------------------------------


## API Testing

### Upload file
POST /api/imports

Content-Type: multipart/form-data

Body:
file: <CSV | JSON | XML file>

Example (curl):
```
curl -X POST http://localhost:8000/api/imports \
-F "file=@sample-files/csv/partial.csv"
```


### List imports
GET /api/imports

Example:
```
curl http://localhost:8000/api/imports
```


### Get import details
GET /api/imports/{id}

Example:
```
curl http://localhost:8000/api/imports/1
```

### Expected Responses

- success → all records valid
- partial → some records failed validation
- failed → all records invalid