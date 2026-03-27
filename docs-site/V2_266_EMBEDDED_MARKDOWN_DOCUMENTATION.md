# V2 266 - Embedded Markdown Documentation

## Objective
Build the first integrated documentation base directly inside CATMIN.

## Scope
- Store Markdown docs in project structure.
- Provide admin Markdown reader.
- Convert Markdown to safe HTML.
- Deliver readable dashboard presentation.

## Architecture
- Docs repository under dedicated folder by domain/module.
- Metadata conventions for title, category, and ordering.
- Renderer pipeline with sanitization.

## Admin Reader
- Document list/navigation panel.
- Content pane with formatted Markdown.
- Basic links, headings, code block support.

## Safety
- Sanitize rendered HTML to prevent script injection.
- Restrict supported Markdown features where needed.

## Result
CATMIN includes a maintainable embedded help center baseline.
