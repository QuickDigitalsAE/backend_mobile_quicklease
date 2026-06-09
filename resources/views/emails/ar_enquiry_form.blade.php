<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>كويك ليس</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700;800&display=swap');

        * {
            padding: 0;
            margin: 0;
            box-sizing: border-box;
        }

        body {
            font-family: Arial, Helvetica, sans-serif;
            direction: rtl;
        }
    </style>
</head>

<body>
    <table
        style="width: 800px; margin: auto; border-spacing: 0; border-collapse: collapse; font-family: Arial, Helvetica, sans-serif">
        <tr>
            <td>
                <img src="{{ config('app.url') }}/public/email_images/header_new.png" alt=""  style="width: 100%;">
            </td>
        </tr>
    </table>


    <table align="center" cellpadding="0" cellspacing="0" width="100%"
        style="max-width: 800px; background: #ffffff; margin: auto; border-spacing: 0; border-collapse: collapse;">
        @if($isAdmin)
        
        <!-- قسم الإشعارات -->
        <tr>
            <td style="background-color: #f2eef9; padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex;">
                        <td
                            style="color: #401a89; font-size: 18px; padding-right: 10px; font-family: Arial, Helvetica, sans-serif; margin: auto 0;">
                            <strong>{{ $enquiryDetail['updated'] ? 'تم تحديث نموذج ' . $enquiryDetail['form_type'] : 'تفاصيل نموذج ' . $enquiryDetail['form_type'] }}</strong>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        
        <tr>
            <td style="padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; align-items: center; justify-content: space-between; direction: rtl;">
                        <td style="color: #401a89; width: 60%;">
                            @if(!empty($enquiryDetail['company_name']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">اسم الشركة:</span>
                                <strong>{{ $enquiryDetail['company_name'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_name']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">اسم العميل:</span>
                                <strong>{{ $enquiryDetail['client_name'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_last_name']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">الكنية:</span>
                                <strong>{{ $enquiryDetail['client_last_name'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_phone']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">رقم الهاتف:</span>
                                <strong>{{ $enquiryDetail['client_phone'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_email']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">البريد الإلكتروني:</span>
                                <strong>{{ $enquiryDetail['client_email'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['from_datetime']) && $enquiryDetail['from_datetime'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto; text-align: right;">
                                <span style="color: #6e6e6e;">من تاريخ ووقت:</span>
                                <strong>{{ $enquiryDetail['from_datetime'] }}</strong>
                            </p>
                            @endif
                            
                            @if(!empty($enquiryDetail['to_datetime']) && $enquiryDetail['to_datetime'] != null)
                            <p
                                style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto; text-align: right;">
                                <span style="color: #6e6e6e;">إلى تاريخ ووقت:</span>
                                <strong>{{ $enquiryDetail['to_datetime'] }}</strong>
                            </p>
                            @endif

                            
                            @if(!empty($enquiryDetail['referer_page_slug']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">رابط الصفحة المرجعية:</span>
                                <strong>{{ $enquiryDetail['referer_page_slug'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['form_status']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">حالة النموذج:</span>
                                <strong>{{ $enquiryDetail['form_status'] }}</strong>
                            </p>
                            @endif
                        </td>
        
                        <td style="color: #401a89; width: 40%;">
                            @if(!empty($enquiryDetail['car_name']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">اسم السيارة:</span>
                                <strong>{{ $enquiryDetail['car_name'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['period']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">المدة:</span>
                                <strong>{{ $enquiryDetail['period'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['lease_to_own']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">تأجير للتمليك:</span>
                                <strong>{{ $enquiryDetail['lease_to_own'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_comments']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">تعليقات العميل:</span>
                                <strong>{{ $enquiryDetail['client_comments'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['country']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">الدولة:</span>
                                <strong>{{ $enquiryDetail['country'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['city']))
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px;">
                                <span style="color: #6e6e6e;">المدينة:</span>
                                <strong>{{ $enquiryDetail['city'] }}</strong>
                            </p>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>

        @else
        <tr>
            <td style="background-color: #f6f7fa; text-align: center; padding: 10px;">
                <p style="color: #401a89; font-size: 24px; font-weight: bold; margin: 0; font-family: Arial, Helvetica, sans-serif">
                    عزيزي {{ $enquiryDetail['client_name'] }} {{ $enquiryDetail['client_last_name'] }}،</p>
                <p style="color: #222; font-size: 16px; margin: 0; font-family: Arial, Helvetica, sans-serif; color: #401a89;">
                    {{ $enquiryDetail['updated'] ? 'تم تحديث نموذج ' . $enquiryDetail['form_type'] . '. يرجى مراجعة التفاصيل أدناه:' :
                                    'شكرًا لتقديم الطلب. سيتواصل معك فريقنا قريبًا.' }}
                </p>
            </td>
        </tr>
        
        <tr>
            <td style="padding: 20px;">
                <table width="100%" style="border-spacing: 0;">
                    <tr style="display: flex; align-items: center; justify-content: space-between;">
                        <td style=" color: #401a89; width: 60%;">
                            @if(!empty($enquiryDetail['company_name']) && $enquiryDetail['company_name'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">اسم الشركة:</span>
                                <strong>{{ $enquiryDetail['company_name'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_phone']) && $enquiryDetail['client_phone'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">رقم الهاتف:</span>
                                <strong>{{ $enquiryDetail['client_phone'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_email']) && $enquiryDetail['client_email'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">البريد الإلكتروني:</span>
                                <strong>{{ $enquiryDetail['client_email'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['referer_page_slug']) && $enquiryDetail['referer_page_slug'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">صفحة الإحالة:</span>
                                <strong>{{ $enquiryDetail['referer_page_slug'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['form_status']) && $enquiryDetail['form_status'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">حالة النموذج:</span>
                                <strong>{{ $enquiryDetail['form_status'] }}</strong>
                            </p>
                            @endif
                        </td>
                        <td style=" color: #401a89; width: 40%; margin-left: auto;">
                            @if(!empty($enquiryDetail['car_name']) && $enquiryDetail['car_name'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">اسم السيارة:</span>
                                <strong>{{ $enquiryDetail['car_name'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['period']) && $enquiryDetail['period'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">المدة:</span>
                                <strong>{{ $enquiryDetail['period'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['lease_to_own']) && $enquiryDetail['lease_to_own'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">تأجير للتمليك:</span>
                                <strong>{{ $enquiryDetail['lease_to_own'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['client_comments']) && $enquiryDetail['client_comments'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">ملاحظات العميل:</span>
                                <strong>{{ $enquiryDetail['client_comments'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['country']) && $enquiryDetail['country'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">الدولة:</span>
                                <strong>{{ $enquiryDetail['country'] }}</strong>
                            </p>
                            @endif
        
                            @if(!empty($enquiryDetail['city']) && $enquiryDetail['city'] != null)
                            <p style="font-family: Arial, Helvetica, sans-serif; padding-block: 10px; margin-inline: auto;">
                                <span style="color: #6e6e6e;">المدينة:</span>
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