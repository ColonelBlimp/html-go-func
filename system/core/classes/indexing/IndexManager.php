<?php declare(strict_types=1);
namespace html_go\indexing;

if (!\defined('MODE')) {
    \define('MODE', 0777);
}
if (!\defined('EMPTY_VALUE')) {
    \define('EMPTY_VALUE', '<empty>');
}
//TODO: Replace this with ENUMS from php 8.1
\define('ENUM_PAGE', 'pages');
\define('ENUM_CATEGORY', 'categories');
\define('ENUM_POST', 'posts');
\define('ENUM_TAG', 'tags');

final class IndexManager
{
    private const CATEGORIES_DIR = 'content'.DS.'common'.DS.'categories';
    private const PAGES_DIR = 'content'.DS.'common'.DS.'pages';
    private const INDEX_DIR = 'cache'.DS.'indexes';
    private const USER_DATA_DIR = 'content'.DS.'user-data';
    private const SLUG_INDEX_FILE = self::INDEX_DIR.DS.'slugindex.inx';
    private const PAGE_INDEX_FILE = self::INDEX_DIR.DS.'pages.inx';
    private const POST_INDEX_FILE = self::INDEX_DIR.DS.'posts.inx';
    private const CAT_INDEX_FILE = self::INDEX_DIR.DS.'categories.inx';
    private const TAG_INDEX_FILE = self::INDEX_DIR.DS.'tags.inx';

    private string $root;

    /**
     * @var array<string, Element> $slugIndex
     */
    private array $slugIndex;

    /**
     * @var array<string, array<int, string>> $categoryToPostIndex
     */
    private array $categoryToPostIndex;

    /**
     * @var array<string, array<int, string>> $tagToPostIndex
     */
    private array $tagToPostIndex;

    /**
     * @var array<string, Element> $postIndex
     */
    private array $postIndex;

    /**
     * @var array<string, Element> $pageIndex
     */
    private array $pageIndex;

    /**
     * @var array<string, Element> $categoryIndex
     */
    private array $categoryIndex;

    /**
     * @var array<string, Element> $tagIndex
     */
    private array $tagIndex;

    /**
     * IndexManager constructor.
     * @param string $root
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    function __construct(string $root) {
        if (($tmp = \realpath($root)) === false) {
            throw new \InvalidArgumentException("Unable to validate the location of the 'content' directory [$root]");
        }
        $this->root = $tmp;
        if (!\is_dir($this->root.DS.self::CATEGORIES_DIR)) {
            $tmp = $this->root.DS.self::CATEGORIES_DIR;
            throw new \RuntimeException(
                "Content directory format is invalid. Directory does not exist [$tmp]");
        }
        if (!\is_dir($this->root.DS.self::PAGES_DIR)) {
            $tmp = $this->root.DS.self::PAGES_DIR; // @codeCoverageIgnore
            throw new \RuntimeException("Content directory format is invalid. Directory does not exist [$tmp]"); // @codeCoverageIgnore
        }
        if (!\is_dir($this->root.DS.self::USER_DATA_DIR)) {
            $tmp = $this->root.DS.self::USER_DATA_DIR; // @codeCoverageIgnore
            throw new \RuntimeException("Content directory format is invalid. Directory does not exist [$tmp]"); // @codeCoverageIgnore
        }
        if (!\file_exists($this->root.DS.self::INDEX_DIR)) {
            if (\mkdir($this->root.DS.self::INDEX_DIR, MODE, true) === false) {
                $tmp = $this->root.DS.self::INDEX_DIR;
                throw new \RuntimeException("Unable to create directory [$tmp]");
            }
        }
        $this->Initialize();
    }

    /**
     * Initialize the indexing system and create the indexes files if needed.
     */
    private function Initialize(): void {
        if (\file_exists($this->root.DS.self::SLUG_INDEX_FILE) === false) {
            $this->categoryIndex = $this->buildCategoryIndex();
            $this->pageIndex = $this->buildPageIndex();
            $this->postIndex = $this->buildPostsIndex();
            $this->buildTagIndex();
            return;
        }

        $this->categoryIndex = $this->loadIndex($this->root.DS.self::CAT_INDEX_FILE);
        $this->postIndex = $this->loadIndex($this->root.DS.self::POST_INDEX_FILE);
        $this->pageIndex = $this->loadIndex($this->root.DS.self::PAGE_INDEX_FILE);
        $this->tagIndex = $this->loadIndex($this->root.DS.self::TAG_INDEX_FILE);
        $this->slugIndex = \array_merge($this->postIndex, $this->categoryIndex, $this->pageIndex);
    }

    /**
     * Scans the <i>content/common/categories</i> folder creating and indexing all the files.
     * When the index is built, it is also loaded.
     * @return array<int, object>
     */
    private function buildCategoryIndex(): array {
        $index = [];
        foreach ($this->parseDirectory($this->root.DS.self::CATEGORIES_DIR.DS.'*'.CONTENT_FILE_EXT) as $filepath) {
            $key = 'category'.FWD_SLASH.\pathinfo($filepath, PATHINFO_FILENAME);
            $index[] = $this->createElement($filepath, $key);
        }
        $this->writeIndex($this->root.DS.self::CAT_INDEX_FILE, $index);
        return $index;
    }

    /**
     * Scans the <i>content/pages</i> folder creating and indexing all the files and folders.
     * When the index is built, it is also loaded.
     * @return array<int, object>
     */
    private function buildPageIndex(): array {
        $index = [];
        $pagesRoot = $this->root.DS.self::PAGES_DIR;
        $len = \strlen($pagesRoot) + 1;
        $pages = $this->scanDirectory($pagesRoot);
        \sort($pages);
        foreach ($pages as $filepath) {
            $index[] = $this->createElement($filepath, \substr(\substr($filepath, $len), 0, -3));
        }
        $this->writeIndex($this->root.DS.self::INDEX_DIR.DS.'pages.inx', $index);
        return $index;
    }

    /**
     * Scans the <i>content/user-data/[username]/posts</i> folder creating and indexing all files.
     * When the index is built, it is also loaded.
     * @return array<int, object>
     */
    private function buildPostsIndex(): array {
        $index = [];
        foreach ($this->parseDirectory($this->root.DS.self::USER_DATA_DIR.DS.'*'.DS.'posts'.DS.'*'.DS.'*'.DS.'*'.CONTENT_FILE_EXT) as $filepath) {
            $index[] = $this->createElement($filepath, \pathinfo($filepath, PATHINFO_FILENAME));
        }
        $this->writeIndex($this->root.DS.self::INDEX_DIR.DS.'posts.inx', $index);
        return $index;
    }

    /**
     * Scans through all the posts extracting the tags.
     * When the index is built, it is also loaded.
     * @return array<int, object>
     */
    private function buildTagIndex(): array {
        $index = [];
        foreach ($this->postIndex as $post) {
            $tags = $post->tags;
            foreach ($tags as $tag) {
                $title = \ucfirst(\str_replace('-', ' ', $tag));
                $index[$tag] = $this->createElementClass($tag, $title, ENUM_TAG);
            }
        }
        $this->writeIndex($this->root.DS.self::INDEX_DIR.DS.'tags.inx', $index);
        return $index;
    }

    /**
     * Recursively scans a folder heirarchy.
     * @return array<int, string>
     */
    private function scanDirectory(string $rootDir): array {
        static $files = [];
        if (($handle = \opendir($rootDir)) === false) {
            throw new \ErrorException("opendir() failed [$rootDir]"); // @codeCoverageIgnore
        }
        while (($entry = \readdir($handle)) !== false) {
            $path = $rootDir.DS.$entry;
            if (\is_dir($path)) {
                if ($entry === '.' || $entry === '..') continue;
                $this->scanDirectory($path);
                continue;
            }
            $files[] = $path;
        }
        \closedir($handle);
        return $files;
    }

    /**
     * @return array<int, string>
     */
    private function parseDirectory(string $pattern): array {
        if (($files = \glob($pattern, GLOB_NOSORT)) === false) {
            throw new \RuntimeException("glob() failed [$pattern]"); // @codeCoverageIgnore
        }
        return $files;
    }

    /**
     * Parses the given filepath parameter and creates a <code>stdClass</code> object to represent
     * an index element.
     * @param string $filepath
     * @param string $key Default is <code>null</code> and used with processing a post, otherwise
     * the key should be provided.
     * @throws \InvalidArgumentException
     * @return object
     */
    private function createElement(string $filepath, string $key = null): object {
        $pathinfo = \pathinfo($filepath);
        if ($key === null) {
            $key = $pathinfo['filename'];
        }
        if (\strpos($filepath, self::CATEGORIES_DIR) !== false) {
            return $this->createElementClass($key, $filepath, ENUM_CATEGORY);
        }
        if (\strpos($filepath, self::PAGES_DIR) !== false) {
            return $this->createElementClass($key, $filepath, ENUM_PAGE);
        }
        if (\strlen($key) < 17) {
            throw new \InvalidArgumentException("Content filename is too short [$key]");
        }
        $dateString = \substr($key, 0, 14);
        $start = 15;
        if (($end = \strpos($key, '_', $start)) === false) {
            throw new \InvalidArgumentException("Content filename syntax error [$key]");
        }
        $tagList = \substr($key, $start, $end-$start);
        $title = \substr($key, $end + 1);
        $year = \substr($dateString, 0, 4);
        $month = \substr($dateString, 5, 2);
        $key = $year.FWD_SLASH.$month.FWD_SLASH.$title;
        $parts = \explode(DS, $pathinfo['dirname']);
        $cnt = \count($parts);
        return $this->createElementClass($key, $filepath, ENUM_POST, $parts[$cnt - 2], $parts[$cnt - 1], $parts[$cnt - 4], $dateString, $tagList);
    }

    /**
     * Creates and populates a stdClass for an index element.
     * @param string $key
     * @param string $path
     * @param string $section
     * @param string $category
     * @param string $type
     * @param string $username
     * @param string $date
     * @param string $tagList
     * @return object stdClass
     */
    private function createElementClass(string $key, string $path, string $section, string $category = EMPTY_VALUE, string $type = EMPTY_VALUE, string $username = EMPTY_VALUE, string $date = EMPTY_VALUE, string $tagList = ''): object {
        $tags = [];
        if (!empty($tagList)) {
            $tags = \explode(',', $tagList);
        }
        $obj = new \stdClass();
        $obj->key = $key;
        $obj->path = $path;
        $obj->section = $section;
        $obj->category = $category;
        $obj->type = $type;
        $obj->username = $username;
        $obj->date = $date;
        $obj->tags = $tags;
        return $obj;
    }

    /**
     * Writes data to an index file, creating the file if necessary.
     * @param array<mixed> $index
     */
    private function writeIndex(string $filepath, array $index): void {
        $index = \serialize($index);
        if (\file_put_contents($filepath, print_r($index, true)) === false) {
            throw new \RuntimeException("file_put_contents() failed [$filepath]"); // @codeCoverageIgnore
        }
    }

    /**
     * Reads the given index file and returns it as an array.
     * @return array<mixed>
     */
    private function loadIndex(string $filepath): array {
        if (\file_exists($filepath) === false) {
            throw new \InvalidArgumentException("Cannot load index file. Does not exist [$filepath]"); // @codeCoverageIgnore
        }
        if (($data = \file_get_contents($filepath)) === false) {
            throw new \ErrorException("file_get_contents() failed [$filepath]"); // @codeCoverageIgnore
        }
        if (($data = \unserialize($data)) === false) {
            throw new \ErrorException("unserialize() failed [$filepath]"); // @codeCoverageIgnore
        }
        return $data;
    }
}
