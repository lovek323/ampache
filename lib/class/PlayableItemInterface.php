<?php
/* vim:set softtabstop=4 shiftwidth=4 expandtab: */

/**
 * This defines how the playable item file classes should work, this lists all required functions and the expected
 * input.
 */
interface PlayableItemInterface
{
    /**
     * Creates member variables for output
     * @param bool $details
     */
    public function format($details = true);

    /**
     * Get the item full name.
     */
    public function getFullname();

    /**
     * Get parent. Return parent `object_type`, `object_id` ; null otherwise.
     */
    public function getParent();

    /**
     * Get direct childrens. Return an array of `object_type`, `object_id` childrens.
     */
    public function getChildren();

    /**
     * Search for direct childrens. Return an array of `object_type`, `object_id` childrens matching the criteria.
     * @param $name
     */
    public function searchChildren($name);

    /**
     * Get all media from all children. Return an array of `object_type`, `object_id` medias.
     * @param $filterType
     */
    public function getMedia($filterType = null);

    /**
     * Get all catalog ids related to this item.
     * @return int[]
     */
    public function getCatalogIds();
}
