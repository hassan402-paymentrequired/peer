<!DOCTYPE html>
<html>
<head>
    <title>Flutterwave Webhook Test</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .container { max-width: 800px; margin: 0 auto; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
        button { background: #007cba; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .result { margin-top: 20px; padding: 15px; background: #f5f5f5; border-radius: 4px; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Flutterwave Webhook Test</h1>
        <p>This tool simulates a Flutterwave transfer webhook to test your endpoint.</p>

        <form id="webhookForm">
            <div class="form-group">
                <label for="reference">Transaction Reference:</label>
                <input type="text" id="reference" name="reference" placeholder="STA_test_reference" required>
            </div>

            <div class="form-group">
                <label for="status">Transfer Status:</label>
                <select id="status" name="status" required>
                    <option value="SUCCESSFUL">SUCCESSFUL</option>
                    <option value="FAILED">FAILED</option>
                </select>
            </div>

            <div class="form-group">
                <label for="amount">Amount (NGN):</label>
                <input type="number" id="amount" name="amount" value="1000" required>
            </div>

            <button type="submit">🚀 Send Test Webhook</button>
        </form>

        <div id="result" class="result" style="display: none;"></div>

        <div style="margin-top: 30px;">
            <h3>📋 Instructions:</h3>
            <ol>
                <li>First, create a test transaction in your database with the reference above</li>
                <li>Set the status to match what you want to test (SUCCESSFUL/FAILED)</li>
                <li>Click "Send Test Webhook" to simulate Flutterwave's webhook</li>
                <li>Check the response and your application logs</li>
            </ol>

            <h3>🔍 What to Check:</h3>
            <ul>
                <li><strong>Response Status:</strong> Should be 200 for success</li>
                <li><strong>Transaction Status:</strong> Should update in database</li>
                <li><strong>Wallet Balance:</strong> Should be adjusted for successful transfers</li>
                <li><strong>Logs:</strong> Check <code>storage/logs/laravel.log</code> for detailed info</li>
            </ul>
        </div>
    </div>

    <script>
        document.getElementById('webhookForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const data = Object.fromEntries(formData);
            
            const resultDiv = document.getElementById('result');
            resultDiv.style.display = 'block';
            resultDiv.innerHTML = '<p>⏳ Sending webhook...</p>';
            
            try {
                const response = await fetch('/test-webhook/simulate-flutterwave', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify(data)
                });
                
                const result = await response.json();
                
                let statusClass = 'success';
                if (result.webhook_response.status_code !== 200) {
                    statusClass = 'error';
                }
                
                resultDiv.className = `result ${statusClass}`;
                resultDiv.innerHTML = `
                    <h3>📊 Test Results:</h3>
                    <p><strong>Webhook Status:</strong> ${result.webhook_response.status_code}</p>
                    <p><strong>Response:</strong></p>
                    <pre>${JSON.stringify(result.webhook_response.content, null, 2)}</pre>
                    
                    <h4>📤 Sent Payload:</h4>
                    <pre>${JSON.stringify(result.test_payload, null, 2)}</pre>
                `;
            } catch (error) {
                resultDiv.className = 'result error';
                resultDiv.innerHTML = `
                    <h3>❌ Error:</h3>
                    <p>${error.message}</p>
                `;
            }
        });
    </script>
</body>
</html>