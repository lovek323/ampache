<?php

namespace Lib\Metadata;

use Lib\Metadata\Model\Metadata as MetadataModel;
use Lib\Metadata\Model\Metadata;
use Lib\Metadata\Model\MetadataField;
use Lib\Metadata\Repository\MetadataFieldRepository;
use Lib\Metadata\Repository\MetadataRepository;

trait MetadataTrait
{
    /**
     * @var Repository\MetadataRepository
     */
    protected $metadataRepository;

    /**
     * @var Repository\MetadataFieldRepository
     */
    protected $metadataFieldRepository;

    /**
     * Determines if the functionality is enabled or not.
     * @var boolean
     */
    protected $enableCustomMetadata;

    /**
     * Cache variable for disabled metadata field names
     * @var array
     */
    protected $disabledMetadataFields = [];

    /**
     * Initialize the repository variables. Needs to be called first if the trait should do something.
     */
    protected function initializeMetadata()
    {
        $this->metadataRepository = new MetadataRepository();
        $this->metadataFieldRepository = new MetadataFieldRepository();
    }


    /**
     * @return Model\Metadata[]
     */
    public function getMetadata()
    {
        /** @noinspection PhpUndefinedFieldInspection (@todo fix $this->id) */
        return $this->metadataRepository->findByObjectIdAndType($this->id, get_class($this));
    }

    /**
     *
     * @param Model\Metadata $metadata
     */
    public function deleteMetadata(Model\Metadata $metadata)
    {
        $this->metadataRepository->remove($metadata);
    }

    /**
     *
     * @param MetadataField $field
     * @param MetadataModel $data
     */
    public function addMetadata(MetadataField $field, $data)
    {
        $metadata = new Metadata();
        $metadata->setField($field);
        /** @noinspection PhpUndefinedFieldInspection (@todo fix $this->id) */
        $metadata->setObjectId($this->id);
        $metadata->setType(get_class($this));
        $metadata->setData($data);
        $this->metadataRepository->add($metadata);
    }

    public function updateOrInsertMetadata(MetadataField $field, $data)
    {
        /* @var array $metadata */
        /** @noinspection PhpUndefinedFieldInspection (@todo: fix $id in a better way) */
        $metadata = $this->metadataRepository->findByObjectIdAndFieldAndType($this->id, $field, get_class($this));
        if ($metadata) {
            $object = reset($metadata);
            $object->setData($data);
            $this->metadataRepository->update($object);
        } else {
            $this->addMetadata($field, $data);
        }
    }

    /**
     *
     * @param $name
     * @param $public
     * @return \Lib\Metadata\Model\MetadataField
     */
    protected function createField($name, $public)
    {
        $field = new MetadataField();
        $field->setName($name);
        if (!$public) {
            $field->hide();
        }
        $this->metadataFieldRepository->add($field);
        return $field;
    }

    /**
     *
     * @param string $propertie
     * @param boolean $public
     * @return Model\MetadataField
     */
    public function getField($propertie, $public = true)
    {
        $fields = $this->metadataFieldRepository->findByName($propertie);
        if (count($fields)) {
            $field = reset($fields);
        } else {
            $field = $this->createField($propertie, $public);
        }
        return $field;
    }

    /**
     *
     * @return boolean
     */
    public static function isCustomMetadataEnabled()
    {
        return (boolean)\AmpConfig::get('enable_custom_metadata');
    }

    /**
     * Get all disabled Metadata field names
     * @return array
     */
    public function getDisabledMetadataFields()
    {
        if (!$this->disabledMetadataFields) {
            $fields = [];
            $ids = explode(',', \AmpConfig::get('disabled_custom_metadata_fields'));
            foreach ($ids as $id) {
                $field = $this->metadataFieldRepository->findById($id);
                if ($field) {
                    $fields[] = $field->getName();
                }
            }
            $this->disabledMetadataFields = array_merge(
                $fields, explode(',', \AmpConfig::get('disabled_custom_metadata_fields_input'))
            );
        }
        return $this->disabledMetadataFields;
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
