<?php
/**
 * ResultHelper
 * @author Cogie
 *
 */
namespace YSwoole\Utils;

use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Response;

class ResultHelper{
    /**
     * 下载文件
     * @param string $path
     * @param string $filename
     */
    public static function file($path,$filename){
        return Response::download($path,$filename,['Content-type'=>'aplication/octet-stream']);
    }

    /**
     * ajax返回
     * @param $data
     * @param $info
     * @param $status
     * @param null $debug
     * @return \Illuminate\Http\JsonResponse
     */
    public static function json($data, $info, $status, $debug = null) {
        $result = [
            'data' => self::ksort($data),
            'info' => $info,
            'status' => $status,
        ];
        if (config('app.debug') && $debug) {
            $result['debug'] = $debug;
        }
    	return Response::json($result, 200, [], 256);
    }
    
    /**
     * api数据返回
     * @param mixed $data
     * @param string $info
     * @param int $status
     */
    public static function api($data, $info, $status){
        $format=Input::get('format','json');
        if (is_object($data)){
            if(method_exists($data,'toArray'))
                $data=$data->toArray();
            else
                $data=get_object_vars($data);
        }
        if($format=='xml'){
            $xml=self::xml_encode(array('data'=>$data, 'info' => $info, 'status' => $status),'MSTResponse');
            header("Content-type:text/xml");
            echo $xml;
            exit;
            return ;
        }else{
            $json=json_encode(array('data'=>self::ksort($data), 'info' => $info, 'status' => $status),JSON_UNESCAPED_UNICODE);
            header("Content-type:application/json");
            echo $json;
            exit;
            return ;
        }
    }
    
    private static function & ksort(&$array){
    	if(is_array($array)){
	    	ksort($array);
	    	
	    	foreach ($array as &$value){
	    		if(is_array($value)){
	    			ksort($value);
	    		}
	    	}
    	}
    	return $array;
    }
    
    private static function xml_encode($data, $root = 'so') {
        $xml = '<?xml version="1.0" encoding="utf-8"?>';
        $xml .= "<{$root}>";
        $xml .= self::array_to_xml($data);
        $xml .= "</{$root}>";
        return $xml;
    }
    
    private static function xml_decode($xml, $root = 'so') {
        $search = '/<(' . $root . ')>(.*)<\/\s*?\\1\s*?>/s';
        $array = array();
        $matches=array();
        if(preg_match($search, $xml, $matches)){
            $array = self::xml_to_array($matches[2]);
        }
        return $array;
    }
    
    private static function array_to_xml($array) {
        if(is_object($array)){
            $array = get_object_vars($array);
        }
        $xml = '';
        foreach($array as $key => $value){
            $_tag = $key;
            $_id = null;
            if(is_numeric($key)){
                $_tag = 'item';
                $_id = ' id="' . $key . '"';
            }
            $xml .= "<{$_tag}{$_id}>";
            $xml .= (is_array($value) || is_object($value)) ? self::array_to_xml($value) : htmlentities($value);
            $xml .= "</{$_tag}>";
        }
        return $xml;
    }
    
    private static function xml_to_array($xml) {
        $search = '/<(\w+)\s*?(?:[^\/>]*)\s*(?:\/>|>(.*?)<\/\s*?\\1\s*?>)/s';
        $array = array ();
        $matches=$_matches=array(); 
        if(preg_match_all($search, $xml, $matches)){
            foreach ($matches[1] as $i => $key) {
                $value = $matches[2][$i];
                if(preg_match_all($search, $value, $_matches)){
                    $array[$key] = self::xml_to_array($value);
                }else{
                    if('ITEM' == strtoupper($key)){
                        $array[] = html_entity_decode($value);
                    }else{
                        $array[$key] = html_entity_decode($value);
                    }
                }
            }
        }
        return $array;
    }
}