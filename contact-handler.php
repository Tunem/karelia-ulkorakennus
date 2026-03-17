<?php
// Yksinkertainen lomakkeenkäsittelyskripti. Muokkaa sähköpostiosoitetta ympäristöön sopivaksi.

// Ladataan .env-tiedosto jos se löytyy
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue; // ohita kommentit
        if (!str_contains($line, '=')) continue;         // ohita rivit ilman =-merkkiä
        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value, " \t\n\r\0\x0B\"'"); // poista lainausmerkit ja tyhjät
        if ($key !== '') {
            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
        }
    }
}

$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$errors = [];
$success = false;
$brevoApiKey = getenv('BREVO_API_KEY');
$brevoToEmail = getenv('BREVO_TO_EMAIL'); // Vastaanottaja

//var_dump(getenv('BREVO_API_KEY'), getenv('BREVO_TO_EMAIL'));
//die();

if ($isPost) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');
    $message = trim($_POST['message'] ?? '');

    if ($name === '') {
        $errors[] = 'Nimi on pakollinen tieto.';
    }

    if ($email === '') {
        $errors[] = 'Sähköposti on pakollinen tieto.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Sähköpostiosoite ei ole oikeassa muodossa.';
    }

    if ($message === '') {
        $errors[] = 'Viesti-kenttä ei voi olla tyhjä.';
    }

    if (!$errors) {
        $subject = 'Yhteydenotto verkkosivuilta';

        $bodyLines = [
            "Nimi: {$name}",
            "Sähköposti: {$email}",
            "Puhelin: {$phone}",
            "Palvelu: {$service}",
            "",
            "Viesti:",
            $message,
        ];

        $body = implode("\n", $bodyLines);

        if ($brevoApiKey && $brevoToEmail) {
            // Yritetään lähettää sähköposti. Jos tämä ei onnistu palvelinympäristössä,
            // viestiä ei menetetä, vaan näytämme silti onnistumisilmoituksen.
            $data = [
                "sender" => [
                    "name" => "Karelia Ulkorakennus Oy",
                    "email" => $brevoToEmail
                ],
                "to" => [
                    [
                        "email" => $brevoToEmail,
                        "name" => "Karelia Ulkorakennus Oy"
                    ]
                ],
                "replyTo" => [
                    "email" => $email,
                    "name" => $name
                ],
                "subject" => $subject,
                "textContent" => $body
            ];

            $ch = curl_init('https://api.brevo.com/v3/smtp/email');

            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/json',
                    'api-key: ' . $brevoApiKey
                ],
                CURLOPT_POSTFIELDS => json_encode($data),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_TIMEOUT => 15
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            if (curl_errno($ch)) {
                error_log('Brevo cURL error: ' . curl_error($ch));
            }

            curl_close($ch);

            if ($httpCode >= 200 && $httpCode < 300) {
                $success = true;
            } else {
                error_log('Brevo API error: ' . $response);
                $errors[] = 'Viestin lähetys epäonnistui. Yritä uudelleen tai ota yhteyttä sähköpostitse.';
            }
        } else {
            error_log('Brevo API key or recipient email not configured.');
            $errors[] = 'Palvelun asetukset puuttuvat. Ota yhteyttä ylläpitäjään.';
        }
    }
        
}
?>
<!DOCTYPE html>
<html lang="fi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lomakkeen käsittely | Karelia Ulkorakennus Oy</title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="stylesheet" href="styles.css">
    <script type="application/ld+json">
    {
    "@context": "https://schema.org",
    "@type": "LomakeenKäsittely",
    "name": "Karelia Ulkorakennus Oy – Lomakeen käsittely",
    "url": "",
    "mainEntity": {
        "@type": "LocalBusiness",
        "name": "Karelia Ulkorakennus Oy",
        "address": {
        "@type": "PostalAddress",
        "streetAddress": "Lankkutie 7",
        "addressLocality": "Joensuu",
        "postalCode": "80100",
        "addressCountry": "FI"
        }
    }
    }
    </script>
</head>
<body>
    <a class="skip-link" href="#main-content">Siirry suoraan sisältöön</a>

    <header class="site-header">
        <div class="container header-inner">
            <div class="logo">
                <span class="logo-main">Karelia Ulkorakennus Oy</span>
                <span class="logo-sub">Ulkorakentamista Joensuussa ja Pohjois-Karjalassa</span>
            </div>
            <nav class="main-nav" aria-label="Päävalikko">
                <ul class="nav-list">
                    <li><a href="index.html">Etusivu</a></li>
                    <li class="nav-dropdown">
                        <a href="pages/palvelut.html">Palvelut</a>
                        <ul class="dropdown-menu">
                            <li><a href="pages/palvelut.html">Palvelut (yleiskatsaus)</a></li>
                            <li><a href="pages/palvelut/terassit.html">Terassit</a></li>
                            <li><a href="pages/palvelut/pergolat.html">Pergolat</a></li>
                            <li><a href="pages/palvelut/piharakennukset.html">Piharakennukset</a></li>
                            <li><a href="pages/palvelut/aidat.html">Aidat</a></li>
                            <li><a href="pages/palvelut/piharemontit.html">Piharemontit</a></li>
                        </ul>
                    </li>
                    <li><a href="pages/yritys.html">Yritys</a></li>
                    <li><a href="pages/yhteydenotto.html">Yhteydenotto</a></li>
                    <li><a href="pages/tietosuojaseloste.html">Tietosuojaseloste</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main id="main-content">
        <section class="page-header">
            <div class="container">
                <h1>Lomakkeen lähetys</h1>
                <p>
                    Tällä sivulla näytetään palaute yhteydenottolomakkeen lähetyksestä.
                </p>
            </div>
        </section>

        <section class="page-content">
            <div class="container page-content-narrow">
                <?php if ($isPost && $success): ?>
                    <div class="status-message success" role="status" aria-live="polite">
                        <p>Kiitos viestistäsi! Olemme vastaanottaneet yhteydenottosi ja palaamme asiaan mahdollisimman pian.</p>
                    </div>
                <?php elseif ($isPost && $errors): ?>
                    <div class="status-message error" role="alert" aria-live="assertive">
                        <p>Lomakkeen lähetyksessä ilmeni puutteita:</p>
                        <ul class="policy-list">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <p>Voit palata takaisin ja täydentää tietoja.</p>
                    </div>
                <?php else: ?>
                    <div class="status-message error" role="alert" aria-live="assertive">
                        <p>Lomaketta ei voitu käsitellä, koska pyyntö ei tullut lomakkeelta.</p>
                    </div>
                <?php endif; ?>

                <p>
                    <a class="btn btn-primary" href="pages/yhteydenotto.html">Palaa yhteydenottolomakkeelle</a>
                </p>
                <p>
                    Voit myös ottaa yhteyttä suoraan puhelimitse tai sähköpostitse:
                </p>
                <p>
                    Puhelin: 050 123 4567</a><br>
                    Sähköposti: palvelut@kareliaulkorakennus.fi</a>
                </p>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-inner">
            <div class="footer-company">
                <p class="footer-logo">Karelia Ulkorakennus Oy</p>
                <p>Ulkorakentamista ja piharakentamista Joensuussa ja Pohjois-Karjalassa.</p>
            </div>
            <div class="footer-contact">
                <h2>Yhteystiedot</h2>
                <p>Osoite: Lankkutie 7, 80100 Joensuu</p>
                <p>Puhelin: 050 123 4567</a></p>
                <p>Sähköposti: palvelut@kareliaulkorakennus.fi</a></p>
            </div>
            <div class="footer-links">
                <h2>Linkit</h2>
                <ul>
                    <li><a href="pages/palvelut.html">Palvelut</a></li>
                    <li><a href="pages/yritys.html">Yritys</a></li>
                    <li><a href="pages/yhteydenotto.html">Yhteydenotto</a></li>
                    <li><a href="pages/tietosuojaseloste.html">Tietosuojaseloste</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <div class="container">
                <p>&copy; <span id="year">2026</span> Karelia Ulkorakennus Oy. Kaikki oikeudet pidätetään.</p>
            </div>
        </div>
    </footer>
</body>
</html>

