<?php

namespace App\Models;

use App\Services\ElasticSearchService;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class Translation extends Model
{
    /**
     * @var mixed
     */
    private $name = 'translation';

    /**
     * 插入文档
     *
     * @param string $originalText
     * @param string $requestIp
     * @param string $requestHeader
     * @return array|callable|mixed
     */
    public function insertRow(
        string $originalText,
        string $requestIp,
        string $requestHeader
    )
    {
        if ($originalText === '') {
            throw new RuntimeException('请传入翻译文本');
        }

        $data = [
            'original_text' => $originalText
        ];

        $requestIpResponse = json_decode(file_get_contents("https://api.ip.sb/geoip/{$requestIp}"), true);

        if (isset($requestIpResponse['code'])) {
            throw new RuntimeException("获取IP信息错误: " . $requestIpResponse['message']);
        }

        $requestIpInfo = $requestIpResponse;

        $data['request_ip'] = $requestIp;
        $data['request_ip_info'] = $requestIpInfo;
        $data['request_header'] = $requestHeader;

        $es = new ElasticSearchService();

        try {
            $id = md5($originalText);
        } catch (\Exception $e) {
            throw new RuntimeException($e->getMessage());
        }

        try {
            $getDocResult = $es->getDoc($this->name, $id);

            if ($getDocResult) {
                $data['updated_at'] = date('Y-m-d H:i:s');

                return $es->updateDoc($this->name, $id, $data);
            }
        } catch (Throwable $e) {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            return $es->addDoc($this->name, $id, $data);
        }

    }

    /**
     * @param string $originalText
     * @return array|callable
     */
    public function queryRow(string $originalText)
    {
        $body = [
            'query' => [
                'match' => [
                    'original_text' => $originalText
                ]
            ]
        ];

        $es = new ElasticSearchService();

        return $es->searchDoc($this->name, $body);
    }

    /**
     * @param $text
     * @return array|null
     */
    public function analyze($text): ?array
    {
        $es = new ElasticSearchService();

        $result = $es->analyze($text);

        if (empty($result['tokens'])) {
            return null;
        }

        return $result['tokens'];
    }
}
