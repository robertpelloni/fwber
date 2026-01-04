# fwber App Icons

## Current Status
This directory should contain PNG icons in various sizes for the PWA manifest.

For now, the app uses SVG icons (`/favicon.svg` and `/icon.svg`) which are resolution-independent and work across all devices.

## Required Icon Sizes for Production
To fully support the PWA manifest, generate PNG icons in these sizes:

- icon-72x72.png
- icon-96x96.png
- icon-128x128.png
- icon-144x144.png
- icon-152x152.png
- icon-192x192.png (required for Android)
- icon-384x384.png
- icon-512x512.png (required for Android)

## Generating Icons
You can use the `/icon.svg` file as the source and generate PNGs using:

### Using ImageMagick:
```bash
for size in 72 96 128 144 152 192 384 512; do
  convert -background none -resize ${size}x${size} ../icon.svg icon-${size}x${size}.png
done
```

### Using Inkscape:
```bash
for size in 72 96 128 144 152 192 384 512; do
  inkscape ../icon.svg --export-filename=icon-${size}x${size}.png -w ${size} -h ${size}
done
```

### Using Online Tools:
- https://realfavicongenerator.net/
- https://www.favicon-generator.org/

## Maskable Icons
For better Android support, consider creating maskable versions of the 192x192 and 512x512 icons with padding (safe zone).

## Shortcut Icons
The manifest also references shortcut icons:
- shortcut-boards-96x96.png
- shortcut-nearby-96x96.png
- shortcut-profile-96x96.png

These can be created by adding relevant symbols to the base icon design.
