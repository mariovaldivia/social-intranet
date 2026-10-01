<?php

namespace App\Tests;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * Every key in translations/ must exist in both English and Spanish.
 */
class TranslationsTest extends TestCase
{
    /**
     * @dataProvider domainProvider
     */
    public function testEnglishAndSpanishDefineTheSameKeys(string $domain): void
    {
        $en = $this->keys($domain, 'en');
        $es = $this->keys($domain, 'es');

        self::assertNotEmpty($en);
        self::assertSame([], array_values(array_diff($en, $es)), "Keys missing in $domain es");
        self::assertSame([], array_values(array_diff($es, $en)), "Keys missing in $domain en");
    }

    public static function domainProvider(): iterable
    {
        yield 'messages' => ['messages'];
        yield 'validators' => ['validators'];
    }

    /** @return string[] dotted keys */
    private function keys(string $domain, string $locale): array
    {
        $file = sprintf('%s/translations/%s+intl-icu.%s.yaml', dirname(__DIR__), $domain, $locale);

        return $this->flatten(Yaml::parseFile($file));
    }

    private function flatten(array $tree, string $prefix = ''): array
    {
        $keys = [];
        foreach ($tree as $key => $value) {
            $keys = is_array($value)
                ? [...$keys, ...$this->flatten($value, $prefix.$key.'.')]
                : [...$keys, $prefix.$key];
        }

        return $keys;
    }
}
