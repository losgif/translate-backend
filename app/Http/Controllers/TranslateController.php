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
            ]
        ]);

        if ($validator->fails()) {
            return $this->failed($validator->errors());
        }

        $translation = new Translation();

        $text = $request->input('text');
        $text = strip_tags($text);
        $text = preg_replace('/([\x80-\xff]*)/i', '', $text);

        $words = preg_replace('/[,.!()]/', '', $text);
        $words = explode(' ', $words);

        $words = Collect($words);

        $hasTranslateText = $translation->queryRow($text);

        if (isset($hasTranslateText['hits']['hits']) && !empty($hasTranslateText['hits']['hits']) && $hasTranslateText['hits']['max_score'] > count($words)) {
            return $this->failed('已翻译过类似文章');
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
                    continue;
                }

                $wordDetails[] = $source;
                unset($words[$index]);
            }
        }

        if ($words->count() !== 0) {
            $htmlResult = $this->multiRequest($words, 'http://dict.kekenet.com/en/');

            foreach ($htmlResult as $index => $html) {
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
            $text = preg_replace('/([\x80-\xff]*)/i', '', $text);

            $translation->insertRow($text, $request->ip(), $request->userAgent());

            $translationGenerate = new TranslationGenerate();
            $translationGenerate->words = $request->input('words');
            $translationGenerate->name = $request->input('name');
            $translationGenerate->desc = $request->input('desc');
            $translationGenerate->image = $request->input('image');
            $translationGenerate->original_text = $request->input('original_text');

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

    /**
     * @param $urls
     * @param $baseUrl
     * @return array
     */
    protected function multiRequest($urls, $baseUrl): array
    {
        $mh = curl_multi_init();
        $urlHandlers = [];
        $urlData = [];
        // 初始化多个请求句柄为一个
        foreach ($urls as $value) {
            $ch = curl_init();
            $url = $baseUrl . $value;
            $url .= strpos($url, '?') ? '&' : '?';
            curl_setopt($ch, CURLOPT_URL, $url);
            // 设置数据通过字符串返回，而不是直接输出
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            $urlHandlers[] = $ch;
            curl_multi_add_handle($mh, $ch);
        }
        $active = null;
        // 检测操作的初始状态是否OK，CURLM_CALL_MULTI_PERFORM为常量值-1
        do {
            // 返回的$active是活跃连接的数量，$mrc是返回值，正常为0，异常为-1
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        // 如果还有活动的请求，同时操作状态OK，CURLM_OK为常量值0
        while ($active && $mrc === CURLM_OK) {
            // 持续查询状态并不利于处理任务，每50ms检查一次，此时释放CPU，降低机器负载
            usleep(50000);
            // 如果批处理句柄OK，重复检查操作状态直至OK。select返回值异常时为-1，正常为1（因为只有1个批处理句柄）
            if (curl_multi_select($mh) !== -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc === CURLM_CALL_MULTI_PERFORM);
            }
        }
        // 获取返回结果
        foreach ($urlHandlers as $index => $ch) {
            $urlData[$index] = curl_multi_getcontent($ch);
            // 移除单个curl句柄
            curl_multi_remove_handle($mh, $ch);
        }
        curl_multi_close($mh);
        return $urlData;
    }
}
