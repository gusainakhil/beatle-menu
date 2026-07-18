# Beetle Analytics - Owner Reports Dashboard

A hand-rolled custom PHP MVC (Model-View-Controller) application representing a restaurant, bar, and cafe reporting dashboard. It handles multi-tenancy, date range filters, asynchronous AJAX graph updates, GST statement calculations, CSV downloads, and browser print CSS styling.

## Technology Stack
- **PHP 8.2+** (no framework)
- **PDO** with prepared statements (MySQL/MariaDB)
- **Composer** (used *only* for PSR-4 autoloading and Dotenv configuration)
- **Tailwind CSS** (via CDN with dynamic brand configuration)
- **Chart.js** (for line, bar, pie, and donut charts)
- **Font Awesome** (icon-rich interface)

## Folder Structure
```
/public
    index.php              <- Front controller / single entry point
    /assets
        /css
            app.css        <- Tailwind variable overrides
            print.css      <- Print layout overrides
        /js
            dashboard.js   <- AJAX interactions and Chart.js binding
/app
    /Controllers           <- Controller layer
    /Models                <- Model layer (PDO database calls)
    /Services              <- Report calculation services
    /Core                  <- Custom MVC Kernel core utilities
    /Views                 <- HTML view and layout templates
/config
    brand.php              <- Brand colors, name, tagline (Single source of truth)
    config.php             <- Database and APP configurations
    routes.php             <- Declarative routing configurations
/database
    schema2.sql            <- MariaDB/MySQL schema used by the app
    /seeders
        seed_demo_data.php <- realistic data generator
```

## Installation & Setup

1. **Clone & Composer Autoloading Setup**:
   ```bash
   composer install
   ```

2. **Database Configuration**:
   Create a MySQL/MariaDB database (e.g. `beetle_analytics`) and update database credentials inside the `.env` file:
   ```env
   APP_ENV=local
   APP_COMPANY_NAME="Beetle Analytics"
   APP_TAGLINE="Restaurant Performance Dashboard"
   APP_DEBUG=true

   DB_CONNECTION=mysql
   DB_HOST=127.0.0.1
   DB_PORT=3306
   DB_DATABASE=beetle_analytics
   DB_USERNAME=beetle_user
   DB_PASSWORD=beetle_pass
   ```

3. **Database Migration & Seeding**:
   Import the `schema2.sql` schema first. This file recreates the app tables, so run it only against the intended local database:
   ```bash
   mysql -u beetle_user -p beetle_analytics < database/schema2.sql
   ```

   Then seed a realistic business (`Beetle Bistro`), waiters, table QR codes, and 250+ customer order entries spanning the last 3 months:
   ```bash
   php database/seeders/seed_demo_data.php
   ```

4. **Launch Local Server**:
   Start the PHP development server routing to the public directory:
   ```bash
   php -S localhost:8000 -t public
   ```
   Open `http://localhost:8000` in your web browser.

5. **Demo Logins**:
   - **Email**: `owner@beetlebistro.com`
   - **Password**: `password`

## Registration API

Create a new business and its first admin user:

```bash
curl -X POST http://127.0.0.1:8000/api/register \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "business_name": "Demo Cafe",
    "business_type": "cafe",
    "owner_name": "Demo Owner",
    "email": "owner@democafe.com",
    "password": "password",
    "phone_number": "+91 90000 00000",
    "city": "Bengaluru",
    "state": "Karnataka",
    "country": "India",
    "latitude": 12.971599,
    "longitude": 77.594566
  }'
```

Successful responses return `business_id` and `admin_user_id`.

## Login API

Log in an admin user:

```bash
curl -X POST http://127.0.0.1:8000/api/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "owner@beetlebistro.com",
    "password": "password"
  }'
```

Successful responses return the admin user and business identifiers.

## Customizing the Dashboard Brand Name
The dashboard name and taglines are read globally from the `.env` configuration file. Adjusting `APP_COMPANY_NAME="Beetle Analytics"` updates the branding instantly across all pages, login views, headers, exports, and printing sheets.
# beatle-menu
