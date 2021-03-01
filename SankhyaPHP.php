<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);


class SankhyaPHP {

  const XML_LOGIN = "PHNlcnZpY2VSZXF1ZXN0IHNlcnZpY2VOYW1lPSJNb2JpbGVMb2dpblNQLmxvZ2luIj4KPHJlcXVlc3RCb2R5Pgo8Tk9NVVNVPiVVU0VSJTwvTk9NVVNVPgo8SU5URVJOTz4lUEFTUyU8L0lOVEVSTk8+CjwvcmVxdWVzdEJvZHk+Cjwvc2VydmljZVJlcXVlc3Q+";
  const JSON_QUERY = "ewogICJzZXJ2aWNlTmFtZSI6ICJEYkV4cGxvcmVyU1AuZXhlY3V0ZVF1ZXJ5IiwKICAicmVxdWVzdEJvZHkiOiB7CiAgICAic3FsIjogIiVRVUVSWSUiCiAgfQp9";

  private $ipProducao;
  private $idLogin;
  private $user;
  private $pass;
  private $lastError;

  function __construct($host, $user, $pass){

    $this->ipProducao = $host;
    $this->user = $user;
    $this->pass = $pass;
  }

  public function getLastError(){
    return $this->lastError;
  }

  private function getXML($xmlStr){
    return simplexml_load_string($xmlStr);
  }

  public function login(){

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "http://".$this->ipProducao."/mge/service.sbr?serviceName=MobileLoginSP.login");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, str_replace(array("%USER%", "%PASS%"), array($this->user, $this->pass), base64_decode(self::XML_LOGIN)));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml'));

    $result=curl_exec($ch);

    if(!$result){
      $this->lastError = "Ocorreu um erro. Por favor, verifique seus parâmetros de configuração.";
      return false;
    }

    $dados = $this->getXML($result);

    if(isset($dados->responseBody) && $dados->attributes()->status == 1){

      $this->idLogin = $dados->responseBody->jsessionid;
      return true;
    }
    else if($dados->attributes()->status == 0){
      $this->lastError = "Credenciais inválidas. Verifique seu usuário e senha.";
    }
  }

  public function logout(){

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "http://".$this->ipProducao."/mge/service.sbr?serviceName=MobileLoginSP.logout");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/xml', "Cookie: JSESSIONID=".$this->idLogin));

    $result=curl_exec($ch);
    $dados = $this->getXML($result);

    if($dados->attributes()->status == 1){
      return true;
    }
    else if($dados->attributes()->status == 0){
      return false;
    }
  }

  public function query($query){

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, "http://".$this->ipProducao."/mge/service.sbr?serviceName=DbExplorerSP.executeQuery");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, str_replace("%QUERY%", $query, base64_decode(self::JSON_QUERY)));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', "Cookie: JSESSIONID=".$this->idLogin));

    $result = curl_exec($ch);

    if(!$result){
      $this->lastError = "Ocorreu um erro. Por favor, verifique sua query.";
      return false;
    }


    $result = utf8_encode($result);
    $res = str_replace("null", "\"\"", $result);
    $res = preg_replace("/\s+\"/", "\"", $res);
    $res = preg_replace("/N.O/", "NÃO", $res);
    $res = preg_replace("/(\t\n)/", "", $res);

    $dados = json_decode($res, true);

    if(!$dados){
      $dados = $this->getXML($res);
      $this->lastError = utf8_encode(base64_decode($dados->statusMessage));
      return false;
    }
    else{
      $dadosCols = [];
      foreach($dados['responseBody']['fieldsMetadata'] as $key){
        $dadosCols[] = $key['name'];
      }

      $dadosOut = [];

      foreach($dados['responseBody']['rows'] as $key=>$val){
        foreach($val as $k=>$v){
          $dadosOut[$key][$dadosCols[$k]] = $v;
        }
      }

      return array("numRows"=>count($dadosOut), "rows"=>$dadosOut);
    }
  }
}

?>
