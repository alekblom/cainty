<?php

namespace Cainty\Shortcodes;

/**
 * Built-in [table] Shortcode
 *
 * Usage: [table caption="Comparison"]Header1|Header2\nVal1|Val2[/table]
 * Prefix cell with ~ for highlight: ~Highlighted|Normal
 */
class TableShortcode
{
    public static function render(array $attrs, string $content, array $context = []): string
    {
        $caption = $attrs['caption'] ?? '';
        $content = trim($content);

        if (empty($content)) {
            return '';
        }

        $rows = preg_split('/\r?\n/', $content);
        $rows = array_filter($rows, fn($r) => trim($r) !== '');

        if (empty($rows)) {
            return '';
        }

        $html = '<div class="cainty-table-wrapper">';

        if ($caption) {
            $html .= '<div class="cainty-table-caption">' . htmlspecialchars($caption) . '</div>';
        }

        $html .= '<table class="cainty-table"><thead><tr>';

        // First row is the header
        $headerCells = explode('|', array_shift($rows));
        foreach ($headerCells as $cell) {
            $cell = trim($cell);
            $cell = ltrim($cell, '~');
            $html .= '<th>' . htmlspecialchars($cell) . '</th>';
        }

        $html .= '</tr></thead><tbody>';

        // Remaining rows are data
        foreach ($rows as $row) {
            $cells = explode('|', $row);
            $html .= '<tr>';
            foreach ($cells as $cell) {
                $cell = trim($cell);
                $highlight = false;
                if (str_starts_with($cell, '~')) {
                    $highlight = true;
                    $cell = ltrim($cell, '~');
                }
                $class = $highlight ? ' class="highlight"' : '';
                $html .= "<td{$class}>" . htmlspecialchars($cell) . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return $html;
    }
}
