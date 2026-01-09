<?php
/**
 * Note class - handles personal notes for users
 */
class Note
{
    private const COLORS = ['default', 'yellow', 'blue', 'green', 'pink', 'purple'];

    /**
     * Create a new note
     */
    public static function create(int $userId, array $data): ?int
    {
        $pdo = Database::getInstance();

        $title = trim($data['title'] ?? '');
        $content = trim($data['content'] ?? '');
        $weekNumber = !empty($data['week_number']) ? (int) $data['week_number'] : null;
        $category = !empty($data['category']) ? $data['category'] : null;
        $book = !empty($data['book']) ? $data['book'] : null;
        $chapter = !empty($data['chapter']) ? (int) $data['chapter'] : null;
        $color = in_array($data['color'] ?? '', self::COLORS) ? $data['color'] : 'default';

        if (empty($title) && empty($content)) {
            return null;
        }

        // Default title if empty
        if (empty($title)) {
            $title = 'Untitled Note';
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO notes (user_id, title, content, week_number, category, book, chapter, color)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$userId, $title, $content, $weekNumber, $category, $book, $chapter, $color]);
            return (int) $pdo->lastInsertId();
        } catch (PDOException $e) {
            return null;
        }
    }

    /**
     * Get a note by ID
     */
    public static function get(int $noteId, int $userId): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
        $stmt->execute([$noteId, $userId]);
        $note = $stmt->fetch();
        return $note ?: null;
    }

    /**
     * Get all notes for a user
     */
    public static function getAllForUser(int $userId, ?string $search = null): array
    {
        $pdo = Database::getInstance();

        $sql = "SELECT * FROM notes WHERE user_id = ?";
        $params = [$userId];

        if ($search) {
            $sql .= " AND (title LIKE ? OR content LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY updated_at DESC";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get notes for a specific week/category
     */
    public static function getForReading(int $userId, int $weekNumber, string $category): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT * FROM notes
            WHERE user_id = ? AND week_number = ? AND category = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $weekNumber, $category]);
        return $stmt->fetchAll();
    }

    /**
     * Get notes for a specific book/chapter
     */
    public static function getForChapter(int $userId, string $book, int $chapter): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT * FROM notes
            WHERE user_id = ? AND book = ? AND chapter = ?
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId, $book, $chapter]);
        return $stmt->fetchAll();
    }

    /**
     * Update a note
     */
    public static function update(int $noteId, int $userId, array $data): bool
    {
        $pdo = Database::getInstance();

        // Verify ownership
        $note = self::get($noteId, $userId);
        if (!$note) {
            return false;
        }

        $title = trim($data['title'] ?? $note['title']);
        $content = trim($data['content'] ?? $note['content']);
        $color = in_array($data['color'] ?? '', self::COLORS) ? $data['color'] : $note['color'];

        if (empty($title)) {
            $title = 'Untitled Note';
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE notes SET title = ?, content = ?, color = ?
                WHERE id = ? AND user_id = ?
            ");
            return $stmt->execute([$title, $content, $color, $noteId, $userId]);
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Delete a note
     */
    public static function delete(int $noteId, int $userId): bool
    {
        $pdo = Database::getInstance();
        try {
            $stmt = $pdo->prepare("DELETE FROM notes WHERE id = ? AND user_id = ?");
            $stmt->execute([$noteId, $userId]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Get note count for user
     */
    public static function getCount(int $userId): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notes WHERE user_id = ?");
        $stmt->execute([$userId]);
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Get recent notes for user
     */
    public static function getRecent(int $userId, int $limit = 5): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT * FROM notes
            WHERE user_id = ?
            ORDER BY updated_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }

    /**
     * Get available colors
     */
    public static function getColors(): array
    {
        return self::COLORS;
    }

    /**
     * Format note reference (week/category or book/chapter)
     */
    public static function formatReference(array $note): string
    {
        if (!empty($note['book']) && !empty($note['chapter'])) {
            $bookName = ReadingPlan::getBookName($note['book']);
            return $bookName . ' ' . $note['chapter'];
        }

        if (!empty($note['week_number']) && !empty($note['category'])) {
            $category = ReadingPlan::getCategory($note['category']);
            $categoryName = $category ? $category['name'] : ucfirst($note['category']);
            return 'Week ' . $note['week_number'] . ' - ' . $categoryName;
        }

        return '';
    }
}
