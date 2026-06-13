# Mapa strony — Sklep ze Złotem (WoodMart + WooCommerce)

```
/
├── Strona główna
│   ├── Hero z banerem + CTA "Sprawdź ceny"
│   ├── Live ticker złota [gold_ticker]
│   ├── Bestsellery / Polecane produkty
│   ├── Dlaczego my (zalety)
│   ├── Alert cenowy CTA [gold_price_alert]
│   └── Stopka: menu, newsletter, social media
│
├── /sklep/
│   ├── Archiwum produktów (kategorie: złoto inwestycyjne, monety, sztabki)
│   ├── Filtry AJAX (waga, próba, cena)
│   └── Sortowanie, widok siatka/lista
│
├── /produkt/{slug}/
│   ├── Dynamiczna cena złota (NBP/GoldAPI × próba × waga × premia)
│   ├── Galeria + zoom
│   ├── Warianty (próba 375/585/750/916/999, waga 1g/5g/10g/1oz/100g/1kg)
│   ├── Przycisk "Dodaj do koszyka" AJAX
│   ├── Udostępnij społecznościowo (FB, Twitter, WhatsApp)
│   └── Powiadom o zmianie ceny [gold_price_alert]
│
├── /koszyk/
│   ├── Produkty z dynamicznie przeliczaną ceną
│   ├── Kod promocyjny (w tym z programu poleceń)
│   └── Przejdź do kasy
│
├── /kasa/
│   ├── Formularz zamówienia (dane osobowe, adres)
│   ├── Płatność: Przelewy24, BLIK, karta, PayPal, szybki przelew
│   ├── Wysyłka: InPost, DPD, odbiór osobisty
│   └── Ref code: automatyczne przypisanie polecenia z cookie [gold_referral]
│
├── /zamowienie/{id}/
│   ├── Potwierdzenie zamówienia
│   ├── Google Ads conversion tracking
│   ├── GA4 purchase event → dataLayer
│   └── Szczegóły zamówienia + status płatności
│
├── /moje-konto/
│   ├── Panel klienta (zamówienia, adresy, dane)
│   ├── Link polecający [gold_referral]
│   ├── Alerty cenowe (lista subskrypcji)
│   └── Historia transakcji
│
├── /o-nas/
│   ├── O firmie, certyfikaty, próby złota
│   └── Dlaczego warto kupić złoto u nas
│
├── /blog/
│   ├── Artykuły: jak inwestować w złoto, ceny, trendy
│   ├── Analizy rynku, notowania
│   └── Poradnik kupującego
│
├── /kontakt/
│   ├── Formularz kontaktowy
│   ├── Dane firmy, NIP, adres
│   ├── Mapa (Google Maps)
│   └── Social media linki
│
├── /regulamin/
│   └── Regulamin sklepu (WooCommerce Pages)
│
├── /polityka-prywatnosci/
│   ├── RODO, cookies, Polityka prywatności
│   └── Klauzula informacyjna
│
├── /gold-feed.xml
│   └── Google Merchant Center / YouTube Shopping XML feed
│
└── /wp-json/gold-shop/v1/
    └── /baselinker/sync  ← webhook Base.com (POST, X-BASELINKER-TOKEN)
```

## Integracje

| Integracja | Typ | Cel | Status |
|------------|-----|-----|--------|
| **Base.com (Base Linker)** | REST API + webhook | Synchronizacja produktów, zamówień, magazynu | ✅ Zrobione |
| **NBP API** | REST (darmowe) | Ceny złota 1g/1oz/1kg, aktualizacja co godzinę | ✅ Zrobione |
| **GoldAPI.io** | REST (płatne) | Live pricing złota, 5-min interwał | ✅ Zrobione |
| **Meta Pixel (FB/IG)** | JavaScript pixel | PageView, ViewContent, AddToCart, Purchase | ✅ Zrobione |
| **TikTok Pixel** | JavaScript pixel | Pełny tracking zdarzeń | ✅ Zrobione |
| **Pinterest Tag** | JavaScript pixel | Tracking konwersji | ✅ Zrobione |
| **Google Tag Manager** | GTM kontener | Zarządzanie tagami, GA4, Google Ads | ✅ Zrobione |
| **GA4 (Google Analytics 4)** | dataLayer | view_item, add_to_cart, purchase enhanced events | ✅ Zrobione |
| **Google Ads** | Konwersja | Śledzenie zakupów na stronie "dziękujemy" | ✅ Zrobione |
| **Google Merchant Center** | XML feed | Feed produktów pod YouTube Shopping, GMC | ✅ Zrobione |
| **Live ticker złota** | Shortcode + HTML | Pływający pasek z notowaniami na froncie | ✅ Zrobione |
| **Price Alert** | Shortcode + AJAX + cron | Subskrypcja "powiadom gdy cena osiągnie X" | ✅ Zrobione |
| **Program poleceń** | Shortcode + cookie | 5% prowizji, link polecający, social share | ✅ Zrobione |
| **Przelewy24** | WooCommerce gateway | Płatności PLN: BLIK, karta, przelew | 🔧 Do konfiguracji |
| **InPost** | WooCommerce shipping | Paczkomaty InPost, DPD | 🔧 Do konfiguracji |
| **Rank Math SEO** | Wtyczka | Schema.org, mapka strony, meta tagi | 🔧 Do konfiguracji |
| **Wordfence** | Wtyczka | Firewall, skanowanie malware | 🔧 Do konfiguracji |
| **LiteSpeed Cache** | Wtyczka | Cache, CSS/JS minifikacja, CDN | 🔧 Do konfiguracji |
| **Cloudflare** | CDN | DNS, DDoS, SSL, cache edge | 🔧 Do konfiguracji |
| **YouTube Shopping** | Google Merchant Center | Produkty widoczne w YouTube | 🔧 Po aktywacji GMC |

## Shortcode'y dostępne w motywie

| Shortcode | Opis |
|-----------|------|
| `[gold_price]` | Wyświetla aktualną cenę złota za 1g |
| `[gold_ticker]` | Pływający pasek z notowaniami (1g, 1oz, zmiana %) |
| `[gold_price_alert]` | Formularz subskrypcji alertu cenowego |
| `[gold_referral]` | Panel programu poleceń (link, statystyki, social share) |
| `[gold_buyback_calculator]` | Kalkulator odkupu złota |

## Struktura URL dla webhooków

```
Webhook Base.com (POST):
  → https://twojsklep.pl/wp-json/gold-shop/v1/baselinker/sync
  → Headers: X-BASELINKER-TOKEN: {token}
  → Body: JSON { event: "order_create|order_update|product_sync|inventory_sync", data: {...} }

Google Merchant Feed (GET):
  → https://twojsklep.pl/gold-feed.xml
  → XML z wszystkimi produktami, dynamicznymi cenami, dostępnością
```

## Kategorie produktów

- **Złoto inwestycyjne** → Sztabki, monety bulionowe
- **Złoto mennicze** → Monety kolekcjonerskie
- **Srebro** → Sztabki, monety
- **Akcesoria** → Etui, certyfikaty, przechowywanie
