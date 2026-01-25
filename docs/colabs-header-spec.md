# CoLabs.com.au Header Specification

This document provides comprehensive documentation for replicating the colabs.com.au header via OxyBridge API. It includes element hierarchy, visual design specifications, and complete JSON tree structure for pixel-accurate recreation.

## Table of Contents

1. [Overview](#overview)
2. [Element Hierarchy](#element-hierarchy)
3. [Visual Design Tokens](#visual-design-tokens)
4. [Element Specifications](#element-specifications)
5. [Responsive Behavior](#responsive-behavior)
6. [OxyBridge JSON Tree Structure](#oxybridge-json-tree-structure)

---

## Overview

The CoLabs header is a minimalist, full-width navigation component featuring:
- **Logo**: Text-based "Colabs" wordmark (not an image)
- **Primary Navigation**: 6 main items with dropdown menus
- **CTA Button**: "Join the Lab" action button
- **Social Links**: 4 social media icons
- **Mobile Menu**: Collapsible hamburger menu for smaller screens

### Design Philosophy

The header follows a clean, modern aesthetic with:
- Generous whitespace
- Subtle typography hierarchy
- Understated color palette (neutrals with accent colors)
- Smooth hover transitions
- Mobile-first responsive approach

---

## Element Hierarchy

```
Header (Section)
├── Container (Div) - max-width container
│   ├── Logo (Link/Text) - "Colabs" wordmark
│   │
│   ├── Navigation (Div) - flex container
│   │   ├── Home (Link)
│   │   ├── Services (Dropdown)
│   │   │   ├── Lab Space
│   │   │   ├── Build a Lab
│   │   │   ├── Innovation Facilitation
│   │   │   └── Office Space
│   │   ├── Sites (Dropdown)
│   │   │   ├── Brunswick
│   │   │   └── Notting Hill
│   │   ├── About (Dropdown)
│   │   │   ├── Our Principles
│   │   │   └── Our Story
│   │   ├── Resources (Dropdown)
│   │   │   ├── Our Community
│   │   │   └── Journal
│   │   └── Contact (Link)
│   │
│   ├── Actions (Div) - right-aligned container
│   │   ├── Social Links (Div)
│   │   │   ├── Instagram (Icon/Link)
│   │   │   ├── Facebook (Icon/Link)
│   │   │   ├── LinkedIn (Icon/Link)
│   │   │   └── Twitter (Icon/Link)
│   │   └── CTA Button (Button) - "Join the Lab"
│   │
│   └── Mobile Menu Toggle (Div) - hidden on desktop
│       └── Hamburger Icon
```

---

## Visual Design Tokens

### Colors

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-bg-header` | `#ffffff` | Header background |
| `--colabs-text-primary` | `#1a1a1a` | Logo, nav links |
| `--colabs-text-secondary` | `#666666` | Dropdown items |
| `--colabs-text-hover` | `#000000` | Link hover state |
| `--colabs-accent-cobalt` | `#2c4bff` | Service card (Lab Space) |
| `--colabs-accent-clay` | `#c76b4a` | Service card (Build a Lab) |
| `--colabs-accent-khaki` | `#b5a642` | Service card (Innovation) |
| `--colabs-accent-lilac` | `#9c7eb8` | Service card (Office Space) |
| `--colabs-cta-bg` | `#1a1a1a` | CTA button background |
| `--colabs-cta-text` | `#ffffff` | CTA button text |
| `--colabs-cta-hover-bg` | `#333333` | CTA button hover background |
| `--colabs-dropdown-bg` | `#ffffff` | Dropdown panel background |
| `--colabs-dropdown-border` | `#e5e5e5` | Dropdown border |
| `--colabs-divider` | `#e5e5e5` | Separator lines |

### Typography

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-font-family` | `'Matter SQ', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif` | All text |
| `--colabs-font-logo` | `600` | Logo font weight |
| `--colabs-font-nav` | `400` | Navigation font weight |
| `--colabs-font-cta` | `500` | CTA button font weight |
| `--colabs-size-logo` | `24px` | Logo font size |
| `--colabs-size-nav` | `15px` | Navigation font size |
| `--colabs-size-dropdown` | `14px` | Dropdown item font size |
| `--colabs-size-cta` | `14px` | CTA button font size |
| `--colabs-letter-spacing` | `0.01em` | Navigation letter spacing |
| `--colabs-line-height` | `1.5` | Default line height |

### Spacing

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-header-height` | `80px` | Header height (desktop) |
| `--colabs-header-height-mobile` | `64px` | Header height (mobile) |
| `--colabs-container-max` | `1440px` | Max container width |
| `--colabs-container-padding` | `40px` | Container horizontal padding |
| `--colabs-container-padding-mobile` | `20px` | Mobile padding |
| `--colabs-nav-gap` | `32px` | Gap between nav items |
| `--colabs-social-gap` | `16px` | Gap between social icons |
| `--colabs-dropdown-padding` | `24px` | Dropdown panel padding |
| `--colabs-dropdown-item-padding` | `12px 0` | Dropdown item padding |

### Effects

| Token | Value | Usage |
|-------|-------|-------|
| `--colabs-transition-fast` | `150ms` | Quick interactions |
| `--colabs-transition-medium` | `250ms` | Standard transitions |
| `--colabs-transition-timing` | `ease-out` | Easing function |
| `--colabs-shadow-dropdown` | `0 10px 40px rgba(0,0,0,0.1)` | Dropdown shadow |
| `--colabs-border-radius-cta` | `24px` | CTA button radius (pill) |
| `--colabs-border-radius-dropdown` | `8px` | Dropdown panel radius |

---

## Element Specifications

### Header Container (Section)

| Property | Value | Responsive |
|----------|-------|------------|
| Position | `fixed` / `sticky` | - |
| Top | `0` | - |
| Width | `100%` | - |
| Height | `80px` | `64px` (mobile) |
| Background | `#ffffff` | - |
| Z-Index | `1000` | - |
| Border Bottom | `1px solid #e5e5e5` (optional) | - |

**OxyBridge Properties:**
```json
{
  "backgroundColor": "#ffffff",
  "position": "fixed",
  "top": "0",
  "width": "100%",
  "zIndex": 1000
}
```

### Inner Container (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | - |
| Align Items | `center` | - |
| Justify Content | `space-between` | - |
| Max Width | `1440px` | - |
| Height | `100%` | - |
| Margin | `0 auto` | - |
| Padding | `0 40px` | `0 20px` (mobile) |

**OxyBridge Properties:**
```json
{
  "display": "flex",
  "alignItems": "center",
  "justifyContent": "space-between",
  "maxWidth": "1440px",
  "height": "100%",
  "marginLeft": "auto",
  "marginRight": "auto",
  "paddingLeft": "40px",
  "paddingRight": "40px"
}
```

### Logo (Link/Heading)

| Property | Value |
|----------|-------|
| Font Family | `'Matter SQ', sans-serif` |
| Font Size | `24px` |
| Font Weight | `600` |
| Color | `#1a1a1a` |
| Text Decoration | `none` |
| Cursor | `pointer` |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Link",
  "content": {
    "content": {
      "text": "Colabs",
      "link": {"url": "/", "type": "url"}
    }
  },
  "design": {
    "typography": {
      "fontFamily": "'Matter SQ', sans-serif",
      "fontSize": {"breakpoint_base": "24px"},
      "fontWeight": "600",
      "color": {"breakpoint_base": "#1a1a1a"},
      "textDecoration": "none"
    }
  }
}
```

### Navigation Container (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | `none` (mobile) |
| Align Items | `center` | - |
| Gap | `32px` | - |

### Navigation Link (Link)

| Property | Value | Hover |
|----------|-------|-------|
| Font Family | `'Matter SQ', sans-serif` | - |
| Font Size | `15px` | - |
| Font Weight | `400` | - |
| Color | `#1a1a1a` | `#000000` |
| Text Decoration | `none` | `none` |
| Text Transform | `none` (normal case) | - |
| Letter Spacing | `0.01em` | - |
| Transition | `color 150ms ease-out` | - |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Link",
  "content": {
    "content": {
      "text": "Home",
      "link": {"url": "/", "type": "url"}
    }
  },
  "design": {
    "typography": {
      "fontFamily": "'Matter SQ', sans-serif",
      "fontSize": {"breakpoint_base": "15px"},
      "fontWeight": "400",
      "color": {"breakpoint_base": "#1a1a1a"},
      "colorHover": "#000000",
      "textDecoration": "none",
      "letterSpacing": "0.01em"
    },
    "effects": {
      "transitionDuration": "150ms",
      "transitionTiming": "ease-out"
    }
  }
}
```

### Dropdown Trigger (Div)

| Property | Value |
|----------|-------|
| Display | `flex` |
| Align Items | `center` |
| Gap | `4px` |
| Cursor | `pointer` |

Contains navigation text and dropdown chevron icon.

### Dropdown Panel (Div)

| Property | Value |
|----------|-------|
| Position | `absolute` |
| Top | `100%` |
| Left | `0` |
| Min Width | `200px` |
| Background | `#ffffff` |
| Border Radius | `8px` |
| Box Shadow | `0 10px 40px rgba(0,0,0,0.1)` |
| Padding | `24px` |
| Opacity | `0` (closed) / `1` (open) |
| Visibility | `hidden` (closed) / `visible` (open) |
| Transform | `translateY(10px)` (closed) / `translateY(0)` (open) |
| Transition | `all 250ms ease-out` |

**OxyBridge Properties:**
```json
{
  "position": "absolute",
  "top": "100%",
  "left": "0",
  "minWidth": "200px",
  "backgroundColor": "#ffffff",
  "borderRadiusAll": "8px",
  "boxShadow": "0 10px 40px rgba(0,0,0,0.1)",
  "paddingTop": "24px",
  "paddingBottom": "24px",
  "paddingLeft": "24px",
  "paddingRight": "24px",
  "opacity": 0,
  "translateY": "10px",
  "transitionDuration": "250ms",
  "transitionTiming": "ease-out"
}
```

### Dropdown Item (Link)

| Property | Value | Hover |
|----------|-------|-------|
| Display | `block` |
| Font Size | `14px` | - |
| Font Weight | `400` | - |
| Color | `#666666` | `#1a1a1a` |
| Padding | `12px 0` | - |
| Border Bottom | `1px solid #e5e5e5` (except last) | - |
| Transition | `color 150ms ease-out` | - |

### Social Links Container (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `flex` | `none` (mobile) |
| Align Items | `center` | - |
| Gap | `16px` | - |

### Social Icon (Link/Icon)

| Property | Value | Hover |
|----------|-------|-------|
| Width | `20px` | - |
| Height | `20px` | - |
| Color | `#666666` | `#1a1a1a` |
| Opacity | `0.8` | `1` |
| Transition | `all 150ms ease-out` | - |

### CTA Button (Button)

| Property | Value | Hover |
|----------|-------|-------|
| Display | `inline-flex` | - |
| Align Items | `center` | - |
| Justify Content | `center` | - |
| Padding | `12px 24px` | - |
| Background | `#1a1a1a` | `#333333` |
| Color | `#ffffff` | `#ffffff` |
| Font Size | `14px` | - |
| Font Weight | `500` | - |
| Border Radius | `24px` (pill) | - |
| Border | `none` | - |
| Cursor | `pointer` | - |
| Transition | `background 150ms ease-out` | - |

**OxyBridge Properties:**
```json
{
  "type": "EssentialElements\\Button",
  "content": {
    "content": {
      "text": "Join the Lab",
      "link": {"url": "/contact", "type": "url"}
    }
  },
  "design": {
    "button": {
      "background": {"breakpoint_base": "#1a1a1a"},
      "backgroundHover": "#333333",
      "typography": {
        "fontFamily": "'Matter SQ', sans-serif",
        "fontSize": {"breakpoint_base": "14px"},
        "fontWeight": "500",
        "color": {"breakpoint_base": "#ffffff"}
      }
    },
    "spacing": {
      "padding": {
        "top": {"breakpoint_base": "12px"},
        "bottom": {"breakpoint_base": "12px"},
        "left": {"breakpoint_base": "24px"},
        "right": {"breakpoint_base": "24px"}
      }
    },
    "borders": {
      "radius": {"all": {"breakpoint_base": {"number": 24, "unit": "px"}}}
    },
    "effects": {
      "transitionDuration": "150ms",
      "transitionTiming": "ease-out"
    }
  }
}
```

### Mobile Menu Toggle (Div)

| Property | Value | Responsive |
|----------|-------|------------|
| Display | `none` | `flex` (tablet and below) |
| Align Items | `center` |
| Cursor | `pointer` |
| Width | `44px` |
| Height | `44px` |

---

## Responsive Behavior

### Breakpoints

| Breakpoint | Width | Changes |
|------------|-------|---------|
| Desktop | `1120px+` | Full navigation visible |
| Tablet Landscape | `1024px` | Navigation starts to compress |
| Tablet Portrait | `768px` | **Mobile nav mode** - hamburger menu |
| Phone Landscape | `480px` | Reduced padding |
| Phone Portrait | `320px` | Minimal header |

### Desktop (1120px+)

- Full horizontal navigation visible
- All dropdown menus accessible on hover
- Social icons visible
- CTA button visible

### Tablet Portrait (768px) and Below

| Element | Change |
|---------|--------|
| Navigation | Hidden, replaced with hamburger menu |
| Social Icons | Hidden in header, moved to mobile menu |
| CTA Button | Hidden in header, moved to mobile menu |
| Header Height | Reduced to `64px` |
| Container Padding | Reduced to `20px` |
| Logo Size | Reduced to `20px` |

### Mobile Menu (Overlay)

When hamburger is clicked:

| Property | Value |
|----------|-------|
| Position | `fixed` |
| Top | `64px` |
| Left | `0` |
| Width | `100%` |
| Height | `calc(100vh - 64px)` |
| Background | `#ffffff` |
| Padding | `24px 20px` |
| Overflow | `auto` |
| Z-Index | `999` |

Mobile menu items are stacked vertically with larger touch targets.

---

## OxyBridge JSON Tree Structure

### Complete Header Tree

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
        "id": 100,
        "data": {
          "type": "EssentialElements\\Section",
          "properties": {
            "design": {
              "background": {
                "color": "#ffffff"
              },
              "size": {
                "width": {"breakpoint_base": "100%"},
                "minHeight": {"breakpoint_base": "80px", "breakpoint_tablet_portrait": "64px"}
              },
              "layout": {
                "display": {"breakpoint_base": "flex"},
                "alignItems": {"breakpoint_base": "center"},
                "justifyContent": {"breakpoint_base": "center"}
              }
            }
          }
        },
        "children": [
          {
            "id": 101,
            "data": {
              "type": "EssentialElements\\Div",
              "properties": {
                "design": {
                  "layout": {
                    "display": {"breakpoint_base": "flex"},
                    "alignItems": {"breakpoint_base": "center"},
                    "justifyContent": {"breakpoint_base": "space-between"},
                    "horizontalGap": {"breakpoint_base": "32px"}
                  },
                  "size": {
                    "width": {"breakpoint_base": "100%"},
                    "maxWidth": {"breakpoint_base": {"number": 1440, "unit": "px"}}
                  },
                  "spacing": {
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
                "id": 102,
                "data": {
                  "type": "EssentialElements\\Link",
                  "properties": {
                    "content": {
                      "content": {
                        "text": "Colabs",
                        "link": {"url": "/", "type": "url"}
                      }
                    },
                    "design": {
                      "typography": {
                        "fontFamily": {"breakpoint_base": "'Matter SQ', -apple-system, sans-serif"},
                        "fontSize": {"breakpoint_base": "24px", "breakpoint_tablet_portrait": "20px"},
                        "fontWeight": {"breakpoint_base": "600"},
                        "color": {"breakpoint_base": "#1a1a1a"},
                        "textDecoration": {"breakpoint_base": "none"}
                      }
                    }
                  }
                },
                "children": [],
                "_parentId": 101
              },
              {
                "id": 103,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "layout": {
                        "display": {"breakpoint_base": "flex", "breakpoint_tablet_portrait": "none"},
                        "alignItems": {"breakpoint_base": "center"},
                        "horizontalGap": {"breakpoint_base": "32px"}
                      }
                    }
                  }
                },
                "children": [
                  {
                    "id": 104,
                    "data": {
                      "type": "EssentialElements\\Link",
                      "properties": {
                        "content": {
                          "content": {
                            "text": "Home",
                            "link": {"url": "/", "type": "url"}
                          }
                        },
                        "design": {
                          "typography": {
                            "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                            "fontSize": {"breakpoint_base": "15px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#1a1a1a"},
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
                    "_parentId": 103
                  },
                  {
                    "id": 105,
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
                            "fontSize": {"breakpoint_base": "15px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#1a1a1a"},
                            "textDecoration": {"breakpoint_base": "none"},
                            "letterSpacing": {"breakpoint_base": "0.01em"}
                          }
                        }
                      }
                    },
                    "children": [],
                    "_parentId": 103
                  },
                  {
                    "id": 106,
                    "data": {
                      "type": "EssentialElements\\Link",
                      "properties": {
                        "content": {
                          "content": {
                            "text": "Sites",
                            "link": {"url": "/sites", "type": "url"}
                          }
                        },
                        "design": {
                          "typography": {
                            "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                            "fontSize": {"breakpoint_base": "15px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#1a1a1a"},
                            "textDecoration": {"breakpoint_base": "none"},
                            "letterSpacing": {"breakpoint_base": "0.01em"}
                          }
                        }
                      }
                    },
                    "children": [],
                    "_parentId": 103
                  },
                  {
                    "id": 107,
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
                            "fontSize": {"breakpoint_base": "15px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#1a1a1a"},
                            "textDecoration": {"breakpoint_base": "none"},
                            "letterSpacing": {"breakpoint_base": "0.01em"}
                          }
                        }
                      }
                    },
                    "children": [],
                    "_parentId": 103
                  },
                  {
                    "id": 108,
                    "data": {
                      "type": "EssentialElements\\Link",
                      "properties": {
                        "content": {
                          "content": {
                            "text": "Resources",
                            "link": {"url": "/resources", "type": "url"}
                          }
                        },
                        "design": {
                          "typography": {
                            "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                            "fontSize": {"breakpoint_base": "15px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#1a1a1a"},
                            "textDecoration": {"breakpoint_base": "none"},
                            "letterSpacing": {"breakpoint_base": "0.01em"}
                          }
                        }
                      }
                    },
                    "children": [],
                    "_parentId": 103
                  },
                  {
                    "id": 109,
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
                            "fontSize": {"breakpoint_base": "15px"},
                            "fontWeight": {"breakpoint_base": "400"},
                            "color": {"breakpoint_base": "#1a1a1a"},
                            "textDecoration": {"breakpoint_base": "none"},
                            "letterSpacing": {"breakpoint_base": "0.01em"}
                          }
                        }
                      }
                    },
                    "children": [],
                    "_parentId": 103
                  }
                ],
                "_parentId": 101
              },
              {
                "id": 110,
                "data": {
                  "type": "EssentialElements\\Div",
                  "properties": {
                    "design": {
                      "layout": {
                        "display": {"breakpoint_base": "flex", "breakpoint_tablet_portrait": "none"},
                        "alignItems": {"breakpoint_base": "center"},
                        "horizontalGap": {"breakpoint_base": "24px"}
                      }
                    }
                  }
                },
                "children": [
                  {
                    "id": 111,
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
                        "id": 112,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://instagram.com/colabs", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#666666"}
                              },
                              "effects": {
                                "opacity": {"breakpoint_base": 0.8},
                                "opacityHover": 1,
                                "transition": {
                                  "duration": {"breakpoint_base": "150ms"}
                                }
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 111
                      },
                      {
                        "id": 113,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://facebook.com/colabs", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#666666"}
                              },
                              "effects": {
                                "opacity": {"breakpoint_base": 0.8},
                                "opacityHover": 1
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 111
                      },
                      {
                        "id": 114,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://linkedin.com/company/colabs", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#666666"}
                              },
                              "effects": {
                                "opacity": {"breakpoint_base": 0.8},
                                "opacityHover": 1
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 111
                      },
                      {
                        "id": 115,
                        "data": {
                          "type": "EssentialElements\\Link",
                          "properties": {
                            "content": {
                              "content": {
                                "text": "",
                                "link": {"url": "https://twitter.com/colabs", "type": "url", "openInNewTab": true}
                              }
                            },
                            "design": {
                              "typography": {
                                "color": {"breakpoint_base": "#666666"}
                              },
                              "effects": {
                                "opacity": {"breakpoint_base": 0.8},
                                "opacityHover": 1
                              }
                            }
                          }
                        },
                        "children": [],
                        "_parentId": 111
                      }
                    ],
                    "_parentId": 110
                  },
                  {
                    "id": 116,
                    "data": {
                      "type": "EssentialElements\\Button",
                      "properties": {
                        "content": {
                          "content": {
                            "text": "Join the Lab",
                            "link": {"url": "/contact", "type": "url"}
                          }
                        },
                        "design": {
                          "button": {
                            "background": {"breakpoint_base": "#1a1a1a"},
                            "backgroundHover": "#333333",
                            "typography": {
                              "fontFamily": {"breakpoint_base": "'Matter SQ', sans-serif"},
                              "fontSize": {"breakpoint_base": "14px"},
                              "fontWeight": {"breakpoint_base": "500"},
                              "color": {"breakpoint_base": "#ffffff"}
                            }
                          },
                          "spacing": {
                            "padding": {
                              "top": {"breakpoint_base": "12px"},
                              "bottom": {"breakpoint_base": "12px"},
                              "left": {"breakpoint_base": "24px"},
                              "right": {"breakpoint_base": "24px"}
                            }
                          },
                          "borders": {
                            "radius": {"all": {"breakpoint_base": {"number": 24, "unit": "px"}}}
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
                    "_parentId": 110
                  }
                ],
                "_parentId": 101
              }
            ],
            "_parentId": 100
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

## Dropdown Menu Structure (Expanded)

For dropdown functionality, each dropdown trigger requires:

### Services Dropdown Items

| Item | URL | Color Accent |
|------|-----|--------------|
| Lab Space | `/services/lab-space` | Cobalt (`#2c4bff`) |
| Build a Lab | `/services/build-a-lab` | Clay (`#c76b4a`) |
| Innovation Facilitation | `/services/innovation-facilitation` | Khaki (`#b5a642`) |
| Office Space | `/services/office-space` | Lilac (`#9c7eb8`) |

### Sites Dropdown Items

| Item | URL |
|------|-----|
| Brunswick | `/sites/brunswick` |
| Notting Hill | `/sites/notting-hill` |

### About Dropdown Items

| Item | URL |
|------|-----|
| Our Principles | `/about/our-principles` |
| Our Story | `/about/our-story` |

### Resources Dropdown Items

| Item | URL |
|------|-----|
| Our Community | `/resources/our-community` |
| Journal | `/resources/journal` |

---

## Implementation Notes

### Font Loading

The Matter SQ font is a custom font. For replication, either:
1. License and load Matter SQ font
2. Use a similar alternative: `Inter`, `DM Sans`, or `Space Grotesk`

### Dropdown Behavior

Dropdowns require JavaScript for:
- Hover trigger (desktop)
- Click trigger (mobile)
- Open/close animations
- Keyboard navigation (accessibility)

### Accessibility Requirements

- All interactive elements must be keyboard accessible
- Dropdowns should support `aria-expanded` attribute
- Mobile menu should trap focus when open
- Color contrast ratios must meet WCAG AA (4.5:1 for text)

### Browser Support

Target browsers:
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

---

## Verification Checklist

Before considering header replication complete:

- [ ] Logo displays correctly as text
- [ ] Navigation items are properly spaced
- [ ] Hover states work on all links
- [ ] CTA button has correct styling and hover effect
- [ ] Social icons display correctly
- [ ] Responsive breakpoints work correctly
- [ ] Mobile menu toggle appears at correct breakpoint
- [ ] Colors match specification
- [ ] Typography matches specification
- [ ] Spacing matches specification

---

*Last updated: 2025-01-25*
*Version: 1.0.0*
