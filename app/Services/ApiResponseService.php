<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response as FoundationResponse;
use Illuminate\Support\Facades\Response;

trait ApiResponseService
{
    /**
     * @var int
     */
    protected $statusCode = FoundationResponse::HTTP_OK;

    /**
     * @return mixed
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param $statusCode
     * @return $this
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    /**
     * @param $data
     * @param array $header
     * @return mixed
     */
    public function respond($data, $header = [])
    {
        return Response::json($data, $this->getStatusCode(), $header);
    }

    /**
     * @param array $data
     * @param null $code
     * @return mixed
     */
    public function code(array $data, $code = null)
    {
        if ($code) {
            $this->setStatusCode($code);
        }

        $code = [
            'code' => $this->statusCode,
        ];

        $data = array_merge($code, $data);

        return $this->respond($data);
    }

    /**
     * @param $message
     * @param int $code
     * @return mixed
     */
    public function failed(
        $message,
        $code = FoundationResponse::HTTP_BAD_REQUEST
    ) {
        return $this->setStatusCode($code)->message($message);
    }

    /**
     * @param $message
     * @return mixed
     */
    public function message($message)
    {
        return $this->code([
            'message' => $message
        ]);
    }

    /**
     * @param string $message
     * @return mixed
     */
    public function internalError($message = "Internal Error!")
    {
        return $this->failed($message, FoundationResponse::HTTP_INTERNAL_SERVER_ERROR);
    }

    /**
     * @param string $message
     * @return mixed
     */
    public function created($message = "created")
    {
        return $this->setStatusCode(FoundationResponse::HTTP_CREATED)
            ->message($message);
    }

    /**
     * @param $data
     * @return mixed
     */
    public function success($data = 'success')
    {
        return $this->code(compact('data'));
    }

    /**
     * @param string $message
     * @return mixed
     */
    public function notFond($message = 'Not Fond!')
    {
        return $this->failed($message, Foundationresponse::HTTP_NOT_FOUND);
    }
}
