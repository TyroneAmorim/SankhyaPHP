<?php

/**
*@author Heiter Developer <dev@heiterdeveloper.com>
*@link https://github.com/HeiterDeveloper/SankhyaPHP
*@copyright 2021 Heiter Developer
*@license Aapache License 2.0
*@license https://www.apache.org/licenses/LICENSE-2.0 Apache License 2.0
**/


class SankhyaPHP {

    private $endereco;
    private $idLogin;
    private $usuario;
    private $senha;
    private $error;
    
    function __construct($usuario, $senha, $endereco){
        
        $this->usuario = $usuario;
        $this->senha = $senha;
        $this->endereco = $endereco;
    }
    
    public function getError(){
        return $this->error;
    }

    public function getXML($xmlStr) {
        return simplexml_load_string($xmlStr);
    }
    
    public function getIdLogin(){
        return $this->idLogin;
    }

    /**
     * Envia uma requisição para API
     * @param type $path url do serviço
     * @param type $headers cabeçalhos http
     * @param type $body corpo da requisição
     * @return SimpleXMLElement
     */
    public function sendToServer($path, $headers, $body) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://" . $this->endereco . $path);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, (array) $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $body);

        $result = curl_exec($ch);

        return $result;
    }

    /**
     * Efetua login na API e guarda o token de sessão
     * @return boolean
     */
    public function login() {

        $xmlLogin = new SimpleXMLElement("<serviceRequest/>");

        $xmlLogin->addAttribute("serviceName", "MobileLoginSP.login");

        $reqBody = $xmlLogin->addChild("requestBody");
        $reqBody->addChild("NOMUSU", $this->usuario);
        $reqBody->addChild("INTERNO", $this->senha);
        $strXmlLogin = $xmlLogin->asXML();


        $dataXML = $this->getXML($this->sendToServer("/mge/service.sbr?serviceName=MobileLoginSP.login", "Content-Type: application/xml", $strXmlLogin));

        if (isset($dataXML->responseBody)) {
            $this->idLogin = $dataXML->responseBody->jsessionid;
            return true;
        } else {
            $this->error = base64_decode((string)$dataXML->statusMessage);
            return false;
        }
    }

    /**
     * Efetua logout na API
     * @return SimpleXMLElement
     */
    public function logout() {

        $data = $this->sendToServer("/mge/service.sbr?serviceName=MobileLoginSP.logout", array("Content-Type: application/xml", "Cookie: JSESSIONID=" . $this->idLogin), "");
        $dataXML = $this->getXML($data);
        return $dataXML;
    }
}

?>
