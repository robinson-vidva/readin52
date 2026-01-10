<?php
/**
 * Generate PNG icons for PWA from the app branding
 * Run this script once to create the necessary icon files
 */

// Icon sizes needed for PWA/iOS
$sizes = [
    72, 96, 128, 144, 152, 167, 180, 192, 384, 512
];

$outputDir = __DIR__ . '/assets/images/';

// Ensure GD is available
if (!function_exists('imagecreatetruecolor')) {
    die("GD library is not installed. Cannot generate PNG icons.\n");
}

foreach ($sizes as $size) {
    $image = imagecreatetruecolor($size, $size);

    // Enable alpha blending
    imagealphablending($image, false);
    imagesavealpha($image, true);

    // Colors
    $brown = imagecolorallocate($image, 93, 64, 55);     // #5D4037
    $white = imagecolorallocate($image, 255, 255, 255);  // white
    $gold = imagecolorallocate($image, 255, 179, 0);     // #FFB300

    // Fill background with brown
    imagefilledrectangle($image, 0, 0, $size, $size, $brown);

    // Draw rounded corners (approximate with filled arcs)
    $radius = (int)($size * 0.1); // 10% corner radius

    // Calculate font sizes relative to icon size
    $mainFontSize = (int)($size * 0.35);  // "52" text
    $subFontSize = (int)($size * 0.10);   // "READ IN" text

    // Draw "52" text in center
    $mainText = "52";
    $mainBox = imagettfbbox($mainFontSize, 0, __DIR__ . '/assets/fonts/arial.ttf', $mainText);

    // Use built-in font if custom font not available
    // Calculate center positions
    $centerX = $size / 2;
    $centerY = $size / 2;

    // Draw "52" using built-in fonts (scaled by icon size)
    if ($size >= 128) {
        // Use imagestring for basic text
        $font = 5; // Largest built-in font
        $textWidth = imagefontwidth($font) * strlen($mainText);
        $textHeight = imagefontheight($font);

        // Draw "52" multiple times to make it bigger/bolder
        $scale = $size / 64;
        $tx = (int)(($size - 40 * $scale) / 2);
        $ty = (int)(($size - 30 * $scale) / 2);

        // Draw large "52" by drawing rectangles
        drawLargeText($image, $mainText, $white, $size);

        // Draw "READ IN" at bottom
        drawSmallText($image, "READ IN", $gold, $size);
    } else {
        // For smaller icons, just draw "52"
        $font = 5;
        $textWidth = imagefontwidth($font) * 2;
        $x = (int)(($size - $textWidth) / 2);
        $y = (int)(($size - imagefontheight($font)) / 2);
        imagestring($image, $font, $x, $y, "52", $white);
    }

    // Save the image
    $filename = $outputDir . "icon-{$size}.png";
    imagepng($image, $filename);
    imagedestroy($image);

    echo "Created: icon-{$size}.png\n";
}

echo "\nAll icons generated successfully!\n";

/**
 * Draw large "52" text
 */
function drawLargeText($image, $text, $color, $size) {
    $centerX = $size / 2;
    $centerY = $size / 2 - $size * 0.05;

    // Scale based on size
    $charWidth = $size * 0.22;
    $charHeight = $size * 0.45;
    $thickness = max(2, (int)($size * 0.06));

    $startX = $centerX - $charWidth;
    $startY = $centerY - $charHeight / 2;

    // Draw "5"
    draw5($image, $startX, $startY, $charWidth, $charHeight, $thickness, $color);

    // Draw "2"
    draw2($image, $startX + $charWidth + $thickness, $startY, $charWidth, $charHeight, $thickness, $color);
}

/**
 * Draw "5" digit
 */
function draw5($image, $x, $y, $w, $h, $t, $color) {
    // Top horizontal
    imagefilledrectangle($image, (int)$x, (int)$y, (int)($x + $w), (int)($y + $t), $color);
    // Left vertical (top half)
    imagefilledrectangle($image, (int)$x, (int)$y, (int)($x + $t), (int)($y + $h/2), $color);
    // Middle horizontal
    imagefilledrectangle($image, (int)$x, (int)($y + $h/2 - $t/2), (int)($x + $w), (int)($y + $h/2 + $t/2), $color);
    // Right vertical (bottom half)
    imagefilledrectangle($image, (int)($x + $w - $t), (int)($y + $h/2), (int)($x + $w), (int)($y + $h), $color);
    // Bottom horizontal
    imagefilledrectangle($image, (int)$x, (int)($y + $h - $t), (int)($x + $w), (int)($y + $h), $color);
}

/**
 * Draw "2" digit
 */
function draw2($image, $x, $y, $w, $h, $t, $color) {
    // Top horizontal
    imagefilledrectangle($image, (int)$x, (int)$y, (int)($x + $w), (int)($y + $t), $color);
    // Right vertical (top half)
    imagefilledrectangle($image, (int)($x + $w - $t), (int)$y, (int)($x + $w), (int)($y + $h/2), $color);
    // Middle horizontal
    imagefilledrectangle($image, (int)$x, (int)($y + $h/2 - $t/2), (int)($x + $w), (int)($y + $h/2 + $t/2), $color);
    // Left vertical (bottom half)
    imagefilledrectangle($image, (int)$x, (int)($y + $h/2), (int)($x + $t), (int)($y + $h), $color);
    // Bottom horizontal
    imagefilledrectangle($image, (int)$x, (int)($y + $h - $t), (int)($x + $w), (int)($y + $h), $color);
}

/**
 * Draw small "READ IN" text at bottom
 */
function drawSmallText($image, $text, $color, $size) {
    if ($size < 128) return;

    $font = ($size >= 256) ? 3 : 2;
    $textWidth = imagefontwidth($font) * strlen($text);
    $x = (int)(($size - $textWidth) / 2);
    $y = (int)($size * 0.75);

    imagestring($image, $font, $x, $y, $text, $color);
}
