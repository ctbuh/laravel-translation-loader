<?php

namespace Spatie\TranslationLoader;

use Illuminate\Translation\Translator as BaseTranslator;

class Translator extends BaseTranslator
{
    protected $keys_used = array();

    public function getKeysUsed()
    {
        return $this->keys_used;
    }

    public function get($key, array $replace = [], $locale = null, $fallback = true)
    {
        $default = parent::get($key, $replace, $locale, $fallback);

        // not found?
        if ($default === $key) {
            // TODO: report missing!
            // event('translation.missing', 'hgfh');
        }

        if (!in_array($key, $this->keys_used)) {
            $this->keys_used[] = $key;
        }

        return $default;
    }
}
