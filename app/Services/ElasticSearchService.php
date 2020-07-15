<?php

namespace App\Services;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

/**
 * ElasticSearch Service
 */
class ElasticSearchService
{
    /**
     * 连接地址
     *
     * @var array
     */
    public $hosts;

    /**
     * 连接客户端实例
     *
     * @var Client
     */
    public $client;

    /**
     * 初始化实例
     */
    public function __construct()
    {
        $host = config('elasticsearch.host');
        $port = config('elasticsearch.port');

        $this->hosts = [
            "{$host}:{$port}"
        ];

        $this->client = ClientBuilder::create()->setHosts($this->hosts)->build();
    }

    /**
     * 创建索引
     *
     * @param string $index
     * @param array $body
     * @return mixed
     */
    public function createIndex(string $index, array $body)
    {
        $parameters = [
            'index' => $index,
            'body'  => $body
        ];

        return $this->client->indices()->create($parameters);
    }

    /**
     * 删除索引
     *
     * @param string $index
     * @return mixed
     */
    public function deleteIndex(string $index)
    {
        $parameters = [
            'index' => $index
        ];

        return $this->client->indices()->delete($parameters);
    }

    /**
     * 创建映射
     *
     * @param string $index
     * @param array $body
     * @return mixed
     */
    public function createMapping(string $index, array $body)
    {
        $parameters = [
            'index' => $index,
            'body'  => $body
        ];

        return $this->client->indices()->putMapping($parameters);
    }

    /**
     * 获取映射
     *
     * @param string $index
     * @return mixed
     */
    public function getMapping(string $index)
    {
        $parameters = [
            'index' => $index,
        ];
        return $this->client->indices()->getMapping($parameters);
    }

    /**
     * 添加文档
     *
     * @param string $index
     * @param string $id
     * @param array $doc
     * @return mixed
     */
    public function addDoc(string $index, string $id, array $doc)
    {
        $parameters = [
            'index' => $index,
            'id'    => $id,
            'body'  => $doc
        ];

        return $this->client->index($parameters);
    }

    /**
     * 通过ID获取文档
     *
     * @param string $index
     * @param string $id
     * @param bool $isLazy
     * @return mixed
     */
    public function getDoc(string $index, string $id, bool $isLazy = false)
    {
        $parameters = [
            'index' => $index,
            'id' => $id
        ];

        if ($isLazy) {
            $parameters['client'] = [
                'future' => 'lazy'
            ];
        }

        return $this->client->get($parameters);
    }

    public function updateDoc(string $index, string $id, array $body)
    {
        $parameters = [
            'index' => $index,
            'id' => $id,
            'body' => [
                'doc' => $body
            ]
        ];

        return $this->client->update($parameters);
    }

    /**
     * 通过ID删除文档
     *
     * @param string $index
     * @param integer $id
     * @return mixed
     */
    public function deleteDoc(string $index, int $id)
    {
        $parameters = [
            'index' => $index,
            'id' => $id
        ];

        return $this->client->delete($parameters);
    }

    /**
     * 全文搜索文档
     *
     * @param string $index
     * @param array $body
     * @param boolean $isLazy
     * @param string $scroll
     * @return array|callable
     */
    public function searchDoc($index, array $body, bool $isLazy = false, $scroll = null)
    {
        $parameters = [
            'index' => $index,
            'body' => $body
        ];

        if ($isLazy) {
            $parameters['client'] = [
                'future' => 'lazy'
            ];
        }

        if ($scroll) {
            $parameters['scroll'] = $scroll;
        }

        return $this->client->search($parameters);
    }

    /**
     * 游标查询
     *
     * @param string $scrollId
     * @param string $scroll
     * @return mixed
     */
    public function scrollDoc(string $scrollId, string $scroll)
    {
        $parameters = [
            'scroll_id' => $scrollId,
            'scroll' => $scroll,
        ];

        return $this->client->scroll($parameters);
    }

    /**
     * 批量操作
     *
     * @param array $body
     * @return mixed
     */
    public function bulkDoc(array $body)
    {
        $parameters = [
            'body' => $body
        ];

        return $this->client->bulk($parameters);
    }

    public function analyze(string $text): array
    {
        $parameters = [
            'index' => 'translation',
            'body' => [
                'analyzer' => 'stop',
                'text' => $text
            ]
        ];

        return $this->client->indices()->analyze($parameters);
    }
}
