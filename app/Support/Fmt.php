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

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }

                // Headers
                if (preg_match('/^###\s+(.*)$/', $line, $m)) {
                    $paraHtml .= '<div class="font-bold text-ink text-sm mt-2 mb-0.5">'.$m[1].'</div>';
                } elseif (preg_match('/^##\s+(.*)$/', $line, $m)) {
                    $paraHtml .= '<div class="font-bold text-ink text-base mt-2.5 mb-0.5">'.$m[1].'</div>';
                }
                // Bullet list
                elseif (preg_match('/^[\*\-]\s+(.*)$/', $line, $m)) {
                    $item = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $m[1]);
                    $paraHtml .= '<div class="flex items-start gap-2 my-0.5 text-ink"><span class="text-brand font-bold text-xs mt-0.5">•</span><span>'.$item.'</span></div>';
                }
                // Numbered list
                elseif (preg_match('/^(\d+)\.\s+(.*)$/', $line, $m)) {
                    $item = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $m[2]);
                    $paraHtml .= '<div class="flex items-start gap-2 my-0.5 text-ink"><span class="text-brand font-bold text-xs mt-0.5">'.$m[1].'.</span><span>'.$item.'</span></div>';
                }
                // Normal text line
                else {
                    $line = preg_replace('/(?<!\w)\*(.*?)\*(?!\w)/', '<em class="italic text-muted">$1</em>', $line);
                    $paraHtml .= ($paraHtml !== '' && ! str_ends_with($paraHtml, '</div>') ? '<br>' : '').$line;
                }
            }

            $htmlBlocks[] = '<div class="mb-2 last:mb-0 leading-relaxed text-ink">'.$paraHtml.'</div>';
        }

        return implode('', $htmlBlocks);
    }
}
