<?php

namespace Lib\Metadata\Repository;

use Dba;
use Lib\Repository;
use Lib\Metadata\Model\Metadata as MetadataModel;

/**
 * @method MetadataModel findById($id)
 * @method array findByObjectIdAndType($id, $type)
 * @method array findByObjectIdAndFieldAndType($id, $field, $type)
 */
class MetadataRepository extends Repository
{
    protected $modelClassName = MetadataModel::class;

    public static function gc()
    {
        $sql = <<<EOSQL
            DELETE FROM metadata USING metadata
                LEFT JOIN song ON song.id=metadata.object_id WHERE song.id IS NULL
EOSQL;

        Dba::write($sql);
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
