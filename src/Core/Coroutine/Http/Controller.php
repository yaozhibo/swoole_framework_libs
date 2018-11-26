<?php
/**
 * Created by PhpStorm.
 * User: 姚志博
 * Date: 2018/11/26
 * Time: 10:04
 */
namespace YSWoole\Core\Coroutine\Http;

use Illuminate\Foundation\Bus\DispatchesJobs;
use YSwoole\Core\Coroutine\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
}
