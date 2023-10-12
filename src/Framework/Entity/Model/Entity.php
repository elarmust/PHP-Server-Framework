<?php

/**
 * Represents an entity object that interacts with the database.
 *
 * @copyright WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use Framework\Core\ClassContainer;
use Framework\Entity\Exceptions\EntityNotFoundException;
use Framework\Entity\Exceptions\EntityAttributeNotFoundException;

class Entity extends EntityType implements EntityInterface {
    public array $attributes = [];
    private ?int $entityId = null;

    /**
     * @param ClassContainer $classContainer
     * @param string $entityType
     */
    function __construct(private ClassContainer $classContainer, string $entityType) {
        parent::__construct(...$this->classContainer->prepareArguments(EntityType::class, [$entityType]));
        $this->loadType();
    }

    /**
     * Load the entity data from database.
     *
     * @param int $entityId The ID of the entity to load.
     * 
     * @throws EntityNotFoundException If the entity is not found.
     * @return void
     */
    public function load(int $entityId): void {
        $attributeDataQuery = $this->database->select('entities_' . $this->getType(), where: ['id' => $entityId]);
        if (!$attributeDataQuery) {
            Throw New EntityNotFoundException($entityId);
        }

        $this->entityId = $entityId;

        foreach ($attributeDataQuery[0] as $attribute => $value) {
            if (!isset($this->getAttributesMeta()[$attribute])) {
                continue;
            }

            $this->attributes[$attribute] = $value;
        }
    }

    /**
     * Save the entity data to the database.
     * 
     * @return void
     */
    public function save(): void {
        $data = $this->getAttributes();
        if (!$this->getId()) {
            $this->database->insert('entities_' . $this->getType(), ['entity_type' => $this->getTypeId()] + $data);
            $this->entityId = $this->database->query('SELECT MAX(id) FROM entities_' . $this->getType() . ' WHERE entity_type = ?', [$this->getTypeId()])[0]['MAX(id)'];
        } else {
            $this->database->update('entities_' . $this->getType(), $data, ['id' => $this->getId()]);
        }
    }

    /**
     * Delete the entity from the database.
     * 
     * @return void
     */
    public function delete(): void {
        $this->database->delete('entities_' . $this->getType(), ['id' => $this->getId()]);
    }

    /**
     * Set data for entity attributes.
     *
     * @param array $attributesValue An associative array of attribute names and their values.
     * 
     * @throws EntityAttributeNotFoundException If an attribute specified in $attributesValue is not found.
     * @return void
     */
    public function setData(array $attributesValue): void {
        foreach ($attributesValue as $attribute => $value) {
            if (array_key_exists($attribute, $this->attributes)) {
                if ($this->attributesMeta[$attribute]['setClass']) {
                    $method = 'set' . $attribute;
                    $value = $this->attributesMeta[$attribute]['setClass']->$method($value);
                }

                $this->attributes[$attribute] = $value;
            } else {
                throw new EntityAttributeNotFoundException($attribute);
            }
        }
    }

    /**
     * Get the ID of the entity.
     *
     * @return int|null The ID of the entity, or null if it hasn't been assigned an ID yet.
     */
    public function getId(): ?int {
        return $this->entityId;
    }

    /**
     * Get data for specific entity fields.
     *
     * @param array $fields An array of field names to retrieve data for.
     * 
     * @throws EntityAttributeNotFoundException If a specified field is not found.
     * @return array
     */
    public function getData(array $fields = []): array{
        if (!$fields) {
            $fields = $this->getFields();
        }

        $return = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $this->attributes)) {
                if ($this->attributesMeta[$field]['getClass']) {
                    $method = 'get' . $field;
                    $return[$field] = $this->attributesMeta[$field]['getClass']->$method();
                } else {
                    $return[$field] = $this->attributes[$field];
                }
            } else {
                throw new EntityAttributeNotFoundException($field);
            }
        }

        return $return;
    }

    /**
     * Get an array of all available entity fields.
     *
     * @return array An array of entity field names.
     */
    public function getFields(): array {
        return array_keys($this->attributes);
    }
}
