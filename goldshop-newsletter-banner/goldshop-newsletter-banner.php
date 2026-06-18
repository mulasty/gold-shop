<?php
/**
 * Plugin Name: Gold Shop - Newsletter Banner
 * Description: Banner popup zachecajacy do zapisu na newsletter
 * Version: 1.0
 * Author: MulaGroup
 */

if (!defined('ABSPATH')) exit;

function gs_newsletter_banner_enqueue() {
    if (is_admin()) return;
    ?>
    <style>
    #gs-newsletter-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        background: linear-gradient(135deg, #1A202C, #2D3748);
        color: #fff;
        padding: 16px 20px;
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 20px;
        flex-wrap: wrap;
        box-shadow: 0 -4px 20px rgba(0,0,0,0.3);
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    }
    #gs-newsletter-banner p {
        margin: 0;
        font-size: 1.05em;
        color: #EDF2F7;
    }
    #gs-newsletter-banner p strong {
        color: #F7FAFC;
    }
    #gs-newsletter-banner form {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    #gs-newsletter-banner input[type="email"] {
        padding: 10px 16px;
        border: 2px solid #4A5568;
        border-radius: 6px;
        font-size: 1em;
        min-width: 220px;
        background: #F7FAFC;
        color: #1A202C;
    }
    #gs-newsletter-banner button {
        padding: 10px 20px;
        background: #2B6CB0;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 1em;
        font-weight: bold;
        cursor: pointer;
        white-space: nowrap;
    }
    #gs-newsletter-banner button:hover {
        background: #215387;
    }
    #gs-newsletter-banner .gs-close {
        background: none;
        border: none;
        color: #718096;
        font-size: 1.4em;
        cursor: pointer;
        padding: 0 8px;
        line-height: 1;
    }
    #gs-newsletter-banner .gs-close:hover {
        color: #fff;
    }
    </style>
    <div id="gs-newsletter-banner">
        <p><strong>Zapisz sie do newslettera i odbierz darmowy poradnik PDF</strong> &bdquo;Pierwsze zloto&rdquo;</p>
        <form id="gs-banner-form" action="#" method="post">
            <input type="email" name="gs_email" placeholder="Twoj adres e-mail" required />
            <button type="submit">Zapisz sie</button>
        </form>
        <button class="gs-close" onclick="document.getElementById('gs-newsletter-banner').style.display='none'" title="Zamknij">&times;</button>
    </div>
    <script>
    document.getElementById('gs-banner-form').addEventListener('submit', function(e) {
        e.preventDefault();
        var email = this.querySelector('input[name=gs_email]').value;
        if (email) {
            fetch('<?php echo admin_url("admin-ajax.php"); ?>', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=gs_newsletter_subscribe&email=' + encodeURIComponent(email)
            }).then(function(r) { return r.json(); })
              .then(function(data) {
                  if (data.success) {
                      document.getElementById('gs-newsletter-banner').innerHTML = '<p style="font-size:1.1em;padding:10px;"><strong>Dziekujemy za zapis!</strong> Sprawdz swoja skrzynke e-mail, aby odebrac poradnik PDF.</p>';
                  } else {
                      alert('Wystapil blad. Sprobuj ponownie.');
                  }
              });
        }
    });
    </script>
    <?php
}
add_action('wp_footer', 'gs_newsletter_banner_enqueue');

function gs_newsletter_ajax_subscribe() {
     = sanitize_email(['email']);
    if (!is_email()) {
        wp_send_json_error('Nieprawidlowy adres e-mail.');
    }
    // Store in options for newsletter plugin integration
     = get_option('gs_newsletter_subscribers', array());
    if (!in_array(, )) {
        [] = ;
        update_option('gs_newsletter_subscribers', );
    }
    wp_send_json_success(array('message' => 'Zapisano pomyslnie!'));
}
add_action('wp_ajax_gs_newsletter_subscribe', 'gs_newsletter_ajax_subscribe');
add_action('wp_ajax_nopriv_gs_newsletter_subscribe', 'gs_newsletter_ajax_subscribe');
