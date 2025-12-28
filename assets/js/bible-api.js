/**
 * ReadIn52 Bible API Client
 *
 * Handles fetching Bible content from HelloAO API
 * with localStorage caching (LRU with max 50 chapters)
 */

const BibleAPI = (function() {
    const BASE_URL = 'https://bible.helloao.org/api';
    const CACHE_KEY = 'readin52_bible_cache';
    const MAX_CACHE_SIZE = 50;

    // Book names for display
    const BOOK_NAMES = {
        'GEN': 'Genesis', 'EXO': 'Exodus', 'LEV': 'Leviticus',
        'NUM': 'Numbers', 'DEU': 'Deuteronomy', 'JOS': 'Joshua',
        'JDG': 'Judges', 'RUT': 'Ruth', '1SA': '1 Samuel',
        '2SA': '2 Samuel', '1KI': '1 Kings', '2KI': '2 Kings',
        '1CH': '1 Chronicles', '2CH': '2 Chronicles', 'EZR': 'Ezra',
        'NEH': 'Nehemiah', 'EST': 'Esther', 'JOB': 'Job',
        'PSA': 'Psalms', 'PRO': 'Proverbs', 'ECC': 'Ecclesiastes',
        'SNG': 'Song of Solomon', 'ISA': 'Isaiah', 'JER': 'Jeremiah',
        'LAM': 'Lamentations', 'EZK': 'Ezekiel', 'DAN': 'Daniel',
        'HOS': 'Hosea', 'JOL': 'Joel', 'AMO': 'Amos',
        'OBA': 'Obadiah', 'JON': 'Jonah', 'MIC': 'Micah',
        'NAM': 'Nahum', 'HAB': 'Habakkuk', 'ZEP': 'Zephaniah',
        'HAG': 'Haggai', 'ZEC': 'Zechariah', 'MAL': 'Malachi',
        'MAT': 'Matthew', 'MRK': 'Mark', 'LUK': 'Luke',
        'JHN': 'John', 'ACT': 'Acts', 'ROM': 'Romans',
        '1CO': '1 Corinthians', '2CO': '2 Corinthians', 'GAL': 'Galatians',
        'EPH': 'Ephesians', 'PHP': 'Philippians', 'COL': 'Colossians',
        '1TH': '1 Thessalonians', '2TH': '2 Thessalonians',
        '1TI': '1 Timothy', '2TI': '2 Timothy', 'TIT': 'Titus',
        'PHM': 'Philemon', 'HEB': 'Hebrews', 'JAS': 'James',
        '1PE': '1 Peter', '2PE': '2 Peter', '1JN': '1 John',
        '2JN': '2 John', '3JN': '3 John', 'JUD': 'Jude',
        'REV': 'Revelation'
    };

    // Total chapters per book
    const BOOK_CHAPTERS = {
        'GEN': 50, 'EXO': 40, 'LEV': 27, 'NUM': 36, 'DEU': 34,
        'JOS': 24, 'JDG': 21, 'RUT': 4, '1SA': 31, '2SA': 24,
        '1KI': 22, '2KI': 25, '1CH': 29, '2CH': 36, 'EZR': 10,
        'NEH': 13, 'EST': 10, 'JOB': 42, 'PSA': 150, 'PRO': 31,
        'ECC': 12, 'SNG': 8, 'ISA': 66, 'JER': 52, 'LAM': 5,
        'EZK': 48, 'DAN': 12, 'HOS': 14, 'JOL': 3, 'AMO': 9,
        'OBA': 1, 'JON': 4, 'MIC': 7, 'NAM': 3, 'HAB': 3,
        'ZEP': 3, 'HAG': 2, 'ZEC': 14, 'MAL': 4, 'MAT': 28,
        'MRK': 16, 'LUK': 24, 'JHN': 21, 'ACT': 28, 'ROM': 16,
        '1CO': 16, '2CO': 13, 'GAL': 6, 'EPH': 6, 'PHP': 4,
        'COL': 4, '1TH': 5, '2TH': 3, '1TI': 6, '2TI': 4,
        'TIT': 3, 'PHM': 1, 'HEB': 13, 'JAS': 5, '1PE': 5,
        '2PE': 3, '1JN': 5, '2JN': 1, '3JN': 1, 'JUD': 1,
        'REV': 22
    };

    /**
     * Get cache from localStorage
     */
    function getCache() {
        try {
            const cache = localStorage.getItem(CACHE_KEY);
            return cache ? JSON.parse(cache) : { entries: [], data: {} };
        } catch (e) {
            return { entries: [], data: {} };
        }
    }

    /**
     * Save cache to localStorage
     */
    function saveCache(cache) {
        try {
            localStorage.setItem(CACHE_KEY, JSON.stringify(cache));
        } catch (e) {
            // localStorage might be full, clear oldest entries
            cache.entries = cache.entries.slice(-Math.floor(MAX_CACHE_SIZE / 2));
            for (const key in cache.data) {
                if (!cache.entries.includes(key)) {
                    delete cache.data[key];
                }
            }
            try {
                localStorage.setItem(CACHE_KEY, JSON.stringify(cache));
            } catch (e2) {
                // Give up on caching
            }
        }
    }

    /**
     * Get cached chapter or null
     */
    function getCached(key) {
        const cache = getCache();
        if (cache.data[key]) {
            // Move to end (LRU)
            cache.entries = cache.entries.filter(k => k !== key);
            cache.entries.push(key);
            saveCache(cache);
            return cache.data[key];
        }
        return null;
    }

    /**
     * Cache a chapter
     */
    function cacheChapter(key, data) {
        const cache = getCache();

        // Remove oldest if at capacity
        while (cache.entries.length >= MAX_CACHE_SIZE) {
            const oldest = cache.entries.shift();
            delete cache.data[oldest];
        }

        cache.entries.push(key);
        cache.data[key] = data;
        saveCache(cache);
    }

    /**
     * Fetch a chapter from the API
     */
    async function fetchChapter(translation, book, chapter) {
        const url = `${BASE_URL}/${translation}/${book}/${chapter}.json`;

        const response = await fetch(url);
        if (!response.ok) {
            throw new Error(`Failed to fetch chapter: ${response.status}`);
        }

        const data = await response.json();
        return data;
    }

    /**
     * Get a chapter (with caching)
     */
    async function getChapter(translation, book, chapter) {
        const cacheKey = `${translation}:${book}:${chapter}`;

        // Check cache first
        const cached = getCached(cacheKey);
        if (cached) {
            return cached;
        }

        try {
            const apiData = await fetchChapter(translation, book, chapter);

            // Transform data to our format
            const verses = [];
            if (apiData.chapter && apiData.chapter.content) {
                for (const item of apiData.chapter.content) {
                    if (item.type === 'verse' && item.content) {
                        let text = '';
                        for (const content of item.content) {
                            if (typeof content === 'string') {
                                text += content;
                            } else if (content.text) {
                                text += content.text;
                            }
                        }
                        verses.push({
                            verse: item.number,
                            text: text.trim()
                        });
                    }
                }
            }

            const result = {
                translation: translation,
                book: book,
                bookName: BOOK_NAMES[book] || book,
                chapter: chapter,
                totalChapters: BOOK_CHAPTERS[book] || 1,
                verses: verses
            };

            // Cache the result
            cacheChapter(cacheKey, result);

            return result;

        } catch (error) {
            console.error('Bible API Error:', error);
            return {
                error: true,
                message: error.message
            };
        }
    }

    /**
     * Get book name
     */
    function getBookName(bookId) {
        return BOOK_NAMES[bookId] || bookId;
    }

    /**
     * Get total chapters for a book
     */
    function getTotalChapters(bookId) {
        return BOOK_CHAPTERS[bookId] || 1;
    }

    /**
     * Clear cache
     */
    function clearCache() {
        localStorage.removeItem(CACHE_KEY);
    }

    /**
     * Get cache stats
     */
    function getCacheStats() {
        const cache = getCache();
        return {
            entries: cache.entries.length,
            maxSize: MAX_CACHE_SIZE
        };
    }

    // Public API
    return {
        getChapter,
        getBookName,
        getTotalChapters,
        clearCache,
        getCacheStats,
        BOOK_NAMES,
        BOOK_CHAPTERS
    };
})();

// Make globally available
window.BibleAPI = BibleAPI;
