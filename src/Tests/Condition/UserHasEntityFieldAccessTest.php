<?php

/**
 * @file
 * Contains \Drupal\rules\Tests\Condition\UserHasEntityFieldAccessTest.
 *
 * Much of the field related code frankly lifted from
 *   Drupal\field\Tests\FieldAccessTest
 */

namespace Drupal\rules\Tests\Condition;

use Drupal\system\Tests\Entity\EntityUnitTestBase;

/**
 * Tests the 'User has field access' condition.
 */
class UserHasEntityFieldAccessTest extends EntityUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['node', 'rules'];

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;

  /**
   * The node storage.
   *
   * @var \Drupal\node\NodeStorage
   */
  protected $nodeStorage;
  
  /**
   * A test node set up with access control
   *
   * @var \Drupal\node\Entity\Node
   */
  protected $node;

  /**
   * Field instance config for tests
   *
   * @var array
   */
  protected $test_field_config;
  
  

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return [
      'name' => 'User has entity field access condition test',
      'description' => 'Tests to see if the user has access to a given field.',
      'group' => 'Rules conditions',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setup();
    $this->conditionManager = $this->container->get('plugin.manager.condition', $this->container->get('container.namespaces'));
    $this->nodeStorage = $this->entityManager->getStorage('node');

    $this->entityManager->getStorage('node_type')
      ->create(['type' => 'page'])
      ->save();

    
    $this->test_field_config = $this->createTestField();

    $this->node = $this->nodeStorage->create([
      'type' => 'page',
      'status' => 1,
    ]);
    
    
    $this->user_with_access = $this->getUser();
    $this->user_with_access->save();
    $uid = $this->user_with_access->uid->value;
    
    $this->user_without_access = $this->getUser();
    $uid2 = $this->user_without_access->save();
    
    
  }
  
  /**
   * Create a text field for testing
   * 
   * @return array
   * @todo move this into a parent class so we can reuse it
   * @see Drupal\field\Tests\FieldAccessTest::setup()
   */
  protected function createTestField($values = []) {
    // Create content type.
    $content_type = 'page';
    $field = array(
      'name' => 'test_view_field',
      'entity_type' => 'node',
      'type' => 'text',
    );
    entity_create('field_config', $field)->save();
    $instance = array(
      'field_name' => $field['name'],
      'entity_type' => 'node',
      'bundle' => $content_type,
    );
    $field_instance_config = entity_create('field_instance_config', $instance)->save();
    return $instance;
  }

  /**
   * 
  /**
   * Returns an user object for testing.
   *
   * @return \Drupal\user\UserInterface
   */
  protected function getUser($values = []) {
    // @todo: Use an entity factory once we have on instead.
    $user_name = $this->randomName();
    return entity_create('user', $values + [
      'name' => $user_name,
      'mail' => "$user_name@email.com",
      'status' => 1,
    ]);
  }
  
  
  
    

  /**
   * Tests evaluating the condition.
   */
  public function testConditionEvaluation() {
    $condition = NULL;
    try {
      $condition = $this->conditionManager->createInstance('rules_entity_field_access');
    }
    catch (\Exception $e) {
    }
    
    $was_set = isset($condition);
    $this->assertNotNull($condition, "rules_entity_field_access plugin was discovered");

    $condition
      ->setContextValue('entity', $this->node)
      ->setContextValue('field', $this->test_field_config['field_name'])
      ->setContextValue('op', 'view')
      ->setContextValue('account', $this->user_with_access);
      
    $this->assertTrue($condition->execute(), 'User has access to our field');
  }
  

}
