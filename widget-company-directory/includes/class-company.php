<?php
namespace WCD;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Handles:
 * - Registering CPT "company" and "company_list"
 * - Registering meta for company fields
 * - Simple helpers used by admin importer
 */
class Company {

    public static function init() : void {
        add_action( 'init', [ __CLASS__, 'register_post_types' ] );
        add_action( 'init', [ __CLASS__, 'register_meta_fields' ] );
    }

    public static function register_post_types() : void {

        // --- Company (the directory entries) ---
        register_post_type( 'company', [
            'label'           => __( 'Companies', 'widget-company-directory' ),
            'public'          => true,
            'show_in_rest'    => true,          // allow Gutenberg/REST querying
            'menu_icon'       => 'dashicons-building',
            'supports'        => [ 'title', 'editor' ], // title=Name, editor=Summary
            'rewrite'         => [ 'slug' => 'company' ],
        ] );

        // --- Company List (curated lists with a custom order) ---
        register_post_type( 'company_list', [
            'label'           => __( 'Company Lists', 'widget-company-directory' ),
            'public'          => false,
            'show_ui'         => true,          // keep edit screen available
            'show_in_menu'    => false,         // ← hide from sidebar menu
            'show_in_rest'    => true,          // so block can query lists
            'menu_icon'       => 'dashicons-list-view',
            'supports'        => [ 'title' ],   // title = list name
        ] );

        // Store the ordered company IDs as a single meta array on the list post.
        register_post_meta( 'company_list', '_wcd_company_ids', [
            'type'          => 'array',
            'single'        => true,
            'show_in_rest'  => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'integer' ],
                ],
            ],
            'auth_callback' => function() { return current_user_can('edit_posts'); },
        ] );
    }

    public static function register_meta_fields() : void {
        // Rating (int 1-10)
        register_post_meta( 'company', '_wcd_rating', [
            'type'         => 'integer',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => 0,
            'auth_callback'=> fn() => current_user_can('edit_posts'),
        ] );

        // Has free trial (boolean)
        register_post_meta( 'company', '_wcd_has_free_trial', [
            'type'         => 'boolean',
            'single'       => true,
            'show_in_rest' => true,
            'default'      => false,
            'auth_callback'=> fn() => current_user_can('edit_posts'),
        ] );

        // Benefits (array of 3 strings)
        register_post_meta( 'company', '_wcd_benefits', [
            'type'         => 'array',
            'single'       => true,
            'show_in_rest' => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
            ],
            'auth_callback'=> fn() => current_user_can('edit_posts'),
        ] );

        // Cons (array of 3 strings)
        register_post_meta( 'company', '_wcd_cons', [
            'type'         => 'array',
            'single'       => true,
            'show_in_rest' => [
                'schema' => [
                    'type'  => 'array',
                    'items' => [ 'type' => 'string' ],
                ],
            ],
            'auth_callback'=> fn() => current_user_can('edit_posts'),
        ] );
    }

    /**
     * Find a 'company' post by exact title using WP_Query, avoiding deprecated get_page_by_title().
     * Returns a WP_Post on success or null if none.
     */
    private static function get_company_by_exact_title( string $title ): ?\WP_Post {
        if ( '' === $title ) return null;

        // Search by title text; we'll confirm exact match after.
        $q = new \WP_Query([
            'post_type'      => 'company',
            'post_status'    => 'any',
            's'              => $title,   // broad search
            'posts_per_page' => 25,       // small batch; tweak if you expect many near-duplicates
            'no_found_rows'  => true,
            'fields'         => 'all',
        ]);

        if ( ! $q->have_posts() ) return null;

        foreach ( $q->posts as $post ) {
            // Exact, case-insensitive compare to avoid false positives from search
            if ( 0 === strcasecmp( $post->post_title, $title ) ) {
                return $post;
            }
        }

        return null;
    }

    /**
     * Create or update a company from one JSON row.
     * Data format follows companies_data.json provided in /data. 
     * Keys: name, rating, benefits[], cons[], has_free_trial, summary
     */
    public static function upsert_company_from_array( array $row ) : int {
        $name         = sanitize_text_field( $row['name'] ?? '' );
        $rating       = intval( $row['rating'] ?? 0 );
        $benefits     = array_map( 'sanitize_text_field', (array) ( $row['benefits'] ?? [] ) );
        $cons         = array_map( 'sanitize_text_field', (array) ( $row['cons'] ?? [] ) );
        $has_free     = (bool) ( $row['has_free_trial'] ?? false );
        $summary      = wp_kses_post( $row['summary'] ?? '' );

        if ( ! $name ) return 0;

        // Find by exact title if exists
        $existing = self::get_company_by_exact_title( $name );

        if ( $existing ) {
            $post_id = $existing->ID;
            wp_update_post([
                'ID'           => $post_id,
                'post_content' => $summary,
            ]);
        } else {
            $post_id = wp_insert_post([
                'post_type'    => 'company',
                'post_status'  => 'publish',
                'post_title'   => $name,
                'post_content' => $summary,
            ]);
        }

        if ( $post_id && ! is_wp_error( $post_id ) ) {
            update_post_meta( $post_id, '_wcd_rating', $rating );
            update_post_meta( $post_id, '_wcd_benefits', $benefits );
            update_post_meta( $post_id, '_wcd_cons', $cons );
            update_post_meta( $post_id, '_wcd_has_free_trial', $has_free );
            return (int) $post_id;
        }

        return 0;
    }
}
