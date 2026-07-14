<!DOCTYPE html>
<html>

<head>
    <title>User Credentials</title>
</head>

<body>

    <h2>Welcome {{ $user->first_name }}</h2>

    <p>Your account has been created successfully.</p>

    <table border="1" cellpadding="8" cellspacing="0">
        <tr>
            <td><strong>Email</strong></td>
            <td>{{ $user->email }}</td>
        </tr>

        <tr>
            <td><strong>Password</strong></td>
            <td>{{ $password }}</td>
        </tr>
    </table>

    <br>

    <p>Please login and change your password after first login.</p>

</body>

</html>