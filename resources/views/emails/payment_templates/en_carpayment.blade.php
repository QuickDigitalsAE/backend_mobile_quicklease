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
    <table  style="width: 800px; margin: auto; border-spacing: 0; border-collapse: collapse; font-family: Arial, Helvetica, sans-serif">
        <tr>
            <td>
                <img src="{{ config('app.url') }}email_images/header_new.png" alt=""  style="width: 100%;">
            </td>
        </tr>
    </table>


    <table align="center" cellpadding="0" cellspacing="0" width="100%"
        style="max-width: 800px; background: #ffffff; margin: auto; border-spacing: 0; border-collapse: collapse;">
        @if($isAdmin)
        <tr>
            <td style="background-color: #f6f7fa; text-align: center; padding: 10px;">
                <p style="color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    Hello Admin,
                </p>
                <p style="color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    A new payment has been completed for a car rental booking. Below are the details:
                </p>
                            
            </td>
        </tr>
        @else
        <tr>
            <td style="background-color: #f6f7fa; text-align: center; padding: 10px;">
                <p style="color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    Dear {{ $bookingDetail['first_name'] }} {{ $bookingDetail['last_name'] }}
                </p>
                <p style="color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    We're excited to let you know that your payment for the car rental booking has been successfully completed.
                </p>
                            
            </td>
        </tr>
        @endif
        <!-- Banner Image -->
        <tr>
            <td>

                <img src="{{ config('app.url') }}email_images/new_payment_banner.png" alt="Service Due"
                    style="width: 100%; display: block;">
            </td>
        </tr>

        <!-- Notification Section -->
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 18px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <strong>Order No: #{{ $bookingDetail['order_number']}} - Payment Status: {{ $bookingDetail['payment_status']}} </strong>
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
                            
                            @if($isAdmin)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Transaction Id:</span>
                                <strong>{{ $bookingDetail['transaction_id'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">First Name:</span>
                                <strong>{{ $bookingDetail['first_name'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Last Name:</span>
                                <strong>{{ $bookingDetail['last_name'] }}</strong>
                            </p>
                            @endif
                            
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Product Title:</span>
                                <strong>{{ $bookingDetail['product_title'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Pickup City:</span>
                                <strong>{{ $bookingDetail['pickup_city'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Pickup Address:</span>
                                <strong>{{ $bookingDetail['pickup_address'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Pickup DateTime:</span>
                                <strong>{{ $bookingDetail['pickup_date_time'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Payment Type:</span>
                                <strong>{{ $bookingDetail['payment_type'] }}</strong>
                            </p>
                            
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Payment Process:</span>
                                <strong>{{ $bookingDetail['card_payment'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Total Days:</span>
                                <strong>{{ $bookingDetail['total_days'] }}</strong>
                            </p>
                            
                        </td>
                        <td style=" color: #401a89; width: 40%; margin-left: auto;">
                            
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Return City:</span>
                                <strong>{{ $bookingDetail['return_city'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Return Address:</span>
                                <strong>{{ $bookingDetail['return_address'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Return DateTime:</span>
                                <strong>{{ $bookingDetail['return_date_time'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">Booking Status:</span>
                                <strong>{{ $bookingDetail['booking_status'] }}</strong>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    @if(isset($bookingDetail['summary_total_amount']) && !empty($bookingDetail['summary_total_amount'])) 
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 14px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <span style="color: #6e6e6e;">Total Amount:</span>
                            <strong> <img src="{{ config('app.url') }}email_images/durham.png" alt=""  style="width: 12px; height: 12px;">{{ $bookingDetail['summary_total_amount'] }}</strong>
                        </td>
                    </tr>
                    @endif
                    @if(isset($bookingDetail['summary_total_vat']) && !empty($bookingDetail['summary_total_vat'])) 
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 14px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <span style="color: #6e6e6e;">Total Vat:</span>
                            <strong> <img src="{{ config('app.url') }}email_images/durham.png" alt=""  style="width: 12px; height: 12px;">{{ $bookingDetail['summary_total_vat'] }}</strong>
                        </td>
                    </tr>
                    @endif
                    @if(isset($bookingDetail['total_discount_incl_vat']) && !empty($bookingDetail['total_discount_incl_vat'])) 
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 14px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <span style="color: #6e6e6e;">Total Discount incl Vat:</span>
                            <strong> <img src="{{ config('app.url') }}email_images/durham.png" alt=""  style="width: 12px; height: 12px;">{{ $bookingDetail['total_discount_incl_vat'] }}</strong>
                        </td>
                    </tr>
                    @endif
                    @if(isset($bookingDetail['partial_amount']) && !empty($bookingDetail['partial_amount']) && $bookingDetail['partial_amount'] != 0)
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 16px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <strong>Partial Amount: <img src="{{ config('app.url') }}email_images/durham.png" alt=""  style="width: 13px; height: 13px;">{{ $bookingDetail['partial_amount'] }}</strong>
                        </td>
                    </tr>
                    @endif
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 18px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            <strong>Grand Total: <img src="{{ config('app.url') }}email_images/durham.png" alt=""  style="width: 13px; height: 13px;">{{ $bookingDetail['total_price'] }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    
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
        
         <!-- Footer Section -->
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; ">
                        <td
                            style="color: #401a89; font-size: 16px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0 auto 0;">
                            @if($isAdmin)
                            <strong>
                                Please log into the admin panel to review the full details.
                            </strong>
                            @else
                            <strong>
                                You will receive a confirmation message shortly with further details.
                            </strong>
                            @endif
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