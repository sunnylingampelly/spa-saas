<?php

namespace App\Domain\Customers\Services;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class CustomerQrCodeService
{
    public function svgFor(string $customerCode): string
    {
        $renderer = new ImageRenderer(new RendererStyle(220), new SvgImageBackEnd);

        return (new Writer($renderer))->writeString($customerCode);
    }
}
