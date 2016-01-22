<?php

namespace Ampache;

use Access;
use AmpConfig;
use Core;
use Dba;

/**
 * This handles all of the preference stuff for Ampache
 */
class Preference extends AbstractDatabaseObject
{
    /**
     * __constructor
     * This does nothing... amazing isn't it!
     */
    private function __construct()
    {
        // Rien a faire
    } // __construct

    /**
     * get_by_user
     * Return a preference for specific user identifier
     * @param $user_id
     * @param $pref_name
     * @return bool
     */
    public static function getByUser($user_id, $pref_name)
    {
        $user_id = Dba::escape($user_id);
        $pref_name = Dba::escape($pref_name);
        $id = self::idFromName($pref_name);

        if (parent::isCached('get_by_user', $user_id)) {
            return parent::getFromCache('get_by_user', $user_id);
        }

        $sql = "SELECT `value` FROM `user_preference` WHERE `preference`='$id' AND `user`='$user_id'";
        $db_results = Dba::read($sql);
        if (Dba::num_rows($db_results) < 1) {
            $sql = "SELECT `value` FROM `user_preference` WHERE `preference`='$id' AND `user`='-1'";
            $db_results = Dba::read($sql);
        }
        $data = Dba::fetchAssoc($db_results);

        parent::addToCache('get_by_user', $user_id, $data['value']);

        return $data['value'];
    } // get_by_user


    /**
     * update
     * This updates a single preference from the given name or id
     * @param $preference
     * @param $user_id
     * @param $value
     * @param bool $applytoall
     * @param bool $applytodefault
     * @return bool
     */
    public static function update($preference, $user_id, $value, $applytoall = false, $applytodefault = false)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $id = self::idFromName($preference);
            $name = $preference;
        } else {
            $name = self::nameFromId($preference);
            $id = $preference;
        }
        if ($applytoall and Access::check('interface', '100')) {
            $user_check = "";
        } else {
            $user_check = " AND `user`='$user_id'";
        }

        if (is_array($value)) {
            $value = implode(',', $value);
        }

        if ($applytodefault and Access::check('interface', '100')) {
            $sql = "UPDATE `preference` SET `value`='$value' WHERE `id`='$id'";
            Dba::write($sql);
        }

        $value = Dba::escape($value);

        if (self::hasAccess($name)) {
            $user_id = Dba::escape($user_id);
            $sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$id'$user_check";
            Dba::write($sql);
            Preference::clearFromSession();

            parent::removeFromCache('get_by_user', $user_id);

            return true;
        } else {
            debug_event(
                'denied',
                $GLOBALS['user']
                    ? $GLOBALS['user']->username
                    : '???' . ' attempted to update ' . $name . ' but does not have sufficient permissions',
                3
            );
        }

        return false;
    } // update

    /**
     * update_level
     * This takes a preference ID and updates the level required to update it (performed by an admin)
     * @param $preference
     * @param $level
     * @return bool
     */
    public static function updateLevel($preference, $level)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $preference_id = self::idFromName($preference);
        } else {
            $preference_id = $preference;
        }

        $preference_id = Dba::escape($preference_id);
        $level = Dba::escape($level);

        $sql = "UPDATE `preference` SET `level`='$level' WHERE `id`='$preference_id'";
        Dba::write($sql);

        return true;
    } // update_level

    /**
     * update_all
     * This takes a preference id and a value and updates all users with the new info
     * @param $preference_id
     * @param $value
     * @return bool
     */
    public static function updateAll($preference_id, $value)
    {
        $preference_id = Dba::escape($preference_id);
        $value = Dba::escape($value);

        $sql = "UPDATE `user_preference` SET `value`='$value' WHERE `preference`='$preference_id'";
        Dba::write($sql);

        parent::clearCache();

        return true;
    } // update_all

    /**
     * exists
     * This just checks to see if a preference currently exists
     * @param $preference
     * @return int
     */
    public static function exists($preference)
    {
        // We assume it's the name
        $name = Dba::escape($preference);
        $sql = "SELECT * FROM `preference` WHERE `name`='$name'";
        $db_results = Dba::read($sql);

        return Dba::num_rows($db_results);
    } // exists

    /**
     * has_access
     * This checks to see if the current user has access to modify this preference
     * as defined by the preference name
     * @param $preference
     * @return bool
     */
    public static function hasAccess($preference)
    {
        // Nothing for those demo thugs
        if (AmpConfig::get('demo_mode')) {
            return false;
        }

        $preference = Dba::escape($preference);

        $sql = "SELECT `level` FROM `preference` WHERE `name`='$preference'";
        $db_results = Dba::read($sql);
        $data = Dba::fetchAssoc($db_results);

        if (Access::check('interface', $data['level'])) {
            return true;
        }

        return false;
    } // has_access

    /**
     * id_from_name
     * This takes a name and returns the id
     * @param $name
     * @return bool
     */
    public static function idFromName($name)
    {
        $name = Dba::escape($name);

        if (parent::isCached('id_from_name', $name)) {
            return parent::getFromCache('id_from_name', $name);
        }

        $sql = "SELECT `id` FROM `preference` WHERE `name`='$name'";
        $db_results = Dba::read($sql);
        $row = Dba::fetchAssoc($db_results);

        parent::addToCache('id_from_name', $name, $row['id']);

        return $row['id'];
    } // id_from_name

    /**
     * name_from_id
     * This returns the name from an id, it's the exact opposite
     * of the function above it, amazing!
     * @param $id
     * @return
     */
    public static function nameFromId($id)
    {
        $id = Dba::escape($id);

        $sql = "SELECT `name` FROM `preference` WHERE `id`='$id'";
        $db_results = Dba::read($sql);

        $row = Dba::fetchAssoc($db_results);

        return $row['name'];
    } // name_from_id

    /**
     * get_catagories
     * This returns an array of the names of the different possible sections
     * it ignores the 'internal' catagory
     */
    public static function getCategories()
    {
        $sql = "SELECT `preference`.`catagory` FROM `preference` GROUP BY `catagory` ORDER BY `catagory`";
        $db_results = Dba::read($sql);

        $results = [];

        while ($row = Dba::fetchAssoc($db_results)) {
            if ($row['catagory'] != 'internal') {
                $results[] = $row['catagory'];
            }
        } // end while

        return $results;
    } // get_catagories

    /**
     * get_all
     * This returns a nice flat array of all of the possible preferences for the specified user
     * @param $user_id
     * @return array
     */
    public static function getAll($user_id)
    {
        $user_id = Dba::escape($user_id);

        $user_limit = "";
        if ($user_id != '-1') {
            $user_limit = "AND `preference`.`catagory` != 'system'";
        }

        $sql = <<<EOSQL
            SELECT preference.name, preference.description, preference.subcatagory, user_preference.value
            FROM preference INNER JOIN user_preference ON user_preference.preference=preference.id
            WHERE user_preference.user='$user_id' AND preference.catagory != 'internal' $user_limit
            ORDER BY preference.subcatagory, preference.description
EOSQL;

        $db_results = Dba::read($sql);
        $results = [];

        while ($row = Dba::fetchAssoc($db_results)) {
            $results[] = [
                'name' => $row['name'],
                'level' => $row['level'],
                'description' => $row['description'],
                'value' => $row['value'],
                'subcategory' => $row['subcatagory'],
            ];
        }

        return $results;
    } // get_all

    /**
     * insert
     * This inserts a new preference into the preference table
     * it does NOT sync up the users, that should be done independently
     * @param $name
     * @param $description
     * @param $default
     * @param $level
     * @param $type
     * @param $catagory
     * @param null $subcatagory
     * @return bool
     */
    public static function insert($name, $description, $default, $level, $type, $catagory, $subcatagory = null)
    {
        if ($subcatagory !== null) {
            $subcatagory = strtolower($subcatagory);
        }
        $sql = "INSERT INTO `preference` (`name`,`description`,`value`,`level`,`type`,`catagory`,`subcatagory`) " .
            "VALUES (?, ?, ?, ?, ?, ?, ?)";
        $db_results = Dba::write(
            $sql,
            [$name, $description, $default, intval($level), $type, $catagory, $subcatagory]
        );

        if (!$db_results) {
            return false;
        }
        $id = Dba::insert_id();
        $params = [$id, $default];
        $sql = "INSERT INTO `user_preference` VALUES (-1,?,?)";
        $db_results = Dba::write($sql, $params);
        if (!$db_results) {
            return false;
        }
        if ($catagory !== "system") {
            $sql = "INSERT INTO `user_preference` SELECT `user`.`id`, ?, ? FROM `user`";
            $db_results = Dba::write($sql, $params);
            if (!$db_results) {
                return false;
            }
        }

        return true;
    } // insert

    /**
     * delete
     * This deletes the specified preference, a name or a ID can be passed
     * @param $preference
     */
    public static function delete($preference)
    {
        // First prepare
        if (!is_numeric($preference)) {
            $sql = "DELETE FROM `preference` WHERE `name` = ?";
        } else {
            $sql = "DELETE FROM `preference` WHERE `id` = ?";
        }

        Dba::write($sql, [$preference]);

        self::cleanPreferences();
    } // delete

    /**
     * rename
     * This renames a preference in the database
     * @param $old
     * @param $new
     */
    public static function rename($old, $new)
    {
        $sql = "UPDATE `preference` SET `name` = ? WHERE `name` = ?";
        Dba::write($sql, [$new, $old]);
    }

    /**
     * clean_preferences
     * This removes any garbage
     */
    public static function cleanPreferences()
    {
        // First remove garbage
        $sql = <<<EOSQL
            DELETE FROM user_preference USING user_preference
            LEFT JOIN preference ON preference.id=user_preference.preference
            WHERE preference.id IS NULL
EOSQL;
        Dba::write($sql);
    } // rebuild_preferences

    /**
     * fix_preferences
     * This takes the preferences, explodes what needs to
     * become an array and boolean everythings
     * @param $results
     * @return
     */
    public static function fixPreferences($results)
    {
        $arrays = [
            'auth_methods', 'getid3_tag_order', 'metadata_order',
            'metadata_order_video', 'art_order', 'registration_display_fields',
            'registration_mandatory_fields'
        ];

        foreach ($arrays as $item) {
            $results[$item] = trim($results[$item])
                ? explode(',', $results[$item])
                : [];
        }

        foreach ($results as $key => $data) {
            if (!is_array($data)) {
                if (strcasecmp($data, "true") == "0") {
                    $results[$key] = 1;
                }
                if (strcasecmp($data, "false") == "0") {
                    $results[$key] = 0;
                }
            }
        }

        return $results;
    } // fix_preferences

    /**
     * load_from_session
     * This loads the preferences from the session rather then creating a connection to the database
     * @param int $uid
     * @return bool
     */
    public static function loadFromSession($uid = -1)
    {
        if (isset($_SESSION['userdata']['preferences'])
            && is_array($_SESSION['userdata']['preferences']) and $_SESSION['userdata']['uid'] == $uid
        ) {
            AmpConfig::set_by_array($_SESSION['userdata']['preferences'], true);
            return true;
        }

        return false;
    } // load_from_session

    /**
     * clear_from_session
     * This clears the users preferences, this is done whenever modifications are made to the preferences
     * or the admin resets something
     */
    public static function clearFromSession()
    {
        unset($_SESSION['userdata']['preferences']);
    }

    /**
     * is_boolean
     * This returns true / false if the preference in question is a boolean preference
     * This is currently only used by the debug view, could be used other places.. wouldn't be a half
     * bad idea
     * @param $key
     * @return bool
     */
    public static function isBoolean($key)
    {
        $boolean_array = [
            'session_cookiesecure',
            'require_session',
            'access_control',
            'require_localnet_session',
            'downsample_remote',
            'track_user_ip',
            'xml_rpc',
            'allow_zip_download',
            'ratings',
            'shoutbox',
            'resize_images',
            'show_album_art',
            'allow_public_registration',
            'captcha_public_reg',
            'admin_notify_reg',
            'use_rss',
            'download',
            'force_http_play',
            'cookie_secure',
            'allow_stream_playback',
            'allow_democratic_playback',
            'use_auth',
            'allow_localplay_playback',
            'debug',
            'lock_songs',
            'transcode_m4a',
            'transcode_mp3',
            'transcode_ogg',
            'transcode_flac',
            'shoutcast_active',
            'httpq_active',
            'show_lyrics',
        ];

        if (in_array($key, $boolean_array)) {
            return true;
        }

        return false;
    } // is_boolean

    /**
     * init
     * This grabs the preferences and then loads them into conf it should be run on page load
     * to initialize the needed variables
     */
    public static function init()
    {
        $user_id = $GLOBALS['user']->id ? Dba::escape($GLOBALS['user']->id) : '-1';

        // First go ahead and try to load it from the preferences
        if (self::loadFromSession($user_id)) {
            return true;
        }

        /* Get Global Preferences */
        $sql = <<<EOSQL
            SELECT preference.name, user_preference.value, syspref.value AS system_value
            FROM preference
                LEFT JOIN user_preference syspref
                    ON syspref.preference=preference.id
                    AND syspref.user='-1'
                    AND preference.catagory'system'
                LEFT JOIN user_preference
                    ON user_preference.preference=preference.id
                    AND user_preference.user = ?
                    AND preference.catagory='system'
EOSQL;
        $db_results = Dba::read($sql, [$user_id]);

        $results = [];
        while ($row = Dba::fetchAssoc($db_results)) {
            $value = $row['system_value'] ? $row['system_value'] : $row['value'];
            $name = $row['name'];
            $results[$name] = $value;
        } // end while sys prefs

        /* Set the Theme mojo */
        if (strlen($results['theme_name']) > 0) {
            // In case the theme was removed
            if (!Core::is_readable(AmpConfig::get('prefix') . '/themes/' . $results['theme_name'])) {
                unset($results['theme_name']);
            }
        } else {
            unset($results['theme_name']);
        }
        // Default theme if we don't get anything from their
        // preferences because we're going to want at least something otherwise
        // the page is going to be really ugly
        if (!isset($results['theme_name'])) {
            $results['theme_name'] = 'reborn';
        }
        $results['theme_path'] = '/themes/' . $results['theme_name'];

        // Load theme settings
        $themecfg = get_theme($results['theme_name']);
        $results['theme_css_base'] = $themecfg['base'];

        if (strlen($results['theme_color']) > 0) {
            // In case the color was removed
            if (!Core::is_readable(
                AmpConfig::get('prefix') . '/themes/' . $results['theme_name'] . '/templates/'
                . $results['theme_color'] . '.css'
            )) {
                unset($results['theme_color']);
            }
        } else {
            unset($results['theme_color']);
        }
        if (!isset($results['theme_color'])) {
            $results['theme_color'] = strtolower($themecfg['colors'][0]);
        }

        AmpConfig::set_by_array($results, true);
        $_SESSION['userdata']['preferences'] = $results;
        $_SESSION['userdata']['uid'] = $user_id;
        return null;
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
