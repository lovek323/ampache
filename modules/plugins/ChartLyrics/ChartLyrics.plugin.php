<?php

class ChartLyrics
{
    public $name           = 'ChartLyrics';
    public $categories     = 'lyrics';
    public $description    = 'Get lyrics from ChartLyrics';
    public $url            = 'http://www.chartlyrics.com';
    public $version        ='000001';
    public $min_ampache    ='360022';
    public $max_ampache    ='999999';

    /**
     * Constructor
     * This function does nothing...
     */
    public function __construct()
    {
        return true;
    } // constructor

    /**
     * install
     * This is a required plugin function
     */
    public function install()
    {
        return true;
    } // install

    /**
     * uninstall
     * This is a required plugin function
     */
    public function uninstall()
    {
        return true;
    } // uninstall

    /**
     * This is a required plugin function; here it populates the prefs we
     * need for this object.
     * @return bool
     */
    public function load()
    {
        return true;
    }

    /**
     * get_lyrics
     * This will look web services for a song lyrics.
     * @param $song
     * @return array|bool
     */
    public function get_lyrics($song)
    {
        $base    = 'http://api.chartlyrics.com/apiv1.asmx/';
        $uri     = $base . 'SearchLyricDirect?artist=' . urlencode($song->f_artist) . '&song=' . urlencode($song->title);
        $request = Requests::get($uri, [], Core::requests_options());
        if ($request->status_code == 200) {
            $xml = simplexml_load_string($request->body);
            if ($xml) {
                if (!empty($xml->Lyric)) {
                    return ['text' => nl2br($xml->Lyric), 'url' => $xml->LyricUrl];
                }
            }
        }
        
        return false;
    } // get_lyrics
} // end Ampachelyricwiki

/* vim:set softtabstop=4 shiftwidth=4 expandtab: */
