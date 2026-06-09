<!DOCTYPE html>
<html lang="ar">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كويك ليس</title>
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
            font-family: Arial, Helvetica, sans-serif;
            direction: rtl; /* Right-to-left for Arabic */
            text-align: right;
        }
        
    </style>
</head>


<body>
    <table
        style="width: 800px; margin: auto; border-spacing: 0; border-collapse: collapse; font-family: Arial, Helvetica, sans-serif"
            dir="rtl">
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
                <p style="text-align: center; color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    مرحبًا مسؤول النظام،
                </p>
                <p style="text-align: center; color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    تم إكمال عملية دفع جديدة لحجز تأجير سيارة. فيما يلي التفاصيل:
                </p>
            </td>
        </tr>
        @else
        <tr>
            <td style="background-color: #f6f7fa; text-align: center; padding: 10px;">
                <p style="text-align: center; color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    عزيزي/عزيزتي {{ $bookingDetail['first_name'] }} {{ $bookingDetail['last_name'] }}
                </p>
                <p style="text-align: center; color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    يسعدنا إبلاغك بأنه قد تم إتمام عملية الدفع الخاصة بحجز تأجير السيارة بنجاح.
                </p>
            </td>
        </tr>
        @endif
    
        <!-- Banner Image -->
        <tr>
            <td>
                <img src="{{ config('app.url') }}email_images/new_payment_banner.png" alt="تم الدفع"
                    style="width: 100%; display: block;">
            </td>
        </tr>
    
        <!-- Notification Section -->
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr>
                        <td style="color: #401a89; font-size: 18px; padding-right: 10px; font-family: Arial, Helvetica, sans-serif;">
                            <strong>رقم الطلب: #{{ $bookingDetail['order_number'] }} - حالة الدفع: {{ $bookingDetail['payment_status'] }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    
         <!-- Vehicle Details -->
        <tr>
            <td style="padding: 20px; direction: rtl; text-align: right;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; align-items: center; justify-content: space-between;">
                        <td style="color: #401a89; width: 60%;">
                            @if($isAdmin)
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">الامعرّف المعاملة:</span>
                                <strong>{{ $bookingDetail['transaction_id'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">الاسم الأول:</span>
                                <strong>{{ $bookingDetail['first_name'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">اسم العائلة:</span>
                                <strong>{{ $bookingDetail['last_name'] }}</strong>
                            </p>
                            @endif
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">عنوان المنتج:</span>
                                <strong>{{ $bookingDetail['product_title'] }}</strong>
                            </p>
                            
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">مدينة الاستلام:</span>
                                <strong>{{ $bookingDetail['pickup_city'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">عنوان الاستلام:</span>
                                <strong>{{ $bookingDetail['pickup_address'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">تاريخ ووقت الاستلام:</span>
                                <strong>{{ $bookingDetail['pickup_date_time'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">حالة الحجز:</span>
                                <strong>{{ $bookingDetail['booking_status'] }}</strong>
                            </p>
                        </td>
                        <td style="color: #401a89; width: 40%; margin-left: auto;">
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">البريد الإلكتروني:</span>
                                <strong>{{ $bookingDetail['email'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">مدينة الإرجاع:</span>
                                <strong>{{ $bookingDetail['return_city'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">عنوان الإرجاع:</span>
                                <strong>{{ $bookingDetail['return_address'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">تاريخ ووقت الإرجاع:</span>
                                <strong>{{ $bookingDetail['return_date_time'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">نوع الدفع:</span>
                                <strong>{{ $bookingDetail['payment_type'] }}</strong>
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
                    <tr style="display: flex;">
                        <td style="color: #6e6e6e; font-size: 14px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0; direction: rtl; text-align: right;">
                            <strong>المبلغ الإجمالي: 
                                <img src="{{ config('app.url') }}email_images/durham.png" alt="" style="width: 12px; height: 12px;">
                                <span dir="ltr" style="direction: ltr; display: inline-block; text-align: left;">{{ $bookingDetail['summary_total_amount'] }}</span>
                            </strong>
                        </td>
                    </tr>
                    @endif
            
                    @if(isset($bookingDetail['summary_total_vat']) && !empty($bookingDetail['summary_total_vat'])) 
                    <tr style="display: flex;">
                        <td style="color: #6e6e6e; font-size: 14px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0; direction: rtl; text-align: right;">
                            <strong>إجمالي الضريبة: 
                                <img src="{{ config('app.url') }}email_images/durham.png" alt="" style="width: 12px; height: 12px;">
                                <span dir="ltr" style="direction: ltr; display: inline-block; text-align: left;">{{ $bookingDetail['summary_total_vat'] }}</span>
                            </strong>
                        </td>
                    </tr>
                    @endif
            
                    @if(isset($bookingDetail['total_discount_incl_vat']) && !empty($bookingDetail['total_discount_incl_vat'])) 
                    <tr style="display: flex;">
                        <td style="color: #6e6e6e; font-size: 14px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0; direction: rtl; text-align: right;">
                            <strong>إجمالي الخصم مع الضريبة: 
                                <img src="{{ config('app.url') }}email_images/durham.png" alt="" style="width: 12px; height: 12px;">
                                <span dir="ltr" style="direction: ltr; display: inline-block; text-align: left;">{{ $bookingDetail['total_discount_incl_vat'] }}</span>
                            </strong>
                        </td>
                    </tr>
                    @endif
            
                    @if(isset($bookingDetail['partial_amount']) && !empty($bookingDetail['partial_amount']) && $bookingDetail['partial_amount'] != 0)
                    <tr style="display: flex;">
                        <td style="color: #401a89; font-size: 16px; padding-left: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0; direction: rtl; text-align: right;">
                            <strong>المبلغ الجزئي: 
                                <img src="{{ config('app.url') }}email_images/durham.png" alt="" style="width: 13px; height: 13px;">
                                <span dir="ltr" style="direction: ltr; display: inline-block; text-align: left;">{{ $bookingDetail['partial_amount'] }}</span>
                            </strong>
                        </td>
                    </tr>
                    @endif
            
                    <tr style="display: flex;">
                        <td style="color: #401a89; font-size: 18px; padding-right: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0; direction: rtl; text-align: right;">
                            <strong>الإجمالي النهائي: 
                                <img src="{{ config('app.url') }}email_images/durham.png" alt="" style="width: 13px; height: 13px;">
                                <span dir="ltr" style="direction: ltr; display: inline-block; text-align: left;">{{ $bookingDetail['total_price'] }}</span>
                            </strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    
        <!-- Contact Section -->
        <tr>
            <td style="background-color: #f6f7fa;">
                <table width="100%" style="border-spacing: 0;">
                    <tr>
                        <td>
                            <div style="margin-top: 20px;"></div>
    
                            <div style="background: #fff; padding: 10px; margin-bottom: 20px; display: flex; flex-direction: row-reverse;">
                                <div style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif;">
                                    *بإتمامك هذا الحجز، فإنك توافق على
                                    <a href="https://test-quicklease-webiste.vercel.app/ar/terms-and-conditions/" style="color: #401a89;">الشروط والأحكام</a> الخاصة بنا.
                                </div>
                            </div>
    
                            <div style="background: #fff; padding: 10px; margin-bottom: 20px; display: flex; flex-direction: row-reverse;">
                                <div style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif;">
                                    لأي استفسار، يُرجى التواصل معنا عبر
                                    <a href="mailto:accounts@quicklease.ae" style="color: #401a89;">billing@quicklease.ae</a>.
                                </div>
                            </div>
    
                            <div style="background: #fff; padding: 10px; margin-bottom: 20px; display: flex; flex-direction: row-reverse;">
                                <div style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif;">
                                    نُقدّر ملاحظاتك، يُرجى مراسلتنا بتجربتك معنا على
                                    <a href="mailto:feedback@quicklease.ae" style="color: #401a89;">contact@quicklease.ae</a>.
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
                    <tr>
                        <td style="color: #401a89; font-size: 16px; padding-right: 10px; font-family: Arial, Helvetica, sans-serif;">
                            @if($isAdmin)
                            <strong>
                                الرجاء تسجيل الدخول إلى لوحة التحكم للاطلاع على التفاصيل الكاملة.
                            </strong>
                            @else
                            <strong>
                                ستتلقى رسالة تأكيد قريبًا تتضمن المزيد من التفاصيل.
                            </strong>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table style="display: flex; width: 800px; margin: auto; border-collapse: collapse;" dir="rtl">
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