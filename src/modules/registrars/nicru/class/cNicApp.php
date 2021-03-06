<?php
/*
 * cNicApp.php - Класс cNicApp
 * Release Date: 26/05/2011                                              *
 * Version 1.0 
 * Author: Alexander Kostetsky
 * Email: finster.seele@gmail.com       
 */
/** 
 * Класс реализующий работу с API RU-CENTER
 * @author Alexander Kostetsky
 * 
 */
class cNicApp {
	/**
	 * Кодировка запросов и ответов API  
	 * @var string
	 */
	const sFrom = "KOI8-R";
	/**
	 * Кодировка в которой приходят данные в API
	 * @var string
	 */
	const sTo = "UTF-8";
	/**
	 * Флаг отладки
	 * @var bool
	 */
	private $bDebug = FALSE;
	/**
	 * Экземпляр класса HTTP_Request2
	 * @var HTTP_Request2
	 */
	protected $_httpClient;
	/**
	 * Статический экземпляр класса HTTP_Request2
	 * @var HTTP_Request2
	 */
	protected static $_staticHttpClient = null;
	/**
	 * Константа URL API
	 * @var string
	 */
	const sApiUrl = "https://www.nic.ru/dns/dealer";
	/**
	 * Константа название формы для работы с API
	 * @var string
	 */
	const sFormField = "SimpleRequest";
	 
	/**
	 * Инициализация класса и сохранение класса  HTTP_Request2
	 * @param HTTP_Request2 $client - экземпляр класса HTTP_Request2
	 * @param bool $bDebug - флаг отладки
	 */
	function __construct($client = null, $bDebug = FALSE) {
		$this->bDebug = $bDebug;
		$this->setHttpClient($client);
	}
	/**
	 * Возвращает экземпляр класса HTTP_Request2
	 * @return HTTP_Request2
	 */
 	public function getHttpClient() {
        return $this->_httpClient;
    }
    /**
     * Сохранияет или создает экземплярпы класса HTTP_Request2 
     * @param HTTP_Request2 $client
     * @return cNicApp
     */
    public function setHttpClient($client) {
        if ($client === null) {
            $client = new HTTP_Request2();
        }
        if (!$client instanceof HTTP_Request2) {
			 throw new Exception("Argument is not an instance of HTTP_Request2.");
        }
        $this->_httpClient = $client;
        self::setStaticHttpClient($client);
        return $this;
    }
    /**
     * Сораняет статический экземпляр класса HTTP_Request2
     * @param HTTP_Request2 $httpClient
     */
    public static function setStaticHttpClient(HTTP_Request2 $httpClient)
    {
        self::$_staticHttpClient = $httpClient;
    }
    
	/**
	 * Удаляльщик 
	 */
	function __destruct() {	} // eof __destruct
	/**
	 * Перегруженный метод __call
	 * 
	 * Обеспечивает обработку вызовов вида new<class_name>
	 * @param string $sMethod
	 * @param array $aArgs
	 */
	public function __call($sMethod, $aArgs){
		if (preg_match('/^new(\w+)/', $sMethod, $aMatches)){
			$sClass = $aMatches[1];
			$foundClassName = null;
 				try {
                     if (!class_exists($sClass, false)) {
              			include_once("iface/{$sClass}.php");
                     }
                     $foundClassName = $sClass;
                     
                 } catch (Exception $e) {
                     // package wasn't here- continue searching
                 }
            if ($foundClassName != null){
                $reflectionObj = new ReflectionClass($foundClassName);
                $instance = $reflectionObj->newInstanceArgs($aArgs);
                return $instance;
            } else {
                throw new Exception("Unable to find '${sClass}' in registered packages");
            }
        } else {
            throw new Exception("No such method ${sMethod}");
        }
	} // eof __call
	/**
	 * Получение ответа 
	 * @param array $aData
	 * @param string $className
	 * @return mixed 
	 */
	public function getQuery($aData, $className='Feed')
    {
        return $this->importUrl($aData, $className);
    } // eof getQuery
    
    /**
     * Возвращает статутс API
     * @param boolean $data - статус API
     * @todo 
     *  <ol>
     *  <li>Добавить логирование</li>
     *  </ol> 
     */
    public function getStatus($data){
    	$aStatus = array();
    	trim($data);
    	$aData = explode("\n",$data);
    	foreach(array_slice($aData, 0, 2) as $value){
    		if(empty($value)) continue;
    		$aTemp = explode(":", $value);
    		$aStatus[$aTemp[0]] = trim($aTemp[1]);
    	}
    	return ($aStatus["State"]=="200 OK")?false:true;
    }
    /**
     * Формирование запроса и обработка ответа. 
     * @param array $aData - входящие данные
     * @param sting $className - имя класса для маппинга данных
     * @return mixed 
     */
	public function importUrl($aData, $className='Feed') {
		$response = $this->get($aData);
        $QueryContent = $response->getBody();
        $QueryContent = iconv(self::sFrom,self::sTo,$QueryContent);
	    if($this->bDebug){
			ob_start();
			echo"\nQER:\n\n\n"; 
			var_dump($QueryContent); 
			echo "\n\n[eof]\n\n";
			$result = ob_get_clean();
	    	error_log($result, 0);
	    }
        if($this->getStatus($QueryContent)){
        	throw new Exception(print_r($QueryContent,true));
        	return null;
        }
        $feed = self::importString($QueryContent, $className);
        return $feed;
    } // eof importUrl
	/**
	 * Формирует запрос к API, выполянет его и получает ответ
	 * @param array $aData
	 * @return 
	 */
    public function get($aData)
    {
        return $this->performHttpRequest($this->prepareRequest($aData));
    } // eof get
    /**
     * Формирует запрос к API ( массив в строку ) 
     * @param unknown_type $aData
     */
	public function prepareRequest($aData){
		$sRequestData = "";
		$queryArray = array();
        foreach ($aData as $name => $value) {
    		if(is_array($value)) {
    			$queryArray[] = "\r\n[".$name."]\r\n";
    			foreach ($value as $sName => $sValue) {	
    				if(is_array($sValue)){
    					foreach ($sValue as $sSubValue) {
    						$queryArray[] = $sName.':'.$sSubValue."\r\n";
    					}
    				}else{
    					$queryArray[] = $sName.':'.$sValue."\r\n";
    				}
    			}
    		} else {
            	$queryArray[] = $name.':'.$value."\r\n";
    		}
    	}
        if (count($queryArray) > 0) {
            $sRequestData .= implode('', $queryArray);
        } else {
            $sRequestData .= '';
        }
        if($this->bDebug){ 
			ob_start();
			echo"\nRSP:\n\n\n"; 
			var_dump($sRequestData); 
			echo "\n\n[eof]\n\n";
			$result = ob_get_clean();
	    	error_log($result, 0);
        }
        return iconv(self::sTo,self::sFrom,$sRequestData);
	} // eof prepareRequest
	/**
	 * Выполняет запрос к API 
	 * @param string $sBody
	 * @return mixed 
	 */
	public function performHttpRequest($sBody)
    {
        $this->_httpClient->setHeader("Content-Type","application/x-www-form-urlencoded");
    	$this->_httpClient->addPostParameter(self::sFormField,$sBody);
    	try {
    		$response = $this->_httpClient->send();
    		if (200 == $response->getStatus()) {
        		return $response;
    		} else {
        		echo 'Unexpected HTTP status: ' . $response->getStatus() . ' ' .
             	$response->getReasonPhrase();
    		}
		} catch (HTTP_Request2_Exception $e) {
    		echo 'Error: ' . $e->getMessage();
		}
    	return $response;
    } // eof performHttpRequest
    /**
     * Разбор ответа в классы
     * @param string $string - строка ответа удаленного сервера
     * @param string $className - имя класса для маппинга данных
     */
	public static function importString($string, $className='Feed')
    {
        if (!class_exists($className, false)) {
          	include_once("iface/{$className}.php");
        }
	    $feed = new $className();
        $feed->transferFromString($string);
        return $feed;
    } // eof importString
    

}

?>