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
}