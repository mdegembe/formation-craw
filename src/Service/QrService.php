<?php

declare(strict_types=1);

namespace Craw\Service;

use Craw\Core\Logger;
use Craw\Domain\SepaQrPayload;
use Endroid\QrCode\Builder\Builder;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Writer\SvgWriter;

final class QrService
{
    /**
     * QR de virement SEPA (EPC069-12).
     *
     * Le niveau de correction M est celui recommande par la norme : suffisant
     * pour un scan fiable sans gonfler la taille du code.
     */
    public static function sepaPng(
        string $beneficiary,
        string $iban,
        int $amountCents,
        string $remittance,
        string $bic = '',
        int $size = 320
    ): string {
        $payload = SepaQrPayload::build($beneficiary, $iban, $amountCents, $remittance, $bic);

        return self::png($payload, $size, ErrorCorrectionLevel::Medium);
    }

    public static function png(
        string $data,
        int $size = 320,
        ErrorCorrectionLevel $level = ErrorCorrectionLevel::Medium
    ): string {
        return Builder::create()
            ->writer(new PngWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel($level)
            ->size($size)
            ->margin(12)
            ->roundBlockSizeMode(RoundBlockSizeMode::Margin)
            ->build()
            ->getString();
    }

    public static function svg(string $data, int $size = 320): string
    {
        return Builder::create()
            ->writer(new SvgWriter())
            ->data($data)
            ->encoding(new Encoding('UTF-8'))
            ->errorCorrectionLevel(ErrorCorrectionLevel::Medium)
            ->size($size)
            ->margin(8)
            ->build()
            ->getString();
    }

    /** Image encodee en data URI, pour l'inclure dans un PDF ou un courriel. */
    public static function dataUri(string $data, int $size = 240): string
    {
        try {
            return 'data:image/png;base64,' . base64_encode(self::png($data, $size));
        } catch (\Throwable $exception) {
            Logger::error('Echec de generation du QR code.', ['message' => $exception->getMessage()]);

            return '';
        }
    }

    public static function sepaDataUri(
        string $beneficiary,
        string $iban,
        int $amountCents,
        string $remittance,
        string $bic = '',
        int $size = 240
    ): string {
        try {
            return 'data:image/png;base64,' . base64_encode(
                self::sepaPng($beneficiary, $iban, $amountCents, $remittance, $bic, $size)
            );
        } catch (\Throwable $exception) {
            Logger::error('Echec de generation du QR SEPA.', ['message' => $exception->getMessage()]);

            return '';
        }
    }
}
