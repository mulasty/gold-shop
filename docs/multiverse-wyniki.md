# Multiverse Tournament — WoodMart Configuration

## Zwycięzca: Universe 2 — "Conversion Machine" 🏆

**Strategia**: maksymalna konwersja, urgency, trust badges, microcopy

| Universe | Focus | Szacowany CR | AOV | Szybkość startu |
|----------|-------|--------------|-----|-----------------|
| **🏆 2 — Conversion Machine** | CR + optymalizacja | **3.0-4.5%** | 2500-4000 PLN | **Natychmiastowy** |
| 3 — Trust & Education | Content + trust | 3.5-5.0% | 4000-8000 PLN | Wolny (30 dni) |
| 1 — Luxury Premium | Brand premium | 2.0-3.0% | 3000-5000 PLN | Średni |

## Fuzja — rekomendowana konfiguracja hybrydowa

Łączymy najlepsze elementy wszystkich 3 wszechświatów:

### Faza 1 (dni 1-14) — Conversion Machine ⚡
- Czerwone CTA (#c0392b) z mikro-kopią "Kupuję bezpiecznie"
- Sticky add-to-cart + trust badges przy guziku
- Free shipping threshold bar w koszyku
- One-page checkout z progress barem
- [gold_ticker] w headerze (transparentność)
- [gold_price_alert] lead generation
- **Cel**: szybki start, pierwsze zamówienia, dane do optymalizacji

### Faza 2 (dni 15-30) — Trust & Education 🤝
- Blog: 10 artykułów inwestycyjnych
- Kalkulator wykupu [gold_buyback_calculator]
- Opinie klientów + Trustpilot
- Certyfikaty NBP + LBMA w stopce
- Live gold price chart na product page
- **Cel**: obniżenie CAC, budowanie autorytetu

### Faza 3 (dni 31-90) — Luxury Premium ✨
- Pełna kolorystyka premium (#1A1A2E + #C9A84C)
- Cormorant Garamond headingi
- Transparent sticky header
- Program poleceń [gold_referral]
- Exit-intent popup z lead magnet
- **Cel**: maksymalizacja AOV i LTV, skalowanie

## Kluczowe taktyki konwersji (priority)

| # | Taktyka | Universe | CR boost |
|---|---------|----------|----------|
| 1 | Trust badges + certyfikat NBP przy CTA | U2 | +15-25% |
| 2 | Sticky add-to-cart z microcopy | U2 | +12-20% |
| 3 | Live gold ticker w headerze | U3 | +10-15% |
| 4 | Free shipping threshold bar | U2 | +8-15% |
| 5 | One-page checkout | U2 | +10-18% |
| 6 | Gold price chart (7d FOMO) | U3 | +5-10% |
| 7 | Blog edukacyjny (10 artykułów) | U3 | +8-12% SEO |
| 8 | Kalkulator wykupu | U3 | +5-8% |
| 9 | Price anchoring (cena vs rynek) | U1 | +5-10% |
| 10 | Program poleceń | U1 | +2-5% |

## Konfiguracja WoodMart — ostateczna

### Kolory
- **Primary**: #c0392b (CTA, przyciski)
- **Secondary**: #C9A84C (trust badges, certyfikaty)
- **Background**: #F8F6F1 (ciepła biel)
- **Header scroll**: rgba(255,255,255,0.92) + blur(12px)
- **Text**: #1A1A2E (granat)
- **Footer**: #0D0D1A (ciemny)

### Header
- Sticky, 70px wysokości
- Logo + [gold_ticker] + Menu (4 pozycje) + Telefon + Mini-cart
- Progress bar pod headerem "Darmowa dostawa od 5000 zł"

### Product Page
- Sticky add-to-cart (pojawia się po scrolu)
- Trust badges NAD i POD przyciskiem
- Countdown "Ostatnie X sztuk" (AJAX)
- Social proof "X osób ogląda"
- Gold price chart 7 dni
- FAQ accordion

### Homepage (kolejność)
1. Hero + countdown timer + CTA
2. Trust badges strip (5 ikon)
3. [gold_ticker] live
4. Bestsellery (grid 4)
5. Kalkulator zysku / buyback
6. Blog — 3 artykuły
7. Opinie klientów
8. Final CTA + newsletter

### Shortcode'y
- ✅ [gold_ticker] — header + homepage
- ✅ [gold_price] — sidebar produktu
- ✅ [gold_price_alert] — lead gen
- ✅ [gold_buyback_calculator] — strona /kalkulator
- ⏸️ [gold_referral] — włącz w F3
