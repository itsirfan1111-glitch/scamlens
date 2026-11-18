<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ScamLens - Check if it's a scam</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <header>
        <h1>Scam<span>Lens</span></h1>
        <p>Paste a suspicious message, link, or job offer. We'll tell you if it looks like a scam.</p>
    </header>

    <main>
        <section id="checker">
            <label for="userInput">Paste your text or URL here:</label>
            <textarea id="userInput" rows="8" placeholder="Example: Congratulations! You have won ₹50,000. Click http://bit.ly/claim-now and share your OTP to receive it."></textarea>
            <div class="examples">
                <span>Try an example:</span>
                <button type="button" class="example-btn" data-text="Congratulations! You have won a lottery. Act now, pay the registration fee and share your OTP on WhatsApp.">Lottery scam</button>
                <button type="button" class="example-btn" data-text="Hiring! Work from home, no experience needed, earn daily. Pay a refundable deposit and message us on Telegram to start.">Fake job</button>
                <button type="button" class="example-btn" data-text="http://paytm-kyc-update.xyz/login">Fake link</button>
                <button type="button" class="example-btn" data-text="Hi, are we meeting for lunch tomorrow?">Safe message</button>
            </div>
            <button id="checkBtn">Check for Scam</button>
            <button type="button" id="clearBtn">Clear</button>
        </section>

        <section id="result" class="hidden">
            <h2>Result</h2>

            <div id="riskBadge"></div>
            <p id="scoreText"></p>

            <h3>Warning signs found:</h3>
            <ul id="warningList"></ul>
        </section>
        <section id="history">
            <h2>Recent scans</h2>
            <ul id="historyList"></ul>
        </section>
    </main>

    <footer>
        <p>ScamLens is a college project. Results are only guidance, not a guarantee. If in doubt, don't click, don't pay, and don't share OTPs.</p>
    </footer>

    <script src="script.js"></script>
</body>
</html>