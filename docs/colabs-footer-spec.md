# CoLabs.com.au Footer Specification

This document provides comprehensive documentation for replicating the colabs.com.au footer via OxyBridge API. It includes element hierarchy, visual design specifications, and complete JSON tree structure for pixel-accurate recreation.

## Table of Contents

1. [Overview](#overview)
2. [Element Hierarchy](#element-hierarchy)
3. [Visual Design Tokens](#visual-design-tokens)
4. [Element Specifications](#element-specifications)
5. [Responsive Behavior](#responsive-behavior)
6. [OxyBridge JSON Tree Structure](#oxybridge-json-tree-structure)

---

## Overview

The CoLabs footer is a comprehensive site footer component featuring:
- **Acknowledgment**: Traditional Custodians acknowledgment text
- **Locations**: 4 physical location addresses with contact information
- **Navigation**: 7 footer navigation links
- **Social Links**: 4 social media icons
- **Branding**: Attribution to design agency
- **Copyright**: Legal copyright notice

### Design Philosophy

The footer follows the same clean, modern aesthetic as the header with:
- Dark background contrasting the light header
- Clear information hierarchy with distinct sections
- Generous whitespace and readable typography
- Consistent branding through Matter SQ font family
- Mobile-responsive stacked layout

---

## Element Hierarchy

```
Footer (Section)
├── Container (Div) - max-width container
│   ├── Top Section (Div) - flex row
│   │   ├── Acknowledgment Column (Div)
│   │   │   └── Acknowledgment Text (Text)
│   │   │
│   │   └── Locations Grid (Div) - 2x2 grid
│   │       ├── Location 1 (Div)
│   │       │   ├── Location Name (Heading)
│   │       │   ├── Address (Text)
│   │       │   └── Phone (Link)
│   │       ├── Location 2 (Div)
│   │       │   ├── Location Name (Heading)
│   │       │   ├── Address (Text)
│   │       │   └── Phone (Link)
│   │       ├── Location 3 (Div)
│   │       │   ├── Location Name (Heading)
│   │       │   ├── Address (Text)
│   │       │   └── Phone (Link)
│   │       └── Location 4 (Div)
│   │           ├── Location Name (Heading)
│   │           ├── Address (Text)
│   │           └── Phone (Link)
│   │
│   ├── Divider (Div) - horizontal line
│   │
│   ├── Middle Section (Div) - flex row
│   │   ├── Brand Attribution (Div)
│   │   │   ├── Text (Text) - "Brand and website by"
│   │   │   └── Logo (Image/Link) - Your Creative logo
│   │   │
│   │   └── Navigation (Div) - flex wrap
│   │       ├── Services (Link)
│   │       ├── Privacy Policy (Link)
│   │       ├── Our Principles (Link)
│   │       ├── Terms and Conditions (Link)
│   │       ├── About (Link)
│   │       ├── Journal (Link)
│   │       └── Contact (Link)
│   │
│   ├── Divider (Div) - horizontal line
│   │
│   └── Bottom Section (Div) - flex row
│       ├── Copyright (Text) - "© 2026 CoLabs"
│       │
│       └── Social Links (Div)
│           ├── Instagram (Icon/Link)
│           ├── Facebook (Icon/Link)
│           ├── LinkedIn (Icon/Link)
│           └── Twitter (Icon/Link)
```

---

## Visual Design Tokens

### Colors

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-footer-bg` | `#1a1a1a` | Footer background |
| `--colabs-footer-text` | `#ffffff` | Primary text color |
| `--colabs-footer-text-muted` | `#999999` | Secondary/muted text |
| `--colabs-footer-text-hover` | `#ffffff` | Link hover state |
| `--colabs-footer-divider` | `#333333` | Horizontal divider lines |
| `--colabs-footer-link` | `#cccccc` | Link color |
| `--colabs-footer-link-hover` | `#ffffff` | Link hover color |

### Typography

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-font-family` | `'Matter SQ', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif` | All text |
| `--colabs-footer-font-acknowledgment` | `400` | Acknowledgment font weight |
| `--colabs-footer-font-location-name` | `500` | Location name font weight |
| `--colabs-footer-font-address` | `400` | Address font weight |
| `--colabs-footer-font-nav` | `400` | Navigation font weight |
| `--colabs-footer-font-copyright` | `400` | Copyright font weight |
| `--colabs-footer-size-acknowledgment` | `14px` | Acknowledgment font size |
| `--colabs-footer-size-location-name` | `14px` | Location name font size |
| `--colabs-footer-size-address` | `13px` | Address font size |
| `--colabs-footer-size-phone` | `13px` | Phone number font size |
| `--colabs-footer-size-nav` | `14px` | Navigation font size |
| `--colabs-footer-size-copyright` | `13px` | Copyright font size |
| `--colabs-footer-line-height` | `1.6` | Default line height |
| `--colabs-footer-letter-spacing` | `0.01em` | Navigation letter spacing |

### Spacing

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-footer-padding-vertical` | `80px` | Footer top/bottom padding |
| `--colabs-footer-padding-vertical-mobile` | `48px` | Mobile top/bottom padding |
| `--colabs-footer-container-max` | `1440px` | Max container width |
| `--colabs-footer-container-padding` | `40px` | Container horizontal padding |
| `--colabs-footer-container-padding-mobile` | `20px` | Mobile horizontal padding |
| `--colabs-footer-section-gap` | `48px` | Gap between footer sections |
| `--colabs-footer-section-gap-mobile` | `32px` | Mobile section gap |
| `--colabs-footer-location-gap` | `32px` | Gap between location cards |
| `--colabs-footer-nav-gap` | `24px` | Gap between nav items |
| `--colabs-footer-social-gap` | `16px` | Gap between social icons |
| `--colabs-footer-divider-margin` | `48px 0` | Divider margin |

### Effects

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-footer-transition` | `150ms` | Link transition duration |
| `--colabs-footer-transition-timing` | `ease-out` | Easing function |

---

## Element Specifications

### Footer Container (Section)

| Property | Value | Responsive |
|----------|-------|------------|
| Background | `#1a1a1a` | - |
| Width | `100%` | - |
| Padding Top | `80px` | `48px` (mobile) |
| Padding Bottom | `80px` | `48px` (mobile) |

**OxyBridge Properties:**
```json
{
  "backgroundColor": "#1a1a1a",
  "width": "100%",
  "paddingTop": "80px",
  "paddingBottom": "80px"
}
```

### Inner Container (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | - |
| Flex Direction | `column` | - |
| Max Width | `1440px` | - |
| Width | `100%` | - |
| Margin | `0 auto` | - |
| Padding | `0 40px` | `0 20px` (mobile) |

**OxyBridge Properties:**
```json
{
  "display": "flex",
  "flexDirection": "column",
  "maxWidth": "1440px",
  "width": "100%",
  "marginLeft": "auto",
  "marginRight": "auto",
  "paddingLeft": "40px",
  "paddingRight": "40px"
}
```

### Top Section (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | `flex` |
| Flex Direction | `row` | `column` (mobile) |
| Justify Content | `space-between` | - |
| Gap | `48px` | `32px` (mobile) |

### Acknowledgment Column (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Max Width | `400px` | `100%` (mobile) |
| Flex Shrink | `0` | - |

### Acknowledgment Text (Text)

| Property | Value |
|----------|-------|
| Font Family | `'Matter SQ', sans-serif` |
| Font Size | `14px` |
| Font Weight | `400` |
| Color | `#999999` |
| Line Height | `1.6` |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Text",
  "content": {
    "content": {
      "text": "Co-Labs Melbourne respectfully acknowledges the Traditional Custodians of the land on which we operate our business – the Boon Wurrung and Wurundjeri peoples of the Kulin Nation."
    }
  },
  "design": {
    "typography": {
      "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
      "fontSize": {"breakpoint_base": "14px"},
      "fontWeight": {"breakpoint_base": "400"},
      "color": {"breakpoint_base": "#999999"},
      "lineHeight": {"breakpoint_base": "1.6"}
    }
  }
}
```

### Locations Grid (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `grid` | - |
| Grid Template Columns | `repeat(2, 1fr)` | `1fr` (mobile) |
| Gap | `32px` | `24px` (mobile) |
| Flex | `1` | - |

### Location Card (Div)

| Property | Value |
|----------|-------|
| Display | `flex` |
| Flex Direction | `column` |
| Gap | `8px` |

### Location Name (Heading)

| Property | Value |
|----------|-------|
| Font Family | `'Matter SQ', sans-serif` |
| Font Size | `14px` |
| Font Weight | `500` |
| Color | `#ffffff` |
| Tags | `h4` |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Heading",
  "content": {
    "content": {
      "text": "CoLabs Coworking",
      "tags": "h4"
    }
  },
  "design": {
    "typography": {
      "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
      "fontSize": {"breakpoint_base": "14px"},
      "fontWeight": {"breakpoint_base": "500"},
      "color": {"breakpoint_base": "#ffffff"}
    }
  }
}
```

### Location Address (Text)

| Property | Value |
|----------|-------|
| Font Family | `'Matter SQ', sans-serif` |
| Font Size | `13px` |
| Font Weight | `400` |
| Color | `#999999` |
| Line Height | `1.5` |

### Location Phone (Link)

| Property | Value | Hover |
|----------|-------|-------|
| Font Family | `'Matter SQ', sans-serif` | - |
| Font Size | `13px` | - |
| Font Weight | `400` | - |
| Color | `#cccccc` | `#ffffff` |
| Text Decoration | `none` | `underline` |
| Transition | `150ms ease-out` | - |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Link",
  "content": {
    "content": {
      "text": "(03) 9111 2399",
      "link": {"url": "tel:+61391112399", "type": "url"}
    }
  },
  "design": {
    "typography": {
      "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
      "fontSize": {"breakpoint_base": "13px"},
      "fontWeight": {"breakpoint_base": "400"},
      "color": {"breakpoint_base": "#cccccc"},
      "colorHover": "#ffffff",
      "textDecoration": {"breakpoint_base": "none"},
      "textDecorationHover": "underline"
    },
    "effects": {
      "transition": {
        "duration": {"breakpoint_base": "150ms"},
        "timingFunction": {"breakpoint_base": "ease-out"}
      }
    }
  }
}
```

### Horizontal Divider (Div)

| Property | Value |
|----------|-------|
| Width | `100%` |
| Height | `1px` |
| Background | `#333333` |
| Margin | `48px 0` |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Div",
  "design": {
    "size": {
      "width": {"breakpoint_base": "100%"},
      "height": {"breakpoint_base": "1px"}
    },
    "background": {
      "color": "#333333"
    },
    "spacing": {
      "margin_top": {"breakpoint_base": "48px"},
      "margin_bottom": {"breakpoint_base": "48px"}
    }
  }
}
```

### Middle Section (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | - |
| Flex Direction | `row` | `column` (mobile) |
| Justify Content | `space-between` | - |
| Align Items | `center` | `flex-start` (mobile) |
| Gap | `32px` | `24px` (mobile) |

### Brand Attribution (Div)

| Property | Value |
|----------|-------|
| Display | `flex` |
| Align Items | `center` |
| Gap | `8px` |

### Attribution Text (Text)

| Property | Value |
|----------|-------|
| Font Family | `'Matter SQ', sans-serif` |
| Font Size | `13px` |
| Font Weight | `400` |
| Color | `#999999` |

### Footer Navigation (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | - |
| Flex Wrap | `wrap` | - |
| Align Items | `center` | - |
| Gap | `24px` | `16px` (mobile) |

### Navigation Link (Link)

| Property | Value | Hover |
|----------|-------|-------|
| Font Family | `'Matter SQ', sans-serif` | - |
| Font Size | `14px` | - |
| Font Weight | `400` | - |
| Color | `#cccccc` | `#ffffff` |
| Text Decoration | `none` | `none` |
| Letter Spacing | `0.01em` | - |
| Transition | `150ms ease-out` | - |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Link",
  "content": {
    "content": {
      "text": "Services",
      "link": {"url": "/services", "type": "url"}
    }
  },
  "design": {
    "typography": {
      "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
      "fontSize": {"breakpoint_base": "14px"},
      "fontWeight": {"breakpoint_base": "400"},
      "color": {"breakpoint_base": "#cccccc"},
      "colorHover": "#ffffff",
      "textDecoration": {"breakpoint_base": "none"},
      "letterSpacing": {"breakpoint_base": "0.01em"}
    },
    "effects": {
      "transition": {
        "duration": {"breakpoint_base": "150ms"},
        "timingFunction": {"breakpoint_base": "ease-out"}
      }
    }
  }
}
```

### Bottom Section (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | - |
| Flex Direction | `row` | `column-reverse` (mobile) |
| Justify Content | `space-between` | - |
| Align Items | `center` | `flex-start` (mobile) |
| Gap | `24px` | `16px` (mobile) |

### Copyright Text (Text)

| Property | Value |
|----------|-------|
| Font Family | `'Matter SQ', sans-serif` |
| Font Size | `13px` |
| Font Weight | `400` |
| Color | `#999999` |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Text",
  "content": {
    "content": {
      "text": "© 2026 CoLabs"
    }
  },
  "design": {
    "typography": {
      "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
      "fontSize": {"breakpoint_base": "13px"},
      "fontWeight": {"breakpoint_base": "400"},
      "color": {"breakpoint_base": "#999999"}
    }
  }
}
```

### Social Links Container (Div)

| Property | Value |
|----------|-------|
| Display | `flex` |
| Align Items | `center` |
| Gap | `16px` |

### Social Icon (Link)

| Property | Value | Hover |
|----------|-------|-------|
| Width | `20px` | - |
| Height | `20px` | - |
| Color | `#999999` | `#ffffff` |
| Opacity | `1` | `1` |
| Transition | `150ms ease-out` | - |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Link",
  "content": {
    "content": {
      "text": "",
      "link": {"url": "https://instagram.com/colabs.aus/", "type": "url", "openInNewTab": true}
    }
  },
  "design": {
    "typography": {
      "color": {"breakpoint_base": "#999999"},
      "colorHover": "#ffffff"
    },
    "size": {
      "width": {"breakpoint_base": "20px"},
      "height": {"breakpoint_base": "20px"}
    },
    "effects": {
      "transition": {
        "duration": {"breakpoint_base": "150ms"},
        "timingFunction": {"breakpoint_base": "ease-out"}
      }
    }
  }
}
```

---

## Responsive Behavior

### Breakpoints

| Breakpoint | Width | Changes |
|------------|-------|---------|
| Desktop | `1120px+` | Full multi-column layout |
| Tablet Landscape | `1024px` | 2-column locations grid |
| Tablet Portrait | `768px` | **Mobile footer mode** - stacked layout |
| Phone Landscape | `480px` | Single column, reduced spacing |
| Phone Portrait | `320px` | Minimal footer |

### Desktop (1120px+)

- Top section: acknowledgment left, 2x2 locations grid right
- Middle section: brand attribution left, navigation links right
- Bottom section: copyright left, social icons right
- Full horizontal padding (40px)

### Tablet Portrait (768px) and Below

| Element | Change |
|---------|--------|
| Top Section | Stacked vertically |
| Locations Grid | Single column |
| Middle Section | Stacked vertically |
| Navigation | Wrapped flex, smaller gaps |
| Bottom Section | Reversed order (social above copyright) |
| Container Padding | Reduced to `20px` |
| Section Gap | Reduced to `32px` |
| Divider Margin | Reduced to `32px 0` |

---

## OxyBridge JSON Tree Structure

### Complete Footer Tree

```json
{
  "root": {
    "id": 1,
    "data": {
      "type": "root",
      "properties": null
    },
    "children": [
      {
        "id": 200,
        "data": {
          "type": "EssentialElements\\Section",
          "properties": {
            "design": {
              "background": {
                "color": "#1a1a1a"
              },
              "size": {
                "width": {"breakpoint_base": "100%"}
              },
              "spacing": {
                "padding": {
                  "top": {"breakpoint_base": "80px", "breakpoint_tablet_portrait": "48px"},
                  "bottom": {"breakpoint_base": "80px", "breakpoint_tablet_portrait": "48px"}
                }
              }
            }
          }
        },
        "children": [
          {
            "id": 201,
            "data": {
              "type": "EssentialElements\\Div",
              "properties": {
                "design": {
                  "layout": {
                    "display": {"breakpoint_base": "flex"},
                    "flexDirection": {"breakpoint_base": "column"}
                  },
                  "size": {
                    "width": {"breakpoint_base": "100%"},
                    "maxWidth": {"breakpoint_base": {"number": 1440, "unit": "px"}}
                  },
                  "spacing": {
                    "margin_left": {"breakpoint_base": "auto"},
                    "margin_right": {"breakpoint_base": "auto"},
                    "padding": {
                      "left": {"breakpoint_base": "40px", "breakpoint_phone_portrait": "20px"},
                      "right": {"breakpoint_base": "40px", "breakpoint_phone_portrait": "20px"}
                    }
                  }
                }
              }
            },
            "children": [
              {
                "id": 202,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "layout": {
                        "display": {"breakpoint_base": "flex"},
                        "flexDirection": {"breakpoint_base": "row", "breakpoint_tablet_portrait": "column"},
                        "justifyContent": {"breakpoint_base": "space-between"},
                        "horizontalGap": {"breakpoint_base": "48px", "breakpoint_tablet_portrait": "32px"}
                      }
                    }
                  }
                },
                "children": [
                  {
                    "id": 203,
                    "data": {
                      "type": "EssentialElements\\Div",
                      "properties": {
                        "design": {
                          "size": {
                            "maxWidth": {"breakpoint_base": "400px", "breakpoint_tablet_portrait": "100%"}
                          },
                          "layout": {
                            "flexShrink": {"breakpoint_base": "0"}
                          }
                        }
                      }
                    },
                    "children": [
                      {
                        "id": 204,
                        "data": {
                          "type": "EssentialElements\\Text",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Co-Labs Melbourne respectfully acknowledges the Traditional Custodians of the land on which we operate our business – the Boon Wurrung and Wurundjeri peoples of the Kulin Nation."
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', -apple-system, sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#999999"},
                                "lineHeight": {"breakpoint_base": "1.6"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 203
                      }
                    ],
                    "_parentId": 202
                  },
                  {
                    "id": 205,
                    "data": {
                      "type": "EssentialElements\\Div",
                      "properties": {
                        "design": {
                          "layout": {
                            "display": {"breakpoint_base": "grid"},
                            "gridTemplateColumns": {"breakpoint_base": "repeat(2, 1fr)", "breakpoint_tablet_portrait": "1fr"},
                            "horizontalGap": {"breakpoint_base": "32px", "breakpoint_tablet_portrait": "24px"},
                            "verticalGap": {"breakpoint_base": "32px", "breakpoint_tablet_portrait": "24px"},
                            "flex": {"breakpoint_base": "1"}
                          }
                        }
                      }
                    },
                    "children": [
                      {
                        "id": 206,
                        "data": {
                          "type": "EssentialElements\\Div",
                          "properties": {
                            "design": {
                              "layout": {
                                "display": {"breakpoint_base": "flex"},
                                "flexDirection": {"breakpoint_base": "column"},
                                "verticalGap": {"breakpoint_base": "8px"}
                              }
                            }
                          }
                        },
                        "children": [
                          {
                            "id": 207,
                            "data": {
                              "type": "EssentialElements\\Heading",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "CoLabs Coworking",
                                    "tags": "h4"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "14px"},
                                    "fontWeight": {"breakpoint_base": "500"},
                                    "color": {"breakpoint_base": "#ffffff"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 206
                          },
                          {
                            "id": 208,
                            "data": {
                              "type": "EssentialElements\\Text",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "1/306 Albert St, Brunswick"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#999999"},
                                    "lineHeight": {"breakpoint_base": "1.5"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 206
                          },
                          {
                            "id": 209,
                            "data": {
                              "type": "EssentialElements\\Link",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "(03) 9111 2399",
                                    "link": {"url": "tel:+61391112399", "type": "url"}
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#cccccc"},
                                    "textDecoration": {"breakpoint_base": "none"}
                                  },
                                  "effects": {
                                    "transition": {
                                      "duration": {"breakpoint_base": "150ms"},
                                      "timingFunction": {"breakpoint_base": "ease-out"}
                                    }
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 206
                          }
                        ],
                        "_parentId": 205
                      },
                      {
                        "id": 210,
                        "data": {
                          "type": "EssentialElements\\Div",
                          "properties": {
                            "design": {
                              "layout": {
                                "display": {"breakpoint_base": "flex"},
                                "flexDirection": {"breakpoint_base": "column"},
                                "verticalGap": {"breakpoint_base": "8px"}
                              }
                            }
                          }
                        },
                        "children": [
                          {
                            "id": 211,
                            "data": {
                              "type": "EssentialElements\\Heading",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "CoLabs Office",
                                    "tags": "h4"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "14px"},
                                    "fontWeight": {"breakpoint_base": "500"},
                                    "color": {"breakpoint_base": "#ffffff"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 210
                          },
                          {
                            "id": 212,
                            "data": {
                              "type": "EssentialElements\\Text",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "17/306 Albert St, Brunswick"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#999999"},
                                    "lineHeight": {"breakpoint_base": "1.5"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 210
                          },
                          {
                            "id": 213,
                            "data": {
                              "type": "EssentialElements\\Link",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "(03) 9111 2399",
                                    "link": {"url": "tel:+61391112399", "type": "url"}
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#cccccc"},
                                    "textDecoration": {"breakpoint_base": "none"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 210
                          }
                        ],
                        "_parentId": 205
                      },
                      {
                        "id": 214,
                        "data": {
                          "type": "EssentialElements\\Div",
                          "properties": {
                            "design": {
                              "layout": {
                                "display": {"breakpoint_base": "flex"},
                                "flexDirection": {"breakpoint_base": "column"},
                                "verticalGap": {"breakpoint_base": "8px"}
                              }
                            }
                          }
                        },
                        "children": [
                          {
                            "id": 215,
                            "data": {
                              "type": "EssentialElements\\Heading",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "CoLabs Lab Space",
                                    "tags": "h4"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "14px"},
                                    "fontWeight": {"breakpoint_base": "500"},
                                    "color": {"breakpoint_base": "#ffffff"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 214
                          },
                          {
                            "id": 216,
                            "data": {
                              "type": "EssentialElements\\Text",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "20/306 Albert St, Brunswick"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#999999"},
                                    "lineHeight": {"breakpoint_base": "1.5"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 214
                          },
                          {
                            "id": 217,
                            "data": {
                              "type": "EssentialElements\\Link",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "(03) 9111 2399",
                                    "link": {"url": "tel:+61391112399", "type": "url"}
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#cccccc"},
                                    "textDecoration": {"breakpoint_base": "none"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 214
                          }
                        ],
                        "_parentId": 205
                      },
                      {
                        "id": 218,
                        "data": {
                          "type": "EssentialElements\\Div",
                          "properties": {
                            "design": {
                              "layout": {
                                "display": {"breakpoint_base": "flex"},
                                "flexDirection": {"breakpoint_base": "column"},
                                "verticalGap": {"breakpoint_base": "8px"}
                              }
                            }
                          }
                        },
                        "children": [
                          {
                            "id": 219,
                            "data": {
                              "type": "EssentialElements\\Heading",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "CoLabs Notting Hill",
                                    "tags": "h4"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "14px"},
                                    "fontWeight": {"breakpoint_base": "500"},
                                    "color": {"breakpoint_base": "#ffffff"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 218
                          },
                          {
                            "id": 220,
                            "data": {
                              "type": "EssentialElements\\Text",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "2 Acacia Place, Notting Hill"
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#999999"},
                                    "lineHeight": {"breakpoint_base": "1.5"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 218
                          },
                          {
                            "id": 221,
                            "data": {
                              "type": "EssentialElements\\Link",
                              "properties": {
                                "content": {
                                  "content": {
                                    "text": "(03) 9111 2399",
                                    "link": {"url": "tel:+61391112399", "type": "url"}
                                  }
                                },
                                "design": {
                                  "typography": {
                                    "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                    "fontSize": {"breakpoint_base": "13px"},
                                    "fontWeight": {"breakpoint_base": "400"},
                                    "color": {"breakpoint_base": "#cccccc"},
                                    "textDecoration": {"breakpoint_base": "none"}
                                  }
                                }
                              }
                            },
                            "children": [],
                            "_parentId": 218
                          }
                        ],
                        "_parentId": 205
                      }
                    ],
                    "_parentId": 202
                  }
                ],
                "_parentId": 201
              },
              {
                "id": 222,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "size": {
                        "width": {"breakpoint_base": "100%"},
                        "height": {"breakpoint_base": "1px"}
                      },
                      "background": {
                        "color": "#333333"
                      },
                      "spacing": {
                        "margin_top": {"breakpoint_base": "48px", "breakpoint_tablet_portrait": "32px"},
                        "margin_bottom": {"breakpoint_base": "48px", "breakpoint_tablet_portrait": "32px"}
                      }
                    }
                  }
                },
                "children": [],
                "_parentId": 201
              },
              {
                "id": 223,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "layout": {
                        "display": {"breakpoint_base": "flex"},
                        "flexDirection": {"breakpoint_base": "row", "breakpoint_tablet_portrait": "column"},
                        "justifyContent": {"breakpoint_base": "space-between"},
                        "alignItems": {"breakpoint_base": "center", "breakpoint_tablet_portrait": "flex-start"},
                        "horizontalGap": {"breakpoint_base": "32px", "breakpoint_tablet_portrait": "24px"}
                      }
                    }
                  }
                },
                "children": [
                  {
                    "id": 224,
                    "data": {
                      "type": "EssentialElements\\Div",
                      "properties": {
                        "design": {
                          "layout": {
                            "display": {"breakpoint_base": "flex"},
                            "alignItems": {"breakpoint_base": "center"},
                            "horizontalGap": {"breakpoint_base": "8px"}
                          }
                        }
                      }
                    },
                    "children": [
                      {
                        "id": 225,
                        "data": {
                          "type": "EssentialElements\\Text",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Brand and website by"
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "13px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#999999"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 224
                      },
                      {
                        "id": 226,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Your Creative",
                                "link": {"url": "https://yourcreative.com.au", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "13px"},
                                "fontWeight": {"breakpoint_base": "500"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 224
                      }
                    ],
                    "_parentId": 223
                  },
                  {
                    "id": 227,
                    "data": {
                      "type": "EssentialElements\\Div",
                      "properties": {
                        "design": {
                          "layout": {
                            "display": {"breakpoint_base": "flex"},
                            "flexWrap": {"breakpoint_base": "wrap"},
                            "alignItems": {"breakpoint_base": "center"},
                            "horizontalGap": {"breakpoint_base": "24px", "breakpoint_tablet_portrait": "16px"}
                          }
                        }
                      }
                    },
                    "children": [
                      {
                        "id": 228,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Services",
                                "link": {"url": "/services", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              },
                              "effects": {
                                "transition": {
                                  "duration": {"breakpoint_base": "150ms"},
                                  "timingFunction": {"breakpoint_base": "ease-out"}
                                }
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      },
                      {
                        "id": 229,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Privacy Policy",
                                "link": {"url": "/privacy-policy", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      },
                      {
                        "id": 230,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Our Principles",
                                "link": {"url": "/about/our-principles", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      },
                      {
                        "id": 231,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Terms and Conditions",
                                "link": {"url": "/terms-and-conditions", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      },
                      {
                        "id": 232,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "About",
                                "link": {"url": "/about", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      },
                      {
                        "id": 233,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Journal",
                                "link": {"url": "/resources/journal", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      },
                      {
                        "id": 234,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "Contact",
                                "link": {"url": "/contact", "type": "url"}
                              }
                            },
                            "design": {
                              "typography": {
                                "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                                "fontSize": {"breakpoint_base": "14px"},
                                "fontWeight": {"breakpoint_base": "400"},
                                "color": {"breakpoint_base": "#cccccc"},
                                "textDecoration": {"breakpoint_base": "none"},
                                "letterSpacing": {"breakpoint_base": "0.01em"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 227
                      }
                    ],
                    "_parentId": 223
                  }
                ],
                "_parentId": 201
              },
              {
                "id": 235,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "size": {
                        "width": {"breakpoint_base": "100%"},
                        "height": {"breakpoint_base": "1px"}
                      },
                      "background": {
                        "color": "#333333"
                      },
                      "spacing": {
                        "margin_top": {"breakpoint_base": "48px", "breakpoint_tablet_portrait": "32px"},
                        "margin_bottom": {"breakpoint_base": "48px", "breakpoint_tablet_portrait": "32px"}
                      }
                    }
                  }
                },
                "children": [],
                "_parentId": 201
              },
              {
                "id": 236,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "layout": {
                        "display": {"breakpoint_base": "flex"},
                        "flexDirection": {"breakpoint_base": "row", "breakpoint_tablet_portrait": "column-reverse"},
                        "justifyContent": {"breakpoint_base": "space-between"},
                        "alignItems": {"breakpoint_base": "center", "breakpoint_tablet_portrait": "flex-start"},
                        "horizontalGap": {"breakpoint_base": "24px", "breakpoint_tablet_portrait": "16px"}
                      }
                    }
                  }
                },
                "children": [
                  {
                    "id": 237,
                    "data": {
                      "type": "EssentialElements\\Text",
                      "properties": {
                        "content": {
                          "content": {
                            "text": "© 2026 CoLabs"
                          }
                        },
                        "design": {
                          "typography": {
                            "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                            "fontSize": {"breakpoint_base": "13px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#999999"}
                          }
                        }
                      }
                    },
                    "children": [],
                    "_parentId": 236
                  },
                  {
                    "id": 238,
                    "data": {
                      "type": "EssentialElements\\Div",
                      "properties": {
                        "design": {
                          "layout": {
                            "display": {"breakpoint_base": "flex"},
                            "alignItems": {"breakpoint_base": "center"},
                            "horizontalGap": {"breakpoint_base": "16px"}
                          }
                        }
                      }
                    },
                    "children": [
                      {
                        "id": 239,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://instagram.com/colabs.aus/", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#999999"}
                              },
                              "size": {
                                "width": {"breakpoint_base": "20px"},
                                "height": {"breakpoint_base": "20px"}
                              },
                              "effects": {
                                "transition": {
                                  "duration": {"breakpoint_base": "150ms"},
                                  "timingFunction": {"breakpoint_base": "ease-out"}
                                }
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 238
                      },
                      {
                        "id": 240,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://facebook.com/colabs.australia", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#999999"}
                              },
                              "size": {
                                "width": {"breakpoint_base": "20px"},
                                "height": {"breakpoint_base": "20px"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 238
                      },
                      {
                        "id": 241,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://linkedin.com/company/colabsaustralia/", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#999999"}
                              },
                              "size": {
                                "width": {"breakpoint_base": "20px"},
                                "height": {"breakpoint_base": "20px"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 238
                      },
                      {
                        "id": 242,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://twitter.com/CoLabsaus", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#999999"}
                              },
                              "size": {
                                "width": {"breakpoint_base": "20px"},
                                "height": {"breakpoint_base": "20px"}
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 238
                      }
                    ],
                    "_parentId": 236
                  }
                ],
                "_parentId": 201
              }
            ],
            "_parentId": 200
          }
        ],
        "_parentId": 1
      }
    ]
  },
  "status": "exported"
}
```

---

## Location Data Reference

| Location | Name | Address | Phone |
|----------|------|---------|-------|
| 1 | CoLabs Coworking | 1/306 Albert St, Brunswick | (03) 9111 2399 |
| 2 | CoLabs Office | 17/306 Albert St, Brunswick | (03) 9111 2399 |
| 3 | CoLabs Lab Space | 20/306 Albert St, Brunswick | (03) 9111 2399 |
| 4 | CoLabs Notting Hill | 2 Acacia Place, Notting Hill | (03) 9111 2399 |

---

## Navigation Links Reference

| Link Text | URL |
|-----------|-----|
| Services | `/services` |
| Privacy Policy | `/privacy-policy` |
| Our Principles | `/about/our-principles` |
| Terms and Conditions | `/terms-and-conditions` |
| About | `/about` |
| Journal | `/resources/journal` |
| Contact | `/contact` |

---

## Social Links Reference

| Platform | URL |
|----------|-----|
| Instagram | `https://instagram.com/colabs.aus/` |
| Facebook | `https://facebook.com/colabs.australia` |
| LinkedIn | `https://linkedin.com/company/colabsaustralia/` |
| Twitter | `https://twitter.com/CoLabsaus` |

---

## Implementation Notes

### Font Loading

The Matter SQ font is a custom font. For replication, either:
1. License and load Matter SQ font
2. Use a similar alternative: `Inter`, `DM Sans`, or `Space Grotesk`

### Social Icons

Social icons should use SVG icons for:
- Instagram
- Facebook
- LinkedIn
- Twitter/X

Recommended icon libraries:
- Feather Icons
- Heroicons
- Font Awesome

### Accessibility Requirements

- All interactive elements must be keyboard accessible
- Phone links use `tel:` protocol for mobile tap-to-call
- External links should indicate they open in new tabs
- Color contrast ratios must meet WCAG AA (4.5:1 for text)
- Footer navigation should be wrapped in `<nav>` element

### Browser Support

Target browsers:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## Verification Checklist

Before considering footer replication complete:

- [ ] Dark background displays correctly (#1a1a1a)
- [ ] Acknowledgment text is properly styled and readable
- [ ] All 4 location cards display correctly
- [ ] Location names use correct font weight (500)
- [ ] Phone numbers are clickable tel: links
- [ ] Horizontal dividers display correctly
- [ ] Brand attribution displays correctly
- [ ] All 7 navigation links work correctly
- [ ] Social icons display and link correctly
- [ ] Copyright text displays correctly
- [ ] Responsive breakpoints work correctly
- [ ] Mobile layout stacks correctly
- [ ] Colors match specification
- [ ] Typography matches specification
- [ ] Spacing matches specification
- [ ] Hover states work on all links

---

*Last updated: 2026-01-25*
*Version: 1.0.0*
