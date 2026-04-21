<?php

function dreamEventsBrandTemplate(string $title, string $contentHtml): string
{
    return '<!doctype html><html><body style="margin:0;background:#090f1f;font-family:Inter,Arial,sans-serif;color:#f8fbff;">'
        . '<div style="max-width:640px;margin:24px auto;padding:24px;background:rgba(24,31,52,.95);border:1px solid rgba(255,255,255,.12);border-radius:16px;">'
        . '<h2 style="margin:0 0 12px;color:#ffffff;">DreamEvents</h2>'
        . '<h3 style="margin:0 0 16px;color:#c4b5fd;">' . htmlspecialchars($title) . '</h3>'
        . '<div style="color:#dbe5ff;line-height:1.6;">' . $contentHtml . '</div>'
        . '<p style="margin-top:20px;color:#94a3b8;font-size:12px;">This is an automated message from DreamEvents.</p>'
        . '</div></body></html>';
}

function sendSystemEmail(string $toEmail, string $toName, string $subject, string $htmlContent): bool
{
    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        return false;
    }

    $from = 'no-reply@dreamevents.local';
    $headers = [
        'MIME-Version: 1.0',
        'Content-type: text/html; charset=UTF-8',
        'From: DreamEvents <' . $from . '>',
        'Reply-To: ' . $from,
        'X-Mailer: PHP/' . phpversion(),
    ];

    return @mail($toEmail, $subject, $htmlContent, implode("\r\n", $headers));
}
