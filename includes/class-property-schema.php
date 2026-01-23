<?php
/**
 * Property Schema Class
 *
 * Provides comprehensive property mappings from simplified AI-friendly names
 * to full Breakdance/Oxygen property paths. Handles effects, transforms,
 * backgrounds, borders, and other styling properties.
 *
 * @package Oxybridge
 * @since 1.0.0
 */

namespace Oxybridge;

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class Property_Schema
 *
 * Defines the mapping between simplified property names used by AI and
 * the full property paths expected by Breakdance/Oxygen builders.
 *
 * Example usage:
 * ```php
 * $schema = new Property_Schema();
 * $path = $schema->get_property_path('opacity');
 * // Returns 'design.effects.opacity'
 * ```
 *
 * @since 1.0.0
 */
class Property_Schema {

    /**
     * Effects property mappings.
     *
     * Maps simplified effect names to Breakdance property paths.
     *
     * @var array
     */
    private static array $effects_mappings = array(
        // Opacity
        'opacity'                => 'design.effects.opacity',
        'opacityHover'           => 'design.effects.opacity_hover',

        // Box Shadow
        'boxShadow'              => 'design.effects.box_shadow',
        'boxShadowHover'         => 'design.effects.box_shadow_hover',

        // Mix Blend Mode
        'mixBlendMode'           => 'design.effects.mix_blend_mode',
        'mixBlendModeHover'      => 'design.effects.mix_blend_mode_hover',

        // Transition properties (applies to transition repeater items)
        'transitionDuration'     => 'design.effects.transition[].duration',
        'transitionTiming'       => 'design.effects.transition[].timing_function',
        'transitionProperty'     => 'design.effects.transition[].property',
        'transitionCustomProperty' => 'design.effects.transition[].custom_property',
        'transitionDelay'        => 'design.effects.transition[].delay',

        // Transform section properties
        'transformOrigin'        => 'design.effects.transform.origin',
        'transformOriginHover'   => 'design.effects.transform.origin_hover',
        'transformOriginPosition' => 'design.effects.transform.origin_position',
        'transformOriginPositionHover' => 'design.effects.transform.origin_position_hover',
        'transformPerspective'   => 'design.effects.transform.perspective',
        'transformPerspectiveHover' => 'design.effects.transform.perspective_hover',
        'transformPerspectiveOrigin' => 'design.effects.transform.perspective_origin',
        'transformPerspectiveOriginHover' => 'design.effects.transform.perspective_origin_hover',
        'transformStyle'         => 'design.effects.transform.transform_style',
        'transformStyleHover'    => 'design.effects.transform.transform_style_hover',

        // Individual transform types (applied via transforms repeater)
        // Rotate transforms
        'rotate'                 => 'design.effects.transform.transforms[].type=rotate',
        'rotateX'                => 'design.effects.transform.transforms[].rotate_x',
        'rotateY'                => 'design.effects.transform.transforms[].rotate_y',
        'rotateZ'                => 'design.effects.transform.transforms[].rotate_z',
        'rotateHover'            => 'design.effects.transform.transforms_hover[].type=rotate',
        'rotateXHover'           => 'design.effects.transform.transforms_hover[].rotate_x',
        'rotateYHover'           => 'design.effects.transform.transforms_hover[].rotate_y',
        'rotateZHover'           => 'design.effects.transform.transforms_hover[].rotate_z',

        // Rotate3D transforms (custom 3D axis rotation)
        'rotate3d'               => 'design.effects.transform.transforms[].type=rotate3d',
        'rotate3dX'              => 'design.effects.transform.transforms[].x',
        'rotate3dY'              => 'design.effects.transform.transforms[].y',
        'rotate3dZ'              => 'design.effects.transform.transforms[].z',
        'rotate3dAngle'          => 'design.effects.transform.transforms[].angle',
        'rotate3dHover'          => 'design.effects.transform.transforms_hover[].type=rotate3d',
        'rotate3dXHover'         => 'design.effects.transform.transforms_hover[].x',
        'rotate3dYHover'         => 'design.effects.transform.transforms_hover[].y',
        'rotate3dZHover'         => 'design.effects.transform.transforms_hover[].z',
        'rotate3dAngleHover'     => 'design.effects.transform.transforms_hover[].angle',

        // Scale transforms (uniform)
        'scale'                  => 'design.effects.transform.transforms[].scale',
        'scaleHover'             => 'design.effects.transform.transforms_hover[].scale',

        // Scale3D transforms (per-axis)
        'scale3d'                => 'design.effects.transform.transforms[].type=scale3d',
        'scaleX'                 => 'design.effects.transform.transforms[].scale_x',
        'scaleY'                 => 'design.effects.transform.transforms[].scale_y',
        'scaleZ'                 => 'design.effects.transform.transforms[].scale_z',
        'scale3dHover'           => 'design.effects.transform.transforms_hover[].type=scale3d',
        'scaleXHover'            => 'design.effects.transform.transforms_hover[].scale_x',
        'scaleYHover'            => 'design.effects.transform.transforms_hover[].scale_y',
        'scaleZHover'            => 'design.effects.transform.transforms_hover[].scale_z',

        // Skew transforms
        'skew'                   => 'design.effects.transform.transforms[].type=skew',
        'skewX'                  => 'design.effects.transform.transforms[].skew_x',
        'skewY'                  => 'design.effects.transform.transforms[].skew_y',
        'skewHover'              => 'design.effects.transform.transforms_hover[].type=skew',
        'skewXHover'             => 'design.effects.transform.transforms_hover[].skew_x',
        'skewYHover'             => 'design.effects.transform.transforms_hover[].skew_y',

        // Translate transforms
        'translate'              => 'design.effects.transform.transforms[].type=translate',
        'translateX'             => 'design.effects.transform.transforms[].translate_x',
        'translateY'             => 'design.effects.transform.transforms[].translate_y',
        'translateZ'             => 'design.effects.transform.transforms[].translate_z',
        'translateHover'         => 'design.effects.transform.transforms_hover[].type=translate',
        'translateXHover'        => 'design.effects.transform.transforms_hover[].translate_x',
        'translateYHover'        => 'design.effects.transform.transforms_hover[].translate_y',
        'translateZHover'        => 'design.effects.transform.transforms_hover[].translate_z',

        // Perspective transform (inline function in transforms repeater)
        'perspective'            => 'design.effects.transform.transforms[].type=perspective',
        'perspectiveValue'       => 'design.effects.transform.transforms[].perspective',
        'perspectiveHover'       => 'design.effects.transform.transforms_hover[].type=perspective',
        'perspectiveValueHover'  => 'design.effects.transform.transforms_hover[].perspective',

        // Custom transform origin position (X/Y percentage when origin is 'custom')
        'transformOriginX'       => 'design.effects.transform.origin_position.x',
        'transformOriginY'       => 'design.effects.transform.origin_position.y',
        'transformOriginXHover'  => 'design.effects.transform.origin_position_hover.x',
        'transformOriginYHover'  => 'design.effects.transform.origin_position_hover.y',

        // Perspective origin position (X/Y percentage)
        'perspectiveOriginX'     => 'design.effects.transform.perspective_origin.x',
        'perspectiveOriginY'     => 'design.effects.transform.perspective_origin.y',
        'perspectiveOriginXHover' => 'design.effects.transform.perspective_origin_hover.x',
        'perspectiveOriginYHover' => 'design.effects.transform.perspective_origin_hover.y',

        // Filter properties (applied via filter repeater)
        'filter'                 => 'design.effects.filter',
        'filterHover'            => 'design.effects.filter_hover',
        'filterBlur'             => 'design.effects.filter[].type=blur',
        'filterBlurAmount'       => 'design.effects.filter[].blur_amount',
        'filterBrightness'       => 'design.effects.filter[].type=brightness',
        'filterBrightnessAmount' => 'design.effects.filter[].amount',
        'filterContrast'         => 'design.effects.filter[].type=contrast',
        'filterContrastAmount'   => 'design.effects.filter[].amount',
        'filterGrayscale'        => 'design.effects.filter[].type=grayscale',
        'filterGrayscaleAmount'  => 'design.effects.filter[].amount',
        'filterHueRotate'        => 'design.effects.filter[].type=hue-rotate',
        'filterHueRotateAmount'  => 'design.effects.filter[].rotate',
        'filterInvert'           => 'design.effects.filter[].type=invert',
        'filterInvertAmount'     => 'design.effects.filter[].amount',
        'filterSaturate'         => 'design.effects.filter[].type=saturate',
        'filterSaturateAmount'   => 'design.effects.filter[].amount',
        'filterSepia'            => 'design.effects.filter[].type=sepia',
        'filterSepiaAmount'      => 'design.effects.filter[].amount',
    );

    /**
     * Background property mappings.
     *
     * Maps simplified background names to Breakdance property paths.
     * Supports gradients, blend modes, overlays, and background layers.
     *
     * @var array
     */
    private static array $background_mappings = array(
        // Basic background properties
        'background'             => 'design.background',
        'backgroundType'         => 'design.background.type',
        'backgroundColor'        => 'design.background.color',
        'backgroundImage'        => 'design.background.image',
        'backgroundHover'        => 'design.background_hover',
        'backgroundTypeHover'    => 'design.background_hover.type',
        'backgroundColorHover'   => 'design.background_hover.color',
        'backgroundImageHover'   => 'design.background_hover.image',

        // Background position, size, repeat, attachment
        'backgroundPosition'     => 'design.background.position',
        'backgroundPositionHover' => 'design.background_hover.position',
        'backgroundSize'         => 'design.background.size',
        'backgroundSizeHover'    => 'design.background_hover.size',
        'backgroundRepeat'       => 'design.background.repeat',
        'backgroundRepeatHover'  => 'design.background_hover.repeat',
        'backgroundAttachment'   => 'design.background.attachment',
        'backgroundAttachmentHover' => 'design.background_hover.attachment',

        // Gradient properties
        'gradient'               => 'design.background.gradient',
        'gradientStyle'          => 'design.background.gradient.style',
        'gradientValue'          => 'design.background.gradient.value',
        'gradientType'           => 'design.background.gradient.type',
        'gradientAngle'          => 'design.background.gradient.angle',
        'gradientColors'         => 'design.background.gradient.colors',
        'gradientStops'          => 'design.background.gradient.stops',
        'gradientHover'          => 'design.background_hover.gradient',
        'gradientStyleHover'     => 'design.background_hover.gradient.style',
        'gradientValueHover'     => 'design.background_hover.gradient.value',
        'gradientTypeHover'      => 'design.background_hover.gradient.type',
        'gradientAngleHover'     => 'design.background_hover.gradient.angle',
        'gradientColorsHover'    => 'design.background_hover.gradient.colors',
        'gradientStopsHover'     => 'design.background_hover.gradient.stops',

        // Radial gradient specific
        'gradientRadialPosition' => 'design.background.gradient.position',
        'gradientRadialPositionHover' => 'design.background_hover.gradient.position',
        'gradientRadialShape'    => 'design.background.gradient.shape',
        'gradientRadialShapeHover' => 'design.background_hover.gradient.shape',
        'gradientRadialSize'     => 'design.background.gradient.size',
        'gradientRadialSizeHover' => 'design.background_hover.gradient.size',

        // Gradient animation
        'gradientAnimation'      => 'design.background.gradient.animation',
        'gradientAnimationDuration' => 'design.background.gradient.animation_duration',

        // Background blend mode
        'backgroundBlendMode'    => 'design.background.blend_mode',
        'backgroundBlendModeHover' => 'design.background_hover.blend_mode',
        'blendMode'              => 'design.background.blend_mode',
        'blendModeHover'         => 'design.background_hover.blend_mode',

        // Overlay properties
        'overlay'                => 'design.background.overlay',
        'overlayColor'           => 'design.background.overlay.color',
        'overlayColorHover'      => 'design.background_hover.overlay.color',
        'overlayImage'           => 'design.background.overlay.image',
        'overlayImageHover'      => 'design.background_hover.overlay.image',
        'overlayGradient'        => 'design.background.overlay.gradient',
        'overlayGradientHover'   => 'design.background_hover.overlay.gradient',
        'overlayOpacity'         => 'design.background.overlay.opacity',
        'overlayOpacityHover'    => 'design.background_hover.overlay.opacity',
        'overlayBlendMode'       => 'design.background.overlay.effects.blend_mode',
        'overlayBlendModeHover'  => 'design.background_hover.overlay.effects.blend_mode',
        'overlayFilter'          => 'design.background.overlay.effects.filter',
        'overlayFilterHover'     => 'design.background_hover.overlay.effects.filter',

        // Background layers (for FancyBackground layer-based backgrounds)
        'backgroundLayers'       => 'design.background.layers',
        'backgroundLayerType'    => 'design.background.layers[].type',
        'backgroundLayerImage'   => 'design.background.layers[].image',
        'backgroundLayerGradient' => 'design.background.layers[].gradient',
        'backgroundLayerOverlayColor' => 'design.background.layers[].overlay_color',
        'backgroundLayerBlendMode' => 'design.background.layers[].blend_mode',
        'backgroundLayerSize'    => 'design.background.layers[].size',
        'backgroundLayerPosition' => 'design.background.layers[].position',
        'backgroundLayerRepeat'  => 'design.background.layers[].repeat',
        'backgroundLayerAttachment' => 'design.background.layers[].attachment',

        // Transition for background
        'backgroundTransitionDuration' => 'design.background.transition_duration',
    );

    /**
     * Border property mappings.
     *
     * Maps simplified border names to Breakdance property paths.
     * Supports per-side borders, individual corner radii, and border styling.
     *
     * @var array
     */
    private static array $border_mappings = array(
        // Uniform border radius (all corners)
        'borderRadius'           => 'design.borders.radius',
        'borderRadiusAll'        => 'design.borders.radius.all',
        'borderRadiusHover'      => 'design.borders.radius_hover',
        'borderRadiusAllHover'   => 'design.borders.radius_hover.all',

        // Individual corner radii (when radius type is not 'all')
        'radiusTopLeft'          => 'design.borders.radius.topLeft',
        'radiusTopRight'         => 'design.borders.radius.topRight',
        'radiusBottomLeft'       => 'design.borders.radius.bottomLeft',
        'radiusBottomRight'      => 'design.borders.radius.bottomRight',
        'radiusTopLeftHover'     => 'design.borders.radius_hover.topLeft',
        'radiusTopRightHover'    => 'design.borders.radius_hover.topRight',
        'radiusBottomLeftHover'  => 'design.borders.radius_hover.bottomLeft',
        'radiusBottomRightHover' => 'design.borders.radius_hover.bottomRight',

        // Uniform border (all sides)
        'border'                 => 'design.borders.border',
        'borderAll'              => 'design.borders.border.all',
        'borderAllWidth'         => 'design.borders.border.all.width',
        'borderAllStyle'         => 'design.borders.border.all.style',
        'borderAllColor'         => 'design.borders.border.all.color',
        'borderHover'            => 'design.borders.border_hover',
        'borderAllHover'         => 'design.borders.border_hover.all',
        'borderAllWidthHover'    => 'design.borders.border_hover.all.width',
        'borderAllStyleHover'    => 'design.borders.border_hover.all.style',
        'borderAllColorHover'    => 'design.borders.border_hover.all.color',

        // Top border (per-side)
        'borderTop'              => 'design.borders.border.top',
        'borderTopWidth'         => 'design.borders.border.top.width',
        'borderTopStyle'         => 'design.borders.border.top.style',
        'borderTopColor'         => 'design.borders.border.top.color',
        'borderTopHover'         => 'design.borders.border_hover.top',
        'borderTopWidthHover'    => 'design.borders.border_hover.top.width',
        'borderTopStyleHover'    => 'design.borders.border_hover.top.style',
        'borderTopColorHover'    => 'design.borders.border_hover.top.color',

        // Right border (per-side)
        'borderRight'            => 'design.borders.border.right',
        'borderRightWidth'       => 'design.borders.border.right.width',
        'borderRightStyle'       => 'design.borders.border.right.style',
        'borderRightColor'       => 'design.borders.border.right.color',
        'borderRightHover'       => 'design.borders.border_hover.right',
        'borderRightWidthHover'  => 'design.borders.border_hover.right.width',
        'borderRightStyleHover'  => 'design.borders.border_hover.right.style',
        'borderRightColorHover'  => 'design.borders.border_hover.right.color',

        // Bottom border (per-side)
        'borderBottom'           => 'design.borders.border.bottom',
        'borderBottomWidth'      => 'design.borders.border.bottom.width',
        'borderBottomStyle'      => 'design.borders.border.bottom.style',
        'borderBottomColor'      => 'design.borders.border.bottom.color',
        'borderBottomHover'      => 'design.borders.border_hover.bottom',
        'borderBottomWidthHover' => 'design.borders.border_hover.bottom.width',
        'borderBottomStyleHover' => 'design.borders.border_hover.bottom.style',
        'borderBottomColorHover' => 'design.borders.border_hover.bottom.color',

        // Left border (per-side)
        'borderLeft'             => 'design.borders.border.left',
        'borderLeftWidth'        => 'design.borders.border.left.width',
        'borderLeftStyle'        => 'design.borders.border.left.style',
        'borderLeftColor'        => 'design.borders.border.left.color',
        'borderLeftHover'        => 'design.borders.border_hover.left',
        'borderLeftWidthHover'   => 'design.borders.border_hover.left.width',
        'borderLeftStyleHover'   => 'design.borders.border_hover.left.style',
        'borderLeftColorHover'   => 'design.borders.border_hover.left.color',
    );

    /**
     * Responsive properties list.
     *
     * Properties that support breakpoint-specific values.
     *
     * @var array
     */
    private static array $responsive_properties = array(
        // Effects - responsive
        'opacity',
        'opacityHover',
        'boxShadow',
        'boxShadowHover',
        'mixBlendMode',
        'mixBlendModeHover',

        // Transform section - responsive
        'transformOrigin',
        'transformOriginHover',
        'transformOriginPosition',
        'transformOriginPositionHover',
        'transformOriginX',
        'transformOriginY',
        'transformOriginXHover',
        'transformOriginYHover',
        'transformPerspective',
        'transformPerspectiveHover',
        'transformPerspectiveOrigin',
        'transformPerspectiveOriginHover',
        'transformStyle',
        'transformStyleHover',
        'perspectiveOriginX',
        'perspectiveOriginY',
        'perspectiveOriginXHover',
        'perspectiveOriginYHover',

        // Individual transforms - responsive (values can change per breakpoint)
        'rotate',
        'rotateX',
        'rotateY',
        'rotateZ',
        'rotateHover',
        'rotateXHover',
        'rotateYHover',
        'rotateZHover',
        'rotate3d',
        'rotate3dX',
        'rotate3dY',
        'rotate3dZ',
        'rotate3dAngle',
        'rotate3dHover',
        'rotate3dXHover',
        'rotate3dYHover',
        'rotate3dZHover',
        'rotate3dAngleHover',
        'scale',
        'scaleX',
        'scaleY',
        'scaleZ',
        'scaleHover',
        'scaleXHover',
        'scaleYHover',
        'scaleZHover',
        'scale3d',
        'scale3dHover',
        'skew',
        'skewX',
        'skewY',
        'skewHover',
        'skewXHover',
        'skewYHover',
        'translate',
        'translateX',
        'translateY',
        'translateZ',
        'translateHover',
        'translateXHover',
        'translateYHover',
        'translateZHover',
        'perspective',
        'perspectiveValue',
        'perspectiveHover',
        'perspectiveValueHover',

        // Filter - responsive
        'filter',
        'filterHover',
        'filterBlur',
        'filterBlurAmount',
        'filterBrightness',
        'filterBrightnessAmount',
        'filterContrast',
        'filterContrastAmount',
        'filterGrayscale',
        'filterGrayscaleAmount',
        'filterHueRotate',
        'filterHueRotateAmount',
        'filterInvert',
        'filterInvertAmount',
        'filterSaturate',
        'filterSaturateAmount',
        'filterSepia',
        'filterSepiaAmount',

        // Transition - responsive
        'transitionDuration',
        'transitionTiming',
        'transitionProperty',
        'transitionDelay',

        // Background - responsive
        'background',
        'backgroundHover',
        'backgroundColor',
        'backgroundColorHover',
        'backgroundPosition',
        'backgroundPositionHover',
        'backgroundSize',
        'backgroundSizeHover',
        'gradient',
        'gradientHover',
        'backgroundBlendMode',
        'backgroundBlendModeHover',
        'blendMode',
        'blendModeHover',
        'overlay',
        'overlayOpacity',
        'overlayOpacityHover',
        'overlayBlendMode',
        'overlayBlendModeHover',

        // Border radius - responsive
        'borderRadius',
        'borderRadiusAll',
        'borderRadiusHover',
        'borderRadiusAllHover',
        'radiusTopLeft',
        'radiusTopRight',
        'radiusBottomLeft',
        'radiusBottomRight',
        'radiusTopLeftHover',
        'radiusTopRightHover',
        'radiusBottomLeftHover',
        'radiusBottomRightHover',

        // Border - responsive
        'border',
        'borderHover',
        'borderAll',
        'borderAllHover',
        'borderTop',
        'borderTopHover',
        'borderRight',
        'borderRightHover',
        'borderBottom',
        'borderBottomHover',
        'borderLeft',
        'borderLeftHover',
    );

    /**
     * Unit-based properties with their default units.
     *
     * Properties that require unit objects (number + unit).
     *
     * @var array
     */
    private static array $unit_properties = array(
        // Transition units
        'transitionDuration'     => 'ms',
        'transitionDelay'        => 'ms',

        // Transform units
        'transformPerspective'   => 'px',
        'transformPerspectiveHover' => 'px',

        // Rotate units (degrees)
        'rotateX'                => 'deg',
        'rotateY'                => 'deg',
        'rotateZ'                => 'deg',
        'rotateXHover'           => 'deg',
        'rotateYHover'           => 'deg',
        'rotateZHover'           => 'deg',

        // Rotate3D angle (degrees)
        'rotate3dAngle'          => 'deg',
        'rotate3dAngleHover'     => 'deg',

        // Skew units (degrees)
        'skewX'                  => 'deg',
        'skewY'                  => 'deg',
        'skewXHover'             => 'deg',
        'skewYHover'             => 'deg',

        // Translate units (pixels)
        'translateX'             => 'px',
        'translateY'             => 'px',
        'translateZ'             => 'px',
        'translateXHover'        => 'px',
        'translateYHover'        => 'px',
        'translateZHover'        => 'px',

        // Perspective value (pixels)
        'perspectiveValue'       => 'px',
        'perspectiveValueHover'  => 'px',

        // Filter units
        'filterBlurAmount'       => 'px',
        'filterBrightnessAmount' => '%',
        'filterContrastAmount'   => '%',
        'filterGrayscaleAmount'  => '%',
        'filterHueRotateAmount'  => 'deg',
        'filterInvertAmount'     => '%',
        'filterSaturateAmount'   => '%',
        'filterSepiaAmount'      => '%',

        // Background/Gradient units
        'gradientAngle'          => 'deg',
        'gradientAngleHover'     => 'deg',
        'backgroundTransitionDuration' => 'ms',
        'gradientAnimationDuration' => 's',

        // Border radius units
        'borderRadiusAll'        => 'px',
        'borderRadiusAllHover'   => 'px',
        'radiusTopLeft'          => 'px',
        'radiusTopRight'         => 'px',
        'radiusBottomLeft'       => 'px',
        'radiusBottomRight'      => 'px',
        'radiusTopLeftHover'     => 'px',
        'radiusTopRightHover'    => 'px',
        'radiusBottomLeftHover'  => 'px',
        'radiusBottomRightHover' => 'px',

        // Border width units (all sides)
        'borderAllWidth'         => 'px',
        'borderAllWidthHover'    => 'px',

        // Border width units (per-side)
        'borderTopWidth'         => 'px',
        'borderTopWidthHover'    => 'px',
        'borderRightWidth'       => 'px',
        'borderRightWidthHover'  => 'px',
        'borderBottomWidth'      => 'px',
        'borderBottomWidthHover' => 'px',
        'borderLeftWidth'        => 'px',
        'borderLeftWidthHover'   => 'px',
    );

    /**
     * Numeric (non-unit) properties with their valid ranges.
     *
     * @var array
     */
    private static array $numeric_properties = array(
        // Opacity
        'opacity'      => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'opacityHover' => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),

        // Scale (uniform and per-axis)
        'scale'        => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleX'       => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleY'       => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleZ'       => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleHover'   => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleXHover'  => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleYHover'  => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),
        'scaleZHover'  => array( 'min' => 0, 'max' => 4, 'step' => 0.1 ),

        // Rotate3D axis vector components (normalized 0-1)
        'rotate3dX'      => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'rotate3dY'      => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'rotate3dZ'      => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'rotate3dXHover' => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'rotate3dYHover' => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'rotate3dZHover' => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),

        // Transform origin position (percentage 0-100)
        'transformOriginX'      => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
        'transformOriginY'      => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
        'transformOriginXHover' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
        'transformOriginYHover' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),

        // Perspective origin position (percentage 0-100)
        'perspectiveOriginX'      => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
        'perspectiveOriginY'      => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
        'perspectiveOriginXHover' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),
        'perspectiveOriginYHover' => array( 'min' => 0, 'max' => 100, 'step' => 1 ),

        // Overlay opacity (0-1)
        'overlayOpacity'          => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
        'overlayOpacityHover'     => array( 'min' => 0, 'max' => 1, 'step' => 0.1 ),
    );

    /**
     * Dropdown/enum properties with their valid values.
     *
     * @var array
     */
    private static array $enum_properties = array(
        'mixBlendMode'      => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'mixBlendModeHover' => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'transitionTiming'  => array(
            'ease-in-out',
            'ease-in',
            'ease-out',
            'ease',
            'linear',
        ),
        'transitionProperty' => array(
            'all',
            'custom',
        ),
        'transformOrigin'   => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),
        'transformOriginHover' => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),
        'transformStyle'    => array(
            'flat',
            'preserve-3d',
        ),
        'transformStyleHover' => array(
            'flat',
            'preserve-3d',
        ),

        // Background blend modes (CSS blend-mode values)
        'backgroundBlendMode' => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'backgroundBlendModeHover' => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'blendMode'         => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'blendModeHover'    => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'overlayBlendMode'  => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),
        'overlayBlendModeHover' => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),

        // Gradient types
        'gradientType'      => array(
            'linear',
            'radial',
            'conic',
        ),
        'gradientTypeHover' => array(
            'linear',
            'radial',
            'conic',
        ),

        // Radial gradient shapes
        'gradientRadialShape' => array(
            'ellipse',
            'circle',
        ),
        'gradientRadialShapeHover' => array(
            'ellipse',
            'circle',
        ),

        // Radial gradient size keywords
        'gradientRadialSize' => array(
            'closest-side',
            'closest-corner',
            'farthest-side',
            'farthest-corner',
        ),
        'gradientRadialSizeHover' => array(
            'closest-side',
            'closest-corner',
            'farthest-side',
            'farthest-corner',
        ),

        // Background type
        'backgroundType'    => array(
            'color',
            'image',
            'gradient',
            'none',
        ),
        'backgroundTypeHover' => array(
            'color',
            'image',
            'gradient',
            'none',
        ),

        // Background layer type
        'backgroundLayerType' => array(
            'image',
            'gradient',
            'overlay_color',
            'none',
        ),

        // Background size
        'backgroundSize'    => array(
            'auto',
            'cover',
            'contain',
            'custom',
        ),
        'backgroundSizeHover' => array(
            'auto',
            'cover',
            'contain',
            'custom',
        ),
        'backgroundLayerSize' => array(
            'auto',
            'cover',
            'contain',
            'custom',
        ),

        // Background position
        'backgroundPosition' => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),
        'backgroundPositionHover' => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),
        'backgroundLayerPosition' => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),

        // Radial gradient position
        'gradientRadialPosition' => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),
        'gradientRadialPositionHover' => array(
            'top left',
            'top center',
            'top right',
            'center left',
            'center',
            'center right',
            'bottom left',
            'bottom center',
            'bottom right',
            'custom',
        ),

        // Background repeat
        'backgroundRepeat'  => array(
            'repeat',
            'repeat-x',
            'repeat-y',
            'no-repeat',
            'space',
            'round',
        ),
        'backgroundRepeatHover' => array(
            'repeat',
            'repeat-x',
            'repeat-y',
            'no-repeat',
            'space',
            'round',
        ),
        'backgroundLayerRepeat' => array(
            'repeat',
            'repeat-x',
            'repeat-y',
            'no-repeat',
            'space',
            'round',
        ),

        // Background attachment
        'backgroundAttachment' => array(
            'scroll',
            'fixed',
            'local',
        ),
        'backgroundAttachmentHover' => array(
            'scroll',
            'fixed',
            'local',
        ),
        'backgroundLayerAttachment' => array(
            'scroll',
            'fixed',
            'local',
        ),

        // Background layer blend mode
        'backgroundLayerBlendMode' => array(
            'normal',
            'multiply',
            'screen',
            'overlay',
            'darken',
            'lighten',
            'color-dodge',
            'color-burn',
            'hard-light',
            'soft-light',
            'difference',
            'exclusion',
            'hue',
            'saturation',
            'color',
            'luminosity',
        ),

        // Border styles (CSS border-style values)
        'borderAllStyle'         => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderAllStyleHover'    => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderTopStyle'         => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderTopStyleHover'    => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderRightStyle'       => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderRightStyleHover'  => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderBottomStyle'      => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderBottomStyleHover' => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderLeftStyle'        => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
        'borderLeftStyleHover'   => array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        ),
    );

    /**
     * Filter type to property mapping.
     *
     * Maps filter types to their value property.
     *
     * @var array
     */
    private static array $filter_type_mappings = array(
        'blur'       => 'blur_amount',
        'brightness' => 'amount',
        'contrast'   => 'amount',
        'grayscale'  => 'amount',
        'hue-rotate' => 'rotate',
        'invert'     => 'amount',
        'saturate'   => 'amount',
        'sepia'      => 'amount',
    );

    /**
     * Transform type to properties mapping.
     *
     * Maps transform types to their sub-properties.
     *
     * @var array
     */
    private static array $transform_type_mappings = array(
        'perspective' => array( 'perspective' ),
        'rotate'      => array( 'rotate_x', 'rotate_y', 'rotate_z' ),
        'rotate3d'    => array( 'x', 'y', 'z', 'angle' ),
        'scale'       => array( 'scale' ),
        'scale3d'     => array( 'scale_x', 'scale_y', 'scale_z' ),
        'skew'        => array( 'skew_x', 'skew_y' ),
        'translate'   => array( 'translate_x', 'translate_y', 'translate_z' ),
    );

    /**
     * Get the Breakdance property path for a simplified property name.
     *
     * @since 1.0.0
     *
     * @param string $property The simplified property name.
     * @return string|null The Breakdance path or null if not found.
     */
    public function get_property_path( string $property ): ?string {
        // Check effects mappings first.
        if ( isset( self::$effects_mappings[ $property ] ) ) {
            return self::$effects_mappings[ $property ];
        }

        // Check background mappings.
        if ( isset( self::$background_mappings[ $property ] ) ) {
            return self::$background_mappings[ $property ];
        }

        // Check border mappings.
        if ( isset( self::$border_mappings[ $property ] ) ) {
            return self::$border_mappings[ $property ];
        }

        return null;
    }

    /**
     * Get all effects property mappings.
     *
     * @since 1.0.0
     *
     * @return array The effects property mappings.
     */
    public function get_effects_mappings(): array {
        return self::$effects_mappings;
    }

    /**
     * Get all background property mappings.
     *
     * @since 1.0.0
     *
     * @return array The background property mappings.
     */
    public function get_background_mappings(): array {
        return self::$background_mappings;
    }

    /**
     * Check if a property is a background property.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if background property, false otherwise.
     */
    public function is_background_property( string $property ): bool {
        return isset( self::$background_mappings[ $property ] );
    }

    /**
     * Get all border property mappings.
     *
     * @since 1.0.0
     *
     * @return array The border property mappings.
     */
    public function get_border_mappings(): array {
        return self::$border_mappings;
    }

    /**
     * Check if a property is a border property.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if border property, false otherwise.
     */
    public function is_border_property( string $property ): bool {
        return isset( self::$border_mappings[ $property ] );
    }

    /**
     * Check if a property is responsive.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if responsive, false otherwise.
     */
    public function is_responsive( string $property ): bool {
        return in_array( $property, self::$responsive_properties, true );
    }

    /**
     * Get list of all responsive properties.
     *
     * @since 1.0.0
     *
     * @return array The responsive properties.
     */
    public function get_responsive_properties(): array {
        return self::$responsive_properties;
    }

    /**
     * Check if a property requires a unit.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if unit-based, false otherwise.
     */
    public function is_unit_property( string $property ): bool {
        return isset( self::$unit_properties[ $property ] );
    }

    /**
     * Get the default unit for a property.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return string|null The default unit or null if not unit-based.
     */
    public function get_default_unit( string $property ): ?string {
        return self::$unit_properties[ $property ] ?? null;
    }

    /**
     * Get all unit properties with their defaults.
     *
     * @since 1.0.0
     *
     * @return array The unit properties.
     */
    public function get_unit_properties(): array {
        return self::$unit_properties;
    }

    /**
     * Check if a property is numeric (non-unit).
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if numeric, false otherwise.
     */
    public function is_numeric_property( string $property ): bool {
        return isset( self::$numeric_properties[ $property ] );
    }

    /**
     * Get the valid range for a numeric property.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return array|null The range (min, max, step) or null if not numeric.
     */
    public function get_numeric_range( string $property ): ?array {
        return self::$numeric_properties[ $property ] ?? null;
    }

    /**
     * Get all numeric properties with their ranges.
     *
     * @since 1.0.0
     *
     * @return array The numeric properties.
     */
    public function get_numeric_properties(): array {
        return self::$numeric_properties;
    }

    /**
     * Check if a property has enumerated values.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if enum, false otherwise.
     */
    public function is_enum_property( string $property ): bool {
        return isset( self::$enum_properties[ $property ] );
    }

    /**
     * Get valid values for an enum property.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return array|null The valid values or null if not enum.
     */
    public function get_enum_values( string $property ): ?array {
        return self::$enum_properties[ $property ] ?? null;
    }

    /**
     * Get all enum properties with their values.
     *
     * @since 1.0.0
     *
     * @return array The enum properties.
     */
    public function get_enum_properties(): array {
        return self::$enum_properties;
    }

    /**
     * Get filter type mappings.
     *
     * @since 1.0.0
     *
     * @return array The filter type mappings.
     */
    public function get_filter_type_mappings(): array {
        return self::$filter_type_mappings;
    }

    /**
     * Get transform type mappings.
     *
     * @since 1.0.0
     *
     * @return array The transform type mappings.
     */
    public function get_transform_type_mappings(): array {
        return self::$transform_type_mappings;
    }

    /**
     * Check if a property is an effects property.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return bool True if effects property, false otherwise.
     */
    public function is_effects_property( string $property ): bool {
        return isset( self::$effects_mappings[ $property ] );
    }

    /**
     * Get all property mappings (all categories).
     *
     * @since 1.0.0
     *
     * @return array All property mappings.
     */
    public function get_all_mappings(): array {
        return array_merge(
            self::$effects_mappings,
            self::$background_mappings,
            self::$border_mappings
        );
    }

    /**
     * Get properties grouped by category.
     *
     * @since 1.0.0
     *
     * @return array Properties grouped by category.
     */
    public function get_properties_by_category(): array {
        return array(
            'effects' => array(
                'opacity'     => array_filter(
                    self::$effects_mappings,
                    fn( $key ) => strpos( $key, 'opacity' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'boxShadow'   => array_filter(
                    self::$effects_mappings,
                    fn( $key ) => strpos( $key, 'boxShadow' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'mixBlendMode' => array_filter(
                    self::$effects_mappings,
                    fn( $key ) => strpos( $key, 'mixBlendMode' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'transition'  => array_filter(
                    self::$effects_mappings,
                    fn( $key ) => strpos( $key, 'transition' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'transform'   => array_filter(
                    self::$effects_mappings,
                    fn( $key ) => strpos( $key, 'transform' ) === 0 ||
                                   strpos( $key, 'rotate' ) === 0 ||
                                   strpos( $key, 'scale' ) === 0 ||
                                   strpos( $key, 'skew' ) === 0 ||
                                   strpos( $key, 'translate' ) === 0 ||
                                   $key === 'perspective',
                    ARRAY_FILTER_USE_KEY
                ),
                'filter'      => array_filter(
                    self::$effects_mappings,
                    fn( $key ) => strpos( $key, 'filter' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
            ),
            'background' => array(
                'basic'       => array_filter(
                    self::$background_mappings,
                    fn( $key ) => strpos( $key, 'background' ) === 0 &&
                                   strpos( $key, 'Layer' ) === false &&
                                   strpos( $key, 'Blend' ) === false &&
                                   strpos( $key, 'Transition' ) === false,
                    ARRAY_FILTER_USE_KEY
                ),
                'gradient'    => array_filter(
                    self::$background_mappings,
                    fn( $key ) => strpos( $key, 'gradient' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'blendMode'   => array_filter(
                    self::$background_mappings,
                    fn( $key ) => strpos( $key, 'blendMode' ) === 0 ||
                                   strpos( $key, 'BlendMode' ) !== false,
                    ARRAY_FILTER_USE_KEY
                ),
                'overlay'     => array_filter(
                    self::$background_mappings,
                    fn( $key ) => strpos( $key, 'overlay' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'layers'      => array_filter(
                    self::$background_mappings,
                    fn( $key ) => strpos( $key, 'backgroundLayer' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
            ),
            'border' => array(
                'radius'      => array_filter(
                    self::$border_mappings,
                    fn( $key ) => strpos( $key, 'radius' ) === 0 ||
                                   strpos( $key, 'borderRadius' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'borderAll'   => array_filter(
                    self::$border_mappings,
                    fn( $key ) => strpos( $key, 'borderAll' ) === 0 ||
                                   $key === 'border' ||
                                   $key === 'borderHover',
                    ARRAY_FILTER_USE_KEY
                ),
                'borderTop'   => array_filter(
                    self::$border_mappings,
                    fn( $key ) => strpos( $key, 'borderTop' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'borderRight' => array_filter(
                    self::$border_mappings,
                    fn( $key ) => strpos( $key, 'borderRight' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'borderBottom' => array_filter(
                    self::$border_mappings,
                    fn( $key ) => strpos( $key, 'borderBottom' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
                'borderLeft'  => array_filter(
                    self::$border_mappings,
                    fn( $key ) => strpos( $key, 'borderLeft' ) === 0,
                    ARRAY_FILTER_USE_KEY
                ),
            ),
        );
    }

    /**
     * Convert a value to a unit object.
     *
     * Creates a Breakdance-compatible unit object from a value and unit.
     *
     * @since 1.0.0
     *
     * @param mixed  $value The numeric value.
     * @param string $unit  The unit type.
     * @return array The unit object.
     */
    public function to_unit_object( $value, string $unit ): array {
        $number = is_numeric( $value ) ? floatval( $value ) : 0;

        return array(
            'number' => $number,
            'unit'   => $unit,
            'style'  => $number . $unit,
        );
    }

    /**
     * Parse a CSS value string into a unit object.
     *
     * Extracts the numeric value and unit from a CSS string like "10px" or "45deg".
     *
     * @since 1.0.0
     *
     * @param string $value The CSS value string.
     * @return array The unit object with number, unit, and style.
     */
    public function parse_css_value( string $value ): array {
        $value = trim( $value );

        // Check for CSS variables.
        if ( strpos( $value, 'var(' ) === 0 ) {
            return array(
                'number' => null,
                'unit'   => 'custom',
                'style'  => $value,
            );
        }

        // Parse numeric value with unit.
        preg_match( '/^(-?[\d.]+)\s*([a-z%]+)?$/i', $value, $matches );

        if ( ! empty( $matches ) ) {
            $number = floatval( $matches[1] );
            $unit   = isset( $matches[2] ) ? strtolower( $matches[2] ) : '';

            return array(
                'number' => $number,
                'unit'   => $unit,
                'style'  => $value,
            );
        }

        // Return as-is for non-parseable values.
        return array(
            'number' => null,
            'unit'   => 'custom',
            'style'  => $value,
        );
    }

    /**
     * Build a box shadow value object.
     *
     * Creates a Breakdance-compatible box shadow object.
     *
     * @since 1.0.0
     *
     * @param string $css_value The CSS box-shadow value.
     * @return array The box shadow object.
     */
    public function build_box_shadow( string $css_value ): array {
        return array(
            'style' => $css_value,
        );
    }

    /**
     * Build a filter item for the filter repeater.
     *
     * @since 1.0.0
     *
     * @param string $type  The filter type (blur, brightness, etc.).
     * @param mixed  $value The filter value.
     * @return array|null The filter item or null if invalid type.
     */
    public function build_filter_item( string $type, $value ): ?array {
        if ( ! isset( self::$filter_type_mappings[ $type ] ) ) {
            return null;
        }

        $item = array(
            'type' => $type,
        );

        $value_property = self::$filter_type_mappings[ $type ];
        $parsed_value   = $this->parse_css_value( (string) $value );

        $item[ $value_property ] = $parsed_value;

        return $item;
    }

    /**
     * Build a transform item for the transforms repeater.
     *
     * @since 1.0.0
     *
     * @param string $type   The transform type.
     * @param array  $values The transform values.
     * @return array|null The transform item or null if invalid type.
     */
    public function build_transform_item( string $type, array $values ): ?array {
        if ( ! isset( self::$transform_type_mappings[ $type ] ) ) {
            return null;
        }

        $item = array(
            'type' => $type,
        );

        $properties = self::$transform_type_mappings[ $type ];

        foreach ( $properties as $prop ) {
            if ( isset( $values[ $prop ] ) ) {
                $item[ $prop ] = $this->parse_css_value( (string) $values[ $prop ] );
            }
        }

        return $item;
    }

    /**
     * Build a transition item for the transition repeater.
     *
     * @since 1.0.0
     *
     * @param array $values The transition values.
     * @return array The transition item.
     */
    public function build_transition_item( array $values ): array {
        $item = array();

        if ( isset( $values['duration'] ) ) {
            $item['duration'] = $this->parse_css_value( (string) $values['duration'] );
        }

        if ( isset( $values['timing_function'] ) ) {
            $item['timing_function'] = $values['timing_function'];
        }

        if ( isset( $values['property'] ) ) {
            $item['property'] = $values['property'];
        }

        if ( isset( $values['custom_property'] ) ) {
            $item['custom_property'] = $values['custom_property'];
        }

        if ( isset( $values['delay'] ) ) {
            $item['delay'] = $this->parse_css_value( (string) $values['delay'] );
        }

        return $item;
    }

    /**
     * Get property metadata including type, responsive status, and validation rules.
     *
     * @since 1.0.0
     *
     * @param string $property The property name.
     * @return array The property metadata.
     */
    public function get_property_metadata( string $property ): array {
        $metadata = array(
            'name'       => $property,
            'path'       => $this->get_property_path( $property ),
            'responsive' => $this->is_responsive( $property ),
            'type'       => 'unknown',
        );

        if ( $this->is_unit_property( $property ) ) {
            $metadata['type']         = 'unit';
            $metadata['default_unit'] = $this->get_default_unit( $property );
        } elseif ( $this->is_numeric_property( $property ) ) {
            $metadata['type']  = 'number';
            $metadata['range'] = $this->get_numeric_range( $property );
        } elseif ( $this->is_enum_property( $property ) ) {
            $metadata['type']   = 'enum';
            $metadata['values'] = $this->get_enum_values( $property );
        } elseif ( strpos( $property, 'boxShadow' ) === 0 ) {
            $metadata['type'] = 'shadow';
        } elseif ( strpos( $property, 'filter' ) === 0 && strpos( $property, 'Amount' ) === false ) {
            $metadata['type'] = 'repeater';
        } elseif ( strpos( $property, 'transition' ) === 0 && $property === 'transition' ) {
            $metadata['type'] = 'repeater';
        } elseif ( strpos( $property, 'gradient' ) === 0 ) {
            // Gradient properties
            if ( $property === 'gradient' || $property === 'gradientHover' ) {
                $metadata['type'] = 'gradient';
            } elseif ( strpos( $property, 'Style' ) !== false || strpos( $property, 'Value' ) !== false ) {
                $metadata['type'] = 'css_value';
            } elseif ( strpos( $property, 'Colors' ) !== false || strpos( $property, 'Stops' ) !== false ) {
                $metadata['type'] = 'repeater';
            }
        } elseif ( strpos( $property, 'overlay' ) === 0 ) {
            // Overlay properties
            if ( $property === 'overlay' ) {
                $metadata['type'] = 'object';
            } elseif ( strpos( $property, 'Color' ) !== false ) {
                $metadata['type'] = 'color';
            } elseif ( strpos( $property, 'Gradient' ) !== false ) {
                $metadata['type'] = 'gradient';
            } elseif ( strpos( $property, 'Filter' ) !== false ) {
                $metadata['type'] = 'repeater';
            }
        } elseif ( strpos( $property, 'backgroundLayer' ) === 0 ) {
            // Background layer properties (repeater-based)
            $metadata['type'] = 'repeater_item';
        } elseif ( strpos( $property, 'backgroundColor' ) === 0 ) {
            $metadata['type'] = 'color';
        } elseif ( strpos( $property, 'borderRadius' ) === 0 || strpos( $property, 'radius' ) === 0 ) {
            // Border radius properties
            if ( $property === 'borderRadius' || $property === 'borderRadiusHover' ) {
                $metadata['type'] = 'radius_object';
            } else {
                $metadata['type'] = 'unit';
                $metadata['default_unit'] = 'px';
            }
        } elseif ( strpos( $property, 'border' ) === 0 ) {
            // Border properties
            if ( $property === 'border' || $property === 'borderHover' ) {
                $metadata['type'] = 'border_complex';
            } elseif ( strpos( $property, 'Color' ) !== false ) {
                $metadata['type'] = 'color';
            } elseif ( strpos( $property, 'Style' ) !== false ) {
                $metadata['type'] = 'enum';
                $metadata['values'] = $this->get_enum_values( $property );
            } elseif ( strpos( $property, 'Width' ) !== false ) {
                $metadata['type'] = 'unit';
                $metadata['default_unit'] = 'px';
            } else {
                // Per-side border objects (borderTop, borderRight, etc.)
                $metadata['type'] = 'border_side';
            }
        }

        return $metadata;
    }

    /**
     * Build a gradient value object.
     *
     * Creates a Breakdance-compatible gradient object from CSS gradient string.
     *
     * @since 1.0.0
     *
     * @param string $css_value The CSS gradient value.
     * @return array The gradient object.
     */
    public function build_gradient( string $css_value ): array {
        return array(
            'style' => $css_value,
            'value' => $css_value,
        );
    }

    /**
     * Build an overlay object.
     *
     * Creates a Breakdance-compatible overlay object.
     *
     * @since 1.0.0
     *
     * @param array $values The overlay values.
     * @return array The overlay object.
     */
    public function build_overlay( array $values ): array {
        $overlay = array();

        if ( isset( $values['color'] ) ) {
            $overlay['color'] = $values['color'];
        }

        if ( isset( $values['image'] ) ) {
            $overlay['image'] = $values['image'];
        }

        if ( isset( $values['gradient'] ) ) {
            $overlay['gradient'] = is_string( $values['gradient'] )
                ? $this->build_gradient( $values['gradient'] )
                : $values['gradient'];
        }

        if ( isset( $values['opacity'] ) ) {
            $overlay['opacity'] = floatval( $values['opacity'] );
        }

        if ( isset( $values['blend_mode'] ) ) {
            $overlay['effects']['blend_mode'] = $values['blend_mode'];
        }

        return $overlay;
    }

    /**
     * Build a background layer object.
     *
     * Creates a Breakdance-compatible background layer for the layers repeater.
     *
     * @since 1.0.0
     *
     * @param string $type   The layer type (image, gradient, overlay_color, none).
     * @param array  $values The layer values.
     * @return array The background layer object.
     */
    public function build_background_layer( string $type, array $values ): array {
        $layer = array(
            'type' => $type,
        );

        if ( $type === 'image' && isset( $values['image'] ) ) {
            $layer['image'] = $values['image'];
        }

        if ( $type === 'gradient' && isset( $values['gradient'] ) ) {
            $layer['gradient'] = is_string( $values['gradient'] )
                ? $this->build_gradient( $values['gradient'] )
                : $values['gradient'];
        }

        if ( $type === 'overlay_color' && isset( $values['overlay_color'] ) ) {
            $layer['overlay_color'] = $values['overlay_color'];
        }

        if ( isset( $values['blend_mode'] ) ) {
            $layer['blend_mode'] = $values['blend_mode'];
        }

        if ( isset( $values['size'] ) ) {
            $layer['size'] = $values['size'];
        }

        if ( isset( $values['position'] ) ) {
            $layer['position'] = $values['position'];
        }

        if ( isset( $values['repeat'] ) ) {
            $layer['repeat'] = $values['repeat'];
        }

        if ( isset( $values['attachment'] ) ) {
            $layer['attachment'] = $values['attachment'];
        }

        return $layer;
    }

    /**
     * Build a border radius object.
     *
     * Creates a Breakdance-compatible border radius object.
     * Supports both uniform radius (all corners) and per-corner radius.
     *
     * @since 1.0.0
     *
     * @param array|string $values The radius values - either a single value for all corners
     *                              or an array with topLeft, topRight, bottomLeft, bottomRight.
     * @return array The border radius object.
     */
    public function build_border_radius( $values ): array {
        // If single value, apply to all corners.
        if ( is_string( $values ) || is_numeric( $values ) ) {
            return array(
                'all' => $this->parse_css_value( (string) $values ),
            );
        }

        $radius = array();

        // Per-corner values.
        if ( isset( $values['all'] ) ) {
            $radius['all'] = $this->parse_css_value( (string) $values['all'] );
        }

        if ( isset( $values['topLeft'] ) ) {
            $radius['topLeft'] = $this->parse_css_value( (string) $values['topLeft'] );
        }

        if ( isset( $values['topRight'] ) ) {
            $radius['topRight'] = $this->parse_css_value( (string) $values['topRight'] );
        }

        if ( isset( $values['bottomLeft'] ) ) {
            $radius['bottomLeft'] = $this->parse_css_value( (string) $values['bottomLeft'] );
        }

        if ( isset( $values['bottomRight'] ) ) {
            $radius['bottomRight'] = $this->parse_css_value( (string) $values['bottomRight'] );
        }

        return $radius;
    }

    /**
     * Build a border side object.
     *
     * Creates a Breakdance-compatible border side object with width, style, and color.
     *
     * @since 1.0.0
     *
     * @param array $values The border side values (width, style, color).
     * @return array The border side object.
     */
    public function build_border_side( array $values ): array {
        $side = array();

        if ( isset( $values['width'] ) ) {
            $side['width'] = $this->parse_css_value( (string) $values['width'] );
        }

        if ( isset( $values['style'] ) ) {
            $side['style'] = $values['style'];
        }

        if ( isset( $values['color'] ) ) {
            $side['color'] = $values['color'];
        }

        return $side;
    }

    /**
     * Build a complete border object.
     *
     * Creates a Breakdance-compatible border object with all four sides.
     * Supports both uniform borders (all sides same) and per-side borders.
     *
     * @since 1.0.0
     *
     * @param array $values The border values. Can include:
     *                      - 'all': Uniform border for all sides
     *                      - 'top', 'right', 'bottom', 'left': Per-side borders
     *                      Each side can have 'width', 'style', 'color'.
     * @return array The border object.
     */
    public function build_border( array $values ): array {
        $border = array();

        // Uniform border for all sides.
        if ( isset( $values['all'] ) ) {
            $border['all'] = is_array( $values['all'] )
                ? $this->build_border_side( $values['all'] )
                : $this->build_border_side( array( 'width' => $values['all'] ) );
        }

        // Per-side borders.
        $sides = array( 'top', 'right', 'bottom', 'left' );
        foreach ( $sides as $side ) {
            if ( isset( $values[ $side ] ) ) {
                $border[ $side ] = is_array( $values[ $side ] )
                    ? $this->build_border_side( $values[ $side ] )
                    : $this->build_border_side( array( 'width' => $values[ $side ] ) );
            }
        }

        return $border;
    }

    /**
     * Parse a CSS border shorthand value.
     *
     * Parses CSS border shorthand like "1px solid #000" into components.
     *
     * @since 1.0.0
     *
     * @param string $css_value The CSS border shorthand value.
     * @return array The parsed border side object.
     */
    public function parse_border_shorthand( string $css_value ): array {
        $css_value = trim( $css_value );

        // Default values.
        $side = array(
            'width' => array( 'number' => 0, 'unit' => 'px', 'style' => '0px' ),
            'style' => 'none',
            'color' => '',
        );

        // Check for 'none' value.
        if ( strtolower( $css_value ) === 'none' || empty( $css_value ) ) {
            return $side;
        }

        // Valid border styles.
        $valid_styles = array(
            'none',
            'solid',
            'dashed',
            'dotted',
            'double',
            'groove',
            'ridge',
            'inset',
            'outset',
        );

        // Split by whitespace.
        $parts = preg_split( '/\s+/', $css_value );

        foreach ( $parts as $part ) {
            $part = trim( $part );

            // Check if it's a border style.
            if ( in_array( strtolower( $part ), $valid_styles, true ) ) {
                $side['style'] = strtolower( $part );
                continue;
            }

            // Check if it's a width value (has a unit or is a number).
            if ( preg_match( '/^-?[\d.]+[a-z%]*$/i', $part ) ) {
                $side['width'] = $this->parse_css_value( $part );
                continue;
            }

            // Otherwise assume it's a color.
            $side['color'] = $part;
        }

        return $side;
    }

    // =========================================================================
    // ELEMENT MAPPING AND SCHEMA METHODS
    // =========================================================================

    /**
     * Element type mapping from simplified names to Breakdance class names.
     *
     * @var array
     */
    private static array $element_type_map = array(
        'Section'   => 'EssentialElements\\Section',
        'Div'       => 'EssentialElements\\Div',
        'Heading'   => 'EssentialElements\\Heading',
        'Text'      => 'EssentialElements\\Text',
        'Button'    => 'EssentialElements\\ButtonV2',
        'Image'     => 'EssentialElements\\Image2',
        'Icon'      => 'EssentialElements\\Icon',
        'Columns'   => 'EssentialElements\\Columns',
        'Column'    => 'EssentialElements\\Column',
        'Container' => 'EssentialElements\\Container',
        'Spacer'    => 'EssentialElements\\Spacer',
        'Divider'   => 'EssentialElements\\Divider',
        'Video'     => 'EssentialElements\\Video',
        'HtmlCode'  => 'OxygenElements\\HtmlCode',
        'CssCode'   => 'OxygenElements\\CssCode',
        'PhpCode'   => 'OxygenElements\\PhpCode',
    );

    /**
     * Simplified properties schema for each element type.
     *
     * @var array
     */
    private static array $element_schemas = array(
        'Section' => array(
            'properties' => array(
                'background' => array( 'type' => 'color', 'description' => 'Background color' ),
                'padding'    => array( 'type' => 'spacing', 'description' => 'Padding (shorthand)' ),
                'display'    => array( 'type' => 'enum', 'values' => array( 'flex', 'grid', 'block' ) ),
                'flexDirection' => array( 'type' => 'enum', 'values' => array( 'row', 'column' ) ),
                'alignItems' => array( 'type' => 'enum', 'values' => array( 'flex-start', 'center', 'flex-end', 'stretch' ) ),
                'justifyContent' => array( 'type' => 'enum', 'values' => array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around' ) ),
                'gap'        => array( 'type' => 'unit', 'description' => 'Gap between children' ),
                'textAlign'  => array( 'type' => 'enum', 'values' => array( 'left', 'center', 'right' ) ),
            ),
        ),
        'Div' => array(
            'properties' => array(
                'background' => array( 'type' => 'color', 'description' => 'Background color' ),
                'padding'    => array( 'type' => 'spacing', 'description' => 'Padding (shorthand)' ),
                'display'    => array( 'type' => 'enum', 'values' => array( 'flex', 'grid', 'block' ) ),
                'flexDirection' => array( 'type' => 'enum', 'values' => array( 'row', 'column' ) ),
                'alignItems' => array( 'type' => 'enum', 'values' => array( 'flex-start', 'center', 'flex-end', 'stretch' ) ),
                'justifyContent' => array( 'type' => 'enum', 'values' => array( 'flex-start', 'center', 'flex-end', 'space-between', 'space-around' ) ),
                'gap'        => array( 'type' => 'unit', 'description' => 'Gap between children' ),
                'gridColumns' => array( 'type' => 'string', 'description' => 'Grid template columns' ),
                'textAlign'  => array( 'type' => 'enum', 'values' => array( 'left', 'center', 'right' ) ),
                'borderRadius' => array( 'type' => 'unit', 'description' => 'Border radius' ),
            ),
        ),
        'Heading' => array(
            'properties' => array(
                'text'       => array( 'type' => 'string', 'required' => true, 'description' => 'Heading text' ),
                'tag'        => array( 'type' => 'enum', 'values' => array( 'h1', 'h2', 'h3', 'h4', 'h5', 'h6' ), 'default' => 'h2' ),
                'color'      => array( 'type' => 'color', 'description' => 'Text color' ),
                'fontSize'   => array( 'type' => 'unit', 'description' => 'Font size', 'responsive' => true ),
                'fontWeight' => array( 'type' => 'enum', 'values' => array( '100', '200', '300', '400', '500', '600', '700', '800', '900' ) ),
                'lineHeight' => array( 'type' => 'unit', 'description' => 'Line height' ),
                'textAlign'  => array( 'type' => 'enum', 'values' => array( 'left', 'center', 'right' ), 'responsive' => true ),
            ),
        ),
        'Text' => array(
            'properties' => array(
                'text'       => array( 'type' => 'string', 'required' => true, 'description' => 'Text content' ),
                'color'      => array( 'type' => 'color', 'description' => 'Text color' ),
                'fontSize'   => array( 'type' => 'unit', 'description' => 'Font size', 'responsive' => true ),
                'fontWeight' => array( 'type' => 'enum', 'values' => array( '400', '500', '600', '700' ) ),
                'lineHeight' => array( 'type' => 'unit', 'description' => 'Line height' ),
                'textAlign'  => array( 'type' => 'enum', 'values' => array( 'left', 'center', 'right' ), 'responsive' => true ),
                'maxWidth'   => array( 'type' => 'unit', 'description' => 'Maximum width' ),
            ),
        ),
        'Button' => array(
            'properties' => array(
                'text'       => array( 'type' => 'string', 'required' => true, 'description' => 'Button text' ),
                'url'        => array( 'type' => 'string', 'description' => 'Button link URL' ),
                'background' => array( 'type' => 'color', 'description' => 'Background color' ),
                'color'      => array( 'type' => 'color', 'description' => 'Text color' ),
                'fontSize'   => array( 'type' => 'unit', 'description' => 'Font size' ),
                'padding'    => array( 'type' => 'spacing', 'description' => 'Padding' ),
                'borderRadius' => array( 'type' => 'unit', 'description' => 'Border radius' ),
            ),
        ),
        'Image' => array(
            'properties' => array(
                'src'        => array( 'type' => 'string', 'required' => true, 'description' => 'Image URL' ),
                'alt'        => array( 'type' => 'string', 'description' => 'Alt text' ),
                'width'      => array( 'type' => 'unit', 'description' => 'Image width' ),
                'maxWidth'   => array( 'type' => 'unit', 'description' => 'Maximum width' ),
                'borderRadius' => array( 'type' => 'unit', 'description' => 'Border radius' ),
            ),
        ),
        'HtmlCode' => array(
            'properties' => array(
                'html'  => array( 'type' => 'code', 'language' => 'html', 'required' => true, 'description' => 'HTML code' ),
                'label' => array( 'type' => 'string', 'description' => 'Builder label' ),
            ),
        ),
        'CssCode' => array(
            'properties' => array(
                'css'   => array( 'type' => 'code', 'language' => 'css', 'required' => true, 'description' => 'CSS code' ),
                'label' => array( 'type' => 'string', 'description' => 'Builder label' ),
            ),
        ),
        'PhpCode' => array(
            'properties'  => array(
                'php'   => array( 'type' => 'code', 'language' => 'php', 'required' => true, 'description' => 'PHP code' ),
                'label' => array( 'type' => 'string', 'description' => 'Builder label' ),
            ),
            'permissions' => array( 'unfiltered_html' ),
        ),
    );

    /**
     * Get the element type mapping.
     *
     * Returns a mapping of simplified element names to their Breakdance class names.
     *
     * @since 1.0.0
     *
     * @return array Element name => Breakdance class name mapping.
     */
    public function get_element_map(): array {
        return self::$element_type_map;
    }

    /**
     * Get the simplified schema for an element type.
     *
     * @since 1.0.0
     *
     * @param string $element_name The simplified element name (e.g., 'Heading').
     * @return array The element schema with properties and permissions.
     */
    public function get_element_simplified_schema( string $element_name ): array {
        return self::$element_schemas[ $element_name ] ?? array( 'properties' => array() );
    }

    /**
     * Get all element simplified schemas.
     *
     * @since 1.0.0
     *
     * @return array All element schemas.
     */
    public function get_all_element_schemas(): array {
        return self::$element_schemas;
    }

    /**
     * Get the Breakdance class name for an element type.
     *
     * @since 1.0.0
     *
     * @param string $element_name The simplified element name.
     * @return string|null The Breakdance class name or null if not found.
     */
    public function get_breakdance_type( string $element_name ): ?string {
        return self::$element_type_map[ $element_name ] ?? null;
    }
}
