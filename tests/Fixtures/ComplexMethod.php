<?php

declare(strict_types=1);

namespace TechRaysLabs\DebtTracker\Tests\Fixtures;

/**
 * Fixture class with exactly measurable complexity properties.
 */
class ComplexMethod
{
    /**
     * Method with cyclomatic complexity of exactly 15.
     * Base: 1 + 14 decision nodes = 15.
     */
    public function highComplexityMethod(
        int $a,
        int $b,
        bool $c,
        bool $d,
        bool $e,
        string $type,
    ): string {
        if ($a > 0) {
            if ($b > 0) {
                if ($c) {
                    $result = 'pos';
                } elseif ($d) {
                    $result = 'mid';
                } else {
                    $result = 'low';
                }
            } elseif ($e) {
                $result = 'neg-e';
            } else {
                $result = 'neg';
            }
        } elseif ($c && $d) {
            $result = 'cd';
        } elseif ($c || $d) {
            $result = 'c-or-d';
        } else {
            switch ($type) {
                case 'x':
                    $result = 'x';

                    break;
                case 'y':
                    $result = 'y';

                    break;
                default:
                    $result = 'default';
            }
        }

        return $result ?? 'unknown';
    }

    /**
     * Method with 45 statements (long method).
     */
    public function longMethod(): array
    {
        $a = 1;
        $b = 2;
        $c = 3;
        $d = 4;
        $e = 5;
        $f = 6;
        $g = 7;
        $h = 8;
        $i = 9;
        $j = 10;
        $k = $a + $b;
        $l = $c + $d;
        $m = $e + $f;
        $n = $g + $h;
        $o = $i + $j;
        $p = $k + $l;
        $q = $m + $n;
        $r = $o + $p;
        $s = $q + $r;
        $t = $s + $a;
        $u = $t + $b;
        $v = $u + $c;
        $w = $v + $d;
        $x = $w + $e;
        $y = $x + $f;
        $z = $y + $g;
        $aa = $z + $h;
        $ab = $aa + $i;
        $ac = $ab + $j;
        $ad = $ac + $k;
        $ae = $ad + $l;
        $af = $ae + $m;
        $ag = $af + $n;
        $ah = $ag + $o;
        $ai = $ah + $p;
        $aj = $ai + $q;
        $ak = $aj + $r;
        $al = $ak + $s;
        $am = $al + $t;
        $an = $am + $u;
        $ao = $an + $v;
        $ap = $ao + $w;
        $aq = $ap + $x;
        $ar = $aq + $y;

        return [$a, $b, $c, $d, $ar];
    }

    /**
     * Method with nesting depth of 6.
     */
    public function deeplyNestedMethod(array $data): string
    {
        if (! empty($data)) {                          // depth 1
            foreach ($data as $item) {                 // depth 2
                if (is_array($item)) {                 // depth 3
                    foreach ($item as $sub) {          // depth 4
                        if ($sub !== null) {            // depth 5
                            if (is_string($sub)) {     // depth 6
                                return $sub;
                            }
                        }
                    }
                }
            }
        }

        return '';
    }
}
