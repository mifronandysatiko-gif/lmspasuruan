<?php

namespace App\Services;

use Exception;
use SimpleXMLElement;
use ZipArchive;

class ExcelImportService
{
    /**
     * Parse uploaded file (XLSX, XLS, CSV) into array of associative rows.
     *
     * @param string $filePath Absolute path to temporary uploaded file
     * @param string $originalName Original client filename
     * @return array<array<string, mixed>> Array of parsed rows with header keys
     */
    public static function parseFile(string $filePath, string $originalName = ''): array
    {
        $ext = strtolower(pathinfo($originalName ?: $filePath, PATHINFO_EXTENSION));

        if ($ext === 'csv' || $ext === 'txt') {
            return self::parseCsv($filePath);
        }

        if ($ext === 'xlsx') {
            return self::parseXlsx($filePath);
        }

        // Try XLSX first, fallback to CSV
        try {
            return self::parseXlsx($filePath);
        } catch (Exception $e) {
            return self::parseCsv($filePath);
        }
    }

    /**
     * Parse CSV / TSV file with auto delimiter detection and UTF-8 BOM removal.
     */
    public static function parseCsv(string $filePath): array
    {
        if (!file_exists($filePath)) {
            throw new Exception("File tidak ditemukan: {$filePath}");
        }

        $content = file_get_contents($filePath);
        if ($content === false || trim($content) === '') {
            return [];
        }

        // Remove UTF-8 BOM
        if (substr($content, 0, 3) === "\xEF\xBB\xBF") {
            $content = substr($content, 3);
        }

        // Auto-detect line endings
        $lines = preg_split('/\r\n|\r|\n/', trim($content));
        if (empty($lines)) {
            return [];
        }

        // Auto-detect delimiter from first line (comma, semicolon, tab)
        $firstLine = $lines[0];
        $delimiters = [',', ';', "\t", '|'];
        $bestDelimiter = ',';
        $maxCount = 0;
        foreach ($delimiters as $delim) {
            $count = substr_count($firstLine, $delim);
            if ($count > $maxCount) {
                $maxCount = $count;
                $bestDelimiter = $delim;
            }
        }

        $stream = fopen('php://memory', 'r+');
        fwrite($stream, $content);
        rewind($stream);

        $headers = [];
        $rows = [];
        $isFirst = true;

        while (($data = fgetcsv($stream, 0, $bestDelimiter)) !== false) {
            if (empty($data) || (count($data) === 1 && trim((string)$data[0]) === '')) {
                continue;
            }

            if ($isFirst) {
                $headers = array_map(function ($h) {
                    return self::normalizeHeader((string) $h);
                }, $data);
                $isFirst = false;
                continue;
            }

            $row = [];
            foreach ($headers as $index => $header) {
                if ($header !== '') {
                    $row[$header] = isset($data[$index]) ? trim((string) $data[$index]) : '';
                }
            }

            if (self::isRowNotEmpty($row)) {
                $rows[] = $row;
            }
        }

        fclose($stream);
        return $rows;
    }

    /**
     * Parse modern Excel (.xlsx) file using pure PHP ZipArchive and SimpleXML.
     */
    public static function parseXlsx(string $filePath): array
    {
        if (!class_exists('ZipArchive')) {
            throw new Exception("Ekstensi PHP ZipArchive tidak aktif.");
        }

        $zip = new ZipArchive();
        $res = $zip->open($filePath);
        if ($res !== true) {
            throw new Exception("Gagal membuka file Excel (.xlsx). Pastikan format file valid.");
        }

        // 1. Read shared strings
        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');
        if ($sharedStringsXml !== false) {
            $xml = simplexml_load_string($sharedStringsXml);
            if ($xml) {
                foreach ($xml->si as $si) {
                    if (isset($si->t)) {
                        $sharedStrings[] = (string) $si->t;
                    } elseif (isset($si->r)) {
                        $text = '';
                        foreach ($si->r as $r) {
                            $text .= (string) $r->t;
                        }
                        $sharedStrings[] = $text;
                    } else {
                        $sharedStrings[] = '';
                    }
                }
            }
        }

        // 2. Read first worksheet
        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        if ($sheetXml === false) {
            // Find first sheet in xl/worksheets/
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $name = $zip->getNameIndex($i);
                if (str_starts_with($name, 'xl/worksheets/sheet') && str_ends_with($name, '.xml')) {
                    $sheetXml = $zip->getFromIndex($i);
                    break;
                }
            }
        }

        $zip->close();

        if ($sheetXml === false) {
            throw new Exception("Worksheet tidak ditemukan dalam file Excel.");
        }

        $sheet = simplexml_load_string($sheetXml);
        if (!$sheet || !isset($sheet->sheetData)) {
            return [];
        }

        $grid = [];
        $maxColIdx = 0;

        foreach ($sheet->sheetData->row as $row) {
            $rowNum = (int) $row['r'];
            $rowData = [];

            foreach ($row->c as $c) {
                $cellRef = (string) $c['r']; // e.g. "A1", "B2"
                $colLetters = preg_replace('/[0-9]/', '', $cellRef);
                $colIdx = self::columnLetterToIndex($colLetters);
                if ($colIdx > $maxColIdx) {
                    $maxColIdx = $colIdx;
                }

                $type = (string) $c['t'];
                $val = '';

                if ($type === 's') {
                    // Shared string index
                    $idx = (int) $c->v;
                    $val = $sharedStrings[$idx] ?? '';
                } elseif ($type === 'inlineStr') {
                    $val = (string) $c->is->t;
                } else {
                    $val = (string) $c->v;
                }

                $rowData[$colIdx] = trim($val);
            }

            $grid[$rowNum] = $rowData;
        }

        if (empty($grid)) {
            return [];
        }

        // Sort rows by row number
        ksort($grid);

        // Header row is first row
        $headerRow = array_shift($grid);
        if (!$headerRow) {
            return [];
        }

        $headers = [];
        for ($i = 0; $i <= $maxColIdx; $i++) {
            $rawHeader = $headerRow[$i] ?? '';
            $headers[$i] = self::normalizeHeader((string) $rawHeader);
        }

        $rows = [];
        foreach ($grid as $rNum => $rData) {
            $row = [];
            foreach ($headers as $colIdx => $headerKey) {
                if ($headerKey !== '') {
                    $row[$headerKey] = $rData[$colIdx] ?? '';
                }
            }
            if (self::isRowNotEmpty($row)) {
                $rows[] = $row;
            }
        }

        return $rows;
    }

    /**
     * Map question rows into structured array for Quiz questions (Soal & Pilihan A-E & Kunci Jawaban).
     *
     * @param array $rows
     * @return array<array{order_number: int, question_text: string, options: array<string, string>, correct_answer: string, input_type: string}>
     */
    public static function mapQuizQuestions(array $rows): array
    {
        $questions = [];
        $defaultOrder = 1;

        foreach ($rows as $row) {
            $qText = self::findValue($row, ['pertanyaan', 'soal', 'question', 'deskripsi_soal', 'isi_pertanyaan']);
            if (empty($qText)) {
                continue;
            }

            $order = (int) self::findValue($row, ['no', 'nomor', 'order', 'urutan', 'no_urut', 'order_number']);
            if ($order <= 0) {
                $order = $defaultOrder;
            }
            $defaultOrder = max($defaultOrder, $order) + 1;

            $optA = self::findValue($row, ['pilihan_a', 'opsi_a', 'jawaban_a', 'option_a', 'a']);
            $optB = self::findValue($row, ['pilihan_b', 'opsi_b', 'jawaban_b', 'option_b', 'b']);
            $optC = self::findValue($row, ['pilihan_c', 'opsi_c', 'jawaban_c', 'option_c', 'c']);
            $optD = self::findValue($row, ['pilihan_d', 'opsi_d', 'jawaban_d', 'option_d', 'd']);
            $optE = self::findValue($row, ['pilihan_e', 'opsi_e', 'jawaban_e', 'option_e', 'e']);

            $options = [];
            if ($optA !== '') $options['A'] = $optA;
            if ($optB !== '') $options['B'] = $optB;
            if ($optC !== '') $options['C'] = $optC;
            if ($optD !== '') $options['D'] = $optD;
            if ($optE !== '') $options['E'] = $optE;

            // Fallback options if minimal
            if (empty($options)) {
                $options = [
                    'A' => 'Pilihan A',
                    'B' => 'Pilihan B',
                ];
            }

            $correct = strtoupper(trim(self::findValue($row, ['kunci_jawaban', 'kunci', 'jawaban_benar', 'correct_answer', 'jawaban', 'kunci_soal', 'kunci_opsi'])));
            // If correct answer is not in A..E, check if it equals option text
            if (!in_array($correct, ['A', 'B', 'C', 'D', 'E'])) {
                foreach ($options as $key => $val) {
                    if (strcasecmp($correct, $val) === 0) {
                        $correct = $key;
                        break;
                    }
                }
            }
            if (!in_array($correct, ['A', 'B', 'C', 'D', 'E'])) {
                $correct = array_key_first($options) ?: 'A';
            }

            $inputType = self::findValue($row, ['tipe', 'tipe_input', 'input_type', 'jenis']);
            $inputType = in_array(strtolower($inputType), ['text', 'essay', 'uraian']) ? 'text' : 'options';

            $questions[] = [
                'order_number' => $order,
                'question_text' => $qText,
                'options' => $options,
                'correct_answer' => $correct,
                'input_type' => $inputType,
            ];
        }

        return $questions;
    }

    /**
     * Map questionnaire rows (Kuesioner Kepuasan - Pilihan / Esai).
     */
    public static function mapQuestionnaireQuestions(array $rows): array
    {
        $questions = [];
        $defaultOrder = 1;

        foreach ($rows as $row) {
            $qText = self::findValue($row, ['pertanyaan', 'soal', 'question', 'deskripsi_pertanyaan', 'isi_pertanyaan']);
            if (empty($qText)) {
                continue;
            }

            $order = (int) self::findValue($row, ['no', 'nomor', 'order', 'urutan', 'no_urut', 'order_number']);
            if ($order <= 0) {
                $order = $defaultOrder;
            }
            $defaultOrder = max($defaultOrder, $order) + 1;

            $typeRaw = strtolower(self::findValue($row, ['tipe', 'tipe_input', 'input_type', 'jenis', 'format']));
            $isText = in_array($typeRaw, ['text', 'essay', 'uraian', 'isian', 'esai']);

            $options = [];
            if (!$isText) {
                $optA = self::findValue($row, ['pilihan_a', 'opsi_a', 'jawaban_a', 'a']);
                $optB = self::findValue($row, ['pilihan_b', 'opsi_b', 'jawaban_b', 'b']);
                $optC = self::findValue($row, ['pilihan_c', 'opsi_c', 'jawaban_c', 'c']);
                $optD = self::findValue($row, ['pilihan_d', 'opsi_d', 'jawaban_d', 'd']);
                $optE = self::findValue($row, ['pilihan_e', 'opsi_e', 'jawaban_e', 'e']);

                if ($optA !== '') $options['A'] = $optA;
                if ($optB !== '') $options['B'] = $optB;
                if ($optC !== '') $options['C'] = $optC;
                if ($optD !== '') $options['D'] = $optD;
                if ($optE !== '') $options['E'] = $optE;

                if (empty($options)) {
                    $options = [
                        'A' => 'Sangat Baik',
                        'B' => 'Baik',
                        'C' => 'Cukup',
                        'D' => 'Kurang',
                    ];
                }
            }

            $questions[] = [
                'order_number' => $order,
                'question_text' => $qText,
                'input_type' => $isText ? 'text' : 'options',
                'options' => $isText ? null : $options,
                'correct_answer' => null,
            ];
        }

        return $questions;
    }

    /**
     * Generate CSV Template string for Quiz questions.
     */
    public static function generateQuizCsvTemplate(): string
    {
        $bom = "\xEF\xBB\xBF"; // UTF-8 BOM so Excel opens with proper accents & columns
        $headers = "No,Pertanyaan,Pilihan_A,Pilihan_B,Pilihan_C,Pilihan_D,Pilihan_E,Kunci_Jawaban\n";
        $samples = [
            '1,"Apa kepanjangan dari SPBE dalam tata kelola pemerintahan?","Sistem Pemerintahan Berbasis Elektronik","Sistem Pelayanan Bersama Elektronik","Standar Pengelolaan Basis Elektronik","Sistem Pengawasan Berita Elektronik","Semua Salah","A"',
            '2,"Manakah yang merupakan pilar utama literasi digital ASN BerAKHLAK?","Digital Skill","Digital Safety","Digital Culture","Digital Ethics","Semua Benar","E"',
            '3,"Instansi pembina pengembangan kompetensi ASN di Kabupaten Pasuruan adalah?","Dinas Kominfo","BKPSDM Kabupaten Pasuruan","Inspektorat Daerah","Bappeda","Bagian Hukum","B"',
        ];

        return $bom . $headers . implode("\n", $samples) . "\n";
    }

    /**
     * Generate CSV Template string for Questionnaire questions.
     */
    public static function generateQuestionnaireCsvTemplate(): string
    {
        $bom = "\xEF\xBB\xBF";
        $headers = "No,Pertanyaan,Tipe_Input,Pilihan_A,Pilihan_B,Pilihan_C,Pilihan_D\n";
        $samples = [
            '1,"Bagaimana penilaian Anda terhadap kejelasan materi yang disampaikan oleh Narasumber?","options","Sangat Baik / Jelas","Baik","Cukup","Kurang"',
            '2,"Bagaimana kualitas teknis media siaran dan kestabilan sistem LMS selama webinar berlangsung?","options","Sangat Memuaskan","Memuaskan","Cukup Memuaskan","Kurang Memuaskan"',
            '3,"Tuliskan saran, kritik, atau topik pelatihan yang Anda butuhkan untuk kegiatan pengembangan kompetensi selanjutnya:","text","","","",""',
        ];

        return $bom . $headers . implode("\n", $samples) . "\n";
    }

    private static function normalizeHeader(string $header): string
    {
        $h = strtolower(trim($header));
        $h = preg_replace('/[^a-z0-9_]/', '_', $h);
        $h = preg_replace('/_+/', '_', $h);
        return trim($h, '_');
    }

    private static function findValue(array $row, array $candidateKeys): string
    {
        foreach ($candidateKeys as $key) {
            $k = self::normalizeHeader($key);
            if (isset($row[$k]) && trim((string)$row[$k]) !== '') {
                return trim((string)$row[$k]);
            }
        }
        return '';
    }

    private static function isRowNotEmpty(array $row): bool
    {
        foreach ($row as $val) {
            if (trim((string) $val) !== '') {
                return true;
            }
        }
        return false;
    }

    private static function columnLetterToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $len = strlen($letters);
        $index = 0;
        for ($i = 0; $i < $len; $i++) {
            $index *= 26;
            $index += (ord($letters[$i]) - ord('A') + 1);
        }
        return $index - 1;
    }
}
