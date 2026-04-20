<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Welcome to Goway</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f4f4f4; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 30px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .header { background: #1a73e8; color: #ffffff; padding: 30px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; }
        .body { padding: 30px; color: #333; line-height: 1.6; }
        .body h2 { color: #1a73e8; }
        .footer { padding: 20px 30px; background: #f9f9f9; text-align: center; color: #999; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Welcome to Goway!</h1>
        </div>
        <div class="body">
            <h2>Hello {{ $user->first_name }} {{ $user->last_name }},</h2>
            <p>Thank you for registering with Goway. Your account has been successfully verified.</p>
            <p>You can now enjoy all the features of our platform. Whether you're booking a ride or hitting the road as a driver, we're glad to have you on board!</p>
            <p>If you have any questions, feel free to reach out to our support team.</p>
            <p>Best regards,<br>The Goway Team</p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} Goway. All rights reserved.
        </div>
    </div>
</body>
</html>
