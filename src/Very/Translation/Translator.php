<?php

namespace Very\Translation;

use Very\Support\Str;

class Translator
{
    /**
     * The default locale being used by the translator.
     *
     * @var string
     */
    protected $locale;

    /**
     * The array of loaded translation groups.
     *
     * @var array
     */
    protected $loaded = [];

    /**
     * Create a new translator instance.
     *
     * @param  string $locale
     */
    public function __construct($locale)
    {
        $this->locale = $locale;
    }

    /**
     * Get the translation for a given key.
     *
     * @param  string $key
     * @param  array  $replace
     * @param  string $locale
     *
     * @return string|array|null
     */
    public function trans($key, array $replace = [], $locale = null)
    {
        $locale = $locale ? $locale : $this->locale;
        $value  = config("lang.{$locale}.$key", $key);
        return $this->makeReplacements($value, $replace);
    }

    /**
     * Make the place-holder replacements on a line.
     *
     * @param  string $line
     * @param  array  $replace
     *
     * @return string
     */
    protected function makeReplacements($line, array $replace)
    {
        foreach ($replace as $key => $value) {
            $line = str_replace(
                [':' . $key, ':' . Str::upper($key), ':' . Str::ucfirst($key)],
                [$value, Str::upper($value), Str::ucfirst($value)],
                $line
            );
        }

        return $line;
    }

    /**
     * Get the default locale being used.
     *
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Set the default locale.
     *
     * @param  string $locale
     *
     * @return void
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
    }
}
