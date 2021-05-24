<?php declare(strict_types=1);
namespace html_go\model;

use PHPUnit\Framework\TestCase;
use html_go\indexing\IndexManager;
use html_go\markdown\ParsedownParser;

class ModelFactoryTest extends TestCase
{
    function testCreat(): void {
        $cfg = new Config(TEST_APP_ROOT.DS.'test-data'.DS.'config');
        $parser = new ParsedownParser();
        $factory = new ModelFactory($cfg, $parser);
        $manager = new IndexManager(TEST_DATA_ROOT);
        $content = $factory->createSingleContentObject($manager->getElementFromSlugIndex('index'));
        $this->assertNotNull($content);
        $obj = new \stdClass();
        $obj->title = 'List Object 1';
        $content = $factory->createListContentObject("Test List", [$obj]);
        $this->assertNotNull($content);
    }
}
