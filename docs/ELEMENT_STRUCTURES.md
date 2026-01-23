# Breakdance/Oxygen Element Data Structures

This document provides the complete data structure reference for all Breakdance/Oxygen elements. Use this when creating pages programmatically via the Oxybridge REST API.

## Table of Contents

1. [Key Concepts](#key-concepts)
2. [Layout Elements](#layout-elements)
3. [Text Elements](#text-elements)
4. [Button & Interactive Elements](#button--interactive-elements)
5. [Media Elements](#media-elements)
6. [Slider Elements](#slider-elements)
7. [List Elements](#list-elements)
8. [Advanced Elements](#advanced-elements)
9. [Property Patterns](#property-patterns)
10. [Breakpoint System](#breakpoint-system)

---

## Key Concepts

### Element Structure

Every element follows this base structure:

```json
{
  "id": "unique-element-id",
  "data": {
    "type": "EssentialElements\\ElementName",
    "properties": {
      "content": { },
      "design": { }
    }
  },
  "children": []
}
```

### Critical: Double-Nested Content

Text content uses **double-nested** `content.content.text` path:

```json
{
  "properties": {
    "content": {
      "content": {
        "text": "Your text here"
      }
    }
  }
}
```

### Breakpoint Values

Style values use `breakpoint_base` for responsive design:

```json
{
  "design": {
    "typography": {
      "color": {
        "breakpoint_base": "#ffffff"
      }
    }
  }
}
```

---

## Layout Elements

### Section (`EssentialElements\\Section`)

Container element for page sections.

```json
{
  "id": "section-1",
  "data": {
    "type": "EssentialElements\\Section",
    "properties": {
      "design": {
        "layout_v2": {
          "layout": "vertical"
        },
        "background": {
          "color": "#0d1424",
          "type": "color"
        },
        "size": {
          "width": "contained",
          "height": "fit-content",
          "minHeight": {
            "breakpoint_base": "100vh"
          }
        },
        "spacing": {
          "padding": {
            "top": {"breakpoint_base": "100px"},
            "bottom": {"breakpoint_base": "100px"},
            "left": {"breakpoint_base": "60px"},
            "right": {"breakpoint_base": "60px"}
          },
          "margin_top": {"breakpoint_base": "0px"},
          "margin_bottom": {"breakpoint_base": "0px"}
        },
        "text_colors": {
          "headings": {"breakpoint_base": "#ffffff"},
          "text": {"breakpoint_base": "#cccccc"},
          "link": {"breakpoint_base": "#0066cc"}
        },
        "borders": {
          "radius": {
            "all": {"breakpoint_base": {"number": 0, "unit": "px"}}
          }
        }
      }
    }
  },
  "children": []
}
```

**Background Types:**
- `color` - Solid color
- `image` - Background image
- `video` - Background video
- `slideshow` - Image slideshow

---

### Div (`EssentialElements\\Div`)

Generic container/wrapper element.

```json
{
  "id": "div-1",
  "data": {
    "type": "EssentialElements\\Div",
    "properties": {
      "design": {
        "layout": {
          "display": {"breakpoint_base": "flex"},
          "flexDirection": {"breakpoint_base": "row"},
          "justifyContent": {"breakpoint_base": "center"},
          "alignItems": {"breakpoint_base": "center"},
          "horizontalGap": {"breakpoint_base": "20px"},
          "verticalGap": {"breakpoint_base": "20px"}
        },
        "size": {
          "width": {"breakpoint_base": "100%"},
          "maxWidth": {"breakpoint_base": {"number": 1200, "unit": "px"}},
          "minHeight": {"breakpoint_base": "auto"}
        },
        "background": {
          "color": "#ffffff"
        },
        "spacing": {
          "padding": {
            "top": {"breakpoint_base": "20px"},
            "bottom": {"breakpoint_base": "20px"},
            "left": {"breakpoint_base": "20px"},
            "right": {"breakpoint_base": "20px"}
          }
        },
        "borders": {
          "radius": {
            "all": {"breakpoint_base": {"number": 8, "unit": "px"}}
          },
          "border": {
            "all": {
              "width": {"breakpoint_base": {"number": 1, "unit": "px"}},
              "style": "solid",
              "color": "#e0e0e0"
            }
          }
        }
      }
    }
  },
  "children": []
}
```

**Display Options:**
- `block`, `flex`, `grid`, `inline-block`, `none`

**Flex Direction:**
- `row`, `column`, `row-reverse`, `column-reverse`

**Grid Layout:**
```json
{
  "layout": {
    "display": {"breakpoint_base": "grid"},
    "gridTemplateColumns": {"breakpoint_base": "repeat(3, 1fr)"},
    "horizontalGap": {"breakpoint_base": "40px"},
    "verticalGap": {"breakpoint_base": "40px"}
  }
}
```

---

### Columns (`EssentialElements\\Columns`)

Multi-column layout container.

```json
{
  "id": "columns-1",
  "data": {
    "type": "EssentialElements\\Columns",
    "properties": {
      "design": {
        "layout": {
          "stack_vertically": {"breakpoint_tablet_portrait": true}
        },
        "size": {
          "width": "contained"
        },
        "spacing": {
          "column_gap": {"breakpoint_base": "30px"},
          "column_padding": {
            "top": {"breakpoint_base": "0px"},
            "bottom": {"breakpoint_base": "0px"}
          }
        }
      }
    }
  },
  "children": []
}
```

---

### Column (`EssentialElements\\Column`)

Individual column within Columns element.

```json
{
  "id": "column-1",
  "data": {
    "type": "EssentialElements\\Column",
    "properties": {
      "design": {
        "size": {
          "width": {"breakpoint_base": "50%"}
        },
        "background": {
          "color": "#f5f5f5"
        },
        "spacing": {
          "padding": {
            "top": {"breakpoint_base": "20px"},
            "bottom": {"breakpoint_base": "20px"},
            "left": {"breakpoint_base": "20px"},
            "right": {"breakpoint_base": "20px"}
          }
        }
      }
    }
  },
  "children": []
}
```

---

### Grid (`EssentialElements\\Grid`)

CSS Grid container.

```json
{
  "id": "grid-1",
  "data": {
    "type": "EssentialElements\\Grid",
    "properties": {
      "design": {
        "grid": {
          "items_per_row": {
            "breakpoint_base": 4,
            "breakpoint_tablet_landscape": 3,
            "breakpoint_tablet_portrait": 2,
            "breakpoint_phone_landscape": 1
          },
          "space_between_items": {"breakpoint_base": "20px"},
          "advanced": {
            "item_vertical_alignment": "stretch"
          }
        },
        "container": {
          "width": "contained"
        }
      }
    }
  },
  "children": []
}
```

---

## Text Elements

### Heading (`EssentialElements\\Heading`)

```json
{
  "id": "heading-1",
  "data": {
    "type": "EssentialElements\\Heading",
    "properties": {
      "content": {
        "content": {
          "text": "Your Heading Text",
          "tags": "h1"
        }
      },
      "design": {
        "typography": {
          "color": {"breakpoint_base": "#000000"},
          "typography": {
            "custom": {
              "customTypography": {
                "fontSize": {"breakpoint_base": {"number": 48, "unit": "px"}},
                "fontWeight": {"breakpoint_base": "700"},
                "lineHeight": {"breakpoint_base": {"number": 1.2, "unit": "em"}},
                "letterSpacing": {"breakpoint_base": {"number": -0.5, "unit": "px"}},
                "textAlign": {"breakpoint_base": "left"},
                "textTransform": "none"
              }
            }
          }
        },
        "size": {
          "width": {"breakpoint_base": "100%"}
        },
        "spacing": {
          "margin_top": {"breakpoint_base": "0px"},
          "margin_bottom": {"breakpoint_base": "20px"}
        }
      }
    }
  },
  "children": []
}
```

**Tag Options:** `h1`, `h2`, `h3`, `h4`, `h5`, `h6`

---

### Text (`EssentialElements\\Text`)

```json
{
  "id": "text-1",
  "data": {
    "type": "EssentialElements\\Text",
    "properties": {
      "content": {
        "content": {
          "text": "Your paragraph text goes here. Supports multiple lines."
        }
      },
      "design": {
        "typography": {
          "color": {"breakpoint_base": "#333333"},
          "typography": {
            "custom": {
              "customTypography": {
                "fontSize": {"breakpoint_base": {"number": 16, "unit": "px"}},
                "fontWeight": {"breakpoint_base": "400"},
                "lineHeight": {"breakpoint_base": {"number": 1.6, "unit": "em"}},
                "textAlign": {"breakpoint_base": "left"}
              }
            }
          }
        },
        "size": {
          "width": {"breakpoint_base": "100%"},
          "maxWidth": {"breakpoint_base": {"number": 700, "unit": "px"}}
        },
        "spacing": {
          "margin_top": {"breakpoint_base": "0px"},
          "margin_bottom": {"breakpoint_base": "16px"}
        }
      }
    }
  },
  "children": []
}
```

---

### Rich Text (`EssentialElements\\RichText`)

```json
{
  "id": "richtext-1",
  "data": {
    "type": "EssentialElements\\RichText",
    "properties": {
      "content": {
        "content": {
          "text": "<h2>Rich Text Title</h2><p>Paragraph with <strong>bold</strong> and <em>italic</em> text.</p><ul><li>List item 1</li><li>List item 2</li></ul>"
        }
      },
      "design": {
        "typography": {
          "default": {
            "color": {"breakpoint_base": "#333333"}
          },
          "h2": {
            "color": {"breakpoint_base": "#000000"},
            "typography": {
              "custom": {
                "customTypography": {
                  "fontSize": {"breakpoint_base": {"number": 32, "unit": "px"}}
                }
              }
            }
          },
          "paragraph": {
            "typography": {
              "custom": {
                "customTypography": {
                  "fontSize": {"breakpoint_base": {"number": 16, "unit": "px"}},
                  "lineHeight": {"breakpoint_base": {"number": 1.6, "unit": "em"}}
                }
              }
            }
          }
        }
      }
    }
  },
  "children": []
}
```

---

## Button & Interactive Elements

### Button (`EssentialElements\\Button`)

```json
{
  "id": "button-1",
  "data": {
    "type": "EssentialElements\\Button",
    "properties": {
      "content": {
        "content": {
          "text": "Click Here",
          "link": {
            "url": "https://example.com",
            "type": "url",
            "target": "_self",
            "noFollow": false
          }
        }
      },
      "design": {
        "button": {
          "style": "custom",
          "background": {"breakpoint_base": "#0066cc"},
          "typography": {
            "color": {"breakpoint_base": "#ffffff"},
            "typography": {
              "custom": {
                "customTypography": {
                  "fontSize": {"breakpoint_base": {"number": 16, "unit": "px"}},
                  "fontWeight": {"breakpoint_base": "600"}
                }
              }
            }
          },
          "padding": {
            "top": {"breakpoint_base": "16px"},
            "bottom": {"breakpoint_base": "16px"},
            "left": {"breakpoint_base": "32px"},
            "right": {"breakpoint_base": "32px"}
          },
          "border": {
            "radius": {"breakpoint_base": {"number": 8, "unit": "px"}}
          },
          "custom": {
            "size": {
              "full_width_at": null
            }
          }
        },
        "spacing": {
          "margin_top": {"breakpoint_base": "0px"},
          "margin_bottom": {"breakpoint_base": "0px"}
        }
      }
    }
  },
  "children": []
}
```

**Link Target Options:** `_self`, `_blank`

**Button Styles:** `primary`, `secondary`, `custom`

---

### Text Link (`EssentialElements\\TextLink`)

```json
{
  "id": "textlink-1",
  "data": {
    "type": "EssentialElements\\TextLink",
    "properties": {
      "content": {
        "content": {
          "text": "Learn More",
          "link": {
            "url": "/about",
            "type": "url",
            "target": "_self"
          }
        }
      },
      "design": {
        "typography": {
          "color": {"breakpoint_base": "#0066cc"},
          "typography": {
            "custom": {
              "customTypography": {
                "fontSize": {"breakpoint_base": {"number": 16, "unit": "px"}},
                "textDecoration": "underline"
              }
            }
          }
        }
      }
    }
  },
  "children": []
}
```

---

### Icon (`EssentialElements\\Icon`)

```json
{
  "id": "icon-1",
  "data": {
    "type": "EssentialElements\\Icon",
    "properties": {
      "content": {
        "content": {
          "icon": {
            "slug": "icon-check",
            "name": "Check",
            "svgCode": "<svg>...</svg>"
          }
        }
      },
      "design": {
        "icon": {
          "color": {"breakpoint_base": "#0066cc"},
          "size": {"breakpoint_base": {"number": 24, "unit": "px"}},
          "style": "none",
          "background": {"breakpoint_base": "transparent"},
          "padding": {"breakpoint_base": {"number": 0, "unit": "px"}}
        }
      }
    }
  },
  "children": []
}
```

**Icon Styles:** `none`, `solid`, `outline`

---

## Media Elements

### Image (`EssentialElements\\Image2`)

```json
{
  "id": "image-1",
  "data": {
    "type": "EssentialElements\\Image2",
    "properties": {
      "content": {
        "image": {
          "from": "url",
          "url": "https://example.com/image.jpg",
          "alt": "Image description",
          "lazy_load": true
        }
      },
      "design": {
        "image": {
          "width": {"breakpoint_base": "100%"},
          "max_width": {"breakpoint_base": {"number": 800, "unit": "px"}},
          "aspect_ratio": "16/9",
          "object_fit": "cover",
          "object_position": "center center"
        },
        "effects": {
          "opacity": {"breakpoint_base": 1},
          "transition_duration": 300
        },
        "borders": {
          "radius": {
            "all": {"breakpoint_base": {"number": 8, "unit": "px"}}
          }
        }
      }
    }
  },
  "children": []
}
```

**Image From Options:**
- `url` - Direct URL
- `media_library` - WordPress media attachment

**Aspect Ratio Options:** `1/1`, `4/3`, `3/2`, `16/9`, `8/5`, `custom`

**Object Fit Options:** `cover`, `contain`, `fill`, `none`

---

### Video (`EssentialElements\\Video`)

```json
{
  "id": "video-1",
  "data": {
    "type": "EssentialElements\\Video",
    "properties": {
      "content": {
        "video": {
          "video": {
            "provider": "youtube",
            "url": "https://www.youtube.com/watch?v=VIDEO_ID",
            "embedUrl": "https://www.youtube.com/embed/VIDEO_ID",
            "videoId": "VIDEO_ID",
            "source": "youtube"
          },
          "ratio": "16:9"
        },
        "youtube": {
          "loading_method": "lightweight",
          "autoplay": false,
          "loop": false,
          "mute": false,
          "modest_branding": true,
          "privacy_mode": true
        }
      },
      "design": {
        "container": {
          "width": {"breakpoint_base": "100%"},
          "borders": {
            "radius": {
              "all": {"breakpoint_base": {"number": 8, "unit": "px"}}
            }
          }
        }
      }
    }
  },
  "children": []
}
```

**Video Providers:** `youtube`, `vimeo`, `dailymotion`, `self-hosted`

**Loading Methods:** `lightweight`, `lazyload`, `embed`

---

### Gallery (`EssentialElements\\Gallery`)

```json
{
  "id": "gallery-1",
  "data": {
    "type": "EssentialElements\\Gallery",
    "properties": {
      "content": {
        "content": {
          "images": [
            {
              "type": "image",
              "image": {
                "url": "https://example.com/image1.jpg",
                "alt": "Image 1"
              }
            },
            {
              "type": "image",
              "image": {
                "url": "https://example.com/image2.jpg",
                "alt": "Image 2"
              }
            }
          ],
          "image_size": "large",
          "link": "lightbox",
          "lazy_load": true
        }
      },
      "design": {
        "layout": {
          "type": "grid",
          "columns": {
            "breakpoint_base": 3,
            "breakpoint_tablet_portrait": 2,
            "breakpoint_phone_portrait": 1
          },
          "gap": {"breakpoint_base": {"number": 10, "unit": "px"}}
        },
        "images": {
          "aspect_ratio": "75%",
          "borders": {
            "radius": {
              "all": {"breakpoint_base": {"number": 4, "unit": "px"}}
            }
          }
        },
        "lightbox": {
          "thumbnails": true,
          "zoom": true
        }
      }
    }
  },
  "children": []
}
```

**Layout Types:** `grid`, `masonry`, `justified`, `slider`

**Link Options:** `lightbox`, `url`, `none`

---

## Slider Elements

### Basic Slider (`EssentialElements\\BasicSlider`)

```json
{
  "id": "slider-1",
  "data": {
    "type": "EssentialElements\\BasicSlider",
    "properties": {
      "content": {
        "content": {
          "title_html_tag": "h2",
          "slides": [
            {
              "title": "Slide 1 Title",
              "text": "<p>Slide 1 description text</p>",
              "background": {
                "type": "image",
                "image": {"url": "https://example.com/slide1.jpg"},
                "lazy_load": true
              },
              "button": {
                "text": "Learn More",
                "link": {"url": "/page1"}
              }
            },
            {
              "title": "Slide 2 Title",
              "text": "<p>Slide 2 description text</p>",
              "background": {
                "type": "color",
                "color": "#0066cc"
              }
            }
          ]
        }
      },
      "design": {
        "size": {
          "width": "full",
          "height": "custom",
          "custom_height": {"breakpoint_base": {"number": 600, "unit": "px"}}
        },
        "slider": {
          "settings": {
            "speed": 500,
            "autoplay": true,
            "autoplayDelay": 5000,
            "loop": true
          },
          "pagination": {
            "type": "bullets"
          },
          "arrows": {
            "show": true,
            "color": {"breakpoint_base": "#ffffff"}
          }
        },
        "slide": {
          "align_children": "center",
          "vertical_align_children": "center",
          "padding": {
            "top": {"breakpoint_base": "60px"},
            "bottom": {"breakpoint_base": "60px"},
            "left": {"breakpoint_base": "40px"},
            "right": {"breakpoint_base": "40px"}
          }
        },
        "typography": {
          "title": {
            "color": {"breakpoint_base": "#ffffff"},
            "typography": {
              "custom": {
                "customTypography": {
                  "fontSize": {"breakpoint_base": {"number": 48, "unit": "px"}}
                }
              }
            }
          },
          "text": {
            "color": {"breakpoint_base": "#ffffff"}
          }
        }
      }
    }
  },
  "children": []
}
```

---

## List Elements

### Basic List (`EssentialElements\\BasicList`)

```json
{
  "id": "list-1",
  "data": {
    "type": "EssentialElements\\BasicList",
    "properties": {
      "content": {
        "content": {
          "list_type": "ul",
          "items": [
            {"text": "List item 1"},
            {"text": "List item 2"},
            {"text": "List item 3"}
          ]
        }
      },
      "design": {
        "typography": {
          "color": {"breakpoint_base": "#333333"}
        },
        "list": {
          "style_type": "disc",
          "spacing": {"breakpoint_base": "8px"}
        }
      }
    }
  },
  "children": []
}
```

---

### Icon List (`EssentialElements\\IconList`)

```json
{
  "id": "iconlist-1",
  "data": {
    "type": "EssentialElements\\IconList",
    "properties": {
      "content": {
        "content": {
          "items": [
            {
              "text": "Feature one",
              "icon": {"slug": "icon-check"}
            },
            {
              "text": "Feature two",
              "icon": {"slug": "icon-check"}
            }
          ]
        }
      },
      "design": {
        "icon": {
          "color": {"breakpoint_base": "#00cc66"},
          "size": {"breakpoint_base": {"number": 20, "unit": "px"}}
        },
        "typography": {
          "color": {"breakpoint_base": "#333333"}
        },
        "spacing": {
          "gap": {"breakpoint_base": "12px"}
        }
      }
    }
  },
  "children": []
}
```

---

## Advanced Elements

### Accordion (`EssentialElements\\AdvancedAccordion`)

```json
{
  "id": "accordion-1",
  "data": {
    "type": "EssentialElements\\AdvancedAccordion",
    "properties": {
      "content": {
        "content": {
          "items": [
            {
              "title": "Accordion Item 1",
              "content": "<p>Content for item 1</p>",
              "open": true
            },
            {
              "title": "Accordion Item 2",
              "content": "<p>Content for item 2</p>",
              "open": false
            }
          ],
          "one_open_at_a_time": true
        }
      },
      "design": {
        "header": {
          "background": {"breakpoint_base": "#f5f5f5"},
          "typography": {
            "color": {"breakpoint_base": "#333333"}
          },
          "padding": {
            "top": {"breakpoint_base": "16px"},
            "bottom": {"breakpoint_base": "16px"},
            "left": {"breakpoint_base": "20px"},
            "right": {"breakpoint_base": "20px"}
          }
        },
        "content": {
          "background": {"breakpoint_base": "#ffffff"},
          "padding": {
            "top": {"breakpoint_base": "20px"},
            "bottom": {"breakpoint_base": "20px"},
            "left": {"breakpoint_base": "20px"},
            "right": {"breakpoint_base": "20px"}
          }
        },
        "icon": {
          "type": "chevron",
          "color": {"breakpoint_base": "#666666"}
        }
      }
    }
  },
  "children": []
}
```

---

### Tabs (`EssentialElements\\Tabs`)

```json
{
  "id": "tabs-1",
  "data": {
    "type": "EssentialElements\\Tabs",
    "properties": {
      "content": {
        "content": {
          "tabs": [
            {
              "title": "Tab 1",
              "content": "<p>Content for tab 1</p>"
            },
            {
              "title": "Tab 2",
              "content": "<p>Content for tab 2</p>"
            }
          ]
        }
      },
      "design": {
        "tabs": {
          "layout": "horizontal",
          "alignment": "left",
          "background": {"breakpoint_base": "#f5f5f5"},
          "active_background": {"breakpoint_base": "#ffffff"},
          "typography": {
            "color": {"breakpoint_base": "#666666"}
          },
          "active_typography": {
            "color": {"breakpoint_base": "#000000"}
          }
        },
        "content": {
          "background": {"breakpoint_base": "#ffffff"},
          "padding": {
            "top": {"breakpoint_base": "24px"},
            "bottom": {"breakpoint_base": "24px"}
          }
        }
      }
    }
  },
  "children": []
}
```

---

## Property Patterns

### Typography Object

```json
{
  "typography": {
    "color": {"breakpoint_base": "#333333"},
    "typography": {
      "custom": {
        "customTypography": {
          "fontFamily": "Inter, sans-serif",
          "fontSize": {"breakpoint_base": {"number": 16, "unit": "px"}},
          "fontWeight": {"breakpoint_base": "400"},
          "fontStyle": "normal",
          "lineHeight": {"breakpoint_base": {"number": 1.6, "unit": "em"}},
          "letterSpacing": {"breakpoint_base": {"number": 0, "unit": "px"}},
          "textAlign": {"breakpoint_base": "left"},
          "textTransform": "none",
          "textDecoration": "none"
        }
      }
    }
  }
}
```

### Spacing Object

```json
{
  "spacing": {
    "padding": {
      "top": {"breakpoint_base": "20px"},
      "bottom": {"breakpoint_base": "20px"},
      "left": {"breakpoint_base": "20px"},
      "right": {"breakpoint_base": "20px"}
    },
    "margin_top": {"breakpoint_base": "0px"},
    "margin_bottom": {"breakpoint_base": "20px"}
  }
}
```

### Border Object

```json
{
  "borders": {
    "radius": {
      "all": {"breakpoint_base": {"number": 8, "unit": "px"}}
    },
    "border": {
      "all": {
        "width": {"breakpoint_base": {"number": 1, "unit": "px"}},
        "style": "solid",
        "color": "#e0e0e0"
      }
    }
  }
}
```

### Background Object

```json
{
  "background": {
    "type": "image",
    "color": "#ffffff",
    "image": {
      "url": "https://example.com/image.jpg",
      "size": "cover",
      "position": "center center",
      "repeat": "no-repeat"
    },
    "overlay": {
      "color": "rgba(0,0,0,0.5)"
    }
  }
}
```

---

## Breakpoint System

Breakdance uses a mobile-first responsive system with these breakpoints:

| Breakpoint | Key | Width |
|------------|-----|-------|
| Desktop (Base) | `breakpoint_base` | 1120px+ |
| Tablet Landscape | `breakpoint_tablet_landscape` | 1024px |
| Tablet Portrait | `breakpoint_tablet_portrait` | 768px |
| Phone Landscape | `breakpoint_phone_landscape` | 480px |
| Phone Portrait | `breakpoint_phone_portrait` | 320px |

### Responsive Value Example

```json
{
  "design": {
    "typography": {
      "typography": {
        "custom": {
          "customTypography": {
            "fontSize": {
              "breakpoint_base": {"number": 48, "unit": "px"},
              "breakpoint_tablet_portrait": {"number": 36, "unit": "px"},
              "breakpoint_phone_portrait": {"number": 28, "unit": "px"}
            }
          }
        }
      }
    }
  }
}
```

---

## Complete Page Example

```json
{
  "tree": {
    "root": {
      "id": "root-1",
      "data": {"type": "EssentialElements\\Root"},
      "children": [
        {
          "id": "hero-section",
          "data": {
            "type": "EssentialElements\\Section",
            "properties": {
              "design": {
                "background": {"color": "#0a1628"},
                "spacing": {
                  "padding": {
                    "top": {"breakpoint_base": "120px"},
                    "bottom": {"breakpoint_base": "120px"}
                  }
                }
              }
            }
          },
          "children": [
            {
              "id": "hero-heading",
              "data": {
                "type": "EssentialElements\\Heading",
                "properties": {
                  "content": {
                    "content": {
                      "text": "Welcome to Our Site",
                      "tags": "h1"
                    }
                  },
                  "design": {
                    "typography": {
                      "color": {"breakpoint_base": "#ffffff"}
                    }
                  }
                }
              },
              "children": []
            },
            {
              "id": "hero-text",
              "data": {
                "type": "EssentialElements\\Text",
                "properties": {
                  "content": {
                    "content": {
                      "text": "Discover amazing features and capabilities."
                    }
                  },
                  "design": {
                    "typography": {
                      "color": {"breakpoint_base": "#cccccc"}
                    }
                  }
                }
              },
              "children": []
            },
            {
              "id": "hero-button",
              "data": {
                "type": "EssentialElements\\Button",
                "properties": {
                  "content": {
                    "content": {
                      "text": "Get Started",
                      "link": {"url": "/signup", "type": "url"}
                    }
                  },
                  "design": {
                    "button": {
                      "background": {"breakpoint_base": "#0066cc"},
                      "typography": {
                        "color": {"breakpoint_base": "#ffffff"}
                      }
                    }
                  }
                }
              },
              "children": []
            }
          ]
        }
      ]
    }
  }
}
```

---

## Element Type Reference

| Element | Type String |
|---------|-------------|
| Section | `EssentialElements\\Section` |
| Div | `EssentialElements\\Div` |
| Columns | `EssentialElements\\Columns` |
| Column | `EssentialElements\\Column` |
| Grid | `EssentialElements\\Grid` |
| Heading | `EssentialElements\\Heading` |
| Text | `EssentialElements\\Text` |
| Rich Text | `EssentialElements\\RichText` |
| Button | `EssentialElements\\Button` |
| Text Link | `EssentialElements\\TextLink` |
| Icon | `EssentialElements\\Icon` |
| Image | `EssentialElements\\Image2` |
| Video | `EssentialElements\\Video` |
| Gallery | `EssentialElements\\Gallery` |
| Basic Slider | `EssentialElements\\BasicSlider` |
| Accordion | `EssentialElements\\AdvancedAccordion` |
| Tabs | `EssentialElements\\Tabs` |
| Basic List | `EssentialElements\\BasicList` |
| Icon List | `EssentialElements\\IconList` |

---

*Generated for Oxybridge REST API - Last updated: January 2026*
