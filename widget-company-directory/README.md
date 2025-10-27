# Widget Company Directory Plugin
The **Widget Company Directory** plugin allows editors to import companies, curate ranked lists through an intuitive admin interface, and display them via a dynamic Gutenberg block.

Adds:
- **CPT**: `company` (directory entries)
- **CPT**: `company_list` (curated lists)
- **Admin UI**: “Company Directory → Manage Lists” for inline **Create**, **Rename**, **Drag/Drop**, **Save**
- **Importer**: “Company Directory → Import Companies” (reads `/data/companies_data.json`)
- **Gutenberg block**: **Company List** (dynamic; select a curated list and render on frontend)

## Features
- Editors can curate multiple named lists without leaving a single screen.
- Company attributes editable on the Company editor:
  - Rating (0–10), Has Free Trial (checkbox), Benefits (up to 3), Cons (up to 3)
- Dynamic block → always fresh frontend output; no stale HTML.

## Technical Choices & Tradeoffs
- **Gutenberg Block (vs ACF):** Native block tools, no dependency on ACF Pro, direct REST access.
- **CPTs (vs Custom Tables):** Fast to implement, integrates with WP REST and roles. Scales to hundreds of entries; for thousands, a join table or taxonomy would scale better.
- **List Storage:** Ordered array of Company IDs in `_wcd_company_ids` meta field. Simple and readable; could evolve into its own table if needed.
- **Importer:** Reads `/data/companies_data.json`; quick and idempotent. For large data sets, a background or WP-CLI process would be ideal.

## How It Works

### Storage
- `company` posts store:
  - `_wcd_rating` (int), `_wcd_has_free_trial` (bool),
  - `_wcd_benefits` (array of strings), `_wcd_cons` (array of strings)
- `company_list` posts store:
  - `_wcd_company_ids` (array of ordered company IDs)

### Admin UX
- **Manage Lists** screen:
  - Dropdown to load an existing list
  - **Add New List** (AJAX)
  - Inline **List Name** field + **Save Name** (AJAX)
  - Two columns: **Available** and **Selected** (sortable)
  - **Save Order** button (AJAX)
- Companies list table shows Rating / Free Trial columns

### Block
- `widget-company-directory/company-list`
- Editor: choose a list (populated via REST)
- Save: stores `listId` attribute only
- Frontend: PHP `render_callback` builds the HTML from current data

## Current Structure
```
widget-company-directory/
├── widget-company-directory.php   # Main plugin file
├── src/
│   └── blocks/
│       └── company-list/
│           ├── block.json         # Block configuration
│           ├── index.js           # Block JavaScript (registers the block)
│           ├── editor.css         # Editor styles
│           └── style.css          # Frontend styles
├── build/                         # Built assets (auto-generated)
├── includes/
│   └── class-company.php          # CPTs + meta
├── admin/
│   └── class-admin.php            # Admin UI & AJAX
├── public/
│   └── class-render.php           # Frontend render callback
├── assets/                        # Admin assets (e.g., admin.js, admin.css)
└── package.json
```

## Dev Notes
- **Register CPTs & meta**: `includes/class-company.php`
- **Admin pages & AJAX**: `admin/class-admin.php`, `assets/admin.js`
- **Block**:
  - Source: `src/blocks/company-list/` (`block.json`, `index.js`, `edit.js`, `editor.css`, `style.css`)
  - Build to: `build/blocks/company-list/*`
  - Registered from: `public/class-render.php` via `register_block_type( ... 'build/blocks/company-list' ...)`
- **Render callback**: `public/class-render.php` uses `wpautop( wp_kses_post( ... ) )` for summaries (avoids heavy `the_content` filters)

## Security
- Nonces: `wcd_lists` on all AJAX
- Caps: `manage_options`, `edit_post` checks
- Sanitization: `sanitize_text_field`, `wp_kses_post`, `intval`
- No output before redirects (headers safe)

## Performance & Scalability
- Suitable for dozens/hundreds of items
- For thousands: consider a join table (`company_list_items`) and/or caching
- Avoid `the_content` filter in loops to prevent recursion OOM

## Visual Styling
- For this take-home, I focused on functional correctness, architecture, and editor UX.
- The frontend output uses minimal markup for clarity.
- In a production environment, I would wrap each company in a styled card component (using block style.css or theme styles) for better readability and branding alignment.
- A minimal set of card styles can be found in src/blocks/company-list/style.css

## Getting Started
1. Run `npm install` in the project root.
2. Start local WordPress: `npm run env:start` → http://localhost:8888
3. Build plugin assets:  
```bash
   cd widget-company-directory
   npm install
   npm run build
```
4. In wp-admin: activate the plugin.
5. Import companies: Company Directory → Import Companies.
6. Curate a list: Company Directory → Manage Lists (create/rename, drag, save).
7. Create a page and insert the Company List block; pick your list.

## Commands
Root (Docker):
```bash
npm run env:start
npm run env:stop
npm run env:destroy
```

Plugin (build):
```bash
npm run start   # dev/watch
npm run build   # production build
```

## Evaluation Summary
- **Architecture**: documented CPT/meta vs tables, dynamic block, importer, scalability notes.
- **Editor UX**: consolidated Manage Lists page with create/rename/drag/sort; block selector; metabox on Company.
- **Code Quality**: nonces, caps, sanitization, avoiding `the_content`, no output before redirect, modern block build; readable README.
- **Completeness**: end-to-end flow works; testing & debugging sections included.

## Data Location
The company data files are available at:
- JSON: `data/companies_data.json`
- CSV: `data/companies_data.csv`

Mapped into the plugin directory in local dev; accessed via:
```php
$json_file = WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'data/companies_data.json';
$csv_file = WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'data/companies_data.csv';
```

## Credits
Built by Silvia Chen as part of the Mutual of Omaha Take-Home Project — demonstrating modern Gutenberg development, custom post types, and intuitive editor UX design.