<?php
/**
 * WposAdminStats is part of Wallace Point of Sale system (WPOS) API
 *
 * WposAdminStats retieves statistics for a specified timeframe
 * Is is used by Wpos admin graph to generate plot data
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
 * @since      File available since 14/04/14 9:41 PM
 */
class WposAdminStats {
    private $data;

    /**
     * Decode any provided JSON
     * @param null $data
     */
    function __construct($data = null){
        // parse the data and put it into an object
        if ($data!==null){
            $this->data = $data;
        } else {
            $this->data = new stdClass();
        }
        if (!isset($this->data->type) || $this->data->type=="all"){
            $this->data->type = null;
        }
    }

    /**
     * Set a range to run the report against
     * @param $stime
     * @param $etime
     */
    public function setRange($stime, $etime){
        $this->data->stime = $stime;
        $this->data->etime = $etime;
    }

    /**
     * Set transaction types to run the report against
     * @param $type
     */
    public function setType($type){
        $this->data->type = $type;
    }

    /**
     * Get overview stats from the current range
     * @param $result
     * @return mixed
     */
    public function getOverviewStats($result){
        $stats = new stdClass();
        $stats->saletotal = 0; // set defaults
        $stats->salenum = 0;
        $stats->refundtotal = 0;
        $stats->refundnum = 0;
        $stats->voidtotal = 0;
        $stats->voidnum = 0;
        $salesMdl = new TransactionsModel();
        $voidMdl = new SaleVoidsModel();
        // check if params set, if not set defaults
        $stime = isset($this->data->stime)?$this->data->stime:(strtotime('-1 week')*1000);
        $etime = isset($this->data->etime)?$this->data->etime:(time()*1000);

        // get non voided sales
        if (($sales = $salesMdl->getTotals($stime, $etime, 3, false, false, $this->data->type))!==false){
            $stats->salerefs = $sales[0]['refs'];
            $stats->saletotal = $sales[0]['stotal'];
            $stats->salenum = $sales[0]['snum'];
        } else {
            $result['error']= $salesMdl->errorInfo;
        }
        // get voided sales
        $voids = $salesMdl->getTotals($stime, $etime, 3, true, false, $this->data->type);
        $stats->voidrefs = $voids[0]['refs'];
        $stats->voidtotal = $voids[0]['stotal'];
        $stats->voidnum = $voids[0]['snum'];

        // get refunds
        $refund = $voidMdl->getTotals($stime, $etime, false, false, $this->data->type);
        $stats->refundrefs = $refund[0]['refs'];
        $stats->refundtotal = $refund[0]['stotal'];
        $stats->refundnum = $refund[0]['snum'];

        // calc total takings
        $stats->totaltakings = round($stats->saletotal - $stats->refundtotal, 2);
        $stats->cost = round($sales[0]['ctotal'], 2);
        $stats->profit = round($stats->totaltakings - $stats->cost, 2);
        $stats->refs = [];
        $temprefs = $stats->salerefs.($stats->voidrefs!=null?(','.$stats->voidrefs):'').($stats->refundrefs!=null?(','.$stats->refundrefs):'');
        $temprefs = explode(',', $temprefs);
        foreach ($temprefs as $value){
            if (!in_array($value, $stats->refs));
                $stats->refs[] = $value;
        }
        $stats->refs = implode(',', $stats->refs);

        $result['data'] = $stats;

        return $result;
    }

    /**
     * Get payment method totals from the current range
     * @param $result
     * @return mixed
     */
    public function getCountTakingsStats($result){
        $stats = [];
        $payMdl = new SalePaymentsModel();
        $voidMdl = new SaleVoidsModel();
        // check if params set, if not set defaults
        $stime = isset($this->data->stime)?$this->data->stime:(strtotime('-1 week')*1000);
        $etime = isset($this->data->etime)?$this->data->etime:(time()*1000);
        // get sales total for each method: actually is payment num total! There may be > one payment per sale
        if (is_array($payments = $payMdl->getTotals($stime, $etime, 3, false, true, $this->data->type))){
            foreach ($payments as $payment){
                $stats[$payment['method']] = new stdClass();
                $stats[$payment['method']]->refs = $payment['refs'];
                $stats[$payment['method']]->saletotal = $payment['stotal'];
                $stats[$payment['method']]->salenum = $payment['snum'];
                $stats[$payment['method']]->refundrefs = '';
                $stats[$payment['method']]->refundtotal = 0; // set defaults
                $stats[$payment['method']]->refundnum = 0;
            }
            // get refunded totals for each method
            if (is_array($refunds = $voidMdl->getTotals($stime, $etime, false, true, $this->data->type))){
                foreach ($refunds as $refund){
                    if (!isset($stats[$refund['method']])){
                        $stats[$refund['method']] = new stdClass();
                    }
                    $stats[$refund['method']]->refundrefs = $refund['refs'];
                    $stats[$refund['method']]->refundtotal = $refund['stotal'];
                    $stats[$refund['method']]->refundnum = $refund['snum'];
                    if (!isset($stats[$refund['method']]->salenum)){
                        $stats[$refund['method']]->saletotal = 0; // set fields with default vals if none set
                        $stats[$refund['method']]->salenum = 0;
                        $stats[$refund['method']]->refs = '';
                    }
                }
            } else {
                $result['error'] = $voidMdl->errorInfo;
            }
        } else {
            $result['error'] = $payMdl->errorInfo;
        }
        // calculate unaccounted totals (unpaid; for accural accounting purposes)
        $transMdl = new TransactionsModel();
        $totals = $transMdl->getUnaccountedTotals($stime, $etime, false, $this->data->type)[0];
        if ($totals['stotal']!=0){
            $stats['Unaccounted'] = new stdClass();
            $stats['Unaccounted']->refs = $totals['refs'];
            $stats['Unaccounted']->saletotal = $totals['stotal'];
            $stats['Unaccounted']->salenum = $totals['snum'];
            $stats['Unaccounted']->refundrefs = '';
            $stats['Unaccounted']->refundtotal = 0;
            $stats['Unaccounted']->refundnum = 0;
        }
        // calcuate balances
        foreach ($stats as $key => $stat){
            $stats[$key]->balance = round($stats[$key]->saletotal-$stats[$key]->refundtotal, 2);
        }
        // include totals if requested
        if (isset($this->data->totals) &&  $this->data->totals == true){
            $stats["Totals"] = $this->getOverviewStats($result)['data'];
        }

        $result['data'] = $stats;

        return $result;
    }

    /**
     * Get what's selling stats for the current range, optionally grouping by supplier
     * @param $result
     * @param int $group (0 for none, 1 for categories, 2 for suppliers)
     * @return mixed
     */
    public function getWhatsSellingStats($result, $group = 0){
        $stats = [];
        $itemsMdl = new SaleItemsModel();
        // check if params set, if not set defaults
        $stime = isset($this->data->stime)?$this->data->stime:(strtotime('-1 week')*1000);
        $etime = isset($this->data->etime)?$this->data->etime:(time()*1000);

        if (is_array($items = $itemsMdl->getStoredItemTotals($stime, $etime, $group, true, $this->data->type))){
            foreach ($items as $item){
                $stats[$item['groupid']] = new stdClass();
                if ($item['groupid']==0){
                    $stats[$item['groupid']]->name = "Miscellaneous";
                } else {
                    $stats[$item['groupid']]->name = $item['name'];
                }
                $stats[$item['groupid']]->refs = $item['refs'];
                $stats[$item['groupid']]->soldqty = $item['itemnum'];
                $stats[$item['groupid']]->discounttotal = number_format($item['discounttotal'], 2, ".", "");
                $stats[$item['groupid']]->taxtotal = number_format($item['taxtotal'], 2, ".", "");
                $stats[$item['groupid']]->soldtotal = number_format($item['itemtotal'], 2, ".", "");
                $stats[$item['groupid']]->refundqty = $item['refnum'];
                $stats[$item['groupid']]->refundtotal = number_format($item['reftotal'], 2, ".", "");
                $stats[$item['groupid']]->netqty = $item['itemnum']-$item['refnum'];
                $stats[$item['groupid']]->balance = number_format($item['itemtotal']-$item['reftotal'], 2, ".", "");
            }
        } else {
            $result['error'] = $itemsMdl->errorInfo;
        }

        $result['data'] = $stats;

        return $result;
    }

    /**
     * Get tax statistics from the current range
     * @param $result
     * @return mixed
     */
    public function getTaxStats($result){
        $stats = [];
        $itemsMdl = new SaleItemsModel();
        // check if params set, if not set defaults
        $stime = isset($this->data->stime)?$this->data->stime:(strtotime('-1 week')*1000);
        $etime = isset($this->data->etime)?$this->data->etime:(time()*1000);

        if (is_array($saleitems = $itemsMdl->getTotalsRange($stime, $etime, true, $this->data->type))){
            $taxMdl = new TaxItemsModel();
            $taxdata = $taxMdl->get();
            $taxes = [];
            foreach ($taxdata as $value){
                $taxes[$value['id']] = (object) $value;
            }

            foreach ($saleitems as $saleitem){
                $itemtax = json_decode($saleitem['tax']);

                if ($itemtax->total==0){
                    if (!array_key_exists(-1, $stats)){
                        $stats[-1] = new stdClass();
                        $stats[-1]->refs = [];
                        $stats[-1]->name = "Untaxed";
                        $stats[-1]->qtyitems = 0;
                        $stats[-1]->saletotal = 0;
                        $stats[-1]->refundtotal = 0;
                        $stats[-1]->saletax = 0;
                        $stats[-1]->refundtax = 0;
                    }
                    if (!in_array($saleitem['ref'], $stats[-1]->refs))
                        $stats[-1]->refs[] = $saleitem['ref'];
                    $stats[-1]->qtyitems += $saleitem['qty'];
                    $stats[-1]->saletotal += $saleitem['itemtotal'];
                    $stats[-1]->refundtotal += $saleitem['refundtotal'];
                } else {
                    // subtotal excludes tax, factors in discount
                    $discountedtax = $saleitem['discount']>0 ?  round($itemtax->total - ($itemtax->total*($saleitem['discount']/100)), 2) : $itemtax->total;
                    //echo($discountedtax);
                    $itemsubtotal = $saleitem['itemtotal'] - $discountedtax;
                    $refundsubtotal = $saleitem['refundtotal'] - round(($discountedtax/$saleitem['qty']) * $saleitem['refundqty'], 2);
                    foreach ($itemtax->values as $key=>$value){
                        if (!array_key_exists($key, $stats)){
                            $stats[$key] = new stdClass();
                            $stats[$key]->refs = [];
                            $stats[$key]->name = isset($taxes[$key])?$taxes[$key]->name:"Unknown";
                            $stats[$key]->qtyitems = 0;
                            $stats[$key]->saletotal = 0;
                            $stats[$key]->refundtotal = 0;
                            //$stats[$key]->saletax = 0;
                            //$stats[$key]->refundtax = 0;
                        }
                        if (!in_array($saleitem['ref'], $stats[$key]->refs))
                            $stats[$key]->refs[] = $saleitem['ref'];
                        $stats[$key]->qtyitems += $saleitem['qty'];
                        $stats[$key]->saletotal += $itemsubtotal; // subtotal excludes tax, factors in discount
                        $stats[$key]->refundtotal += $refundsubtotal;
                        //$stats[$key]->saletax += $saleitem['discount']>0 ? round($value - ($value*($saleitem['discount']/100)), 2) : $value;
                        // $stats[$key]->refundtax += $saleitem['discount']>0 ? (round($value/($saleitem['discount']/100), 2)/$saleitem['qty'])*$saleitem['refundqty']: ($value/$saleitem['qty'])*$saleitem['refundqty'];
                    }
                }
            }
            foreach ($stats as $key=>$value){
                $taxitems = WposAdminUtilities::getTaxTable()['items'];
                $stats[$key]->saletax = round($taxitems[$key]['multiplier']*$stats[$key]->saletotal, 2);
                $stats[$key]->refundtax = round($taxitems[$key]['multiplier']*$stats[$key]->refundtotal, 2);
                $stats[$key]->balance = number_format($stats[$key]->saletax-$stats[$key]->refundtax, 2);
            }
            // Get cash rounding total
            $roundtotals = $itemsMdl->getRoundingTotal($stime, $etime);
            if ($roundtotals!==false){
                $stats[0] = new stdClass();
                $stats[0]->refs = $roundtotals[0]['refs'];
                $stats[0]->name = "Cash Rounding";
                $stats[0]->qty = $roundtotals[0]['num'];
                $stats[0]->total = $roundtotals[0]['total'];
            } else {
                $result['error'] = $itemsMdl->errorInfo;
            }
        } else {
            $result['error'] = $itemsMdl->errorInfo;
        }

        $result['data'] = $stats;

        return $result;
    }

    /**
     * Get grouped sales stats for the current range, grouped by user, device or location
     * @param $result
     * @param string $type
     * @return mixed
     */
    public function getDeviceBreakdownStats($result, $type = 'device'){
        $stats = [];
        $salesMdl = new TransactionsModel();
        $voidMdl = new SaleVoidsModel();
        // check if params set, if not set defaults
        $stime = isset($this->data->stime)?$this->data->stime:(strtotime('-1 week')*1000);
        $etime = isset($this->data->etime)?$this->data->etime:(time()*1000);
        // setup default object
        $defaults = new stdClass();
        $defaults->refs = '';
        $defaults->refundrefs = '';
        $defaults->voidtotal = 0;
        $defaults->voidnum = 0;
        $defaults->saletotal = 0;
        $defaults->salenum = 0;
        $defaults->refundtotal = 0;
        $defaults->refundnum = 0;
        // get non voided sales
        if (($sales = $salesMdl->getGroupedTotals($stime, $etime, 3, false, $type))!==false){
            foreach ($sales as $sale){
                if ($sale['groupid']==null) $sale['name'] = "Admin Dash";
                if (!isset($stats[$sale['groupid']])){
                    $stats[$sale['groupid']] = clone $defaults;
                    $stats[$sale['groupid']]->name = $sale['name'];
                }
                $stats[$sale['groupid']]->refs = $sale['refs'];
                $stats[$sale['groupid']]->salerefs = $sale['refs'];
                $stats[$sale['groupid']]->saletotal = $sale['stotal'];
                $stats[$sale['groupid']]->salenum = $sale['snum'];
            }
        } else {
            $result['error']= "Error getting sales: ".$salesMdl->errorInfo;
        }
        // get voided sales
        if (($voids = $salesMdl->getGroupedTotals($stime, $etime, 3, true, $type))!==false){
            foreach ($voids as $void){
                if ($void['groupid']==null) $sale['name'] = "Admin Dash";
                if (!isset($stats[$void['groupid']])){
                    $stats[$void['groupid']] = clone $defaults;
                    $stats[$void['groupid']]->name = $void['name'];
                }
                $stats[$void['groupid']]->refs .= ($stats[$void['groupid']]->refs==''?'':',').$void['refs'];
                $stats[$void['groupid']]->voidrefs = $void['refs'];
                $stats[$void['groupid']]->voidtotal = $void['stotal'];
                $stats[$void['groupid']]->voidnum = $void['snum'];
            }
        } else {
            $result['error']= "Error getting voided sales: ".$salesMdl->errorInfo;
        }
        // get refunds
        if (($refunds = $voidMdl->getGroupedTotals($stime, $etime, false, $type))!==false){
            foreach ($refunds as $refund){
                if ($refund['groupid']==null) $sale['name'] = "Admin Dash";
                if (!isset($stats[$refund['groupid']])){
                    $stats[$refund['groupid']] = clone $defaults;
                    $stats[$refund['groupid']]->name = $refund['name'];
                }
                $stats[$refund['groupid']]->refs .= ($stats[$refund['groupid']]->refs==''?'':',').$refund['refs'];
                $stats[$refund['groupid']]->refundrefs = $refund['refs'];
                $stats[$refund['groupid']]->refundtotal = $refund['stotal'];
                $stats[$refund['groupid']]->refundnum = $refund['snum'];
            }
        } else {
            $result['error']= "Error getting refunds: ".$voidMdl->errorInfo;
        }
        // calc total takings for each device/location
        foreach ($stats as $key => $stat) {
            $stats[$key]->balance = number_format($stat->saletotal - $stat->refundtotal, 2, '.', '');
        }

        // include totals if requested
        if ($this->data->totals == true){
            $result = $this->getOverviewStats($result);
            $stats["Totals"] = $result['data'];
        }

        $result['data'] = $stats;

        return $result;
    }

    /**
     * Get the current stock levels, does not take into account the current range
     * @param $result
     * @return mixed
     */
    public function getStockLevels($result){
        $stats = [];
        $stockMdl = new StockModel();
        $stocks = $stockMdl->get(null, null, true);
        if ($stocks===false){
            $result['error']= "Error getting stock data: ".$stockMdl->errorInfo;
        }
        foreach ($stocks as $stock){
            $stats[$stock['id']] = new stdClass();
            if ($stock['locationid']==0){
                $stats[$stock['id']]->location = "Warehouse";
            } else {
                $stats[$stock['id']]->location = $stock['location'];
            }
            $stats[$stock['id']]->name = $stock['name'];
            $stats[$stock['id']]->supplier = $stock['supplier'];
            $stats[$stock['id']]->stocklevel = $stock['stocklevel'];
            $stats[$stock['id']]->stockvalue = $stock['stockvalue'];
        }
        $result['data'] = $stats;
        return $result;
    }
}