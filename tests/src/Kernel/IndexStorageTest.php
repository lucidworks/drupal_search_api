<?php

/**
 * @file
 * Contains \Drupal\Tests\search_api\Kernel\IndexStorageTest.
 */

namespace Drupal\Tests\search_api\Kernel;

use Drupal\Core\Config\Entity\ConfigEntityStorage;
use Drupal\search_api\IndexInterface;
use Drupal\KernelTests\KernelTestBase;

/**
 * Tests whether the storage of search indexes works correctly.
 *
 * @group search_api
 */
class IndexStorageTest extends KernelTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = array('search_api', 'user', 'system');

  /**
   * The search index storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $storage;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->installSchema('system', 'key_value_expire');

    $this->storage = $this->container->get('entity_type.manager')->getStorage('search_api_index');
  }

  /**
   * Tests all CRUD operations as a queue of operations.
   */
  public function testIndexCRUD() {
    $this->assertTrue($this->storage instanceof ConfigEntityStorage, 'The Search API Index storage controller is loaded.');

    $index = $this->indexCreate();
    $this->indexLoad($index);
    $this->indexDelete($index);
  }

  /**
   * Tests whether creating an index works correctly.
   *
   * @return \Drupal\search_api\IndexInterface
   *  The newly created search index.
   */
  public function indexCreate() {
    $indexData = array(
      'id' => 'test',
      'name' => 'Index test name',
      'tracker' => 'default',
    );

    $index = $this->storage->create($indexData);
    $this->assertTrue($index instanceof IndexInterface, 'The newly created entity is a search index.');
    $index->save();

    return $index;
  }

  /**
   * Tests whether loading an index works correctly.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index used for the test.
   */
  public function indexLoad(IndexInterface $index) {
    $loaded_index = $this->storage->load($index->id());
    $this->assertSame($index->label(), $loaded_index->label());
  }

  /**
   * Tests whether deleting an index works correctly.
   *
   * @param \Drupal\search_api\IndexInterface $index
   *   The index used for the test.
   */
  public function indexDelete(IndexInterface $index) {
    $this->storage->delete(array($index));
    $loaded_index = $this->storage->load($index->id());
    $this->assertNull($loaded_index);
  }

}