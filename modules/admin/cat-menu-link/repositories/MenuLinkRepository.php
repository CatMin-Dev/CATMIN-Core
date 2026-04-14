<?php

declare(strict_types=1);

namespace Modules\CatMenuLink\repositories;

use Core\database\ConnectionManager;
use PDO;

final class MenuLinkRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = (new ConnectionManager())->connection();
        $this->ensureSchema();
    }

    public function stats(): array
    {
        return [
            'menus' => (int) $this->scalar('SELECT COUNT(DISTINCT menu_key) FROM mod_cat_menu_link_items'),
            'items' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_menu_link_items'),
            'visible' => (int) $this->scalar('SELECT COUNT(*) FROM mod_cat_menu_link_items WHERE is_visible = 1'),
        ];
    }

    public function listMenus(): array
    {
        $stmt = $this->pdo->query('SELECT menu_key, COUNT(*) AS c FROM mod_cat_menu_link_items GROUP BY menu_key ORDER BY menu_key ASC');
        return $stmt ? (array) $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function listItems(string $menuKey): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_menu_link_items WHERE menu_key = :menu_key ORDER BY sort_order ASC, id ASC');
        $stmt->execute(['menu_key' => $menuKey]);
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listAll(int $limit = 300): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM mod_cat_menu_link_items ORDER BY menu_key ASC, sort_order ASC, id ASC LIMIT :limit');
        $stmt->bindValue(':limit', max(1, min(1000, $limit)), PDO::PARAM_INT);
        $stmt->execute();
        return (array) $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function upsertEntityLink(array $payload): array
    {
        $menuKey = strtolower(trim((string) ($payload['menu_key'] ?? 'main_nav')));
        $entityType = strtolower(trim((string) ($payload['entity_type'] ?? 'page')));
        $entityId = (int) ($payload['entity_id'] ?? 0);
        if ($menuKey === '' || $entityType === '' || $entityId <= 0) {
            return ['ok' => false, 'message' => 'Paramètres menu invalides.'];
        }

        $existing = $this->pdo->prepare('SELECT id FROM mod_cat_menu_link_items WHERE menu_key = :menu_key AND entity_type = :entity_type AND entity_id = :entity_id LIMIT 1');
        $existing->execute(['menu_key' => $menuKey, 'entity_type' => $entityType, 'entity_id' => $entityId]);
        $id = $existing->fetchColumn();

        $params = [
            'menu_key' => $menuKey,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'parent_item_id' => ($pid = (int) ($payload['parent_item_id'] ?? 0)) > 0 ? $pid : null,
            'label_override' => ($v = trim((string) ($payload['label_override'] ?? ''))) !== '' ? $v : null,
            'target_url' => ($u = trim((string) ($payload['target_url'] ?? ''))) !== '' ? $u : null,
            'link_type' => strtolower(trim((string) ($payload['link_type'] ?? 'entity_link'))),
            'sort_order' => (int) ($payload['sort_order'] ?? 0),
            'is_visible' => !empty($payload['is_visible']) ? 1 : 0,
        ];

        if ($id !== false) {
            $sql = 'UPDATE mod_cat_menu_link_items SET parent_item_id = :parent_item_id, label_override = :label_override, target_url = :target_url, link_type = :link_type, sort_order = :sort_order, is_visible = :is_visible, updated_at = CURRENT_TIMESTAMP WHERE id = :id';
            $params['id'] = (int) $id;
            $ok = $this->pdo->prepare($sql)->execute($params);
            return ['ok' => $ok, 'id' => (int) $id, 'message' => $ok ? 'Item menu mis à jour.' : 'Échec mise à jour menu.'];
        }

        $sql = 'INSERT INTO mod_cat_menu_link_items (menu_key, entity_type, entity_id, parent_item_id, label_override, target_url, link_type, sort_order, is_visible, created_at, updated_at)
                VALUES (:menu_key, :entity_type, :entity_id, :parent_item_id, :label_override, :target_url, :link_type, :sort_order, :is_visible, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
        $ok = $this->pdo->prepare($sql)->execute($params);
        return ['ok' => $ok, 'id' => $ok ? (int) $this->pdo->lastInsertId() : 0, 'message' => $ok ? 'Item menu créé.' : 'Échec création menu.'];
    }

    public function reorder(string $menuKey, array $rows): array
    {
        $menuKey = strtolower(trim($menuKey));
        if ($menuKey === '') {
            return ['ok' => false, 'message' => 'Menu invalide'];
        }

        $this->pdo->beginTransaction();
        try {
            $stmt = $this->pdo->prepare('UPDATE mod_cat_menu_link_items SET parent_item_id = :parent_item_id, sort_order = :sort_order, updated_at = CURRENT_TIMESTAMP WHERE id = :id AND menu_key = :menu_key');
            foreach ($rows as $row) {
                $stmt->execute([
                    'id' => (int) ($row['id'] ?? 0),
                    'menu_key' => $menuKey,
                    'parent_item_id' => (($pid = (int) ($row['parent_item_id'] ?? 0)) > 0 ? $pid : null),
                    'sort_order' => (int) ($row['sort_order'] ?? 0),
                ]);
            }
            $this->pdo->commit();
            return ['ok' => true, 'message' => 'Ordre menu mis à jour.'];
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['ok' => false, 'message' => 'Échec reorder menu: ' . $e->getMessage()];
        }
    }

    public function delete(int $id): array
    {
        if ($id <= 0) {
            return ['ok' => false, 'message' => 'ID invalide'];
        }
        $stmt = $this->pdo->prepare('DELETE FROM mod_cat_menu_link_items WHERE id = :id');
        $ok = $stmt->execute(['id' => $id]);
        return ['ok' => $ok, 'message' => $ok ? 'Item supprimé.' : 'Échec suppression item.'];
    }

    private function scalar(string $sql): int
    {
        $value = $this->pdo->query($sql);
        if (!$value) {
            return 0;
        }
        return (int) $value->fetchColumn();
    }

    private function ensureSchema(): void
    {
        $driver = (string) $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec(
                'CREATE TABLE IF NOT EXISTS mod_cat_menu_link_items ('
                . 'id INTEGER PRIMARY KEY AUTOINCREMENT,'
                . 'menu_key VARCHAR(120) NOT NULL,'
                . 'entity_type VARCHAR(80) NOT NULL,'
                . 'entity_id INTEGER NOT NULL,'
                . 'parent_item_id INTEGER NULL,'
                . 'label_override VARCHAR(180) NULL,'
                . 'target_url VARCHAR(500) NULL,'
                . 'link_type VARCHAR(40) NOT NULL DEFAULT \'entity_link\','
                . 'sort_order INTEGER NOT NULL DEFAULT 0,'
                . 'is_visible INTEGER NOT NULL DEFAULT 1,'
                . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
                . 'updated_at DATETIME NULL'
                . ')'
            );
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_menu_link_menu ON mod_cat_menu_link_items(menu_key)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_menu_link_entity ON mod_cat_menu_link_items(entity_type, entity_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_menu_link_parent ON mod_cat_menu_link_items(parent_item_id)');
            $this->pdo->exec('CREATE INDEX IF NOT EXISTS ix_mod_cat_menu_link_sort ON mod_cat_menu_link_items(sort_order)');
            return;
        }

        $this->pdo->exec(
            'CREATE TABLE IF NOT EXISTS mod_cat_menu_link_items ('
            . 'id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,'
            . 'menu_key VARCHAR(120) NOT NULL,'
            . 'entity_type VARCHAR(80) NOT NULL,'
            . 'entity_id BIGINT UNSIGNED NOT NULL,'
            . 'parent_item_id BIGINT UNSIGNED NULL,'
            . 'label_override VARCHAR(180) NULL,'
            . 'target_url VARCHAR(500) NULL,'
            . 'link_type VARCHAR(40) NOT NULL DEFAULT \'entity_link\','
            . 'sort_order INT NOT NULL DEFAULT 0,'
            . 'is_visible TINYINT(1) NOT NULL DEFAULT 1,'
            . 'created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,'
            . 'updated_at DATETIME NULL,'
            . 'KEY ix_mod_cat_menu_link_menu (menu_key),'
            . 'KEY ix_mod_cat_menu_link_entity (entity_type, entity_id),'
            . 'KEY ix_mod_cat_menu_link_parent (parent_item_id),'
            . 'KEY ix_mod_cat_menu_link_sort (sort_order)'
            . ') ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }
}
