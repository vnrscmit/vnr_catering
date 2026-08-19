<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Canteen Credentials</title>
</head>

<body style="font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f7f6; margin: 0; padding: 20px;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: #f4f7f6;">
        <tr>
            <td align="center">
                <!-- Main Card Container -->
                <table width="550" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 16px; box-shadow: 0 8px 20px rgba(0,0,0,0.1); border-top: 8px solid #2E7D32; overflow: hidden;">
                    <tr>
                        <td style="padding: 40px 30px;">
                            <!-- Header with Icon -->
                            <table width="100%">
                                <tr>
                                  <td align="center" style="padding:0 20px 15px;">
                                        <img
                                            src="https://ahaar.vnrseeds.in/assets/images/ahaar_logo_login_3.png"
                                            alt="AHAAR Logo"
                                            width="180"
                                            border="0"
                                            style="display:block;border:0;outline:none;text-decoration:none;">
                                    </td>
                                </tr>
                            </table>

                            <!-- Divider Line -->
                            <hr style="border: 0; height: 2px; background: linear-gradient(to right, #2E7D32, #A5D6A7); margin: 20px 0;">

                            <!-- Greeting -->
                            <p style="font-size: 16px; color: #333; line-height: 1.6;">Dear <strong>{{ $user->first_name }}</strong>,</p>
                            <p style="font-size: 15px; color: #555; line-height: 1.6;">Your account for the office canteen portal has been created. Please find your login details below:</p>

                            <!-- Credentials Box (Visual Highlight) -->
                            <table width="100%" cellpadding="15" style="background-color: #F9FBE7; border-left: 6px solid #2E7D32; border-radius: 8px; margin: 25px 0;">
                               <tr>
    <td>
        <table>
            <tr>
                <td style="font-weight: bold; color: #333; width: 100px;">&#128279; Portal:</td>
                <td style="color: #1A73E8;"><a href="https://ahaar.vnrseeds.in/">https://ahaar.vnrseeds.in/</a></td>
            </tr>
            
            <tr>
                <td style="font-weight: bold; color: #333; width: 100px;">&#128241; App:</td>
                <td style="color: #1A73E8;">
                    <a href="https://ahaar.vnrseeds.in/AhaarLive.apk" target="_blank">
                        Download
                    </a>
                </td>
            </tr>
            
            <tr>
                <td style="font-weight: bold; color: #333; width: 100px;">&#128100; User ID:</td>
                <td style="color: #333;">{{ $user->mobile }}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; color: #333; width: 100px;">&#128273; Password:</td>
                <td style="color: #D32F2F; font-weight: 600; letter-spacing: 1px; background-color: #FFF3E0; padding: 4px 12px; border-radius: 20px; display: inline-block;">{{ $password }}</td>
            </tr>
        </table>
    </td>
</tr>
                            </table>

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
            </td>
        </tr>
    </table>
    </td>
    </tr>
    </table>
</body>

</html>