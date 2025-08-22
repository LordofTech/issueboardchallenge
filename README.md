# Issues Board

## Project Overview

The **Issues Board** is a small full-stack web application that allows users to create, view, and manage issues. The system supports **real-time updates** so that new or updated issues appear immediately in the interface.

This version uses **Laravel (backend) + Blade + Vanilla JS (frontend)**. React and Vite have been removed for simplicity and reliability.

**Tech Stack**:

* **Backend:** Laravel 10 + MySQL
* **Frontend:** Blade templates + Vanilla JavaScript
* **Real-time updates:** Laravel Reverb (WebSocket)
* **Dependencies:** Laravel default packages; no React or Vite frontend dependencies

---

## Project Structure

```
├── app/
│   └── Http/
│       └── Controllers/
│           └── Api/
│               └── IssueController.php       # API logic for managing issues
├── database/
│   ├── migrations/                            # Database migration files
│   └── seeders/                               # Seeder files for demo data
├── resources/
│   ├── css/
│   │   └── app.css                             # Optional CSS file
│   └── views/
│       └── issues/
│           └── index.blade.php                 # Complete frontend with Vanilla JS
├── routes/
│   ├── web.php                                 # Serves the Blade view
│   └── api.php                                 # API endpoints
├── .env                                        # Environment configuration
├── package.json                                # JS dependencies (for CSS refresh)
├── vite.config.js                              # Simplified Vite config (CSS only)
└── README.md                                   # This file
```

---

## Setup Instructions

### 1. Clone the repository

```bash
git clone <repository-url>
cd issues-board
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Configure Environment

Copy `.env.example` to `.env` and update with your settings:

```env
APP_NAME="Issues Board"
APP_ENV=local
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=issues_board
DB_USERNAME=root
DB_PASSWORD=

BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=

VITE_REVERB_APP_KEY=local
VITE_REVERB_HOST=127.0.0.1
VITE_REVERB_PORT=6001
VITE_REVERB_SCHEME=http
```

> Make sure your database exists and credentials are correct.

### 4. Run Migrations and Seeders

```bash
php artisan migrate --seed
```

### 5. Start Laravel Server

```bash
php artisan serve
```

### 6. Start WebSocket Server (Laravel Reverb)

```bash
php artisan websockets:serve
```

### 7. Access Application

Open your browser at:

```
http://localhost:8000/
```

You should see the **Issues Board** with live updates.

---

## API Endpoints

**GET /api/issues**

* Retrieves a paginated list of issues.
* Supports filtering by `status` or `priority`.
* Example Response:

```json
{
  "data": [
    {
      "id": 1,
      "title": "Sample Issue",
      "description": "This is a test issue",
      "status": "open",
      "priority": "high",
      "updated_at": "2025-08-22T15:00:00Z"
    }
  ],
  "meta": { "page": 1, "per_page": 15, "total": 1 }
}
```

**POST /api/issues**

* Creates a new issue.
* Mandatory fields: `title`, `description`.
* Optional fields: `status`, `priority`.
* Response includes created issue and broadcast event info.

**Optional PATCH /api/issues/{id}**

* Updates `status` or `priority` for an existing issue.
* Emits a real-time broadcast event to update frontend automatically.

---

## Real-Time Features

* **WebSocket Channel:** `issues`
* **Events:** `IssueCreated`, `IssueUpdated`
* **Frontend Integration:** Vanilla JS subscribes to channels and updates the DOM immediately.
* **Visual Cue:** Newly created or updated issues are highlighted briefly.

---

## Logging

* All API requests are logged with:

  * HTTP method & route
  * Response code
  * Duration
* Validation failures and issue creation/update events are logged.
* Broadcast events are also logged for monitoring.

---

## Notes on Setup Choices

* **Blade + Vanilla JS** was chosen to simplify the frontend and remove React/Vite dependency issues.
* This ensures the application works immediately after cloning without complex build steps.
* Real-time updates are fully supported via Laravel Reverb without extra frontend frameworks.

---

## Testing

* Run feature test for issue creation:

```bash
php artisan test --filter=IssueCreationTest
```

* Test WebSocket real-time functionality by creating/updating issues in the UI.

---

## Optional Enhancements (Stretch)

* Optimistic UI updates
* Audit log table for create/update events
* Rate-limiting for write endpoints

---

 **Summary:**
The project is fully functional using **Laravel + Blade + Vanilla JS**, supports real-time updates, validation, logging, and provides a clean, simple frontend. This setup satisfies all core assessment requirements.

