<?php

/**
 * Description of Vucem_Xml
 * 
 * Esta clase conforma arreglos en archivos XML requeridos por la Ventanilla Única de Comercio Exterior (VUCEM)
 *
 * @author Jaime E. Valdez jvaldezch at gmail
 */
class Vucem_Xml {

    protected $_firephp;
    protected $_dir;
    protected $_array;
    protected $_domtree;
    protected $_envelope;
    protected $_cadena;
    protected $_body;
    protected $_document;
    protected $_service;
    protected $_header;
    protected $_request;
    protected $_comprobante;
    protected $_firmaElectronica;
    protected $_xsl;
    protected $_preview = false;

    function get_dir() {
        return $this->_dir;
    }

    function set_dir($_dir) {
        $this->_dir = $_dir;
    }

    function get_preview() {
        return $this->_preview;
    }

    function set_preview($_preview) {
        $this->_preview = $_preview;
    }

    /**
     * 
     * Constructor, parametros obligatorios.
     * 
     * @param boolean $cove Si el XML va ser COVE
     * @param boolean $edoc Si el XML va ser EDOCUMENT
     */
    function __construct($cove = false, $edoc = false) {
        require_once "FirePHPCore/lib/FirePHP.class.php";
        $this->_firephp = FirePHP::getInstance(true);
        $this->_domtree = new DOMDocument("1.0", "UTF-8");
        $this->_domtree->formatOutput = true;
        if ($cove === true) {
            $this->_envelope = $this->_domtree->createElementNS("http://schemas.xmlsoap.org/soap/envelope/", "soapenv:Envelope");
            $this->_envelope->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:oxml", "http://www.ventanillaunica.gob.mx/cove/ws/oxml/");
            $this->_domtree->appendChild($this->_envelope);
        } elseif ($edoc === true) {
            $this->_envelope = $this->_domtree->createElementNS("http://schemas.xmlsoap.org/soap/envelope/", "soapenv:Envelope");
            $this->_envelope->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:dig", "http://www.ventanillaunica.gob.mx/aga/digitalizar/ws/oxml/DigitalizarDocumento");
            $this->_envelope->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:res", "http://www.ventanillaunica.gob.mx/common/ws/oxml/respuesta");
            $this->_domtree->appendChild($this->_envelope);
        }
        $this->_body = $this->_domtree->createElement("soapenv:Body");
        $this->_header = $this->_domtree->createElement("soapenv:Header");
        $this->_envelope->appendChild($this->_header);
        $this->_envelope->appendChild($this->_body);
        $this->_xsl = file_get_contents(APPLICATION_PATH . "/../library/Cove02.xsl");
    }

    /**
     * 
     * Genera un XML para genera un COVE.
     * 
     * @param array $data Arreglo de datos para hacer COVE
     * @param boolean $hideCredentials Enviar TRUE en caso de querer esconder las claves de acceso a VUCEM, Ej. caso de descarga de XML.
     * @return string
     * @throws Exception
     */
    public function xmlCove($data, $hideCredentials = false) {
        try {
            $this->_array = $data;
            if ($hideCredentials === false) {
                $this->_credenciales();
            }
            $this->_service = $this->_domtree->createElement("oxml:solicitarRecibirCoveServicio");
            $this->_body->appendChild($this->_service);
            $this->_comprobante = $this->_domtree->createElement("oxml:comprobantes");
            $this->_firmaElectronica = $this->_domtree->createElement("oxml:firmaElectronica");
            $this->_comprobante->appendChild($this->_firmaElectronica);
            $this->_service->appendChild($this->_comprobante);
            $this->_generalesCove();
            $this->_razonSocialDomicilio("emisor");
            $this->_razonSocialDomicilio("destinatario");
            $this->_mercancias();
            if ($this->_preview === false) {
                $this->_cadenaOriginal();
            }
        } catch (Exception $ex) {
            throw new Exception("Zend Exception found on <strong>" . __METHOD__ . "</strong> : " . $ex->getMessage());
        }
    }

    /**
     * 
     * Genera un XML para EDOCUMENT
     * 
     * @param type $data Arreglo de datos para hacer EDOCUMENT
     * @param boolean $hideCredentials Enviar TRUE en caso de querer esconder las claves de acceso a VUCEM, Ej. caso de descarga de XML.
     * @return type
     * @throws Exception
     */
    public function xmlEdocument($data, $hideCredentials = false) {
        try {
            $this->_array = $data;
            if ($hideCredentials === false) {
                $this->_credenciales();
            }
            $this->_service = $this->_domtree->createElement("dig:registroDigitalizarDocumentoServiceRequest");
            $this->_service->appendChild($this->_domtree->createElement("dig:correoElectronico", $this->_array["archivo"]["correoElectronico"]));
            $this->_document = $this->_domtree->createElement("dig:documento");
            $this->_service->appendChild($this->_document);
            $this->_request = $this->_domtree->createElement("dig:peticionBase");
            $this->_service->appendChild($this->_request);
            $this->_firmaElectronica = $this->_domtree->createElement("res:firmaElectronica");
            $this->_request->appendChild($this->_firmaElectronica);
            $this->_body->appendChild($this->_service);
            $this->_documento();
            $this->_cadena = "|{$this->_array["usuario"]["username"]}|{$this->_array["archivo"]["correoElectronico"]}|{$this->_array["archivo"]["idTipoDocumento"]}|{$this->_array["archivo"]["nombreDocumento"]}|{$this->_array["archivo"]["rfcConsulta"]}|{$this->_array["archivo"]["hash"]}|";
            $this->_cadenaOriginalManual("res");
        } catch (Exception $ex) {
            throw new Exception("Zend Exception found on <strong>" . __METHOD__ . "</strong> : " . $ex->getMessage());
        }
    }

    /**
     * 
     * Genera XML para consulta de un COVE
     * 
     * @param array $data Arreglo con datos para consulta
     * @param boolean $hideCredentials Enviar TRUE en caso de querer esconder las claves de acceso a VUCEM, Ej. caso de descarga de XML.
     * @return type
     * @throws Exception
     */
    public function xmlConsultaCove($data, $hideCredentials = false) {
        try {
            $this->_array = $data;
            if ($hideCredentials === false) {
                $this->_credenciales();
            }
            $this->_envelope->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:con", "http://www.ventanillaunica.gob.mx/ConsultarEdocument/");
            $this->_service = $this->_domtree->createElement("con:ConsultarEdocumentRequest");
            $this->_document = $this->_domtree->createElement("con:request");
            $this->_firmaElectronica = $this->_domtree->createElement("con:firmaElectronica");
            $this->_document->appendChild($this->_firmaElectronica);
            $search = $this->_domtree->createElement("con:criterioBusqueda");
            $search->appendChild($this->_domtree->createElement("con:eDocument", $this->_array["consulta"]["cove"]));
            $this->_document->appendChild($search);
            $this->_service->appendChild($this->_document);
            $this->_body->appendChild($this->_service);
            $this->_cadena = "|{$this->_array["usuario"]["username"]}|{$this->_array["consulta"]["cove"]}|";
            $this->_cadenaOriginalManual();
        } catch (Exception $ex) {
            throw new Exception("Zend Exception found on <strong>" . __METHOD__ . "</strong> : " . $ex->getMessage());
        }
    }

    /**
     * 
     * Genera XML para consulta de un EDOCUMENT
     * 
     * @param type $data Arreglo de datos
     * @param type $hideCredentials Enviar TRUE en caso de querer esconder las claves de acceso a VUCEM, Ej. caso de descarga de XML.
     * @throws Exception
     */
    public function xmlConsultaEdocument($data, $hideCredentials = false) {
        try {
            $this->_array = $data;
            if ($hideCredentials === false) {
                $this->_credenciales();
            }
            $this->_envelope->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:con", "http://www.ventanillaunica.gob.mx/ConsultarEdocument/");
            $this->_service = $this->_domtree->createElement("con:ConsultarEdocumentRequest");
            $this->_document = $this->_domtree->createElement("con:request");

            $this->_firmaElectronica = $this->_domtree->createElement("con:firmaElectronica");
            $this->_document->appendChild($this->_firmaElectronica);

            $search = $this->_domtree->createElement("con:criterioBusqueda");
            $search->appendChild($this->_domtree->createElement("con:eDocument", $this->_array["consulta"]["edocument"]));
            $this->_document->appendChild($search);

            $this->_service->appendChild($this->_document);
            $this->_body->appendChild($this->_service);
            $this->_cadena = "|{$this->_array["usuario"]["username"]}|{$this->_array["consulta"]["edocument"]}|";
            $this->_cadenaOriginalManual();
        } catch (Exception $ex) {
            throw new Exception("Zend Exception found on <strong>" . __METHOD__ . "</strong> : " . $ex->getMessage());
        }
    }

    /**
     * 
     * Genera XML para consulta de una operación realizada en VUCEM
     * 
     * @param type $data Arreglo de datos
     * @param type $hideCredentials Enviar TRUE en caso de querer esconder las claves de acceso a VUCEM, Ej. caso de descarga de XML.
     * @throws Exception
     */
    public function consultaEstatusOperacionCove($data, $hideCredentials = false) {
        try {
            $this->_array = $data;
            if ($hideCredentials === false) {
                $this->_credenciales();
            }
            $this->_service = $this->_domtree->createElement("oxml:solicitarConsultarRespuestaCoveServicio");
            $this->_service->appendChild($this->_domtree->createElement("oxml:numeroOperacion", $this->_array["consulta"]["operacion"]));
            $this->_body->appendChild($this->_service);
            $this->_firmaElectronica = $this->_domtree->createElement("oxml:firmaElectronica");
            $this->_service->appendChild($this->_firmaElectronica);
            $this->_cadena = "|{$this->_array["consulta"]["operacion"]}|{$this->_array["usuario"]["username"]}|";
            $this->_cadenaOriginalManual();
        } catch (Exception $ex) {
            throw new Exception("Zend Exception found on <strong>" . __METHOD__ . "</strong> : " . $ex->getMessage());
        }
    }

    /**
     * 
     * Genera XML para consulta de una operación de Edocument realizada en VUCEM
     * 
     * @param type $data Arreglo de datos
     * @param type $hideCredentials Enviar TRUE en caso de querer esconder las claves de acceso a VUCEM, Ej. caso de descarga de XML.
     * @throws Exception
     */
    public function consultaEstatusOperacionEdocument($data, $hideCredentials = false) {
        try {
            $this->_array = $data;
            if ($hideCredentials === false) {
                $this->_credenciales();
            }
            $this->_service = $this->_domtree->createElement("dig:consultaDigitalizarDocumentoServiceRequest");
            $this->_service->appendChild($this->_domtree->createElement("dig:numeroOperacion", $this->_array["consulta"]["operacion"]));
            $this->_body->appendChild($this->_service);
            $peticionBase = $this->_domtree->createElement("dig:peticionBase");
            $this->_service->appendChild($peticionBase);
            $this->_firmaElectronica = $this->_domtree->createElement("res:firmaElectronica");
            $peticionBase->appendChild($this->_firmaElectronica);
            $this->_cadena = "|{$this->_array["usuario"]["username"]}|{$this->_array["consulta"]["operacion"]}|";
            $this->_cadenaOriginalManual("res");
        } catch (Exception $ex) {
            throw new Exception("Zend Exception found on <strong>" . __METHOD__ . "</strong> : " . $ex->getMessage());
        }
    }

    /**
     * 
     * Agrega los datos para la creación del XML de Edocument
     * 
     * @return type
     */
    protected function _documento() {
        try {
            $this->_document->appendChild($this->_domtree->createElement("dig:idTipoDocumento", $this->_array["archivo"]["idTipoDocumento"]));
            $this->_document->appendChild($this->_domtree->createElement("dig:nombreDocumento", $this->_array["archivo"]["nombreDocumento"]));
            $this->_document->appendChild($this->_domtree->createElement("dig:rfcConsulta", $this->_array["archivo"]["rfcConsulta"]));
            $this->_document->appendChild($this->_domtree->createElement("dig:archivo", $this->_array["archivo"]["archivo"]));
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 
     * Genera la firma digital necesaria para todas las operaciones de VUCEM
     * Notar: Si el tipo de sello es nuevo hace un cifrado OPENSSL_ALGO_SHA256
     * 
     * @param string $cadena
     * @return type
     */
    protected function _firma($cadena) {
        try {
            $firma = "";
            if ((boolean) $this->_array["usuario"]["new"] === true) {
                openssl_sign(html_entity_decode($cadena), $firma, $this->_array["usuario"]["key"], OPENSSL_ALGO_SHA256);
            } else {
                openssl_sign(html_entity_decode($cadena), $firma, $this->_array["usuario"]["key"]);
            }
            return base64_encode($firma);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 
     * Agrega los datos a la sección de firma digital
     * 
     * @return type
     */
    protected function _cadenaOriginal() {
        try {
            $xml = str_replace(array("oxml:", "wsse:", "soapenv:", "dig:", "res:"), "", $this->_domtree->saveXML());
            $xslt = new XSLTProcessor();
            $xslt->importStylesheet(new SimpleXMLElement($this->_xsl));
            $cadena = rtrim(ltrim(str_replace(array("<html>", "</html>", "<br>", "\r\n", "\r", "\n", "Cadena Orignal :"), "", $xslt->transformToXml(new SimpleXMLElement($xml)))));
            if (isset($cadena)) {
                $this->_firmaElectronica->appendChild($this->_domtree->createElement("oxml:certificado", $this->_array["usuario"]["certificado"]));
                $this->_firmaElectronica->appendChild($this->_domtree->createElement("oxml:cadenaOriginal", $cadena));
                $this->_firmaElectronica->appendChild($this->_domtree->createElement("oxml:firma", $this->_firma($cadena)));
                return true;
            }
            return false;
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 
     * Para casos de EDOCUMENT consultas de operaciones e Edocuments se usa esta función en donde se genera la cadena manualmente
     * 
     * @return type
     */
    protected function _cadenaOriginalManual($namespace = null) {
        try {
            if (!isset($namespace)) {
                $namespace = "oxml";
            }
            $this->_firmaElectronica->appendChild($this->_domtree->createElement("{$namespace}:certificado", $this->_array["usuario"]["certificado"]));
            $this->_firmaElectronica->appendChild($this->_domtree->createElement("{$namespace}:cadenaOriginal", $this->_cadena));
            $this->_firmaElectronica->appendChild($this->_domtree->createElement("{$namespace}:firma", $this->_firma($this->_cadena)));
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 
     * Agrega las credenciales de acceso a VUCEM, username es RFC del sello y el password de Web Service
     * 
     * @return type
     */
    protected function _credenciales() {
        try {
            $security = $this->_domtree->createElement("wsse:Security");
            $security->setAttributeNS("http://www.w3.org/2000/xmlns/", "xmlns:wsse", "http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd");
            $username = $this->_domtree->createElement("wsse:UsernameToken");
            $username->appendChild($this->_domtree->createElement("wsse:Username", $this->_array["usuario"]["username"]));
            $username->appendChild($this->_domtree->createElement("wsse:Password", $this->_array["usuario"]["password"]));
            $security->appendChild($username);
            $this->_header->appendChild($security);
        } catch (Exception $ex) {
            return $ex->getMessage();
        }
    }

    /**
     * 
     * Conforma los datos necesarios generales del COVE
     * 
     */
    protected function _generalesCove() {
        $keys = array("e-document", "tipoOperacion", "patenteAduanal", "fechaExpedicion", "tipoFigura", "correoElectronico", "observaciones", "numeroFacturaOriginal");
        foreach ($keys as $key) {
            if (isset($this->_array["factura"][$key])) {
                $this->_comprobante->appendChild($this->_domtree->createElement("oxml:{$key}", $this->_encodeChar($this->_array["factura"][$key])));
            }
        }
        $factura = $this->_domtree->createElement("oxml:factura");
        $factura->appendChild($this->_domtree->createElement("oxml:certificadoOrigen", isset($this->_array["factura"]["certificadoOrigen"]) ? 1 : 0));
        $factura->appendChild($this->_domtree->createElement("oxml:subdivision", isset($this->_array["factura"]["subdivision"]) ? 1 : 0));
        if (isset($this->_array["factura"]["rfcConsulta"])) {
            if (is_array($this->_array["factura"]["rfcConsulta"])) {
                foreach ($this->_array["factura"]["rfcConsulta"] as $rfc) {
                    $this->_comprobante->appendChild($this->_domtree->createElement("oxml:rfcConsulta", $this->_encodeChar($rfc)));
                }
            } else {
                $this->_comprobante->appendChild($this->_domtree->createElement("oxml:rfcConsulta", $this->_encodeChar($this->_array["factura"]["rfcConsulta"])));
            }
        }
        $this->_comprobante->appendChild($factura);
    }

    /**
     * 
     * Genera los datos necesarios para Emisor/Destinatario en COVE
     * 
     * @param type $namespace
     */
    protected function _razonSocialDomicilio($namespace) {
        if (isset($this->_array[$namespace])) {
            $element = $this->_domtree->createElement("oxml:{$namespace}");
            $iden = array("tipoIdentificador", "identificacion", "nombre");
            foreach ($iden as $ide) {
                if (isset($this->_array[$namespace][$ide])) {
                    $element->appendChild($this->_domtree->createElement("oxml:{$ide}", $this->_encodeChar($this->_array[$namespace][$ide])));
                }
            }
            $domicilio = $this->_domtree->createElement("oxml:domicilio");
            $domi = array("calle", "numeroExterior", "numeroInterior", "colonia", "localidad", "municipio", "entidadFederativa", "codigoPostal", "pais");
            foreach ($domi as $dom) {
                if (isset($this->_array[$namespace][$dom]) && $this->_array[$namespace][$dom] != "") {
                    $domicilio->appendChild($this->_domtree->createElement("oxml:{$dom}", $this->_encodeChar($this->_array[$namespace][$dom])));
                }
            }
            $element->appendChild($domicilio);
            $this->_comprobante->appendChild($element);
        }
    }

    /**
     * 
     * Agrega las mercancias o productos al XML del COVE.
     * 
     */
    protected function _mercancias() {
        if (isset($this->_array["mercancias"])) {
            $keys = array("descripcionGenerica", "numParte", "secuencial", "claveUnidadMedida", "tipoMoneda", "cantidad", "valorUnitario", "valorTotal", "valorDolares");
            foreach ($this->_array["mercancias"] as $item) {
                $element = $this->_domtree->createElement("oxml:mercancias");
                foreach ($keys as $key) {
                    if (isset($item[$key])) {
                        $element->appendChild($this->_domtree->createElement("oxml:{$key}", $this->_encodeChar($item[$key])));
                    }
                }
                $this->_comprobante->appendChild($element);
            }
        }
    }

    /**
     * 
     * Codifica los caracteres, esta funcion puede ser modificada de acuerdo con el tipo de codificación que maneje la aplicación
     * 
     * @param string $value
     * @return type
     */
    protected function _encodeChar($value) {
        return htmlentities(trim($value));
    }

    /**
     * Regresa valores Valores permitidos [0-TAX_ID, 1-RFC, 2-CURP,3-SIN_TAX_ID]
     * 
     * @param string $rfc
     * @param string $pais
     * @return string
     */
    public function tipoIdentificador($rfc, $pais) {
        $regRfc = "/^[A-Z]{3,4}([0-9]{2})(1[0-2]|0[1-9])([0-3][0-9])([A-Z0-9]{3,4})$/";
        if (($pais == "MEX" || $pais == "MEXICO") && preg_match($regRfc, str_replace(" ", "", trim($rfc)))) {
            if ($rfc != "EXTR920901TS4") {
                if (strlen($rfc) > 12) {
                    return "2";
                }
                return "1";
            } else {
                return "0";
            }
        }
        if (($pais == "MEX" || $pais == "MEXICO") && !preg_match($regRfc, str_replace(" ", "", trim($rfc)))) {
            return "0";
        }
        if ($pais != "MEX" && trim($rfc) != "") {
            return "0";
        }
        if ($pais != "MEX" && trim($rfc) == "") {
            return "3";
        }
    }

    /**
     * 
     * Reemplaza namespaces no necesarios para hacer parsing en PHP del XML resultante.
     * 
     * @param string $string
     * @return string
     * @throws Exception
     */
    public function replace($string) {
        try {
            return str_replace(array("S:", "soapenv:", "oxml:", "con:", "wsse:", "wsu:", "env:"), "", $string);
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Regresa el XML
     * 
     * @return string
     * @throws Exception
     */
    public function getXml() {
        try {
            return (string) $this->_domtree->saveXML();
        } catch (Exception $e) {
            throw new Exception("Exception on " . __METHOD__ . ": " . $e->getMessage());
        }
    }

    /**
     * 
     * Guarda el XML en disco.
     * 
     * @return type
     * @throws Exception
     */
    public function saveToDisk($type) {
        try {
            if ($this->get_dir() !== null) {
                if (file_exists($this->get_dir())) {
                    $this->_domtree->save($this->get_dir() . DIRECTORY_SEPARATOR . $type . "_" . sha1((string) $this->_domtree->saveXML()) . ".xml");
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
