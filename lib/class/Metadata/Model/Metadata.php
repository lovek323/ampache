<?php

namespace Lib\Metadata\Model;

use Lib\AbstractDatabaseObject;
use Lib\Interfaces\Model;

/**
 * Description of metadata
 *
 * @author raziel
 */
class Metadata extends AbstractDatabaseObject implements Model
{
    /**
     * Database ID
     * @var integer
     */
    protected $id;

    /**
     * A library item like song or video
     * @var \LibraryItemInterface
     */
    protected $objectId;

    /**
     * Tag Field
     * @var MetadataField
     */
    protected $field;

    /**
     * Tag Data
     * @var string
     */
    protected $data;

    /**
     *
     * @var string
     */
    protected $type;

    /**
     *
     * @var array Stores relation between SQL field name and repository class name so we
     * can initialize objects the right way
     */
    protected $fieldClassRelations = ['field' => MetadataField::class];

    /**
     *
     * @return \LibraryItemInterface
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     *
     * @return MetadataField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     *
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     *
     * @param integer $object
     */
    public function setObjectId($object)
    {
        $this->objectId = $object;
    }

    /**
     *
     * @param MetadataField $field
     */
    public function setField(MetadataField $field)
    {
        $this->field = $field;
    }

    /**
     *
     * @param string $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

    /**
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     *
     * @param string $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
