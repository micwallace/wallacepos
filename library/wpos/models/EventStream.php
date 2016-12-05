<?php
/**
 * EventStream is part of Wallace Point of sales system API
 *
 * EventStream is used to output progress/result in HTML5 EventSource format
 *
 * LICENSE: WPOS is currently under development, all rights are reserved.
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014-2020 WallaceIT. (https://wallaceit.com.au)
 * @license    http://www.example.com/license   BSD License
 * @link       http://www.example.com/package/PackageName
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      File available since 11/10/14 12:51PM
 */
class EventStream {
    public static function iniStream(){
        header('Connection: keep-alive');
        header('Cache-Control: no-cache');
        header("Content-Type: text/event-stream\n\n");
        # Set this so PHP doesn't timeout during a long stream
        set_time_limit(0);
        # Disable Apache and PHP's compression of output to the client
        apache_setenv('no-gzip', 1);
        ini_set('zlib.output_compression', 0);
        # Set implicit flush, and flush all current buffers
        ini_set('implicit_flush', 1);
        for ($i = 0; $i < ob_get_level(); $i++)
            ob_end_flush();
        ob_implicit_flush(1);
    }
    public static function sendStreamData($data){
        // echo eventsource event object, followed by 2x\n to cause browser to fire event
        $data['output'] = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x80-\x9F](\[1G)?/u', '', $data['output']); // replace control codes in terminal output
        echo('data: '.json_encode($data)."\n\n");
    }
}