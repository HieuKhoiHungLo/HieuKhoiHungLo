# Codebase Documentation

## Architecture Overview
The application follows a custom MVC (Model-View-Controller) architecture with a Repository Pattern layer for data access.

### Directory Structure
- **app/**: Core application logic.
  - **Controllers/**: Handle incoming requests (e.g., `ApplicationController`, `AdminController`).
  - **Models/**: Active Record models (e.g., `ThiSinh`, `MasterData`). *Note: Direct usage in Views is deprecated.*
  - **Repositories/**: Data access layer (e.g., `MasterDataRepository`, `ThiSinhRepository`).
  - **Core/**: Framework core (`App`, `Controller`, `Database`, `Router`).
- **resources/views/**: PHP View templates.
  - **application/**: Student application views (includes partials for better organization).
  - **admin/**: Admin dashboard views.
  - **layouts/**: Shared layouts (`header.php`, `footer.php`).
- **public/**: Document root.
  - **assets/**: Static assets (CSS, JS, Images).
  - **index.php**: Entry point.

## Key Changes (Sprint 3)
1.  **View Refactoring**:
    -   Views no longer instantiate Models directly.
    -   Shared data (settings, menus) is injected via `App\Core\Controller`.
    -   `application/index.php` refactored into partials (`header_profile`, `roadmap`, `history`).

2.  **Frontend Optimization**:
    -   Critical JS moved to footer to prevent render blocking.
    -   CSS loading verified.

3.  **Testing**:
    -   Manual test scripts created in `tests/` for Repositories and Controllers.

## Guidelines
-   **Data Access**: Always use Repositories. Do not use Models directly in Controllers if a Repository exists.
-   **Views**: Views should only display data passed to them. Do not fetch data inside Views.
-   **Security**: Ensure CSRF protection is used for all POST requests.
