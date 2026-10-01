<?php
declare(strict_types=1);

namespace Somen\InventoryManagementSystem\Services;

use Somen\InventoryManagementSystem\Core\Env;
use Somen\InventoryManagementSystem\Core\Session;

/**
 * Self-hosted image captcha (PHP GD only - no keys, no external service, no TTF needed).
 * Answer is stored in the session as an HMAC, valid 5 minutes, one attempt per image.
 */
final class CaptchaService
{
    private const ALPHABET = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    private const LENGTH = 5;
    private const TTL = 300;

    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagerotate');
    }

    private static function hash(string $code): string
    {
        return hash_hmac('sha256', strtoupper($code), (string)Env::get('APP_KEY', 'ims-default-key'));
    }

    /** Create a new challenge, remember it, return PNG bytes. */
    public static function image(): string
    {
        $code = '';
        for ($i = 0; $i < self::LENGTH; $i++) {
            $code .= self::ALPHABET[random_int(0, strlen(self::ALPHABET) - 1)];
        }
        Session::set('captcha', ['h' => self::hash($code), 't' => time()]);

        $w = 200;
        $h = 64;
        $img = imagecreatetruecolor($w, $h);
        $ivory = imagecolorallocate($img, 248, 245, 238);
        $navy  = imagecolorallocate($img, 11, 31, 58);
        $gold  = imagecolorallocate($img, 201, 162, 75);
        imagefilledrectangle($img, 0, 0, $w, $h, $ivory);

        // noise: gold lines + dots
        for ($i = 0; $i < 7; $i++) {
            imagesetthickness($img, random_int(1, 2));
            imageline($img, random_int(0, $w), random_int(0, $h), random_int(0, $w), random_int(0, $h), $gold);
        }
        for ($i = 0; $i < 140; $i++) {
            imagesetpixel($img, random_int(0, $w - 1), random_int(0, $h - 1), $i % 2 ? $gold : $navy);
        }

        // each char: draw small with built-in font, scale up, rotate, blit
        $x = 14;
        foreach (str_split($code) as $ch) {
            $small = imagecreatetruecolor(9, 15);
            $bg = imagecolorallocate($small, 255, 0, 255);
            imagecolortransparent($small, $bg);
            imagefilledrectangle($small, 0, 0, 9, 15, $bg);
            $fg = imagecolorallocate($small, 11, 31, 58);
            imagechar($small, 5, 0, 0, $ch, $fg);

            $scale = random_int(30, 38) / 10; // 3.0 - 3.8x
            $cw = (int)(9 * $scale);
            $chh = (int)(15 * $scale);
            $big = imagecreatetruecolor($cw, $chh);
            $bgb = imagecolorallocate($big, 255, 0, 255);
            imagecolortransparent($big, $bgb);
            imagefilledrectangle($big, 0, 0, $cw, $chh, $bgb);
            imagecopyresized($big, $small, 0, 0, 0, 0, $cw, $chh, 9, 15);

            $rot = imagerotate($big, random_int(-22, 22), $bgb);
            if ($rot !== false) {
                imagecolortransparent($rot, $bgb);
                imagecopymerge($img, $rot, $x, random_int(2, 12), 0, 0, imagesx($rot), imagesy($rot), 100);
            }
            $x += 34;
        }
        imagesetthickness($img, 1);
        imageline($img, 0, random_int(18, 46), $w, random_int(18, 46), $navy);

        ob_start();
        imagepng($img);
        return (string)ob_get_clean();
    }

    /** One attempt per image: the stored challenge is always cleared. */
    public static function check(string $answer): bool
    {
        $c = Session::pull('captcha');
        if (!is_array($c) || (time() - (int)($c['t'] ?? 0)) > self::TTL) {
            return false;
        }
        return hash_equals((string)$c['h'], self::hash(trim($answer)));
    }
}
