# AtoZe Engineering - Developer Journal

> A technical log documenting the architecture, implementation decisions, and development journey of the A to Ze Engineering PLC website.

**Project Type**: Security Solutions Company Website
**Stack**: PHP, HTML/CSS/JS, JSON-based data storage
**Current Branch**: `v2` (Main: `main`)

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Architecture Decisions](#architecture-decisions)
3. [What We Built](#what-we-built)
4. [How Things Work](#how-things-work)
5. [Development Journey](#development-journey)
6. [Technical Implementation](#technical-implementation)
7. [Data Structure](#data-structure)
8. [Future Considerations](#future-considerations)

---

## Project Overview

This is a complete web solution for A to Ze Engineering PLC, a security solutions company in Ethiopia. The project evolved from a simple static website into a dynamic platform with a custom-built admin panel and analytics system.

**Key Requirements Met:**
- Product catalog for security equipment (CCTV, alarms, access control, etc.)
- Project portfolio showcasing completed installations
- Contact form with anti-spam protection
- Admin panel for content management
- Custom analytics tracking system (no Google Analytics dependency)
- JSON-based storage (no database required)

---

## Architecture Decisions

### 1. **Why JSON Instead of a Database?**

**Decision**: Store all data in JSON files rather than using MySQL/PostgreSQL.

**Rationale**:
- Simple deployment (no database setup required)
- Easy backup (just copy the `Json/` directory)
- Version control friendly (changes are visible in git diffs)
- Perfect for relatively static content (products, projects, FAQs)
- Reduces hosting requirements and costs
- Fast read operations for small datasets

**Trade-offs**:
- Not suitable for high-traffic sites with concurrent writes
- Manual data normalization required
- No built-in relationship handling

**Implementation**: Created a `DataManager` class (`admin/lib/DataManager.php`) to handle all JSON operations with:
- File locking (`LOCK_EX`) to prevent concurrent write issues
- Error handling for read/write operations
- Pretty-printed JSON for human readability

```php
// Key design pattern: Centralized data access
$dataManager = new DataManager(__DIR__ . '/../Json');
$products = $dataManager->getData('normalized_products.json');
```

### 2. **Custom Analytics Instead of Third-Party**

**Decision**: Build a custom analytics system rather than using Google Analytics.

**Rationale**:
- Data ownership and privacy
- No external dependencies
- Tailored metrics for business needs
- Learning opportunity
- No tracking consent issues

**Components Built**:
- `SessionTracker.php`: Generates unique session IDs using SHA1 hash of IP + User Agent + random seed
- `EventTracker.php`: Logs events to daily `.jsonl` files (JSON Lines format)
- `Aggregator.php`: Computes metrics (pageviews, bounce rate, unique visitors)
- `RequestLogger.php`: Captures page views via 1x1 pixel tracker

**Data Flow**:
```
User visits page → tracker.php generates pixel → SessionTracker creates/retrieves session
→ EventTracker logs to JSONL → Admin views aggregated stats
```

### 3. **Namespace Organization**

Organized code into logical namespaces:
- `Analytics\`: All analytics-related classes
- `CMS\`: Content management classes (DataManager)
- `FormGuide\Handlx\`: Form handling utilities

This prevents naming conflicts and improves code organization without requiring an autoloader.

### 4. **File Upload Strategy**

**Decision**: Store uploads in `/uploads` directory with original filenames.

**Structure**:
```
uploads/
├── products/      # Product images
├── projects/      # Project images
└── slides/        # Homepage carousel images
```

**Considerations**: No file renaming for hash-based security (trade-off for simplicity), but could be enhanced later.

---

## What We Built

### Public-Facing Website

#### 1. **Homepage** (`index.html`)
- Dynamic carousel using Swiper.js (data from `slides.json`)
- Featured products section
- Services overview
- Contact CTA with phone number

#### 2. **Products Catalog** (`products.html`, `product-details.html`)
- Product listing with categories
- Individual product detail pages
- Image galleries
- Specifications and pricing

#### 3. **Projects Portfolio** (`projects.html`, `project-single.html`)
- Completed project showcases
- Client names and locations
- Itemized lists of installed equipment
- Total project costs
- Project images

**Data Structure** (`Json/projects.json`):
```json
{
  "company_name": "Aticom investment group",
  "items": ["Cctv camera", "Smart parking system", ...],
  "total_price": "2,461,000",
  "location": "Addis Ababa, Ethiopia",
  "description": "...",
  "image": "images/aticom_investment_group.png"
}
```

#### 4. **Services Pages** (`services.html`, `service-single.html`)
- Service categories with SVG icons
- Detailed descriptions
- Data loaded from `Json/Service.json`

**Services Offered**:
- Security Camera Installation
- Employee Management Systems
- Automatic Door Openers
- Alarm Systems
- Fire Detection Systems
- Intercom & Video Door Phones

#### 5. **Contact Form** (`contact.html`)
- AJAX-powered form submission
- PHPMailer integration for email delivery
- Gregwar CAPTCHA for spam protection
- Form validation via `FormGuide\PHPFormValidator`

**Technical Flow**:
```
User fills form → form.js AJAX POST → handler.php → FormHandler validates
→ CAPTCHA check → PHPMailer sends email → JSON response → UI update
```

#### 6. **FAQ Page** (`fqa.html`)
- Accordion-style Q&A
- Loaded from `Json/faq.json`

### Admin Panel

Built a complete admin CMS from scratch without using existing frameworks.

#### Dashboard (`admin/index.php`)
**Features**:
- Total pageviews counter
- Unique visitor count
- Bounce rate calculation
- Pageviews-per-day chart (last 7 days)
- Date range filtering
- Export functionality (logs, sessions, pageviews)

**UI**: Custom Tailwind-based dark mode interface with responsive design.

#### Product Management (`manage_products.php`, `edit_product.php`)
- CRUD operations for products
- Image upload handling
- Updates `normalized_products.json`

#### Project Management (`manage_projects.php`, `edit_project.php`)
- Add/edit/delete projects
- Multi-item lists for equipment
- Image association
- Updates `projects.json`

#### Slide Management (`manage_slides.php`, `edit_slide.php`)
- Homepage carousel content control
- Image upload for slides
- Updates `slides.json`

#### Authentication System (`lib/Auth.php`)
- Session-based authentication
- Password hashing with `password_verify()`
- User data stored in `config/users.json`
- Protected routes with redirect to login

```php
// Auth pattern used throughout admin
$auth = new Auth(__DIR__ . '/config/users.json');
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}
```

#### Analytics Exports (`export.php`)
Supports exporting:
- Event logs (JSONL format)
- Session data (JSON)
- Pageview aggregates (CSV-ready)

---

## How Things Work

### Form Submission Pipeline

```
1. User fills contact form on contact.html
2. form.js intercepts submit event, prevents default
3. jQuery AJAX POST to handler.php with serialized form data
4. handler.php creates FormHandler instance
5. FormHandler validates CAPTCHA (session-based)
6. FormHandler runs field validations (email format, required fields)
7. PHPMailer composes email from form fields
8. Email sent via SMTP
9. JSON response returned: {result: 'success'} or {result: 'error', errors: [...]}
10. form.js updates UI (show success or error messages)
```

### Analytics Tracking System

#### Session Creation
```php
// SessionTracker generates unique ID on first visit
$sessionId = sha1($ip . $ua . uniqid(mt_rand(), true));
// Stored in PHP session and in admin/sessions/{sessionId}.json
```

#### Event Logging
```php
// EventTracker appends to daily JSONL file
// admin/logs/2025-10-14.jsonl
{"timestamp":"2025-10-14T10:30:00+00:00","event_type":"pageview","url":"/products.html",...}
{"timestamp":"2025-10-14T10:31:15+00:00","event_type":"pageview","url":"/contact.html",...}
```

**Why JSONL?** JSON Lines format allows appending without parsing entire file, perfect for logs.

#### Metric Aggregation
```php
// Aggregator computes stats on-demand (no pre-aggregation)
// Bounce Rate = (Sessions with ≤1 pageview) / Total Sessions * 100
// Pageviews = Count of all lines in all JSONL files
// Unique Visitors = Count of files in sessions/ directory
```

### Data Manager Pattern

All JSON file operations go through `DataManager`:

```php
// Reading
$dataManager = new DataManager('/path/to/Json');
$products = $dataManager->getData('normalized_products.json');

// Writing (with atomic file locking)
$dataManager->saveData('projects.json', $updatedProjects);
```

**Benefits**:
- Centralized error handling
- Consistent JSON encoding (pretty-print, unescaped slashes)
- File locking prevents race conditions
- Easy to add caching layer later

---

## Development Journey

Based on git history, here's how the project evolved:

### Phase 1: Static Website Foundation
```
Initial commits: Static HTML pages for homepage, about, services, products
```
- Built responsive layouts with Bootstrap
- Implemented Swiper.js for image carousels
- Created service pages with custom SVG icons

### Phase 2: Dynamic Content with JSON
```
Commit: "implement dynamic FAQ section using JSON data"
Commit: "Add JSON loading functionality for featured products, FAQ, top bar messages, and Swiper slides"
```
- Migrated hardcoded content to JSON files
- Created loading functions for products, FAQs, slides
- Enabled content updates without code changes

### Phase 3: Analytics System
```
Commit: "Add initial analytics tracking functionality"
Commit: "feat: initialize admin server with user authentication and file management APIs"
```
- Built custom SessionTracker and EventTracker
- Created JSONL logging system
- Implemented admin dashboard with metrics

### Phase 4: Admin Panel Development
```
Commit: "Some adjustments for the admin panel etc."
Commit: "feat: enhance server startup log to display all available IP addresses"
```
- Created CRUD interfaces for products, projects, slides
- Built authentication system
- Added data export functionality

### Phase 5: Code Refactoring
```
Commit: "Refactor code structure for improved readability and maintainability" (multiple)
```
- Introduced namespace organization
- Created reusable components (partials)
- Improved code quality and documentation
- Built DataManager abstraction

### Phase 6: Content Updates & Polish
```
Commit: "Edits on the layout and add new products"
Commit: "Update contact phone number across multiple HTML files and add new logo image"
```
- Added real project data
- Updated contact information
- Refined responsive layouts

---

## Technical Implementation

### Dependencies

#### Composer Packages
```json
{
  "phpmailer/phpmailer": "Email sending",
  "gregwar/captcha": "CAPTCHA generation",
  "FormGuide/PHPFormValidator": "Form validation"
}
```

Installed in `vendor/` directory with Composer autoloader.

#### Frontend Libraries
- **Bootstrap 5**: Grid system and components
- **Swiper.js**: Touch-enabled carousels
- **jQuery**: AJAX and DOM manipulation (form.js)
- **Font Awesome / Custom Icons**: SVG icon sets

### File Structure

```
AtoZe/
├── admin/                          # Admin panel (CMS)
│   ├── config/
│   │   └── users.json              # Admin credentials (hashed passwords)
│   ├── lib/
│   │   ├── Aggregator.php          # Analytics calculations
│   │   ├── Auth.php                # Authentication handler
│   │   ├── DataManager.php         # JSON file operations
│   │   ├── EventTracker.php        # Event logging
│   │   ├── RequestLogger.php       # HTTP request logging
│   │   ├── SessionTracker.php      # Session management
│   │   └── Renderer.php            # Template rendering
│   ├── partials/
│   │   ├── header.php              # Admin header template
│   │   └── footer.php              # Admin footer template
│   ├── logs/                       # JSONL event logs (daily files)
│   ├── sessions/                   # Session JSON files
│   ├── index.php                   # Dashboard
│   ├── login.php                   # Login page
│   ├── logout.php                  # Logout handler
│   ├── manage_products.php         # Product CRUD listing
│   ├── edit_product.php            # Product edit form
│   ├── manage_projects.php         # Project CRUD listing
│   ├── edit_project.php            # Project edit form
│   ├── manage_slides.php           # Slide CRUD listing
│   ├── edit_slide.php              # Slide edit form
│   ├── events.php                  # Event log viewer
│   ├── tracker.php                 # 1x1 pixel tracker endpoint
│   └── export.php                  # Data export handler
│
├── Json/                           # Data storage
│   ├── normalized_products.json    # Product catalog
│   ├── projects.json               # Project portfolio
│   ├── Service.json                # Service listings
│   ├── faq.json                    # FAQ content
│   ├── slides.json                 # Homepage carousel
│   ├── topbar-messages.json        # Top bar notifications
│   ├── team.json                   # Team members
│   └── analytics.json              # Analytics config
│
├── src/                            # Form handling source
│   ├── FormHandler.php             # Form processing wrapper
│   └── ReCaptchaValidator.php      # reCAPTCHA validation
│
├── uploads/                        # User-uploaded files
│   ├── products/
│   ├── projects/
│   └── slides/
│
├── css/                            # Stylesheets
├── js/                             # JavaScript files
├── images/                         # Static assets
├── fonts/                          # Icon fonts (et-line, elegant, icofont)
├── vendor/                         # Composer dependencies
│
├── index.html                      # Homepage
├── about-us.html                   # About page
├── products.html                   # Product catalog
├── product-details.html            # Product detail template
├── projects.html                   # Project portfolio
├── project-single.html             # Project detail template
├── services.html                   # Services listing
├── service-single.html             # Service detail template
├── contact.html                    # Contact form
├── fqa.html                        # FAQ page
├── handler.php                     # Form submission endpoint
└── form.js                         # Form AJAX handling
```

### Key Design Patterns

#### 1. **Singleton-ish Data Access**
```php
// DataManager instance per request
$dm = new DataManager($jsonDir);
$data = $dm->getData('file.json');
```

#### 2. **Session-Based Auth**
```php
// Check on every protected page
if (!$auth->isLoggedIn()) {
    header('Location: login.php');
    exit;
}
```

#### 3. **JSON Lines for Logs**
Append-only logging without parsing entire file:
```php
file_put_contents($file, json_encode($event) . PHP_EOL, FILE_APPEND | LOCK_EX);
```

#### 4. **Template Partials**
```php
// Reusable header/footer
require_once __DIR__ . '/partials/header.php';
// Page content here
require_once __DIR__ . '/partials/footer.php';
```

#### 5. **AJAX Form Handling**
```javascript
$('#contact_form').submit(function(e) {
    e.preventDefault();
    $.ajax({
        type: "POST",
        url: 'handler.php',
        data: $form.serialize(),
        success: after_form_submitted,
        dataType: 'json'
    });
});
```

---

## Data Structure

### Products (`normalized_products.json`)
```json
{
  "id": 1,
  "name": "HD CCTV Camera",
  "category": "Surveillance",
  "price": "5,000 ETB",
  "description": "...",
  "image": "uploads/products/camera1.jpg",
  "specifications": {
    "resolution": "1080p",
    "night_vision": true
  }
}
```

### Projects (`projects.json`)
```json
{
  "company_name": "Ethiopian Red Cross",
  "items": ["Cctv camera"],
  "total_price": "944,000",
  "location": "Addis Ababa, Ethiopia",
  "description": "High-value CCTV installation...",
  "image": "images/ethiopian_red_cross.png"
}
```

### Services (`Service.json`)
```json
{
  "name": "Security Camera Installation",
  "description": "Professional CCTV camera installation...",
  "icon": "<svg>...</svg>"
}
```

### Sessions (`admin/sessions/{sessionId}.json`)
```json
{
  "session_id": "f014fa261e198be3540fa3c37513d1320b44ca1f",
  "ip": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "start_time": "2025-10-14T10:00:00+00:00",
  "referrer": "https://google.com"
}
```

### Event Logs (`admin/logs/2025-10-14.jsonl`)
```jsonl
{"timestamp":"2025-10-14T10:30:00+00:00","event_type":"pageview","event_data":{},"url":"/index.html","session_id":"f014fa...","user_agent":"Mozilla...","ip":"192.168.1.100"}
{"timestamp":"2025-10-14T10:31:15+00:00","event_type":"pageview","event_data":{},"url":"/products.html","session_id":"f014fa...","user_agent":"Mozilla...","ip":"192.168.1.100"}
```

---

## Future Considerations

### Scalability
- [ ] Implement caching layer for JSON files (Redis/Memcached)
- [ ] Move to database when product catalog exceeds ~500 items
- [ ] Add CDN for images and static assets
- [ ] Implement lazy loading for images

### Security Enhancements
- [ ] Add CSRF tokens to forms
- [ ] Implement rate limiting on contact form
- [ ] Hash uploaded filenames to prevent path traversal
- [ ] Add Content Security Policy headers
- [ ] Enable HTTPS and HSTS

### Feature Additions
- [ ] Search functionality for products
- [ ] Product filtering by category/price
- [ ] Admin activity logs
- [ ] Backup/restore functionality
- [ ] Email notification system for form submissions
- [ ] Multi-language support (Amharic/English)

### Code Quality
- [ ] Add PHPUnit tests for DataManager and Analytics classes
- [ ] Implement PSR-4 autoloading
- [ ] Add type hints throughout codebase (PHP 7.4+)
- [ ] Create API documentation with PHPDoc
- [ ] Set up CI/CD pipeline

### Performance
- [ ] Minify CSS/JS assets
- [ ] Implement image optimization pipeline
- [ ] Add service worker for offline support
- [ ] Optimize JSONL log parsing (consider SQLite for analytics)

---

## Development Setup

### Requirements
- PHP 7.0+
- Apache/Nginx with mod_rewrite
- Composer (for dependencies)
- Write permissions on `Json/`, `admin/sessions/`, `admin/logs/`, `uploads/`

### Installation
```bash
# Clone repository
git clone <repo-url>
cd AtoZe

# Install PHP dependencies
composer install

# Set permissions
chmod 755 Json/ admin/sessions/ admin/logs/ uploads/

# Configure web server to point to project root
# Access: http://localhost/
# Admin: http://localhost/admin/
```

### Environment Setup
1. Update `src/FormHandler.php` with SMTP credentials
2. Create admin user in `admin/config/users.json`:
   ```json
   [
     {
       "username": "admin",
       "password_hash": "$2y$10$..."  // Use password_hash('yourpassword', PASSWORD_DEFAULT)
     }
   ]
   ```

---

## Lessons Learned

### What Worked Well
1. **JSON-based storage** was perfect for this project size
2. **Custom analytics** gave full control over data and metrics
3. **Namespace organization** kept code clean without heavy frameworks
4. **JSONL format** for logs was efficient and debuggable
5. **Composer packages** saved time on email/validation/captcha

### What Could Be Improved
1. **Lack of automated tests** makes refactoring risky
2. **No API** limits integration possibilities
3. **Manual image optimization** is tedious
4. **Session tracking** could be more robust (handle cookie blockers)
5. **Form builder** for admin would reduce repetitive CRUD code

### Key Takeaways
- Start simple, add complexity only when needed
- File-based systems are underrated for small projects
- Custom solutions teach more than frameworks
- Code organization matters more than choosing the "right" framework
- Analytics don't always need third-party services

---

## Contributing

This is a client project, but the patterns and approaches can be learned from and adapted.

### Code Style
- PSR-1/PSR-2 PHP standards
- 4-space indentation
- Descriptive variable names
- Comment complex logic

### Git Workflow
- `main`: Production-ready code
- `v2`: Active development branch
- Feature branches for major changes
- Descriptive commit messages

---

## License & Credits

**Client**: A to Ze Engineering PLC
**Location**: Addis Ababa, Ethiopia
**Contact**: +251 913 332 323

**Developer Notes**: This project was built as a complete web solution including both frontend and custom admin panel. No WordPress or off-the-shelf CMS was used - everything is custom-built to client specifications.

---

**Last Updated**: 2025-10-14
**Current Version**: v2 (in development)
**Production Branch**: main
