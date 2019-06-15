<?php


namespace lkeme\BiliHelper\entity;


use Exception;
use lkeme\BiliHelper\exception\AuthException;
use lkeme\BiliHelper\Log;

class ReturnDto
{
    public $code;

    public $message;

    public $data;

    public $logs;

    public function __construct($code, $message, $data, $logs=null)
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
        $this->logs = $logs;
    }

    public static function success($data=null, $message='操作成功', $code=200){
        return new ReturnDto($code, $message, $data);
    }

    public static function error($message='操作失败', $code=500){
        return new ReturnDto($code, $message, null);
    }

    /**
     * 根据异常产生返回DTO
     * @param Exception $exception 异常
     * @return ReturnDto 返回DTO
     */
    public static function exception($exception){
        if($exception instanceof AuthException){
            return self::error($exception->getMessage(), 401);
        }
        return self::error($exception->getMessage());
    }

    public function toJson(): string {
        //如果logs为空，则设置为Log中积累的日志
        $this->logs = $this->logs == null? Log::$logs: $this->logs;
        return json_encode($this, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    public function __toString()
    {
       return $this->toJson();
    }
}