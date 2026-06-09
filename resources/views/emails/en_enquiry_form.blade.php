<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick Lease</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap');
    </style>
    <style>
        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif
        }
    </style>
</head>

<body>
    <table
        style="width: 800px; margin: auto; border-spacing: 0; border-collapse: collapse; font-family: Arial, Helvetica, sans-serif">
        <tr>
            <td>
                <img src="{{ config('app.url') }}email_images/header_new.png" alt=""  style="width: 100%;">
            </td>
        </tr>
    </table>


    <table align="center" cellpadding="0" cellspacing="0" width="100%"
        style="max-width: 800px; background: #ffffff; margin: auto; border-spacing: 0; border-collapse: collapse;">
        @if($isAdmin)
        <!-- Notification Section -->
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 18px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <strong>{{ $enquiryDetail['updated'] ? $enquiryDetail['form_type'].' Form Updated Detail' :
                                        $enquiryDetail['form_type'].' Form Detail' }} </strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Vehicle Details -->
        <tr>
            <td style="padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; align-items: center; justify-content: space-between;">
                        <td style=" color: #401a89; width: 60%;">
                            @if(!empty($enquiryDetail['company_name']) && $enquiryDetail['company_name'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Company Name:</span>
                                <strong>{{ $enquiryDetail['company_name'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_name']) && $enquiryDetail['client_name'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Name:</span>
                                <strong>{{ $enquiryDetail['client_name'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_last_name']) && $enquiryDetail['client_last_name'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Last Name:</span>
                                <strong>{{ $enquiryDetail['client_last_name'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_phone']) && $enquiryDetail['client_phone'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Phone:</span>
                                <strong>{{ $enquiryDetail['client_phone'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_email']) && $enquiryDetail['client_email'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Email:</span>
                                <strong>{{ $enquiryDetail['client_email'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['from_datetime']) && $enquiryDetail['from_datetime'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">From DateTime:</span>
                                <strong>{{ $enquiryDetail['from_datetime'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['to_datetime']) && $enquiryDetail['to_datetime'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">To DateTime:</span>
                                <strong>{{ $enquiryDetail['to_datetime'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['referer_page_slug']) && $enquiryDetail['referer_page_slug'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Referer Page Slug:</span>
                                <strong>{{ $enquiryDetail['referer_page_slug'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['form_status']) && $enquiryDetail['form_status'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Form Status:</span>
                                <strong>{{ $enquiryDetail['form_status'] }}</strong>
                            </p>
                            @endif
                        </td>
                        <td style=" color: #401a89; width: 40%; margin-left: auto;">
                            @if(!empty($enquiryDetail['car_name']) && $enquiryDetail['car_name'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Car Name:</span>
                                <strong>{{ $enquiryDetail['car_name'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['period']) && $enquiryDetail['period'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Period:</span>
                                <strong>{{ $enquiryDetail['period'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['lease_to_own']) && $enquiryDetail['lease_to_own'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Lease to Own:</span>
                                <strong>{{ $enquiryDetail['lease_to_own'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_comments']) && $enquiryDetail['client_comments'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Comments:</span>
                                <strong>{{ $enquiryDetail['client_comments'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['country']) && $enquiryDetail['country'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Country:</span>
                                <strong>{{ $enquiryDetail['country'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['city']) && $enquiryDetail['city'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">City:</span>
                                <strong>{{ $enquiryDetail['city'] }}</strong>
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @else
        
        <!-- Notification Section -->
        <tr>
            <td style="background-color: #f6f7fa; text-align: center; padding: 10px;">
                <p style="color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    Dear {{ $enquiryDetail['client_name'] }} {{ $enquiryDetail['client_last_name'] }},</p>
                <p style="color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    {{ $enquiryDetail['updated'] ? $enquiryDetail['form_type'].' form has been updated. Please see details below:' :
                                    'Thank you for submitting a request. Our team will contact you soon.' }}
                </p>
            </td>
        </tr>
        
        <!-- Vehicle Details -->
        <tr>
            <td style="padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; align-items: center; justify-content: space-between;">
                        <td style=" color: #401a89; width: 60%;">
                            @if(!empty($enquiryDetail['company_name']) && $enquiryDetail['company_name'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Company Name:</span>
                                <strong>{{ $enquiryDetail['company_name'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_phone']) && $enquiryDetail['client_phone'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Phone:</span>
                                <strong>{{ $enquiryDetail['client_phone'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_email']) && $enquiryDetail['client_email'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Email:</span>
                                <strong>{{ $enquiryDetail['client_email'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['referer_page_slug']) && $enquiryDetail['referer_page_slug'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Referer Page Slug:</span>
                                <strong>{{ $enquiryDetail['referer_page_slug'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['form_status']) && $enquiryDetail['form_status'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Form Status:</span>
                                <strong>{{ $enquiryDetail['form_status'] }}</strong>
                            </p>
                            @endif
                        </td>
                        <td style=" color: #401a89; width: 40%; margin-left: auto;">
                            @if(!empty($enquiryDetail['car_name']) && $enquiryDetail['car_name'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Car Name:</span>
                                <strong>{{ $enquiryDetail['car_name'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['period']) && $enquiryDetail['period'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Period:</span>
                                <strong>{{ $enquiryDetail['period'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['lease_to_own']) && $enquiryDetail['lease_to_own'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Lease to Own:</span>
                                <strong>{{ $enquiryDetail['lease_to_own'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['client_comments']) && $enquiryDetail['client_comments'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Client Comments:</span>
                                <strong>{{ $enquiryDetail['client_comments'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['country']) && $enquiryDetail['country'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Country:</span>
                                <strong>{{ $enquiryDetail['country'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['city']) && $enquiryDetail['city'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">City:</span>
                                <strong>{{ $enquiryDetail['city'] }}</strong>
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        @endif
        <!-- Contact Section -->
        <tr>
            <td style="background-color: #f6f7fa;" class="dd">
                <table width="100%" style="border-spacing: 0;">
                    <tr>
                        <td>
                            <div style="margin-top: 20px;"></div>
                            <div
                                style="background: #fff; padding: 10px 10px; margin-bottom: 20px; display: flex; ">
                                <div
                                    style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 10px;">
                                    *By completing this booking, you agree to our
                                    <a href="https://quicklease.ae/terms-and-conditions/"
                                        style="color: #401a89;">Terms & Conditions</a>.
                                </div>
                            </div>
                            <div
                                style="background: #fff;  padding: 10px 10px;  margin-bottom: 20px; display: flex; ">
                                <div
                                    style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 10px;">
                                    For any clarification, please contact us on
                                    <a href="mailto:accounts@quicklease.ae"
                                        style="color: #401a89;">billing@quicklease.ae</a>.
                                </div>
                            </div>
                            <div
                                style="background: #fff; padding: 10px 10px; margin-bottom: 20px; display: flex; ">
                                <div
                                    style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 10px;">
                                    We value your feedback please do write to us your experience with us at
                                    <a href="mailto:feedback@quicklease.ae"
                                        style="color: #401a89;">contact@quicklease.ae</a>.
                                </div>
                            </div>
                        </td>

                    </tr>

                </table>
            </td>
        </tr>
        
    </table>



    <table style=" display: flex; width: 800px; margin: auto; border-collapse: collapse;">
        <tbody style="display: flex; margin: auto;">
            <tr>
                <td >
                    <img src="{{ config('app.url') }}email_images/new_footer.png" alt="Quicklease Logo">
                </td>
            </tr>
        </tbody>
    </table>
</body>

</html>