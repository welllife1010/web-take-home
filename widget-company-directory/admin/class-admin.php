<?php
namespace WCD;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Admin UI:
 * - A “Company Directory” menu with two pages:
 *    1) Import Companies (reads JSON from /data/companies_data.json)
 *    2) Manage Lists (create a list and drag/drop companies to set order)
 */
class Admin {

    public static function init() : void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menus' ] );
        add_action( 'admin_post_wcd_import_companies', [ __CLASS__, 'handle_import' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'wp_ajax_wcd_save_list', [ __CLASS__, 'ajax_save_list' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_company_metaboxes' ] );
        add_action( 'save_post_company', [ __CLASS__, 'save_company_meta' ], 10, 2 );
        add_filter( 'manage_edit-company_columns', [ __CLASS__, 'company_columns' ] );
        add_action( 'manage_company_posts_custom_column', [ __CLASS__, 'company_columns_data' ], 10, 2 );
        add_action( 'wp_ajax_wcd_rename_list', [ __CLASS__, 'ajax_rename_list' ] );
        add_action( 'wp_ajax_wcd_create_list', [ __CLASS__, 'ajax_create_list' ] );
    }

    public static function register_menus() : void {
        add_menu_page(
            __( 'Company Directory', 'widget-company-directory' ),
            __( 'Company Directory', 'widget-company-directory' ),
            'manage_options',
            'wcd',
            [ __CLASS__, 'render_import_page' ],
            'dashicons-building',
            26
        );

        add_submenu_page(
            'wcd',
            __( 'Import Companies', 'widget-company-directory' ),
            __( 'Import Companies', 'widget-company-directory' ),
            'manage_options',
            'wcd',
            [ __CLASS__, 'render_import_page' ]
        );

        add_submenu_page(
            'wcd',
            __( 'Manage Lists', 'widget-company-directory' ),
            __( 'Manage Lists', 'widget-company-directory' ),
            'manage_options',
            'wcd-lists',
            [ __CLASS__, 'render_lists_page' ]
        );
    }

    public static function enqueue_assets( $hook ) : void {
        if ( strpos( $hook, 'wcd' ) === false ) return;
        wp_enqueue_style( 'wcd-admin', WIDGET_COMPANY_DIRECTORY_PLUGIN_URL . 'assets/admin.css', [], '1.0.0' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'wcd-admin', WIDGET_COMPANY_DIRECTORY_PLUGIN_URL . 'assets/admin.js', [ 'jquery', 'jquery-ui-sortable' ], '1.0.0', true );
        wp_localize_script( 'wcd-admin', 'WCDAdmin', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'adminBase' => admin_url(),
            'nonce'   => wp_create_nonce( 'wcd_lists' ),
        ] );
    }

    /** Page 1: Import */
    public static function render_import_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        $json_path = WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'data/companies_data.json'; // mounted by .wp-env.json in local dev
        ?>
        <div class="wrap">
            <?php 
                if ( isset($_GET['wcd_msg']) && $_GET['wcd_msg'] === 'imported' ) {
                    $count = intval($_GET['count'] ?? 0);
                    echo '<div class="notice notice-success is-dismissible"><p>'
                        . esc_html( sprintf( 'Imported %d companies successfully.', $count ) )
                        . '</p></div>';
                }
                if ( isset($_GET['wcd_msg']) && $_GET['wcd_msg'] === 'nojson' ) {
                    echo '<div class="notice notice-error is-dismissible"><p>'
                        . esc_html__( 'companies_data.json not found.', 'widget-company-directory' )
                        . '</p></div>';
                }
            ?>

            <h1><?php esc_html_e( 'Import Companies', 'widget-company-directory' ); ?></h1>
            <p><?php esc_html_e( 'This will read /data/companies_data.json and create/update Company posts.', 'widget-company-directory' ); ?></p>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <?php wp_nonce_field( 'wcd_import' ); ?>
                <input type="hidden" name="action" value="wcd_import_companies" />
                <p><code><?php echo esc_html( $json_path ); ?></code></p>
                <p><button class="button button-primary"><?php esc_html_e( 'Run Import', 'widget-company-directory' ); ?></button></p>
            </form>
        </div>
        <?php
    }

    public static function handle_import() : void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Unauthorized', 'widget-company-directory' ) );
        }
        check_admin_referer( 'wcd_import' );

        $json_path = WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'data/companies_data.json';

        if ( ! file_exists( $json_path ) ) {
            wp_safe_redirect( add_query_arg( 'wcd_msg', 'nojson', admin_url( 'admin.php?page=wcd' ) ), 303 );
            exit;
        }

        $raw  = file_get_contents( $json_path );
        $rows = json_decode( $raw, true ) ?: [];

        $created = 0;
        foreach ( $rows as $row ) {
            $id = \WCD\Company::upsert_company_from_array( $row );
            if ( $id ) $created++;
        }

        // IMPORTANT: no echo/print before this line
        wp_safe_redirect( add_query_arg( [ 'wcd_msg' => 'imported', 'count' => $created ], admin_url( 'admin.php?page=wcd' ) ), 303 );
        exit;
    }


    /** Manage Lists */
    public static function render_lists_page() : void {
        if ( ! current_user_can( 'manage_options' ) ) return;

        // Fetch lists
        $lists = get_posts([
            'post_type'      => 'company_list',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'post_status'    => 'publish',
        ]);

        // Create a default list if none exist
        if ( empty( $lists ) ) {
            $demo_id = wp_insert_post([
                'post_type'   => 'company_list',
                'post_status' => 'publish',
                'post_title'  => 'Recommended List',
            ]);
            $lists = [ get_post( $demo_id ) ];
        }

        // Determine current list from ?list_id=...
        $current = $lists[0];
        if ( isset( $_GET['list_id'] ) ) {
            $requested = (int) $_GET['list_id'];
            foreach ( $lists as $list ) {
                if ( (int) $list->ID === $requested ) { $current = $list; break; }
            }
        }

        // Selected IDs (normalized)
        $raw = get_post_meta( $current->ID, '_wcd_company_ids', true );
        $company_ids = is_array( $raw ) ? $raw : [];
        $company_ids = array_values( array_filter( array_map( 'intval', $company_ids ), fn($id)=>$id>0 ) );

        // All companies
        $companies = get_posts([
            'post_type'      => 'company',
            'posts_per_page' => -1,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'fields'         => 'ids',
        ]);
        $companies = array_values( array_filter( array_map( 'intval', $companies ), fn($id)=>$id>0 ) );
        $available = array_values( array_diff( $companies, $company_ids ) );
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Manage Company Lists', 'widget-company-directory' ); ?></h1>

            <p><?php esc_html_e( 'Pick a list, rename it, create a new one, and drag companies to set order.', 'widget-company-directory' ); ?></p>

            <form method="get" action="" style="display:flex;gap:8px;align-items:center;">
                <input type="hidden" name="page" value="wcd-lists" />
                <select name="list_id" id="wcd-list-select">
                    <?php foreach ( $lists as $list ) : ?>
                        <option value="<?php echo esc_attr( $list->ID ); ?>" <?php selected( $current->ID, $list->ID ); ?>>
                            <?php echo esc_html( $list->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="button"><?php esc_html_e( 'Load', 'widget-company-directory' ); ?></button>
                <button type="button" class="button" id="wcd-create-list"><?php esc_html_e( 'Add New List', 'widget-company-directory' ); ?></button>
            </form>

            <div class="wcd-columns" style="margin-top:16px;">
                <div class="wcd-col">
                    <h2><?php esc_html_e( 'Available Companies', 'widget-company-directory' ); ?></h2>
                    <ul id="wcd-available" class="wcd-list">
                        <?php foreach ( $available as $cid ) : ?>
                            <li data-id="<?php echo esc_attr( $cid ); ?>"><?php echo esc_html( get_the_title( $cid ) ); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="wcd-col">
                    <div style="display:flex;align-items:center;gap:8px;">
                        <h2 style="margin:0;"><?php esc_html_e( 'Selected (Drag to Order)', 'widget-company-directory' ); ?></h2>
                    </div>

                    <!-- Inline list name editor -->
                    <div class="wcd-inline-rename" style="margin:8px 0 16px; display:flex; gap:8px; align-items:center;">
                        <label for="wcd-list-name" style="font-weight:600;"><?php esc_html_e('List Name','widget-company-directory'); ?>:</label>
                        <input type="text" id="wcd-list-name" value="<?php echo esc_attr( $current->post_title ); ?>" style="min-width:260px;">
                        <button class="button" id="wcd-rename-list" data-list="<?php echo esc_attr( $current->ID ); ?>"><?php esc_html_e('Save Name','widget-company-directory'); ?></button>
                        <span id="wcd-name-status" class="wcd-muted"></span>
                    </div>

                    <ul id="wcd-selected" class="wcd-list">
                        <?php foreach ( $company_ids as $cid ) :
                            if ( ! $cid ) continue; ?>
                            <li data-id="<?php echo esc_attr( $cid ); ?>"><?php echo esc_html( get_the_title( $cid ) ); ?></li>
                        <?php endforeach; ?>
                    </ul>

                    <p style="margin-top:12px;">
                        <button class="button button-primary" id="wcd-save" data-list="<?php echo esc_attr( $current->ID ); ?>">
                            <?php esc_html_e( 'Save Order', 'widget-company-directory' ); ?>
                        </button>
                        <span id="wcd-status"></span>
                    </p>
                </div>
            </div>
        </div>
        <?php
    }

    public static function ajax_rename_list() : void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error('cap');
        check_ajax_referer( 'wcd_lists', 'nonce' );

        $list_id = intval( $_POST['list_id'] ?? 0 );
        $title   = isset($_POST['title']) ? sanitize_text_field( wp_unslash($_POST['title']) ) : '';

        if ( $list_id <= 0 || '' === $title ) wp_send_json_error('bad-input');

        $post = get_post( $list_id );
        if ( ! $post || $post->post_type !== 'company_list' ) wp_send_json_error('bad-list');
        if ( ! current_user_can( 'edit_post', $list_id ) ) wp_send_json_error('cap');

        $updated = wp_update_post([ 'ID' => $list_id, 'post_title' => $title ], true );
        if ( is_wp_error( $updated ) ) wp_send_json_error( $updated->get_error_message() );

        wp_send_json_success([ 'id' => $list_id, 'title' => $title ]);
    }

    public static function ajax_create_list() : void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error('cap');
        check_ajax_referer( 'wcd_lists', 'nonce' );

        $title = isset($_POST['title']) ? sanitize_text_field( wp_unslash($_POST['title']) ) : '';
        if ( '' === $title ) wp_send_json_error('bad-input');

        $id = wp_insert_post([
            'post_type'   => 'company_list',
            'post_status' => 'publish',
            'post_title'  => $title,
        ], true );

        if ( is_wp_error( $id ) ) wp_send_json_error( $id->get_error_message() );

        delete_post_meta( $id, '_wcd_company_ids' );

        wp_send_json_success([ 'id' => (int) $id, 'title' => $title ]);
    }


    // Metaboxes for Company CPT
    public static function add_company_metaboxes() : void {
        add_meta_box(
            'wcd_company_meta',
            __( 'Company Details', 'widget-company-directory' ),
            [ __CLASS__, 'render_company_meta_box' ],
            'company',
            'normal',
            'default'
        );
    }

    /** Render the metabox HTML */
    public static function render_company_meta_box( \WP_Post $post ) : void {
        // Nonce for save
        wp_nonce_field( 'wcd_company_meta', 'wcd_company_meta_nonce' );

        $rating   = (int) get_post_meta( $post->ID, '_wcd_rating', true );
        $trial    = (bool) get_post_meta( $post->ID, '_wcd_has_free_trial', true );
        $benefits = (array) get_post_meta( $post->ID, '_wcd_benefits', true );
        $cons     = (array) get_post_meta( $post->ID, '_wcd_cons', true );

        // Ensure exactly 3 fields for benefits/cons
        for ( $i = 0; $i < 3; $i++ ) {
            if ( ! isset( $benefits[$i] ) ) $benefits[$i] = '';
            if ( ! isset( $cons[$i] ) )     $cons[$i]     = '';
        }
        ?>
        <style>
        .wcd-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .wcd-field{margin-bottom:12px;}
        .wcd-inline{display:flex;align-items:center;gap:8px;}
        .wcd-muted{color:#666;font-size:12px;}
        .wcd-list{display:grid;gap:8px}
        .wcd-list input[type="text"]{width:100%;}
        </style>

        <div class="wcd-grid">
        <div>
            <div class="wcd-field">
            <label for="wcd_rating"><strong><?php esc_html_e('Rating (1–10)','widget-company-directory'); ?></strong></label><br/>
            <input type="number" id="wcd_rating" name="wcd_rating" min="0" max="10" step="1" value="<?php echo esc_attr( $rating ); ?>" />
            <div class="wcd-muted"><?php esc_html_e('Enter an integer from 0 to 10.','widget-company-directory'); ?></div>
            </div>

            <div class="wcd-field wcd-inline">
            <input type="checkbox" id="wcd_has_free_trial" name="wcd_has_free_trial" value="1" <?php checked( $trial ); ?> />
            <label for="wcd_has_free_trial"><strong><?php esc_html_e('Has Free Trial','widget-company-directory'); ?></strong></label>
            </div>
        </div>

        <div>
            <div class="wcd-field">
            <strong><?php esc_html_e('Benefits (up to 3)','widget-company-directory'); ?></strong>
            <div class="wcd-list">
                <?php for ( $i=0; $i<3; $i++ ) : ?>
                <input type="text" name="wcd_benefits[]" value="<?php echo esc_attr( $benefits[$i] ); ?>" placeholder="<?php printf( esc_attr__('Benefit %d','widget-company-directory'), $i+1 ); ?>" />
                <?php endfor; ?>
            </div>
            </div>

            <div class="wcd-field">
            <strong><?php esc_html_e('Cons (up to 3)','widget-company-directory'); ?></strong>
            <div class="wcd-list">
                <?php for ( $i=0; $i<3; $i++ ) : ?>
                <input type="text" name="wcd_cons[]" value="<?php echo esc_attr( $cons[$i] ); ?>" placeholder="<?php printf( esc_attr__('Con %d','widget-company-directory'), $i+1 ); ?>" />
                <?php endfor; ?>
            </div>
            </div>
        </div>
        </div>
        <?php
    }

    public static function save_company_meta( int $post_id, \WP_Post $post ) : void {
        // Capability & nonce checks
        if ( ! isset( $_POST['wcd_company_meta_nonce'] ) || ! wp_verify_nonce( $_POST['wcd_company_meta_nonce'], 'wcd_company_meta' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( $post->post_type !== 'company' ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Rating
        $rating = isset($_POST['wcd_rating']) ? (int) $_POST['wcd_rating'] : 0;
        $rating = max( 0, min( 10, $rating ) );
        update_post_meta( $post_id, '_wcd_rating', $rating );

        // Free trial
        $trial = ! empty( $_POST['wcd_has_free_trial'] ) ? true : false;
        update_post_meta( $post_id, '_wcd_has_free_trial', $trial );

        // Benefits / Cons (sanitize, trim empties, max 3)
        $benefits = array_slice(
            array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['wcd_benefits'] ?? [] ) ),
            0, 3
        );
        $benefits = array_values( array_filter( $benefits, fn($v)=> $v !== '' ) );

        $cons = array_slice(
            array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['wcd_cons'] ?? [] ) ),
            0, 3
        );
        $cons = array_values( array_filter( $cons, fn($v)=> $v !== '' ) );

        if ( empty( $benefits ) ) {
            delete_post_meta( $post_id, '_wcd_benefits' );
        } else {
            update_post_meta( $post_id, '_wcd_benefits', $benefits );
        }

        if ( empty( $cons ) ) {
            delete_post_meta( $post_id, '_wcd_cons' );
        } else {
            update_post_meta( $post_id, '_wcd_cons', $cons );
        }
    }

    public static function company_columns( array $cols ) : array {
        // Insert after title
        $new = [];
        foreach ( $cols as $k => $v ) {
            $new[ $k ] = $v;
            if ( $k === 'title' ) {
                $new['wcd_rating'] = __('Rating','widget-company-directory');
                $new['wcd_trial']  = __('Free Trial','widget-company-directory');
            }
        }
        return $new;
    }

    public static function company_columns_data( string $col, int $post_id ) : void {
        if ( $col === 'wcd_rating' ) {
            echo esc_html( (int) get_post_meta( $post_id, '_wcd_rating', true ) );
        } elseif ( $col === 'wcd_trial' ) {
            echo get_post_meta( $post_id, '_wcd_has_free_trial', true ) ? '✅' : '—';
        }
    }

    public static function ajax_save_list() : void {
        if ( ! current_user_can( 'manage_options' ) ) wp_send_json_error('cap');
        check_ajax_referer( 'wcd_lists', 'nonce' );

        $list_id = intval( $_POST['list_id'] ?? 0 );
        $ids     = array_values( array_filter(
            array_map( 'intval', (array) ( $_POST['ids'] ?? [] ) ),
            fn( $id ) => $id > 0
        ));

        if ( $list_id <= 0 ) wp_send_json_error('no-list');

        $post = get_post( $list_id );
        if ( ! $post || $post->post_type !== 'company_list' ) wp_send_json_error('bad-list');
        if ( ! current_user_can( 'edit_post', $list_id ) ) wp_send_json_error('cap');

        if ( empty( $ids ) ) {
            delete_post_meta( $list_id, '_wcd_company_ids' );
        } else {
            update_post_meta( $list_id, '_wcd_company_ids', $ids );
        }

        wp_send_json_success([ 'count' => count( $ids ) ]);
    }

}
