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
    style="width: 800px; margin: auto; border-spacing: 0; border-collapse: collapse; font-family: Arial, Helvetica, sans-serif; direction: rtl;">
        <tr>
            <td>
                <img src="{{ config('app.url') }}email_images/header_new.png" alt=""  style="width: 100%;">
            </td>
        </tr>
    </table>



    <table align="center" cellpadding="0" cellspacing="0" width="100%"
        style="max-width: 800px; background: #ffffff; margin: auto; border-spacing: 0; border-collapse: collapse;">
       
        <!-- Greeting Section -->
        <tr>
            <td style="background-color: #f6f7fa; text-align: center; padding: 10px;">
                <p
                    style="text-align: center; color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    عزيزي العميل الكريم</p>
                <p
                    style="text-align: center; color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    تحياتنا من كويك ليس لتأجير السيارات!</p>
            </td>
        </tr>
        
        <!-- صورة البانر -->
        <tr>
            <td>
                <img src="{{ config('app.url') }}email_images/new_banner.png" alt="الخدمة المستحقة"
                    style="width: 100%; display: block;">
            </td>
        </tr>


        <!-- Notification Section -->
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex;">
                        <td
                            style="color: #401a89; font-size: 18px; padding-right: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0;">
                            <strong>رقم الطلب: #{{ $bookingDetail['order_number'] }} - تفاصيل الحجز</strong>
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
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">عنوان المنتج:</span>
                                <strong>{{ $bookingDetail['product_title'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">الاسم الأول:</span>
                                <strong>{{ $bookingDetail['first_name'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">اسم العائلة:</span>
                                <strong>{{ $bookingDetail['last_name'] }}</strong>
                            </p>
                            
                            @if($bookingDetail['change_status'] && !empty($bookingDetail['booking_status'])) 
                                <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto; text-align: right;" dir="rtl">
                                    <span style="color: #6e6e6e;">حالة الحجز:</span>
                                    <strong>{{ $bookingDetail['booking_status'] }}</strong>
                                </p>
                            @endif
                            @if(!$bookingDetail['change_status']) 
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">البريد الإلكتروني:</span>
                                <strong>{{ $bookingDetail['email'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">الموافقة على الشروط:</span>
                                <strong>{{ $bookingDetail['accept_terms'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">رخصة قيادة سارية:</span>
                                <strong>{{ $bookingDetail['valid_driving_license'] }}</strong>
                            </p>
                            
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto; text-align: right;">
                                <span style="color: #6e6e6e;">إجمالي الأيام:</span>
                                <strong>{{ $bookingDetail['total_days'] }}</strong>
                            </p>
                            
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">نوع الدفع:</span>
                                <strong>{{ $bookingDetail['payment_type'] }}</strong>
                            </p>
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">حالة الدفع:</span>
                                <strong>{{ $bookingDetail['payment_status'] }}</strong>
                            </p>
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto; direction: rtl; text-align: right;">
                                <span style="color: #6e6e6e;">طريقة الدفع:</span>
                                <strong>{{ $bookingDetail['card_payment'] }}</strong>
                            </p>
                            @endif
                        </td>
                        @if(!$bookingDetail['change_status']) 
                        <td style="color: #401a89; width: 40%; margin-left: auto;">
                            @if(!empty($bookingDetail['booking_page_slug']))
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">المُحيل:</span>
                                <strong>{{ $bookingDetail['booking_page_slug'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($bookingDetail['car_month']))
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">شهر السيارات:</span>
                                <strong>{{ $bookingDetail['car_month'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($bookingDetail['car_monthly_price']))
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">سعر السيارة الشهري:</span>
                                <strong>{{ $bookingDetail['car_monthly_price'] }}</strong>
                            </p>
                            @endif
                            
                            <p style="text-align: right; font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">رقم الهاتف:</span>
                                <strong>{{ $bookingDetail['phone_number'] }}</strong>
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
                            
                        </td>
                        @endif
                    </tr>
                </table>
            </td>
        </tr>
        @if(!empty($bookingDetail['description']))
            <tr>
                <td style="color: #401a89;">
                    <p
                        style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto; direction: rtl; text-align: right;">
                        <span style="color: #6e6e6e;">تعليقات الحالة:</span>
                        <strong>{{ $bookingDetail['description'] }}</strong>
                    </p>
                </td>        
            </tr>
        @endif

        @if(!$bookingDetail['change_status']) 
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex;">
                        <td
                            style="color: #401a89; font-size: 18px; padding-right: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0;">
                            <strong>تفاصيل التغطية:</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        <!-- Vehicle Details -->
        <tr>
            <td style="padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr>
                        <td style="width: 16%; text-align: right; color: #401a89; margin-left: auto;">
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 20px; margin-inline: auto;">
                                <strong>تفاصيل العنصر</strong>
                            </p>
                        </td>
                        <td style="width: 16%; text-align: right; color: #401a89; margin-left: auto;">
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <strong>سعر الوحدة</strong>
                            </p>
                        </td>
                        <td style="width: 16%; text-align: right; color: #401a89; margin-left: auto;">
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <strong>الأيام</strong>
                            </p>
                        </td>
                        <td style="width: 16%; text-align: right; color: #401a89; margin-left: auto;">
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <strong>المبلغ</strong>
                            </p>
                        </td>
                        <td style="width: 16%; text-align: right; color: #401a89; margin-left: auto;">
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <strong>ضريبة القيمة المضافة</strong>
                            </p>
                        </td>
                        <td style="width: 16%; text-align: right; color: #401a89; margin-left: auto;">
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <strong>المبلغ النهائي</strong>
                            </p>
                        </td>
                    </tr>
        
                    @foreach($bookingDetail['extras'] as $key => $val)
                        @if(isset($val['selected_locations']) && !empty($val['selected_locations']))
                            <tr style="padding-block: 15px;">
                                <td style="width: 16%; color: #6e6e6e;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['title'] }}</span>
                                        <br>
                                        <span><label>العنوان: </label> {{ $val['selected_locations']['title'] }} - {{ $val['selected_locations']['custom_address'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['selected_locations']['price'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span> - </span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['selected_locations']['sum_price'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['selected_locations']['vat'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['selected_locations']['vat_price'] }}</span>
                                    </p>
                                </td>
                            </tr>
                        @else
                            <tr style="padding-block: 15px;">
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['title'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['price'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $bookingDetail['total_days'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['sum_price'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['vat'] }}</span>
                                    </p>
                                </td>
                                <td style="width: 16%; text-align: right; color: #6e6e6e; margin-left: auto;">
                                    <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 30px; margin-inline: auto;">
                                        <span>{{ $val['vat_price'] }}</span>
                                    </p>
                                </td>
                            </tr>
                        @endif
                    @endforeach
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
            
                    @if(isset($bookingDetail['partial_amount']) && !empty($bookingDetail['partial_amount']))
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
        @endif
        <tr>
            <td style="background-color: #f6f7fa;" class="dd" dir="rtl">
                <table width="100%" style="border-spacing: 0;">
                    <tr>
                        <td>
                            <div style="margin-top: 20px;"></div>
        
                            <!-- Terms & Conditions -->
                            <div style="background: #fff; padding: 10px 10px; margin-bottom: 20px; display: flex; flex-direction: row-reverse;">
                                <div style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 10px auto 0;">
                                    *بإتمام هذا الحجز، فإنك توافق على 
                                    <a href="https://quicklease.ae/ar/terms-and-conditions/"
                                       style="color: #401a89;">الشروط والأحكام</a>.
                                </div>
                            </div>
        
                            <!-- Billing Contact -->
                            <div style="background: #fff; padding: 10px 10px; margin-bottom: 20px; display: flex; flex-direction: row-reverse;">
                                <div style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 10px auto 0;">
                                    لأي استفسار، يرجى التواصل معنا عبر البريد الإلكتروني 
                                    <a href="mailto:accounts@quicklease.ae" style="color: #401a89;">billing@quicklease.ae</a>.
                                </div>
                            </div>
        
                            <!-- Feedback Contact -->
                            <div style="background: #fff; padding: 10px 10px; margin-bottom: 20px; display: flex; flex-direction: row-reverse;">
                                <div style="color: #401a89; font-size: 14px; padding-inline: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 10px auto 0;">
                                    نحن نقدر ملاحظاتك. يرجى مشاركة تجربتك معنا عبر البريد الإلكتروني 
                                    <a href="mailto:feedback@quicklease.ae" style="color: #401a89;">contact@quicklease.ae</a>.
                                </div>
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

    </table>

    <table style=" display: flex; width: 800px; margin: auto; border-collapse: collapse; direction: rtl; text-align: right;">
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