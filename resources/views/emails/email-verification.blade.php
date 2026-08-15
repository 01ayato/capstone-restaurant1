<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Email Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            color: #333;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #f97316;
            padding-bottom: 20px;
        }
        .header h1 {
            color: #f97316;
            margin: 0;
            font-size: 28px;
        }
        .content {
            line-height: 1.6;
            margin: 20px 0;
        }
        .code-block {
            background-color: #f4f4f4;
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            text-align: center;
            margin: 25px 0;
        }
        .code-block .code {
            font-size: 32px;
            font-weight: bold;
            color: #f97316;
            letter-spacing: 2px;
            font-family: monospace;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #eee;
            padding-top: 20px;
            font-size: 12px;
            color: #999;
        }
        .warning {
            background-color: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 4px;
            padding: 10px;
            margin: 15px 0;
            font-size: 13px;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Smoke n' Grill</h1>
        </div>

        <div class="content">
            <p>Hello {{ $user->name }},</p>

            <p>Thank you for registering with Smoke n' Grill! To complete your registration and verify your email address, please use the verification code below:</p>

            <div class="code-block">
                <div class="code">{{ $code }}</div>
            </div>

            <p>This code will expire in 10 minutes. If you did not request this verification, please ignore this email.</p>

            <div class="warning">
                <strong>⚠️ Security Note:</strong> Never share your verification code with anyone. Our support team will never ask for this code.
            </div>

            <p>Best regards,<br>
            <strong>The Smoke n' Grill Team</strong></p>
        </div>

        <div class="footer">
            <p>© {{ date('Y') }} Smoke n' Grill. All rights reserved.</p>
            <p>If you need help, please contact us at smokengrill143@outlook.ph</p>
        </div>
    </div>
</body>
</html>
