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
class Channelincome extends CI_Controller
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
		$this->load->model('channelanaly/channelincomemodel', 'channelincome');
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
		$fromTime = $this->common->getFromTime();
		$toTime = $this->common->getToTime();
		$channel = $this->common->getChannel();
		$server = $this->common->getServer();
		$version = $this->common->getVersion();

		$this->data['result'] = $this->channelincome->getChannelincomeData($fromTime, $toTime, $channel, $server, $version);

		$this->common->requireProduct();
		$this->common->loadHeaderWithDateControl(lang('m_quDaoShouRu_yao'));
		
		$ruteName = $this->router->fetch_class();
		$canRead = $this->common->isDisplay($ruteName);
		if($canRead == '1'){
			$this->load->view('channelanaly/channelincomeview',$this->data);
		}
		else{
			$this->load->view('forbidden');
		}
	}

	function echarts()
	{
		$fromTime = $this->common->getFromTime();
		$toTime = $this->common->getToTime();
		$channel = $this->common->getChannel();
		$server = $this->common->getServer();
		$version = $this->common->getVersion();

		$this->data['result'] = $this->channelincome->getChannelincomeData($fromTime, $toTime, $channel, $server, $version);
		$this->data['type'] = $_GET['type'];

		$this->load->view('layout/reportheader');
		$this->load->view('widgets/channelincome',$this->data);
	}
}
