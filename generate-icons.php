<?php
/**
 * Generate PNG icons for PWA
 * Creates simple, solid icons with "52" text
 */

$sizes = [72, 96, 128, 144, 152, 167, 180, 192, 384, 512];
$outputDir = __DIR__ . '/assets/images/';

// Colors
$bgColor = [93, 64, 55];      // #5D4037 brown
$textColor = [255, 255, 255]; // white
$accentColor = [255, 179, 0]; // #FFB300 gold

foreach ($sizes as $size) {
    // Create true color image
    $image = imagecreatetruecolor($size, $size);

    // Allocate colors
    $brown = imagecolorallocate($image, $bgColor[0], $bgColor[1], $bgColor[2]);
    $white = imagecolorallocate($image, $textColor[0], $textColor[1], $textColor[2]);
    $gold = imagecolorallocate($image, $accentColor[0], $accentColor[1], $accentColor[2]);

    // Fill background
    imagefilledrectangle($image, 0, 0, $size - 1, $size - 1, $brown);

    // Draw "52" text centered
    $text = "52";

    // Calculate font size and position based on image size
    // Using built-in font (1-5, where 5 is largest)
    $font = 5;
    $charWidth = imagefontwidth($font);
    $charHeight = imagefontheight($font);

    // Scale factor for drawing large text
    $scale = $size / 64;

    // Draw "52" using rectangles (7-segment style)
    $digitWidth = (int)($size * 0.25);
    $digitHeight = (int)($size * 0.4);
    $thickness = max(2, (int)($size * 0.05));
    $gap = (int)($size * 0.05);

    $totalWidth = $digitWidth * 2 + $gap;
    $startX = (int)(($size - $totalWidth) / 2);
    $startY = (int)(($size - $digitHeight) / 2) - (int)($size * 0.05);

    // Draw "5"
    draw5($image, $startX, $startY, $digitWidth, $digitHeight, $thickness, $white);

    // Draw "2"
    draw2($image, $startX + $digitWidth + $gap, $startY, $digitWidth, $digitHeight, $thickness, $white);

    // Draw "READ IN" text below for larger icons
    if ($size >= 128) {
        $subText = "READ IN";
        $subY = $startY + $digitHeight + (int)($size * 0.08);
        $subFont = ($size >= 192) ? 3 : 2;
        $subWidth = imagefontwidth($subFont) * strlen($subText);
        $subX = (int)(($size - $subWidth) / 2);
        imagestring($image, $subFont, $subX, $subY, $subText, $gold);
    }

    // Save PNG
    $filename = $outputDir . "icon-{$size}.png";
    imagepng($image, $filename, 9); // Maximum compression
    imagedestroy($image);

    echo "Generated: icon-{$size}.png (" . filesize($filename) . " bytes)\n";
}

echo "\nDone! All icons generated.\n";

/**
 * Draw digit "5" using filled rectangles
 */
function draw5($image, $x, $y, $w, $h, $t, $color) {
    $halfH = (int)($h / 2);

    // Top horizontal bar
    imagefilledrectangle($image, (int)$x, (int)$y, (int)($x + $w), (int)($y + $t), $color);

    // Upper left vertical
    imagefilledrectangle($image, (int)$x, (int)$y, (int)($x + $t), (int)($y + $halfH), $color);

    // Middle horizontal bar
    imagefilledrectangle($image, (int)$x, (int)($y + $halfH - $t/2), (int)($x + $w), (int)($y + $halfH + $t/2), $color);

    // Lower right vertical
    imagefilledrectangle($image, (int)($x + $w - $t), (int)($y + $halfH), (int)($x + $w), (int)($y + $h), $color);

    // Bottom horizontal bar
    imagefilledrectangle($image, (int)$x, (int)($y + $h - $t), (int)($x + $w), (int)($y + $h), $color);
}

/**
 * Draw digit "2" using filled rectangles
 */
function draw2($image, $x, $y, $w, $h, $t, $color) {
    $halfH = (int)($h / 2);

    // Top horizontal bar
    imagefilledrectangle($image, (int)$x, (int)$y, (int)($x + $w), (int)($y + $t), $color);

    // Upper right vertical
    imagefilledrectangle($image, (int)($x + $w - $t), (int)$y, (int)($x + $w), (int)($y + $halfH), $color);

    // Middle horizontal bar
    imagefilledrectangle($image, (int)$x, (int)($y + $halfH - $t/2), (int)($x + $w), (int)($y + $halfH + $t/2), $color);

    // Lower left vertical
    imagefilledrectangle($image, (int)$x, (int)($y + $halfH), (int)($x + $t), (int)($y + $h), $color);

    // Bottom horizontal bar
    imagefilledrectangle($image, (int)$x, (int)($y + $h - $t), (int)($x + $w), (int)($y + $h), $color);
}
