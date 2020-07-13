<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TranslationGenerate extends Model
{
    use SoftDeletes;

    /**
     * @param $value
     * @return mixed
     */
    protected function getWordsAttribute($value): array
    {
        return json_decode($value, true);
    }

    /**
     * @param $value
     */
    protected function setWordsAttribute($value): void
    {
        $this->attributes['words'] = json_encode($value);
    }
}
