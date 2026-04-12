# Roadmap de reconstruction modules — CATMIN R002

**Statut**: Actif  
**Date de validation**: 2026-04-12  
**DB**: cleanée (core_modules = 0 rows, aucune table mod_*)  
**Environnement**: catmin 0.4.0-rc.5, schema 0.1.0-dev.3

---

## Politique go/no-go globale

Avant d'installer un module en DB (et d'activer ses hooks), les conditions suivantes doivent toutes être vraies :

| Condition | Vérificateur |
|-----------|-------------|
| `bridge.contract.json` présent et valide vs schema v1 | CI / module-install-runner |
| `manifest.json` complet + `min_core_version` compatible | CoreModuleLoader |
| Checksums `SHA-256` présents et corrects | CoreModuleChecksumValidator |
| Signature RSA valide (canal stable/release) | CoreModuleSignatureValidator |
| Toutes les dépendances bridges actives en DB | CoreModuleLoader (check deps) |
| Aucune collision de table DB | CoreModuleInstallRunner |
| Permissions déclarées dans bridge.contract.json | CoreModuleTrustPolicy |
| Sidebar module déclarée dans `manifest.json.sidebar_entries` | Core module manifest loader |
| Table `core_sidebar_order` présente via migration core explicite | Core migrations (pas de création silencieuse au boot) |

---

## Phase 0 — Pré-infra (non bridges, fondation technique)

> Ces modules ne sont pas des bridges mais sont requis par tous les modules maîtres.

| # | Module | Dépend de | Go/No-Go spécifique |
|---|--------|-----------|---------------------|
| 0.1 | **CAT-MEDIA** | — | Tables media propres, MIME validation, stockage configuré |
| 0.2 | **CAT-FILEMANAGER** | CAT-MEDIA | File browser admin fonctionnel |
| 0.3 | **WYSIWYG** | CAT-MEDIA, CAT-FILEMANAGER | Éditeur stable, sanitisation HTML, aucun XSS |
| 0.4 | **PAGEBUILDER** | WYSIWYG, CAT-CONTENT-BLOCKS | Modulaire, basé blocs, rendu cohérent |

**Blocage si non validé** : aucun module maître ne peut être activé sans Phase 0 complète.

---

## Phase 1 — Bridges Niveau 0 (aucune dépendance bridge)

> Parallélisables entre eux. Ordre recommandé par impact décroissant.

| # | Bridge | Slug | Tables créées | Permissions déclarées | Priorité |
|---|--------|------|--------------|----------------------|----------|
| 1.1 | **CAT-SLUG** | cat-slug | `mod_cat_slugs` | slug.read, slug.write, slug.delete, slug.reorder | 🔴 CRITIQUE |
| 1.2 | **CAT-WORKFLOW** | cat-workflow | `mod_cat_workflows`, `mod_cat_workflow_states` | workflow.read, workflow.write, workflow.admin | haute |
| 1.3 | **CAT-REVISION** | cat-revision | `mod_cat_revisions` | revision.read, revision.restore, revision.delete | haute |
| 1.4 | **CAT-CONTENT-BLOCKS** | cat-content-blocks | `mod_cat_content_blocks`, `mod_cat_block_types` | content-blocks.read/write/delete/reorder | haute |
| 1.5 | **CAT-TAGS** | cat-tags | `mod_cat_tags`, `mod_cat_taggables` | tags.read, tags.write, tags.delete | normale |
| 1.6 | **CAT-CATEGORIES** | cat-categories | `mod_cat_categories` | categories.read/write/delete/reorder | normale |
| 1.7 | **CAT-AUTHOR** | cat-author | `mod_cat_authors`, `mod_cat_author_meta` | author.read, author.write, author.delete | normale |
| 1.8 | **CAT-RELATION** | cat-relation | `mod_cat_relations` | relation.read, relation.write, relation.delete | normale |
| 1.9 | **CAT-MEDIA-LINK** | cat-media-link | `mod_cat_media_links` | media-link.read/write/delete | normale |

**Go/No-Go Phase 1** : cat-slug doit être validé AVANT tout autre bridge de Phase 1.  
Les autres bridges de Phase 1 peuvent être installés en parallèle une fois 1.1 actif.

---

## Phase 2 — Bridges Niveau 1 (dépendent de Phase 1)

| # | Bridge | Dépend de | Tables | Permissions | Go/No-Go |
|---|--------|-----------|--------|-------------|----------|
| 2.1 | **CAT-SEO-META** | cat-slug | `mod_cat_seo_meta` | seo.read, seo.write, seo.delete | cat-slug actif en DB |
| 2.2 | **CAT-MENU-LINK** | cat-slug | `mod_cat_menu_links` | menu-link.read/write/delete/reorder | cat-slug actif en DB |
| 2.3 | **CAT-PUBLISHING** | cat-workflow | `mod_cat_publishings` | publishing.read/draft/publish/admin | cat-workflow actif en DB |

---

## Phase 3 — Bridges Niveau 2 (dépendent de Phase 1 + 2)

| # | Bridge | Dépend de | Tables | Permissions | Go/No-Go |
|---|--------|-----------|--------|-------------|----------|
| 3.1 | **CAT-SEARCH-INDEX** | cat-slug, cat-seo-meta, cat-tags, cat-categories, cat-author | `mod_cat_search_index`, `mod_cat_search_entries` | search-index.read/sync/admin | Tous bridges amont actifs |

---

## Phase 4 — Modules maîtres

> Aucun module maître avant Phase 0 + tous les bridges requis listés dans son bridge.contract.json actifs en DB.

| # | Module | Bridges obligatoires | Go/No-Go |
|---|--------|----------------------|----------|
| 4.1 | **CAT-PAGE** | cat-slug, cat-seo-meta, cat-content-blocks, cat-media-link, cat-publishing, cat-workflow, cat-revision | Phase 0 + bridges listés actifs |
| 4.2 | **CAT-BLOG** | + cat-tags, cat-categories, cat-author, cat-relation | CAT-PAGE validé + bridges additionnels |
| 4.3 | **CAT-DIRECTORY** | cat-slug, cat-seo-meta, cat-tags, cat-categories, cat-relation, cat-search-index | Phase 3 complète |
| 4.4 | **CAT-FORMS** | cat-slug | cat-slug actif |
| 4.5 | **CAT-COMMENTS** | cat-author, cat-workflow, cat-publishing | bridges listés actifs |

---

## Phase 5 — Modules service / lecture seule

> Installables dès que leurs bridges source sont actifs. N'introduisent aucune table en écriture cross-bridge.

| # | Module | Source bridges (RO) |
|---|--------|---------------------|
| 5.1 | **CAT-SEO-DASHBOARD** | cat-seo-meta, cat-slug, cat-search-index |
| 5.2 | **CAT-ANALYTICS** | tous (events agrégés) |
| 5.3 | **CAT-ACTIVITY** | events core + bridges |
| 5.4 | **CAT-AUDIT** | events core + bridges |
| 5.5 | **CAT-NOTIFICATIONS** | events emis bridges |
| 5.6 | **CAT-DASHBOARD-INSIGHTS** | agrégation multi-bridges |

---

## Checklist pipeline par module

Pour chaque module, avant merge/release :

```
[ ] bridge.contract.json créé et valide (jsonschema --instance contre bridge-contract-v1.schema.json)
[ ] manifest.json : schema_version, slug, version, min_core_version, release_channel
[ ] migrations/ : fichiers SQL nommés, sql_version confirmée dans bridge.contract.json
[ ] Permissions dans bridge.contract.json correspondent aux appels policy dans le code
[ ] Checksums SHA-256 générés (checksums.json)
[ ] Signé RSA si canal stable (signature.json + key_id = catmin-official-anchor-001)
[ ] sync-official-modules-index.php exécuté → official-release-index.json mis à jour
[ ] Install via market pipeline (CoreMarketInstaller)
[ ] DB : core_modules.status = active confirmé
[ ] Hooks chargés via CoreModuleLoader (boot.php DB-aware)
[ ] git commit + push
```

---

## Politique keyring

| Contexte | Comportement |
|----------|-------------|
| **Prod/Stable** | Keyring embarqué core + registre distant signé (GitHub). Registre distant accepté uniquement si signé par clé de confiance déjà embarquée. |
| **Dev** | Signature assouplie/désactivable. Clés locales de test acceptées. Bypass via config explicite. |
| **CI officiel** | Bloquant : checksums + signature + compatibilité déclarée. |
| **Community** | Contrôle renforcé, avertissements forts, mode quarantaine possible. Pas de blocage total. |

---

## Règles UI déclaratives (R003/R004)

| Sujet | Règle validée |
|------|----------------|
| Widgets/snippets bridge | Déclarés dans `bridge.contract.json` (`widgets[]`). |
| Implémentation widgets | Fichiers dans `widgets/` (ou classe entrypoint), sans redéclarer le contrat. |
| Sidebar module | Déclarée dans `manifest.json` via `sidebar_entries`. |
| Settings sections | Injection dynamique uniquement si bridge/module installé (pas de sections vides core). |
| Settings standalone module | Interdit par défaut, exception uniquement sur validation manuelle explicite. |

Exemple minimal `manifest.json` pour sidebar:

```json
{
	"sidebar_entries": [
		{
			"group": "content",
			"key": "cat-page.pages",
			"label": "Pages",
			"icon": "file-earmark-text",
			"route": "/pages",
			"order": 100,
			"visibility": "default",
			"permissions": ["page.read"]
		}
	]
}
```

---

## Prochaine action immédiate

**Module à traiter : `cat-slug` (Phase 1.1)**

Livraisons attendues :
1. Structure dossier `catmin/modules/admin/cat-slug/`
2. `manifest.json` complet
3. `bridge.contract.json` valide (permissions namespace strict)
4. Migration SQL : table `mod_cat_slugs`
5. Install via pipeline → DB actif → hooks boot vérifiés
6. Signature + checksum + sync index
7. `git commit`
