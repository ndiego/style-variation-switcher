<?php
/**
 * Plugin Name: Style Variation Switcher
 * Description: Allows visitors to switch between block theme style variations on the frontend.
 * Version:     0.1.0
 * Author:      Nick Diego
 * License:     GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: style-variation-switcher
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Style_Variation_Switcher {
    public function __construct() {
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_footer', array( $this, 'render_switcher' ) );
        add_action( 'wp_head', array( $this, 'preload_variation_fonts' ), 100 );
    }

    /**
     * Preload all fonts from all variations and the default theme
     */
    public function preload_variation_fonts(): void {
        $variations  = WP_Theme_JSON_Resolver::get_style_variations();
        $theme_data  = WP_Theme_JSON_Resolver::get_theme_data();
        $font_faces  = array();
        
        // Get fonts from default theme.
        $default_fonts = $theme_data->get_data()['settings']['typography']['fontFamilies']['theme'] ?? array();
        
        foreach ( $default_fonts as $font ) {
            if ( isset( $font['fontFace'] ) ) {
                $generated = $this->generate_font_face( $font );
                if ( $generated ) {
                    $font_faces[] = $generated;
                }
            }
        }

        // Get fonts from all variations.
        foreach ( $variations as $variation ) {
            $variation_fonts = $variation['settings']['typography']['fontFamilies']['theme'] ?? array();
            
            foreach ( $variation_fonts as $font ) {
                if ( isset( $font['fontFace'] ) ) {
                    $generated = $this->generate_font_face( $font );
                    if ( $generated ) {
                        $font_faces[] = $generated;
                    }
                }
            }
        }

        $unique_font_faces = array_unique( array_filter( $font_faces ) );

        if ( ! empty( $unique_font_faces ) ) {
            echo '<style class="wp-fonts-local">' . "\n";
            echo implode( "\n", $unique_font_faces );
            echo '</style>' . "\n";
        }
    }

    /**
     * Generate font-face CSS for a font
     * 
     * @param array $font Font configuration.
     * @return string Font-face CSS.
     */
    private function generate_font_face( $font ): string {
        if ( ! isset( $font['fontFace'] ) ) {
            return '';
        }

        $css = '';
        foreach ( $font['fontFace'] as $face ) {
            if ( ! isset( $face['src'] ) ) {
                continue;
            }

            $srcs = array_map( function( $src ) {
                // Clean up the file path.
                $src = str_replace( 'file:./', '', $src );
                $src = str_replace( 'file:/', '', $src );
                
                // Get the full URL.
                $url = get_theme_file_uri( $src );
                return sprintf(
                    "url('%s') format('%s')",
                    esc_url( $url ),
                    $face['format'] ?? 'woff2'
                );
            }, $face['src'] );

            $font_family = preg_replace( '/,\s*(?:serif|sans-serif|monospace)$/i', '', $font['fontFamily'] );

            if ( ! empty( $srcs ) ) {
                $css .= sprintf(
                    "@font-face{font-family:%s;font-style:%s;font-weight:%s;font-display:%s;src:%s;}",
                    $font_family,
                    $face['fontStyle'] ?? 'normal',
                    $face['fontWeight'] ?? '400',
                    'fallback',
                    implode( ',', $srcs )
                );
            }
        }

        return $css;
    }

    public function enqueue_assets(): void {
        wp_enqueue_style(
            'style-variation-switcher',
            plugin_dir_url( __FILE__ ) . 'assets/css/style-variation-switcher.css',
            array(),
            '1.0.0'
        );

        wp_enqueue_script(
            'style-variation-switcher',
            plugin_dir_url( __FILE__ ) . 'assets/js/style-variation-switcher.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        $variations = array_map( function( $variation ) {
            if ( isset( $variation['settings'] ) && 
                array_key_exists( 'color', $variation['settings'] ) && 
                count( array_diff( array_keys( $variation['settings'] ), array( 'color', 'custom' ) ) ) === 0 ) {
                $variation['color_only'] = true;
            } else {
                $variation['color_only'] = false;
            }
            return $variation;
        }, WP_Theme_JSON_Resolver::get_style_variations() );
        
        // Remove duplicates based on title.
        $variations = array_values( array_reduce( $variations, function( $carry, $variation ) {
            $carry[ sanitize_title( $variation['title'] ) ] = $variation;
            return $carry;
        }, array() ) );
        
        $variation_styles = array();
        
        // Get default stylesheet.
        $variation_styles['default'] = wp_get_global_stylesheet();
        
        // Get each variation's styles.
        foreach ( $variations as $variation ) {        
            $variation_slug = sanitize_title( $variation['title'] );
            $variation_styles[ $variation_slug ] = $this->generate_variation_css( $variation );
        }

        wp_localize_script( 'style-variation-switcher', 'styleVariationSwitcher', array(
            'variations'     => $variation_styles,
            'variationsData' => $variations,
        ) );
    }

    /**
     * Generate CSS for a single variation
     * 
     * @param array $variation The variation data.
     * @return string Generated CSS.
     */
    private function generate_variation_css( $variation ) {
        $base_theme_data = WP_Theme_JSON_Resolver::get_theme_data();
        
        // Create a new theme.json instance with the variation data.
        $variation_json = new WP_Theme_JSON(
            array_merge(
                $base_theme_data->get_data(),
                $variation
            )
        );

        return $variation_json->get_stylesheet() ?? '';
    }

    public function render_switcher(): void {
        static $variations = null;
        if ( is_null( $variations ) ) {
            $variations = array_map( function( $variation ) {
                if ( isset( $variation['settings'] ) && 
                    array_key_exists( 'color', $variation['settings'] ) && 
                    count( array_diff( array_keys( $variation['settings'] ), array( 'color', 'custom' ) ) ) === 0 ) {
                    $variation['color_only'] = true;
                } else {
                    $variation['color_only'] = false;
                }
                return $variation;
            }, WP_Theme_JSON_Resolver::get_style_variations() );

            // Remove duplicates based on title.
            $variations = array_values( array_reduce( $variations, function( $carry, $variation ) {
                $carry[ sanitize_title( $variation['title'] ) ] = $variation;
                return $carry;
            }, array() ) );

            // Filter to only show color-only variations.
            $variations = array_filter( $variations, function( $variation ) {
                return isset( $variation['color_only'] ) && $variation['color_only'] === true;
            } );
        }
        
        if ( empty( $variations ) ) {
            return;
        }

        // Get default theme colors from theme.json.
        $theme_data = WP_Theme_JSON_Resolver::get_theme_data();
        $default_colors = $theme_data->get_data()['settings']['color']['palette'] ?? array();

        $default_palette = array(
            'base'     => $default_colors[0]['color'] ?? '#000000',
            'contrast' => $default_colors[1]['color'] ?? '#ffffff',
            'accent1'  => $default_colors[2]['color'] ?? '#dddddd',
            'accent2'  => $default_colors[3]['color'] ?? '#999999',
        );

        ?>
        <div class="style-variation-switcher">
            <button class="color-swatch default-swatch active" data-value="">
                <div class="swatch-grid">
                    <span class="swatch-color" style="background-color: <?php echo esc_attr( $default_palette['base'] ); ?>"></span>
                    <span class="swatch-color" style="background-color: <?php echo esc_attr( $default_palette['contrast'] ); ?>"></span>
                    <span class="swatch-color" style="background-color: <?php echo esc_attr( $default_palette['accent1'] ); ?>"></span>
                    <span class="swatch-color" style="background-color: <?php echo esc_attr( $default_palette['accent2'] ); ?>"></span>
                </div>
                <span class="screen-reader-text"><?php esc_html_e( 'Default Style', 'style-variation-switcher' ); ?></span>
            </button>
            <?php foreach ( $variations as $variation ) : ?>
                <?php 
                $variation_slug = sanitize_title( $variation['title'] );
                $colors = array(
                    'base'     => $variation['settings']['color']['palette']['theme'][0]['color'] ?? '#000000',
                    'contrast' => $variation['settings']['color']['palette']['theme'][1]['color'] ?? '#ffffff',
                    'accent1'  => $variation['settings']['color']['palette']['theme'][2]['color'] ?? '#dddddd',
                    'accent2'  => $variation['settings']['color']['palette']['theme'][3]['color'] ?? '#999999',
                );
                ?>
                <button class="color-swatch" data-value="<?php echo esc_attr( $variation_slug ); ?>">
                    <div class="swatch-grid">
                        <span class="swatch-color" style="background-color: <?php echo esc_attr( $colors['base'] ); ?>"></span>
                        <span class="swatch-color" style="background-color: <?php echo esc_attr( $colors['contrast'] ); ?>"></span>
                        <span class="swatch-color" style="background-color: <?php echo esc_attr( $colors['accent1'] ); ?>"></span>
                        <span class="swatch-color" style="background-color: <?php echo esc_attr( $colors['accent2'] ); ?>"></span>
                    </div>
                    <span class="screen-reader-text"><?php echo esc_html( $variation['title'] ); ?></span>
                </button>
            <?php endforeach; ?>
        </div>
        <?php
    }
}

new Style_Variation_Switcher();


