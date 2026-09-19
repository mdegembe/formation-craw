<?php

declare(strict_types=1);

/**
 * Fragments HTML reutilisables dans les courriels.
 *
 * Definis comme fonctions plutot que comme partials : dans un courriel, tout
 * doit etre en ligne, et l'imbrication de gabarits complique inutilement.
 */
if (!function_exists('email_button')) {
    function email_button(string $label, string $href): string
    {
        return sprintf(
            '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:20px 0;">'
            . '<tr><td style="background-color:#198754;border-radius:50rem;">'
            . '<a href="%s" style="display:inline-block;padding:13px 28px;color:#ffffff;'
            . 'font-weight:600;font-size:15px;text-decoration:none;">%s</a>'
            . '</td></tr></table>',
            e($href),
            e($label)
        );
    }
}

if (!function_exists('email_rows')) {
    /**
     * Tableau cle / valeur, utilise pour les coordonnees bancaires.
     *
     * @param array<string, string> $rows
     */
    function email_rows(array $rows, bool $highlightLast = false): string
    {
        $html = '<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"'
            . ' style="border-collapse:collapse;margin:16px 0;">';

        $keys = array_keys($rows);
        $lastKey = end($keys);

        foreach ($rows as $label => $value) {
            $isLast = $highlightLast && $label === $lastKey;

            $html .= sprintf(
                '<tr>'
                . '<td style="padding:10px 12px;border:1px solid #dee2e6;background-color:#f8f9fa;'
                . 'font-size:13px;color:#5c636a;width:42%%;">%s</td>'
                . '<td style="padding:10px 12px;border:1px solid %s;background-color:%s;'
                . 'font-size:15px;font-weight:700;font-family:monospace;">%s</td>'
                . '</tr>',
                e($label),
                $isLast ? '#198754' : '#dee2e6',
                $isLast ? '#d1e7dd' : '#ffffff',
                e($value)
            );
        }

        return $html . '</table>';
    }
}

if (!function_exists('email_note')) {
    function email_note(string $text, string $tone = 'info'): string
    {
        [$background, $border, $color] = match ($tone) {
            'success' => ['#d1e7dd', '#a3cfbb', '#0f5132'],
            'warning' => ['#fff3cd', '#ffe69c', '#664d03'],
            'danger' => ['#f8d7da', '#f1aeb5', '#842029'],
            default => ['#cfe2ff', '#9ec5fe', '#084298'],
        };

        return sprintf(
            '<table role="presentation" width="100%%" cellpadding="0" cellspacing="0" border="0"'
            . ' style="margin:16px 0;"><tr><td style="padding:14px 16px;background-color:%s;'
            . 'border:1px solid %s;border-radius:6px;color:%s;font-size:14px;">%s</td></tr></table>',
            $background,
            $border,
            $color,
            $text
        );
    }
}
