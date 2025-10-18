#!/bin/bash
# Script de conversion SVG → PNG et optimisation pour Open Graph
# Usage: ./convert.sh

# Mode strict sauf pour les conversions individuelles
set -u

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "=== Vérification des outils requis ==="

# Outils bloquants (au moins un outil de conversion SVG requis)
CONVERTER=""
if command -v rsvg-convert &> /dev/null; then
    CONVERTER="rsvg-convert"
    echo "✓ rsvg-convert disponible"
elif command -v inkscape &> /dev/null; then
    CONVERTER="inkscape"
    echo "✓ inkscape disponible"
elif command -v convert &> /dev/null; then
    CONVERTER="imagemagick"
    echo "✓ ImageMagick disponible"
else
    echo "❌ ERREUR: Aucun outil de conversion SVG → PNG trouvé"
    echo ""
    echo "Installez l'un de ces outils (Debian/Ubuntu):"
    echo "  sudo apt install librsvg2-bin    # Recommandé (léger et rapide)"
    echo "  sudo apt install inkscape        # Alternative"
    echo "  sudo apt install imagemagick     # Alternative"
    exit 1
fi

# Outils d'optimisation (optionnels)
OPTIMIZERS=()
MISSING_OPTIMIZERS=()

if command -v optipng &> /dev/null; then
    OPTIMIZERS+=("optipng")
    echo "✓ optipng disponible"
else
    MISSING_OPTIMIZERS+=("optipng")
fi

if command -v pngcrush &> /dev/null; then
    OPTIMIZERS+=("pngcrush")
    echo "✓ pngcrush disponible"
else
    MISSING_OPTIMIZERS+=("pngcrush")
fi

if command -v pngquant &> /dev/null; then
    OPTIMIZERS+=("pngquant")
    echo "✓ pngquant disponible"
else
    MISSING_OPTIMIZERS+=("pngquant")
fi

echo ""
echo "=== Conversion SVG → PNG ==="

# Fonction de conversion selon l'outil disponible
convert_svg() {
    local svg_file="$1"
    local png_file="$2"
    local width="$3"
    local height="$4"

    case "$CONVERTER" in
        rsvg-convert)
            rsvg-convert "$svg_file" -w "$width" -h "$height" -o "$png_file" 2>/dev/null
            ;;
        inkscape)
            inkscape "$svg_file" --export-type=png --export-filename="$png_file" \
                     --export-width="$width" --export-height="$height" 2>/dev/null
            ;;
        imagemagick)
            convert -background none "$svg_file" -resize "${width}x${height}" "$png_file" 2>/dev/null
            ;;
    esac
}

# Compteurs
converted=0
failed=0

# Conversion de tous les SVG
for svg_file in *.svg; do
    [ -f "$svg_file" ] || continue

    # Déterminer les dimensions selon le nom du fichier
    if [[ "$svg_file" == *"-square.svg" ]]; then
        width=1200
        height=1200
    else
        width=1200
        height=630
    fi

    png_file="${svg_file%.svg}.png"

    echo "Conversion: $svg_file → $png_file (${width}×${height})"

    if convert_svg "$svg_file" "$png_file" "$width" "$height"; then
        ((converted++))
    else
        echo "  ⚠️  Échec de conversion"
        ((failed++))
    fi
done

echo ""
echo "Conversions réussies: $converted"
[ $failed -gt 0 ] && echo "Conversions échouées: $failed"

# Optimisation des PNG si des outils sont disponibles
if [ ${#OPTIMIZERS[@]} -gt 0 ]; then
    echo ""
    echo "=== Optimisation des PNG ==="

    for png_file in *.png; do
        [ -f "$png_file" ] || continue

        original_size=$(stat -c%s "$png_file" 2>/dev/null || stat -f%z "$png_file" 2>/dev/null)
        echo "Optimisation: $png_file"

        # optipng (lossless)
        if [[ " ${OPTIMIZERS[@]} " =~ " optipng " ]]; then
            optipng -quiet -o2 "$png_file" 2>/dev/null || true
        fi

        # pngcrush (lossless)
        if [[ " ${OPTIMIZERS[@]} " =~ " pngcrush " ]]; then
            temp_file="${png_file}.tmp"
            pngcrush -q "$png_file" "$temp_file" 2>/dev/null && mv "$temp_file" "$png_file" || rm -f "$temp_file"
        fi

        # pngquant (lossy mais haute qualité) - optionnel
        # Décommentez si vous voulez une compression encore plus forte
        # if [[ " ${OPTIMIZERS[@]} " =~ " pngquant " ]]; then
        #     pngquant --quality=85-95 --skip-if-larger --force --ext .png "$png_file" 2>/dev/null || true
        # fi

        new_size=$(stat -c%s "$png_file" 2>/dev/null || stat -f%z "$png_file" 2>/dev/null)

        # Formatage des tailles
        original_human=$(numfmt --to=iec-i --suffix=B $original_size 2>/dev/null || echo "$original_size octets")
        new_human=$(numfmt --to=iec-i --suffix=B $new_size 2>/dev/null || echo "$new_size octets")

        saved=$((original_size - new_size))
        if [ $saved -gt 0 ]; then
            percent=$((saved * 100 / original_size))
            saved_human=$(numfmt --to=iec-i --suffix=B $saved 2>/dev/null || echo "$saved octets")
            echo "  $original_human → $new_human  ✓ Optimisé : -${percent}% -$saved_human"
        else
            echo "  $original_human → $new_human  ✗ Original conservé (optimisation inefficace)"
        fi
    done
fi

echo ""
echo "=== Résumé ==="
echo "✓ Conversion terminée avec $CONVERTER"
if [ ${#OPTIMIZERS[@]} -gt 0 ]; then
    echo "✓ Optimisation effectuée avec: ${OPTIMIZERS[*]}"
fi

# Afficher les outils manquants (non bloquants)
if [ ${#MISSING_OPTIMIZERS[@]} -gt 0 ]; then
    echo ""
    echo "📝 Optimisation supplémentaire possible avec (optionnel):"
    if [[ " ${MISSING_OPTIMIZERS[@]} " =~ " optipng " ]]; then
        echo "  sudo apt install optipng      # Optimisation lossless (recommandé)"
    fi
    if [[ " ${MISSING_OPTIMIZERS[@]} " =~ " pngcrush " ]]; then
        echo "  sudo apt install pngcrush     # Optimisation lossless alternative"
    fi
    if [[ " ${MISSING_OPTIMIZERS[@]} " =~ " pngquant " ]]; then
        echo "  sudo apt install pngquant     # Compression lossy haute qualité"
    fi
fi

echo ""
echo "✓ Terminé !"
