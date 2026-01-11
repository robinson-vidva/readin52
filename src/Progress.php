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
     * Get user statistics (based on chapter-level progress)
     */
    public static function getStats(int $userId): array
    {
        $pdo = Database::getInstance();

        // Count completed readings based on chapter progress
        // A reading is complete when all its chapters are marked complete
        $totalCompleted = 0;
        $byCategory = [];

        foreach (self::CATEGORIES as $cat) {
            $byCategory[$cat] = 0;
        }

        // Check each week/category combination
        for ($week = 1; $week <= 52; $week++) {
            foreach (self::CATEGORIES as $category) {
                if (self::isCategoryComplete($userId, $week, $category)) {
                    $totalCompleted++;
                    $byCategory[$category]++;
                }
            }
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
     * Calculate reading streak (consecutive weeks with all 4 categories completed)
     */
    private static function calculateStreak(int $userId): int
    {
        // Find all fully completed weeks based on chapter progress
        $completedWeeks = [];
        for ($week = 1; $week <= 52; $week++) {
            $allComplete = true;
            foreach (self::CATEGORIES as $category) {
                if (!self::isCategoryComplete($userId, $week, $category)) {
                    $allComplete = false;
                    break;
                }
            }
            if ($allComplete) {
                $completedWeeks[] = $week;
            }
        }

        if (empty($completedWeeks)) {
            return 0;
        }

        // Sort descending and count consecutive streak
        rsort($completedWeeks);

        $streak = 0;
        $expectedWeek = $completedWeeks[0];

        foreach ($completedWeeks as $weekNum) {
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
     * Get current week based on user's chapter-level progress
     * Returns the lowest week number that is not fully complete
     */
    private static function getCurrentWeek(int $userId): int
    {
        $pdo = Database::getInstance();

        // For each week 1-52, check if all chapters are complete using a single efficient query
        // Get count of completed chapters per week
        $stmt = $pdo->prepare("
            SELECT week_number, COUNT(*) as completed_count
            FROM chapter_progress
            WHERE user_id = ? AND completed = 1
            GROUP BY week_number
        ");
        $stmt->execute([$userId]);
        $completedByWeek = [];
        while ($row = $stmt->fetch()) {
            $completedByWeek[$row['week_number']] = (int)$row['completed_count'];
        }

        // Find first incomplete week
        for ($week = 1; $week <= 52; $week++) {
            $weekData = ReadingPlan::getWeek($week);
            if (!$weekData) continue;

            // Count total chapters in this week
            $totalChapters = 0;
            foreach ($weekData['readings'] as $reading) {
                foreach ($reading['passages'] as $passage) {
                    $totalChapters += count($passage['chapters']);
                }
            }

            $completedChapters = $completedByWeek[$week] ?? 0;
            if ($completedChapters < $totalChapters) {
                return $week;
            }
        }

        // All weeks complete - return week 52
        return 52;
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
     * Get weekly completion counts for a user (based on chapter progress)
     */
    public static function getWeeklyCompletionCounts(int $userId): array
    {
        $counts = array_fill(1, 52, 0);

        // Count completed categories for each week based on chapter progress
        for ($week = 1; $week <= 52; $week++) {
            foreach (self::CATEGORIES as $category) {
                if (self::isCategoryComplete($userId, $week, $category)) {
                    $counts[$week]++;
                }
            }
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
