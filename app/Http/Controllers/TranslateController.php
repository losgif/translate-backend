<?php

namespace App\Http\Controllers;

use App\Models\BlackList;
use App\Models\Translation;
use App\Models\TranslationGenerate;
use App\Models\Word;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Throwable;

class TranslateController extends Controller
{
    public function analyze(Request $request): JsonResponse
    {
        $validator = Validator::make($request->toArray(), [
            'text' => [
                'required'
            ],
            'force' => [
                'required'
            ]
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors());
        }

        $translation = new Translation();

        $text = $request->input('text');
        $text = strip_tags($text);
        $text = preg_replace('/([\x80-\xff]*)/i', '', $text);

        $words = preg_replace('/[,.!?()]/', '', $text);
        $words = explode(' ', $words);

        $words = Collect($words);

        $hasTranslateText = $translation->queryRow($text);

        if (isset($hasTranslateText['hits']['hits']) && !empty($hasTranslateText['hits']['hits']) && $hasTranslateText['hits']['max_score'] > count($words) && $request->input('force')) {
            return $this->failed('已翻译过类似文章', 403);
        }

        if ($words->isEmpty()) {
            return $this->failed('没有查询到单词');
        }

        $duplicates = $words->duplicates();

        if ($duplicates) {
            foreach ($duplicates as $key => $value) {
                unset($words[$key]);
            }
        }

        $wordModel = new Word();
        $blackList = new BlackList();

        $wordDetails = array();
        foreach ($words as $index => $word) {

            $isInBlackListResult = $blackList->queryRow($word);

            if (isset($isInBlackListResult['hits']['hits']) && !empty($isInBlackListResult['hits']['hits'])) {
                unset($words[$index]);
                continue;
            }

            $wordResult = $wordModel->queryRow($word);

            if (isset($wordResult['hits']['hits']) && !empty($wordResult['hits']['hits'])) {
                $source = $wordResult['hits']['hits'][0]['_source'];

                if ($source['voice'] === '' || $source['phonetic'] === '' || $source['english_chinese_interpretation'] === '') {
                    unset($words[$index]);
                }

                $wordDetails[] = $source;
                unset($words[$index]);
            } else {
                $html = file_get_contents('http://dict.kekenet.com/en/' . $words[$index]);
                $encoding = mb_detect_encoding($html, array("ASCII", "GB2312", "GBK", 'BIG5', "UTF-8"));
                $html = iconv($encoding, "UTF-8//TRANSLIT", $html);

                preg_match_all('/<input name="q" type="text" class="send" value="(.*?)" \/>/s', $html, $wordMatchResult);
                preg_match_all('/<div class="titWord">.*?<span style="font-size:16px;margin-right:5px;" class="phn">(.*?)<\/span>/s', $html, $phoneticMatchResult);
                preg_match_all('/<div class="titWord">.*?<a title="点击发音" onclick="asplay_h5\(\'(.*?)\'\);return false;" href="javascript:;">.*?<\/a>/us', $html, $voiceMatchResult);
                preg_match_all('/<h1 class="s_column">英汉解释<\/h1>.*?<ul class="s_ul">(.*?)<\/ul>/us', $html, $englishChineseInterpretationMatchResult);
                preg_match_all('/<h1 class="s_column">同义词<\/h1>.*?<ul class="s_ul">(.*?)<\/ul>/us', $html, $synonymsMatchResult);
                preg_match_all('/<h1 class="s_column">反义词<\/h1>.*?<ul class="s_ul">(.*?)<\/ul>/us', $html, $antonymsMatchResult);
                preg_match_all('/<h1 class="s_column">词汇辨析<\/h1>.*?<ul class="s_ul">(.*?)<\/ul>.*?<div style="line-height:180%">(.*?)<\/div>/us', $html, $vocabularyAnalysisMatchResult);
                preg_match_all('/<h1 class="s_column">参考例句<\/h1>.*?<ul id="s_ul">(.*?)<\/ul>/us', $html, $referenceExampleSentencesMatchResult);
                preg_match_all('/<h1 class="s_column">英英解释<\/h1>.*?<ol class="s_ul">(.*?)<\/ol>/us', $html, $englishInterpretationMatchResult);
                preg_match_all('/<h1 class="s_column">网络释义<\/h1>.*?<ul class="s_ul">(.*?)<\/ul>/us', $html, $webDefinitionsMatchResult);
                preg_match_all('/<h1 class="t_c_column">相关参考<\/h1>.*?<ul class="s_ul">(.*?)<\/ul>/us', $html, $relatedReferenceMatchResult);

                if (!isset($wordMatchResult[1][0], $phoneticMatchResult[1][0], $englishChineseInterpretationMatchResult[1][0])) {
                    continue;
                }

                $wordSaveData['word'] = $wordMatchResult[1][0];
                $wordSaveData['phonetic'] = $phoneticMatchResult[1][0] ?? '';
                $wordSaveData['voice'] = $voiceMatchResult[1][0] ?? '';
                $wordSaveData['english_chinese_interpretation'] = $englishChineseInterpretationMatchResult[1][0] ?? '';
                $wordSaveData['synonyms'] = $synonymsMatchResult[1][0] ?? '';
                $wordSaveData['antonyms'] = $antonymsMatchResult[1][0] ?? '';
                $wordSaveData['vocabulary_analysis'] = '<span class="vocabulary-analysis">' . ($vocabularyAnalysisMatchResult[1][0] ?? '') . '</span><br>' . ($vocabularyAnalysisMatchResult[2][0] ?? '');
                $wordSaveData['reference_example_sentences'] = $referenceExampleSentencesMatchResult[1][0] ?? '';
                $wordSaveData['english_interpretation'] = $englishInterpretationMatchResult[1][0] ?? '';
                $wordSaveData['web_definitions'] = $webDefinitionsMatchResult[1][0] ?? '';
                $wordSaveData['related_reference'] = $relatedReferenceMatchResult[1][0] ?? '';

                $wordModel->insertRow($wordSaveData);

                $wordDetails[] = $wordSaveData;
            }
        }

        return $this->success([
            'words' => $wordDetails,
            'text' => $text
        ]);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function insertWordToBlackList(Request $request): JsonResponse
    {
        $validator = Validator::make($request->toArray(), [
            'word' => [
                'required'
            ]
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors());
        }

        $blackList = new BlackList();
        $blackList->insertRow([
            'word' => $request->input('word')
        ]);

        return $this->success('拉黑成功');
    }

    /**
     * 保存生成文件数据
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function generate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->toArray(), [
            'words' => [
                'required'
            ],
            'name' => [
                'required'
            ],
            'desc' => [
                'required'
            ],
            'image' => [
                'required'
            ],
            'original_text' => [
                'required'
            ]
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors());
        }

        try {
            $translation = new Translation();

            $text = $request->input('original_text');
            $text = strip_tags($text);
            $translationText = preg_replace('/([\x80-\xff]*)/i', '', $text);

            $translation->insertRow($translationText, $request->ip(), $request->userAgent());

            $translationGenerate = new TranslationGenerate();
            $translationGenerate->words = $request->input('words');
            $translationGenerate->name = $request->input('name');
            $translationGenerate->desc = $request->input('desc');
            $translationGenerate->image = $request->input('image');
            $translationGenerate->original_text = $text;

            $translationGenerate->save();

            return $this->success(json_encode($translationGenerate->id));
        } catch (Throwable $e) {
            return $this->failed($e->getMessage());
        }
    }

    /**
     * @param Request $request
     * @param TranslationGenerate $translationGenerate
     * @return JsonResponse
     */
    public function generateGet(Request $request, TranslationGenerate $translationGenerate): JsonResponse
    {
        try {
            return $this->success($translationGenerate);
        } catch (Throwable $e) {
            return $this->failed($e->getMessage());
        }
    }
}
