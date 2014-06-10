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
use Drupal\Core\Entity\ContentEntityInterface;


/**
 * Tests the 'User has field access' condition.
 */
class UserHasEntityFieldAccessTest extends EntityUnitTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['entity_test', 'rules'];

  /**
   * The condition manager.
   *
   * @var \Drupal\Core\Condition\ConditionManager
   */
  protected $conditionManager;
  
  
  protected $entityStorage;
  
  protected $entityType;
  
  /**
   * A test entity set up with access control
   *
   * @var \Drupal\Core\Entity\ContentEntityInterface
   */
  protected $entity;

  /**
   * Field instance config for tests
   *
   * @var array
   */
  protected $testFieldConfig;
  
  /**
   * A user with access rights to the test field
   *
   * @var \Drupal\user\UserInterface
   */
  protected $testUser;

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
    $this->testUser = $this->createUser();
    entity_test_install();
    $this->entity = $this->createTestEntity('entity_test');
    $this->entity->save();    
  }

  /**
   * Creates a test entity.
   *
   * Borrowed, cheerfully, from Drupal\system\Tests\Entity\EntityFieldTest.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   */
  protected function createTestEntity($entity_type) {
    $entity_name = $this->randomName();
    $entity_user = $this->createUser();
    $entity_field_text = $this->randomName();

    // Pass in the value of the name field when creating. With the user
    // field we test setting a field after creation.
    $entity = entity_create($entity_type);
    $entity->user_id->target_id = $this->testUser->id();
    $entity->name->value = $entity_name;

    // Set a value for the test field.
    $fld = $entity->field_test_text;
    $fld->value = $entity_field_text;

    return $entity;
  }
  
  /**
   * Create a text field for testing
   * 
   * @return array
   * @todo move this into a parent class so we can reuse it
   * @see Drupal\field\Tests\FieldAccessTest::setup()
   */
  protected function createTestField($values = []) {
    
    $field_instance_config = entity_create('field_instance_config', $instance)->save();
    return $instance;
  }


  /**
   * Tests evaluating the condition.
   */
  public function testConditionEvaluation() {
    //first, test an unrestricted field.
    //See entity_test_entity_field_access() for how the access tests work.
    $this->entity->field_test_text->value = 'No restrictions!';
    $condition = $this->conditionManager->createInstance('rules_entity_field_access');    
    $condition
      ->setContextValue('entity', $this->entity)
      ->setContextValue('field', 'field_test_text')
      ->setContextValue('op', 'view')
      ->setContextValue('account', $this->testUser);
      
    $this->assertTrue($condition->execute(), 'User should have access to our field');
    
    //Now try a case where we should not have access
    $this->entity->field_test_text->value = 'no access value';
    $condition = $this->conditionManager->createInstance('rules_entity_field_access');    
    $condition
      ->setContextValue('entity', $this->entity)
      ->setContextValue('field', 'field_test_text')
      ->setContextValue('op', 'view')
      ->setContextValue('account', $this->testUser);

    $this->assertFalse($condition->execute(), 'User should not have access to our field');
  }
}
