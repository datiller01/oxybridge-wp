# OxyBridge Element Structures and Styling Properties

This document provides comprehensive documentation for all styling properties supported by the OxyBridge AI endpoint. These properties enable AI-driven page generation with full design fidelity using the Breakdance/Oxygen page builders.

## Table of Contents

1. [Overview](#overview)
2. [Property Categories](#property-categories)
3. [Effects Properties](#effects-properties)
4. [Transform Properties](#transform-properties)
5. [Gradient and Background Properties](#gradient-and-background-properties)
6. [Border Properties](#border-properties)
7. [Responsive Values](#responsive-values)
8. [CSS Variables](#css-variables)
9. [Usage Examples](#usage-examples)

---

## Overview

OxyBridge provides a simplified API for styling elements. Properties use camelCase naming and map to the full Breakdance property paths internally. All properties support hover variants (append `Hover` to property name) and responsive breakpoints where noted.

### Property Value Types

| Type | Description | Example |
|------|-------------|---------|
| `number` | Numeric value (no unit) | `0.8` |
| `unit` | Value with CSS unit | `"10px"`, `"45deg"`, `"300ms"` |
| `color` | CSS color value | `"#ff0000"`, `"rgba(0,0,0,0.5)"` |
| `enum` | Predefined values | `"ease-in-out"`, `"multiply"` |
| `css_variable` | CSS custom property | `"var(--primary-color)"` |

---

## Property Categories

| Category | Description |
|----------|-------------|
| [Effects](#effects-properties) | Opacity, shadows, filters, transitions, blend modes |
| [Transform](#transform-properties) | Rotate, scale, skew, translate, perspective |
| [Gradient/Background](#gradient-and-background-properties) | Gradients, overlays, blend modes, background images |
| [Border](#border-properties) | Border radius, styles, widths, colors |

---

## Effects Properties

Effects properties control visual effects like transparency, shadows, filters, and transitions.

### Opacity

Controls element transparency.

| Property | Type | Range | Default | Responsive | Description |
|----------|------|-------|---------|------------|-------------|
| `opacity` | number | 0-1 | 1 | Yes | Element transparency |
| `opacityHover` | number | 0-1 | - | Yes | Opacity on hover state |

**Example:**
```json
{
  "opacity": 0.8,
  "opacityHover": 1,
  "transitionDuration": "300ms"
}
```

### Box Shadow

CSS box-shadow effects for drop shadows and depth.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `boxShadow` | string | - | Yes | CSS box-shadow value |
| `boxShadowHover` | string | - | Yes | Box shadow on hover |

**Supported Formats:**
- Standard shadow: `"0 4px 6px rgba(0,0,0,0.1)"`
- Inset shadow: `"inset 0 2px 4px rgba(0,0,0,0.06)"`
- Multiple shadows: `"0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)"`
- CSS variable: `"var(--shadow-lg)"`

**Example:**
```json
{
  "boxShadow": "0 2px 4px rgba(0,0,0,0.1)",
  "boxShadowHover": "0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23)",
  "transitionDuration": "300ms"
}
```

### Mix Blend Mode

Controls how element blends with content behind it.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `mixBlendMode` | enum | `"normal"` | Yes | Element blend mode |
| `mixBlendModeHover` | enum | - | Yes | Blend mode on hover |

**Valid Values:**
`normal`, `multiply`, `screen`, `overlay`, `darken`, `lighten`, `color-dodge`, `color-burn`, `hard-light`, `soft-light`, `difference`, `exclusion`, `hue`, `saturation`, `color`, `luminosity`

**Example:**
```json
{
  "mixBlendMode": "multiply",
  "mixBlendModeHover": "normal"
}
```

### Transitions

Smooth animations between property changes.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `transitionDuration` | unit | `"300ms"` | Yes | Animation duration (ms/s) |
| `transitionTiming` | enum | `"ease-in-out"` | Yes | Easing function |
| `transitionProperty` | enum | `"all"` | Yes | Which properties to animate |
| `transitionCustomProperty` | string | - | Yes | Custom CSS property name |
| `transitionDelay` | unit | `"0ms"` | Yes | Delay before animation |

**Transition Timing Values:**
`ease-in-out`, `ease-in`, `ease-out`, `ease`, `linear`

**Transition Property Values:**
`all`, `custom` (use `transitionCustomProperty` for specific property)

**Example - Basic Transition:**
```json
{
  "transitionDuration": "300ms",
  "transitionTiming": "ease-out"
}
```

**Example - Specific Property Transition:**
```json
{
  "transitionProperty": "custom",
  "transitionCustomProperty": "transform",
  "transitionDuration": "500ms",
  "transitionTiming": "ease-in-out"
}
```

### CSS Filters

Visual effects applied to elements.

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `filterBlur` | unit | px | Yes | Gaussian blur amount |
| `filterBrightness` | unit | % | Yes | Brightness (100% = normal) |
| `filterContrast` | unit | % | Yes | Contrast (100% = normal) |
| `filterGrayscale` | unit | % | Yes | Grayscale conversion (0-100%) |
| `filterHueRotate` | unit | deg | Yes | Color hue rotation (0-360deg) |
| `filterInvert` | unit | % | Yes | Color inversion (0-100%) |
| `filterSaturate` | unit | % | Yes | Color saturation (100% = normal) |
| `filterSepia` | unit | % | Yes | Sepia tone effect (0-100%) |

Each filter property supports a hover variant (e.g., `filterBlurHover`).

**Example - Frosted Glass:**
```json
{
  "filterBlur": "10px",
  "opacity": 0.8
}
```

**Example - Grayscale to Color on Hover:**
```json
{
  "filterGrayscale": "100%",
  "filterGrayscaleHover": "0%",
  "transitionDuration": "400ms"
}
```

**Example - Vintage Photo Effect:**
```json
{
  "filterSepia": "40%",
  "filterSaturate": "80%",
  "filterContrast": "90%"
}
```

---

## Transform Properties

Transform properties enable 2D and 3D element transformations.

### Rotate

Rotation around X, Y, and Z axes.

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `rotateX` | unit | deg | Yes | Rotation around X axis (tilt forward/back) |
| `rotateY` | unit | deg | Yes | Rotation around Y axis (turn left/right) |
| `rotateZ` | unit | deg | Yes | Rotation around Z axis (standard 2D rotation) |
| `rotateXHover` | unit | deg | Yes | X rotation on hover |
| `rotateYHover` | unit | deg | Yes | Y rotation on hover |
| `rotateZHover` | unit | deg | Yes | Z rotation on hover |

**Example - 2D Rotation:**
```json
{
  "rotateZ": "45deg"
}
```

**Example - Spin on Hover:**
```json
{
  "rotateZ": "0deg",
  "rotateZHover": "360deg",
  "transitionDuration": "500ms"
}
```

**Example - 3D Card Flip:**
```json
{
  "rotateY": "0deg",
  "rotateYHover": "180deg",
  "perspective": "1000px",
  "transformStyle": "preserve-3d",
  "transitionDuration": "600ms"
}
```

### Rotate3D (Advanced)

Custom 3D axis rotation.

| Property | Type | Range | Responsive | Description |
|----------|------|-------|------------|-------------|
| `rotate3dX` | number | 0-1 | Yes | X component of rotation axis |
| `rotate3dY` | number | 0-1 | Yes | Y component of rotation axis |
| `rotate3dZ` | number | 0-1 | Yes | Z component of rotation axis |
| `rotate3dAngle` | unit | deg | Yes | Rotation angle around the axis |

### Scale

Uniform and per-axis scaling.

| Property | Type | Range | Default | Responsive | Description |
|----------|------|-------|---------|------------|-------------|
| `scale` | number | 0-4 | 1 | Yes | Uniform scaling factor |
| `scaleX` | number | 0-4 | 1 | Yes | Horizontal scaling |
| `scaleY` | number | 0-4 | 1 | Yes | Vertical scaling |
| `scaleZ` | number | 0-4 | 1 | Yes | Z-axis scaling (3D) |
| `scaleHover` | number | 0-4 | - | Yes | Scale on hover |
| `scaleXHover` | number | 0-4 | - | Yes | X scale on hover |
| `scaleYHover` | number | 0-4 | - | Yes | Y scale on hover |
| `scaleZHover` | number | 0-4 | - | Yes | Z scale on hover |

**Example - Grow on Hover:**
```json
{
  "scale": 1,
  "scaleHover": 1.05,
  "transitionDuration": "200ms"
}
```

**Example - Press Button Effect:**
```json
{
  "scale": 1,
  "scaleHover": 0.95,
  "transitionDuration": "150ms"
}
```

### Skew

Horizontal and vertical skewing (shearing).

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `skewX` | unit | deg | Yes | Horizontal skew angle |
| `skewY` | unit | deg | Yes | Vertical skew angle |
| `skewXHover` | unit | deg | Yes | X skew on hover |
| `skewYHover` | unit | deg | Yes | Y skew on hover |

**Example - Skewed Heading:**
```json
{
  "skewX": "-5deg",
  "transformOrigin": "bottom left"
}
```

### Translate

Move elements in 2D/3D space.

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `translateX` | unit | px/em/rem/% | Yes | Horizontal translation |
| `translateY` | unit | px/em/rem/% | Yes | Vertical translation |
| `translateZ` | unit | px | Yes | Z-axis translation (3D depth) |
| `translateXHover` | unit | px/em/rem/% | Yes | X translation on hover |
| `translateYHover` | unit | px/em/rem/% | Yes | Y translation on hover |
| `translateZHover` | unit | px | Yes | Z translation on hover |

**Example - Float Up on Hover:**
```json
{
  "translateY": "0px",
  "translateYHover": "-5px",
  "boxShadow": "0 2px 4px rgba(0,0,0,0.1)",
  "boxShadowHover": "0 8px 16px rgba(0,0,0,0.15)",
  "transitionDuration": "300ms"
}
```

**Example - Slide Effect:**
```json
{
  "translateX": "0px",
  "translateXHover": "10px"
}
```

### Perspective

Distance from viewer for 3D transforms.

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `perspective` | unit | px | Yes | Perspective distance (lower = more dramatic) |
| `perspectiveValue` | unit | px | Yes | Inline perspective transform |
| `perspectiveHover` | unit | px | Yes | Perspective on hover |
| `perspectiveOriginX` | unit | px/% | Yes | X position of vanishing point |
| `perspectiveOriginY` | unit | px/% | Yes | Y position of vanishing point |

**Example - 3D Tilt Card:**
```json
{
  "rotateX": "0deg",
  "rotateY": "0deg",
  "rotateXHover": "-5deg",
  "rotateYHover": "10deg",
  "perspective": "1000px",
  "transitionDuration": "300ms"
}
```

### Transform Origin

Point around which transforms are applied.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `transformOrigin` | enum | `"center"` | Yes | Transform origin preset |
| `transformOriginHover` | enum | - | Yes | Origin on hover |
| `transformOriginX` | unit | `"50%"` | Yes | Custom X position (0-100%) |
| `transformOriginY` | unit | `"50%"` | Yes | Custom Y position (0-100%) |

**Transform Origin Values:**
`top left`, `top center`, `top right`, `center left`, `center`, `center right`, `bottom left`, `bottom center`, `bottom right`, `custom`

### Transform Style

How child elements are positioned in 3D space.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `transformStyle` | enum | `"flat"` | Yes | 3D rendering mode |
| `transformStyleHover` | enum | - | Yes | Transform style on hover |

**Values:** `flat`, `preserve-3d`

---

## Gradient and Background Properties

### Basic Background

| Property | Type | Responsive | Description |
|----------|------|------------|-------------|
| `backgroundColor` | color | Yes | Solid background color |
| `backgroundColorHover` | color | Yes | Background color on hover |
| `backgroundImage` | url | Yes | Background image URL |
| `backgroundType` | enum | Yes | Background type (color/image/gradient/none) |
| `backgroundPosition` | enum | Yes | Image/gradient position |
| `backgroundSize` | enum | Yes | Image/gradient size |
| `backgroundRepeat` | enum | Yes | Image repeat behavior |
| `backgroundAttachment` | enum | Yes | Scroll behavior (scroll/fixed/local) |

**Background Position Values:**
`top left`, `top center`, `top right`, `center left`, `center`, `center right`, `bottom left`, `bottom center`, `bottom right`, `custom`

**Background Size Values:**
`auto`, `cover`, `contain`, `custom`

**Background Repeat Values:**
`repeat`, `repeat-x`, `repeat-y`, `no-repeat`, `space`, `round`

### Gradients

Gradient backgrounds support CSS string format or structured objects.

| Property | Type | Responsive | Description |
|----------|------|------------|-------------|
| `gradient` | string/object | Yes | Full gradient definition |
| `gradientStyle` | string | Yes | CSS gradient string |
| `gradientValue` | string | Yes | Gradient value |
| `gradientType` | enum | Yes | Gradient type (linear/radial/conic) |
| `gradientAngle` | unit | Yes | Angle for linear gradients |
| `gradientColors` | array | Yes | Color stop array |
| `gradientHover` | string/object | Yes | Gradient on hover |

**Gradient Type Values:**
`linear`, `radial`, `conic`

**Radial-Specific Properties:**

| Property | Type | Description |
|----------|------|-------------|
| `gradientRadialPosition` | enum | Position of radial center |
| `gradientRadialShape` | enum | `ellipse` or `circle` |
| `gradientRadialSize` | enum | `closest-side`, `closest-corner`, `farthest-side`, `farthest-corner` |

**Example - Linear Gradient:**
```json
{
  "gradient": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)"
}
```

**Example - Radial Gradient:**
```json
{
  "gradient": "radial-gradient(circle at center, #ffffff 0%, #000000 100%)"
}
```

**Example - Gradient with Hover:**
```json
{
  "gradient": "linear-gradient(90deg, #667eea, #764ba2)",
  "gradientHover": "linear-gradient(270deg, #667eea, #764ba2)",
  "transitionDuration": "500ms"
}
```

**Example - Mesh Gradient:**
```json
{
  "gradient": "radial-gradient(at 40% 20%, #ff6b6b 0px, transparent 50%), radial-gradient(at 80% 0%, #feca57 0px, transparent 50%), radial-gradient(at 0% 50%, #48dbfb 0px, transparent 50%)"
}
```

### Background Blend Mode

How background layers blend together.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `backgroundBlendMode` | enum | `"normal"` | Yes | Blend mode for background layers |
| `backgroundBlendModeHover` | enum | - | Yes | Blend mode on hover |
| `blendMode` | enum | `"normal"` | Yes | Alias for backgroundBlendMode |

**Blend Mode Values:**
`normal`, `multiply`, `screen`, `overlay`, `darken`, `lighten`, `color-dodge`, `color-burn`, `hard-light`, `soft-light`, `difference`, `exclusion`, `hue`, `saturation`, `color`, `luminosity`

**Example - Duotone Effect:**
```json
{
  "gradient": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
  "backgroundBlendMode": "color",
  "filterGrayscale": "100%"
}
```

### Overlay

Overlay effects on top of backgrounds.

| Property | Type | Responsive | Description |
|----------|------|------------|-------------|
| `overlayColor` | color | Yes | Solid color overlay |
| `overlayColorHover` | color | Yes | Overlay color on hover |
| `overlayImage` | url | Yes | Image overlay |
| `overlayGradient` | string | Yes | Gradient overlay |
| `overlayGradientHover` | string | Yes | Gradient overlay on hover |
| `overlayOpacity` | number | Yes | Overlay opacity (0-1) |
| `overlayOpacityHover` | number | Yes | Overlay opacity on hover |
| `overlayBlendMode` | enum | Yes | How overlay blends |

**Example - Hero Section Overlay:**
```json
{
  "overlayGradient": "linear-gradient(180deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.8) 100%)"
}
```

**Example - Color Tint Overlay:**
```json
{
  "overlayColor": "rgba(102,126,234,0.5)",
  "overlayBlendMode": "multiply"
}
```

### Background Layers

For complex, layered backgrounds (FancyBackground).

| Property | Type | Description |
|----------|------|-------------|
| `backgroundLayers` | array | Array of background layer objects |
| `backgroundLayerType` | enum | Layer type (image/gradient/overlay_color/none) |
| `backgroundLayerImage` | url | Image for layer |
| `backgroundLayerGradient` | string | Gradient for layer |
| `backgroundLayerOverlayColor` | color | Color overlay for layer |
| `backgroundLayerBlendMode` | enum | Blend mode for layer |
| `backgroundLayerSize` | enum | Size for layer |
| `backgroundLayerPosition` | enum | Position for layer |
| `backgroundLayerRepeat` | enum | Repeat behavior for layer |
| `backgroundLayerAttachment` | enum | Attachment for layer |

---

## Border Properties

### Border Radius

Rounded corners for elements.

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `borderRadius` | object/unit | px | Yes | Border radius (all corners or object) |
| `borderRadiusAll` | unit | px | Yes | Uniform radius for all corners |
| `radiusTopLeft` | unit | px | Yes | Top-left corner radius |
| `radiusTopRight` | unit | px | Yes | Top-right corner radius |
| `radiusBottomLeft` | unit | px | Yes | Bottom-left corner radius |
| `radiusBottomRight` | unit | px | Yes | Bottom-right corner radius |

All properties support hover variants (e.g., `borderRadiusHover`, `radiusTopLeftHover`).

**Example - Uniform Radius:**
```json
{
  "borderRadiusAll": "8px"
}
```

**Example - Per-Corner Radius:**
```json
{
  "radiusTopLeft": "20px",
  "radiusTopRight": "20px",
  "radiusBottomLeft": "0px",
  "radiusBottomRight": "0px"
}
```

**Example - Pill Shape:**
```json
{
  "borderRadiusAll": "9999px"
}
```

### Border Style

Border line styles.

| Property | Type | Default | Responsive | Description |
|----------|------|---------|------------|-------------|
| `borderAllStyle` | enum | `"none"` | Yes | Style for all sides |
| `borderTopStyle` | enum | `"none"` | Yes | Top border style |
| `borderRightStyle` | enum | `"none"` | Yes | Right border style |
| `borderBottomStyle` | enum | `"none"` | Yes | Bottom border style |
| `borderLeftStyle` | enum | `"none"` | Yes | Left border style |

All properties support hover variants.

**Border Style Values:**
`none`, `solid`, `dashed`, `dotted`, `double`, `groove`, `ridge`, `inset`, `outset`

### Border Width

Border thickness.

| Property | Type | Unit | Responsive | Description |
|----------|------|------|------------|-------------|
| `borderAllWidth` | unit | px | Yes | Width for all sides |
| `borderTopWidth` | unit | px | Yes | Top border width |
| `borderRightWidth` | unit | px | Yes | Right border width |
| `borderBottomWidth` | unit | px | Yes | Bottom border width |
| `borderLeftWidth` | unit | px | Yes | Left border width |

All properties support hover variants.

### Border Color

Border colors.

| Property | Type | Responsive | Description |
|----------|------|------------|-------------|
| `borderAllColor` | color | Yes | Color for all sides |
| `borderTopColor` | color | Yes | Top border color |
| `borderRightColor` | color | Yes | Right border color |
| `borderBottomColor` | color | Yes | Bottom border color |
| `borderLeftColor` | color | Yes | Left border color |

All properties support hover variants.

**Example - Simple Border:**
```json
{
  "borderAllWidth": "1px",
  "borderAllStyle": "solid",
  "borderAllColor": "#e0e0e0"
}
```

**Example - Bottom Border Only:**
```json
{
  "borderBottomWidth": "2px",
  "borderBottomStyle": "solid",
  "borderBottomColor": "#3b82f6"
}
```

**Example - Border with Hover:**
```json
{
  "borderAllWidth": "1px",
  "borderAllStyle": "solid",
  "borderAllColor": "#e0e0e0",
  "borderAllColorHover": "#3b82f6",
  "transitionDuration": "200ms"
}
```

---

## Responsive Values

Properties marked as "Responsive: Yes" support different values per breakpoint.

### Available Breakpoints

| Breakpoint Key | Description |
|----------------|-------------|
| `breakpoint_base` | Desktop/default value |
| `breakpoint_tablet_landscape` | Tablet landscape |
| `breakpoint_tablet_portrait` | Tablet portrait |
| `breakpoint_phone_landscape` | Phone landscape |
| `breakpoint_phone_portrait` | Phone portrait |

### Responsive Value Format

```json
{
  "opacity": {
    "breakpoint_base": 1,
    "breakpoint_tablet_portrait": 0.9,
    "breakpoint_phone_portrait": 0.8
  }
}
```

Or simplified (applies to base breakpoint):
```json
{
  "opacity": 1
}
```

---

## CSS Variables

All color and unit properties support CSS custom properties (variables).

### Supported Formats

```json
{
  "backgroundColor": "var(--primary-color)",
  "boxShadow": "var(--shadow-lg)",
  "borderAllColor": "var(--border-color)",
  "transitionDuration": "var(--transition-duration)"
}
```

### With Fallback Values

```json
{
  "backgroundColor": "var(--primary-color, #3b82f6)"
}
```

---

## Usage Examples

### Card with Hover Effects

```json
{
  "type": "Div",
  "backgroundColor": "#ffffff",
  "borderRadiusAll": "8px",
  "boxShadow": "0 1px 3px rgba(0,0,0,0.12), 0 1px 2px rgba(0,0,0,0.24)",
  "boxShadowHover": "0 10px 20px rgba(0,0,0,0.19), 0 6px 6px rgba(0,0,0,0.23)",
  "translateY": "0px",
  "translateYHover": "-5px",
  "transitionDuration": "300ms",
  "transitionTiming": "ease-out"
}
```

### Gradient Hero Section

```json
{
  "type": "Section",
  "gradient": "linear-gradient(135deg, #667eea 0%, #764ba2 100%)",
  "overlayGradient": "linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(0,0,0,0.3) 100%)"
}
```

### Button with Press Effect

```json
{
  "type": "Button",
  "backgroundColor": "#3b82f6",
  "backgroundColorHover": "#2563eb",
  "borderRadiusAll": "6px",
  "scale": 1,
  "scaleHover": 0.95,
  "boxShadow": "0 4px 6px rgba(59,130,246,0.3)",
  "boxShadowHover": "0 2px 3px rgba(59,130,246,0.3)",
  "transitionDuration": "150ms"
}
```

### 3D Flip Card

```json
{
  "type": "Div",
  "rotateY": "0deg",
  "rotateYHover": "180deg",
  "perspective": "1000px",
  "transformStyle": "preserve-3d",
  "transitionDuration": "600ms",
  "transitionTiming": "ease-in-out"
}
```

### Glassmorphism Effect

```json
{
  "type": "Div",
  "backgroundColor": "rgba(255,255,255,0.1)",
  "filterBlur": "10px",
  "borderAllWidth": "1px",
  "borderAllStyle": "solid",
  "borderAllColor": "rgba(255,255,255,0.2)",
  "borderRadiusAll": "16px"
}
```

### Image with Grayscale to Color Hover

```json
{
  "type": "Image",
  "filterGrayscale": "100%",
  "filterGrayscaleHover": "0%",
  "scale": 1,
  "scaleHover": 1.05,
  "transitionDuration": "400ms"
}
```

---

## Property Quick Reference

### Effects Summary

| Property | Hover Variant | Type |
|----------|---------------|------|
| `opacity` | `opacityHover` | number (0-1) |
| `boxShadow` | `boxShadowHover` | string |
| `mixBlendMode` | `mixBlendModeHover` | enum |
| `filterBlur` | `filterBlurHover` | unit (px) |
| `filterBrightness` | `filterBrightnessHover` | unit (%) |
| `filterContrast` | `filterContrastHover` | unit (%) |
| `filterGrayscale` | `filterGrayscaleHover` | unit (%) |
| `filterHueRotate` | `filterHueRotateHover` | unit (deg) |
| `filterInvert` | `filterInvertHover` | unit (%) |
| `filterSaturate` | `filterSaturateHover` | unit (%) |
| `filterSepia` | `filterSepiaHover` | unit (%) |
| `transitionDuration` | - | unit (ms/s) |
| `transitionTiming` | - | enum |
| `transitionProperty` | - | enum |
| `transitionDelay` | - | unit (ms/s) |

### Transform Summary

| Property | Hover Variant | Type |
|----------|---------------|------|
| `rotateX` | `rotateXHover` | unit (deg) |
| `rotateY` | `rotateYHover` | unit (deg) |
| `rotateZ` | `rotateZHover` | unit (deg) |
| `scale` | `scaleHover` | number (0-4) |
| `scaleX` | `scaleXHover` | number (0-4) |
| `scaleY` | `scaleYHover` | number (0-4) |
| `scaleZ` | `scaleZHover` | number (0-4) |
| `skewX` | `skewXHover` | unit (deg) |
| `skewY` | `skewYHover` | unit (deg) |
| `translateX` | `translateXHover` | unit (px/em/rem/%) |
| `translateY` | `translateYHover` | unit (px/em/rem/%) |
| `translateZ` | `translateZHover` | unit (px) |
| `perspective` | `perspectiveHover` | unit (px) |
| `transformOrigin` | `transformOriginHover` | enum |
| `transformStyle` | `transformStyleHover` | enum |

### Gradient/Background Summary

| Property | Hover Variant | Type |
|----------|---------------|------|
| `gradient` | `gradientHover` | string/object |
| `gradientType` | `gradientTypeHover` | enum |
| `gradientAngle` | `gradientAngleHover` | unit (deg) |
| `backgroundColor` | `backgroundColorHover` | color |
| `backgroundBlendMode` | `backgroundBlendModeHover` | enum |
| `overlayColor` | `overlayColorHover` | color |
| `overlayGradient` | `overlayGradientHover` | string |
| `overlayOpacity` | `overlayOpacityHover` | number (0-1) |
| `overlayBlendMode` | `overlayBlendModeHover` | enum |

### Border Summary

| Property | Hover Variant | Type |
|----------|---------------|------|
| `borderRadiusAll` | `borderRadiusAllHover` | unit (px) |
| `radiusTopLeft` | `radiusTopLeftHover` | unit (px) |
| `radiusTopRight` | `radiusTopRightHover` | unit (px) |
| `radiusBottomLeft` | `radiusBottomLeftHover` | unit (px) |
| `radiusBottomRight` | `radiusBottomRightHover` | unit (px) |
| `borderAllWidth` | `borderAllWidthHover` | unit (px) |
| `borderAllStyle` | `borderAllStyleHover` | enum |
| `borderAllColor` | `borderAllColorHover` | color |
| `borderTopWidth` | `borderTopWidthHover` | unit (px) |
| `borderTopStyle` | `borderTopStyleHover` | enum |
| `borderTopColor` | `borderTopColorHover` | color |
| `borderRightWidth` | `borderRightWidthHover` | unit (px) |
| `borderRightStyle` | `borderRightStyleHover` | enum |
| `borderRightColor` | `borderRightColorHover` | color |
| `borderBottomWidth` | `borderBottomWidthHover` | unit (px) |
| `borderBottomStyle` | `borderBottomStyleHover` | enum |
| `borderBottomColor` | `borderBottomColorHover` | color |
| `borderLeftWidth` | `borderLeftWidthHover` | unit (px) |
| `borderLeftStyle` | `borderLeftStyleHover` | enum |
| `borderLeftColor` | `borderLeftColorHover` | color |

---

*Last updated: 2026-01-24*
*Version: 1.0.0*
