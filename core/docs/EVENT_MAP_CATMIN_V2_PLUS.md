# CATMIN Event Map V2+

## Scope
Cette cartographie standardise les evenements CATMIN pour le core, les modules et les addons.

Convention de nommage:
- Format: `domaine.action` ou `domaine.sousdomaine.action`
- Nom bus runtime: `catmin.<event_name>`
- Exemple: `auth.login.succeeded` devient `catmin.auth.login.succeeded`

Conventions techniques:
- Dispatch centralise via `App\Services\CatminEventBus`
- Enregistrement listeners via `hooks.php` (modules/addons) et `CatminHookLoader`
- Payload minimal recommande:
  - `occurred_at` (ISO8601)
  - `actor` (`id`, `username`, `type`)
  - `context` (`ip`, `request_id`, `source`)
  - `resource` (entite metier cible)
- Visibilite:
  - `internal`: usage interne CATMIN
  - `public`: consommable addons/webhooks/API

## Core System Events
| Event | Domaine | Trigger | Payload minimal | Source | Listeners typiques | Criticite | Visibilite | Trace |
|---|---|---|---|---|---|---|---|---|
| module.discovered | system | scan modules | module slug/name/version | ModuleManager | diagnostics | medium | internal | audit.info |
| module.enabled | system | activation module | module | ModuleManager | cache flush, logs | high | public | audit.info |
| module.disabled | system | desactivation module | module | ModuleManager | clean hooks, logs | high | public | audit.warning |
| module.installed | system | installation module | module, installer | module command | migrations, docs | high | public | audit.info |
| module.uninstalled | system | suppression module | module | module command | cleanup, logs | high | public | audit.warning |
| addon.discovered | system | scan addons | addon slug/name/version | AddonManager | diagnostics | low | internal | audit.info |
| addon.enabled | system | activation addon | addon | addon command | hooks, logs | high | public | audit.info |
| addon.disabled | system | desactivation addon | addon | addon command | cleanup, logs | high | public | audit.warning |
| addon.installed | system | installation addon | addon, installer | addon install command | migrations | high | public | audit.info |
| addon.uninstalled | system | uninstall addon | addon | addon uninstall command | cleanup | high | public | audit.warning |
| addon.booting | system | bootstrap addon demarre | addon, routes_path | AddonLoader | diagnostics | medium | internal | log.info |
| addon.booted | system | bootstrap addon termine | addon | AddonLoader | observability | medium | internal | log.info |
| setting.created | system | nouvelle setting | setting key/value | SettingService | cache, logs | high | public | audit.info |
| setting.updated | system | mise a jour setting | setting key/old/new | SettingService | webhooks, logs | high | public | audit.info |
| setting.deleted | system | suppression setting | setting key | SettingService | cache, logs | high | public | audit.warning |
| config.cache.cleared | system | cache config purge | actor, scope | CLI/Admin action | warmup | medium | internal | log.info |
| system.health.checked | system | check sante execute | summary checks | system/validate commands | monitoring | high | internal | log.info |
| system.maintenance.enabled | system | maintenance on | actor, reason | maintenance command | alerts | high | public | audit.warning |
| system.maintenance.disabled | system | maintenance off | actor | maintenance command | alerts | high | public | audit.info |
| system.update.started | system | update start | version target | updater | monitoring | high | internal | audit.info |
| system.update.finished | system | update success | version, duration | updater | notifications | high | internal | audit.info |
| system.update.failed | system | update fail | version, error | updater | alerting | critical | internal | audit.error |

## Auth & Security Events
| Event |
|---|
| auth.login.succeeded |
| auth.login.failed |
| auth.logout |
| auth.password.changed |
| auth.password.reset.requested |
| auth.password.reset.completed |
| auth.2fa.enabled |
| auth.2fa.disabled |
| auth.2fa.challenge.passed |
| auth.2fa.challenge.failed |
| auth.2fa.recovery_code.used |
| security.csrf.failed |
| security.rate_limit.hit |
| security.permission.denied |
| security.suspicious.activity.detected |

## IAM Events
| Event |
|---|
| user.created |
| user.updated |
| user.deleted |
| user.activated |
| user.deactivated |
| role.created |
| role.updated |
| role.deleted |
| role.protected.deletion_blocked |
| permission.assigned |
| permission.revoked |
| role.assigned.to_user |
| role.removed.from_user |

## Content Events
| Event |
|---|
| page.created |
| page.updated |
| page.deleted |
| page.published |
| page.unpublished |
| news.created |
| news.updated |
| news.deleted |
| news.published |
| blog.post.created |
| blog.post.updated |
| blog.post.deleted |
| blog.post.published |
| media.uploaded |
| media.deleted |
| media.replaced |
| media.downloaded |

## Shop Events
| Event |
|---|
| shop.product.created |
| shop.product.updated |
| shop.product.deleted |
| shop.product.stock.low |
| shop.product.stock.out |
| shop.category.created |
| shop.category.updated |
| shop.category.deleted |
| shop.order.created |
| shop.order.updated |
| shop.order.cancelled |
| shop.order.paid |
| shop.order.refunded |
| shop.order.shipped |
| shop.customer.created |
| shop.customer.updated |
| shop.invoice.generated |
| shop.invoice.sent |
| shop.invoice.failed |

## Mail / Notification Events
| Event |
|---|
| mail.template.created |
| mail.template.updated |
| mail.template.deleted |
| mail.sent |
| mail.failed |
| mail.queued |
| mail.retrying |
| notification.dispatched |
| notification.failed |

## API / Webhook Events
| Event |
|---|
| api.token.created |
| api.token.revoked |
| api.request.received |
| api.request.failed |
| api.rate_limit.hit |
| webhook.received |
| webhook.validated |
| webhook.rejected |
| webhook.dispatched |
| webhook.delivery.failed |
| webhook.delivery.succeeded |

## Observability Events
| Event |
|---|
| audit.entry.created |
| audit.export.generated |
| log.error.recorded |
| log.warning.recorded |
| monitoring.health.failed |
| monitoring.health.recovered |
| monitoring.performance.threshold_exceeded |

## Admin UI Hook Events
| Event |
|---|
| admin.sidebar.building |
| admin.dashboard.widgets.collecting |
| admin.header.actions.collecting |
| admin.user.menu.collecting |
| admin.help.links.collecting |

## Structure Technique Recommandee
- `App\Services\CatminEventBus`: dispatch/listen/registry.
- `App\Services\CatminEventMapService`: catalogue documente + status implementation.
- `hooks.php` modules/addons: declaration listeners metier.
- `catmin:event-map:status`: visibilite implementation vs documentation.
- Prefix runtime unique: `catmin.` pour eviter collisions ecosysteme Laravel.

## Priorites d'implementation
1. auth login/logout/2fa
2. users/roles/permissions
3. settings
4. modules/addons
5. api/webhooks
6. shop orders/invoices
7. mail
8. admin ui hooks

## Etat initial V2+
- Events implementes et visibles: via `php artisan catmin:event-map:status`
- Events documentes mais non cables: via la meme commande
- Listeners de base: webhooks module + addons actifs
- Validation addon listener: test `CatminAddonEventListenerTest`
