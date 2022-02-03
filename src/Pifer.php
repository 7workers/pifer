<?php  namespace Pifer;

abstract class Pifer
{
    public static function getPif($fname)
    {
        $gd = @imagecreatefromstring(file_get_contents($fname));

        if (!is_object($gd) && !is_resource($gd)) throw new ExceptionCorruptedImage($fname);

        $hash = dechex(self::getHashFromGd($gd, 8));

        imagedestroy($gd);

        if (strlen($hash) < 12) return null;
        if (preg_match('/(.)(.)\1\2\1\2\1\2/', $hash)) return null;

        return $hash;
    }

    public static function distance($pif1, $pif2)
    {
        if (extension_loaded('gmp')) {
            $dh = gmp_hamdist('0x' . $pif1, '0x' . $pif2);
        } else {
            $pif1 = self::hexDec($pif1);
            $pif2 = self::hexDec($pif2);

            $dh = 0;

            for ($i = 0; $i < 64; $i++) {
                $k = (1 << $i);

                if (($pif1 & $k) !== ($pif2 & $k)) $dh++;
            }
        }

        return $dh;
    }

    private static function getHashFromGd( $gd, $size)
    {
        $w  = $size + 1;
        $h = $size;

        $gd_res = imagecreatetruecolor($w, $h);
        
        imagecopyresampled($gd_res, $gd, 0, 0, 0, 0, $w, $h, imagesx($gd), imagesy($gd));

        $p   = 0;
        $bit = 1;

        for ($y = 0; $y < $h; $y++) {
            $rgb  = imagecolorsforindex($gd_res, imagecolorat($gd_res, 0, $y));
            $left = floor(($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3);

            for ($x = 1; $x < $w; $x++) {
                $rgb   = imagecolorsforindex($gd_res, imagecolorat($gd_res, $x, $y));
                $right = floor(($rgb['red'] + $rgb['green'] + $rgb['blue']) / 3);

                if ($left > $right) $p |= $bit;

                $left = $right;
                $bit  = $bit << 1;
            }
        }

        imagedestroy($gd_res);

        return $p;
    }

    private static function hexDec( $hex )
    {
        if (strlen($hex) == 16 && hexdec($hex[0]) > 8) {
            list($higher, $lower) = array_values(unpack('N2', hex2bin($hex)));
            return $higher << 32 | $lower;
        }

        return hexdec($hex);
    }
}