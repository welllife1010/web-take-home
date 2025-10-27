# Widget Company Directory Plugin

This is your plugin workspace. A basic Gutenberg block has been scaffolded to get you started.

## Current Structure

```
widget-company-directory/
├── widget-company-directory.php   # Main plugin file
├── src/                           # Source files
│   ├── blocks/
│   │   └── company-list/          # Gutenberg block (starter)
│   │       ├── block.json         # Block configuration
│   │       ├── index.js           # Block JavaScript
│   │       ├── editor.css         # Editor styles
│   │       └── style.css          # Frontend styles
│   └── index.js                   # Entry point
├── build/                         # Built assets (auto-generated)
├── includes/
│   └── class-company.php          # Starter Company class
├── admin/                         # For admin functionality
├── public/                        # For frontend functionality
├── assets/                        # For additional assets
└── package.json
```

## Building the Block

```bash
# Development mode - auto-rebuilds on file changes
npm run start

# Production build
npm run build
```

## Data Location

The company data files are available at:
- JSON: `data/companies_data.json`
- CSV: `data/companies_data.csv`

In your plugin code, you can access them via:
```php
$json_file = WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'data/companies_data.json';
$csv_file = WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'data/companies_data.csv';
```

## The Starter Block

A minimal Gutenberg block has been created at `src/blocks/company-list/`.

You can:
- Enhance this block to display company lists
- Use it as-is and render via PHP (dynamic block)
- Create a completely different approach (shortcode, template function, etc.)
- Delete it and start fresh

The block is already registered in the main plugin file.

## Helpful WordPress Functions & Hooks

### Custom Post Types
```php
register_post_type( 'company', $args );
```

### Meta Fields
```php
add_post_meta( $post_id, 'rating', $rating );
get_post_meta( $post_id, 'rating', true );
update_post_meta( $post_id, 'benefits', array( ... ) );
```

### Admin Menus
```php
add_action( 'admin_menu', 'your_function' );
add_menu_page( 'Companies', 'Companies', 'manage_options', 'companies', 'callback' );
```

### Shortcodes
```php
add_shortcode( 'company_list', 'your_function' );
```

### Dynamic Blocks (Server-side Rendering)
```php
register_block_type( 'widget-directory/company-list', array(
    'render_callback' => 'render_company_list_block'
) );
```

### Working with Block Attributes
In `block.json`:
```json
"attributes": {
    "listId": {
        "type": "string",
        "default": ""
    }
}
```

In JavaScript:
```js
const { listId } = attributes;
setAttributes({ listId: newValue });
```

## Tips

1. **Don't overthink it** - A working solution is better than a perfect one
2. **Document decisions** - Comments explaining "why" are valuable
3. **WordPress standards** - Follow WordPress coding standards when possible
4. **Focus on requirements** - Prioritize the core functionality over polish

## Testing Your Work

1. Make sure the plugin is activated at http://localhost:8888/wp-admin/plugins.php
2. Build your block: `npm run build` (or `npm run start` for dev mode)
3. Create a new page/post
4. Add the "Company List" block and verify it appears
5. Test your admin interface for viewing/editing companies
6. Test the import process
7. Create a recommended list
8. View the frontend display

## Debugging

- Check browser console for JavaScript errors
- Check WordPress debug log at `wp-content/debug.log`
- Use `error_log()` in PHP to output to debug log
- Use `console.log()` in JavaScript to debug in browser

Good luck!
