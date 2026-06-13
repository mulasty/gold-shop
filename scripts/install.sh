#!/bin/bash
# ==========================================
# Skrypt instalacyjny - Sklep ze złotem
# WordPress + WooCommerce + Base Linker
# ==========================================

set -e

echo "============================================"
echo " Instalacja Sklepu ze Złotem"
echo " WordPress + WooCommerce + Base Linker"
echo "============================================"
echo ""

# Sprawdzenie wymagań
echo "[CHECK] Sprawdzanie wymagań..."
command -v php >/dev/null 2>&1 || { echo "Błąd: PHP wymagane"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "Błąd: Composer wymagane"; exit 1; }
command -v mysql >/dev/null 2>&1 || { echo "Ostrzeżenie: mysql CLI nie znaleziony"; }

echo "[OK] Wymagania spełnione"
echo ""

# Kopiowanie konfiguracji
if [ ! -f .env ]; then
    cp .env.example .env
    echo "[INFO] Skopiowano .env.example do .env"
    echo "[INFO] Edytuj plik .env i uzupełnij swoje dane"
fi

# Instalacja zależności
echo "[INSTALL] Instalowanie zależności Composer..."
composer install --no-dev --optimize-autoloader

# Konfiguracja WordPress
echo "[CONFIG] Generowanie kluczy bezpieczeństwa..."
if [ ! -f wp-config.php ]; then
    cp config/wp-config-sample.php wp-config.php
    echo "[INFO] Skopiowano wp-config-sample.php do wp-config.php"
    echo "[INFO] Edytuj plik wp-config.php i uzupełnij dane bazy danych"
fi

# Pobranie WordPress jeśli nie istnieje
if [ ! -f wp/wp-settings.php ]; then
    echo "[INSTALL] Pobieranie WordPress..."
    wp core download --path=wp --allow-root 2>/dev/null || {
        echo "[WARN] WP-CLI nie znaleziony. WordPress musi być pobrany ręcznie."
        echo "[INFO] Możesz użyć: composer install"
    }
fi

# Konfiguracja WooCommerce
echo "[CONFIG] Uruchamianie konfiguratora WooCommerce..."
php scripts/configure-woocommerce.php

echo ""
echo "============================================"
echo " Instalacja zakończona!"
echo "============================================"
echo ""
echo "Kroki do wykonania przez przeglądarkę:"
echo "1. Otwórz https://twojadomena.pl/wp-admin/install.php"
echo "2. Zainstaluj WordPress"
echo "3. Zaloguj się do panelu administracyjnego"
echo "4. Przejdź do Wtyczki → Zainstaluj nowe"
echo "5. Zainstaluj i aktywuj:"
echo "   - WooCommerce"
echo "   - Rank Math SEO"
echo "   - Base.com (wtyczka Base)"
echo "   - Meta for WooCommerce"
echo "   - TikTok for WooCommerce"
echo "   - Pinterest for WooCommerce"
echo "   - LiteSpeed Cache"
echo "   - Wordfence Security"
echo "   - Przelewy24 WooCommerce"
echo "6. Przejdź do Wygląd → Motywy"
echo "7. Zainstaluj i aktywuj WoodMart (theme parent)"
echo "8. Aktywuj WoodMart Child"
echo "9. Zaimportuj demo 'Jewellery 2' z WoodMart"
echo "10. Skonfiguruj wtyczki zgodnie z dokumentacją"
echo ""
echo "Gotowe! Sklep ze złotem gotowy do działania."
