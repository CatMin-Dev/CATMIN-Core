# CATMIN Market Trust Policy (065/069/070)

## Niveaux de confiance dépôt
- `official`
- `trusted`
- `community`
- `blocked`

## Policy globale (settings/module-repositories)
- autorisation par niveau
- checksums requis par niveau et global
- signature requise par niveau et global
- affichage community par défaut
- masquage non vérifiés
- filtres de canaux (`stable`, `beta`, `alpha`, `experimental`)
- règles lifecycle (`deprecated`, `abandoned`, `archived`)

## Lifecycle module
- `active`
- `deprecated`
- `abandoned`
- `replaced`
- `archived`
- `experimental`

## Trust score module
Score sur 100, basé sur:
- trust dépôt
- signature/intégrité
- canal de release
- lifecycle
- docs/changelog

Le score est indicatif: il ne bypass jamais les politiques de sécurité.

