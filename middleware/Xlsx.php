<?php

/**
 * Xuất file Excel (.xlsx) không cần thư viện hay extension zip:
 * tự ghi gói ZIP (không nén) chứa các phần XML tối thiểu của SpreadsheetML.
 *
 * Xlsx::download('bao-cao.xlsx', [
 *     ['name' => 'Đơn hàng', 'headers' => ['Mã', 'Tổng'], 'rows' => [['TA01', 1500000]]],
 * ]);
 *
 * Số (int/float) được ghi dạng số có định dạng #,##0; mọi giá trị khác ghi dạng chữ
 * (nên truyền số điện thoại, mã... dưới dạng string để giữ số 0 đứng đầu).
 */
class Xlsx
{
    public static function download(string $filename, array $sheets): never
    {
        $binary = self::build($sheets);

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . rawurlencode($filename) . '"; filename*=UTF-8\'\'' . rawurlencode($filename));
        header('Content-Length: ' . strlen($binary));
        header('Cache-Control: no-store');
        echo $binary;
        exit;
    }

    public static function build(array $sheets): string
    {
        $files = [];
        $sheetXml = [];
        $names = [];

        foreach (array_values($sheets) as $index => $sheet) {
            $names[] = self::sheetName((string)($sheet['name'] ?? 'Sheet' . ($index + 1)), $names);
            $sheetXml[] = self::sheetXml($sheet['headers'] ?? [], $sheet['rows'] ?? []);
        }

        if ($sheetXml === []) {
            $names[] = 'Sheet1';
            $sheetXml[] = self::sheetXml([], []);
        }

        $count = count($sheetXml);
        $overrides = '';
        $workbookSheets = '';
        $workbookRels = '';

        for ($i = 1; $i <= $count; $i++) {
            $overrides .= '<Override PartName="/xl/worksheets/sheet' . $i . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>';
            $workbookSheets .= '<sheet name="' . self::esc($names[$i - 1]) . '" sheetId="' . $i . '" r:id="rId' . $i . '"/>';
            $workbookRels .= '<Relationship Id="rId' . $i . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $i . '.xml"/>';
            $files['xl/worksheets/sheet' . $i . '.xml'] = $sheetXml[$i - 1];
        }

        $workbookRels .= '<Relationship Id="rId' . ($count + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>';

        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';

        $files['[Content_Types].xml'] = $xml
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . $overrides . '</Types>';

        $files['_rels/.rels'] = $xml
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';

        $files['xl/workbook.xml'] = $xml
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets>' . $workbookSheets . '</sheets></workbook>';

        $files['xl/_rels/workbook.xml.rels'] = $xml
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . $workbookRels . '</Relationships>';

        // 0: mặc định, 1: tiêu đề (đậm, nền xanh nhạt), 2: số #,##0
        $files['xl/styles.xml'] = $xml
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE8F7EC"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs><cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';

        return self::zip($files);
    }

    private static function sheetXml(array $headers, array $rows): string
    {
        $data = '';
        $rowNumber = 0;
        $widths = [];

        $addRow = static function (array $cells, bool $isHeader) use (&$data, &$rowNumber, &$widths): void {
            $rowNumber++;
            $data .= '<row r="' . $rowNumber . '">';

            foreach (array_values($cells) as $index => $value) {
                $reference = self::columnLetter($index) . $rowNumber;

                if (!$isHeader && (is_int($value) || is_float($value))) {
                    $data .= '<c r="' . $reference . '" s="2"><v>' . $value . '</v></c>';
                    $length = strlen(number_format((float)$value, 0, ',', '.'));
                } else {
                    $text = (string)($value ?? '');
                    $style = $isHeader ? ' s="1"' : '';
                    $data .= '<c r="' . $reference . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . self::esc($text) . '</t></is></c>';
                    $length = mb_strlen($text, 'UTF-8');
                }

                $widths[$index] = max($widths[$index] ?? 8, min(60, $length + 2));
            }

            $data .= '</row>';
        };

        if ($headers !== []) {
            $addRow($headers, true);
        }

        foreach ($rows as $row) {
            $addRow(array_values($row), false);
        }

        $cols = '';
        foreach ($widths as $index => $width) {
            $cols .= '<col min="' . ($index + 1) . '" max="' . ($index + 1) . '" width="' . $width . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0">'
            . ($headers !== [] ? '<pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/>' : '')
            . '</sheetView></sheetViews>'
            . ($cols !== '' ? '<cols>' . $cols . '</cols>' : '')
            . '<sheetData>' . $data . '</sheetData></worksheet>';
    }

    private static function columnLetter(int $index): string
    {
        $letter = '';
        $index++;

        while ($index > 0) {
            $remainder = ($index - 1) % 26;
            $letter = chr(65 + $remainder) . $letter;
            $index = intdiv($index - 1, 26);
        }

        return $letter;
    }

    /** Tên sheet: tối đa 31 ký tự, không chứa : \ / ? * [ ], không trùng nhau. */
    private static function sheetName(string $name, array $existing): string
    {
        $name = trim((string)preg_replace('/[:\\\\\/?*\[\]]/u', ' ', $name));
        $name = mb_substr($name === '' ? 'Sheet' : $name, 0, 31, 'UTF-8');
        $base = $name;
        $suffix = 2;

        while (in_array($name, $existing, true)) {
            $name = mb_substr($base, 0, 28, 'UTF-8') . ' ' . $suffix++;
        }

        return $name;
    }

    private static function esc(string $text): string
    {
        // Bỏ ký tự điều khiển không hợp lệ trong XML 1.0.
        $text = (string)preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F]/', '', $text);

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    /** Ghi gói ZIP kiểu "stored" (không nén). */
    private static function zip(array $files): string
    {
        $archive = '';
        $central = '';
        $offset = 0;
        $dosTime = 0;
        $dosDate = (1 << 5) | 1 | ((2024 - 1980) << 9);

        foreach ($files as $name => $content) {
            $crc = crc32($content);
            $size = strlen($content);
            $nameLength = strlen($name);

            $local = pack('VvvvvvVVVvv', 0x04034b50, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0)
                . $name . $content;

            $central .= pack('VvvvvvvVVVvvvvvVV', 0x02014b50, 20, 20, 0x0800, 0, $dosTime, $dosDate, $crc, $size, $size, $nameLength, 0, 0, 0, 0, 0, $offset)
                . $name;

            $archive .= $local;
            $offset += strlen($local);
        }

        return $archive . $central
            . pack('VvvvvVVv', 0x06054b50, 0, 0, count($files), count($files), strlen($central), $offset, 0);
    }
}
