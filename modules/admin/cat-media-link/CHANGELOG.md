# Changelog

## 1.0.0-dev.3
- Ajout presets image (`mod_cat_media_presets`) et variantes (`mod_cat_media_variants`)
- Ajout settings image processing (`mod_cat_media_settings`)
- Génération auto des variants à l'upload image
- Service Imagick dédié (`ImageProcessingService`) avec resize/crop/regenerate
- Service crop (`CropperService`) pour normalisation/export JSON
- UI admin: section IMAGE PROCESSING, PRESETS, VARIANTS, éditeur crop manuel graphique
- Nouvelles routes: presets/settings/regenerate/manual-crop/delete-variant

## 1.0.0-dev.1
- Initialisation du module CAT-MEDIA-LINK
- SQL assets + links
- Dashboard admin + panel embarqué
- Widgets bridge média
