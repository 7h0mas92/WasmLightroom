// wasm/filters.cpp
// Moteur de filtres WebAssembly pour WasmLightroom

#include <cstdint>
#include <cmath>
#include <algorithm>
#include <cstring>

extern "C" {

// ============================================================
// FILTRES GLOBAUX
// ============================================================

/**
 * Niveaux de gris
 * @param pixels     Pointeur vers les données RGBA
 * @param numPixels  Nombre de pixels (width * height)
 * @param intensity  Intensité du filtre [0-100]
 */
void apply_grayscale(uint8_t* pixels, int numPixels, int intensity) {
    float factor = intensity / 100.0f;
    for (int i = 0; i < numPixels; i++) {
        int offset = i * 4;
        uint8_t r = pixels[offset];
        uint8_t g = pixels[offset + 1];
        uint8_t b = pixels[offset + 2];

        // Luminance pondérée (BT.709)
        uint8_t gray = static_cast<uint8_t>(0.2126f * r + 0.7152f * g + 0.0722f * b);

        pixels[offset]     = static_cast<uint8_t>(r + factor * (gray - r));
        pixels[offset + 1] = static_cast<uint8_t>(g + factor * (gray - g));
        pixels[offset + 2] = static_cast<uint8_t>(b + factor * (gray - b));
        // Alpha inchangé
    }
}

/**
 * Sépia
 */
void apply_sepia(uint8_t* pixels, int numPixels, int intensity) {
    float factor = intensity / 100.0f;
    for (int i = 0; i < numPixels; i++) {
        int offset = i * 4;
        uint8_t r = pixels[offset];
        uint8_t g = pixels[offset + 1];
        uint8_t b = pixels[offset + 2];

        float sepiaR = std::min(255.0f, 0.393f * r + 0.769f * g + 0.189f * b);
        float sepiaG = std::min(255.0f, 0.349f * r + 0.686f * g + 0.168f * b);
        float sepiaB = std::min(255.0f, 0.272f * r + 0.534f * g + 0.131f * b);

        pixels[offset]     = static_cast<uint8_t>(r + factor * (sepiaR - r));
        pixels[offset + 1] = static_cast<uint8_t>(g + factor * (sepiaG - g));
        pixels[offset + 2] = static_cast<uint8_t>(b + factor * (sepiaB - b));
    }
}

/**
 * Inversion
 */
void apply_invert(uint8_t* pixels, int numPixels, int intensity) {
    float factor = intensity / 100.0f;
    for (int i = 0; i < numPixels; i++) {
        int offset = i * 4;
        uint8_t r = pixels[offset];
        uint8_t g = pixels[offset + 1];
        uint8_t b = pixels[offset + 2];

        pixels[offset]     = static_cast<uint8_t>(r + factor * ((255 - r) - r));
        pixels[offset + 1] = static_cast<uint8_t>(g + factor * ((255 - g) - g));
        pixels[offset + 2] = static_cast<uint8_t>(b + factor * ((255 - b) - b));
    }
}

/**
 * Luminosité [-100, +100]
 */
void apply_brightness(uint8_t* pixels, int numPixels, int value) {
    for (int i = 0; i < numPixels; i++) {
        int offset = i * 4;
        for (int c = 0; c < 3; c++) {
            int val = pixels[offset + c] + value;
            pixels[offset + c] = static_cast<uint8_t>(std::max(0, std::min(255, val)));
        }
    }
}

/**
 * Contraste [-100, +100]
 */
void apply_contrast(uint8_t* pixels, int numPixels, int value) {
    float factor = (259.0f * (value + 255.0f)) / (255.0f * (259.0f - value));
    for (int i = 0; i < numPixels; i++) {
        int offset = i * 4;
        for (int c = 0; c < 3; c++) {
            float newVal = factor * (pixels[offset + c] - 128.0f) + 128.0f;
            pixels[offset + c] = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, newVal)));
        }
    }
}

// ============================================================
// FILTRE RADIAL (local, appliqué au clic)
// ============================================================

/**
 * Applique un filtre en cercle avec dégradé
 * @param pixels     Données RGBA
 * @param width      Largeur de l'image
 * @param height     Hauteur de l'image
 * @param centerX    Centre X du cercle
 * @param centerY    Centre Y du cercle
 * @param radius     Rayon du cercle
 * @param filterType 0=grayscale, 1=sepia, 2=invert, 3=brightness
 * @param intensity  Intensité [0-100]
 */
void apply_radial_filter(uint8_t* pixels, int width, int height,
                          int centerX, int centerY, int radius,
                          int filterType, int intensity) {
    float maxDist = static_cast<float>(radius);

    int startY = std::max(0, centerY - radius);
    int endY   = std::min(height, centerY + radius);
    int startX = std::max(0, centerX - radius);
    int endX   = std::min(width, centerX + radius);

    for (int y = startY; y < endY; y++) {
        for (int x = startX; x < endX; x++) {
            float dx = static_cast<float>(x - centerX);
            float dy = static_cast<float>(y - centerY);
            float dist = std::sqrt(dx * dx + dy * dy);

            if (dist > maxDist) continue;

            // Dégradé doux vers les bords
            float localIntensity = (intensity / 100.0f) * (1.0f - (dist / maxDist));
            int offset = (y * width + x) * 4;

            uint8_t r = pixels[offset];
            uint8_t g = pixels[offset + 1];
            uint8_t b = pixels[offset + 2];

            float nr, ng, nb;

            switch (filterType) {
                case 0: { // Grayscale
                    float gray = 0.2126f * r + 0.7152f * g + 0.0722f * b;
                    nr = r + localIntensity * (gray - r);
                    ng = g + localIntensity * (gray - g);
                    nb = b + localIntensity * (gray - b);
                    break;
                }
                case 1: { // Sepia
                    float sr = std::min(255.0f, 0.393f * r + 0.769f * g + 0.189f * b);
                    float sg = std::min(255.0f, 0.349f * r + 0.686f * g + 0.168f * b);
                    float sb = std::min(255.0f, 0.272f * r + 0.534f * g + 0.131f * b);
                    nr = r + localIntensity * (sr - r);
                    ng = g + localIntensity * (sg - g);
                    nb = b + localIntensity * (sb - b);
                    break;
                }
                case 2: { // Invert
                    nr = r + localIntensity * ((255 - r) - r);
                    ng = g + localIntensity * ((255 - g) - g);
                    nb = b + localIntensity * ((255 - b) - b);
                    break;
                }
                case 3: { // Brightness
                    float adj = localIntensity * 100.0f;
                    nr = r + adj;
                    ng = g + adj;
                    nb = b + adj;
                    break;
                }
                default:
                    nr = r; ng = g; nb = b;
            }

            pixels[offset]     = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, nr)));
            pixels[offset + 1] = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, ng)));
            pixels[offset + 2] = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, nb)));
        }
    }
}

// ============================================================
// FILTRE MASQUÉ (pinceau / masque de fusion)
// ============================================================

/**
 * Applique un filtre uniquement là où le masque est non-nul.
 * Le masque est une image en niveaux de gris (un canal, même taille).
 * La valeur du masque [0-255] module l'intensité du filtre.
 *
 * @param pixels     Données RGBA de l'image
 * @param mask       Données du masque (1 octet par pixel, 0-255)
 * @param numPixels  Nombre de pixels
 * @param filterType 0=grayscale, 1=sepia, 2=invert, 3=brightness
 * @param intensity  Intensité globale [0-100]
 */
void apply_masked_filter(uint8_t* pixels, uint8_t* mask, int numPixels,
                          int filterType, int intensity) {
    float globalFactor = intensity / 100.0f;

    for (int i = 0; i < numPixels; i++) {
        float maskVal = mask[i] / 255.0f;
        float localFactor = globalFactor * maskVal;

        if (localFactor < 0.001f) continue;

        int offset = i * 4;
        uint8_t r = pixels[offset];
        uint8_t g = pixels[offset + 1];
        uint8_t b = pixels[offset + 2];

        float nr, ng, nb;

        switch (filterType) {
            case 0: {
                float gray = 0.2126f * r + 0.7152f * g + 0.0722f * b;
                nr = r + localFactor * (gray - r);
                ng = g + localFactor * (gray - g);
                nb = b + localFactor * (gray - b);
                break;
            }
            case 1: {
                float sr = std::min(255.0f, 0.393f * r + 0.769f * g + 0.189f * b);
                float sg = std::min(255.0f, 0.349f * r + 0.686f * g + 0.168f * b);
                float sb = std::min(255.0f, 0.272f * r + 0.534f * g + 0.131f * b);
                nr = r + localFactor * (sr - r);
                ng = g + localFactor * (sg - g);
                nb = b + localFactor * (sb - b);
                break;
            }
            case 2: {
                nr = r + localFactor * ((255 - r) - r);
                ng = g + localFactor * ((255 - g) - g);
                nb = b + localFactor * ((255 - b) - b);
                break;
            }
            case 3: {
                float adj = localFactor * 100.0f;
                nr = r + adj;
                ng = g + adj;
                nb = b + adj;
                break;
            }
            default:
                nr = r; ng = g; nb = b;
        }

        pixels[offset]     = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, nr)));
        pixels[offset + 1] = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, ng)));
        pixels[offset + 2] = static_cast<uint8_t>(std::max(0.0f, std::min(255.0f, nb)));
    }
}

// ============================================================
// HISTOGRAMME
// ============================================================

/**
 * Calcule l'histogramme des niveaux de gris (256 bins)
 * @param pixels     Données RGBA
 * @param numPixels  Nombre de pixels
 * @param histogram  Tableau de 256 ints (sortie)
 */
void compute_histogram(uint8_t* pixels, int numPixels, int* histogram) {
    // Reset
    for (int i = 0; i < 256; i++) histogram[i] = 0;

    for (int i = 0; i < numPixels; i++) {
        int offset = i * 4;
        uint8_t gray = static_cast<uint8_t>(
            0.2126f * pixels[offset] +
            0.7152f * pixels[offset + 1] +
            0.0722f * pixels[offset + 2]
        );
        histogram[gray]++;
    }
}

} // extern "C"
