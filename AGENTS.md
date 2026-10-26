# UI conventions for AI contributors

## Product scope and implementation source

- Before implementing or changing a product module, read `.kiro/IMPLEMENTATION_BLUEPRINT.md`, `.kiro/IMPLEMENTATION_AUDIT_AND_PLAN.md`, and the applicable requirements/design/tasks under `.kiro/specs/`.
- The product source of truth is `Amanah_Final_Production_SRS.pdf` (provided at `/home/yamin/Documents/SefBee/Amanah_Final_Production_SRS.pdf` in the current workspace). The older `Amanah_SRS_v1.pdf` is a draft; if it conflicts with the final production SRS, follow the final SRS and record unresolved policy decisions rather than guessing.
- A Kiro spec is an implementation plan, not the full product scope. Check the blueprint before treating a module as out of scope. Do not assume a module is implemented just because related code exists; inspect its workflows and acceptance criteria.
- Keep each change within the requested module and its documented dependencies. Do not silently omit requirements, invent business policy, or weaken privacy, consent, approval, or audit controls. Record unresolved requirements as explicit questions/decisions in the relevant spec.
- Preserve the existing Laravel application patterns: Laravel 12, PHP 8.2+, Blade admin UI, named routes, middleware/RBAC, shared Blade components, Yajra DataTables for server-side tables, Eloquent models/migrations, and Spatie ActivityLog where the feature changes operational data.
- The CMS is a headless content-management module for a separate public Next.js/React frontend. Build CMS administration in this application using the UI rules below; do not turn the staff admin into the public website or silently replace the documented frontend architecture.
- Respect the production SRS boundaries: no clinical records, payroll processing, automated AI matching/assignment, or family/caregiver login portals unless an approved SRS change explicitly adds them. Matching may filter and explain candidates; people make decisions.

## Shared application UI conventions

Apply these rules to every new page and every page you change in this repository.

## Shared page title and breadcrumb

- Extend `layouts.base` for authenticated application pages.
- Provide the `$breadcrumb` array in the view. The shared toolbar in `layouts/base.blade.php` renders the page's only page-level title and breadcrumb.
- Do not repeat the record name or page title in the content area. This includes detail-page summary headers and form-card headers that merely restate the toolbar title.
- Put page-level buttons in `@section('actions')`; the shared toolbar renders them beside the title. Keep field labels and section headings that describe distinct content.
- On tabbed detail pages, put the tab navigation in `@section('tabs')`; the shared toolbar renders it below the title and breadcrumb. Keep only the tab panels inside the content container.
- Use a meaningful final breadcrumb item for the current page and link earlier items to their index pages.

## CRUD page structure

- Use `resources/views/users/index.blade.php` as the list-page reference: a full-width `.row` and `.col-md-12`, page actions above the table, and a `.card` containing the table.
- Render server-side tables with the shared `<x-datatable>` component. Keep table column definitions in the view and data/query behavior in the controller.
- Use `resources/views/users/create.blade.php` and `resources/views/users/edit.blade.php` as form-page references: `.row` with `.col-md-8.offset-md-2`, the shared `<x-alert :show_validations="false" />`, then a `.card.shadow-sm` with the form inside its body.
- Do not add a card header whose only purpose is repeating the page title. Keep submit and cancel actions together at the end of the form.
- Use `resources/views/users/show.blade.php` as the detail-page reference: organized responsive cards, with no second page title in the content.
- For card section headings, use `<h6 class="card-title mb-0 fw-bold">…</h6>` inside `.card-header` so the theme vertically centers the heading within its header.

## Consistency and scope

- Keep spacing, responsive grid widths, card treatment, buttons, and form controls consistent with the Users CRUD views.
- Reuse existing Blade components and layout sections before introducing new markup or styles.
- When updating an existing page, preserve its behavior and data while bringing its page structure into these conventions.
- Do not duplicate breadcrumbs inside page content or introduce a separate page-level heading component.
