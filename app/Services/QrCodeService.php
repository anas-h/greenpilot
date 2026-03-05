<?php

namespace App\Services;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

class QrCodeService
{
    public function generate(string $data, int $size = 300): string
    {
        return QrCode::format('svg')->size($size)->generate($data);
    }

    public function generateForConteneur(string $codeQr, int $size = 300): string
    {
        return $this->generate($codeQr, $size);
    }
}
