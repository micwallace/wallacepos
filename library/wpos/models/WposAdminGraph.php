<?php
/**
 * WposAdminGraph is part of Wallace Point of Sale system (WPOS) API
 *
 * WposAdminGraph generates graph plot data using the functions available in WposAdminStats
 * It can plot using any function that provides stime/etime parameters
 *
 * WallacePOS is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 3.0 of the License, or (at your option) any later version.
 *
 * WallacePOS is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details:
 * <https://www.gnu.org/licenses/lgpl.html>
 *
 * @package    wpos
 * @copyright  Copyright (c) 2014 WallaceIT. (https://wallaceit.com.au)

 * @link       https://wallacepos.com
 * @author     Michael B Wallace <micwallace@gmx.com>
 * @since      File available since 14/04/14 9:42 PM
 */
class WposAdminGraph {
    /**
     * @var mixed provided paramters decoded from JSON
     */
    private $data;

    /**
     * @param $data
     */
    function __construct($data=null){
        // parse the data if needed and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
    }

    /**
     * Generate plot data using the specified type
     * @param $result
     * @param $graphtype
     * @return mixed
     */
    private function getGraph($result, $graphtype){
        // validate input
        $jsonval = new JsonValidate($this->data, '{"stime":1, "etime":1, "interval":1}');
        if (($errors = $jsonval->validate())!==true){
            $result['error'] = $errors;
            return $result;
        }
        // Initialize the stats object
        $stats = new WposAdminStats(null);
        $graph = [];
        $serieslist = [];
        $interval = isset($this->data->interval)?$this->data->interval:(86400000); // default interval is one day
        $curstime = isset($this->data->stime)?$this->data->stime:(strtotime('-1 week')*1000);
        $curetime = $curstime + $interval;
        $stopetime = isset($this->data->etime)?$this->data->etime:(time()*1000);
        $tempstats = null;
        while ($curstime<=$stopetime){
            $stats->setRange($curstime, $curetime);
            switch($graphtype){
                case 1: $tempstats=$stats->getOverviewStats($result);
                   break;
                case 2: $tempstats=$stats->getCountTakingsStats($result);
                   break;
                case 3: $tempstats=$stats->getDeviceBreakdownStats($result);
                    break;
                case 4: $tempstats=$stats->getDeviceBreakdownStats($result, 'location');
                    break;
            }
            if ($tempstats['error']=="OK"){
                // put into series list
                foreach ($tempstats['data'] as $key => $value){
                    $serieslist[$key] = $key;
                }
                // put into array
                $graph[$curstime] = $tempstats['data'];
            } else {
                $result['error'].= $tempstats['error'];
                break;
            }
            // move to the next segment
            $curstime+=$interval;
            $curetime+=$interval;
        }
        // if it's not the general graph we need to loop through and fill in null data
        if ($graphtype!=1){
            $defaultobj = new stdClass();
            $defaultobj->balance = 0;
            // loop through each series value and add 0 values for null data
            foreach ($graph as $ykey => $yvals){
                //$result['error'].="\n".json_encode($yvals);
                foreach ($serieslist as $value){ // use serieslist to spot null values
                        if ($yvals[$value] == null || empty($yvals)){ // check if series key exists in current timeset
                            //$result['error'].="\nInserting default";
                            $yvals[$value] = $defaultobj;
                            $graph[$ykey] = $yvals;
                        }
                }
            }
        }

        $result['data'] = $graph;

        return $result;
    }

    /**
     * Generate overview plot data (sales, refunds, takings)
     * @param $result
     * @return mixed
     */
    public function getOverviewGraph($result){
        return $this->getGraph($result, 1);
    }

    /**
     * Generate payment method plot data
     * @param $result
     * @return mixed
     */
    public function getMethodGraph($result){
        return $this->getGraph($result, 2);
    }

    /**
     * Generate device takings plot data
     * @param $result
     * @return mixed
     */
    public function getDeviceGraph($result){
        return $this->getGraph($result, 3);
    }

    /**
     * Generate location takings plot data
     * @param $result
     * @return mixed
     */
    public function getLocationGraph($result){
        return $this->getGraph($result, 4);
    }

}