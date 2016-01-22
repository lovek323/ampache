<?php

namespace Lib;

abstract class AbstractDatabaseObject
{
    protected $id;

    /**
     * @var array Stores relation between SQL field name and class name so we
     * can initialize objects the right way
     */
    protected $fieldClassRelations = [];

    /** @var object */
    private $originalData;

    /**
     * AbstractDatabaseObject constructor.
     */
    public function __construct()
    {
        $this->remapCamelcase();
        $this->initializeChildObjects();
    }

    public function getId()
    {
        return $this->id;
    }

    /**
     * @param $property
     * @return bool
     */
    protected function isPropertyDirty($property)
    {
        return $this->originalData->$property !== $this->$property;
    }

    public function isDirty()
    {
        return true;
    }

    /**
     * Get all changed properties
     * TODO: we get all properties for now...need more logic here...
     * @return array
     */
    public function getDirtyProperties()
    {
        $properties = get_object_vars($this);
        unset($properties['id']);
        unset($properties['fieldClassRelations']);
        return $this->fromCamelCase($properties);
    }

    /**
     * Convert the object properties to camelCase.
     * This works in constructor because the properties are here from
     * fetch_object before the constructor get called.
     */
    protected function remapCamelcase()
    {
        foreach (get_object_vars($this) as $key => $val) {
            if (strpos($key, '_') !== false) {
                $camelCaseKey        = lcfirst(str_replace(' ', '', ucwords(str_replace('_', ' ', $key))));
                $this->$camelCaseKey = $val;
                unset($this->$key);
            }
        }
    }
    
    protected function fromCamelCase($properties)
    {
        $data = [];
        foreach ($properties as $propertie => $value) {
            $newPropertyKey        = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $propertie));
            $data[$newPropertyKey] = $value;
        }
        return $data;
    }

    /**
     * Adds child Objects based of the Model Information
     * TODO: Someday we might need lazy loading, but for now it should be ok.
     */
    public function initializeChildObjects()
    {
        foreach ($this->fieldClassRelations as $field => $repositoryName) {
            if (class_exists($repositoryName)) {
                /* @var $repository Repository */
                $repository   = new $repositoryName;
                $this->$field = $repository->findById($this->$field);
            }
        }
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
