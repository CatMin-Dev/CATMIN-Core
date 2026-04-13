# CHANGELOG — cat-authors

## [1.0.0-dev.3] - 2026-04-13

### Changed
- Refonte du module pour faire de l auteur une extension 1:1 d un compte admin existant
- Suppression de l UI a onglets et du registre manuel des roles auteurs
- Ajout des champs prenom et nom dans la fiche auteur
- Suppression du site web au profit d une gestion dynamique des reseaux sociaux avec icones
- Simplification de l administration: activation d un compte auteur, edition, retrait
- Mise a jour des widgets pour consommer le nouveau format de reseaux sociaux

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
