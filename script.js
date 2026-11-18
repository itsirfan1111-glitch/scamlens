// 1. Grab the elements from the page using their ids
const checkBtn    = document.getElementById('checkBtn');
const userInput   = document.getElementById('userInput');
const resultBox   = document.getElementById('result');
const riskBadge   = document.getElementById('riskBadge');
const scoreText   = document.getElementById('scoreText');
const warningList = document.getElementById('warningList');

// 2. Run this code when the button is clicked
checkBtn.addEventListener('click', async function () {

    const text = userInput.value.trim();

    // Stop if the box is empty
    if (text === '') {
        alert('Please paste some text or a URL first.');
        return;
    }

    // Show a loading message on the button
    checkBtn.textContent = 'Checking...';
    checkBtn.disabled = true;

    try {
        // 3. Send the text to analyze.php
        const formData = new FormData();
        formData.append('text', text);

        const response = await fetch('analyze.php', {
            method: 'POST',
            body: formData
        });

        // 4. Read the JSON reply from PHP
        const data = await response.json();

        if (data.error) {
            alert(data.error);
        } else {
            showResult(data);
        }

    } catch (error) {
        alert('Something went wrong. Is Apache running?');
        console.log(error);
    }

    // Put the button back to normal
    checkBtn.textContent = 'Check for Scam';
    checkBtn.disabled = false;
});

// 5. Function that displays the result on the page
function showResult(data) {

    // Show the hidden result section
    resultBox.classList.remove('hidden');

    // Badge: text and color
    riskBadge.textContent = data.level + ' Risk';
    riskBadge.className = data.level.toLowerCase();   // low, medium or high

    // Score text
    scoreText.textContent = 'Risk score: ' + data.score + ' / 100';

    // Warning list: clear old items, then add new ones
    warningList.innerHTML = '';
    data.warnings.forEach(function (warning) {
        const li = document.createElement('li');
        li.textContent = warning;
        warningList.appendChild(li);
    });

    loadHistory();

    // Scroll down so the result is visible
    resultBox.scrollIntoView({ behavior: 'smooth' });
}
// 6. Load and show the recent scans
async function loadHistory() {
    try {
        const response = await fetch('history.php');
        const scans = await response.json();
        const historyList = document.getElementById('historyList');

        historyList.innerHTML = '';

        if (scans.length === 0) {
            historyList.innerHTML = '<li>No scans yet.</li>';
            return;
        }

        scans.forEach(function (scan) {
            const li = document.createElement('li');

            const textSpan = document.createElement('span');
            textSpan.textContent = scan.input_text;

            const tag = document.createElement('span');
            tag.textContent = scan.risk_level + ' (' + scan.score + ')';
            tag.className = 'tag ' + scan.risk_level.toLowerCase();

            li.appendChild(textSpan);
            li.appendChild(tag);
            historyList.appendChild(li);
        });
    } catch (error) {
        console.log(error);
    }
}

// Load the list when the page opens
loadHistory();
// 7. Example buttons: fill the text box when clicked
document.querySelectorAll('.example-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        userInput.value = btn.dataset.text;
        userInput.focus();
    });
});

// 8. Clear button: empty the box and hide the result
document.getElementById('clearBtn').addEventListener('click', function () {
    userInput.value = '';
    resultBox.classList.add('hidden');
    userInput.focus();
});