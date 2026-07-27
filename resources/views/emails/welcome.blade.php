<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>Welcome</title>
</head>

<body style="font-family: Arial, sans-serif; color: #1f2937; line-height: 1.6;">
    <h2>Welcome, {{ $user->name }}!</h2>
    <p>Your account on {{ config('app.name') }} has been created successfully.</p>
    <p>You can now start creating tasks and organizing your work.</p>
</body>

</html>
