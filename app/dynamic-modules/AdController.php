<?php
namespace Shopnear\DynamicModules;

use Shopnear\Core\Database;

class AdController
{
    public static function getActiveSponsoredProducts(int $limit = 4): array
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT p.*, v.store_name, v.store_slug, ac.id as campaign_id, ac.type, ac.bid_amount
            FROM ad_campaigns ac
            JOIN products p ON ac.product_id = p.id
            JOIN vendors v ON ac.vendor_id = v.id
            WHERE ac.status = 'active' AND ac.spent < ac.budget AND p.stock_quantity > 0
            ORDER BY ac.bid_amount DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        $ads = $stmt->fetchAll();

        // Record impression
        foreach ($ads as $ad) {
            self::recordImpression((int)$ad['campaign_id']);
        }

        return $ads;
    }

    public static function recordImpression(int $campaignId): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO ad_metrics (campaign_id, impressions, clicks, recorded_date)
            VALUES (:cid, 1, 0, CURRENT_DATE)
            ON CONFLICT(campaign_id, recorded_date) DO UPDATE SET
                impressions = impressions + 1
        ");
        $stmt->execute(['cid' => $campaignId]);
    }

    public static function recordClick(int $campaignId): void
    {
        $db = Database::getInstance();
        $stmt = $db->prepare("
            INSERT INTO ad_metrics (campaign_id, impressions, clicks, recorded_date)
            VALUES (:cid, 0, 1, CURRENT_DATE)
            ON CONFLICT(campaign_id, recorded_date) DO UPDATE SET
                clicks = clicks + 1
        ");
        $stmt->execute(['cid' => $campaignId]);

        // Deduct CPC cost
        $campaignStmt = $db->prepare("SELECT type, bid_amount, budget, spent FROM ad_campaigns WHERE id = :id");
        $campaignStmt->execute(['id' => $campaignId]);
        $campaign = $campaignStmt->fetch();

        if ($campaign && $campaign['type'] === 'CPC') {
            $newSpent = $campaign['spent'] + $campaign['bid_amount'];
            $status = ($newSpent >= $campaign['budget']) ? 'budget_exhausted' : 'active';
            $update = $db->prepare("UPDATE ad_campaigns SET spent = :spent, status = :status WHERE id = :id");
            $update->execute(['spent' => $newSpent, 'status' => $status, 'id' => $campaignId]);
        }
    }
}
