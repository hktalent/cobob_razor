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
 * Dauusers Controller
 *
 * @category PHP
 * @package  Controller
 * @author   Cobub Team <open.cobub@gmail.com>
 * @license  http://www.cobub.com/docs/en:razor:license GPL Version 3
 * @link     http://www.cobub.com
 */
class Newserveractivity extends CI_Controller
{
	private $data = array();

	/**
	 * Construct funciton, to pre-load database configuration
	 *
	 * @return void
	 */
	function __construct()
	{
		parent::__construct();
		//common function
		$this->load->Model('common');
		//dauusers model
		$this->load->model('sysanaly/newserveractivitymodel', 'newserveractivity');
		//check is_logged_in
		$this->common->requireLogin();
		//export csv lib
		$this->load->library('export');
		//get versions
		$this->load->model('event/userevent', 'userevent');
		//get channels
		$this->load->model('channelmodel', 'channel');
		//get servers
		$this->load->model('servermodel', 'server');
		//check compare product
		$this->common->checkCompareProduct();
	}

	/**
	 * Index function , load view userremainview
	 *
	 * @return void
	 */
	function index()
	{	
		$channel = $this->common->getChannel();
		$server = $this->common->getServer();
		$version = $this->common->getVersion();
		$fromTime = $this->common->getFromTime();
		$toTime = $this->common->getToTime();

		$this->data['result'] = $this->newserveractivity->getNewserveractivityData($fromTime,$toTime,$channel,$server,$version);
		$this->data['select'] = $this->newserveractivity->getSelectName($fromTime,$toTime,$channel,$server,$version);

		$this->common->requireProduct();
		$this->common->loadHeaderWithDateControl(lang('m_kaiFuHuoDong_yao'));
		
		$ruteName = $this->router->fetch_class();
		$canRead = $this->common->isDisplay($ruteName);
		if($canRead == '1'){
			$this->load->view('sysanaly/newserveractivityview',$this->data);
		}
		else{
			$this->load->view('forbidden');
		}
	}
	function charts(){
		$channel = $this->common->getChannel();
		$server = $this->common->getServer();
		$version = $this->common->getVersion();
		$fromTime = $this->common->getFromTime();
		$toTime = $this->common->getToTime();

		$activityIssue = $_GET['activity_issue'];
		$this->data['result1'] = $this->newserveractivity->getDetailNewserveractivityData($fromTime,$toTime,$channel,$server,$version,$activityIssue,$detailstype='output');
		$this->data['result2'] = $this->newserveractivity->getDetailNewserveractivityData($fromTime,$toTime,$channel,$server,$version,$activityIssue,$detailstype='consume');
		$this->data['result3'] = $this->newserveractivity->getDetailNewserveractivityData($fromTime,$toTime,$channel,$server,$version,$activityIssue,$detailstype='action');

		$this->load->view('layout/reportheader');
		$this->load->view('widgets/newserveractivity',$this->data);
	}
	function filter(){
		$channel = $this->common->getChannel();
		$server = $this->common->getServer();
		$version = $this->common->getVersion();
		$fromTime = $this->common->getFromTime();
		$toTime = $this->common->getToTime();
		$name = $_GET['name'];
		if($name == '全部'){
			$result = $this->newserveractivity->getNewserveractivityData($fromTime,$toTime,$channel,$server,$version);
		}
		else{
			$result = $this->newserveractivity->getFilterNewserveractivityData($fromTime,$toTime,$channel,$server,$version,$name);
		}
		echo json_encode($result);
	}
	function distributeddetails(){
		$channel = $this->common->getChannel();
		$server = $this->common->getServer();
		$version = $this->common->getVersion();
		$fromTime = $this->common->getFromTime();
		$toTime = $this->common->getToTime();

		$propid = $_GET['propid'];
		$activity_issue = $_GET['activity_issue'];
		$detailstype = $_GET['detailstype'];

		$result = $this->newserveractivity->getDistributeddetails($fromTime,$toTime,$channel,$server,$version,$propid,$activity_issue,$detailstype);
		echo json_encode($result);
	}
}
