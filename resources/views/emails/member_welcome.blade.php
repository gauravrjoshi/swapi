<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to Unnati</title>
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
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
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
        .card {
            background-color: #f8fafc;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 30px;
        }
        .card-title {
            font-size: 14px;
            font-weight: 700;
            color: #4f46e5;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 16px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            border-bottom: 1px dashed #e2e8f0;
            padding-bottom: 8px;
        }
        .info-row:last-child {
            margin-bottom: 0;
            border-bottom: none;
            padding-bottom: 0;
        }
        .info-label {
            color: #6b7280;
            font-weight: 500;
        }
        .info-value {
            color: #1f2937;
            font-weight: 600;
            word-break: break-all;
        }
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .btn {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #ffffff !important;
            text-decoration: none;
            padding: 14px 32px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            display: inline-block;
            box-shadow: 0 4px 10px rgba(79, 70, 229, 0.2);
            transition: all 0.2s ease;
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
            <h1>Welcome to Unnati!</h1>
            <p>Your account is ready</p>
        </div>
        <div class="content">
            <div class="greeting">Hello {{ $user->name }},</div>
            <div class="message">
                We're excited to have you on board! Your StatelyWorld Unnati account is now ready. You can log in using the credentials provided below.
            </div>

            <div class="card">
                <div class="card-title">Account Details</div>
                
                <table style="width: 100%; border-collapse: collapse;">
                    <tr style="border-bottom: 1px dashed #e2e8f0;">
                        <td style="padding: 10px 0; color: #6b7280; font-weight: 500;">Email Address</td>
                        <td style="padding: 10px 0; color: #1f2937; font-weight: 600; text-align: right;">{{ $user->email }}</td>
                    </tr>
                    <tr style="border-bottom: 1px dashed #e2e8f0;">
                        <td style="padding: 10px 0; color: #6b7280; font-weight: 500;">Password</td>
                        <td style="padding: 10px 0; color: #1f2937; font-weight: 600; text-align: right; font-family: monospace; font-size: 16px;">{{ $plainPassword }}</td>
                    </tr>
                    <tr>
                        <td style="padding: 10px 0; color: #6b7280; font-weight: 500;">Workspace UNID</td>
                        <td style="padding: 10px 0; color: #4f46e5; font-weight: 700; text-align: right;">{{ $user->unid }}</td>
                    </tr>
                </table>
            </div>

            <div class="message" style="margin-bottom: 0;">
                For your security, we strongly recommend changing your password as soon as you log in for the first time.
            </div>
        </div>
        <div class="footer">
            <p>This is an automated message from StatelyWorld.</p>
            <p>&copy; {{ date('Y') }} StatelyWorld. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
