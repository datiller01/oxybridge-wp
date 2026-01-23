<?php
/**
 * Design Tokens Class
 *
 * Extracts and formats site design tokens (colors, fonts, spacing) for AI agents.
 * Provides a compact, optimized token set from Breakdance global settings.
 *
 * @package Oxybridge
 * @since 1.1.0
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Design_Tokens
 *
 * Extracts design tokens from Breakdance global settings.
 *
 * @since 1.1.0
 */
class Design_Tokens {

    /**
     * Option prefix for Breakdance settings.
     *
     * @var string
     */
    const OPTION_PREFIX = 'breakdance_';

    /**
     * Get all design tokens as a compact structure.
     *
     * Returns colors, fonts, and spacing in an AI-friendly format.
     *
     * @since 1.1.0
     *
     * @return array {
     *     Design tokens array.
     *
     *     @type array $colors  Named color tokens.
     *     @type array $fonts   Font family tokens.
     *     @type array $spacing Spacing scale tokens.
     *     @type array $meta    Metadata about the tokens.
     * }
     */
    public function get_tokens(): array {
        return array(
            'colors'  => $this->get_color_tokens(),
            'fonts'   => $this->get_font_tokens(),
            'spacing' => $this->get_spacing_tokens(),
            'meta'    => array(
                'extracted_at' => current_time( 'c' ),
                'site_url'     => get_site_url(),
            ),
        );
    }

    /**
     * Get color tokens.
     *
     * Extracts named colors from Breakdance global settings
     * and formats them as simple key-value pairs.
     *
     * @since 1.1.0
     *
     * @return array Associative array of color name => hex value.
     */
    public function get_color_tokens(): array {
        $tokens   = array();
        $settings = $this->get_global_settings();

        if ( empty( $settings['colors'] ) || ! is_array( $settings['colors'] ) ) {
            return $this->get_default_colors();
        }

        foreach ( $settings['colors'] as $color ) {
            if ( isset( $color['id'], $color['value'] ) ) {
                // Create a clean key from the ID or label.
                $key = isset( $color['label'] )
                    ? $this->slugify( $color['label'] )
                    : $color['id'];

                $tokens[ $key ] = $color['value'];
            }
        }

        // If no colors found, return sensible defaults.
        if ( empty( $tokens ) ) {
            return $this->get_default_colors();
        }

        return $tokens;
    }

    /**
     * Get font tokens.
     *
     * Extracts font family definitions from Breakdance settings.
     *
     * @since 1.1.0
     *
     * @return array Associative array of font role => font family.
     */
    public function get_font_tokens(): array {
        $tokens   = array();
        $settings = $this->get_global_settings();

        // Extract typography settings.
        if ( isset( $settings['typography'] ) && is_array( $settings['typography'] ) ) {
            $typography = $settings['typography'];

            // Look for heading and body font definitions.
            if ( isset( $typography['heading']['fontFamily'] ) ) {
                $tokens['heading'] = $typography['heading']['fontFamily'];
            }
            if ( isset( $typography['body']['fontFamily'] ) ) {
                $tokens['body'] = $typography['body']['fontFamily'];
            }

            // Also extract font sizes if available.
            if ( isset( $typography['heading']['fontSize'] ) ) {
                $tokens['heading_size'] = $typography['heading']['fontSize'];
            }
            if ( isset( $typography['body']['fontSize'] ) ) {
                $tokens['body_size'] = $typography['body']['fontSize'];
            }
        }

        // If no fonts found, return sensible defaults.
        if ( empty( $tokens ) ) {
            return $this->get_default_fonts();
        }

        return $tokens;
    }

    /**
     * Get spacing tokens.
     *
     * Extracts spacing scale from Breakdance settings.
     *
     * @since 1.1.0
     *
     * @return array Associative array of spacing size => value.
     */
    public function get_spacing_tokens(): array {
        $tokens   = array();
        $settings = $this->get_global_settings();

        // Extract spacing settings.
        if ( isset( $settings['spacing'] ) && is_array( $settings['spacing'] ) ) {
            foreach ( $settings['spacing'] as $key => $value ) {
                if ( is_string( $value ) || is_numeric( $value ) ) {
                    $tokens[ $key ] = $value;
                }
            }
        }

        // If no spacing found, return sensible defaults.
        if ( empty( $tokens ) ) {
            return $this->get_default_spacing();
        }

        return $tokens;
    }

    /**
     * Get default color tokens.
     *
     * @since 1.1.0
     *
     * @return array Default color tokens.
     */
    private function get_default_colors(): array {
        return array(
            'primary'    => '#0066cc',
            'secondary'  => '#1d293d',
            'accent'     => '#ff6b35',
            'text'       => '#333333',
            'text_light' => '#666666',
            'background' => '#ffffff',
            'surface'    => '#f5f5f5',
        );
    }

    /**
     * Get default font tokens.
     *
     * @since 1.1.0
     *
     * @return array Default font tokens.
     */
    private function get_default_fonts(): array {
        return array(
            'heading'      => 'Inter, sans-serif',
            'body'         => 'Inter, sans-serif',
            'heading_size' => '2.5rem',
            'body_size'    => '1rem',
        );
    }

    /**
     * Get default spacing tokens.
     *
     * @since 1.1.0
     *
     * @return array Default spacing tokens.
     */
    private function get_default_spacing(): array {
        return array(
            'xs'  => '4px',
            'sm'  => '8px',
            'md'  => '16px',
            'lg'  => '32px',
            'xl'  => '64px',
            '2xl' => '128px',
        );
    }

    /**
     * Get global settings from Breakdance.
     *
     * @since 1.1.0
     *
     * @return array Global settings array.
     */
    private function get_global_settings(): array {
        // Try using Breakdance Data API first.
        if ( function_exists( 'Breakdance\Data\get_global_option' ) ) {
            $settings = \Breakdance\Data\get_global_option( 'global_settings_json_string' );
            if ( is_array( $settings ) ) {
                return $settings;
            }
            if ( is_string( $settings ) && ! empty( $settings ) ) {
                $decoded = json_decode( $settings, true );
                if ( is_array( $decoded ) ) {
                    return $decoded;
                }
            }
        }

        // Fallback to direct option access.
        $option_name     = self::OPTION_PREFIX . 'global_settings_json_string';
        $global_settings = get_option( $option_name, '' );

        if ( is_string( $global_settings ) && ! empty( $global_settings ) ) {
            $decoded = json_decode( $global_settings, true );
            if ( is_array( $decoded ) ) {
                return $decoded;
            }
        }

        return array();
    }

    /**
     * Convert a string to a slug.
     *
     * @since 1.1.0
     *
     * @param string $text Text to convert.
     * @return string Slugified text.
     */
    private function slugify( string $text ): string {
        $text = strtolower( $text );
        $text = preg_replace( '/[^a-z0-9]+/', '_', $text );
        $text = trim( $text, '_' );
        return $text;
    }
}
