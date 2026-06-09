<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Quick Lease Booking</title>
</head>
<body style="margin:0; padding:0; background:#f4f6f8; font-family:Arial,Helvetica,sans-serif;">

    <table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f6f8;">
        <tr>
            <td align="center">

                <!-- MAIN CONTAINER -->
                <table width="800" cellpadding="0" cellspacing="0" style="background:#ffffff; border-collapse:collapse;">

                    <!-- HEADER -->
                    <tr>
                        <td>
                            <img src="{{ config('app.url') }}email_images/header_new.png"
                                 alt="Quick Lease"
                                 style="width:100%; display:block;">
                        </td>
                    </tr>

                    <!-- GREETING -->
                    <tr>
                        <td style="background:#f6f7fa; padding:20px; text-align:left;">
                            <h2 style="margin:0; color:#401a89;">Dear Valued Customer</h2>
                            <p style="margin:5px 0 0; color:#401a89; font-size:15px;">
                                Greetings from Quick Lease Car Rental!
                            </p>
                        </td>
                    </tr>

                    <!-- BANNER -->
                    <tr>
                        <td>
                            <img src="{{ config('app.url') }}email_images/new_banner.png"
                                 alt="Banner"
                                 style="width:100%; display:block;">
                        </td>
                    </tr>

                    <!-- ORDER -->
                    <tr>
                        <td style="background:#f2eef9; padding:15px;">
                            <strong style="color:#401a89; font-size:16px;">
                                Order No: #{{ $bookingDetail['order_number'] }}
                            </strong>
                        </td>
                    </tr>

                    <!-- DETAILS -->
                    <tr>
                        <td style="padding:20px;">
                            <table width="100%" cellpadding="6" cellspacing="0"
                                   style="border:1px solid #eaeaea; border-radius:6px;">

                                <tr>
                                    <td colspan="2" style="color:#401a89; font-size:18px; padding-bottom:10px;">
                                        <strong>Booking Information</strong>
                                    </td>
                                </tr>

                                <tr>
                                    <td width="35%" style="color:#777;">Product Title</td>
                                    <td><strong>{{ $bookingDetail['product_title'] }}</strong></td>
                                </tr>

                                <tr>
                                    <td style="color:#777;">First Name</td>
                                    <td><strong>{{ $bookingDetail['first_name'] }}</strong></td>
                                </tr>

                                <tr>
                                    <td style="color:#777;">Last Name</td>
                                    <td><strong>{{ $bookingDetail['last_name'] }}</strong></td>
                                </tr>

                                @if($bookingDetail['change_status'] && !empty($bookingDetail['booking_status']))
                                <tr>
                                    <td style="color:#777;">Booking Status</td>
                                    <td><strong>{{ $bookingDetail['booking_status'] }}</strong></td>
                                </tr>
                                @endif

                                @if(!$bookingDetail['change_status'])
                                <tr>
                                    <td style="color:#777;">Email</td>
                                    <td><strong>{{ $bookingDetail['email'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Accept Terms</td>
                                    <td><strong>{{ $bookingDetail['accept_terms'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Valid Driving License</td>
                                    <td><strong>{{ $bookingDetail['valid_driving_license'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Total Days</td>
                                    <td><strong>{{ $bookingDetail['total_days'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Payment Type</td>
                                    <td><strong>{{ $bookingDetail['payment_type'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Payment Status</td>
                                    <td><strong>{{ $bookingDetail['payment_status'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Payment Process</td>
                                    <td><strong>{{ $bookingDetail['card_payment'] }}</strong></td>
                                </tr>

                                @if(!empty($bookingDetail['booking_page_slug']))
                                <tr>
                                    <td style="color:#777;">Referrer</td>
                                    <td><strong>{{ $bookingDetail['booking_page_slug'] }}</strong></td>
                                </tr>
                                @endif

                                @if(!empty($bookingDetail['car_month']))
                                <tr>
                                    <td style="color:#777;">Car Months</td>
                                    <td><strong>{{ $bookingDetail['car_month'] }}</strong></td>
                                </tr>
                                @endif

                                @if(!empty($bookingDetail['car_monthly_price']))
                                <tr>
                                    <td style="color:#777;">Car Monthly Price</td>
                                    <td><strong>{{ $bookingDetail['car_monthly_price'] }}</strong></td>
                                </tr>
                                @endif

                                <tr>
                                    <td style="color:#777;">Phone Number</td>
                                    <td><strong>{{ $bookingDetail['phone_number'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Pickup City</td>
                                    <td><strong>{{ $bookingDetail['pickup_city'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Pickup Address</td>
                                    <td><strong>{{ $bookingDetail['pickup_address'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Pickup DateTime</td>
                                    <td><strong>{{ $bookingDetail['pickup_date_time'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Return City</td>
                                    <td><strong>{{ $bookingDetail['return_city'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Return Address</td>
                                    <td><strong>{{ $bookingDetail['return_address'] }}</strong></td>
                                </tr>
                                <tr>
                                    <td style="color:#777;">Return DateTime</td>
                                    <td><strong>{{ $bookingDetail['return_date_time'] }}</strong></td>
                                </tr>
                                @endif

                            </table>
                        </td>
                    </tr>

                    @if(!empty($bookingDetail['description']))
                    <tr>
                        <td style="padding:15px; color:#401a89;">
                            <strong>Status Comments:</strong> {{ $bookingDetail['description'] }}
                        </td>
                    </tr>
                    @endif

                    <!-- EXTRAS -->
                    @if(!$bookingDetail['change_status'])
                    <tr>
                        <td style="padding:20px; background:#f2eef9;">
                            <strong style="color:#401a89;">Coverages / Extras Detail</strong>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:20px;">
                            <table width="100%" cellpadding="8" cellspacing="0" style="border-collapse:collapse;">
                                <tr style="background:#f2eef9;color:#401a89;font-weight:bold;">
                                    <td>Item</td>
                                    <td align="right">Unit</td>
                                    <td align="right">Days</td>
                                    <td align="right">Amount</td>
                                    <td align="right">VAT</td>
                                    <td align="right">Final</td>
                                </tr>

                                @foreach($bookingDetail['extras'] as $val)
                                    @if(isset($val['selected_locations']) && !empty($val['selected_locations']))
                                    <tr>
                                        <td>
                                            {{ $val['title'] }}<br>
                                            <small>Address: {{ $val['selected_locations']['title'] }} - {{ $val['selected_locations']['custom_address'] }}</small>
                                        </td>
                                        <td align="right">{{ $val['selected_locations']['price'] }}</td>
                                        <td align="right">-</td>
                                        <td align="right">{{ $val['selected_locations']['sum_price'] }}</td>
                                        <td align="right">{{ $val['selected_locations']['vat'] }}</td>
                                        <td align="right">{{ $val['selected_locations']['vat_price'] }}</td>
                                    </tr>
                                    @else
                                    <tr>
                                        <td>{{ $val['title'] }}</td>
                                        <td align="right">{{ $val['price'] }}</td>
                                        <td align="right">{{ $bookingDetail['total_days'] }}</td>
                                        <td align="right">{{ $val['sum_price'] }}</td>
                                        <td align="right">{{ $val['vat'] }}</td>
                                        <td align="right">{{ $val['vat_price'] }}</td>
                                    </tr>
                                    @endif
                                @endforeach

                            </table>
                        </td>
                    </tr>

                    <!-- SUMMARY -->
                    <tr>
                        <td style="background:#f2eef9; padding:20px; text-align:right;">
                            @if(!empty($bookingDetail['summary_total_amount']))
                                <p>Total Amount:
                                    <img src="{{ config('app.url') }}email_images/durham.png" width="12">
                                    <strong>{{ $bookingDetail['summary_total_amount'] }}</strong>
                                </p>
                            @endif

                            @if(!empty($bookingDetail['summary_total_vat']))
                                <p>Total VAT:
                                    <img src="{{ config('app.url') }}email_images/durham.png" width="12">
                                    <strong>{{ $bookingDetail['summary_total_vat'] }}</strong>
                                </p>
                            @endif

                            @if(!empty($bookingDetail['total_discount_incl_vat']))
                                <p>Total Discount incl VAT:
                                    <img src="{{ config('app.url') }}email_images/durham.png" width="12">
                                    <strong>{{ $bookingDetail['total_discount_incl_vat'] }}</strong>
                                </p>
                            @endif

                            @if(!empty($bookingDetail['partial_amount']))
                                <p style="font-size:16px;">
                                    <strong>Partial Amount:
                                        <img src="{{ config('app.url') }}email_images/durham.png" width="13">
                                        {{ $bookingDetail['partial_amount'] }}
                                    </strong>
                                </p>
                            @endif

                            <p style="font-size:18px; color:#401a89;">
                                <strong>Grand Total:
                                    <img src="{{ config('app.url') }}email_images/durham.png" width="13">
                                    {{ $bookingDetail['total_price'] }}
                                </strong>
                            </p>
                        </td>
                    </tr>
                    @endif

                    <!-- FOOTER -->
                    <tr>
                        <td style="padding:20px; font-size:13px; color:#401a89;">
                            <p>*By completing this booking you agree to our
                                <a href="https://quicklease.ae/terms-and-conditions/" style="color:#401a89;">Terms & Conditions</a>
                            </p>
                            <p>Billing: <a href="mailto:billing@quicklease.ae" style="color:#401a89;">billing@quicklease.ae</a></p>
                            <p>Feedback: <a href="mailto:contact@quicklease.ae" style="color:#401a89;">contact@quicklease.ae</a></p>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            <img src="{{ config('app.url') }}email_images/new_footer.png"
                                 style="width:100%; display:block;">
                        </td>
                    </tr>

                </table>

            </td>
        </tr>
    </table>

</body>
</html>
