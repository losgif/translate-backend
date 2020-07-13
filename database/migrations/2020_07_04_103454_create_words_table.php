<?php

use App\Services\ElasticSearchService;
use Illuminate\Database\Migrations\Migration;

class CreateWordsTable extends Migration
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
                    'phonetic' => [
                        'type' => 'text',
                        'index' => false
                    ],
                    'voice' => [
                        'type' => 'text',
                        'index' => false
                    ],
                    'english_chinese_interpretation' => [
                        'type' => 'text',
                    ],
                    'synonyms' => [
                        'type' => 'text',
                    ],
                    'antonyms' => [
                        'type' => 'text',
                    ],
                    'vocabulary_analysis' => [
                        'type' => 'text'
                    ],
                    'reference_example_sentences' => [
                        'type' => 'text'
                    ],
                    'english_interpretation' => [
                        'type' => 'text'
                    ],
                    'web_definitions' => [
                        'type' => 'text'
                    ],
                    'related_reference' => [
                        'type' => 'text'
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

        $es->createIndex('words', $body);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        $es = new ElasticSearchService();

        $es->deleteIndex('words');
    }
}
