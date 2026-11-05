<?php

namespace App\Support;

/** Pemformat angka gaya Indonesia. */
class Fmt
{
    public static function rp(float|int|null $n): string
    {
        return 'Rp '.number_format((float) $n, 0, ',', '.');
    }

    public static function num(float|int|null $n, int $dec = 1): string
    {
        $s = number_format((float) $n, $dec, ',', '.');

        return $dec > 0 ? rtrim(rtrim($s, '0'), ',') : $s;
    }

    /**
     * Memformat teks markdown dari AI menjadi HTML rapi untuk Light Mode:
     * - Bold: **teks** -> <strong class="font-bold text-ink">
     * - Paragraf: Jeda tepat 1 baris (mb-2), tanpa double break berlebihan
     * - Poin: * atau - diformat rapi dengan bullet brand
     * - Nomor: 1. atau 2. diformat angka rapi
     */
    public static function markdownAi(?string $text): string
    {
        if (! $text) {
            return '';
        }

        $s = htmlspecialchars(trim($text), ENT_QUOTES, 'UTF-8');

        // Bold: **teks**
        $s = preg_replace('/\*\*(.*?)\*\*/s', '<strong class="font-bold text-ink">$1</strong>', $s);

        // Split paragraphs by 2 or more newlines
        $paragraphs = preg_split("/\n\s*\n/", $s);
        $htmlBlocks = [];

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                continue;
            }

            $lines = explode("\n", $para);
            $paraHtml = '';
            $lineCount = count($lines);

            for ($i = 0; $i < $lineCount; $i++) {
                $line = trim($lines[$i]);
                if ($line === '') {
                    continue;
                }

                // Markdown table block
                if (str_starts_with($line, '|') && str_ends_with($line, '|')) {
                    $tableLines = [];
                    while ($i < $lineCount) {
                        $candidate = trim($lines[$i]);
                        if (str_starts_with($candidate, '|') && str_ends_with($candidate, '|')) {
                            $tableLines[] = $candidate;
                            $i++;
                        } else {
                            break;
                        }
                    }
                    $i--;

                    $paraHtml .= self::renderMarkdownTable($tableLines);

                    continue;
                }

                // Headers
                if (preg_match('/^####\s+(.*)$/', $line, $m)) {
                    $paraHtml .= '<div class="font-bold text-ink text-xs sm:text-sm mt-2 mb-0.5">'.$m[1].'</div>';
                } elseif (preg_match('/^###\s+(.*)$/', $line, $m)) {
                    $paraHtml .= '<div class="font-bold text-ink text-sm mt-2 mb-0.5">'.$m[1].'</div>';
                } elseif (preg_match('/^##?\s+(.*)$/', $line, $m)) {
                    $paraHtml .= '<div class="font-bold text-ink text-base mt-2.5 mb-0.5">'.$m[1].'</div>';
                } elseif ($line === '---') {
                    $paraHtml .= '<hr class="border-line my-2">';
                } elseif (preg_match('/^&gt;\s*(.*)$/', $line, $m)) {
                    $quote = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $m[1]);
                    $paraHtml .= '<div class="border-l-2 border-brand/60 bg-white/80 pl-2.5 py-1 my-1 text-ink/90 rounded-r">'.$quote.'</div>';
                } elseif (preg_match('/^[\*\-•]\s+(.*)$/', $line, $m)) {
                    $item = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $m[1]);
                    $paraHtml .= '<div class="flex items-start gap-2 my-0.5 text-ink"><span class="text-brand font-bold text-xs mt-0.5">•</span><span>'.$item.'</span></div>';
                } elseif (preg_match('/^(\d+)\.\s+(.*)$/', $line, $m)) {
                    $item = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $m[2]);
                    $paraHtml .= '<div class="flex items-start gap-2 my-0.5 text-ink"><span class="text-brand font-bold text-xs mt-0.5">'.$m[1].'.</span><span>'.$item.'</span></div>';
                } else {
                    $line = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $line);
                    $paraHtml .= ($paraHtml !== '' && ! str_ends_with($paraHtml, '</div>') && ! str_ends_with($paraHtml, '<hr class="border-line my-2">') ? '<br>' : '').$line;
                }
            }

            $htmlBlocks[] = '<div class="mb-2 last:mb-0 leading-relaxed text-ink">'.$paraHtml.'</div>';
        }

        return implode('', $htmlBlocks);
    }

    /**
     * Merender baris tabel Markdown menjadi tabel HTML ringkas.
     *
     * @param  array<int, string>  $tableLines
     */
    private static function renderMarkdownTable(array $tableLines): string
    {
        $rows = [];

        foreach ($tableLines as $rawRow) {
            $trimmed = trim($rawRow, "| \t");
            if (preg_match('/^[\s:\-\|]+$/', $trimmed)) {
                continue;
            }

            $cells = array_map('trim', explode('|', $trimmed));
            $rows[] = $cells;
        }

        if (empty($rows)) {
            return '';
        }

        $header = array_shift($rows);
        $thead = '<thead class="bg-[#e8f2ec] text-ink font-bold"><tr>';
        foreach ($header as $cell) {
            $cellHtml = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $cell);
            $thead .= '<th class="px-2.5 py-1.5 text-left border-b border-line/80">'.$cellHtml.'</th>';
        }
        $thead .= '</tr></thead>';

        $tbody = '<tbody class="divide-y divide-line/60 bg-white">';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach ($row as $cell) {
                $cellHtml = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $cell);
                $tbody .= '<td class="px-2.5 py-1.5 align-top">'.$cellHtml.'</td>';
            }
            $tbody .= '</tr>';
        }
        $tbody .= '</tbody>';

        return '<div class="overflow-x-auto my-2 rounded-lg border border-line/80"><table class="w-full text-xs border-collapse">'.$thead.$tbody.'</table></div>';
    }
}
