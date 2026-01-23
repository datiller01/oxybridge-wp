# Oxybridge Quick Reference

Fast lookup for common element patterns.

## Critical Rule: Double-Nested Content

All text content uses `content.content.text`:

```json
"properties": {
  "content": {
    "content": {
      "text": "Your text"
    }
  }
}
```

## Element Types

| Element | Type |
|---------|------|
| Section | `EssentialElements\\Section` |
| Div | `EssentialElements\\Div` |
| Heading | `EssentialElements\\Heading` |
| Text | `EssentialElements\\Text` |
| Button | `EssentialElements\\Button` |
| Image | `EssentialElements\\Image2` |

## Minimal Examples

### Heading

```json
{
  "id": "h1",
  "data": {
    "type": "EssentialElements\\Heading",
    "properties": {
      "content": {"content": {"text": "Hello World", "tags": "h1"}},
      "design": {"typography": {"color": {"breakpoint_base": "#000"}}}
    }
  },
  "children": []
}
```

### Text

```json
{
  "id": "p1",
  "data": {
    "type": "EssentialElements\\Text",
    "properties": {
      "content": {"content": {"text": "Paragraph text"}},
      "design": {"typography": {"color": {"breakpoint_base": "#333"}}}
    }
  },
  "children": []
}
```

### Button

```json
{
  "id": "btn1",
  "data": {
    "type": "EssentialElements\\Button",
    "properties": {
      "content": {
        "content": {
          "text": "Click Me",
          "link": {"url": "#", "type": "url"}
        }
      },
      "design": {
        "button": {
          "background": {"breakpoint_base": "#0066cc"},
          "typography": {"color": {"breakpoint_base": "#fff"}}
        }
      }
    }
  },
  "children": []
}
```

### Section with Background

```json
{
  "id": "section1",
  "data": {
    "type": "EssentialElements\\Section",
    "properties": {
      "design": {
        "background": {"color": "#0a1628"},
        "spacing": {
          "padding": {
            "top": {"breakpoint_base": "100px"},
            "bottom": {"breakpoint_base": "100px"}
          }
        }
      }
    }
  },
  "children": []
}
```

### Div with Flex Layout

```json
{
  "id": "div1",
  "data": {
    "type": "EssentialElements\\Div",
    "properties": {
      "design": {
        "layout": {
          "display": {"breakpoint_base": "flex"},
          "justifyContent": {"breakpoint_base": "center"},
          "horizontalGap": {"breakpoint_base": "20px"}
        }
      }
    }
  },
  "children": []
}
```

### Div with Grid Layout

```json
{
  "id": "grid1",
  "data": {
    "type": "EssentialElements\\Div",
    "properties": {
      "design": {
        "layout": {
          "display": {"breakpoint_base": "grid"},
          "gridTemplateColumns": {"breakpoint_base": "repeat(3, 1fr)"},
          "horizontalGap": {"breakpoint_base": "30px"}
        }
      }
    }
  },
  "children": []
}
```

### Image

```json
{
  "id": "img1",
  "data": {
    "type": "EssentialElements\\Image2",
    "properties": {
      "content": {
        "image": {
          "from": "url",
          "url": "https://example.com/image.jpg",
          "alt": "Description"
        }
      },
      "design": {
        "image": {
          "width": {"breakpoint_base": "100%"},
          "aspect_ratio": "16/9"
        }
      }
    }
  },
  "children": []
}
```

## Breakpoints

| Name | Key | Width |
|------|-----|-------|
| Desktop | `breakpoint_base` | 1120px+ |
| Tablet Landscape | `breakpoint_tablet_landscape` | 1024px |
| Tablet Portrait | `breakpoint_tablet_portrait` | 768px |
| Phone Landscape | `breakpoint_phone_landscape` | 480px |
| Phone Portrait | `breakpoint_phone_portrait` | 320px |

## Common Design Properties

### Colors

```json
"design": {
  "typography": {"color": {"breakpoint_base": "#333"}},
  "background": {"color": "#fff"}
}
```

### Spacing

```json
"design": {
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

### Borders

```json
"design": {
  "borders": {
    "radius": {"all": {"breakpoint_base": {"number": 8, "unit": "px"}}},
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

### Size

```json
"design": {
  "size": {
    "width": {"breakpoint_base": "100%"},
    "maxWidth": {"breakpoint_base": {"number": 1200, "unit": "px"}},
    "minHeight": {"breakpoint_base": "auto"}
  }
}
```

## API Usage

### Create Page with Tree

```bash
curl -X POST -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"title":"My Page","status":"publish","tree":{...}}' \
  "https://site.com/wp-json/oxybridge/v1/pages"
```

### Regenerate CSS

```bash
curl -X POST -u "user:app-password" \
  "https://site.com/wp-json/oxybridge/v1/regenerate-css/123"
```

### Read Document

```bash
curl -u "user:app-password" \
  "https://site.com/wp-json/oxybridge/v1/documents/123"
```
