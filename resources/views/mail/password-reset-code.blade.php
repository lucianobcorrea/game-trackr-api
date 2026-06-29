<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Password Reset</title>
</head>

<body>
    <h2>Password Reset</h2>

    <p>We received a request to reset your password.</p>

    <p>Use the verification code below to continue:</p>

    <h1 style="letter-spacing: 5px;">
        {{ $code }}
    </h1>

    <p>This code will expire in 15 minutes.</p>

    <p>If you did not request a password reset, you can safely ignore this email.</p>
</body>

</html>