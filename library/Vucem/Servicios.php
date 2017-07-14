<?php

/**
 * Description of Vucem_Servicios
 * 
 * Esta clase hace la gestión para enviar un XML a VUCEM, puede 
 * ser omitida si se usa otro mecanismo para comunicarse con VUCEM.
 * 
 * Importante: VUCEM en horas pico tiende a tardar mucho en responder, por lo que se estima un maximo
 * de 400 segundos para timeout, modificar en caso necesario, pero en caso necesario
 * debe ser modificado el php.ini
 * 
 * URL"s: los URL o Endpoints de VUCEM pueden cambiar
 *
 * @author Jaime E. Valdez jvaldezch at gmail
 */
class Vucem_Servicios {

    protected $coveEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla/RecibirCoveService";
    protected $edocumentEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService";
    protected $respuestaEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla/ConsultarRespuestaCoveService";
    protected $respuestaEdocEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla/DigitalizarDocumentoService";
    protected $pedimentoEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla-ws-pedimentos/ConsultarPedimentoCompletoService";
    protected $partidaEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla-ws-pedimentos/ConsultarPartidaService";
    protected $consultaEndpoint = "https://www.ventanillaunica.gob.mx/ventanilla/ConsultarEdocumentService";
    protected $aduana;
    protected $patente;
    protected $pedimento;
    protected $xml;
    protected $response;
    protected $dir;

    function getXml() {
        return $this->xml;
    }

    function setXml($xml) {
        $this->xml = $xml;
    }

    function getResponse() {
        return $this->response;
    }

    function setDir($dir) {
        $this->dir = $dir;
    }

    function getDir() {
        return $this->dir;
    }

    function getAduana() {
        return $this->aduana;
    }

    function getPatente() {
        return $this->patente;
    }

    function getPedimento() {
        return $this->pedimento;
    }

    function setAduana($aduana) {
        $this->aduana = $aduana;
    }

    function setPatente($patente) {
        $this->patente = $patente;
    }

    function setPedimento($pedimento) {
        $this->pedimento = $pedimento;
    }

    /**
     * 
     */
    function __construct() {
        require_once "FirePHPCore/lib/FirePHP.class.php";
        $this->_firephp = FirePHP::getInstance(true);
    }

    /**
     * 
     * Envia un COVE en formato XML
     * Se requiere variable $xml
     * 
     * @param string $xml
     * @param string $service
     * @return type
     * @throws Exception
     */
    public function consumirServicioCove() {
        try {
            $headers = array(
                "Content-type: text/xml; charset=UTF-8",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Content-length: " . strlen($this->xml) . "");
            $soap = curl_init();
            curl_setopt($soap, CURLOPT_URL, $this->coveEndpoint);
            curl_setopt($soap, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($soap, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($soap, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($soap, CURLOPT_CONNECTTIMEOUT ,0); 
            curl_setopt($soap, CURLOPT_TIMEOUT, 400);
            curl_setopt($soap, CURLOPT_POST, true);
            curl_setopt($soap, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($soap, CURLOPT_POSTFIELDS, $this->xml);
            $result = curl_exec($soap);
            curl_close($soap);
            $this->response = $result;
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Consulta de estatus de COVE
     * Se requiere variable $xml
     * 
     * @throws Exception
     */
    public function consultaEstatusCove() {
        try {
            $headers = array(
                "Content-type: text/xml; charset=UTF-8",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Content-length: " . strlen($this->xml) . "");
            $soap = curl_init();
            curl_setopt($soap, CURLOPT_URL, $this->respuestaEndpoint);
            curl_setopt($soap, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($soap, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($soap, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($soap, CURLOPT_CONNECTTIMEOUT ,0); 
            curl_setopt($soap, CURLOPT_TIMEOUT, 400);
            curl_setopt($soap, CURLOPT_POST, true);
            curl_setopt($soap, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($soap, CURLOPT_POSTFIELDS, $this->xml);
            $result = curl_exec($soap);
            curl_close($soap);
            $this->response = $result;
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Consulta de estatus de Edocument
     * Se requiere variable $xml
     * 
     * @throws Exception
     */
    public function consultaEstatusEdocument() {
        try {
            $this->_consumirServicio($this->edocumentEndpoint);
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Consulta de operacion de Edocument
     * Se requiere variable $xml
     * 
     * @throws Exception
     */
    public function consumirServicioEdocument() {
        try {
            $this->_consumirServicio($this->respuestaEdocEndpoint);
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Descarga pedimento
     * Se requiere variable $xml
     * 
     * @throws Exception
     */
    public function consumirPedimento() {
        try {
            $this->_consumirServicio($this->pedimentoEndpoint);
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Descarga partida
     * Se requiere variable $xml
     * 
     * @throws Exception
     */
    public function consumirPartida() {
        try {
            $this->_consumirServicio($this->partidaEndpoint);
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Descarga un Edocument
     * Se requiere variable $xml
     * 
     * @throws Exception
     */
    public function consultaEdocument() {
        try {
            $this->_consumirServicio($this->consultaEndpoint);
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Función base para Curl
     * 
     * @param type $endpoint
     * @throws Exception
     */
    public function _consumirServicio($endpoint) {
        try {
            $headers = array(
                "Content-type: text/xml; charset=UTF-8",
                "Accept: text/xml",
                "Cache-Control: no-cache",
                "Pragma: no-cache",
                "Content-length: " . strlen($this->xml) . "");
            $soap = curl_init();
            curl_setopt($soap, CURLOPT_URL, $endpoint);
            curl_setopt($soap, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($soap, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($soap, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($soap, CURLOPT_CONNECTTIMEOUT ,0); 
            curl_setopt($soap, CURLOPT_TIMEOUT, 600); 
            curl_setopt($soap, CURLOPT_POST, true);
            curl_setopt($soap, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($soap, CURLOPT_POSTFIELDS, $this->xml);
            $result = curl_exec($soap);
            curl_close($soap);
            $this->response = $result;
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Guarda en disco la respuesta de VUCEM
     * 
     * @param string $filename
     * @throws Exception
     */
    public function saveToDisk($filename) {
        try {
            if ($this->getDir() !== null) {
                if (file_exists($this->getDir())) {
                    $newDir = $this->getDir() . DIRECTORY_SEPARATOR . $this->patente . DIRECTORY_SEPARATOR . $this->aduana . DIRECTORY_SEPARATOR . $this->pedimento;
                    if (!file_exists($newDir)) {
                        mkdir($newDir, 0777, true);
                    }
                    file_put_contents($newDir . DIRECTORY_SEPARATOR . $filename . ".xml", $this->getResponse());
                } else {
                    throw new Exception("Directory do not exists.");
                }
            } else {
                throw new Exception("Directory is not set.");
            }
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

}
