# CATMIN Community Signing & Trust Admission (081)

## Objectif
Document officiel pour les éditeurs `community` et `trusted` qui veulent publier des modules compatibles CATMIN et soumettre leur dépôt au market.

## Parcours officiel éditeur

### Étape 1: Préparer le module
- Respecter la structure CATMIN.
- Fournir un `manifest.json` valide (standard CATMIN).
- Déclarer clairement la compatibilité (`catmin_min`, `catmin_max`, `php_min`, `php_max`, `db_supported`, `db_constraints`).

### Étape 2: Construire la release
- Produire `module.zip` (package final).
- Générer `checksums.json` (SHA-256, déterministe, avec `module_hash`).
- Générer `signature.json` si signature activée/requise.
- Si dépôt multi-modules: publier `catmin-repository.json`.

Référence pipeline:
- `docs/release/module-release-pipeline.md`

### Étape 3: Publier la release
- Dépôt public accessible.
- Artefacts téléchargeables sans ambiguïté de version:
  - `module.zip`
  - `manifest.json`
  - `checksums.json`
  - `signature.json` (si applicable)
  - `release-metadata.json`

### Étape 4: Soumettre à CATMIN
Dossier minimal de soumission:
- URL dépôt
- owner/éditeur
- clé publique (ou keyring public de l’éditeur)
- documentation minimale (installation, compatibilité, changelog)
- statut demandé: `community` ou `trusted`

### Étape 5: Vérification CATMIN
Contrôles réalisés:
- validation structure module
- validation index dépôt (`catmin-repository.json`) si multi-modules
- validation release artifacts
- validation checksums (`files` + `module_hash`)
- validation signature RSA
- validation compatibilité core/PHP/DB
- validation capabilities et niveau de risque

### Étape 6: Décision
- refus
- admission `community`
- admission `trusted`
- refus `trusted` avec admission `community` possible

## Conditions minimales pour admission `community`
- Manifest standard valide.
- Release cohérente avec artefacts complets.
- Checksums SHA-256 valides.
- Compatibilité documentée.
- Capacités module justifiées.

## Conditions minimales pour admission `trusted`
- Tout le minimum `community`.
- Signature RSA valide.
- Clé publique fournie et exploitable.
- Documentation suffisante.
- Validation manuelle CATMIN.

## Cas de refus
- Structure non standard.
- Index dépôt absent/invalide (si requis).
- Artefacts incomplets.
- Checksums invalides.
- Signature invalide ou clé introuvable.
- Compatibilité insuffisamment documentée.
- Capacités critiques injustifiées.

## Règles sécurité
- Aucun fallback “best effort” sur l’intégrité.
- Aucun contournement en mode strict.
- Aucune clé privée dans le dépôt ou dans les ZIP.

## Checklist de soumission (copier/coller)
- [ ] Module conforme structure CATMIN
- [ ] Manifest validé
- [ ] `module.zip` final publié
- [ ] `checksums.json` publié
- [ ] `signature.json` publié (si applicable)
- [ ] `release-metadata.json` publié
- [ ] Compatibilité core/PHP/DB documentée
- [ ] Capacités module documentées
- [ ] Clé publique fournie
- [ ] URL dépôt + owner fournis

