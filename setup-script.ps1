# GOLD SHOP NEWSLETTER SETUP SCRIPT
$siteUrl = "http://goldshop.mulagroup.eu"
$cookieFile = "$env:TEMP\wp_cookies.txt"
$adminUser = "mulagroup"
$adminPass = "HeHdU3n8kTXnmn@y"

Write-Output "=== Step 1: Login and get nonce ==="
$null = curl.exe -c $cookieFile -b $cookieFile -s -L "$siteUrl/wp-login.php" -d "log=$adminUser&pwd=$adminPass&wp-submit=Zaloguj&redirect_to=%2Fwp-admin%2F&testcookie=1" -o "$env:TEMP\wp_login_output.txt"
$adminHtml = curl.exe -b $cookieFile -s "$siteUrl/wp-admin/"
if ($adminHtml -match 'wpApiSettings.*?nonce.*?"([^"]+)"') {
  $nonce = $Matches[1]
  Write-Output "Nonce: $nonce"
} else {
  Write-Output "ERROR: Could not extract nonce"
  exit 1
}

function Call-API {
  param($Method, $Endpoint, $BodyJson)
  $url = "$siteUrl$Endpoint"
  if ($BodyJson) {
    $bodyFile = "$env:TEMP\wp_api_body.json"
    $BodyJson | Out-File -FilePath $bodyFile -Encoding utf8 -NoNewline
    $result = & curl.exe -b $cookieFile -H "X-WP-Nonce: $nonce" -H "Content-Type: application/json" -X $Method -d "@$bodyFile" -s $url 2>&1
  } else {
    $result = & curl.exe -b $cookieFile -H "X-WP-Nonce: $nonce" -H "Content-Type: application/json" -X $Method -s $url 2>&1
  }
  return $result
}

# ============================================
Write-Output "`n=== Step 2: Create Landing Page ==="
$pageContent = @"
<h2>Darmowy poradnik - "Pierwsze zloto"</h2>
<div class="wp-block-group" style="background: linear-gradient(135deg, #1A202C, #2D3748); color: #fff; padding: 60px 20px; text-align: center; border-radius: 8px; margin-bottom: 30px;">
<h1 style="color: #F7FAFC; font-size: 2.5em; margin-bottom: 15px;">Darmowy poradnik - "Pierwsze zloto"</h1>
<p style="font-size: 1.3em; color: #CBD5E0; max-width: 700px; margin: 0 auto 25px;">Pobierz bezplatny poradnik i dowiedz sie, jak madrze zaczac inwestowac w zloto. Praktyczna wiedza dla poczatkujacych - bez zadnych zobowiazan.</p>
</div>
<div style="display: flex; gap: 30px; max-width: 1000px; margin: 0 auto; flex-wrap: wrap;">
<div style="flex: 1; min-width: 280px; background: #F7FAFC; padding: 30px; border-radius: 10px; border: 2px solid #2B6CB0;">
<h2 style="color: #2B6CB0; margin-top: 0;">Zapisz sie do newslettera i odbierz poradnik</h2>
<p style="font-size: 1.1em;">Podaj swoj adres e-mail, aby otrzymac <strong>darmowy poradnik PDF "Pierwsze zloto"</strong> oraz regularne informacje o rynku zlota inwestycyjnego.</p>
<form action="#" method="post" style="margin-top: 20px;">
<input type="email" name="email" placeholder="Twoj adres e-mail" required style="width: 100%; padding: 14px; border: 2px solid #CBD5E0; border-radius: 6px; font-size: 1em; margin-bottom: 12px; box-sizing: border-box;" />
<button type="submit" style="width: 100%; padding: 14px; background: #2B6CB0; color: #fff; border: none; border-radius: 6px; font-size: 1.1em; font-weight: bold; cursor: pointer;">Odbieram darmowy poradnik</button>
</form>
<p style="font-size: 0.85em; color: #718096; margin-top: 10px;">Szanujemy Twoja prywatnosc. Mozesz wypisac sie w kazdej chwili.</p>
</div>
<div style="flex: 1; min-width: 280px;">
<h2 style="color: #2D3748;">Co znajdziesz w poradniku?</h2>
<ul style="list-style: none; padding: 0;">
<li style="padding: 12px 0; border-bottom: 1px solid #E2E8F0;"><strong style="color: #2B6CB0;">Dlaczego zloto?</strong><br/>Poznaj fundamenty inwestowania w kruszec.</li>
<li style="padding: 12px 0; border-bottom: 1px solid #E2E8F0;"><strong style="color: #2B6CB0;">Sztabka czy moneta?</strong><br/>Porownanie dwoch glownych form zlota inwestycyjnego.</li>
<li style="padding: 12px 0; border-bottom: 1px solid #E2E8F0;"><strong style="color: #2B6CB0;">Od czego zaczac?</strong><br/>Praktyczne wskazowki dotyczace gramatury i budzetu.</li>
<li style="padding: 12px 0; border-bottom: 1px solid #E2E8F0;"><strong style="color: #2B6CB0;">Jak nie dac sie oszukac?</strong><br/>5 zasad bezpiecznego zakupu zlota.</li>
<li style="padding: 12px 0; border-bottom: 1px solid #E2E8F0;"><strong style="color: #2B6CB0;">Gdzie przechowywac?</strong><br/>Skrytka bankowa, domowy sejf czy skarbiec?</li>
<li style="padding: 12px 0;"><strong style="color: #2B6CB0;">Kiedy sprzedac?</strong><br/>Strategie wyjscia dla inwestorow.</li>
</ul>
</div>
</div>
<div style="text-align: center; margin: 40px 0;">
<a href="/" style="display: inline-block; padding: 14px 30px; background: #b8860b; color: #fff; border-radius: 6px; text-decoration: none; font-weight: bold;">Wroc do sklepu</a>
</div>
"@

$pageBody = @{
  title = "Darmowy poradnik - Pierwsze zloto"
  content = $pageContent
  slug = "poradnik-pierwsze-zloto"
  status = "publish"
} | ConvertTo-Json -Depth 5 -Compress

$pageResult = Call-API -Method POST -Endpoint "/wp-json/wp/v2/pages" -BodyJson $pageBody
Write-Output $pageResult

# ============================================
Write-Output "`n=== Step 3: Create Footer Newsletter Widget (footer1) ==="
$widgetHtml = @"
<div style="text-align: center; padding: 20px;">
  <h3 style="color: #2B6CB0; margin-bottom: 10px;">Zapisz sie i otrzymaj darmowy poradnik "Pierwsze zloto"</h3>
  <form id="footer-newsletter-form" action="#" method="post" style="max-width: 400px; margin: 0 auto;">
    <div style="display: flex; gap: 8px; justify-content: center;">
      <input type="email" name="email" placeholder="Twoj adres e-mail" required style="flex: 1; padding: 12px 16px; border: 2px solid #CBD5E0; border-radius: 6px; font-size: 1em; min-width: 200px;" />
      <button type="submit" style="padding: 12px 24px; background: #2B6CB0; color: #fff; border: none; border-radius: 6px; font-size: 1em; font-weight: bold; cursor: pointer; white-space: nowrap;">Zapisz sie</button>
    </div>
  </form>
  <p style="font-size: 0.8em; color: #718096; margin-top: 8px;">Szanujemy Twoja prywatnosc. Bez spamu.</p>
</div>
"@

$widgetBody = @{
  id_base = "custom_html"
  sidebar = "footer1"
  instance = @{
    encoded = $true
    raw = $false
    content = $widgetHtml
    title = "Newsletter"
  }
} | ConvertTo-Json -Depth 3 -Compress

$widgetResult = Call-API -Method POST -Endpoint "/wp-json/wp/v2/widgets" -BodyJson $widgetBody
Write-Output $widgetResult

# ============================================
Write-Output "`n=== Step 4: Install Newsletter Plugin ==="
$pluginBody = @{
  slug = "newsletter"
  status = "active"
} | ConvertTo-Json -Compress

$pluginResult = Call-API -Method POST -Endpoint "/wp-json/wp/v2/plugins" -BodyJson $pluginBody
Write-Output $pluginResult

Write-Output "`n=== SETUP COMPLETE ==="
