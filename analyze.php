<?php
// Tell the browser we are sending back JSON data
header('Content-Type: application/json');
require 'db.php';
// 1. Get the text sent from JavaScript
$text = isset($_POST['text']) ? trim($_POST['text']) : '';

// If nothing was typed, stop here
if ($text === '') {
    echo json_encode(['error' => 'Please paste some text or a URL first.']);
    exit;
}

// Lowercase copy, so "URGENT" and "urgent" are treated the same
$lower = strtolower($text);

$score = 0;
$warnings = [];

// 2. Helper function: does the text contain any word from a list?
function containsAny($haystack, $words) {
    foreach ($words as $word) {
        if (strpos($haystack, $word) !== false) {
            return true;
        }
    }
    return false;
}

// 3. RULE 1: Urgency pressure (+15)
$urgencyWords = ['act now', 'urgent', 'immediately', 'last chance', 'expires today',
                 'account blocked', 'account suspended', 'within 24 hours', 'hurry',
                 'is blocked', 'will be blocked', 'is suspended', 'will be suspended',
                 'update kyc', 'verify now', 'click now', 'kyc now'];
if (containsAny($lower, $urgencyWords)) {
    $score += 15;
    $warnings[] = 'Urgency pressure: scammers rush you so you don\'t have time to think.';
}

// 4. RULE 2: Asks for sensitive information (+30)
$sensitiveWords = ['otp', 'password', 'pin number', 'your pin', 'cvv', 'card number',
                   'bank details', 'aadhaar', 'account number'];
if (containsAny($lower, $sensitiveWords)) {
    $score += 30;
    $warnings[] = 'Asks for sensitive information (OTP, password, card or bank details). Real companies never ask for these in a message.';
}

// 5. RULE 3: Asks for money upfront (+25)
$moneyWords = ['registration fee', 'security deposit', 'processing fee', 'pay first',
               'advance payment', 'refundable deposit', 'joining fee', 'training fee'];
if (containsAny($lower, $moneyWords)) {
    $score += 25;
    $warnings[] = 'Asks for money upfront. Genuine employers and prizes do not charge you a fee first.';
}

// 6. RULE 4: Too-good-to-be-true job or prize (+20)
$tooGoodWords = ['no experience', 'work from home', 'earn daily', 'earn per week',
                 'guaranteed income', 'you have won', 'lottery', 'congratulations',
                 'easy money', 'part time job'];
if (containsAny($lower, $tooGoodWords)) {
    $score += 20;
    $warnings[] = 'Too good to be true: big rewards for little or no effort is a classic scam sign.';
}

// 7. RULE 5: Contact only via WhatsApp or Telegram (+10)
$chatWords = ['whatsapp', 'telegram', 'signal app'];
if (containsAny($lower, $chatWords)) {
    $score += 10;
    $warnings[] = 'Pushes you to chat on WhatsApp or Telegram, where scammers are hard to trace.';
}
// 7b. RULE 6: Suspicious links (+20)

// Official domains of brands scammers often copy
$brands = [
    'paytm'    => ['paytm.com'],
    'sbi'      => ['sbi.co.in', 'onlinesbi.sbi'],
    'hdfc'     => ['hdfcbank.com'],
    'icici'    => ['icicibank.com'],
    'amazon'   => ['amazon.in', 'amazon.com'],
    'flipkart' => ['flipkart.com'],
    'google'   => ['google.com'],
    'paypal'   => ['paypal.com']
];

$shorteners = ['bit.ly', 'tinyurl.com', 'goo.gl', 't.co', 'cutt.ly',
               'is.gd', 'rb.gy', 'shorturl.at', 'tiny.cc'];

// Find links in the text (starting with http://, https:// or www.)
preg_match_all('/(?:https?:\/\/|www\.)[^\s]+/i', $text, $matches);
$urls = $matches[0];

// If the user pasted only a link like "bit.ly/win" (no spaces, has a dot)
if (count($urls) === 0 && strpos($text, ' ') === false && strpos($text, '.') !== false) {
    $urls[] = $text;
}

$linkProblems = [];

foreach ($urls as $url) {
    // Add http:// if missing, so parse_url() can read it
    $fullUrl = preg_match('/^https?:\/\//i', $url) ? $url : 'http://' . $url;
    $host = strtolower((string) parse_url($fullUrl, PHP_URL_HOST));

    if ($host === '') {
        continue;
    }

    // Check 1: IP address instead of a domain name
    if (filter_var($host, FILTER_VALIDATE_IP)) {
        $linkProblems[] = "The link $host uses a raw IP address instead of a website name.";
    }

    // Check 2: URL shortener
    if (in_array($host, $shorteners)) {
        $linkProblems[] = "The link uses a URL shortener ($host), which hides the real destination.";
    }

    // Check 3: @ symbol in the URL
    if (strpos($url, '@') !== false) {
        $linkProblems[] = 'The link contains an @ symbol, a trick to disguise the real website.';
    }

    // Check 4: too many hyphens in the domain
    if (substr_count($host, '-') >= 2) {
        $linkProblems[] = "The domain $host has many hyphens, common in fake lookalike sites.";
    }

    // Check 5: scam-prone domain endings
    if (preg_match('/\.(xyz|top|tk|ml|ga|cf|gq|click|work|loan)$/', $host)) {
        $linkProblems[] = "The domain $host ends in an extension often used by scam sites.";
    }

    // Check 6: pretends to be a known brand
    foreach ($brands as $brand => $officialDomains) {
        if (strpos($host, $brand) !== false) {
            $isOfficial = false;
            foreach ($officialDomains as $official) {
                // official if host is exactly the domain or a subdomain of it
                if ($host === $official || substr($host, -strlen('.' . $official)) === '.' . $official) {
                    $isOfficial = true;
                }
            }
            if (!$isOfficial) {
                $linkProblems[] = "The domain $host uses the name \"$brand\" but is not the official website.";
            }
        }
    }
}

// Add points only ONCE, however many problems were found
// Add points based on how many problems were found
$linkProblems = array_unique($linkProblems);

if (count($linkProblems) > 0) {
    // 15 points per problem, but never more than 45 from links alone
    $score += min(count($linkProblems) * 15, 45);
    foreach (array_unique($linkProblems) as $problem) {
        $warnings[] = 'Suspicious link: ' . $problem;
    }
}
// 8. Cap the score at 100
if ($score > 100) {
    $score = 100;
}

// 9. Convert the score into a risk level
if ($score < 30) {
    $level = 'Low';
} elseif ($score < 60) {
    $level = 'Medium';
} else {
    $level = 'High';
}

// 10. If nothing was found, add a friendly message
if (count($warnings) === 0) {
    $warnings[] = 'No common scam patterns found. Stay careful anyway.';
}

// 12. Save the scan in the database
$stmt = $conn->prepare('INSERT INTO scans (input_text, score, risk_level) VALUES (?, ?, ?)');
$stmt->bind_param('sis', $text, $score, $level);
$stmt->execute();
$stmt->close();

// 11. Send the result back as JSON
echo json_encode([
    'score'    => $score,
    'level'    => $level,
    'warnings' => $warnings
]);