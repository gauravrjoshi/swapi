<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset OTP</title>
    <style>
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: #f6f9fc;
            margin: 0;
            padding: 0;
            color: #333333;
            -webkit-font-smoothing: antialiased;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #eef2f5;
        }
        .header {
            background: linear-gradient(135deg, #ec4899 0%, #d946ef 100%);
            padding: 40px 20px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 26px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .header p {
            margin: 8px 0 0 0;
            font-size: 15px;
            opacity: 0.9;
        }
        .content {
            padding: 40px 30px;
        }
        .greeting {
            font-size: 18px;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 16px;
        }
        .message {
            font-size: 15px;
            line-height: 1.6;
            color: #4b5563;
            margin-bottom: 30px;
        }
        .otp-container {
            text-align: center;
            margin: 36px 0;
        }
        .otp-card {
            background: #fdf2f8;
            border: 2px dashed #f472b6;
            border-radius: 12px;
            padding: 20px 40px;
            display: inline-block;
        }
        .otp-code {
            font-family: 'Courier New', Courier, monospace;
            font-size: 38px;
            font-weight: 800;
            color: #db2777;
            letter-spacing: 6px;
            margin: 0;
        }
        .otp-label {
            font-size: 12px;
            font-weight: 700;
            color: #ec4899;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-top: 8px;
        }
        .warning {
            font-size: 13px;
            color: #9ca3af;
            text-align: center;
            margin-top: 30px;
            border-top: 1px solid #f3f4f6;
            padding-top: 20px;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 30px;
            text-align: center;
            font-size: 13px;
            color: #9ca3af;
            border-top: 1px solid #eef2f5;
        }
        .footer p {
            margin: 4px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Reset Your Password</h1>
            <p>Verification Code Request</p>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $name }},</div>
            <div class="message">
                We received a request to reset the password for your StatelyWorld Unnati account. Use the 6-digit verification code (OTP) below to complete your password reset.
            </div>

            <div class="otp-container">
                <div class="otp-card">
                    <div class="otp-code">{{ $otp }}</div>
                    <div class="otp-label">Verification Code</div>
                </div>
            </div>

            <div class="message" style="text-align: center; font-size: 14px; color: #6b7280;">
                This verification code is valid for **60 minutes**. If you did not make this request, you can safely ignore this email and your password will remain unchanged.
            </div>

            <div class="warning">
                Never share this code with anyone. StatelyWorld representatives will never ask you for this verification code.
            </div>
        </div>
        <div class="footer">
            <p>This is an automated message from StatelyWorld.</p>
            <p>&copy; {{ date('Y') }} StatelyWorld. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
