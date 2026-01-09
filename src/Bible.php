<?php
/**
 * Bible class - defines biblical book structure and categories
 */
class Bible
{
    /**
     * Get all Bible books organized by canonical category
     */
    public static function getBooksByCategory(): array
    {
        return [
            // OLD TESTAMENT
            'pentateuch' => [
                'name' => 'Pentateuch',
                'testament' => 'old',
                'description' => 'The Five Books of Moses',
                'books' => ['GEN', 'EXO', 'LEV', 'NUM', 'DEU']
            ],
            'historical' => [
                'name' => 'Historical Books',
                'testament' => 'old',
                'description' => 'History of Israel',
                'books' => ['JOS', 'JDG', 'RUT', '1SA', '2SA', '1KI', '2KI', '1CH', '2CH', 'EZR', 'NEH', 'EST']
            ],
            'wisdom' => [
                'name' => 'Wisdom & Poetry',
                'testament' => 'old',
                'description' => 'Poetic and Wisdom Literature',
                'books' => ['JOB', 'PSA', 'PRO', 'ECC', 'SNG']
            ],
            'major_prophets' => [
                'name' => 'Major Prophets',
                'testament' => 'old',
                'description' => 'The Longer Prophetic Books',
                'books' => ['ISA', 'JER', 'LAM', 'EZK', 'DAN']
            ],
            'minor_prophets' => [
                'name' => 'Minor Prophets',
                'testament' => 'old',
                'description' => 'The Twelve Shorter Prophets',
                'books' => ['HOS', 'JOL', 'AMO', 'OBA', 'JON', 'MIC', 'NAM', 'HAB', 'ZEP', 'HAG', 'ZEC', 'MAL']
            ],
            // NEW TESTAMENT
            'gospels' => [
                'name' => 'Gospels',
                'testament' => 'new',
                'description' => 'The Life of Christ',
                'books' => ['MAT', 'MRK', 'LUK', 'JHN']
            ],
            'acts' => [
                'name' => 'History',
                'testament' => 'new',
                'description' => 'Early Church History',
                'books' => ['ACT']
            ],
            'pauline' => [
                'name' => 'Pauline Epistles',
                'testament' => 'new',
                'description' => 'Letters of Paul',
                'books' => ['ROM', '1CO', '2CO', 'GAL', 'EPH', 'PHP', 'COL', '1TH', '2TH', '1TI', '2TI', 'TIT', 'PHM']
            ],
            'general_epistles' => [
                'name' => 'General Epistles',
                'testament' => 'new',
                'description' => 'Other New Testament Letters',
                'books' => ['HEB', 'JAS', '1PE', '2PE', '1JN', '2JN', '3JN', 'JUD']
            ],
            'prophecy' => [
                'name' => 'Prophecy',
                'testament' => 'new',
                'description' => 'Apocalyptic Literature',
                'books' => ['REV']
            ]
        ];
    }

    /**
     * Get full book name from code
     */
    public static function getBookName(string $bookCode): string
    {
        return ReadingPlan::getBookName($bookCode);
    }

    /**
     * Get book info by code
     */
    public static function getBookInfo(string $bookCode): ?array
    {
        $categories = self::getBooksByCategory();

        foreach ($categories as $catId => $category) {
            if (in_array($bookCode, $category['books'])) {
                return [
                    'code' => $bookCode,
                    'name' => self::getBookName($bookCode),
                    'category' => $catId,
                    'categoryName' => $category['name'],
                    'testament' => $category['testament']
                ];
            }
        }

        return null;
    }

    /**
     * Get all books in the Old Testament
     */
    public static function getOldTestamentBooks(): array
    {
        $categories = self::getBooksByCategory();
        $books = [];

        foreach ($categories as $category) {
            if ($category['testament'] === 'old') {
                $books = array_merge($books, $category['books']);
            }
        }

        return $books;
    }

    /**
     * Get all books in the New Testament
     */
    public static function getNewTestamentBooks(): array
    {
        $categories = self::getBooksByCategory();
        $books = [];

        foreach ($categories as $category) {
            if ($category['testament'] === 'new') {
                $books = array_merge($books, $category['books']);
            }
        }

        return $books;
    }

    /**
     * Get book completion stats for a user organized by biblical category
     */
    public static function getBookProgressByCategory(int $userId): array
    {
        // Get raw book completion stats
        $bookStats = Badge::getBookCompletionStats($userId);
        $categories = self::getBooksByCategory();

        $result = [];

        foreach ($categories as $catId => $category) {
            $categoryData = [
                'id' => $catId,
                'name' => $category['name'],
                'testament' => $category['testament'],
                'description' => $category['description'],
                'books' => [],
                'totalChapters' => 0,
                'completedChapters' => 0,
                'percentage' => 0,
                'booksComplete' => 0,
                'totalBooks' => count($category['books'])
            ];

            foreach ($category['books'] as $bookCode) {
                $stats = $bookStats[$bookCode] ?? [
                    'completed' => 0,
                    'total' => 0,
                    'percentage' => 0,
                    'isComplete' => false
                ];

                $categoryData['books'][$bookCode] = [
                    'code' => $bookCode,
                    'name' => self::getBookName($bookCode),
                    'completed' => $stats['completed'],
                    'total' => $stats['total'],
                    'percentage' => $stats['percentage'],
                    'isComplete' => $stats['isComplete']
                ];

                $categoryData['totalChapters'] += $stats['total'];
                $categoryData['completedChapters'] += $stats['completed'];

                if ($stats['isComplete']) {
                    $categoryData['booksComplete']++;
                }
            }

            // Calculate category percentage
            if ($categoryData['totalChapters'] > 0) {
                $categoryData['percentage'] = round(
                    ($categoryData['completedChapters'] / $categoryData['totalChapters']) * 100,
                    1
                );
            }

            $result[$catId] = $categoryData;
        }

        return $result;
    }

    /**
     * Get overall Bible progress stats
     */
    public static function getOverallProgress(int $userId): array
    {
        $categoryProgress = self::getBookProgressByCategory($userId);

        $totalChapters = 0;
        $completedChapters = 0;
        $totalBooks = 0;
        $booksComplete = 0;
        $otChapters = 0;
        $otCompleted = 0;
        $ntChapters = 0;
        $ntCompleted = 0;

        foreach ($categoryProgress as $category) {
            $totalChapters += $category['totalChapters'];
            $completedChapters += $category['completedChapters'];
            $totalBooks += $category['totalBooks'];
            $booksComplete += $category['booksComplete'];

            if ($category['testament'] === 'old') {
                $otChapters += $category['totalChapters'];
                $otCompleted += $category['completedChapters'];
            } else {
                $ntChapters += $category['totalChapters'];
                $ntCompleted += $category['completedChapters'];
            }
        }

        return [
            'totalChapters' => $totalChapters,
            'completedChapters' => $completedChapters,
            'percentage' => $totalChapters > 0 ? round(($completedChapters / $totalChapters) * 100, 1) : 0,
            'totalBooks' => $totalBooks,
            'booksComplete' => $booksComplete,
            'oldTestament' => [
                'total' => $otChapters,
                'completed' => $otCompleted,
                'percentage' => $otChapters > 0 ? round(($otCompleted / $otChapters) * 100, 1) : 0
            ],
            'newTestament' => [
                'total' => $ntChapters,
                'completed' => $ntCompleted,
                'percentage' => $ntChapters > 0 ? round(($ntCompleted / $ntChapters) * 100, 1) : 0
            ]
        ];
    }
}
