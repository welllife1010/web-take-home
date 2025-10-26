# Widget Company Directory Plugin - Take-Home Assessment

**Time Limit:** 60 minutes

Hello! The Take Home test requirements will be sent in a separate markdown file.

---

## 🚀 Quick Setup Instructions

Get started in 3 minutes:

### 1. Install Dependencies
```bash
npm i
# OR 
npm setup #from the root to install and build everything in one go
```

### 2. Start WordPress Environment
```bash
npm run env:start 
# OR
wp-env start #if globally installed
```

This uses [@wordpress/env](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/) to automatically:
- Download and configure WordPress 6.7
- Start Docker containers
- Install and activate your plugin
- Set up the database

**First time will take 1-2 minutes. Subsequent starts are much faster.**

### 3. (If you haven't already) Build the Gutenberg Block
Open a new terminal window:

```bash
cd widget-company-directory
npm install
npm run build
```

For development with live reload:
```bash
npm run start
```

### 4. Access WordPress
- **Site:** http://localhost:8888
- **Admin:** http://localhost:8888/wp-admin
  - Username: `admin`
  - Password: `password`

### 5. Verify Setup
1. Go to http://localhost:8888/wp-admin/plugins.php
2. Confirm "Widget Company Directory" is activated
3. Create a new post or page
4. Click the "+" icon to add a block
5. Search for "Company List" - you should see a starter block!

**You're ready to code!** The plugin is at `widget-company-directory/` and company data is in `data/`.

### 6. Stop the Environment (When Done)
```bash
npm run env:stop
```

To completely remove everything and start fresh:
```bash
npm run env:destroy
```

### Troubleshooting

**Docker not running?**
- Make sure Docker Desktop is running before `npm run env:start`

**Port 8888 already in use?**
- Stop other services using port 8888, or modify `.wp-env.json`

**Block not appearing?**
- Make sure you ran `npm run build` in the `widget-company-directory` folder
- Check browser console for JavaScript errors
- Try clearing your browser cache

**WordPress not loading?**
- Wait 1-2 minutes on first start (WordPress needs to install)
- Check Docker containers: `docker ps`
- View logs: `wp-env logs`

---

## Overview

Build a WordPress plugin that manages a directory of widget companies and allows editors to create curated, sorted "Recommended Lists" for frontend display.

### Scenario

You're building a directory of 20 widget companies. Non-technical editors need to:
- View and edit company information
- Create "Recommended Lists" - curated, sorted subsets of companies
- Display these lists on frontend pages

## Getting Started (Detailed)

### Prerequisites

- Node.js (v18 or higher) and npm
- Docker Desktop installed and running
- Git
- A code editor

### Setup Instructions

1. Clone this repository:
   ```bash
   git clone <repository-url>
   cd web-take-home
   ```

2. Install dependencies:
   ```bash
   npm install
   ```

3. Start the WordPress environment:
   ```bash
   npm run env:start
   ```

   This will:
   - Download and start WordPress in Docker
   - Auto-install and activate the plugin
   - Set up the database
   - Map the data directory to the plugin

4. Build the block assets:
   ```bash
   cd widget-company-directory
   npm install
   npm run build
   ```

   Or for development with auto-rebuild:
   ```bash
   npm run start
   ```

5. Access WordPress:
   - **WordPress Site:** http://localhost:8888
   - **Admin Dashboard:** http://localhost:8888/wp-admin
     - Username: `admin`
     - Password: `password`

6. The plugin is located at `widget-company-directory/`
7. Company data is provided in the `data/` folder (both JSON and CSV formats)

## Requirements

### 1. Data Import (10-15 min)

- Import the provided 20 companies from the `data/` folder
- Choose: JSON, CSV, or both - **document your decision**
- Method is up to you: admin page, WP-CLI command, migration script, etc.
- The import should be repeatable and documented

### 2. Admin Interface (20-25 min)

- View and edit existing companies
- Create and manage "Recommended Lists" with custom sort order
- **Design decision:** How should editors curate and sort lists? Document your UX choice
- Leave a comment/note explaining how to add NEW companies (don't build full CRUD)

### 3. Frontend Display (20-25 min)

- Display a curated list on the frontend
- Choose your implementation: Gutenberg block, shortcode, template function, or ACF block
- Display for each company:
  - Name
  - Rating
  - Benefits
  - Cons
  - Free Trial badge
  - Summary

### 4. Documentation (5 min)

Update this README with:
- Your import process and rationale
- How editors use the system
- Your architecture decisions (storage, data modeling, etc.)
- How to add new companies to the system
- Any tradeoffs you made

## Data Structure

Each of the 20 companies has the following attributes:

- **Name** (string)
- **Rating** (integer, 1-10)
- **Benefits** (array of 3 strings)
- **Cons** (array of 3 strings)
- **Has Free Trial** (boolean)
- **Summary** (text, ~100 words)

## Technical Choices

You decide:
- ACF or Gutenberg blocks?
- Custom Post Type, custom tables, or options?
- How to store and manage curated lists?
- What import mechanism to use?

**Document your tradeoffs** - we want to see your decision-making process, not perfection.

## Evaluation Criteria

- **Architecture:** Storage choices, data modeling, scalability considerations
- **Editor UX:** How intuitive is the list curation experience?
- **Code Quality:** Readable, organized, follows WordPress coding standards
- **Completeness:** Does it work end-to-end?

## Project Structure

```
web-take-home/
├── data/                          # Company data files
│   ├── companies_data.json
│   └── companies_data.csv
├── widget-company-directory/      # Your WordPress plugin
│   ├── widget-company-directory.php  # Main plugin file
│   ├── src/                       # Source files
│   │   ├── blocks/
│   │   │   └── company-list/      # Gutenberg block (starter)
│   │   │       ├── block.json
│   │   │       ├── index.js
│   │   │       ├── editor.css
│   │   │       └── style.css
│   │   └── index.js
│   ├── build/                     # Built assets (generated)
│   ├── includes/                  # Core plugin classes
│   ├── admin/                     # Admin-specific functionality
│   ├── public/                    # Frontend-specific functionality
│   ├── assets/                    # Additional CSS, JS, images
│   └── package.json
├── .wp-env.json                   # WordPress environment config
├── package.json                   # Project dependencies
└── README.md                      # This file
```
