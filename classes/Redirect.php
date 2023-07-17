<?php

declare(strict_types=1);

namespace Bnomei;

use Kirby\Toolkit\V;

final class Redirect
{
    /**
     * @var string
     */
    private $fromuri;
    /**
     * @var string
     */
    private $touri;
    /**
     * @var int
     */
    private $code;

    public function __construct(string $fromuri, string $touri, $code = 301)
    {
        $this->fromuri = $fromuri;
        $this->touri = $touri;
        $this->code = static::normalizeCode($code);
    }

    public function matches(string $url): bool
    {
        $from = rtrim($this->from(), '/');

        // plain string
        if (in_array($url, [
            $from,
            $from . '/', // issue #10
        ])) {
            return true;
        }

        // regex
        $pattern = '~^' . $from . '$~'; // regex delimiters
        if (preg_match($pattern, $url, $matches) === 1) {
            if (count($matches) > 1) {
                foreach ($matches as $key => $value) {
                    $this->touri = str_replace('$' . $key, $value, $this->touri);
                }
            }
            return true;
        }

        return false;
    }

    public function from(): string
    {
        return $this->fromuri;
    }

    public function to(): string
    {
        return $this->touri;
    }

    public function code(): int
    {
        return $this->code;
    }

    public function toArray(): array
    {
        return [
            'fromuri' => $this->from(),
            'touri' => $this->to(),
            'code' => '_' . $this->code(),
        ];
    }

    public function __debugInfo()
    {
        return $this->toArray();
    }

    public static function url($url): string
    {
        $id = '/' . trim($url, '/');
        $page = page($id);
        if ($page) {
            return url($page->url());
        }

        if (V::url($url)) {
            return url($url);
        }

        return url($url);
    }

    public static function normalizeCode($code): int
    {
        if (is_string($code)) {
            $code = intval(str_replace('_', '', $code));
        }
        if (! $code) {
            $code = 301;
        }

        return $code;
    }
}
