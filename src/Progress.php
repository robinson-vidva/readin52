<?php
/**
 * Reading Progress model class
 */
class Progress
{
    private const CATEGORIES = ['poetry', 'history', 'prophecy', 'gospels'];
    private const TOTAL_READINGS = 208; // 52 weeks × 4 categories

    // ==========================================
    // CHAPTER-LEVEL PROGRESS METHODS
    // ==========================================

    /**
     * Get chapter progress for a specific week and category
     */
    public static function getChapterProgress(int $userId, int $weekNumber, string $category): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT book, chapter, completed, completed_at
            FROM chapter_progress
            WHERE user_id = ? AND week_number = ? AND category = ?
            ORDER BY book, chapter
        ");
        $stmt->execute([$userId, $weekNumber, $category]);

        $progress = [];
        foreach ($stmt->fetchAll() as $row) {
            $key = $row['book'] . '_' . $row['chapter'];
            $progress[$key] = [
                'book' => $row['book'],
                'chapter' => (int) $row['chapter'],
                'completed' => (bool) $row['completed'],
                'completed_at' => $row['completed_at']
            ];
        }
        return $progress;
    }

    /**
     * Get all chapter progress for a week
     */
    public static function getWeekChapterProgress(int $userId, int $weekNumber): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT category, book, chapter, completed, completed_at
            FROM chapter_progress
            WHERE user_id = ? AND week_number = ?
            ORDER BY category, book, chapter
        ");
        $stmt->execute([$userId, $weekNumber]);

        $progress = [];
        foreach (self::CATEGORIES as $cat) {
            $progress[$cat] = [];
        }

        foreach ($stmt->fetchAll() as $row) {
            $key = $row['book'] . '_' . $row['chapter'];
            $progress[$row['category']][$key] = [
                'book' => $row['book'],
                'chapter' => (int) $row['chapter'],
                'completed' => (bool) $row['completed'],
                'completed_at' => $row['completed_at']
            ];
        }
        return $progress;
    }

    /**
     * Mark chapter as complete (no toggle - only sets to complete)
     */
    public static function markChapterComplete(int $userId, int $weekNumber, string $category, string $book, int $chapter): array
    {
        if ($weekNumber < 1 || $weekNumber > 52) {
            return ['success' => false, 'error' => 'Invalid week'];
        }
        if (!in_array($category, self::CATEGORIES)) {
            return ['success' => false, 'error' => 'Invalid category'];
        }

        $pdo = Database::getInstance();

        $stmt = $pdo->prepare("
            INSERT INTO chapter_progress (user_id, week_number, category, book, chapter, completed, completed_at)
            VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE completed = 1, completed_at = CURRENT_TIMESTAMP
        ");
        $stmt->execute([$userId, $weekNumber, $category, $book, $chapter]);

        // Check if all chapters in category are complete
        $categoryComplete = self::isCategoryComplete($userId, $weekNumber, $category);

        // Check and award badges
        $newBadges = Badge::checkAndAwardBadges($userId);

        return [
            'success' => true,
            'completed' => true,
            'categoryComplete' => $categoryComplete,
            'newBadges' => $newBadges
        ];
    }

    /**
     * Toggle chapter completion status (legacy - kept for compatibility)
     */
    public static function toggleChapter(int $userId, int $weekNumber, string $category, string $book, int $chapter): array
    {
        if ($weekNumber < 1 || $weekNumber > 52) {
            return ['success' => false, 'error' => 'Invalid week'];
        }
        if (!in_array($category, self::CATEGORIES)) {
            return ['success' => false, 'error' => 'Invalid category'];
        }

        $pdo = Database::getInstance();

        // Check current status
        $stmt = $pdo->prepare("
            SELECT completed FROM chapter_progress
            WHERE user_id = ? AND week_number = ? AND category = ? AND book = ? AND chapter = ?
        ");
        $stmt->execute([$userId, $weekNumber, $category, $book, $chapter]);
        $current = $stmt->fetch();

        $newStatus = !($current && $current['completed']);

        if ($newStatus) {
            $stmt = $pdo->prepare("
                INSERT INTO chapter_progress (user_id, week_number, category, book, chapter, completed, completed_at)
                VALUES (?, ?, ?, ?, ?, 1, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE completed = 1, completed_at = CURRENT_TIMESTAMP
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO chapter_progress (user_id, week_number, category, book, chapter, completed, completed_at)
                VALUES (?, ?, ?, ?, ?, 0, NULL)
                ON DUPLICATE KEY UPDATE completed = 0, completed_at = NULL
            ");
        }

        $stmt->execute([$userId, $weekNumber, $category, $book, $chapter]);

        // Check if all chapters in category are complete
        $categoryComplete = self::isCategoryComplete($userId, $weekNumber, $category);

        // Check and award badges if progress was made
        $newBadges = $newStatus ? Badge::checkAndAwardBadges($userId) : [];

        return [
            'success' => true,
            'completed' => $newStatus,
            'categoryComplete' => $categoryComplete,
            'newBadges' => $newBadges
        ];
    }

    /**
     * Check if all chapters in a category are complete
     */
    public static function isCategoryComplete(int $userId, int $weekNumber, string $category): bool
    {
        // Get the week's readings to know how many chapters there should be
        $week = ReadingPlan::getWeek($weekNumber);
        if (!$week || !isset($week['readings'][$category])) {
            return false;
        }

        $totalChapters = 0;
        foreach ($week['readings'][$category]['passages'] as $passage) {
            $totalChapters += count($passage['chapters']);
        }

        // Count completed chapters
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM chapter_progress
            WHERE user_id = ? AND week_number = ? AND category = ? AND completed = 1
        ");
        $stmt->execute([$userId, $weekNumber, $category]);
        $completedChapters = (int) $stmt->fetch()['count'];

        return $completedChapters >= $totalChapters;
    }

    /**
     * Get chapter-based statistics
     */
    public static function getChapterStats(int $userId): array
    {
        // Calculate total chapters in plan
        $totalChapters = self::getTotalChaptersInPlan();

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM chapter_progress
            WHERE user_id = ? AND completed = 1
        ");
        $stmt->execute([$userId]);
        $completedChapters = (int) $stmt->fetch()['count'];

        return [
            'total_chapters' => $totalChapters,
            'completed_chapters' => $completedChapters,
            'percentage' => $totalChapters > 0 ? round(($completedChapters / $totalChapters) * 100, 1) : 0
        ];
    }

    /**
     * Calculate total chapters in the reading plan
     */
    public static function getTotalChaptersInPlan(): int
    {
        static $total = null;
        if ($total !== null) {
            return $total;
        }

        $total = 0;
        $weeks = ReadingPlan::getWeeks();
        foreach ($weeks as $week) {
            foreach ($week['readings'] as $reading) {
                foreach ($reading['passages'] as $passage) {
                    $total += count($passage['chapters']);
                }
            }
        }
        return $total;
    }

    /**
     * Get chapters completed per week (for weekly progress)
     */
    public static function getWeekChapterCounts(int $userId, int $weekNumber): array
    {
        $week = ReadingPlan::getWeek($weekNumber);
        if (!$week) {
            return ['total' => 0, 'completed' => 0];
        }

        $totalChapters = 0;
        foreach ($week['readings'] as $reading) {
            foreach ($reading['passages'] as $passage) {
                $totalChapters += count($passage['chapters']);
            }
        }

        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM chapter_progress
            WHERE user_id = ? AND week_number = ? AND completed = 1
        ");
        $stmt->execute([$userId, $weekNumber]);
        $completedChapters = (int) $stmt->fetch()['count'];

        return [
            'total' => $totalChapters,
            'completed' => $completedChapters
        ];
    }

    // ==========================================
    // ORIGINAL CATEGORY-LEVEL METHODS (kept for compatibility)
    // ==========================================

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
                INSERT INTO reading_progress (user_id, week_number, category, completed, completed_at)
                VALUES (?, ?, ?, 1, CURRENT_TIMESTAMP)
                ON DUPLICATE KEY UPDATE completed = 1, completed_at = CURRENT_TIMESTAMP
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO reading_progress (user_id, week_number, category, completed, completed_at)
                VALUES (?, ?, ?, 0, NULL)
                ON DUPLICATE KEY UPDATE completed = 0, completed_at = NULL
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
            ) AS completed_users
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

    /**
     * Delete all progress for a user
     */
    public static function deleteAllProgress(int $userId): bool
    {
        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // Delete category-level progress
            $stmt = $pdo->prepare("DELETE FROM reading_progress WHERE user_id = ?");
            $stmt->execute([$userId]);

            // Delete chapter-level progress
            $stmt = $pdo->prepare("DELETE FROM chapter_progress WHERE user_id = ?");
            $stmt->execute([$userId]);

            $pdo->commit();
            return true;
        } catch (PDOException $e) {
            $pdo->rollBack();
            return false;
        }
    }
}
