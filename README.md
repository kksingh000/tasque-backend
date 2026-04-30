# Tasque — Team Task Manager

A full-stack team task manager with role-based access control. Admins create projects and tasks; members work on what's assigned to them. Built as a take-home assignment.

**Live demo:** _replace with your Railway/Vercel URLs after deploy_
**Demo video:** _replace with your video link_

---

## Stack

| Layer | Tech |
|---|---|
| Backend | Laravel 11 (PHP 8.2), Sanctum auth, MySQL |
| Frontend | React 18 + Vite, React Router, Axios |
| Auth | Sanctum personal access tokens (Bearer) |
| Hosting | Railway (backend + MySQL), Vercel (frontend) — both free tier |

## Features

- **Authentication** — register, login, logout with token-based auth
- **Role-based access control** — `admin` and `member` roles with different permissions
- **Project management** — admins create projects and add members
- **Task management** — create, assign, prioritize, set due dates, track status (`todo` / `in_progress` / `done`)
- **Dashboard** — stats overview, "my tasks", overdue tasks
- **Kanban-style task board** per project
- **Member access scope** — non-admins only see projects they own or are members of

### Permission matrix

| Action | Admin | Project owner | Project member | Task assignee (member) |
|---|---|---|---|---|
| Create project | ✓ | — | — | — |
| Edit / delete project | ✓ | ✓ | — | — |
| Create / edit / delete tasks | ✓ | ✓ | — | — |
| Update status of own assigned task | ✓ | ✓ | — | ✓ |
| View project & its tasks | ✓ | ✓ | ✓ | ✓ |

---

## Project structure

```
team-task-manager/
├── backend/         Laravel 11 REST API
│   ├── app/
│   ├── routes/api.php
│   ├── database/migrations/
│   ├── start.sh           ← Railway entry
│   ├── nixpacks.toml
│   └── railway.json
└── frontend/        Vite + React SPA
    ├── src/
    ├── vercel.json
    └── package.json
```

---

## Local setup

### Prerequisites
- PHP 8.2+, Composer
- Node 18+, npm
- MySQL 8 running locally (or use the Railway DB once provisioned)

### Backend

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate

# Edit .env: set DB_DATABASE, DB_USERNAME, DB_PASSWORD for your local MySQL

php artisan migrate --seed
php artisan serve
# → http://localhost:8000
```

### Frontend

```bash
cd frontend
cp .env.example .env
# .env should contain: VITE_API_URL=http://localhost:8000/api

npm install
npm run dev
# → http://localhost:5173
```

### Demo accounts (after seeding)

| Role | Email | Password |
|---|---|---|
| Admin | `admin@taskmanager.com` | `Admin@123` |
| Member | `alice@taskmanager.com` | `Member@123` |
| Member | `bob@taskmanager.com` | `Member@123` |

---

## Deploying to Railway (backend) + Vercel (frontend)

### 1. Backend → Railway

1. Push the `backend/` folder to a new GitHub repo (or use the `backend/` subdir of the main repo).
2. On [railway.app](https://railway.app) → **New Project** → **Deploy from GitHub repo** → select the repo.
3. Add a **MySQL** plugin in the same project. Railway will inject `MYSQL_URL` / `MYSQLHOST` / etc. automatically.
4. In your service's **Variables** tab, set:
   ```
   APP_NAME=Tasque
   APP_ENV=production
   APP_DEBUG=false
   APP_URL=https://<your-railway-domain>.up.railway.app
   DB_CONNECTION=mysql
   DB_HOST=${{ MySQL.MYSQLHOST }}
   DB_PORT=${{ MySQL.MYSQLPORT }}
   DB_DATABASE=${{ MySQL.MYSQL_DATABASE }}
   DB_USERNAME=${{ MySQL.MYSQLUSER }}
   DB_PASSWORD=${{ MySQL.MYSQL_ROOT_PASSWORD }}
   CORS_ALLOWED_ORIGINS=https://<your-vercel-domain>.vercel.app
   ```
   > Note: from your previous Railway experience — use the **public proxy hostname** (`maglev.proxy.rlwy.net`) for `DB_HOST` only when connecting from outside Railway. Inside the Railway network, use the internal `MYSQLHOST` reference shown above.

5. The included `start.sh` runs migrations + seeders + starts PHP automatically. App will be live at the Railway URL.
6. Hit `/api/health` to verify.

### 2. Frontend → Vercel

1. Push the `frontend/` folder to a new GitHub repo (or use the same monorepo and set "Root Directory" = `frontend`).
2. On [vercel.com](https://vercel.com) → **Add New Project** → import the repo.
3. Set **Environment Variable**:
   ```
   VITE_API_URL=https://<your-railway-domain>.up.railway.app/api
   ```
4. Deploy. Vercel auto-detects Vite. The included `vercel.json` handles SPA routing.
5. After deployment, go back to your Railway backend's `CORS_ALLOWED_ORIGINS` and confirm it includes your Vercel URL.

---

## API reference

Base URL: `/api`. All protected routes require `Authorization: Bearer <token>` header.

### Auth
| Method | Path | Body | Auth |
|---|---|---|---|
| POST | `/auth/register` | `name, email, password, password_confirmation, role?` | — |
| POST | `/auth/login` | `email, password` | — |
| POST | `/auth/logout` | — | ✓ |
| GET | `/auth/me` | — | ✓ |

### Projects
| Method | Path | Notes |
|---|---|---|
| GET | `/projects` | Admins see all; members see only their own/joined |
| POST | `/projects` | Admin only. Body: `name, description?, member_ids[]?` |
| GET | `/projects/{id}` | Owner / member / admin |
| PUT | `/projects/{id}` | Owner or admin. Body: `name?, description?, status?, member_ids[]?` |
| DELETE | `/projects/{id}` | Owner or admin |
| GET | `/projects/available-members` | Lists all users (for the create/edit form) |

### Tasks
| Method | Path | Notes |
|---|---|---|
| GET | `/projects/{id}/tasks` | List tasks for project |
| POST | `/projects/{id}/tasks` | Owner or admin. Body: `title, description?, assigned_to?, status?, priority?, due_date?` |
| GET | `/tasks/{id}` | Anyone with project access |
| PUT | `/tasks/{id}` | Owner/admin can update all fields. Members can only update `status` of their own assigned tasks. |
| DELETE | `/tasks/{id}` | Owner or admin |

### Dashboard
| Method | Path | Notes |
|---|---|---|
| GET | `/dashboard` | Returns `stats`, `my_tasks`, `overdue_tasks` scoped to the user's access |

### Health
| Method | Path | Notes |
|---|---|---|
| GET | `/api/health` | Returns `{status: "ok"}` — used by Railway healthchecks |

---

---

## License

MIT
