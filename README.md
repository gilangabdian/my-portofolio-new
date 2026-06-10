# 🎨 My Portfolio — Full-Stack Web with Admin Panel

A personal portfolio website built with a **Neo-Brutalism / Comic Book** design theme. It features a public-facing showcase and a private admin panel to manage all portfolio content. The project is structured as a **monorepo**, separating the frontend and backend.

---

## 🗂️ Project Structure

```
my-portofolio-web/
├── frontend/          # Vue 3 SPA (Vite + TailwindCSS v4)
├── backend/           # Laravel 12 REST API
└── .github/
    └── workflows/
        └── ci.yml     # GitHub Actions CI/CD
```

---

## ✨ Features

### 🌐 Public Portfolio
- **Homepage** — Hero section, quick highlights
- **About** — Personal bio and overview
- **Projects** — Filterable project showcase with live demo & GitHub links, featured badge
- **Artworks** — Gallery of digital art and redraws
- **Certificates** — Academic and professional certifications
- **Contacts** — Social media and contact links

### 🔐 Admin Panel (Protected)
Full CRUD management for all portfolio content via a private dashboard accessible at `/admin/dashboard`.

| Section | Capabilities |
|---|---|
| **Profile** | Update bio, photo, availability status |
| **Projects** | Create / Edit / Delete, set featured, attach skills, upload thumbnail |
| **Skills** | Create / Edit / Delete, icon identifier support |
| **Experiences** | Create / Edit / Delete, date range, description |
| **Certificates** | Create / Edit / Delete, upload image |
| **Artworks** | Upload digital art images / redraws |
| **Contacts** | Manage contact links & social media |
| **Visitors** | View realtime visitor count |

Authentication is token-based using **Laravel Sanctum**. The frontend stores the token in `localStorage` and guards routes using Vue Router's global navigation guard.

---

## 🛠️ Tech Stack

### Frontend
| Technology | Purpose |
|---|---|
| [Vue 3](https://vuejs.org/) | UI Framework (Composition API) |
| [Vite](https://vitejs.dev/) | Build tool & dev server |
| [TailwindCSS v4](https://tailwindcss.com/) | Utility-first styling |
| [Vue Router 4](https://router.vuejs.org/) | Client-side SPA routing |
| [GSAP](https://gsap.com/) | Scroll and entrance animations |
| [Iconify for Vue](https://iconify.design/) | Icon library |
| [SweetAlert2](https://sweetalert2.github.io/) | Stylish dialog & toast notifications |
| [marked](https://marked.js.org/) | Markdown rendering |
| [@vueuse/core](https://vueuse.org/) | Composition utility helpers |

### Backend
| Technology | Purpose |
|---|---|
| [Laravel 12](https://laravel.com/) | REST API Framework |
| [PHP 8.2+](https://www.php.net/) | Server runtime |
| [Laravel Sanctum](https://laravel.com/docs/sanctum) | API Token Authentication |
| [Cloudinary](https://cloudinary.com/) | Cloud image storage & delivery |
| [MySQL](https://www.mysql.com/) | Production database |
| [SQLite](https://www.sqlite.org/) | Testing database |
| [PHPUnit](https://phpunit.de/) | Automated testing |

---

## 📬 Want to Collaborate or Have Questions?

This is a **personal portfolio project** — the source code is shared publicly for **learning and showcasing purposes only**, not as a template for reuse.

If you're interested in how it was built, have questions, or want to collaborate, feel free to reach out through my portfolio's contact page.

> ⚠️ **Please do not clone or deploy this project and present it as your own work.**
> This repository is protected under the [MIT License](LICENSE), which requires proper credit attribution.

<!--
## 🚀 Getting Started

### Prerequisites
- Node.js >= 20
- PHP >= 8.2
- Composer
- MySQL (for local dev) or SQLite (for testing)
- A Cloudinary account

### 1. Clone the Repository

```bash
git clone https://github.com/gilangages/my-portofolio-new.git
cd my-portofolio-new
```

### 2. Backend Setup

```bash
cd backend
composer install
cp .env.example .env
# Fill in DB credentials and CLOUDINARY_URL in .env
php artisan key:generate
php artisan migrate
# Optional: php artisan db:seed
php artisan serve
```

### 3. Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

> Make sure VITE_API_BASE_URL in the frontend points to http://localhost:8000/api
-->

---

## 🔌 API Overview

All endpoints are prefixed with `/api`. The backend exposes the following routes:

**Public (No Auth)**
- `GET /profile`
- `GET /projects` · `GET /projects/{id}`
- `GET /skills` · `GET /skills/{id}`
- `GET /experiences` · `GET /experiences/{id}`
- `GET /certificates` · `GET /certificates/{id}`
- `GET /contacts` · `GET /contacts/{id}`
- `GET /artworks` · `GET /artworks/{id}`
- `POST /visitors`
- `POST /admin/login`

**Protected (Bearer Token required)**
- `DELETE /admin/logout`
- `GET /admin/visitors/count`
- Full CRUD (`POST`, `PUT`, `DELETE`) for: `projects`, `skills`, `experiences`, `certificates`, `contacts`, `artworks`
- `POST /profile` (update)

---

## 🤖 CI/CD

This project uses **GitHub Actions** to automatically validate both the backend and frontend on every push or pull request to the `main` branch.

Pipeline (`.github/workflows/ci.yml`):
- ✅ **Backend** → Install Composer, run migrate, run `php artisan test`
- ✅ **Frontend** → Install NPM, run `npm run build`

---

## 📁 Frontend Pages & Routes

| Route | Component | Auth Required |
|---|---|---|
| `/` | Homepage | ❌ |
| `/about` | About | ❌ |
| `/projects` | AllProjects | ❌ |
| `/artworks` | AllArtworks | ❌ |
| `/certificates` | AllCertificates | ❌ |
| `/contacts` | AllContacts | ❌ |
| `/admin/login` | AdminLogin | Guest Only |
| `/admin/dashboard` | AdminDashboard | ✅ |
| `/admin/dashboard/projects` | Project List | ✅ |
| `/admin/dashboard/skills` | Skill List | ✅ |
| `/admin/dashboard/experiences` | Experience List | ✅ |
| `/admin/dashboard/certificates` | Certificate List | ✅ |
| `/admin/dashboard/contacts` | Contact List | ✅ |
| `/admin/dashboard/artworks` | Artwork List | ✅ |
| `/admin/dashboard/profile` | Update Profile | ✅ |

---

## 📄 License

This project is open source and available under the [MIT License](LICENSE).
