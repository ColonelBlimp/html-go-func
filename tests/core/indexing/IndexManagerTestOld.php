<?php declare(strict_types=1);
namespace html_go\indexing;


class IndexManagerTestOld extends IndexingTestCase
{
    /**
     * @runInSeparateProcess
     */
    function testConstructorAppRootException(): void {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unable to validate the application root [');
        new IndexManager(IndexingTestCase::APP_ROOT.DIRECTORY_SEPARATOR.'content'.CONTENT_FILE_EXT);
    }

    /**
     * @runInSeparateProcess
     */
    function testConstructorCategoryDirException(): void {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Content directory format is invalid. Directory does not exist [');
        new IndexManager(__DIR__);
    }

    /**
     * @runInSeparateProcess
     */
    function testInitialization(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $this->assertFileExists(IndexingTestCase::APP_ROOT.DS.'cache'.DS.'indexes'.DS.'slugindex.inx');
        $this->assertFileExists(IndexingTestCase::APP_ROOT.DS.'cache'.DS.'indexes'.DS.'cat2posts.inx');
        $this->assertFileExists(IndexingTestCase::APP_ROOT.DS.'cache'.DS.'indexes'.DS.'posts.inx');
        $this->assertFileExists(IndexingTestCase::APP_ROOT.DS.'cache'.DS.'indexes'.DS.'pages.inx');
        $this->assertFileExists(IndexingTestCase::APP_ROOT.DS.'cache'.DS.'indexes'.DS.'categories.inx');
    }

    /**
     * @depends testInitialization
     * @runInSeparateProcess
     */
    function testElementExists(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $this->assertTrue($manager->elementExists('contact'));
        $this->assertFalse($manager->elementExists('contact-us'));
    }

    /**
     * @depends testInitialization
     * @runInSeparateProcess
     */
    function testSlugIndex(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $elem = $manager->getElementFromSlugIndex('uncategorized');
        $this->assertNotNull($elem);
        $this->assertInstanceOf(Element::class, $elem);

        // Category
        $this->assertSame('uncategorized', $elem->getKey());
        $this->assertSame(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'common'.DS.'categories'.DS.'uncategorized'.CONTENT_FILE_EXT, $elem->getPath());
        $this->assertSame('categories', $elem->getSection());
        $this->assertSame(EMPTY_VALUE, $elem->getCategory());
        $this->assertSame(EMPTY_VALUE, $elem->getType());
        $this->assertSame(EMPTY_VALUE, $elem->getUsername());

        // Page
        $elem = $manager->getElementFromSlugIndex('about');
        $this->assertNotNull($elem);
        $this->assertSame('about', $elem->getKey());
        $this->assertSame(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'common'.DS.'pages'.DS.'about'.CONTENT_FILE_EXT, $elem->getPath());
        $this->assertSame('pages', $elem->getSection());
        $this->assertSame(EMPTY_VALUE, $elem->getCategory());
        $this->assertSame(EMPTY_VALUE, $elem->getType());
        $this->assertSame(EMPTY_VALUE, $elem->getUsername());

        // Sub-page
        $elem = $manager->getElementFromSlugIndex('apiaries/chilukwa');
        $this->assertNotNull($elem);
        $this->assertSame('apiaries/chilukwa', $elem->getKey());
        $this->assertSame(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'common'.DS.'pages'.DS.'apiaries'.DS.'chilukwa'.DS.'_index'.CONTENT_FILE_EXT, $elem->getPath());
        $this->assertSame('pages', $elem->getSection());
        $this->assertSame(EMPTY_VALUE, $elem->getCategory());
        $this->assertSame(EMPTY_VALUE, $elem->getType());
        $this->assertSame(EMPTY_VALUE, $elem->getUsername());

        // Image Post
        $elem = $manager->getElementFromSlugIndex('harvest-time');
        $this->assertNotNull($elem);
        $this->assertSame('harvest-time', $elem->getKey());
        $this->assertSame(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'user-data'.DS.'@testuser'.DS.'posts'.DS.'harvesting'.DS.'image'.DS.'20210101000000_tagone,tagtwo,tagthree_harvest-time'.CONTENT_FILE_EXT, $elem->getPath());
        $this->assertSame('posts', $elem->getSection());
        $this->assertSame('harvesting', $elem->getCategory());
        $this->assertSame('image', $elem->getType());
        $this->assertSame('@testuser', $elem->getUsername());
        $this->assertSame('20210101000000', $elem->getDate());
        $this->assertCount(3, $elem->getTags());

        // No tags
        $elem = $manager->getElementFromSlugIndex('s');
        $this->assertNotNull($elem);
        $this->assertSame('s', $elem->getKey());
        $this->assertSame(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'user-data'.DS.'@testuser'.DS.'posts'.DS.'harvesting'.DS.'regular'.DS.'20210101030000__s'.CONTENT_FILE_EXT, $elem->getPath());
        $this->assertSame('posts', $elem->getSection());
        $this->assertSame('harvesting', $elem->getCategory());
        $this->assertSame('regular', $elem->getType());
        $this->assertSame('@testuser', $elem->getUsername());
        $this->assertSame('20210101030000', $elem->getDate());
        $this->assertEmpty($elem->getTags());

        // Quote Post
        $elem = $manager->getElementFromSlugIndex('tested-quote');
        $this->assertNotNull($elem);
        $this->assertSame('tested-quote', $elem->getKey());
        $this->assertSame(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'user-data'.DS.'@testuser'.DS.'posts'.DS.'uncategorized'.DS.'quote'.DS.'20210101020000_tagone,tagtwo_tested-quote'.CONTENT_FILE_EXT, $elem->getPath());
        $this->assertSame('posts', $elem->getSection());
        $this->assertSame('uncategorized', $elem->getCategory());
        $this->assertSame('quote', $elem->getType());
        $this->assertSame('@testuser', $elem->getUsername());
        $this->assertSame('20210101020000', $elem->getDate());
        $this->assertCount(2, $elem->getTags());
    }

    /**
     * @runInSeparateProcess
     */
    function testPostsForCategory(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $posts = $manager->getPostsForCategory('uncategorized');
        $this->assertNotNull($posts);
        $this->assertNotEmpty($posts);
        $this->assertCount(2, $posts);
        foreach ($posts as $slug => $elem) {
            $this->assertSame($slug, $elem->getKey());
            $this->assertSame('uncategorized', $elem->getCategory());
        }
        $posts = $manager->getPostsForCategory('harvesting');
        $this->assertNotNull($posts);
        $this->assertNotEmpty($posts);
        $this->assertCount(2, $posts);
        foreach ($posts as $slug => $elem) {
            $this->assertSame($slug, $elem->getKey());
            $this->assertSame('harvesting', $elem->getCategory());
        }

        $this->assertNull($manager->getPostsForCategory('unknown'));
    }

    /**
     * @runInSeparateProcess
     */
    function testCategoryIndex(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $index = $manager->getCategoryIndex();
        $this->assertCount(2, $index);
        foreach ($index as $elem) {
            $this->assertSame('categories', $elem->getSection());
        }
    }

    /**
     * @runInSeparateProcess
     */
    function testPageIndex(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $index = $manager->getPageIndex();
        $this->assertCount(6, $index);
        foreach ($index as $elem) {
            $this->assertSame('pages', $elem->getSection());
        }
    }

    /**
     * @runInSeparateProcess
     */
    function testPostIndex(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $index = $manager->getPostIndex();
        $this->assertCount(4, $index);
        foreach ($index as $elem) {
            $this->assertSame('posts', $elem->getSection());
        }
    }

    /**
     * @runInSeparateProcess
     */
    function testShortFilenameException(): void {
        $this->assertTrue(touch(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'user-data'.DS.'@testuser'.DS.'posts'.DS.'harvesting'.DS.'regular'.DS.'2021010100000__s'.CONTENT_FILE_EXT));
        $this->expectException(\InvalidArgumentException::class);
        new IndexManager(IndexingTestCase::APP_ROOT);
    }

    /**
     * @runInSeparateProcess
     */
    function testSyntaxFilenameException(): void {
        $this->assertTrue(touch(\realpath(IndexingTestCase::APP_ROOT).DS.'content'.DS.'user-data'.DS.'@testuser'.DS.'posts'.DS.'harvesting'.DS.'regular'.DS.'20210101000000_wibble'.CONTENT_FILE_EXT));
        $this->expectException(\InvalidArgumentException::class);
        new IndexManager(IndexingTestCase::APP_ROOT);
    }

    /**
     * @runInSeparateProcess
     */
    function testTagIndex(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $index = $manager->getTagIndex();
        $this->assertCount(3, $index);
        foreach ($index as $elem) {
            $this->assertSame('tags', $elem->getSection());
        }
    }

    /**
     * @runInSeparateProcess
     */
    function testPostsForTag(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $posts = $manager->getPostsForTag('tagone');
        $this->assertNotNull($posts);
        $this->assertCount(3, $posts);
        $this->assertNull($manager->getPostsForTag('unknown'));
    }

    /**
     * @runInSeparateProcess
     */
    function testSlugNotFoundException(): void {
        $manager = new IndexManager(IndexingTestCase::APP_ROOT);
        $this->assertNotNull($manager);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Key [unknown] does not exist in the index. Use 'elementExists(...) before calling this method!");
        $manager->getElementFromSlugIndex('unknown');
    }
}
