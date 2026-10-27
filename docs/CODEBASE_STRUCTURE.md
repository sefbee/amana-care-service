# Codebase structure

The Laravel application is organized by product responsibility. `app/Admin` owns internal staff operations; `app/FrontEnd` owns the headless CMS that controls the separate public website. The public Next.js/React website itself is not part of this Laravel repository.

## Product areas

```text
app/
├── Admin/
│   ├── Controllers/       Staff dashboard, identity, catalog, CRM, and admin-console actions
│   ├── Models/            Staff, access-control, catalog, CRM, and household records
│   ├── Services/          Internal workflows, file upload, notification, and SMS services
│   ├── Authorization/     Admin permission helpers
│   ├── Attributes/        Admin permission metadata
│   └── Rules/             Admin-domain validation rules
├── FrontEnd/
│   └── Cms/
│       ├── Controllers/   CMS administration and public CMS API endpoints
│       ├── Models/        CMS-owned content, navigation, settings, and media records
│       ├── Services/      Publishing and public-content workflow
│       └── Support/       CMS schemas and HTML sanitization
├── Http/                  Shared Laravel base controller, authentication, API auth, middleware, and requests
├── View/                  Shared Blade components
├── Concerns/ Traits/      Shared framework/application helpers
└── Providers/             Application service providers

resources/views/
├── admin/                  Staff-facing dashboard, CRM, catalog, identity, and admin console
├── front-end/cms/          Staff UI for managing the external public website
├── auth/                   Shared authentication screens
├── components/ layouts/    Shared admin UI shell and components
└── errors/ vendor/         Framework/package views

database/
├── migrations/             Shared database migration history (kept flat for Laravel discovery)
└── seeders/
    ├── Admin/               Roles, the single menu seeder, catalog, and super-admin seeders
    └── FrontEnd/Cms/        CMS access setup

routes/
├── web.php                 Route entrypoint and shared root redirect
├── admin.php               Internal staff routes
├── front-end.php           Authenticated CMS administration routes
├── api.php                 API auth routes and frontend API include
└── front-end-api.php       Public CMS read/lead API routes
```

## Placement rules for new work

- Put staff-only controllers, domain records, services, Blade pages, and seeders under `Admin` / `resources/views/admin` / `database/seeders/Admin`.
- Put public-website content-management controllers, models, services, support code, Blade administration screens, and public CMS endpoints under `FrontEnd/Cms` and matching `resources/views/front-end/cms` / `database/seeders/FrontEnd/Cms` folders.
- Keep authentication, middleware, shared layout/components, shared helpers, and Laravel bootstrapping in their framework/shared locations. Do not duplicate shared admin chrome under either product area.
- Register staff routes in `routes/admin.php`, CMS administration routes in `routes/front-end.php`, and public CMS API endpoints in `routes/front-end-api.php`. Keep route paths, route names, middleware, and permission identifiers stable when moving implementations.
- Keep migrations in `database/migrations` so Laravel's normal migration discovery continues to work.
- `database/seeders/Admin/MenuSeeder.php` is the one menu-creation seeder. Add menu entries there rather than creating additional menu seeders.
