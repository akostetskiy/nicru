<?php
include_once("cNicRequests.php");

/** 
 * @author alexkost
 * 
 * 
 */
class cContract extends cNicRequests {
	const sFeed = "ContractFeed";
	const sDataBlock = 'contract';
	var $_aParam = array();

	/**
	 * 
	 */
	public function __construct() {
		
		$this->_params["request"]="contract";
		parent::__construct();
	}
	
	/**
	 * 
	 */
	function __destruct() {
		
	}
	private function Create(){
		$this->_params["operation"]="create";
	}
	/**
	 * �������� ������ ������� ��� ��������������� ���������������  (��)
	 * @param $aData
	 */
	public function CreatePbul($aData = array()){
		$this->_aParam['contract-type']='PRS';
		$this->Create();
	} // eof CreatePbul
	/**
	 * �������� ������ ������� ��� ������������ ����
	 */
	public function CreateOrg($aData = array()){
		$this->_aParam['contract-type']='ORG';
		$this->Create();
	} // eof CreateOrg
	/**
	 * �������� ������ ������� ��� ����������� ����
	 * @param unknown_type $aData
	 */
	public function CreatePrs($aData = array()){
		$this->_aParam['contract-type']='PRS';
		$this->Create();
	} // eof CreatePrs
	public function getQueryData()
    {
    	$aParam = array();
		$aParam = array_merge($aParam, $this->getQueryString());
		$aParam[self::sDataBlock] = $this->_aParam;
		return $aParam;
    }
    
	public function sGetFeed(){
		return self::sFeed;
	}
}

?>