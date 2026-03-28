# WYSIWYG CMS — Prompt 330

## Éditeur retenu : Quill.js 1.3.7

### Pourquoi Quill
- Aucune dépendance jQuery (contrairement à Summernote)
- Compatible Bootstrap 5 visuellement
- Chargé via CDN uniquement sur les pages concernées (create/edit Pages et Articles)
- API simple : `new Quill(container, options)` + `quill.root.innerHTML`
- Thème `snow` : barre d'outils claire et professionnelle
- ~250 KB min, pas de build Vite nécessaire

### CDN utilisé
```
https://cdn.quilljs.com/1.3.7/quill.snow.css  (via @push('head'))
https://cdn.quilljs.com/1.3.7/quill.min.js    (via @push('scripts'))
```

---

## Architecture de l'intégration

### Pattern utilisé dans les vues
1. `<textarea id="content" name="content" class="d-none">{{ old('content', $page->content) }}</textarea>` — champ caché portant la valeur HTML
2. `<div id="quill-editor">` — conteneur visible de l'éditeur
3. Sur `submit` : `contentTA.value = quill.root.innerHTML` — synchronisation

### Initialisation du contenu existant
```js
if (contentTA.value.trim() !== '') {
    quill.clipboard.dangerouslyPasteHTML(contentTA.value);
}
```

### Restauration automatique de l'onglet actif après erreur
```js
const tabFields = { 'tab-content': ['title','slug','excerpt','content'], ... };
const errorKeys = @json(array_keys($errors->messages()));
// active le premier onglet contenant une erreur
```

---

## Pipeline de sanitization HTML (ContentSanitizerService)

### Tags autorisés
`p, br, hr, strong, b, em, i, u, s, del, mark, sub, sup, h2, h3, h4, h5, h6, ul, ol, li, a, img, blockquote, pre, code, table, thead, tbody, tfoot, tr, th, td, figure, figcaption, span, div`

> **Note** : `h1` est volontairement exclu pour réserver le titre de page au `<h1>` du template frontend.

### Tags supprimés avec leur contenu entier
`script, style, head, meta, link, base, iframe, object, embed, form, input, button, select, textarea, noscript`

### Attributs autorisés par tag
| Tag | Attributs autorisés |
|-----|---------------------|
| `a` | `href`, `title`, `target`, `rel` |
| `img` | `src`, `alt`, `title`, `width`, `height` |
| `td` | `colspan`, `rowspan` |
| `th` | `colspan`, `rowspan`, `scope` |
| `ol` | `type`, `start` |
| `li` | `value` |
| `pre`, `code` | `class` |
| `span`, `div`, `p` | `class` |

### Validations supplémentaires
- Tout attribut `on*` → supprimé (event handlers)
- Tout attribut `data-*` → supprimé
- `href` / `src` : acceptés uniquement si `http://`, `https://`, `mailto:`, `//`, `/`, `#`, `?`, `./`, `../`, ou chemin relatif sans schéma
- `javascript:` / `data:` URI → supprimés (avec détection de l'obfuscation via whitespace/entités)
- `<a target="_blank">` → force `rel="noopener noreferrer"` (anti-tabnapping)

### Ce qui est volontairement interdit
- `<script>` et tout JS inline
- `<iframe>` de tout type
- `<form>` / `<input>` dans le contenu
- `style` attribut inline
- `data-*` attributs
- `javascript:` / `data:` URIs

---

## DB — Nouvelles colonnes

### Table `pages`
| Colonne | Type | Notes |
|---------|------|-------|
| `excerpt` | `text nullable` | Résumé court |
| `meta_title` | `varchar(255) nullable` | SEO title |
| `meta_description` | `varchar(320) nullable` | SEO description |

### Table `articles`
| Colonne | Type | Notes |
|---------|------|-------|
| `meta_title` | `varchar(255) nullable` | SEO title inline |
| `meta_description` | `varchar(320) nullable` | SEO description inline |

> La relation `seo_meta_id` reste en place pour compatibilité backward, mais la saisie SEO se fait désormais via les champs inline.

---

## Formulaires — Structure onglets

### Pages (create / edit)
| Onglet | Champs |
|--------|--------|
| Contenu | titre, slug (auto-génération), extrait, éditeur Quill |
| Publication | statut, date de publication |
| SEO | meta_title, meta_description |

### Articles (create / edit)
| Onglet | Champs |
|--------|--------|
| Contenu | titre, type (article/news), slug, extrait, éditeur Quill |
| Publication | statut, date de publication |
| SEO | meta_title, meta_description |
| Médias | media_asset_id (picker visuel prévu en prompt 332) |

---

## Services modifiés

### ContentSanitizerService (`app/Services/ContentSanitizerService.php`)
- Nouvellement créé
- Utilisé via injection de constructeur dans `PagesAdminService` et `ArticleAdminService`
- Méthode publique : `sanitize(string $html): string`
- Ne lance pas d'exception — retourne `''` en cas d'entrée vide / non parsable

### PagesAdminService
- Injection `ContentSanitizerService` en constructeur
- `create()` et `update()` : passent le `content` par `$this->sanitizer->sanitize()`
- Acceptent maintenant `excerpt`, `meta_title`, `meta_description`

### ArticleAdminService
- Idem — champs `meta_title`, `meta_description` ajoutés

---

## Ajout d'un WYSIWYG dans un autre module

1. Ajouter dans la vue :
```blade
@push('head')
<link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
@endpush

<textarea id="content" name="content" class="d-none">{{ old('content') }}</textarea>
<div id="quill-editor" class="border rounded"></div>

@push('scripts')
<script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
<script>
const quill = new Quill('#quill-editor', { theme: 'snow', modules: { toolbar: [...] } });
const ta = document.getElementById('content');
if (ta.value) quill.clipboard.dangerouslyPasteHTML(ta.value);
document.querySelector('form').addEventListener('submit', () => {
    ta.value = quill.root.innerHTML === '<p><br></p>' ? '' : quill.root.innerHTML;
});
</script>
@endpush
```

2. Dans le service correspondant, injecter `ContentSanitizerService` et appeler `$this->sanitizer->sanitize($payload['content'] ?? '')`.

---

## Préparer un builder plus avancé (GrapesJS — Prompt 333)

- Les colonnes `content` (longText) et `content_type` sont agnostiques au format
- La sanitization actuelle préserve les `class` et attributs layout si la whitelist est élargie
- Pour passer à GrapesJS : ajouter `content_raw` (JSON GrapesJS) sans toucher `content` (HTML final rendu)
- La sanitization reste active sur le HTML exporté par GrapesJS avant stockage dans `content`

---

## Tests

**Fichier** : `tests/Unit/Content/ContentSanitizerTest.php`  
**17 tests, 32 assertions — tous verts**

Couvrent :
- Tags autorisés conservés (p, strong, h2, ul, table)
- script/iframe supprimés avec contenu
- `javascript:` href stripé (y compris version obfusquée avec espaces)
- `data:` URI stripée
- `on*` event handlers stripés
- `style` attribut strippé
- Tag non autorisé unwrappé (contenu conservé)
- `target="_blank"` → `rel="noopener noreferrer"` ajouté
- Chaîne vide → retourné vide
