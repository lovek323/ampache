<?php

namespace Lib\Metadata\Repository;

use Dba;
use Lib\Repository;
use Lib\Metadata\Model\MetadataField;

/**
 * @method MetadataField findById($id)
 * @method MetadataField[] findByName($name)
 */
class MetadataFieldRepository extends Repository
{
    protected $modelClassName = MetadataField::class;

    public static function gc()
    {
        Dba::write(
            'DELETE FROM `metadata_field` USING `metadata_field` LEFT JOIN `metadata` ON `metadata`.`field` = `metadata_field`.`id` WHERE `metadata`.`id` IS NULL'
        );
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
