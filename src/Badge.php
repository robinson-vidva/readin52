<?php
/**
 * Badge class - handles badge operations and awarding
 */
class Badge
{
    /**
     * Get all badge definitions
     */
    public static function getAll(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT * FROM badges ORDER BY sort_order ASC");
        return $stmt->fetchAll();
    }

    /**
     * Get badges by category
     */
    public static function getByCategory(string $category): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM badges WHERE category = ? ORDER BY sort_order ASC");
        $stmt->execute([$category]);
        return $stmt->fetchAll();
    }

    /**
     * Get a user's earned badges
     */
    public static function getUserBadges(int $userId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT b.*, ub.earned_at
            FROM user_badges ub
            JOIN badges b ON ub.badge_id = b.id
            WHERE ub.user_id = ?
            ORDER BY ub.earned_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Get count of user's badges
     */
    public static function getUserBadgeCount(int $userId): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM user_badges WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Check if user has a specific badge
     */
    public static function userHasBadge(int $userId, string $badgeId): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT 1 FROM user_badges WHERE user_id = ? AND badge_id = ?");
        $stmt->execute([$userId, $badgeId]);
        return $stmt->fetch() !== false;
    }

    /**
     * Award a badge to a user
     */
    public static function award(int $userId, string $badgeId): bool
    {
        if (self::userHasBadge($userId, $badgeId)) {
            return false;
        }

        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->prepare("INSERT INTO user_badges (user_id, badge_id) VALUES (?, ?)");
            $stmt->execute([$userId, $badgeId]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Check and award all eligible badges for a user
     * Returns array of newly awarded badge IDs
     */
    public static function checkAndAwardBadges(int $userId): array
    {
        $awarded = [];
        $badges = self::getAll();
        $stats = self::getUserStats($userId);

        foreach ($badges as $badge) {
            if (self::userHasBadge($userId, $badge['id'])) {
                continue;
            }

            // Skip badges without valid criteria
            if (empty($badge['criteria'])) {
                continue;
            }

            $criteria = json_decode($badge['criteria'], true);
            if (!is_array($criteria)) {
                continue;
            }

            $earned = self::checkCriteria($userId, $criteria, $stats);

            if ($earned && self::award($userId, $badge['id'])) {
                $awarded[] = $badge;
            }
        }

        return $awarded;
    }

    /**
     * Check if criteria is met
     */
    private static function checkCriteria(int $userId, array $criteria, array $stats): bool
    {
        $type = $criteria['type'] ?? '';

        switch ($type) {
            case 'book':
                return isset($criteria['book']) && self::checkBookCompletion($userId, $criteria['book']);

            case 'category':
                return isset($criteria['category']) && self::checkCategoryCompletion($userId, $criteria['category']);

            case 'readings':
                return isset($criteria['count']) && $stats['total_completed'] >= $criteria['count'];

            case 'week_complete':
                return isset($criteria['count']) && $stats['complete_weeks'] >= $criteria['count'];

            case 'consecutive_weeks':
                return isset($criteria['count']) && $stats['streak'] >= $criteria['count'];

            case 'percentage':
                return isset($criteria['value']) && $stats['percentage'] >= $criteria['value'];

            case 'streak_days':
                return isset($criteria['count']) && $stats['day_streak'] >= $criteria['count'];

            default:
                return false;
        }
    }

    /**
     * Get user stats for badge checking
     */
    private static function getUserStats(int $userId): array
    {
        $stats = Progress::getStats($userId);
        $chapterStats = Progress::getChapterStats($userId);

        return [
            'total_completed' => $stats['total_completed'],
            'percentage' => $stats['percentage'],
            'streak' => $stats['streak'],
            'complete_weeks' => self::getCompleteWeeksCount($userId),
            'day_streak' => self::calculateDayStreak($userId),
        ];
    }

    /**
     * Count weeks with all 4 readings complete
     */
    private static function getCompleteWeeksCount(int $userId): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM (
                SELECT week_number
                FROM reading_progress
                WHERE user_id = ? AND completed = 1
                GROUP BY week_number
                HAVING COUNT(*) = 4
            ) AS complete_weeks
        ");
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Calculate day streak (consecutive days with reading)
     */
    private static function calculateDayStreak(int $userId): int
    {
        $pdo = Database::getInstance();

        // Get all dates when user completed a chapter
        $stmt = $pdo->prepare("
            SELECT DISTINCT DATE(completed_at) as reading_date
            FROM chapter_progress
            WHERE user_id = ? AND completed = 1 AND completed_at IS NOT NULL
            ORDER BY reading_date DESC
        ");
        $stmt->execute([$userId]);
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($dates)) {
            return 0;
        }

        $streak = 0;
        $today = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));

        // Check if first date is today or yesterday (streak is active)
        if ($dates[0] !== $today && $dates[0] !== $yesterday) {
            return 0;
        }

        $expectedDate = $dates[0];

        foreach ($dates as $date) {
            if ($date === $expectedDate) {
                $streak++;
                $expectedDate = date('Y-m-d', strtotime($expectedDate . ' -1 day'));
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Check if a specific book is completed
     */
    public static function checkBookCompletion(int $userId, string $bookCode): bool
    {
        // Get total chapters for this book from the reading plan
        $totalChapters = self::getBookChaptersInPlan($bookCode);
        if ($totalChapters === 0) {
            return false;
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(DISTINCT chapter) as count
            FROM chapter_progress
            WHERE user_id = ? AND book = ? AND completed = 1
        ");
        $stmt->execute([$userId, $bookCode]);
        $completed = (int) $stmt->fetch()['count'];

        return $completed >= $totalChapters;
    }

    /**
     * Get the number of chapters for a book that are in the reading plan
     */
    private static function getBookChaptersInPlan(string $bookCode): int
    {
        static $cache = [];

        if (isset($cache[$bookCode])) {
            return $cache[$bookCode];
        }

        $count = 0;
        $weeks = ReadingPlan::getWeeks();

        foreach ($weeks as $week) {
            foreach ($week['readings'] as $reading) {
                foreach ($reading['passages'] as $passage) {
                    if ($passage['book'] === $bookCode) {
                        $count += count($passage['chapters']);
                    }
                }
            }
        }

        $cache[$bookCode] = $count;
        return $count;
    }

    /**
     * Check if a category is fully completed
     */
    public static function checkCategoryCompletion(int $userId, string $category): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM reading_progress
            WHERE user_id = ? AND category = ? AND completed = 1
        ");
        $stmt->execute([$userId, $category]);
        $completed = (int) $stmt->fetch()['count'];

        // Each category has 52 readings (one per week)
        return $completed >= 52;
    }

    /**
     * Get book completion stats for a user
     */
    public static function getBookCompletionStats(int $userId): array
    {
        $pdo = Database::getInstance();

        // Get all completed chapters by book
        $stmt = $pdo->prepare("
            SELECT book, COUNT(DISTINCT chapter) as completed
            FROM chapter_progress
            WHERE user_id = ? AND completed = 1
            GROUP BY book
        ");
        $stmt->execute([$userId]);
        $completed = [];
        foreach ($stmt->fetchAll() as $row) {
            $completed[$row['book']] = (int) $row['completed'];
        }

        // Get total chapters per book in the plan
        $totals = self::getAllBookChaptersInPlan();

        $stats = [];
        foreach ($totals as $book => $total) {
            $done = $completed[$book] ?? 0;
            $stats[$book] = [
                'completed' => $done,
                'total' => $total,
                'percentage' => $total > 0 ? round(($done / $total) * 100, 1) : 0,
                'isComplete' => $done >= $total
            ];
        }

        return $stats;
    }

    /**
     * Get all book chapters in the reading plan
     */
    private static function getAllBookChaptersInPlan(): array
    {
        static $cache = null;

        if ($cache !== null) {
            return $cache;
        }

        $cache = [];
        $weeks = ReadingPlan::getWeeks();

        foreach ($weeks as $week) {
            foreach ($week['readings'] as $reading) {
                foreach ($reading['passages'] as $passage) {
                    $book = $passage['book'];
                    if (!isset($cache[$book])) {
                        $cache[$book] = 0;
                    }
                    $cache[$book] += count($passage['chapters']);
                }
            }
        }

        return $cache;
    }

    /**
     * Get recent badges (last 5 earned by any user)
     */
    public static function getRecentBadges(int $limit = 5): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT b.*, ub.earned_at, u.name as user_name
            FROM user_badges ub
            JOIN badges b ON ub.badge_id = b.id
            JOIN users u ON ub.user_id = u.id
            ORDER BY ub.earned_at DESC
            LIMIT ?
        ");
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get all badges with user's progress
     */
    public static function getAllWithProgress(int $userId): array
    {
        $badges = self::getAll();
        $userBadges = self::getUserBadges($userId);
        $earnedIds = array_column($userBadges, 'id');

        foreach ($badges as &$badge) {
            $badge['earned'] = in_array($badge['id'], $earnedIds);
            if ($badge['earned']) {
                foreach ($userBadges as $ub) {
                    if ($ub['id'] === $badge['id']) {
                        $badge['earned_at'] = $ub['earned_at'];
                        break;
                    }
                }
            }
        }

        return $badges;
    }
}
