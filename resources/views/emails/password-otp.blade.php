<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Password Reset OTP</title>
</head>

<body style="margin:0;padding:40px 0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td align="center">

                <table width="620" cellpadding="0" cellspacing="0"
                    style="background:#ffffff;border-radius:12px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,.08);">

                    <!-- Logo -->
                    <tr>
                        <td align="center" style="padding:35px 20px 15px;">
                            <img
                                src="https://ahaar-dev.vnrseeds.in/assets/images/ahaar_logo_login_3.png"
                                alt="AHAAR Logo"
                                width="180"
                                border="0"
                                style="display:block;border:0;outline:none;text-decoration:none;">
                        </td>
                    </tr>

                    <!-- Heading -->
                    <tr>
                        <td align="center" style="padding:5px 40px;">

                            <h2 style="margin:0;font-size:30px;color:#2E6115;">
                                Verify Your Identity
                            </h2>

                        </td>
                    </tr>

                    <!-- Description -->
                    <tr>
                        <td align="center"
                            style="padding:20px 55px 10px;color:#666;font-size:16px;line-height:28px;">

                            We received a request to reset your password.

                            Please use the One-Time Password (OTP) below to continue.

                        </td>
                    </tr>

                    <!-- OTP Box -->
                    <tr>
                        <td align="center" style="padding:20px;">

                            <table width="520"
                                style="background:#F5F7F8;border-radius:10px;padding:25px;">

                                <tr>
                                    <td align="center">

                                        <span style="
                                            font-size:42px;
                                            font-weight:bold;
                                            letter-spacing:12px;
                                            color:#222;">
                                            {{ $otp }}
                                        </span>

                                    </td>
                                </tr>

                            </table>

                        </td>
                    </tr>

                    <!-- Note -->
                    <tr>
                        <td align="center"
                            style="padding:15px 60px;color:#888;font-size:15px;line-height:26px;">

                            This OTP will expire in
                            <strong>2 minutes</strong>.

                            <br><br>

                            If you didn't request a password reset,
                            you can safely ignore this email.

                        </td>
                    </tr>

                    <!-- Divider -->
                    <tr>
                        <td style="padding:20px 40px;">

                            <hr style="border:none;border-top:1px solid #e8e8e8;">

                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center"
                            style="padding:0 40px 30px;color:#999;font-size:14px;">

                            <strong style="color:#2E6115;">
                                VNR Seeds Pvt. Ltd.
                            </strong>

                            <br><br>

                            This is an automated email. Please do not reply.

                            <br><br>

                            © {{ date('Y') }} VNR Seeds Pvt. Ltd. All Rights Reserved.

                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>

</html>