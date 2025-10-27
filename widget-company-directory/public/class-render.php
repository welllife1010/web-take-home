<?php
namespace WCD;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Registers the block from block.json and wires a render_callback
 * so the frontend HTML is built in PHP (dynamic block).
 */
class Render {

    public static function init() : void {
        add_action( 'init', [ __CLASS__, 'register_block' ] );
    }

    public static function register_block() : void {
        register_block_type(
            WIDGET_COMPANY_DIRECTORY_PLUGIN_DIR . 'build/blocks/company-list',
            [
                'render_callback' => [ __CLASS__, 'render_company_list' ],
            ]
        );
    }

    public static function render_company_list( array $attributes, string $content ) : string {
        $list_id = isset($attributes['listId']) ? intval($attributes['listId']) : 0;

        // Wrapper attributes: includes wp-block-* class and alignment classes automatically.
        $wrapper_attrs = function_exists('get_block_wrapper_attributes')
            ? get_block_wrapper_attributes( [ 'class' => 'wcd-company-list' ] )
            : 'class="wcd-company-list"';

        if ( $list_id <= 0 ) {
            return "<div $wrapper_attrs><em>" . esc_html__( 'No list selected.', 'widget-company-directory' ) . "</em></div>";
        }

        $ids = (array) get_post_meta( $list_id, '_wcd_company_ids', true );
        if ( empty( $ids ) ) {
            return "<div $wrapper_attrs><em>" . esc_html__( 'List is empty.', 'widget-company-directory' ) . "</em></div>";
        }

        // Normalize IDs
        $ids = (array) get_post_meta( $list_id, '_wcd_company_ids', true );
        $ids = array_values( array_filter( array_map( 'intval', $ids ), fn( $id ) => $id > 0 ) );

        if ( empty( $ids ) ) {
            return "<div $wrapper_attrs><em>" . esc_html__( 'List is empty.', 'widget-company-directory' ) . "</em></div>";
        }

        ob_start();
        echo "<ul $wrapper_attrs>";
        foreach ( $ids as $cid ) {
            $post = get_post( $cid );
            if ( ! $post ) {
                continue; // skip missing/deleted items
            }

            $title    = get_the_title( $cid );
            $raw      = get_post_field( 'post_content', $cid );
            $summary  = wpautop( wp_kses_post( $raw ) ); // no the_content filter; avoids recursion & saves memory
            $rating   = (int) get_post_meta( $cid, '_wcd_rating', true );
            $benefits = (array) get_post_meta( $cid, '_wcd_benefits', true );
            $cons     = (array) get_post_meta( $cid, '_wcd_cons', true );
            $trial    = (bool) get_post_meta( $cid, '_wcd_has_free_trial', true );

            ?>
            <article class="wcd-company">
                <h3>
                    <?php echo esc_html( $title ); ?>
                </h3>

                <div class="company-rating"><?php echo esc_html( 'Rating: ' . $rating . '/10' ); ?></div>


                <?php 
                    if ( $trial ) {
                        echo '<span class="free-trial-badge">' . esc_html__( 'Free Trial', 'widget-company-directory' ) . '</span>';
                    }
                ?>

                <?php if ( ! empty( $benefits ) ) : ?>
                    <div class="company-benefits">
                        <strong><?php esc_html_e('Benefits:','widget-company-directory'); ?></strong>
                        <ul>
                            <?php foreach ( $benefits as $b ) : ?>
                                <li><?php echo esc_html( $b ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ( ! empty( $cons ) ) : ?>
                    <div class="company-cons">
                        <strong><?php esc_html_e('Cons:','widget-company-directory'); ?></strong>
                        <ul>
                            <?php foreach ( $cons as $c ) : ?>
                                <li><?php echo esc_html( $c ); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="company-summary"><?php echo $summary; // already filtered ?></div>
            </article>
            <?php
        }
        echo '</ul>';
        return ob_get_clean();
    }
}
