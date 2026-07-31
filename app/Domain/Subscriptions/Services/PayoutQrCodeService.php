<?php

namespace App\Domain\Subscriptions\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class PayoutQrCodeService
{
    public function svgForAmount(float $amount, string $note): string
    {
        $upiId = config('subscriptions.payout.upi_id');
        $payeeName = rawurlencode((string) config('subscriptions.payout.account_name'));

        $uri = "upi://pay?pa={$upiId}&pn={$payeeName}&am={$amount}&cu=INR&tn=".rawurlencode($note);

        $renderer = new ImageRenderer(new RendererStyle(200), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($uri);
    }
}
