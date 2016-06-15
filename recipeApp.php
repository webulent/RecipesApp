<?php
/**
 * Created by IntelliJ IDEA.
 * User: bulent
 * Date: 12.04.2016
 * Time: 11:22
 */

class recipeApp extends pdoConnection
{
    public $con;
    public $data_type = 'json';
    public $post_data = null;

    public function __construct(){
        $this->con = $this->connect_to_pdo_new();
    }

    public function welcome(){
        return 'Hoşgeldiniz';
    }

    public function printJson($arr){
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
        header('Content-Type: application/json');
        echo json_encode($arr);
    }

    public function printXml($arr){
        header('Content-Type: text/html; charset=utf-8');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));
        header('Content-Type: application/xml');
        $arr = json_decode(json_encode($arr), true);
        echo $this->array_to_xml($arr, new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><root/>'))->asXML();
    }

    public function array_to_xml(array $arr, SimpleXMLElement $xml)
    {
        foreach ($arr as $k => $v) {
            is_array($v)
                ? $this->array_to_xml($v, $xml->addChild("item")) //item to $k
                : $xml->addChild($k, $v);
        }
        return $xml;
    }

    public function printMessage($arr, $error = 0)
    {
        if($this->data_type == 'json') {
            self::printJson($arr);
        }else if($this->data_type == 'xml'){
                self::printXml($arr);
        }else{
            print_r($arr);
        }

        if ($error == 1) {
            exit;
        }
    }
}