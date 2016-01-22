<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */

namespace Lib;

use Dba;
use Lib\Interfaces\Model;

/**
 * @method array findByObjectIdAndType($id, $type)
 */
class Repository
{
    protected $modelClassName;
    
    /**
     * @var array Stores relation between SQL field name and class name so we
     * can initialize objects the right way
     */
    protected $fieldClassRelations = [];

    protected function findBy($fields, $values)
    {
        $table = $this->getTableName();
        return $this->getRecords($table, $fields, $values);
    }

    /**
     * @return AbstractDatabaseObject[]
     */
    public function findAll()
    {
        $table = $this->getTableName();
        return $this->getRecords($table);
    }

    /**
     *
     * @param $id
     * @return AbstractDatabaseObject
     */
    public function findById($id)
    {
        $rows = $this->findBy(['id'], [$id]);
        return count($rows) ? reset($rows) : null;
    }

    private function getRecords($table, $field = null, $value = null)
    {
        $data = [];
        $sql  = $this->assembleQuery($table, $field);

        $statement = Dba::read($sql, is_array($value) ? $value : [$value]);
        while ($object = Dba::fetch_object($statement, $this->modelClassName)) {
            $data[$object->getId()] = $object;
        }
        return $data;
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return AbstractDatabaseObject|null
     */
    public function __call($name, $arguments)
    {
        if (preg_match('/^findBy(.*)$/', $name, $matches)) {
            $parts = explode('AND', $matches[1]);
            return $this->findBy(
                    $parts,
                    $this->resolveObjects($arguments)
            );
        }
        return null;
    }

    private function getTableName()
    {
        $className = get_called_class();
        $nameParts = explode('\\', $className);
        $tableName = preg_replace_callback(
                '/(?<=.)([A-Z])/',
                function($m) {
                    return '_' . strtolower($m[0]);
                }, end($nameParts));
        return lcfirst($tableName);
    }

    public function add(AbstractDatabaseObject $object)
    {
        $properties = $object->getDirtyProperties();
        $this->setPrivateProperty(
                $object,
                'id',
                $this->insertRecord($properties)
        );
    }

    public function update(AbstractDatabaseObject $object)
    {
        if ($object->isDirty()) {
            $properties = $object->getDirtyProperties();
            $this->updateRecord($object->getId(), $properties);
        }
    }

    public function remove(AbstractDatabaseObject $object)
    {
        $id = $object->getId();
        $this->deleteRecord($id);
    }

    protected function insertRecord($properties)
    {
        $sql = 'INSERT INTO ' . $this->getTableName() . ' (' . implode(',', array_keys($properties)) . ')'
                . ' VALUES(' . implode(',', array_fill(0, count($properties), '?')) . ')';
        Dba::write($sql, array_values($this->resolveObjects($properties)));
        return Dba::insert_id();
    }

    protected function updateRecord($id, $properties)
    {
        $sql = 'UPDATE ' . $this->getTableName()
                . ' SET ' . implode(',', $this->getKeyValuePairs($properties))
                . ' WHERE id = ?';
        $properties[] = $id;
        Dba::write(
                $sql,
                array_values($this->resolveObjects($properties))
        );
    }

    protected function deleteRecord($id)
    {
        $sql = 'DELETE FROM ' . $this->getTableName()
                . ' WHERE id = ?';
        Dba::write($sql, [$id]);
    }

    protected function getKeyValuePairs($properties)
    {
        $pairs = [];
        foreach ($properties as $property => $value) {
            $pairs[] = $property . '= ?';
        }
        return $pairs;
    }

    /**
     * Set a private or protected variable.
     * Only used in case where a property should not publicly writable
     * @param AbstractDatabaseObject $object
     * @param string $property
     * @param mixed $value
     */
    protected function setPrivateProperty(AbstractDatabaseObject $object, $property, $value)
    {
        $reflectionClass    = new \ReflectionClass(get_class($object));
        $ReflectionProperty = $reflectionClass->getProperty($property);
        $ReflectionProperty->setAccessible(true);
        $ReflectionProperty->setValue($object, $value);
    }

    /**
     * Resolve all objects into id's
     * @param Model[] $properties
     * @return array
     */
    protected function resolveObjects(array $properties)
    {
        foreach ($properties as $property => $value) {
            if (is_object($value)) {
                $properties[$property] = $value->getId();
            }
        }
        return $properties;
    }

    /**
     * Create query for one or multiple fields
     * @param string $table
     * @param array $fields
     * @return string
     */
    public function assembleQuery($table, $fields)
    {
        $sql = 'SELECT * FROM ' . $table;
        if ($fields) {
            $sql .= ' WHERE ';
            $sqlParts = [];
            foreach ($fields as $field) {
                $sqlParts[] = '`' . $this->camelCaseToUnderscore($field) . '` = ?';
            }
            $sql .= implode(' and ', $sqlParts);
        }
        
        return $sql;
    }

    public function camelCaseToUnderscore($string)
    {
        return strtolower(preg_replace('/(?<=\\w)(?=[A-Z])/','_$1', $string));
    }
}
