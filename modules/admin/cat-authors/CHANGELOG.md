# CHANGELOG — cat-authors

## [1.0.0-dev.1] - 2026-04-13

### Added
- Implémentation initiale du bridge CAT-AUTHOR (R010)
- Table `mod_cat_author_profiles` : profils auteurs editoriaux liés aux comptes admin
- Table `mod_cat_author_links` : liaison profil auteur → entités (articles, pages…)
- Table `mod_cat_author_roles` : registre des rôles admin signalés comme "auteur-capable"
- Services : AuthorProfileService, AuthorLinkService, AuthorSelectorService, AuthorDisplayService, AuthorValidationService, AuthorRoleRegistryService, AuthorIntegrationService
- UI admin : onglet Profils (listing, création, édition) + onglet Rôles autorisés (registre manuel)
- Panel embarqué : sélecteur auteur pour modules maîtres (cat-blog, cat-page…)
- Widgets : author_badge_admin, author_card, author_mini_card_front, author_bio_block, author_identity_inline
- Hook `content.editor.panels` enregistré (ordre 90)
- Dépendance forte : cat-seo-meta
- Section navigation : Organisation
