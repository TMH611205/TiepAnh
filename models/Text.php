<?php

/** Xử lý chữ tiếng Việt không phụ thuộc extension intl. */
class Text
{
    /** Chữ thường, bỏ dấu tiếng Việt (đ -> d). */
    public static function unaccent(string $text): string
    {
        static $map = null;

        if ($map === null) {
            $map = [];
            $groups = [
                'a' => 'áàảãạăắằẳẵặâấầẩẫậ',
                'e' => 'éèẻẽẹêếềểễệ',
                'i' => 'íìỉĩị',
                'o' => 'óòỏõọôốồổỗộơớờởỡợ',
                'u' => 'úùủũụưứừửữự',
                'y' => 'ýỳỷỹỵ',
                'd' => 'đ',
            ];

            foreach ($groups as $plain => $accented) {
                foreach (preg_split('//u', $accented, -1, PREG_SPLIT_NO_EMPTY) as $character) {
                    $map[$character] = $plain;
                }
            }
        }

        $text = strtr(mb_strtolower($text, 'UTF-8'), $map);

        // Dấu rời (chuỗi Unicode dạng tổ hợp) còn sót lại.
        return (string)preg_replace('/\p{Mn}+/u', '', $text);
    }

    /** Dạng so khớp từ khóa: chữ thường, không dấu, ký tự lạ thành khoảng trắng. */
    public static function plain(string $text): string
    {
        return trim((string)preg_replace('/[^a-z0-9]+/', ' ', self::unaccent($text)));
    }

    /** Slug cho URL: chữ thường, không dấu, nối bằng gạch ngang. */
    public static function slug(string $text, string $fallback = 'san-pham'): string
    {
        $slug = trim((string)preg_replace('/[^a-z0-9]+/', '-', self::unaccent($text)), '-');

        return $slug !== '' ? $slug : $fallback;
    }
}
