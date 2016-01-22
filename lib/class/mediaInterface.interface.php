<?php

/**
 * This defines how the media file classes should work, this lists all required functions and the expected input.
 */
interface MediaInterface
{
    /**
     * Returns an array of strings; current types are 'native' and 'transcode'.
     * @param null $player
     * @return
     */
    public function get_stream_types($player = null);

    /**
     * Returns the url to stream the specified object.
     * @param $oid
     * @param string $additional_params
     * @param null $player
     * @param bool $local
     * @return
     */
    public static function play_url($oid, $additional_params = '', $player = null, $local = false);

    /**
     * get_transcode_settings
     *
     * Should only be called if 'transcode' was returned by get_stream_types
     * Returns a raw transcode command for this item; the optional target
     * parameter can be used to request a specific format instead of the
     * default from the configuration file.
     * @param null $target
     * @param null $player
     * @param array $options
     * @return
     */
    public function getTranscodeSettings($target = null, $player = null, $options = []);

    /**
     * get_stream_name
     * Get the complete name to display for the stream.
     */
    public function get_stream_name();

    public function set_played($user, $agent, $location);
}

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
