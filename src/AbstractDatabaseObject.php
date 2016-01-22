<?php

namespace Ampache;

use AmpConfig;
use Dba;

/**
 * This is a general object that is extended by all of the basic
 * database based objects in ampache. It attempts to do some standard
 * caching for all of the objects to cut down on the database calls
 */
abstract class AbstractDatabaseObject
{
    private static $object_cache = [];

    // Statistics for debugging
    public static $cache_hit = 0;

    private static $enabled = false;

    /**
     * Retrieves the info from the database and puts it in the cache.
     *
     * @param $id
     * @param string $tableName
     * @return array|bool
     */
    public function getInfo($id, $tableName = '')
    {
        $tableName = $tableName ? Dba::escape($tableName) : Dba::escape(strtolower(get_class($this)));

        // Make sure we've got a real id
        if (!is_numeric($id)) {
            return [];
        }

        if (self::isCached($tableName, $id)) {
            return self::getFromCache($tableName, $id);
        }

        $sql = "SELECT * FROM `$tableName` WHERE `id`='$id'";
        $db_results = Dba::read($sql);

        if (!$db_results) {
            return [];
        }

        $row = Dba::fetchAssoc($db_results);

        self::addToCache($tableName, $id, $row);

        return $row;
    } // get_info

    /**
     * clear_cache
     */
    public static function clearCache()
    {
        self::$object_cache = [];
    }

    /**
     * is_cached
     * this checks the cache to see if the specified object is there
     * @param $index
     * @param $id
     * @return bool
     */
    public static function isCached($index, $id)
    {
        // Make sure we've got some parents here before we dive below
        if (!isset(self::$object_cache[$index])) {
            return false;
        }

        return isset(self::$object_cache[$index][$id]);
    } // is_cached

    /**
     * get_from_cache
     * This attempts to retrieve the specified object from the cache we've got here
     * @param $index
     * @param $id
     * @return bool
     */
    public static function getFromCache($index, $id)
    {
        // Check if the object is set
        if (isset(self::$object_cache[$index]) && isset(self::$object_cache[$index][$id])) {
            self::$cache_hit++;
            return self::$object_cache[$index][$id];
        }

        return false;
    } // get_from_cache

    /**
     * This adds the specified object to the specified index in the cache
     * @param $index
     * @param $id
     * @param $data
     * @return bool|null
     */
    public static function addToCache($index, $id, $data)
    {
        if (!self::$enabled) {
            return false;
        }
        $value = is_null($data) ? false : $data;
        self::$object_cache[$index][$id] = $value;
        return null;
    }

    /**
     * This function clears something from the cache, there are a few places we need to do this
     * in order to have things display correctly
     * @param $index
     * @param $id
     */
    public static function removeFromCache($index, $id)
    {
        if (isset(self::$object_cache[$index]) && isset(self::$object_cache[$index][$id])) {
            unset(self::$object_cache[$index][$id]);
        }
    }

    /**
     * Load in the cache settings once so we can avoid function calls
     */
    public static function autoInit()
    {
        self::$enabled = AmpConfig::get('memory_cache');
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
