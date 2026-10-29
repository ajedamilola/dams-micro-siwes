<?php

namespace App\Support;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Minimal, dependency-free XLSX writer for tabular exports. Values are
 * written as inline strings or numbers, with bold rows and a currency
 * number format available.
 */
final class SimpleXlsx
{
    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     * @param  array<int, int>  $boldRows  Zero-based row indexes rendered bold.
     * @param  array<int, float>  $columnWidths  Zero-based column widths.
     */
    public static function download(string $filename, array $rows, array $boldRows = [], array $columnWidths = []): BinaryFileResponse
    {
        $path = tempnam(sys_get_temp_dir(), 'statement-');

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('[Content_Types].xml', self::contentTypes());
        $zip->addFromString('_rels/.rels', self::rootRels());
        $zip->addFromString('xl/workbook.xml', self::workbook());
        $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRels());
        $zip->addFromString('xl/styles.xml', self::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', self::sheet($rows, $boldRows, $columnWidths));

        $zip->close();

        return response()
            ->download($path, $filename, [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @param  array<int, array<int, string|int|float|null>>  $rows
     * @param  array<int, int>  $boldRows
     * @param  array<int, float>  $columnWidths
     */
    private static function sheet(array $rows, array $boldRows, array $columnWidths): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>';
        $xml .= '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';

        if ($columnWidths !== []) {
            $xml .= '<cols>';
            foreach ($columnWidths as $index => $width) {
                $xml .= '<col min="'.($index + 1).'" max="'.($index + 1).'" width="'.$width.'" customWidth="1"/>';
            }
            $xml .= '</cols>';
        }

        $xml .= '<sheetData>';

        foreach ($rows as $rowIndex => $row) {
            $xml .= '<row r="'.($rowIndex + 1).'">';
            $bold = in_array($rowIndex, $boldRows, true);

            foreach (array_values($row) as $columnIndex => $value) {
                if ($value === null || $value === '') {
                    continue;
                }

                $cell = self::column($columnIndex).($rowIndex + 1);

                if (is_int($value) || is_float($value)) {
                    $number = is_int($value)
                        ? (string) $value
                        : rtrim(rtrim(number_format($value, 4, '.', ''), '0'), '.');
                    $style = $bold ? 3 : 2;
                    $xml .= '<c r="'.$cell.'" s="'.$style.'"><v>'.$number.'</v></c>';

                    continue;
                }

                $style = $bold ? 1 : 0;
                $text = htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<c r="'.$cell.'" s="'.$style.'" t="inlineStr"><is><t xml:space="preserve">'.$text.'</t></is></c>';
            }

            $xml .= '</row>';
        }

        return $xml.'</sheetData></worksheet>';
    }

    private static function column(int $index): string
    {
        $letters = '';

        while ($index >= 0) {
            $letters = chr(65 + ($index % 26)).$letters;
            $index = intdiv($index, 26) - 1;
        }

        return $letters;
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbook(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="Statement" sheetId="1" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="#,##0.00"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="4">'
            .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="0" borderId="0" xfId="0" applyFont="1"/>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="164" fontId="1" fillId="0" borderId="0" xfId="0" applyNumberFormat="1" applyFont="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
    }
}
