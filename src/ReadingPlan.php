<?php
/**
 * Reading Plan class - Database-backed
 */
class ReadingPlan
{
    private static ?array $categoriesCache = null;
    private static ?array $translationsCache = null;

    /**
     * Get app name from database settings
     */
    public static function getAppName(): string
    {
        return Database::getSetting('app_name', 'ReadIn52');
    }

    /**
     * Get app tagline
     */
    public static function getAppTagline(): string
    {
        return 'Journey Through Scripture in 52 Weeks';
    }

    /**
     * Get available translations from database
     */
    public static function getTranslations(): array
    {
        if (self::$translationsCache === null) {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("SELECT id, name, language, direction FROM bible_translations ORDER BY name");
            self::$translationsCache = $stmt->fetchAll();
        }
        return self::$translationsCache;
    }

    /**
     * Get translations grouped by language
     */
    public static function getTranslationsGroupedByLanguage(): array
    {
        $translations = self::getTranslations();
        $grouped = [];

        foreach ($translations as $trans) {
            $lang = $trans['language'] ?? 'Other';
            if (!isset($grouped[$lang])) {
                $grouped[$lang] = [];
            }
            $grouped[$lang][] = $trans;
        }

        // Sort languages alphabetically, but put English first
        uksort($grouped, function($a, $b) {
            if ($a === 'English') return -1;
            if ($b === 'English') return 1;
            return strcasecmp($a, $b);
        });

        return $grouped;
    }

    /**
     * Get categories from database
     */
    public static function getCategories(): array
    {
        if (self::$categoriesCache === null) {
            $pdo = Database::getInstance();
            $stmt = $pdo->query("SELECT id, name, color, sort_order FROM reading_categories ORDER BY sort_order");
            self::$categoriesCache = $stmt->fetchAll();
        }
        return self::$categoriesCache;
    }

    /**
     * Get a specific category by ID
     */
    public static function getCategory(string $categoryId): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("SELECT id, name, color, sort_order FROM reading_categories WHERE id = ?");
        $stmt->execute([$categoryId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all weeks
     */
    public static function getWeeks(): array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("
            SELECT rp.week_number, rp.category_id, rp.reference, rp.passages
            FROM reading_plan rp
            LEFT JOIN reading_categories rc ON rp.category_id = rc.id
            ORDER BY rp.week_number, rc.sort_order
        ");
        $rows = $stmt->fetchAll();

        // Group by week
        $weeks = [];
        foreach ($rows as $row) {
            $weekNum = $row['week_number'];
            if (!isset($weeks[$weekNum])) {
                $weeks[$weekNum] = ['week' => $weekNum, 'readings' => []];
            }
            $weeks[$weekNum]['readings'][$row['category_id']] = [
                'reference' => $row['reference'],
                'passages' => json_decode($row['passages'], true)
            ];
        }

        return array_values($weeks);
    }

    /**
     * Get a specific week's readings
     */
    public static function getWeek(int $weekNumber): ?array
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            SELECT rp.category_id, rp.reference, rp.passages
            FROM reading_plan rp
            LEFT JOIN reading_categories rc ON rp.category_id = rc.id
            WHERE rp.week_number = ?
            ORDER BY rc.sort_order
        ");
        $stmt->execute([$weekNumber]);
        $rows = $stmt->fetchAll();

        if (empty($rows)) {
            return null;
        }

        $readings = [];
        foreach ($rows as $row) {
            $readings[$row['category_id']] = [
                'reference' => $row['reference'],
                'passages' => json_decode($row['passages'], true)
            ];
        }

        return [
            'week' => $weekNumber,
            'readings' => $readings
        ];
    }

    /**
     * Get readings for a specific week with category details
     */
    public static function getWeekWithDetails(int $weekNumber): ?array
    {
        $week = self::getWeek($weekNumber);
        if (!$week) {
            return null;
        }

        $categories = self::getCategories();
        $categoryMap = [];
        foreach ($categories as $cat) {
            $categoryMap[$cat['id']] = $cat;
        }

        $readings = [];
        foreach ($week['readings'] as $categoryId => $reading) {
            $readings[$categoryId] = [
                'category' => $categoryMap[$categoryId] ?? null,
                'reference' => $reading['reference'],
                'passages' => $reading['passages']
            ];
        }

        return [
            'week' => $weekNumber,
            'readings' => $readings
        ];
    }

    /**
     * Get total number of weeks
     */
    public static function getTotalWeeks(): int
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->query("SELECT COUNT(DISTINCT week_number) as count FROM reading_plan");
        return (int) $stmt->fetch()['count'];
    }

    /**
     * Update a single reading (admin function)
     */
    public static function updateReading(int $weekNumber, string $categoryId, string $reference, array $passages): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("
            INSERT INTO reading_plan (week_number, category_id, reference, passages)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE reference = VALUES(reference), passages = VALUES(passages)
        ");
        return $stmt->execute([$weekNumber, $categoryId, $reference, json_encode($passages)]);
    }

    /**
     * Update a category (admin function)
     */
    public static function updateCategory(string $id, string $name, string $color): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("UPDATE reading_categories SET name = ?, color = ? WHERE id = ?");
        $result = $stmt->execute([$name, $color, $id]);
        self::$categoriesCache = null; // Clear cache
        return $result;
    }

    /**
     * Add a translation (admin function)
     */
    public static function addTranslation(string $id, string $name, string $language, string $direction = 'ltr'): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("INSERT IGNORE INTO bible_translations (id, name, language, direction) VALUES (?, ?, ?, ?)");
        $result = $stmt->execute([$id, $name, $language, $direction]);
        self::$translationsCache = null; // Clear cache
        return $result;
    }

    /**
     * Delete a translation (admin function)
     */
    public static function deleteTranslation(string $id): bool
    {
        $pdo = Database::getInstance();
        $stmt = $pdo->prepare("DELETE FROM bible_translations WHERE id = ?");
        $result = $stmt->execute([$id]);
        self::$translationsCache = null; // Clear cache
        return $result;
    }

    /**
     * Export reading plan as JSON string
     */
    public static function export(): string
    {
        $data = [
            'appName' => self::getAppName(),
            'appTagline' => self::getAppTagline(),
            'defaultTranslation' => Database::getSetting('default_translation', 'eng_kjv'),
            'availableTranslations' => self::getTranslations(),
            'categories' => self::getCategories(),
            'weeks' => self::getWeeks()
        ];

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Import reading plan from JSON string
     */
    public static function import(string $json): array
    {
        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['success' => false, 'error' => 'Invalid JSON format'];
        }

        // Validate structure
        if (!isset($data['weeks']) || !is_array($data['weeks'])) {
            return ['success' => false, 'error' => 'Missing or invalid weeks array'];
        }

        if (count($data['weeks']) !== 52) {
            return ['success' => false, 'error' => 'Reading plan must have exactly 52 weeks'];
        }

        $pdo = Database::getInstance();

        try {
            $pdo->beginTransaction();

            // Import translations if provided
            if (isset($data['availableTranslations'])) {
                $stmt = $pdo->prepare("INSERT INTO bible_translations (id, name, language, direction) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), language = VALUES(language), direction = VALUES(direction)");
                foreach ($data['availableTranslations'] as $trans) {
                    $stmt->execute([
                        $trans['id'],
                        $trans['name'],
                        $trans['language'],
                        $trans['direction'] ?? 'ltr'
                    ]);
                }
            }

            // Import categories if provided
            if (isset($data['categories'])) {
                $stmt = $pdo->prepare("INSERT INTO reading_categories (id, name, color, sort_order) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE name = VALUES(name), color = VALUES(color), sort_order = VALUES(sort_order)");
                $sortOrder = 0;
                foreach ($data['categories'] as $cat) {
                    $stmt->execute([
                        $cat['id'],
                        $cat['name'],
                        $cat['color'],
                        $sortOrder++
                    ]);
                }
            }

            // Clear existing readings and import new ones
            $pdo->exec("TRUNCATE TABLE reading_plan");

            $stmt = $pdo->prepare("INSERT INTO reading_plan (week_number, category_id, reference, passages) VALUES (?, ?, ?, ?)");
            foreach ($data['weeks'] as $week) {
                $weekNum = $week['week'];
                foreach ($week['readings'] as $categoryId => $reading) {
                    $stmt->execute([
                        $weekNum,
                        $categoryId,
                        $reading['reference'],
                        json_encode($reading['passages'])
                    ]);
                }
            }

            $pdo->commit();

            // Clear caches
            self::$categoriesCache = null;
            self::$translationsCache = null;

            return ['success' => true];

        } catch (Exception $e) {
            $pdo->rollBack();
            return ['success' => false, 'error' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Clear translations cache (call after syncing)
     */
    public static function clearTranslationsCache(): void
    {
        self::$translationsCache = null;
    }

    /**
     * Get book name from ID
     */
    public static function getBookName(string $bookId): string
    {
        $books = [
            'GEN' => 'Genesis', 'EXO' => 'Exodus', 'LEV' => 'Leviticus',
            'NUM' => 'Numbers', 'DEU' => 'Deuteronomy', 'JOS' => 'Joshua',
            'JDG' => 'Judges', 'RUT' => 'Ruth', '1SA' => '1 Samuel',
            '2SA' => '2 Samuel', '1KI' => '1 Kings', '2KI' => '2 Kings',
            '1CH' => '1 Chronicles', '2CH' => '2 Chronicles', 'EZR' => 'Ezra',
            'NEH' => 'Nehemiah', 'EST' => 'Esther', 'JOB' => 'Job',
            'PSA' => 'Psalms', 'PRO' => 'Proverbs', 'ECC' => 'Ecclesiastes',
            'SNG' => 'Song of Solomon', 'ISA' => 'Isaiah', 'JER' => 'Jeremiah',
            'LAM' => 'Lamentations', 'EZK' => 'Ezekiel', 'DAN' => 'Daniel',
            'HOS' => 'Hosea', 'JOL' => 'Joel', 'AMO' => 'Amos',
            'OBA' => 'Obadiah', 'JON' => 'Jonah', 'MIC' => 'Micah',
            'NAM' => 'Nahum', 'HAB' => 'Habakkuk', 'ZEP' => 'Zephaniah',
            'HAG' => 'Haggai', 'ZEC' => 'Zechariah', 'MAL' => 'Malachi',
            'MAT' => 'Matthew', 'MRK' => 'Mark', 'LUK' => 'Luke',
            'JHN' => 'John', 'ACT' => 'Acts', 'ROM' => 'Romans',
            '1CO' => '1 Corinthians', '2CO' => '2 Corinthians', 'GAL' => 'Galatians',
            'EPH' => 'Ephesians', 'PHP' => 'Philippians', 'COL' => 'Colossians',
            '1TH' => '1 Thessalonians', '2TH' => '2 Thessalonians',
            '1TI' => '1 Timothy', '2TI' => '2 Timothy', 'TIT' => 'Titus',
            'PHM' => 'Philemon', 'HEB' => 'Hebrews', 'JAS' => 'James',
            '1PE' => '1 Peter', '2PE' => '2 Peter', '1JN' => '1 John',
            '2JN' => '2 John', '3JN' => '3 John', 'JUD' => 'Jude',
            'REV' => 'Revelation'
        ];

        return $books[$bookId] ?? $bookId;
    }
}
