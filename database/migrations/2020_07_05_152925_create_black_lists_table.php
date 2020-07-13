<?php

use App\Services\ElasticSearchService;
use Illuminate\Database\Migrations\Migration;

class CreateBlackListsTable extends Migration
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
                    'word' => [
                        'type' => 'text',
                        "analyzer" => "standard",
                        "search_analyzer" => "standard"
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

        $es->createIndex('black_lists', $body);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $es = new ElasticSearchService();

        $es->deleteIndex('black_lists');
    }
}
