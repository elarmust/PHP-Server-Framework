<?php

/**
 * Entity type class.
 *
 * This class handles the creation, management, and deletion of entity types and their attributes.
 *
 * @copyright WW Byte OÜ.
 */

namespace Framework\Entity;

use Framework\Container\ClassContainer;
use Framework\Database\DataTypes\DataTypeInterface;
use Framework\Database\Database;
use Exception;
use InvalidArgumentException;
use RuntimeException;

class EntityType implements EntityTypeInterface {
    protected int $typeId;
    protected bool $eav;
    protected array $attributes;
    protected array $attributesMeta;

    /**
     * @param ClassContainer $classContainer
     * @param Database $database
     * @param string $typeName The name of the entity type.
     */
    function __construct(
        private ClassContainer $classContainer,
        protected Database $database,
        protected string $type
    ) {}

    /**
     * Load entity type from the database.
     *
     * @throws RuntimeException
     * @return void
     */
    public function loadType(): void {
        $entityTypeData = $this->database->select('entity_types', null, ['entity_type' => $this->getType()]);
        if (!$entityTypeData) {
            throw new RuntimeException($this->getType());
        }

        $this->typeId = $entityTypeData[0]['id'];
        $this->eav = $entityTypeData[0]['eav'] ?? false;
        $entityAttributes = $this->database->select('entity_' . $this->getType() . '_attributes');
        foreach ($entityAttributes ?? [] as $attribute) {
            $this->attributesMeta[$attribute['attribute_name']]['attributeId'] = $attribute['id'];
            if ($attribute['get_class'] && !class_exists($attribute['get_class'])) {
                throw new RuntimeException("Class '" . $attribute['get_class'] . "' does not exist!");
            }

            if ($attribute['get_class']) {
                $class = $this->classContainer->get($attribute['get_class'], [$this], singleton: false);
                $this->attributesMeta[$attribute['attribute_name']]['getClass'] = $class;
            } else {
                $this->attributesMeta[$attribute['attribute_name']]['getClass'] = null;
            }

            if ($attribute['set_class'] && !class_exists($attribute['set_class'])) {
                throw new RuntimeException("Class '" . $attribute['set_class'] . "' does not exist!");
            }

            if ($attribute['set_class']) {
                $class = $this->classContainer->get($attribute['set_class'], [$this], singleton: false);
                $this->attributesMeta[$attribute['attribute_name']]['setClass'] = $class;
            } else {
                $this->attributesMeta[$attribute['attribute_name']]['setClass'] = null;
            }

            if ($attribute['input_list_class'] && !class_exists($attribute['input_list_class'])) {
                throw new RuntimeException("Class '" . $attribute['input_list_class'] . "' does not exist!");
            }

            if ($attribute['input_list_class']) {
                $class = $this->classContainer->get($attribute['input_list_class'], [$this], singleton: false);
                $this->attributesMeta[$attribute['attribute_name']]['inputListClass'] = $class;
            } else {
                $this->attributesMeta[$attribute['attribute_name']]['inputListClass'] = null;
            }

            $this->attributesMeta[$attribute['attribute_name']]['default'] = $attribute['default'];
            $this->attributes[$attribute['attribute_name']] = null;
        }
    }

    /**
     * Create a new entity type.
     *
     * @throws RuntimeException If the entity type already exists.
     * @return void
     */
    public function createType(): void {
        $existing = $this->database->select('entity_types', null, ['entity_type' => $this->getType()]);
        if ($existing) {
            throw new RuntimeException($this->getType());
        }

        $this->database->insert('entity_types', ['entity_type' => $this->getType()]);
        $this->typeId = $this->database->query('SELECT MAX(id) FROM entity_types WHERE entity_type = ?', [$this->getType()])[0]['MAX(id)'];

        $this->database->query('
            CREATE TABLE IF NOT EXISTS `entities_' . $this->getType() . '` (
                `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `entity_type` INT NOT NULL,
                    CONSTRAINT entity_type_' . $this->getType() . '
                    FOREIGN KEY (entity_type)
                    REFERENCES entity_types(id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
            )
        ');

        $this->database->query('
            CREATE TABLE IF NOT EXISTS `entity_' . $this->getType() . '_attributes` (
                `id` INT NOT NULL PRIMARY KEY AUTO_INCREMENT,
                `attribute_name` VARCHAR(32) NOT NULL,
                `entity_type` INT NOT NULL,
                `get_class` VARCHAR(256) DEFAULT NULL,
                `set_class` VARCHAR(256) DEFAULT NULL,
                `input_list_class` VARCHAR(256) DEFAULT NULL,
                `default` TEXT DEFAULT NULL,
                 CONSTRAINT entity_' . $this->getType() . '_attributes
                    FOREIGN KEY (entity_type)
                    REFERENCES entity_types(id)
                        ON DELETE CASCADE
                        ON UPDATE CASCADE
            )
        ');
    }

    /**
     * Delete the entity type and its attributes from the database.
     *
     * @return void
     */
    public function deleteType(): void {
        $this->database->delete('entity_types', ['entity_type' => $this->getType()]);
        $this->database->query('DROP TABLE entity_' . $this->getType() . '_attributes');
        $this->database->query('DROP TABLE entities_' . $this->getType());
    }

    /**
     * Add an attribute to the entity type.
     *
     * @param string $attributeName The name of the attribute to add.
     * @param DataTypeInterface $dataType The data type of the attribute.
     * @param string|null $getClass The class used for preprocessing the retrieved value.
     * @param string|null $setClass The class used for postprocessing the value before saving.
     * @param string|null $inputListClass The class used for retrieving a list of accepted values.
     * 
     * @throws InvalidArgumentException
     * @throws Exception If the provided class for getting, setting, or input list does not exist.
     * @return void
     */
    public function addAttribute(string $attributeName, DataTypeInterface $dataType, string $getClass = null, string $setClass = null, string $inputListClass = null): void {
        if (isset($this->attributes[$attributeName])) {
            return;
        }

        if ($getClass && !class_exists($getClass)) {
            throw new InvalidArgumentException("Class '" . $getClass . "' does not exist!");
        }

        if ($setClass && !class_exists($setClass)) {
            throw new InvalidArgumentException("Class '" . $setClass . "' does not exist!");
        }

        if ($inputListClass && !class_exists($inputListClass)) {
            throw new InvalidArgumentException("Class '" . $inputListClass . "' does not exist!");
        }

        $existing = $this->database->select('entity_' . $this->getType() . '_attributes', null, ['attribute_name' => $attributeName]);
        if ($existing) {
            throw new InvalidArgumentException($attributeName);

        }

        $dataTypeString = $dataType->dataType();
        if ($dataType->dataLength()) {
            $dataTypeString .= '(' . $dataType->dataLength() . ')';
        }

        $default = trim(var_export($dataType->defaultValue(), true), "'");
        if ($default == 'NULL') {
            $default = 'DEFAULT NULL';
        } else if ($dataType->notNull()) {
            $default = 'NOT NULL DEFAULT ' . $default;
        } else {
            $default = 'DEFAULT ' . $default;
        }

        $this->database->insert('entity_' . $this->getType() . '_attributes', [
            'attribute_name' => $attributeName,
            'entity_type' => $this->getTypeId(),
            'get_class' => $getClass,
            'set_class' => $setClass,
            'input_list_class' => $inputListClass,
            'default' => $default
        ]);

        $this->database->query('ALTER TABLE entities_' . $this->getType() . ' ADD ' . $attributeName . ' ' . $dataTypeString . ' ' . $default);
    }

    /**
     * Delete an attribute from the entity type.
     *
     * @param string $attributeName The name of the attribute to delete.
     * 
     * @return void
     */
    public function deleteAttribute(string $attributeName): void {
        $this->database->delete('entity_' . $this->getType() . '_attributes', ['attribute_name' => $attributeName]);
        $this->database->query('ALTER TABLE entities_' . $this->getType() . ' DROP ' . $attributeName);
    }

    /**
     * Get the name of the entity type.
     *
     * @return string|null The name of the entity type.
     */
    public function getType(): ?string {
        return $this->type;
    }

    /**
     * Get the ID of the entity type.
     *
     * @return int|null The ID of the entity type.
     */
    public function getTypeId(): ?int {
        return $this->typeId;
    }

    /**
     * Get the attributes associated with the entity type.
     *
     * @return array The attributes as an associative array.
     */
    public function getAttributes(): array {
        return $this->attributes;
    }

    /**
     * Get metadata for the entity type attributes.
     *
     * @return array The attribute metadata as an associative array.
     */
    public function getAttributesMeta(): array {
        return $this->attributesMeta;
    }
}
