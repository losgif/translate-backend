<?php

use App\Services\ElasticSearchService;
use Illuminate\Database\Migrations\Migration;

class CreateTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        $es = new ElasticSearchService();

        $body = [
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0
            ],
            'mappings' => [
                'properties' => [
                    'original_text' => [
                        'type' => 'text',
                        "analyzer" => "standard",
                        "search_analyzer" => "standard"
                    ],
                    'request_ip' => [
                        'type' => 'ip',
                        'index' => false
                    ],
                    'request_ip_info' => [
                        'type' => 'object',
                    ],
                    'request_header' => [
                        'type' => 'text',
                        'index' => false
                    ],
                    'created_at' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss',
                    ],
                    'updated_at' => [
                        'type' => 'date',
                        'format' => 'yyyy-MM-dd HH:mm:ss',
                    ]
                ]
            ]
        ];

        $es->createIndex('translation', $body);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $es = new ElasticSearchService();

        $es->deleteIndex('translation');
    }
}
