<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PUROSIS</title>
</head>
<body style="margin: 5; padding:5; background-color: #f4f4f4; font-family: Arial, sans-serif;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color: #f4f4f4;">
        <tr>
            <td align="center" style="padding: 20px 0;">
                
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="600" style="background-color: #ffffff; width: 600px; max-width: 600px; border-collapse: collapse; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                    
                    <tr>
                        <td align="center">
                            <img src="{{ asset('purosis.png') }}" alt="Purosis" width="600" style="display: block; width: 100%; max-width: 600px; height: auto;">
                        </td>
                    </tr>

                @yield('content')

                <!-- Footer -->
                 <tr>
                        <td style="background-color: #222222; padding: 30px 20px; text-align: center;">
                            <p style="margin: 0 0 15px 0; font-family: Arial, sans-serif; font-size: 16px; color: #ffffff;">If you have any urgent questions, feel free to contact us</p>
                            
                            <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                                <tr>
                                    <td align="center" style="font-family: Arial, sans-serif; font-size: 15px; color: #ffffff;">
                                        <span style="display: inline-block; vertical-align: middle; color: #ffffff;"><img src="{{asset('public/images/emails-emails.png')}}"> info@purosis.com</span>
                                        <span style="display: inline-block; color: #666666; margin: 0 15px; vertical-align: middle;">|</span>
                                        <a href="tel:+917940308678" style="display: inline-block; vertical-align: middle; color: #ffffff;"><img src="{{asset('public/images/emails-phone.png')}}"> +91 7940308678</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color: #ffffff; padding: 20px; text-align: center; border-top: 1px solid #eeeeee;">
                            <p style="margin: 0; font-family: Arial, sans-serif; font-size: 12px; color: #999999;">All Rights Reserved. &copy;PUROSIS.</p>
                        </td>
                    </tr>

                </table>
                </td>
        </tr>
    </table>
</body>
</html>