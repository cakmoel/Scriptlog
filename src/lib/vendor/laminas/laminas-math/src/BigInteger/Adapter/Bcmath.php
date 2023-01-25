<?php

namespace Laminas\Math\BigInteger\Adapter;

use Laminas\Math\BigInteger\Exception;

use function base_convert;
use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmod;
use function bcmul;
use function bcpow;
use function bcpowmod;
use function bcsqrt;
use function bcsub;
use function chr;
use function ltrim;
use function mb_strlen;
use function ord;
use function preg_match;
use function str_pad;
use function strpos;
use function strtolower;

use const STR_PAD_LEFT;

/**
 * Bcmath extension adapter
 */
class Bcmath implements AdapterInterface
{
    /**
     * Create string representing big integer in decimal form from arbitrary integer format
     *
     * @param  string $operand
     * @param  int|null $base
     * @return bool|string
     */
    public function init($operand, $base = null)
    {
        $sign    = strpos($operand, '-') === 0 ? '-' : '';
        $operand = ltrim($operand, '-+');

        if (null === $base) {
            // decimal
            if (preg_match('#^([1-9][0-9]*)$#', $operand, $m)) {
                $base    = 10;
                $operand = $m[1];
            // octal
            } elseif (preg_match('#^(0[0-7]+)$#', $operand, $m)) {
                $base    = 8;
                $operand = $m[1];
            // hex
            } elseif (preg_match('#^(?:0x)?([0-9a-f]+)$#', strtolower($operand), $m)) {
                $base    = 16;
                $operand = $m[1];
            // scientific notation
            } elseif (preg_match('#^([1-9]?\.?[0-9]+)[eE]\+?([0-9]+)$#', $operand, $m)) {
                $base    = 10;
                $operand = bcmul($m[1], bcpow('10', $m[2]));
            } else {
                return false;
            }
        }

        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedNotEqualOperator
        if ($base != 10) {
            $operand = $this->baseConvert($operand, $base, 10);
        }

        $prod = bcmul($operand, '1');
        if (bccomp($operand, $prod) !== 0) {
            return false;
        }

        return $sign . $operand;
    }

    /**
     * Add two big integers
     *
     * @param  string $leftOperand
     * @param  string $rightOperand
     * @return string
     */
    public function add($leftOperand, $rightOperand)
    {
        return bcadd($leftOperand, $rightOperand, 0);
    }

    /**
     * Subtract two big integers
     *
     * @param  string $leftOperand
     * @param  string $rightOperand
     * @return string
     */
    public function sub($leftOperand, $rightOperand)
    {
        return bcsub($leftOperand, $rightOperand, 0);
    }

    /**
     * Multiply two big integers
     *
     * @param string $leftOperand
     * @param string $rightOperand
     * @return string
     */
    public function mul($leftOperand, $rightOperand)
    {
        return bcmul($leftOperand, $rightOperand, 0);
    }

    /**
     * Divide two big integers and return integer part result.
     * Raises exception if the divisor is zero.
     *
     * @param string $leftOperand
     * @param string $rightOperand
     * @return string
     * @throws Exception\DivisionByZeroException
     */
    public function div($leftOperand, $rightOperand)
    {
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ($rightOperand == 0) {
            throw new Exception\DivisionByZeroException(
                "Division by zero; divisor = {$rightOperand}"
            );
        }

        return bcdiv($leftOperand, $rightOperand, 0);
    }

    /**
     * Raise a big integers to another
     *
     * @param  string $operand
     * @param  string $exp
     * @return string
     */
    public function pow($operand, $exp)
    {
        return bcpow($operand, $exp, 0);
    }

    /**
     * Get the square root of a big integer
     *
     * @param  string $operand
     * @return string
     */
    public function sqrt($operand)
    {
        return bcsqrt($operand, 0);
    }

    /**
     * Get absolute value of a big integer
     *
     * @param  string $operand
     * @return string
     */
    public function abs($operand)
    {
        return ltrim($operand, '-');
    }

    /**
     * Get modulus of a big integer
     *
     * @param  string $leftOperand
     * @param  string $rightOperand
     * @return string
     */
    public function mod($leftOperand, $rightOperand)
    {
        return bcmod($leftOperand, $rightOperand);
    }

    /**
     * Raise a big integer to another, reduced by a specified modulus
     *
     * @param  string $leftOperand
     * @param  string $rightOperand
     * @param  string $modulus
     * @return string
     */
    public function powmod($leftOperand, $rightOperand, $modulus)
    {
        return bcpowmod($leftOperand, $rightOperand, $modulus, 0);
    }

    /**
     * Compare two big integers and returns result as an integer where
     * Returns < 0 if leftOperand is less than rightOperand;
     * > 0 if leftOperand is greater than rightOperand, and 0 if they are equal.
     *
     * @param  string $leftOperand
     * @param  string $rightOperand
     * @return int
     */
    public function comp($leftOperand, $rightOperand)
    {
        return bccomp($leftOperand, $rightOperand, 0);
    }

    /**
     * Convert big integer into it's binary number representation
     *
     * @param  string $operand
     * @param  bool $twoc return in two's complement form
     * @return string
     */
    public function intToBin($operand, $twoc = false)
    {
        $nb         = chr(0);
        $isNegative = strpos($operand, '-') === 0;
        $operand    = ltrim($operand, '+-0');

        if (empty($operand)) {
            return $nb;
        }

        if ($isNegative && $twoc) {
            $operand = bcsub($operand, '1');
        }

        $bytes = '';
        while (bccomp($operand, '0', 0) > 0) {
            $temp    = bcmod($operand, '16777216');
            $bytes   = chr($temp >> 16) . chr($temp >> 8) . chr($temp) . $bytes;
            $operand = bcdiv($operand, '16777216', 0);
        }
        $bytes = ltrim($bytes, $nb);

        if ($twoc) {
            if (ord($bytes[0]) & 0x80) {
                $bytes = $nb . $bytes;
            }
            return $isNegative ? ~$bytes : $bytes;
        }

        return $bytes;
    }

    /**
     * Convert big integer into it's binary number representation
     *
     * @param  string $bytes
     * @param  bool   $twoc whether binary number is in twos' complement form
     * @return string
     */
    public function binToInt($bytes, $twoc = false)
    {
        $isNegative = (ord($bytes[0]) & 0x80) && $twoc;

        if ($isNegative) {
            $bytes = ~$bytes;
        }

        $len   = (mb_strlen($bytes, '8bit') + 3) & 0xfffffffc;
        $bytes = str_pad($bytes, $len, chr(0), STR_PAD_LEFT);

        $result = '0';
        for ($i = 0; $i < $len; $i += 4) {
            $result = bcmul($result, '4294967296'); // 2**32
            $result = bcadd($result, 0x1000000 * ord($bytes[$i]) +
                    ((ord($bytes[$i + 1]) << 16) |
                     (ord($bytes[$i + 2]) << 8) |
                      ord($bytes[$i + 3])));
        }

        if ($isNegative) {
            $result = bcsub('-' . $result, '1');
        }

        return $result;
    }

    /**
     * Base conversion. Bases 2..62 are supported
     *
     * @param  string $operand
     * @param  int    $fromBase
     * @param  int    $toBase
     * @return string
     * @throws Exception\InvalidArgumentException
     */
    public function baseConvert($operand, $fromBase, $toBase = 10)
    {
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ($fromBase == $toBase) {
            return $operand;
        }

        if ($fromBase < 2 || $fromBase > 62) {
            throw new Exception\InvalidArgumentException(
                "Unsupported base: {$fromBase}, should be 2..62"
            );
        }
        if ($toBase < 2 || $toBase > 62) {
            throw new Exception\InvalidArgumentException(
                "Unsupported base: {$toBase}, should be 2..62"
            );
        }

        $sign    = strpos($operand, '-') === 0 ? '-' : '';
        $operand = ltrim($operand, '-+');

        $chars = self::BASE62_ALPHABET;

        // convert to decimal
        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ($fromBase == 10) {
            $decimal = $operand;
        } else {
            $decimal = '0';
            for ($i = 0, $len  = mb_strlen($operand, '8bit'); $i < $len; $i++) {
                $decimal = bcmul($decimal, $fromBase);

                $remainder = $fromBase <= 36
                    ? base_convert($operand[$i], $fromBase, 10)
                    : strpos($chars, $operand[$i]);

                $decimal = bcadd($decimal, $remainder, 0);
            }
        }

        // phpcs:ignore SlevomatCodingStandard.Operators.DisallowEqualOperators.DisallowedEqualOperator
        if ($toBase == 10) {
            return $decimal;
        }

        // convert decimal to base
        $result = '';
        do {
            $remainder = bcmod($decimal, $toBase);
            $decimal   = bcdiv($decimal, $toBase);

            $intermediate = $toBase <= 36
                ? base_convert($remainder, 10, $toBase)
                : $chars[$remainder];

            $result = $intermediate . $result;
        } while (bccomp($decimal, '0'));

        return $sign . $result;
    }
}
