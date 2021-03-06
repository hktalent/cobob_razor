<?php
/**
 * Cobub Razor
 *
 * An open source mobile analytics system
 *
 * PHP versions 5
 *
 * @category  MobileAnalytics
 * @package   CobubRazor
 * @author    Cobub Team <open.cobub@gmail.com>
 * @copyright 2011-2016 NanJing Western Bridge Co.,Ltd.
 * @license   http://www.cobub.com/docs/en:razor:license GPL Version 3
 * @link      http://www.cobub.com
 * @since     Version 0.1
 */

/**
 * Versionmodel Model
 *
 * @category PHP
 * @package  Model
 * @author   Cobub Team <open.cobub@gmail.com>
 * @license  http://www.cobub.com/docs/en:razor:license GPL Version 3
 * @link     http://www.cobub.com
 */
class Versionmodel extends CI_Model
{


    /** 
     * Construct load 
     * Construct function 
     * 
     * @return void 
     */
    function __construct()
    {
        parent::__construct();
        $this -> load -> model("common");
        $this -> load -> model('product/productmodel', 'product');

    }
    
    /** 
     * Get basic version info 
     * getBasicVersionInfo function 
     * 
     * @param string $productId productid 
     * @param string $date      date 
     * 
     * @return void 
     */
    function getBasicVersionInfo($productId, $date)
    {
        $dwdb = $this -> load -> database('dw', true);
        $sql = "SELECT
                    /*应用版本*/
                    tt.version_name,
                    /*启动次数*/
                    ifnull(t.sessions, 0) sessions,
                    /*活跃用户*/
                    ifnull(t.startusers, 0) startusers,
                    /*新增用户*/
                    ifnull(t.newusers, 0) newusers,
                    /*升级用户*/
                    ifnull(t.upgradeusers, 0) upgradeusers,
                    /*累计用户*/
                    (
                        SELECT
                            ifnull(max(allusers), 0)
                        FROM
                            " . $dwdb -> dbprefix('dim_date') . " da,
                            " . $dwdb -> dbprefix('sum_basic_product_version') . " pv
                        WHERE
                            da.datevalue = '$date'
                        AND pv.date_sk <= da.date_sk
                        AND pv.product_id = $productId
                        AND pv.version_name = tt.version_name
                    ) allusers
                FROM
                    /*根据产品ID和版本信息查询得到版本号、启动次数等信息*/
                    (
                        SELECT
                            p.version_name,
                            sessions,
                            startusers,
                            newusers,
                            upgradeusers
                        FROM
                            " . $dwdb -> dbprefix('sum_basic_product_version') . " s,
                            " . $dwdb -> dbprefix('dim_date') . " d,
                            " . $dwdb -> dbprefix('dim_product') . " p
                        WHERE
                            d.datevalue = '$date'
                        AND d.date_sk = s.date_sk
                        AND s.product_id = $productId
                        AND p.product_id = s.product_id
                        AND p.product_active = 1
                        AND p.channel_active = 1
                        AND p.version_active = 1
                        AND p.version_name = s.version_name
                        GROUP BY
                            p.version_name
                    ) t
                RIGHT JOIN (
                    /*查询得到版本号信息*/
                    SELECT DISTINCT
                        pp.version_name
                    FROM
                        " . $dwdb -> dbprefix('dim_product') . " pp
                    WHERE
                        pp.product_id = $productId
                    AND pp.product_active = 1
                    AND pp.channel_active = 1
                    AND pp.version_active = 1
                ) tt ON tt.version_name = t.version_name";
        $query = $dwdb -> query($sql);
        $basicRet = $query -> result();
        $ret = array();
        $totalusers = 0;
        $activeUsers = 0;
        if ($basicRet != null && count($basicRet) > 0) {

            for ($i = 0; $i < count($basicRet); $i++) {
                $record = array();
                $record["version"] = $basicRet[$i] -> version_name;
                $record["total"] = $basicRet[$i] -> allusers;
                $record["new"] = $basicRet[$i] -> newusers;
                $record["update"] = $basicRet[$i] -> upgradeusers;
                $record["active"] = $basicRet[$i] -> startusers;
                $record["start"] = $basicRet[$i] -> sessions;

                array_push($ret, $record);
            }
        }
        return $ret;
    }
    
    /** 
     * Get the report data 
     * GetVersionData function 
     * 
     * @param string $fromTime  fromtime 
     * @param string $toTime    totime 
     * @param string $productid productid 
     * 
     * @return array 
     */
    function getVersionData($fromTime, $toTime, $productid)
    {
        $ret = array();
        $currentProduct = $this -> common -> getCurrentProduct();
        $productId = $currentProduct -> id;
        //$fromTime = $this->product->getReportStartDate($currentProduct,$fromTime);
        $dwdb = $this -> load -> database('dw', true);
        $sql = "select d.datevalue,p.version_name,
				ifnull(startusers,0) startusers,
				ifnull(newusers,0) newusers			
				from (select date_sk,datevalue 
				from " . $dwdb -> dbprefix('dim_date') . "  where
				datevalue between '$fromTime' and '$toTime')  d 
				cross join 
				(select pp.version_name 
				from " . $dwdb -> dbprefix('dim_product') . " pp
				where pp.product_id =$productid and
				pp.product_active=1 and pp.channel_active=1
				 and pp.version_active=1 
				 group by pp.version_name) p
				left join (select * from 
				" . $dwdb -> dbprefix('sum_basic_product_version') . " 
				where product_id=$productid) s  
				on d.date_sk = s.date_sk 
				and s.version_name = p.version_name	
				group by datevalue,p.version_name
		";
        $query = $dwdb -> query($sql);
        if ($query != null && $query -> num_rows > 0) {

            $arr = $query -> result_array();

            $content_arr = array();
            for ($i = 0; $i < count($arr); $i++) {
                $row = $arr[$i];
                $versionname = $row['version_name'];
                $allkey = array_keys($content_arr);
                if (!in_array($versionname, $allkey))
                    $content_arr[$versionname] = array();
                $tmp = array();
                $tmp['startusers'] = $row['startusers'];
                $tmp['datevalue'] = substr($row['datevalue'], 0, 10);
                $tmp['newusers'] = $row['newusers'];
                $tmp['version_name'] = $row['version_name'];
                array_push($content_arr[$versionname], $tmp);

            }
            $all_version_name = array_keys($content_arr);
            $ret['content'] = $content_arr;

        }
        //$ret ['title'] = $title;
        return $ret;

    }
    
    /** 
     * Get version contrast 
     * getVersionContrast function 
     * 
     * @param string $productId productid 
     * @param string $from      from 
     * @param string $to        to 
     * @param string $version   version 
     * 
     * @return query 
     */
    function getVersionContrast($productId, $from, $to, $version)
    {
        $dwdb = $this -> load -> database('dw', true);
        if ($version != 100) {
            $sql = "select 
            d.version_name,
	       ifnull(startusers,0) startusers,
	       ifnull(newusers,0) newusers
		  from
		( select version_name, product_id,
		  sum(startusers) startusers,
		  sum(newusers) newusers
		  from " . $dwdb -> dbprefix('sum_basic_product_version') . " v 
		  inner join " . $dwdb -> dbprefix('dim_date') . " d
	      on v.date_sk =d.date_sk and 
	      d.datevalue between '$from' and '$to'
	       where v.product_id=$productId
	        group by v.version_name
		     ) d 
		left join " . $dwdb -> dbprefix('dim_product') . " p 
		on d.version_name=p.version_name 
		and d.product_id = p.product_id
		where p.product_id = $productId
		 and p.product_active=1 
		and p.channel_active=1 and p.version_active=1 
		group by d.version_name
		order by startusers desc,newusers desc limit $version ";
        } else {
            $sql = "select 
	            d.version_name,
		       ifnull(startusers,0) startusers,
		       ifnull(newusers,0) newusers
			  from
			( select version_name, product_id,
			  sum(startusers) startusers,
			  sum(newusers) newusers
			  from " . $dwdb -> dbprefix('sum_basic_product_version') . " v 
			  inner join " . $dwdb -> dbprefix('dim_date') . " d
		      on v.date_sk =d.date_sk and 
		      d.datevalue between '$from' and '$to'
		       where v.product_id=$productId
		        group by v.version_name
			     ) d 
			left join " . $dwdb -> dbprefix('dim_product') . " p 
			on d.version_name=p.version_name 
			and d.product_id = p.product_id
			where p.product_id = $productId
			 and p.product_active=1 
			and p.channel_active=1 and p.version_active=1 
			group by d.version_name
			order by startusers desc,newusers desc ";
        }
        $query = $dwdb -> query($sql);
        return $query;

    }
    
    /** 
     * Get new and active all count 
     * GetNewAndActiveAllCount function 
     * 
     * @param string $productId productid 
     * @param string $from      from 
     * @param string $to        to 
     * 
     * @return query 
     */
    function getNewAndActiveAllCount($productId, $from, $to)
    {
        $dwdb = $this -> load -> database('dw', true);
        $sql = " select 
	    ifnull(sum(vv.startusers),0) startusers ,
	    ifnull(sum(vv.newusers),0) newusers
        from(
	       select 
	       ifnull(startusers,0) startusers,
	       ifnull(newusers,0) newusers
		  from
		( select version_name, product_id,
		  sum(startusers) startusers,
		  sum(newusers) newusers
		  from " . $dwdb -> dbprefix('sum_basic_product_version') . " v 
		  inner join " . $dwdb -> dbprefix('dim_date') . " d
	      on v.date_sk =d.date_sk and 
	      d.datevalue between '$from' and '$to'
	       where v.product_id=$productId
	        group by v.version_name
		     ) d 
		left join " . $dwdb -> dbprefix('dim_product') . " p 
		on d.version_name=p.version_name 
		and d.product_id = p.product_id
		where p.product_id = $productId
		 and p.product_active=1 
		and p.channel_active=1 and p.version_active=1 
		group by d.version_name	) vv";
        $query = $dwdb -> query($sql);
        return $query -> result_array();
    }

}
?>