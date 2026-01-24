<?php
/**
 * REST Settings Controller.
 *
 * Handles global settings, colors, fonts, variables, and breakpoints.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge\REST;

/**
 * Settings REST controller.
 *
 * @since 1.1.0
 */
class REST_Settings extends REST_Controller {

    /**
     * Register settings routes.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_routes(): void {
        register_rest_route(
            $this->get_namespace(),
            '/colors',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_colors' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/fonts',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_fonts' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/variables',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_variables' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/breakpoints',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_breakpoints' ),
                'permission_callback' => '__return_true',
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/global-styles',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_global_styles' ),
                'permission_callback' => '__return_true',
                'args'                => array(
                    'category' => array(
                        'description' => __( 'Style category.', 'oxybridge-wp' ),
                        'type'        => 'string',
                        'default'     => 'all',
                        'enum'        => array( 'all', 'colors', 'fonts', 'spacing', 'breakpoints' ),
                    ),
                ),
            )
        );

        register_rest_route(
            $this->get_namespace(),
            '/css-classes',
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_css_classes' ),
                'permission_callback' => '__return_true',
            )
        );
    }

    /**
     * Get colors endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_colors( \WP_REST_Request $request ) {
        $colors = $this->fetch_colors();

        return $this->format_response(
            array(
                'colors' => $colors,
                'count'  => count( $colors ),
            )
        );
    }

    /**
     * Get fonts endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_fonts( \WP_REST_Request $request ) {
        $fonts = $this->fetch_fonts();

        return $this->format_response(
            array(
                'fonts' => $fonts,
                'count' => count( $fonts ),
            )
        );
    }

    /**
     * Get variables endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_variables( \WP_REST_Request $request ) {
        $variables = $this->fetch_variables();

        return $this->format_response(
            array(
                'variables' => $variables,
                'count'     => count( $variables ),
            )
        );
    }

    /**
     * Get breakpoints endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_breakpoints( \WP_REST_Request $request ) {
        $breakpoints = $this->fetch_breakpoints();

        return $this->format_response(
            array(
                'breakpoints' => $breakpoints,
                'count'       => count( $breakpoints ),
            )
        );
    }

    /**
     * Get global styles endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_global_styles( \WP_REST_Request $request ) {
        $category = $request->get_param( 'category' );

        $styles = array();

        if ( $category === 'all' || $category === 'colors' ) {
            $styles['colors'] = $this->fetch_colors();
        }

        if ( $category === 'all' || $category === 'fonts' ) {
            $styles['fonts'] = $this->fetch_fonts();
        }

        if ( $category === 'all' || $category === 'breakpoints' ) {
            $styles['breakpoints'] = $this->fetch_breakpoints();
        }

        if ( $category === 'all' || $category === 'spacing' ) {
            $styles['spacing'] = $this->fetch_spacing();
        }

        return $this->format_response( $styles );
    }

    /**
     * Get CSS classes endpoint callback.
     *
     * @since 1.1.0
     * @param \WP_REST_Request $request The request object.
     * @return \WP_REST_Response
     */
    public function get_css_classes( \WP_REST_Request $request ) {
        $classes = $this->fetch_css_classes();

        return $this->format_response(
            array(
                'classes' => $classes,
                'count'   => count( $classes ),
            )
        );
    }

    /**
     * Fetch colors from global settings.
     *
     * @since 1.1.0
     * @return array Array of color definitions.
     */
    private function fetch_colors(): array {
        $option_prefix = $this->get_option_prefix();
        $settings_json = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( empty( $settings_json ) ) {
            return $this->get_default_colors();
        }

        $settings = json_decode( $settings_json, true );

        if ( ! is_array( $settings ) ) {
            return $this->get_default_colors();
        }

        $colors = array();

        // Extract color palette.
        if ( isset( $settings['colorPalette'] ) && is_array( $settings['colorPalette'] ) ) {
            foreach ( $settings['colorPalette'] as $color ) {
                if ( isset( $color['name'] ) && isset( $color['value'] ) ) {
                    $colors[] = array(
                        'name'     => $color['name'],
                        'value'    => $color['value'],
                        'variable' => $color['variable'] ?? null,
                    );
                }
            }
        }

        return $colors;
    }

    /**
     * Fetch fonts from global settings.
     *
     * @since 1.1.0
     * @return array Array of font definitions.
     */
    private function fetch_fonts(): array {
        $option_prefix = $this->get_option_prefix();
        $settings_json = get_option( $option_prefix . 'global_settings_json_string', '' );

        if ( empty( $settings_json ) ) {
            return $this->get_default_fonts();
        }

        $settings = json_decode( $settings_json, true );

        if ( ! is_array( $settings ) ) {
            return $this->get_default_fonts();
        }

        $fonts = array();

        // Extract typography settings.
        if ( isset( $settings['typography']['fonts'] ) && is_array( $settings['typography']['fonts'] ) ) {
            foreach ( $settings['typography']['fonts'] as $font ) {
                $fonts[] = array(
                    'family'   => $font['family'] ?? '',
                    'fallback' => $font['fallback'] ?? 'sans-serif',
                    'weights'  => $font['weights'] ?? array( '400', '700' ),
                    'source'   => $font['source'] ?? 'google',
                );
            }
        }

        return $fonts;
    }

    /**
     * Fetch variables from global settings.
     *
     * @since 1.1.0
     * @return array Array of CSS variables.
     */
    private function fetch_variables(): array {
        $option_prefix = $this->get_option_prefix();
        $variables_json = get_option( $option_prefix . 'variables_json_string', '' );

        if ( empty( $variables_json ) ) {
            return array();
        }

        $variables = json_decode( $variables_json, true );

        if ( ! is_array( $variables ) ) {
            return array();
        }

        $formatted = array();

        foreach ( $variables as $key => $value ) {
            $formatted[] = array(
                'name'  => $key,
                'value' => $value,
            );
        }

        return $formatted;
    }

    /**
     * Fetch breakpoints from global settings.
     *
     * @since 1.1.0
     * @return array Array of breakpoint definitions.
     */
    private function fetch_breakpoints(): array {
        // Default Oxygen/Breakdance breakpoints.
        return array(
            array(
                'id'       => 'breakpoint_base',
                'label'    => __( 'Desktop', 'oxybridge-wp' ),
                'minWidth' => 992,
                'maxWidth' => null,
                'default'  => true,
            ),
            array(
                'id'       => 'breakpoint_tablet_landscape',
                'label'    => __( 'Tablet Landscape', 'oxybridge-wp' ),
                'minWidth' => 768,
                'maxWidth' => 991,
                'default'  => false,
            ),
            array(
                'id'       => 'breakpoint_tablet_portrait',
                'label'    => __( 'Tablet Portrait', 'oxybridge-wp' ),
                'minWidth' => 576,
                'maxWidth' => 767,
                'default'  => false,
            ),
            array(
                'id'       => 'breakpoint_phone_landscape',
                'label'    => __( 'Phone Landscape', 'oxybridge-wp' ),
                'minWidth' => 480,
                'maxWidth' => 575,
                'default'  => false,
            ),
            array(
                'id'       => 'breakpoint_phone_portrait',
                'label'    => __( 'Phone Portrait', 'oxybridge-wp' ),
                'minWidth' => 0,
                'maxWidth' => 479,
                'default'  => false,
            ),
        );
    }

    /**
     * Fetch spacing settings.
     *
     * @since 1.1.0
     * @return array Spacing settings.
     */
    private function fetch_spacing(): array {
        return array(
            'units' => array( 'px', 'em', 'rem', '%', 'vw', 'vh' ),
            'scale' => array(
                'xs'  => '4px',
                'sm'  => '8px',
                'md'  => '16px',
                'lg'  => '24px',
                'xl'  => '32px',
                '2xl' => '48px',
                '3xl' => '64px',
            ),
        );
    }

    /**
     * Fetch CSS classes.
     *
     * @since 1.1.0
     * @return array Array of CSS class definitions.
     */
    private function fetch_css_classes(): array {
        $option_prefix = $this->get_option_prefix();
        $classes_json = get_option( $option_prefix . 'breakdance_classes_json_string', '' );

        if ( empty( $classes_json ) ) {
            return array();
        }

        $classes = json_decode( $classes_json, true );

        if ( ! is_array( $classes ) ) {
            return array();
        }

        $formatted = array();

        foreach ( $classes as $class ) {
            if ( isset( $class['name'] ) ) {
                $formatted[] = array(
                    'id'     => $class['id'] ?? '',
                    'name'   => $class['name'],
                    'styles' => $class['styles'] ?? array(),
                );
            }
        }

        return $formatted;
    }

    /**
     * Get default colors.
     *
     * @since 1.1.0
     * @return array Default color palette.
     */
    private function get_default_colors(): array {
        return array(
            array( 'name' => 'Primary', 'value' => '#3B82F6' ),
            array( 'name' => 'Secondary', 'value' => '#10B981' ),
            array( 'name' => 'Accent', 'value' => '#8B5CF6' ),
            array( 'name' => 'Text', 'value' => '#1F2937' ),
            array( 'name' => 'Background', 'value' => '#FFFFFF' ),
        );
    }

    /**
     * Get default fonts.
     *
     * @since 1.1.0
     * @return array Default font settings.
     */
    private function get_default_fonts(): array {
        return array(
            array(
                'family'   => 'Inter',
                'fallback' => 'sans-serif',
                'weights'  => array( '400', '500', '600', '700' ),
                'source'   => 'google',
            ),
        );
    }
}
