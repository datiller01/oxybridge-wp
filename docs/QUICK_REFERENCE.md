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
| Link | `EssentialElements\\Link` |
| Icon | `EssentialElements\\Icon` |
| Container | `OxygenElements\\Container` |
| WpMenu | `EssentialElements\\WpMenu` |

## Minimal Examples

### Heading

```json
{
  "id": 100,
  "data": {
    "type": "EssentialElements\\Heading",
    "properties": {
      "content": {"content": {"text": "Hello World", "tags": "h1"}},
      "design": {"typography": {"color": {"breakpoint_base": "#000"}}}
    }
  },
  "children": [],
  "_parentId": 1
}
```

### Text

```json
{
  "id": 101,
  "data": {
    "type": "EssentialElements\\Text",
    "properties": {
      "content": {"content": {"text": "Paragraph text"}},
      "design": {"typography": {"color": {"breakpoint_base": "#333"}}}
    }
  },
  "children": [],
  "_parentId": 1
}
```

### Button

```json
{
  "id": 102,
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
  "children": [],
  "_parentId": 1
}
```

### Section with Background

```json
{
  "id": 100,
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
  "children": [],
  "_parentId": 1
}
```

### Div with Flex Layout

```json
{
  "id": 103,
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
  "children": [],
  "_parentId": 100
}
```

### Div with Grid Layout

```json
{
  "id": 104,
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
  "children": [],
  "_parentId": 100
}
```

### Image

```json
{
  "id": 105,
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
  "children": [],
  "_parentId": 100
}
```

## Tree Structure (Required)

Every valid tree MUST have this structure:

```json
{
  "root": {
    "id": 1,
    "data": {
      "type": "root",
      "properties": null
    },
    "children": [/* child elements with _parentId */]
  },
  "status": "exported"
}
```

**Critical Requirements:**
- `root.id` must be integer (typically 1)
- `root.data.type` must be lowercase `"root"`
- `root.data.properties` must be `null`
- `status` must be `"exported"`
- All child elements must have integer IDs (100, 101, 102...)
- All children must have `_parentId` referencing parent ID

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

## API Endpoints

### Public Endpoints (No Auth)

| Endpoint | Description |
|----------|-------------|
| `GET /health` | Health check, returns builder status |
| `GET /info` | Plugin and builder version info |
| `GET /ai/docs` | Complete API documentation |
| `GET /ai/context` | AI context with element types and paths |
| `GET /ai/schema` | AI-friendly property schema |
| `GET /ai/tokens` | Design tokens (colors, fonts) |
| `GET /ai/components` | List available component templates |
| `GET /ai/components/{name}` | Get specific component template |
| `GET /global-styles` | All global styles |
| `GET /colors` | Global color palette |
| `GET /fonts` | Global font settings |
| `GET /breakpoints` | Responsive breakpoints |
| `GET /template-types` | Available template types |

### Authenticated Endpoints

| Endpoint | Method | Description |
|----------|--------|-------------|
| `/templates` | GET | List all templates |
| `/templates` | POST | Create template with tree |
| `/templates/{id}` | GET | Get template with tree |
| `/templates/{id}` | PUT | Update template |
| `/templates/{id}` | DELETE | Delete template |
| `/documents/{id}` | GET | Read document tree |
| `/documents/{id}` | POST | Update document tree |
| `/pages` | GET/POST | List/create pages |
| `/ai/validate` | POST | Validate tree structure |
| `/ai/transform` | POST | Transform simplified tree |

## AI Workflow Endpoints

### GET /ai/context

Returns everything needed to start building:

```json
{
  "version": "1.0.0",
  "builder": "Breakdance",
  "components": ["hero-basic", "header-basic", ...],
  "breakpoints": ["breakpoint_base", "breakpoint_tablet_portrait", ...],
  "element_types": ["EssentialElements\\Section", ...],
  "property_paths": {
    "text": "content.content.text",
    "background": "design.background",
    "padding": "design.spacing.padding"
  }
}
```

### GET /ai/schema

Returns AI-friendly property schema with recipes:

```json
{
  "version": "1.0.0",
  "valueTypes": {...},
  "properties": {
    "effects": {...},
    "background": {...},
    "typography": {...}
  },
  "recipes": {
    "fadeOnHover": {...},
    "cardWithShadow": {...},
    "frostedGlass": {...}
  }
}
```

### POST /ai/validate

Validates tree structure with detailed errors:

**Request:**
```json
{"tree": {...}}
```

**Response:**
```json
{
  "valid": false,
  "errors": [
    {
      "code": "missing_parent_id",
      "path": "root.children[0]._parentId",
      "message": "Element must have a _parentId property",
      "expected": "integer",
      "example": 1
    }
  ],
  "warnings": [...],
  "error_count": 1,
  "warning_count": 0
}
```

### POST /ai/transform

Transforms simplified input to valid Oxygen tree:

**Features:**
- Accepts flat `elements` array input
- Auto-assigns `_parentId` to children
- Normalizes `content.text` to `content.content.text`
- Returns processing stats for large trees (50+ elements)

**Request (simplified format):**
```json
{
  "tree": {
    "elements": [
      {"id": 100, "type": "Heading", "text": "Hello"}
    ]
  }
}
```

**Response:**
```json
{
  "success": true,
  "tree": {
    "root": {...},
    "status": "exported"
  },
  "_processing": {
    "nodes_processed": 50,
    "duration_ms": 45,
    "peak_memory_mb": 2.1
  }
}
```

## Template Creation

### POST /templates

Create a template with full tree in single request:

**Request:**
```json
{
  "title": "My Header",
  "type": "header",
  "tree": {
    "root": {
      "id": 1,
      "data": {"type": "root", "properties": null},
      "children": [...]
    },
    "status": "exported"
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "template": {
    "id": 123,
    "title": "My Header",
    "type": "header",
    "edit_url": "https://site.com/?breakdance_iframe=true&id=123"
  },
  "element_count": 17,
  "tree_saved": true,
  "css_regenerated": true
}
```

**Template Types:** `header`, `footer`, `global_block`, `popup`, `template`

## Available Component Templates

Pre-built component templates available via `GET /ai/components/{name}`:

| Component | Description |
|-----------|-------------|
| `header-basic` | Basic header with logo, nav, CTA |
| `header_centered` | Centered logo with nav below |
| `header_transparent` | Transparent overlay header |
| `header_sticky` | Sticky header with shadow |
| `hero-basic` | Basic hero section |
| `cta-section` | Call-to-action block |

## Basic API Usage

### Create Page with Tree

```bash
curl -X POST -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"title":"My Page","status":"publish","tree":{...}}' \
  "https://site.com/wp-json/oxybridge/v1/pages"
```

### Create Header Template

```bash
curl -X POST -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"title":"Site Header","type":"header","tree":{...}}' \
  "https://site.com/wp-json/oxybridge/v1/templates"
```

### Validate Tree Before Saving

```bash
curl -X POST -u "user:app-password" \
  -H "Content-Type: application/json" \
  -d '{"tree":{...}}' \
  "https://site.com/wp-json/oxybridge/v1/ai/validate"
```

### Get Component Template

```bash
curl "https://site.com/wp-json/oxybridge/v1/ai/components/header-basic"
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

## Common Mistakes

1. **String IDs instead of integers** - Use `"id": 100` not `"id": "100"`
2. **Missing _parentId** - All children must reference parent ID
3. **Missing status: "exported"** - Required at tree root level
4. **root.data.properties not null** - Must be `null`, not `{}`
5. **Including _nextNodeId** - Do NOT include, causes IO-TS errors
6. **Missing namespace prefix** - Use `EssentialElements\\Heading` not `Heading`
