<?php declare(strict_types=1);
namespace html_go\indexing;

use InvalidArgumentException;
use html_go\exceptions\InternalException;

final class IndexManager extends AbstractIndexer
{
    /** @var array<string, Element> $catIndex */
    private array $catIndex;

    /** @var array<string, Element> $pageIndex */
    private array $pageIndex;

    /** @var array<string, Element> $postIndex */
    private array $postIndex;

    /** @var array<mixed> $menuIndex */
    private array $menuIndex;

    /**
     * A tag is NOT represented by any file on the physical filessytem.
     * @var array<string, Element> $tagIndex
     */
    private array $tagIndex;

    /**
     * The slug index holds references to files on the physical filesystem.,
     * and is a combination of the catIndex, postIndex and pageIndex
     * @var array<string, Element> $slugIndex
     */
    private array $slugIndex;

    /** @var array<string, Element> $tag2postIndex */
    private array $tag2postIndex;

    /** @var array<string, Element> $cat2postIndex */
    private array $cat2postIndex;

    /**
     * IndexManager constructor.
     * @param string $parentDir The parent directory for the content directory.
     * @throws \InvalidArgumentException If the parent directory is invalid.
     * @throws InternalException
     */
    function __construct(string $parentDir) {
        parent::__construct($parentDir);
        $this->initialize();
    }

    /**
     * Rebuild all the indexes.
     */
    function reindex(): void {
        $this->catIndex = $this->buildCategoryIndex();
        $pageMenuIndex = $this->buildPageAndMenuIndexes();
        $this->pageIndex = $pageMenuIndex[0];
        $this->menuIndex = $pageMenuIndex[1];
        $this->postIndex = $this->buildPostIndex();
        $compositeIndex = $this->buildCompositeIndexes();
        $this->tagIndex = $compositeIndex[0];
        $this->tag2postIndex = $compositeIndex[1];
        $this->cat2postIndex = $compositeIndex[2];
        $this->slugIndex = \array_merge($this->postIndex, $this->catIndex, $this->pageIndex, $this->tagIndex);
    }

    /**
     * Returns an object representing an element in the index.
     * @param string $key
     * @throws \InvalidArgumentException If the given $key does not exist in the index.
     * @return Element
     */
    function getElementFromSlugIndex(string $key): Element {
        if (!isset($this->slugIndex[$key])) {
            throw new InvalidArgumentException("Key does not exist in the slugIndex! Use 'elementExists()' before calling this method.");
        }
        return $this->slugIndex[$key];
    }

    /**
     * Check if an key exists in the <b>slug index</b>.
     * @param string $key
     * @return bool <code>true</code> if exists, otherwise <code>false</code>
     */
    function elementExists(string $key): bool {
        return isset($this->slugIndex[$key]);
    }

    /**
     * Return the posts index.
     * @return array<string, Element>
     */
    function getPostsIndex(): array {
        return $this->postIndex;
    }

    /**
     * Return the category index.
     * @return array<string, Element>
     */
    function getCategoriesIndex(): array {
        return $this->catIndex;
    }

    /**
     * Return the tag index.
     * @return array<string, Element>
     */
    function getTagIndex(): array {
        return $this->tagIndex;
    }

    /**
     * Return the menus index.
     * @return array<mixed>
     */
    function getMenusIndex(): array {
        return $this->menuIndex;
    }

    /**
     * Return the tag index.
     * @return array<string, Element>
     */
    function getPageIndex(): array {
        return $this->pageIndex;
    }

    private function initialize(): void {
        $this->catIndex = $this->loadIndex($this->catInxFile);
        $this->pageIndex = $this->loadIndex($this->pageInxFile);
        $this->postIndex = $this->loadIndex($this->postInxFile);
        $this->tagIndex = $this->loadIndex($this->tagInxFile);
        $this->cat2postIndex = $this->loadIndex($this->cat2postInxFile);
        $this->tag2postIndex = $this->loadIndex($this->tag2postInxFile);
        $this->menuIndex = $this->loadIndex($this->menuInxFile);
        $this->slugIndex = \array_merge($this->postIndex, $this->catIndex, $this->pageIndex, $this->tagIndex);
    }

    /**
     * @return array<string, Element>
     */
    private function buildCategoryIndex(): array {
        $index = [];
        foreach ($this->parseDirectory($this->commonDir.DS.'category'.DS.'*'.CONTENT_FILE_EXT) as $filepath) {
            $root = \substr($filepath, \strlen($this->commonDir) + 1);
            $key = \str_replace(DS, FWD_SLASH, \substr($root, 0, \strlen($root) - CONTENT_FILE_EXT_LEN));
            $index[$key] = $this->createElement($key, $filepath, ENUM_CATEGORY);
        }
        $this->writeIndex($this->catInxFile, $index);
        return $index;
    }

    /**
     * Builds two indexes: menu and post indexes.
     * @return array<mixed>
     */
    private function buildPageAndMenuIndexes(): array {
        $pageDir = $this->commonDir.DS.'pages';
        $len = \strlen($pageDir) + 1;
        $pages = $this->scanDirectory($pageDir);
        \sort($pages);
        $menuInx = [];
        foreach ($pages as $filepath) {
            $location = \substr($filepath, $len);
            $key = \str_replace(DS, FWD_SLASH, \substr($location, 0, (\strlen($location) - CONTENT_FILE_EXT_LEN)));
            if (\str_ends_with($key, '/index')) {
                $key = substr($key, 0, \strlen($key) - 6);
            } else {
                if ($key === 'index') {
                    $key = FWD_SLASH;
                }
            }
            $pageInx[$key] = $this->createElement($key, $filepath, ENUM_PAGE);
            $menuInx = $this->mergeToMenuIndex($menuInx, $this->buildMenus($key, $filepath));
        }

        // Add Tag landing page
        $filepath = $this->commonDir.DS.'landing'.DS.'tags'.DS.'index'.CONTENT_FILE_EXT;
        $key = TAG_INDEX_KEY;
        $pageInx[$key] = $this->createElement($key, $filepath, ENUM_TAG);
        $menuInx = $this->mergeToMenuIndex($menuInx, $this->buildMenus($key, $filepath));

        // Add Category landing page
        $filepath = $this->commonDir.DS.'landing'.DS.'category'.DS.'index'.CONTENT_FILE_EXT;
        $key = CAT_INDEX_KEY;
        $pageInx[$key] = $this->createElement($key, $filepath, ENUM_CATEGORY);
        $menuInx = $this->mergeToMenuIndex($menuInx, $this->buildMenus($key, $filepath));

        // Add Blog (posts) landing page
        $filepath = $this->commonDir.DS.'landing'.DS.'blog'.DS.'index'.CONTENT_FILE_EXT;
        $key = BLOG_INDEX_KEY;
        $pageInx[$key] = $this->createElementClass($key, $filepath, ENUM_POST);
        $menuInx = $this->mergeToMenuIndex($menuInx, $this->buildMenus($key, $filepath));

        $this->writeIndex($this->pageInxFile, $pageInx);
        $menuInx = $this->orderMenuEntries($menuInx);
        $this->writeIndex($this->menuInxFile, $menuInx);
        return [$pageInx, $menuInx];
    }

    /**
     * Builds the post index.
     * @return array<string, Element>
     */
    private function buildPostIndex(): array {
        $index = [];
        foreach ($this->parseDirectory($this->userDataDir.DS.'*'.DS.'posts'.DS.'*'.DS.'*'.DS.'*'.CONTENT_FILE_EXT) as $filepath) {
            $key = \pathinfo($filepath, PATHINFO_FILENAME);
            $element = $this->createElement($key, $filepath, ENUM_POST);
            $index[(string)$element->key] = $element;
        }
        $this->writeIndex($this->postInxFile, $index);
        return $index;
    }

    /**
     * Reads the given file and creates an array of menus in which this
     * resource is listed.
     * @return array<mixed>
     */
    private function buildMenus(string $key, string $filepath): array {
        if (empty($key)) {
            throw new \InvalidArgumentException("Key is empty for [$filepath]"); // @codeCoverageIgnore
        }
        if (($json = \file_get_contents($filepath)) === false) {
            throw new InternalException("file_get_contents() failed reading [$filepath]"); // @codeCoverageIgnore
        }
        $data = \json_decode($json, true);
        $menus = [];
        if (isset($data['menus'])) {
            foreach ($data['menus'] as $name => $defs) {
                $node = new \stdClass();
                $node->key = $key;
                foreach ($defs as $label => $value) {
                    $node->$label = $value;
                }
                $menus[$name][] = $node;
            }
        }
        return $menus;
    }

    /**
     * Does a <code>usort</code> on the <code>weight</code> property.
     * @param array<mixed> $index the unsorted array
     * @return array<mixed> the sorted array
     */
    private function orderMenuEntries(array $index): array {
        foreach ($index as $name => $defs) {
            \usort($defs, function($a, $b): int {
                if ($a->weight === $b->weight) {
                    return 0;
                }
                return  $a->weight > $b->weight ? 1 : -1;
            });
            $index[$name] = $defs;
        }
        return $index;
    }

    /**
     * Builds three indexes: 'category 2 posts', 'tag 2 posts' and tag index.
     * @throws InternalException
     * @return array<mixed>
     */
    private function buildCompositeIndexes(): array {
        $tagInx = [];
        $tag2PostsIndex = [];
        $cat2PostIndex = [];
        foreach ($this->postIndex as $post) {
            if (!isset($post->key, $post->tags, $post->category)) {
                throw new InternalException("Invalid format of index element: ".print_r($post, true)); // @codeCoverageIgnore
            }
            foreach ($post->tags as $tag) {
                $key = 'tag'.FWD_SLASH.(string)$tag;
                $tagInx[$key] = $this->createElementClass($key, EMPTY_VALUE, ENUM_TAG);
                $tag2PostsIndex[$key][] = $post->key;
            }
            $cat2PostIndex[$post->category] = $post->key;
        }
        $this->writeIndex($this->tagInxFile, $tagInx);
        $this->writeIndex($this->tag2postInxFile, $tag2PostsIndex);
        $this->writeIndex($this->cat2postInxFile, $cat2PostIndex);
        return [$tagInx, $tag2PostsIndex, $cat2PostIndex];
    }

    /**
     * Merge the given menu array into the master menu index returning the new
     * master menu index.
     * @param array<mixed> $initial
     * @param array<mixed> $toMerge The menu array to be merged.
     * @return array<mixed>
     */
    private function mergeToMenuIndex(array $initial, array $toMerge): array {
        foreach ($toMerge as $name => $def) {
            if (isset($initial[$name])) {
                $nodes = $initial[$name];
                $initial[$name] = \array_merge($nodes, $def);
            } else {
                $initial[$name] = $def;
            }
        }
        return $initial;
    }

    /**
     * Create an Element object.
     * @param string $key
     * @param string $filepath
     * @param string $section
     * @throws InternalException
     * @throws InvalidArgumentException
     * @return Element
     */
    private function createElement(string $key, string $filepath, string $section): Element {
        if (empty($key)) {
            throw new InternalException("Key is empty for [$filepath]"); // @codeCoverageIgnore
        }
        switch ($section) {
            case ENUM_CATEGORY:
            case ENUM_TAG:
            case ENUM_PAGE:
                return $this->createElementClass($key, $filepath, $section);
            case ENUM_POST:
                if (\strlen($key) < 17) {
                    throw new InvalidArgumentException("Post content filename is too short [$key]");
                }
                $pathinfo = \pathinfo($filepath);
                $dateString = \substr($key, 0, 14);
                $start = 15;
                if (($end = \strpos($key, '_', $start)) === false) {
                    throw new InvalidArgumentException("Post content filename syntax error [$key]");
                }
                $tagList = \substr($key, $start, $end - $start);
                $title = \substr($key, $end + 1);
                $year = \substr($dateString, 0, 4);
                $month = \substr($dateString, 4, 2);
                $key = $year.FWD_SLASH.$month.FWD_SLASH.$title;
                $parts = \explode(DS, $pathinfo['dirname']);
                $cnt = \count($parts);
                return $this->createElementClass($key, $filepath, ENUM_POST, $parts[$cnt - 1], $parts[$cnt - 2], $parts[$cnt - 4], $dateString, $tagList);
            default:
                throw new InternalException("Unknown section [$section]"); // @codeCoverageIgnore
        }
    }

    /**
     * Creates and populates an index Element class.
     * @param string $key The index key
     * @param string $path The filepath
     * @param string $type
     * @param string $section 'pages', 'posts', 'categories' or 'tags'
     * @param string $category
     * @param string $username
     * @param string $date
     * @param string $tagList
     * @return Element stdClass
     */
    private function createElementClass(string $key, string $path, string $section, string $type = EMPTY_VALUE, string $category = EMPTY_VALUE, string $username = EMPTY_VALUE, string $date = EMPTY_VALUE, string $tagList = EMPTY_VALUE): Element {
        $tags = [];
        if (!empty($tagList)) {
            $tags = \explode(',', $tagList);
        }
        $obj = new Element();
        $obj->key = $key;
        $obj->path = $path;
        $obj->type = $type;
        $obj->section = $section;
        $obj->category = $category;
        $obj->username = $username;
        $obj->date = $date;
        $obj->tags = $tags;
        return $obj;
    }
}
