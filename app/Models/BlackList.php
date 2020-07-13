<?php

namespace App\Models;

use App\Services\ElasticSearchService;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;
use Throwable;

class BlackList extends Model
{
    private $name = 'black_lists';

    public function insertRow(
        array $data
    )
    {
        $es = new ElasticSearchService();

        try {
            $id = $data['word'];
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

    public function queryRow($word)
    {
        $body = [
            'query' => [
                'match' => [
                    'word' => $word
                ]
            ]
        ];

        $es = new ElasticSearchService();

        return $es->searchDoc($this->name, $body);
    }
}
