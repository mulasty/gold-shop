# Sklep ze Złotem - WordPress + WooCommerce + Base Linker

Kompletny pakiet kodu dla sklepu internetowego sprzedającego złoto (bizuteria, sztabki, monety) z integracją Base Linker i social media.

## Tech Stack

| Komponent | Technologia |
|---|---|
| CMS | WordPress 6.6+ |
| Sklep | WooCommerce 9.3+ |
| Theme | **WoodMart** (demo Jewellery 2) + Child Theme |
| Base Linker | Base.com (natywna wtyczka) |
| Social Media | Meta (FB/IG), TikTok, Pinterest, YouTube Shopping |
| SEO | Rank Math SEO |
| Cache | LiteSpeed Cache + Cloudflare CDN |
| Bezpieczeństwo | Wordfence, Limit Login Attempts |
| Płatności | Przelewy24, Stripe, BLIK, PayPo |
| Ceny złota | API NBP (darmowe) / GoldAPI.io (live) |

## Struktura projektu

```
gold-shop/
├── .env.example                    # Zmienne środowiskowe
├── .htaccess                       # Konfiguracja Apache (cache, security, SSL)
├── robots.txt                      # SEO - dyrektywy dla botów
├── composer.json                   # Zależności PHP/WordPress
├── package.json                    # Skrypty npm
├── config/
│   └── wp-config-sample.php       # Konfiguracja WordPress
├── scripts/
│   ├── install.sh                  # Skrypt instalacyjny
│   └── configure-woocommerce.php   # Konfigurator WooCommerce
└── wp-content/
    └── themes/
        └── woodmart-child/
            ├── style.css           # Child Theme - deklaracja
            ├── functions.php       # Główna logika (ceny, pixele, Base Linker, WooCommerce)
            ├── inc/
            │   └── class-base-linker-integration.php
            └── woocommerce/
                ├── single-product/
                │   ├── price.php   # Dynamiczne ceny złota na stronie produktu
                │   └── meta.php    # Metadane (próba, waga, premia)
                ├── loop/
                │   └── price.php   # Dynamiczne ceny na liście produktów
                ├── cart/
                │   └── cart.php    # Koszyk z podsumowaniem złota
                └── checkout/
```

## Kluczowe funkcje

### 1. Dynamiczne ceny złota
- Automatyczne pobieranie cen z API NBP (darmowe, raz dziennie) lub GoldAPI.io (live)
- Shortcode `[gold_price unit="g" purity="585"]` - wyświetl cenę złota dowolnie na stronie
- REST API: `GET /wp-json/gold-shop/v1/prices` - endpoint dla aplikacji zewnętrznych
- Produkty z metadanych: waga + próba + premia = automatyczna kalkulacja ceny
- Kalkulator wykupu `[gold_buyback_calculator]` - wycena online
- Widget w panelu admina z bieżącymi notowaniami

### 2. Base Linker (Base.com)
- Natywna integracja przez wtyczkę Base.com z WordPress.org
- Klasa `Gold_Base_Linker_Integration` rozszerzająca WooCommerce Integration API
- Webhook REST API do synchronizacji zamówień, produktów, stanów magazynowych
- Auto-sync produktów przy zapisie
- Mapa statusów zamówień Base.com ↔ WooCommerce

### 3. Social Media Pixele
- **Meta Pixel** (Facebook/Instagram) - PageView, ViewContent, AddToCart, Purchase
- **TikTok Pixel** - PageView + eventy
- **Pinterest Tag** - PageView
- Gotowe eventy dla WooCommerce (koszyk, zakup, produkt)

### 4. WooCommerce
- Waluta: PLN z formatem polskim (1 234,56 zł)
- Podatki: VAT 23% (standard), VAT 8% (złoto inwestycyjne), zwolnienie (sztabki)
- Wysyłka: DPD/DHL/InPost z ubezpieczeniem dla wartościowych przesyłek
- Płatności: Przelewy24, BLIK, Stripe, PayPo
- Pola produktu: waga (gramy), próba (375-999), premia (%)

### 5. SEO
- Rank Math z dedykowanym Schema.org dla produktów złotych (material, weight)
- robots.txt zoptymalizowany pod e-commerce
- Struktura URL: przyjazna dla SEO

### 6. Bezpieczeństwo i wydajność
- .htaccess z nagłówkami security (HSTS, X-Frame, Content-Type)
- Kompresja GZIP
- Cache przeglądarki (1 rok dla statyków)
- Blokada dostępu do wrażliwych plików
- Przekierowanie HTTP → HTTPS

## Instalacja

### Wymagania
- PHP 8.1+
- MySQL 8.0+ / MariaDB 10.6+
- Apache z mod_rewrite
- Composer
- Node.js (opcjonalnie)

### Krok po kroku

1. **Przygotowanie serwera**
   ```bash
   git clone <repo-url> gold-shop
   cd gold-shop
   cp .env.example .env
   # Edytuj .env - uzupełnij dane bazy, domenę, klucze API
   ```

2. **Instalacja zależności**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

3. **Konfiguracja WordPress**
   ```bash
   cp config/wp-config-sample.php wp-config.php
   # Edytuj wp-config.php - uzupełnij dane bazy, klucze bezpieczeństwa
   ```

4. **Instalacja przez przeglądarkę**
   - Otwórz `https://twojadomena.pl/wp-admin/install.php`
   - Zainstaluj WordPress
   - W panelu → Wtyczki → aktywuj WooCommerce i pozostałe

5. **Theme**
   - Kup i zainstaluj WoodMart z ThemeForest
   - Aktywuj WoodMart Child
   - Zaimportuj demo **Jewellery 2** (najnowsze, dedykowane jubilerstwu)

6. **Konfiguracja wtyczek**
   - Skonfiguruj Rank Math (kreator → Sklep internetowy)
   - Podłącz Meta for WooCommerce (Facebook/Instagram)
   - Podłącz TikTok for WooCommerce
   - Podłącz Pinterest for WooCommerce
   - Zainstaluj i skonfiguruj Base.com wtyczkę

7. **Ustawienia child theme**
   - Przejdź do panelu admina → Złoto
   - Wybierz źródło cen złota (NBP lub GoldAPI.io)
   - Wpisz pixele social media
   - Skonfiguruj webhook Base.com

## API Endpoints

| Endpoint | Metoda | Opis |
|---|---|---|
| `/wp-json/gold-shop/v1/prices` | GET | Bieżące ceny złota (gram, uncja, kg) |
| `/wp-json/gold-shop/v1/baselinker/sync` | POST | Webhook synchronizacji Base.com |

## Shortcode'y

| Shortcode | Parametry | Opis |
|---|---|---|
| `[gold_price unit="g" purity="585" label="Złoto"]` | unit: g/oz/kg, purity: 375-999 | Wyświetla aktualną cenę złota |
| `[gold_buyback_calculator]` | brak | Interaktywny kalkulator wykupu złota |

## Zarządzanie produktami złotymi

Każdy produkt może być oznaczony jako "Produkt złoty" z polami:
- **Waga** (gramy)
- **Próba** (375/585/750/916/999)
- **Premia** (%) - marża ponad cenę rynkową

Cena produktu jest automatycznie kalkulowana:
```
Cena = cena_rynkowa_złota × (próba/1000) × waga × (1 + premia/100)
```

## Harmonogram wdrożenia

| Faza | Zadania | Czas |
|---|---|---|
| 1 | Zakup hostingu VPS, domeny, SSL | 1 dzień |
| 2 | Instalacja WordPress + WooCommerce + theme | 2 dni |
| 3 | Konfiguracja child theme (ceny złota, pixele, Base Linker) | 2 dni |
| 4 | Integracja Base.com + marketplace | 3 dni |
| 5 | Social media (katalogi, pixele, sklepy) | 3 dni |
| 6 | Content (blog, opisy produktów, zdjęcia) | 7 dni |
| 7 | Testy + optymalizacja wydajności | 3 dni |
| 8 | Start | 1 dzień |
| **Razem** | | **~22 dni** |

## Utrzymanie miesięczne

| Element | Koszt (PLN) |
|---|---|
| Hosting VPS | 100-200 |
| WoodMart licencja | ~20/mies. (jednorazowo $59) |
| Base.com | 49-199 |
| GoldAPI.io (opcjonalnie) | ~30 |
| Domena + SSL | ~10 |
| Developer (opieka) | 500-1000 |
| **Razem** | **~700-1500 PLN/mies.** |
