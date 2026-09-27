<?php
namespace Shopnear\DynamicModules;

use Shopnear\Core\Database;

class LandingPageController
{
    public static function getPage(string $slug): ?array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT * FROM landing_pages WHERE slug = :slug AND status = 'published'");
        $stmt->execute(['slug' => $slug]);
        $page = $stmt->fetch();
        if ($page) {
            $page['theme_config'] = json_decode($page['theme_config'] ?? '{}', true);
        }
        return $page ?: null;
    }

    public static function createOrUpdatePage(array $data): int
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO landing_pages (slug, title, hero_heading, hero_subheading, theme_config, status)
            VALUES (:slug, :title, :heading, :subheading, :theme, :status)
            ON CONFLICT(slug) DO UPDATE SET
                title = excluded.title,
                hero_heading = excluded.hero_heading,
                hero_subheading = excluded.hero_subheading,
                theme_config = excluded.theme_config,
                status = excluded.status
        ");
        $stmt->execute([
            'slug' => $data['slug'],
            'title' => $data['title'],
            'heading' => $data['hero_heading'] ?? '',
            'subheading' => $data['hero_subheading'] ?? '',
            'theme' => json_encode($data['theme_config'] ?? []),
            'status' => $data['status'] ?? 'published'
        ]);
        return (int)$db->lastInsertId();
    }
}
