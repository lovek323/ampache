<?php

namespace Ampache;

use Access;
use LibraryItemInterface;
use UI;
use User;

/**
 * Abstracting out functionality needed by both normal and smart playlists.
 */
abstract class AbstractPlaylist extends AbstractDatabaseObject implements LibraryItemInterface
{
    // Database variables
    /**
     * @var int $id
     */
    public $id;
    /**
     * @var string $name
     */
    public $name;
    /**
     * @var int $user
     */
    public $user;
    /**
     * @var string $type
     */
    public $type;

    /**
     * @var string $f_type
     */
    public $f_type;
    /**
     * @var string $f_name
     */
    public $f_name;
    /**
     * @var string $f_user
     */
    public $f_user;

    abstract public function getItems();

    /**
     * format
     * This takes the current playlist object and gussies it up a little
     * bit so it is presentable to the users
     * @param bool $details
     */
    public function format($details = true)
    {
        $this->f_name = $this->name;
        $this->f_type = ($this->type == 'private') ? UI::get_icon('lock', T_('Private')) : '';

        if ($details) {
            $client = new User($this->user);
            $client->format();
            $this->f_user = $client->f_name;
        }
    } // format

    /**
     * has_access
     * This function returns true or false if the current user
     * has access to this playlist
     */
    public function hasAccess()
    {
        if (!Access::check('interface', 25)) {
            return false;
        }
        if ($this->user == $GLOBALS['user']->id) {
            return true;
        } else {
            return Access::check('interface', 75);
        }
    } // has_access

    public function getMedia($filterType = null)
    {
        $medias = $this->getItems();
        if ($filterType) {
            $nmedias = [];
            foreach ($medias as $media) {
                if ($media['object_type'] == $filterType) {
                    $nmedias[] = $media;
                }
            }
            $medias = $nmedias;
        }
        return $medias;
    }

    public function getKeywords()
    {
        return [];
    }

    public function getFullname()
    {
        return $this->f_name;
    }

    public function getParent()
    {
        return null;
    }

    public function getChildren()
    {
        $childrens = [];
        $items = $this->getItems();
        foreach ($items as $item) {
            if (!in_array($item['object_type'], $childrens)) {
                $childrens[$item['object_type']] = [];
            }
            $childrens[$item['object_type']][] = $item['object_id'];
        }

        return $this->getItems();
    }

    public function searchChildren($name)
    {
        return [];
    }

    public function getUserOwner()
    {
        return $this->user;
    }

    public function getDefaultArtKind()
    {
        return 'default';
    }

    public function getDescription()
    {
        return null;
    }

    public function displayArt($thumb = 2)
    {
        // no art
    }

    /**
     * get_catalogs
     *
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function getCatalogIds()
    {
        return [];
    }
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
