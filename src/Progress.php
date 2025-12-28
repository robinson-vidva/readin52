<?php
/**
 * Reading Progress model class
 */
class Progress
{
    private const CATEGORIES = ['poetry', 'history', 'prophecy', 'gospels'];
    private const TOTAL_READINGS = 208; // 52 weeks × 4 categories

    /**
     * Get progress for a specific week
     */
    public static function getWeekProgress(int $userId, int $weekNumber): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT category, completed, completed_at
            FROM reading_progress
            WHERE user_id = ? AND week_number = ?
        ");
        $stmt->execute([$userId, $weekNumber]);
        $results = $stmt->fetchAll();

        $progress = [];
        foreach (self::CATEGORIES as $category) {
            $progress[$category] = [
                'completed' => false,
                'completed_at' => null
            ];
        }

        foreach ($results as $row) {
            $progress[$row['category']] = [
                'completed' => (bool) $row['completed'],
                'completed_at' => $row['completed_at']
            ];
        }

        return $progress;
    }

    /**
     * Get all progress for a user
     */
    public static function getAllProgress(int $userId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT week_number, category, completed, completed_at
            FROM reading_progress
            WHERE user_id = ?
            ORDER BY week_number, category
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    /**
     * Mark a reading as complete or incomplete
     */
    public static function setProgress(int $userId, int $weekNumber, string $category, bool $completed): bool
    {
        if ($weekNumber < 1 || $weekNumber > 52) {
            return false;
        }

        if (!in_array($category, self::CATEGORIES)) {
            return false;
        }

        $pdo = Database::getInstance();

        if ($completed) {
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO reading_progress (user_id, week_number, category, completed, completed_at)
                VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT OR REPLACE INTO reading_progress (user_id, week_number, category, completed, completed_at)
                VALUES (?, ?, ?, 0, NULL)
            ");
        }

        return $stmt->execute([$userId, $weekNumber, $category]);
    }

    /**
     * Toggle a reading's completion status
     */
    public static function toggleProgress(int $userId, int $weekNumber, string $category): array
    {
        $current = self::getWeekProgress($userId, $weekNumber);
        $isCompleted = !$current[$category]['completed'];

        if (self::setProgress($userId, $weekNumber, $category, $isCompleted)) {
            return [
                'success' => true,
                'completed' => $isCompleted
            ];
        }

        return ['success' => false];
    }

    /**
     * Get user statistics
     */
    public static function getStats(int $userId): array
    {
        $pdo = Database::getInstance();

        // Total completed
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM reading_progress
            WHERE user_id = ? AND completed = 1
        ");
        $stmt->execute([$userId]);
        $totalCompleted = (int) $stmt->fetch()['count'];

        // Completed by category
        $stmt = $pdo->prepare("
            SELECT category, COUNT(*) as count
            FROM reading_progress
            WHERE user_id = ? AND completed = 1
            GROUP BY category
        ");
        $stmt->execute([$userId]);
        $byCategory = [];
        foreach ($stmt->fetchAll() as $row) {
            $byCategory[$row['category']] = (int) $row['count'];
        }

        // Calculate streak
        $streak = self::calculateStreak($userId);

        // Get current week (based on first reading)
        $currentWeek = self::getCurrentWeek($userId);

        return [
            'total_completed' => $totalCompleted,
            'total_readings' => self::TOTAL_READINGS,
            'percentage' => round(($totalCompleted / self::TOTAL_READINGS) * 100, 1),
            'by_category' => $byCategory,
            'streak' => $streak,
            'current_week' => $currentWeek
        ];
    }

    /**
     * Calculate reading streak (consecutive weeks with all 4 completed)
     */
    private static function calculateStreak(int $userId): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT week_number, COUNT(*) as count
            FROM reading_progress
            WHERE user_id = ? AND completed = 1
            GROUP BY week_number
            HAVING count = 4
            ORDER BY week_number DESC
        ");
        $stmt->execute([$userId]);
        $completedWeeks = $stmt->fetchAll();

        if (empty($completedWeeks)) {
            return 0;
        }

        $streak = 0;
        $expectedWeek = null;

        foreach ($completedWeeks as $row) {
            $weekNum = (int) $row['week_number'];
            if ($expectedWeek === null) {
                $expectedWeek = $weekNum;
            }

            if ($weekNum === $expectedWeek) {
                $streak++;
                $expectedWeek--;
            } else {
                break;
            }
        }

        return $streak;
    }

    /**
     * Get current week based on user's progress
     */
    private static function getCurrentWeek(int $userId): int
    {
        $pdo = Database::getInstance();

        // Find the lowest week with incomplete readings
        $stmt = $pdo->prepare("
            SELECT MIN(w.week) as current_week
            FROM (
                SELECT 1 as week UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
                UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
                UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
                UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
                UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
                UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50
                UNION SELECT 51 UNION SELECT 52
            ) w
            WHERE (
                SELECT COUNT(*) FROM reading_progress
                WHERE user_id = ? AND week_number = w.week AND completed = 1
            ) < 4
        ");
        $stmt->execute([$userId]);
        $result = $stmt->fetch();

        return $result['current_week'] ?? 1;
    }

    /**
     * Get completion stats for all users (admin)
     */
    public static function getGlobalStats(): array
    {
        $pdo = Database::getInstance();

        // Total readings completed across all users
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM reading_progress
            WHERE completed = 1
        ");
        $totalCompleted = (int) $stmt->fetch()['count'];

        // Users with at least one reading
        $stmt = $pdo->query("
            SELECT COUNT(DISTINCT user_id) as count
            FROM reading_progress
            WHERE completed = 1
        ");
        $activeReaders = (int) $stmt->fetch()['count'];

        // Users who completed all readings
        $stmt = $pdo->query("
            SELECT COUNT(*) as count
            FROM (
                SELECT user_id
                FROM reading_progress
                WHERE completed = 1
                GROUP BY user_id
                HAVING COUNT(*) = 208
            )
        ");
        $completedPlan = (int) $stmt->fetch()['count'];

        return [
            'total_completed' => $totalCompleted,
            'active_readers' => $activeReaders,
            'completed_plan' => $completedPlan
        ];
    }

    /**
     * Get weekly completion counts for a user
     */
    public static function getWeeklyCompletionCounts(int $userId): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT week_number, COUNT(*) as completed_count
            FROM reading_progress
            WHERE user_id = ? AND completed = 1
            GROUP BY week_number
        ");
        $stmt->execute([$userId]);

        $counts = array_fill(1, 52, 0);
        foreach ($stmt->fetchAll() as $row) {
            $counts[(int) $row['week_number']] = (int) $row['completed_count'];
        }

        return $counts;
    }
}
