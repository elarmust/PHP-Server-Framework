<?php

namespace Framework\Tests\Tests;

use OpenSwoole\Coroutine;
use PHPUnit\Framework\TestCase;
use Framework\Database\Database;
use Framework\Tests\Tests\Data\TestModel;
use Framework\Model\Exception\ModelException;

class ModelTest extends TestCase {
    private TestModel $model;

    static function tearDownAfterClass(): void {
        $model = FRAMEWORK->getClassContainer()->get(TestModel::class, useCache: false);
        $model->getDatabase()->query('DROP TABLE IF EXISTS models_testmodel');
    }

    protected function setUp(): void {
        $this->model = FRAMEWORK->getClassContainer()->get(TestModel::class, useCache: false);
        $this->model->getDatabase()->query('DROP TABLE IF EXISTS models_testmodel');
        $this->model->getDatabase()->query('
            CREATE TABLE IF NOT EXISTS
                ' . $this->model->getTableName() . ' (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(255) DEFAULT NULL,
                    age INT DEFAULT NULL
                )
        ');
    }

    public function testTableNameReturnsDefault() {
        $tableName = $this->model->getTableName();
        $this->assertEquals('models_testmodel', $tableName);
    }

    public function testGetDatabase() {
        $database = $this->model->getDatabase();
        $this->assertInstanceOf(Database::class, $database);
    }

    public function testGetProperties() {
        $properties = $this->model->getProperties();
        $this->assertIsArray($properties);
    }

    public function testIsDataProperty() {
        $this->model->modifyProperties([
            'name',
            'age',
            'nonDataProperty' => [
                'notData' => true
            ]
        ]);

        $this->assertTrue($this->model->isDataProperty('name'));
        $this->assertTrue($this->model->isDataProperty('age'));
        $this->assertFalse($this->model->isDataProperty('notDataProperty'));
        $this->assertFalse($this->model->isDataProperty('doesNotExist'));
    }

    public function testGetDefaultValue() {
        $this->model->modifyProperties([
            'name' => [
                'default' => 'test name'
            ],
            'age' => [
                'default' => 30
            ],
            'test'
        ]);

        $this->assertEquals('test name', $this->model->getDefaultValue('name'));
        $this->assertEquals(30, $this->model->getDefaultValue('age'));
        $this->assertEquals(null, $this->model->getDefaultValue('test'));
        $this->assertEquals(null, $this->model->getDefaultValue('doesNotExist'));
    }

    public function testIsPropertyPersistent() {
        $this->model->modifyProperties([
            'name' => [
                'default' => 'test name'
            ],
            'age' => [
                'default' => 30
            ],
            'nonPersistent' => [
                'persistent' => false
            ],
            'test'
        ]);

        $this->assertTrue($this->model->isPropertyPersistent('name'));
        $this->assertTrue($this->model->isPropertyPersistent('age'));
        $this->assertFalse($this->model->isPropertyPersistent('nonPersistent'));
        $this->assertTrue($this->model->isPropertyPersistent('test'));
        $this->assertFalse($this->model->isPropertyPersistent('doesNotExist'));
    }

    public function testIsPropertyReadonly() {
        $this->model->modifyProperties([
            'name' => [
                'default' => 'test name',
                'readonly' => true
            ],
            'age' => [
                'default' => 30
            ],
            'test'
        ]);

        $this->assertTrue($this->model->isPropertyReadonly('name'));
        $this->assertFalse($this->model->isPropertyReadonly('age'));
        $this->assertFalse($this->model->isPropertyReadonly('test'));
        $this->assertFalse($this->model->isPropertyReadonly('doesNotExist'));
    }

    public function testClonedModelWithNewData() {
        $this->model->modifyProperties(['name', 'age']);
        $currentProperties = $this->model->getProperties();
        $data = ['name' => 'testname', 'age' => 50];
        $model = $this->model->withData($data);

        $this->assertNotSame($this->model, $model);
        $this->assertEquals(['id' => $model->id()] + $data, $model->getData());

        // Check that the properties are not changed.
        $this->assertEquals($currentProperties, $model->getProperties());
    }

    public function testProperties() {
        $currentProperties = $this->model->getProperties();
        $properties = [
            'name' => [
                'default' => 'test name',
                'customProperty' => 'test value',
                'anotherCustomProperty' => 'test value 2'
            ],
            'age' => [
                'default' => 30
            ]
        ];
        $this->model->modifyProperties($properties);

        $retrievedProperties = $this->model->getProperties();
        $this->assertEquals(array_merge($properties, $currentProperties), $retrievedProperties);
    }

    public function testRemoveProperties() {
        $properties = [
            'name' => [
                'readonly' => false
            ],
            'age' => [
                'key' => [
                    'value' => 'test',
                    'value2' => 'test2',
                    'value3' => 'test3'
                ]
            ],
        ];

        $this->model->modifyProperties($properties);
        $this->model->removeProperties([
            'name', // Remove name
            'age' => [
                'key' => ['value2', 'value3'] // From age->key remove value2 and value3
            ]
        ]);

        $retrievedProperties = $this->model->getProperties();
        $this->assertArrayNotHasKey('name', $retrievedProperties);
        $this->assertArrayHasKey('age', $retrievedProperties);
        $this->assertArrayHasKey('key', $retrievedProperties['age']);
        $this->assertArrayHasKey('value', $retrievedProperties['age']['key']);
        $this->assertArrayNotHasKey('value2', $retrievedProperties['age']['key']);
        $this->assertArrayNotHasKey('value3', $retrievedProperties['age']['key']);
    }

    public function testDefaultDataValues() {
        $properties = [
            'name' => [
                'default' => 'test name'
            ],
            'age' => [
                'default' => 30
            ]
        ];
        $this->model->modifyProperties($properties);

        $this->assertEquals('test name', $this->model->name);
        $this->assertEquals(30, $this->model->age);
    }

    public function testCreateAndLoadSimpleData(): void {
        $this->model->modifyProperties(['name']);

        $model = $this->model->create(['name' => 'test']);
        $this->assertEquals('test', $model->name);
        $this->assertEquals(1, $model->id());

        $model = $this->model->load($model->id());
        $this->assertEquals('test', $model->name);
        $this->assertEquals(1, $model->id());
    }

    public function testSetData(): void {
        $this->model->modifyProperties(['name']);
        $model = $this->model->create(['name' => 'test']);
        $this->assertEquals('test', $model->name);
        $model->setData(['name' => 'test2'])->save();

        $model = $this->model->load($model->id());
        $this->assertEquals('test2', $model->name);
    }

    public function testCreatedAt() {
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD created_at DATETIME NULL DEFAULT NULL');
        $this->model->modifyProperties(['created_at']);

        $model = $this->model->create();
        $this->assertNotNull($model->created_at);
    }

    public function testUpdatedAt() {
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD saved_at DATETIME NULL DEFAULT NULL');
        $this->model->modifyProperties(['saved_at']);

        $model = $this->model->create();

        $model->setData(['name' => 'test2'])->save();
        $updatedAt = $model->saved_at;
        $this->assertNotNull($updatedAt);
        $this->assertEquals('test2', $model->name);
        Coroutine::sleep(1);
        $model->setData(['name' => 'test3'])->save();
        $this->assertNotEquals($updatedAt, $model->saved_at);
        $this->assertEquals('test3', $model->name);
    }

    public function testDelete(): void {
        $model = $this->model->create();
        $model->delete();

        try {
            $model = $this->model->load($model->id());
            $this->assertFalse(true, 'Unreachable');
        } catch (ModelException $e) {
            $this->assertEquals(ModelException::class, get_class($e));
        }
    }

    public function testRestoreNonRestorable(): void {
        $this->model->modifyProperties(['name']);
        $model = $this->model->create(['name' => 'test']);
        $this->assertEquals('test', $model->name);
        $model->delete()->restore();

        try {
            $model = $this->model->load(1, includeArchived: true);
            $this->assertFalse(true, 'Unreachable');
        } catch (ModelException $e) {
            $this->assertEquals(ModelException::class, get_class($e));
        }
    }

    public function testRestore(): void {
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD deleted_at DATETIME DEFAULT NULL');
        $this->model->modifyProperties(['name', 'deleted_at']);
        $model = $this->model->create(['name' => 'test']);
        $this->assertEquals('test', $model->name);
        $model->delete();

        $model = $this->model->load(1, includeArchived: true);
        $this->assertNotEquals(null, $model->deleted_at);

        $model->restore();
        $this->assertEquals(null, $model->deleted_at);
    }

    public function testReadOnlyProperty() {
        $this->model->modifyProperties([
            'name' => [
                'readonly' => true
            ]
        ]);
        // Readonly property can only be set on new model.
        $model = $this->model->create(['name' => 'test']);

        try {
            $model->id = 123;
            $this->assertFalse(true, 'Unreachable');
        } catch (ModelException $e) {
            $this->assertEquals(1, $model->id());
            $this->assertEquals(ModelException::class, get_class($e));
        }
    }

    public function testDefaultValuesWithCreate() {
        $properties = [
            'name' => [
                'default' => 'test name'
            ],
            'age' => [
                'default' => 30
            ]
        ];
        $this->model->modifyProperties($properties);

        $model = $this->model->create();

        $this->assertEquals('test name', $model->name);
        $this->assertEquals(30, $model->age);
    }

    public function testNonPersistentDataValues() {
        $properties = [
            'name' => [
                'default' => 'test name'
            ],
            'age' => [
                'default' => 30
            ],
            'nonPersistent' => [
                'default' => 'test',
                'persistent' => false
            ]
        ];
        $this->model->modifyProperties($properties);

        $model = $this->model->create(['nonPersistent' => 'test2']);
        $this->assertEquals('test2', $model->nonPersistent);

        $model = $this->model->load($model->id());
        $this->assertEquals('test', $model->nonPersistent);
    }

    public function testMultiplePropertiesAndData() {
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD email VARCHAR(255) DEFAULT NULL');
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD created_at DATETIME NULL DEFAULT NULL');
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD saved_at DATETIME NULL DEFAULT NULL');
        $this->model->getDatabase()->query('ALTER TABLE models_testmodel ADD deleted_at DATETIME NULL DEFAULT NULL');
        $properties = [
            'name' => [
                'default' => 'Test Name'
            ],
            'age' => [
                'default' => 30
            ],
            'email' => [
                'default' => 'default@email.address',
                'readonly' => true
            ],
            'nonPersistent' => [
                'default' => 'not persistent',
                'persistent' => false
            ],
            'created_at',
            'saved_at',
            'deleted_at'
        ];
        $this->model->modifyProperties($properties);

        $model = $this->model->create();

        $this->assertEquals('Test Name', $model->name);
        $this->assertEquals(30, $model->age);
        $this->assertEquals('default@email.address', $model->email);
        $this->assertEquals('not persistent', $model->nonPersistent);
        $this->assertNotNull($model->created_at);
        $this->assertNull($model->saved_at);
        $this->assertNull($model->deleted_at);

        // Test that email cannot be changed.
        try {
            $model->email = 'new@email.address';
            $this->assertFalse(true, 'Unreachable');
        } catch (ModelException $e) {
            $this->assertEquals(ModelException::class, get_class($e));
        }

        // Test update.
        $model->setData(['name' => 'new name', 'age' => 50]);
        $this->assertEquals('new name', $model->name);
        $this->assertEquals(50, $model->age);

        // Test non persistent data after save and load.
        $model->nonPersistent = 'new value';
        $model->save();
        $model = $model->load($model->id());
        $this->assertEquals('not persistent', $model->nonPersistent);
        // Check that saved_at is not null after save.
        $this->assertNotNull($model->saved_at);

        // Test delete and loading of archived data.
        $model->delete();
        try {
            $model = $model->load($model->id(), includeArchived: true);
        } catch (ModelException $e) {
            $this->assertFalse(true, 'Unreachable');
        }

        // Ensure that deleted_at is not null.
        $this->assertNotNull($model->deleted_at);

        // Test restore
        $model->restore();
        // Ensure that deleted_at is null.
        $this->assertNull($model->deleted_at);

        // Remove timestamp properties and check that these properties are not present.
        $model->getDatabase()->query('ALTER TABLE models_testmodel DROP COLUMN created_at');
        $model->getDatabase()->query('ALTER TABLE models_testmodel DROP COLUMN saved_at');
        $model->getDatabase()->query('ALTER TABLE models_testmodel DROP COLUMN deleted_at');
        $model->removeProperties(['created_at', 'saved_at', 'deleted_at']);
        $properties = $model->getProperties();
        $this->assertArrayNotHasKey('created_at', $properties);
        $this->assertArrayNotHasKey('saved_at', $properties);
        $this->assertArrayNotHasKey('deleted_at', $properties);

        // Attempt to save the model.
        try {
            $model->save();
        } catch (ModelException $e) {
            $this->assertFalse(true, 'Unreachable');
        }

        // Delete the model.
        $model->delete();

        // Ensure it is not longer exists in database.
        try {
            $model->load($model->id(), includeArchived: true);
            $this->assertFalse(true, 'Unreachable');
        } catch (ModelException $e) {
            $this->assertEquals(ModelException::class, get_class($e));
        }
    }
}
