<?php

/**
 * 
 * copyright @ WereWolf Labs OÜ.
 */

namespace Framework\Entity\Model;

use Framework\Core\ClassManager;
use Framework\Entity\Exceptions\EntityNotFoundException;
use Framework\Entity\Exceptions\EntityAttributeNotFoundException;

class Entity extends EntityType implements EntityInterface {
    private ClassManager $classManager;
    public array $attributes = [];
    private ?int $entityId = null;

    function __construct(ClassManager $classManager, string $entityType) {
        $this->classManager = $classManager;
        parent::__construct(...$this->classManager->prepareArguments(EntityType::class, [$entityType]));
        $this->loadType();
    }

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

    public function save(): void {
        $data = $this->getAttributes();
        if (!$this->getId()) {
            $this->database->insert('entities_' . $this->getType(), ['entity_type' => $this->getTypeId()] + $data);
            $this->entityId = $this->database->query('SELECT MAX(id) FROM entities_' . $this->getType() . ' WHERE entity_type = ?', [$this->getTypeId()])[0]['MAX(id)'];
        } else {
            $this->database->update('entities_' . $this->getType(), $data, ['id' => $this->getId()]);
        }
    }

    public function delete(): void {
        $this->database->delete('entities_' . $this->getType(), ['id' => $this->getId()]);
    }

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

    public function getId(): ?int {
        return $this->entityId;
    }

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

    public function getFields(): array {
        return array_keys($this->attributes);
    }
}