<?php

/**
 * Entity type class.
 * Creates and manages entity type data.
 *
 * Copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use Exception;
use Framework\Core\ClassContainer;
use Framework\Database\Database;
use Framework\Database\DataTypes\DataTypeInterface;
use Framework\Entity\Exceptions\EntityAttributeAlreadyExistsException;
use Framework\Entity\Exceptions\EntityTypeAlreadyExistsException;
use Framework\Entity\Exceptions\EntityTypeNotFoundException;

class EntityType implements EntityTypeInterface{
    private ClassContainer $classContainer;
    protected Database $database;
    protected string $type;
    protected int $typeId;
    protected bool $eav;
    protected array $attributes;
    protected array $attributesMeta;

    function __construct(ClassContainer $classContainer, Database $database, string $typeName) {
        $this->classContainer = $classContainer;
        $this->database = $database;
        $this->type = $typeName;
    }

    public function loadType(): void {
        $entityTypeData = $this->database->select('entity_types', null, ['entity_type' => $this->getType()]);
        if (!$entityTypeData) {
            throw new EntityTypeNotFoundException($this->getType());
        }

        $this->typeId = $entityTypeData[0]['id'];
        $this->eav = $entityTypeData[0]['eav'] ?? false;
        $entityAttributes = $this->database->select('entity_' . $this->getType() . '_attributes');
        foreach ($entityAttributes ?? [] as $attribute) {
            $this->attributesMeta[$attribute['attribute_name']]['attributeId'] = $attribute['id'];
            if ($attribute['get_class'] && !class_exists($attribute['get_class'])) {
                throw new Exception("Class '" . $attribute['get_class'] . "' does not exist!");
            }

            if ($attribute['get_class']) {
                $class = $this->classContainer->get($attribute['get_class'], [$this], cache: false);
                $this->attributesMeta[$attribute['attribute_name']]['getClass'] = $class;
            } else {
                $this->attributesMeta[$attribute['attribute_name']]['getClass'] = null;
            }

            if ($attribute['set_class'] && !class_exists($attribute['set_class'])) {
                throw new Exception("Class '" . $attribute['set_class'] . "' does not exist!");
            }

            if ($attribute['set_class']) {
                $class = $this->classContainer->get($attribute['set_class'], [$this], cache: false);
                $this->attributesMeta[$attribute['attribute_name']]['setClass'] = $class;
            } else {
                $this->attributesMeta[$attribute['attribute_name']]['setClass'] = null;
            }

            if ($attribute['input_list_class'] && !class_exists($attribute['input_list_class'])) {
                throw new Exception("Class '" . $attribute['input_list_class'] . "' does not exist!");
            }

            if ($attribute['input_list_class']) {
                $class = $this->classContainer->get($attribute['input_list_class'], [$this], cache: false);
                $this->attributesMeta[$attribute['attribute_name']]['inputListClass'] = $class;
            } else {
                $this->attributesMeta[$attribute['attribute_name']]['inputListClass'] = null;
            }

            $this->attributesMeta[$attribute['attribute_name']]['default'] = $attribute['default'];
            $this->attributes[$attribute['attribute_name']] = null;
        }
    }

    public function createType(): void {
        $existing = $this->database->select('entity_types', null, ['entity_type' => $this->getType()]);
        if ($existing) {
            throw new EntityTypeAlreadyExistsException($this->getType());
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

    public function deleteType(): void {
        $this->database->delete('entity_types', ['entity_type' => $this->getType()]);
        $this->database->query('DROP TABLE entity_' . $this->getType() . '_attributes');
        $this->database->query('DROP TABLE entities_' . $this->getType());
    }

    public function addAttribute(string $attributeName, DataTypeInterface $dataType, string $getClass = null, string $setClass = null, string $inputListClass = null): void {
        if (isset($this->attributes[$attributeName])) {
            return;
        }

        if ($getClass && !class_exists($getClass)) {
            throw new Exception("Class '" . $getClass . "' does not exist!");
        }

        if ($setClass && !class_exists($setClass)) {
            throw new Exception("Class '" . $setClass . "' does not exist!");
        }

        if ($inputListClass && !class_exists($inputListClass)) {
            throw new Exception("Class '" . $inputListClass . "' does not exist!");
        }

        $existing = $this->database->select('entity_' . $this->getType() . '_attributes', null, ['attribute_name' => $attributeName]);
        if ($existing) {
            throw new EntityAttributeAlreadyExistsException($attributeName);

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

    public function deleteAttribute(string $attributeName): void {
        $this->database->delete('entity_' . $this->getType() . '_attributes', ['attribute_name' => $attributeName]);
        $this->database->query('ALTER TABLE entities_' . $this->getType() . ' DROP ' . $attributeName);
    }

    public function getType(): ?string {
        return $this->type;
    }

    public function getTypeId(): ?string {
        return $this->typeId;
    }

    public function getAttributes(): array {
        return $this->attributes;
    }

    public function getAttributesMeta(): array {
        return $this->attributesMeta;
    }
}