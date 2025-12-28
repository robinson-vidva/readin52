<?php
/**
 * Reading Plan class
 */
class ReadingPlan
{
    private static ?array $data = null;

    /**
     * Load reading plan data
     */
    public static function load(): array
    {
        if (self::$data === null) {
            $path = CONFIG_PATH . '/reading-plan.json';
            if (!file_exists($path)) {
                throw new Exception('Reading plan file not found');
            }
            self::$data = json_decode(file_get_contents($path), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid reading plan JSON');
            }
        }
        return self::$data;
    }

    /**
     * Get app name
     */
    public static function getAppName(): string
    {
        $data = self::load();
        return $data['appName'] ?? 'ReadIn52';
    }

    /**
     * Get app tagline
     */
    public static function getAppTagline(): string
    {
        $data = self::load();
        return $data['appTagline'] ?? 'Journey Through Scripture in 52 Weeks';
    }

    /**
     * Get available translations
     */
    public static function getTranslations(): array
    {
        $data = self::load();
        return $data['availableTranslations'] ?? [];
    }

    /**
     * Get categories
     */
    public static function getCategories(): array
    {
        $data = self::load();
        return $data['categories'] ?? [];
    }

    /**
     * Get a specific category by ID
     */
    public static function getCategory(string $categoryId): ?array
    {
        $categories = self::getCategories();
        foreach ($categories as $category) {
            if ($category['id'] === $categoryId) {
                return $category;
            }
        }
        return null;
    }

    /**
     * Get all weeks
     */
    public static function getWeeks(): array
    {
        $data = self::load();
        return $data['weeks'] ?? [];
    }

    /**
     * Get a specific week's readings
     */
    public static function getWeek(int $weekNumber): ?array
    {
        $weeks = self::getWeeks();
        foreach ($weeks as $week) {
            if ($week['week'] === $weekNumber) {
                return $week;
            }
        }
        return null;
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
        return count(self::getWeeks());
    }

    /**
     * Save reading plan (admin function)
     */
    public static function save(array $data): bool
    {
        $path = CONFIG_PATH . '/reading-plan.json';
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        if (file_put_contents($path, $json) === false) {
            return false;
        }

        self::$data = null; // Clear cache
        return true;
    }

    /**
     * Export reading plan as JSON string
     */
    public static function export(): string
    {
        $data = self::load();
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

        // Validate each week
        foreach ($data['weeks'] as $week) {
            if (!isset($week['week']) || !isset($week['readings'])) {
                return ['success' => false, 'error' => 'Invalid week structure'];
            }

            $requiredCategories = ['poetry', 'history', 'prophecy', 'gospels'];
            foreach ($requiredCategories as $cat) {
                if (!isset($week['readings'][$cat])) {
                    return ['success' => false, 'error' => "Week {$week['week']} missing $cat reading"];
                }
            }
        }

        if (!self::save($data)) {
            return ['success' => false, 'error' => 'Failed to save reading plan'];
        }

        return ['success' => true];
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
